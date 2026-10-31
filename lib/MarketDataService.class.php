<?php

/**
 * Amed Framework
 * Class: MarketDataService
 * File: MarketDataService.class.php
 * Description: High availability financial market data service with multi source failover and atomic caching
 */

class MarketDataService
{
    private const CACHE_SUBDIR = 'cache/market/';
    private const CACHE_FILENAME = 'market_rates.json';
    private const LOCK_FILENAME = 'refresh.lock';
    private const CACHE_TTL = 300;
    private const RETRY_COOLDOWN = 120;
    private const CONNECT_TIMEOUT = 2;
    private const FETCH_TIMEOUT = 4;
    private const REDIS_KEY = 'bulletin_market_rates_v1';

    private const INSTRUMENT_ORDER = [
        'GRAM_ALTIN',
        'USD_TRY',
        'EUR_TRY',
        'GBP_TRY',
        'XU100',
        'BTC_USD',
        'GRAM_GUMUS',
        'BRENT',
    ];

    private static bool $refreshScheduled = false;
    private static array $dictionaryCache = [];

    /**
     * Retrieve formatted market rates for the requested language.
     *
     * @param string $languageCode Current active language code
     * @return array{updated_at: int, items: array<int, array>}
     */
    public static function getRates(string $languageCode = 'en'): array
    {
        $cachedData = self::readCache();
        $currentTime = time();

        if ($cachedData === null) {
            $cachedData = self::getSeedRates();
            self::scheduleDeferredRefresh();
        } else {
            $updatedAt = (int) ($cachedData['updated_at'] ?? 0);
            $lastAttemptAt = (int) ($cachedData['last_attempt_at'] ?? 0);

            if (($currentTime - $updatedAt) > self::CACHE_TTL) {
                if (($currentTime - $lastAttemptAt) > self::RETRY_COOLDOWN) {
                    self::scheduleDeferredRefresh();
                }
            }
        }

        $cleanLang = preg_replace('/[^a-z]/', '', strtolower($languageCode));
        if ($cleanLang === '') {
            $cleanLang = 'en';
        }
        $langDictionary = self::getLanguageDictionary($cleanLang);

        $items = [];
        $rawItems = $cachedData['items'] ?? [];
        $seedItems = self::getSeedRates()['items'];

        foreach (self::INSTRUMENT_ORDER as $code) {
            $item = $rawItems[$code] ?? ($seedItems[$code] ?? null);
            if (!is_array($item)) {
                continue;
            }

            $priceRaw = (float) ($item['price_raw'] ?? 0.0);
            $changePctRaw = (float) ($item['change_pct_raw'] ?? 0.0);
            $diffRaw = (float) ($item['diff_raw'] ?? 0.0);
            $decimals = (int) ($item['decimals'] ?? 2);
            $prefix = (string) ($item['prefix'] ?? '');
            $i18nKey = (string) ($item['i18n_key'] ?? '');

            $direction = 'flat';
            if ($changePctRaw > 0.0001) {
                $direction = 'up';
            } elseif ($changePctRaw < -0.0001) {
                $direction = 'down';
            }

            $title = (string) ($langDictionary[$i18nKey] ?? $code);

            $items[] = [
                'code' => $code,
                'i18n_key' => $i18nKey,
                'title' => $title,
                'prefix' => $prefix,
                'price_raw' => $priceRaw,
                'price_formatted' => self::formatPrice($priceRaw, $decimals, $prefix, $cleanLang),
                'change_pct_raw' => $changePctRaw,
                'change_pct_formatted' => self::formatPercentage($changePctRaw, $cleanLang),
                'diff_raw' => $diffRaw,
                'diff_formatted' => self::formatDifference($diffRaw, $decimals, $prefix, $cleanLang),
                'direction' => $direction,
            ];
        }

        return [
            'updated_at' => (int) ($cachedData['updated_at'] ?? $currentTime),
            'items' => $items,
        ];
    }

    /**
     * Load and cache the localization dictionary for market instrument labels.
     */
    private static function getLanguageDictionary(string $cleanLang): array
    {
        if (isset(self::$dictionaryCache[$cleanLang])) {
            return self::$dictionaryCache[$cleanLang];
        }

        $baseDir = (defined('root_dir') ? root_dir : dirname(__DIR__) . '/') . 'mod/index/lang/';
        $langFile = $baseDir . $cleanLang . '.php';
        if (!is_file($langFile)) {
            $langFile = $baseDir . 'en.php';
        }

        $dictionary = [];
        if (is_file($langFile)) {
            $lang = [];
            require $langFile;
            if (isset($lang['index']['mod_news']['economy_news']) && is_array($lang['index']['mod_news']['economy_news'])) {
                $dictionary = $lang['index']['mod_news']['economy_news'];
            }
        }

        self::$dictionaryCache[$cleanLang] = $dictionary;
        return $dictionary;
    }

    /**
     * Refresh market data from upstream feeds.
     *
     * @param bool $force Force refresh ignoring cooldown and time limits
     * @return array
     */
    public static function refreshRates(bool $force = false): array
    {
        $cacheDir = self::getCacheDirectory();
        $lockFile = $cacheDir . self::LOCK_FILENAME;

        $lockHandle = @fopen($lockFile, 'c+');
        if (!$lockHandle) {
            return self::readCache() ?? self::getSeedRates();
        }

        if (!@flock($lockHandle, LOCK_EX | LOCK_NB)) {
            @fclose($lockHandle);
            return self::readCache() ?? self::getSeedRates();
        }

        @chmod($lockFile, 0666);

        $previous = self::readCache();
        $currentTime = time();

        if (!$force && $previous !== null) {
            $lastAttempt = (int) ($previous['last_attempt_at'] ?? 0);
            $updatedAt = (int) ($previous['updated_at'] ?? 0);

            if (($currentTime - $updatedAt) < self::CACHE_TTL) {
                @flock($lockHandle, LOCK_UN);
                @fclose($lockHandle);
                return $previous;
            }

            if (($currentTime - $lastAttempt) < self::RETRY_COOLDOWN) {
                @flock($lockHandle, LOCK_UN);
                @fclose($lockHandle);
                return $previous;
            }
        }

        $fetchResults = self::executeParallelFetch();
        $primaryFeedData = self::parsePrimaryPayload((string) ($fetchResults['primary'] ?? ''));
        $secondaryFeedData = self::parseSecondaryPayload((string) ($fetchResults['secondary'] ?? ''));
        $coinFeedData = self::parseCoinPayload((string) ($fetchResults['coin'] ?? ''));
        $backupCoinData = self::parseBackupCoinPayload((string) ($fetchResults['backup_coin'] ?? ''));

        $bankData = [];
        $needBankData = empty($primaryFeedData['USD']['Selling']) && empty($secondaryFeedData['TRY=']['last']);
        if ($needBankData) {
            $bankBody = self::fetchSingleUrl('https://www.tcmb.gov.tr/kurlar/today.xml');
            $bankData = self::parseBankXml($bankBody);
        }

        $liveCount = 0;
        $normalized = self::mergePayloads(
            $primaryFeedData,
            $secondaryFeedData,
            $coinFeedData,
            $backupCoinData,
            $bankData,
            $previous,
            $liveCount
        );

        $normalized['last_attempt_at'] = $currentTime;
        if ($liveCount > 0 || $previous === null) {
            $normalized['updated_at'] = $currentTime;
            $normalized['updated_iso'] = date('c', $currentTime);
        } else {
            $normalized['updated_at'] = (int) ($previous['updated_at'] ?? $currentTime);
            $normalized['updated_iso'] = (string) ($previous['updated_iso'] ?? date('c', $currentTime));
        }

        self::writeCache($normalized);

        @flock($lockHandle, LOCK_UN);
        @fclose($lockHandle);

        return $normalized;
    }

    /**
     * Schedule a deferred background refresh after response output has finished.
     */
    private static function scheduleDeferredRefresh(): void
    {
        if (self::$refreshScheduled) {
            return;
        }

        if (PHP_SAPI === 'cli') {
            return;
        }

        self::$refreshScheduled = true;

        register_shutdown_function(static function () {
            while (ob_get_level() > 0) {
                @ob_end_flush();
            }
            flush();

            if (session_status() === PHP_SESSION_ACTIVE) {
                @session_write_close();
            }

            if (function_exists('fastcgi_finish_request')) {
                fastcgi_finish_request();
            }

            MarketDataService::refreshRates(false);
        });
    }

    /**
     * Execute parallel request across multiple upstream network feeds.
     */
    private static function executeParallelFetch(): array
    {
        $urls = [
            'primary' => 'https://finans.truncgil.com/v4/today.json',
            'secondary' => 'https://quote.cnbc.com/quote-html-webservice/restQuote/symbolType/symbol?symbols=TRY%3D%7CEURTRY%3D%7CGBPTRY%3D%7C%40LCO.1%7CBTC.CM%3D%7CXAU%3D%7CXAG%3D%7C.XU100&requestMethod=itv&noform=1&partnerId=2&fund=1&exthrs=1&output=json&events=1',
            'coin' => 'https://api.binance.com/api/v3/ticker/24hr?symbol=BTCUSDT',
            'backup_coin' => 'https://api.coingecko.com/api/v3/simple/price?ids=bitcoin&vs_currencies=usd&include_24hr_change=true',
        ];

        $multiHandle = curl_multi_init();
        $handles = [];
        $responses = [];

        foreach ($urls as $feedKey => $feedUrl) {
            $singleHandle = curl_init($feedUrl);
            curl_setopt_array($singleHandle, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => self::CONNECT_TIMEOUT,
                CURLOPT_TIMEOUT => self::FETCH_TIMEOUT,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36',
            ]);
            curl_multi_add_handle($multiHandle, $singleHandle);
            $handles[$feedKey] = $singleHandle;
        }

        $active = null;
        do {
            $status = curl_multi_exec($multiHandle, $active);
            if ($active > 0) {
                curl_multi_select($multiHandle, 0.05);
            }
        } while ($active > 0 && $status === CURLM_OK);

        foreach ($handles as $feedKey => $singleHandle) {
            $httpCode = (int) curl_getinfo($singleHandle, CURLINFO_HTTP_CODE);
            $body = (string) curl_multi_getcontent($singleHandle);
            $responses[$feedKey] = ($httpCode >= 200 && $httpCode < 300) ? $body : '';
            curl_multi_remove_handle($multiHandle, $singleHandle);
            curl_close($singleHandle);
        }

        curl_multi_close($multiHandle);

        return $responses;
    }

    /**
     * Fetch a single URL with error checks.
     */
    private static function fetchSingleUrl(string $url): string
    {
        $singleHandle = curl_init($url);
        curl_setopt_array($singleHandle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => self::CONNECT_TIMEOUT,
            CURLOPT_TIMEOUT => self::FETCH_TIMEOUT,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36',
        ]);
        $response = (string) curl_exec($singleHandle);
        $code = (int) curl_getinfo($singleHandle, CURLINFO_HTTP_CODE);
        curl_close($singleHandle);

        return ($code >= 200 && $code < 300) ? $response : '';
    }

    /**
     * Parse primary market JSON feed.
     */
    private static function parsePrimaryPayload(string $json): array
    {
        if ($json === '') {
            return [];
        }
        $data = json_decode($json, true);
        return is_array($data) ? $data : [];
    }

    /**
     * Parse secondary quotes JSON feed.
     */
    private static function parseSecondaryPayload(string $json): array
    {
        if ($json === '') {
            return [];
        }
        $data = json_decode($json, true);
        if (!is_array($data)) {
            return [];
        }
        $quotes = $data['FormattedQuoteResult']['FormattedQuote'] ?? [];
        if (!is_array($quotes)) {
            return [];
        }

        $indexed = [];
        foreach ($quotes as $quote) {
            $symbolIdentifier = (string) ($quote['symbol'] ?? '');
            if ($symbolIdentifier !== '') {
                $indexed[$symbolIdentifier] = $quote;
            }
        }
        return $indexed;
    }

    /**
     * Parse digital coin ticker JSON feed.
     */
    private static function parseCoinPayload(string $json): ?array
    {
        if ($json === '') {
            return null;
        }
        $data = json_decode($json, true);
        if (!is_array($data) || empty($data['lastPrice'])) {
            return null;
        }
        return [
            'price' => (float) $data['lastPrice'],
            'diff' => (float) ($data['priceChange'] ?? 0.0),
            'change_pct' => (float) ($data['priceChangePercent'] ?? 0.0),
        ];
    }

    /**
     * Parse backup digital coin JSON feed.
     */
    private static function parseBackupCoinPayload(string $json): ?array
    {
        if ($json === '') {
            return null;
        }
        $data = json_decode($json, true);
        if (!is_array($data) || empty($data['bitcoin']['usd'])) {
            return null;
        }
        return [
            'price' => (float) $data['bitcoin']['usd'],
            'change_pct' => (float) ($data['bitcoin']['usd_24h_change'] ?? 0.0),
        ];
    }

    /**
     * Parse official bank XML currency rates.
     */
    private static function parseBankXml(string $xml): array
    {
        if ($xml === '') {
            return [];
        }
        $rates = [];
        try {
            $parsed = @simplexml_load_string($xml);
            if ($parsed && isset($parsed->Currency)) {
                foreach ($parsed->Currency as $currencyItem) {
                    $currencyCode = (string) $currencyItem['CurrencyCode'];
                    $sellingPrice = (float) str_replace(',', '.', (string) $currencyItem->ForexSelling);
                    if ($sellingPrice > 0) {
                        $rates[$currencyCode] = $sellingPrice;
                    }
                }
            }
        } catch (\Throwable) {
            return [];
        }
        return $rates;
    }

    /**
     * Merge multiple upstream sources into the normalized dictionary in strict wireframe slot order.
     */
    private static function mergePayloads(
        array $primaryData,
        array $secondaryData,
        ?array $coinData,
        ?array $backupCoinData,
        array $bankData,
        ?array $previous,
        int &$liveCount = 0
    ): array {
        $prevItems = $previous['items'] ?? [];
        $seed = self::getSeedRates()['items'];
        $liveCount = 0;

        // Pre-calculate USD_TRY so derived Ounce-to-Gram fallbacks can reference it
        $dollarPrice = 0.0;
        $dollarChange = 0.0;
        if (!empty($primaryData['USD']['Selling'])) {
            $dollarPrice = (float) $primaryData['USD']['Selling'];
            $dollarChange = (float) ($primaryData['USD']['Change'] ?? 0.0);
        } elseif (!empty($secondaryData['TRY=']['last'])) {
            $dollarPrice = (float) str_replace(',', '', (string) $secondaryData['TRY=']['last']);
            $dollarChange = (float) str_replace(['%', '+', 'UNCH'], '', (string) ($secondaryData['TRY=']['change_pct'] ?? '0'));
        } elseif (!empty($bankData['USD'])) {
            $dollarPrice = (float) $bankData['USD'];
            $dollarChange = 0.0;
        }

        $items = [];

        // Slot 1: GRAM_ALTIN
        $goldPrice = 0.0;
        $goldChange = 0.0;
        if (!empty($primaryData['GRA']['Selling'])) {
            $goldPrice = (float) $primaryData['GRA']['Selling'];
            $goldChange = (float) ($primaryData['GRA']['Change'] ?? 0.0);
        } elseif (!empty($secondaryData['XAU=']['last']) && $dollarPrice > 0.0) {
            $ounceGold = (float) str_replace(',', '', (string) $secondaryData['XAU=']['last']);
            $goldPrice = ($ounceGold * $dollarPrice) / 31.1034768;
            $goldChangeRaw = (float) str_replace(['%', '+', 'UNCH'], '', (string) ($secondaryData['XAU=']['change_pct'] ?? '0'));
            $goldChange = (((1.0 + ($goldChangeRaw / 100.0)) * (1.0 + ($dollarChange / 100.0))) - 1.0) * 100.0;
        }
        $items['GRAM_ALTIN'] = self::resolveInstrument(
            'GRAM_ALTIN',
            'gram_gold',
            '',
            2,
            $goldPrice,
            $goldChange,
            null,
            $prevItems['GRAM_ALTIN'] ?? null,
            $seed['GRAM_ALTIN'],
            $liveCount
        );

        // Slot 2: USD_TRY
        $items['USD_TRY'] = self::resolveInstrument(
            'USD_TRY',
            'dollar',
            '',
            4,
            $dollarPrice,
            $dollarChange,
            null,
            $prevItems['USD_TRY'] ?? null,
            $seed['USD_TRY'],
            $liveCount
        );

        // Slot 3: EUR_TRY
        $euroPrice = 0.0;
        $euroChange = 0.0;
        if (!empty($primaryData['EUR']['Selling'])) {
            $euroPrice = (float) $primaryData['EUR']['Selling'];
            $euroChange = (float) ($primaryData['EUR']['Change'] ?? 0.0);
        } elseif (!empty($secondaryData['EURTRY=']['last'])) {
            $euroPrice = (float) str_replace(',', '', (string) $secondaryData['EURTRY=']['last']);
            $euroChange = (float) str_replace(['%', '+', 'UNCH'], '', (string) ($secondaryData['EURTRY=']['change_pct'] ?? '0'));
        } elseif (!empty($bankData['EUR'])) {
            $euroPrice = (float) $bankData['EUR'];
            $euroChange = 0.0;
        }
        $items['EUR_TRY'] = self::resolveInstrument(
            'EUR_TRY',
            'euro',
            '',
            4,
            $euroPrice,
            $euroChange,
            null,
            $prevItems['EUR_TRY'] ?? null,
            $seed['EUR_TRY'],
            $liveCount
        );

        // Slot 4: GBP_TRY
        $poundPrice = 0.0;
        $poundChange = 0.0;
        if (!empty($primaryData['GBP']['Selling'])) {
            $poundPrice = (float) $primaryData['GBP']['Selling'];
            $poundChange = (float) ($primaryData['GBP']['Change'] ?? 0.0);
        } elseif (!empty($secondaryData['GBPTRY=']['last'])) {
            $poundPrice = (float) str_replace(',', '', (string) $secondaryData['GBPTRY=']['last']);
            $poundChange = (float) str_replace(['%', '+', 'UNCH'], '', (string) ($secondaryData['GBPTRY=']['change_pct'] ?? '0'));
        } elseif (!empty($bankData['GBP'])) {
            $poundPrice = (float) $bankData['GBP'];
            $poundChange = 0.0;
        }
        $items['GBP_TRY'] = self::resolveInstrument(
            'GBP_TRY',
            'sterling',
            '',
            4,
            $poundPrice,
            $poundChange,
            null,
            $prevItems['GBP_TRY'] ?? null,
            $seed['GBP_TRY'],
            $liveCount
        );

        // Slot 5: XU100
        $indexPrice = 0.0;
        $indexChange = 0.0;
        $indexDiff = null;
        if (!empty($secondaryData['.XU100']['last'])) {
            $indexPrice = (float) str_replace(',', '', (string) $secondaryData['.XU100']['last']);
            $indexChange = (float) str_replace(['%', '+', 'UNCH'], '', (string) ($secondaryData['.XU100']['change_pct'] ?? '0'));
            $rawIndexDiff = trim((string) ($secondaryData['.XU100']['change'] ?? ''));
            if ($rawIndexDiff !== '' && $rawIndexDiff !== 'UNCH') {
                $indexDiff = (float) str_replace([',', '+'], '', $rawIndexDiff);
            }
        } elseif (!empty($primaryData['XU100']['Selling'])) {
            $indexPrice = (float) $primaryData['XU100']['Selling'];
            $indexChange = (float) ($primaryData['XU100']['Change'] ?? 0.0);
        }
        $items['XU100'] = self::resolveInstrument(
            'XU100',
            'bist100',
            '',
            2,
            $indexPrice,
            $indexChange,
            $indexDiff,
            $prevItems['XU100'] ?? null,
            $seed['XU100'],
            $liveCount
        );

        // Slot 6: BTC_USD
        $bitcoinPrice = 0.0;
        $bitcoinChange = 0.0;
        $bitcoinDiff = null;
        if ($coinData !== null && $coinData['price'] > 0.0) {
            $bitcoinPrice = $coinData['price'];
            $bitcoinChange = $coinData['change_pct'];
            $bitcoinDiff = $coinData['diff'];
        } elseif (!empty($secondaryData['BTC.CM=']['last'])) {
            $bitcoinPrice = (float) str_replace(',', '', (string) $secondaryData['BTC.CM=']['last']);
            $bitcoinChange = (float) str_replace(['%', '+', 'UNCH'], '', (string) ($secondaryData['BTC.CM=']['change_pct'] ?? '0'));
            $rawBitcoinDiff = trim((string) ($secondaryData['BTC.CM=']['change'] ?? ''));
            if ($rawBitcoinDiff !== '' && $rawBitcoinDiff !== 'UNCH') {
                $bitcoinDiff = (float) str_replace([',', '+', '$'], '', $rawBitcoinDiff);
            }
        } elseif ($backupCoinData !== null && $backupCoinData['price'] > 0.0) {
            $bitcoinPrice = $backupCoinData['price'];
            $bitcoinChange = $backupCoinData['change_pct'];
        }
        $items['BTC_USD'] = self::resolveInstrument(
            'BTC_USD',
            'bitcoin',
            '$',
            0,
            $bitcoinPrice,
            $bitcoinChange,
            $bitcoinDiff,
            $prevItems['BTC_USD'] ?? null,
            $seed['BTC_USD'],
            $liveCount
        );

        // Slot 7: GRAM_GUMUS
        $silverPrice = 0.0;
        $silverChange = 0.0;
        if (!empty($primaryData['GUMUS']['Selling'])) {
            $silverPrice = (float) $primaryData['GUMUS']['Selling'];
            $silverChange = (float) ($primaryData['GUMUS']['Change'] ?? 0.0);
        } elseif (!empty($secondaryData['XAG=']['last']) && $dollarPrice > 0.0) {
            $ounceSilver = (float) str_replace(',', '', (string) $secondaryData['XAG=']['last']);
            $silverPrice = ($ounceSilver * $dollarPrice) / 31.1034768;
            $silverChangeRaw = (float) str_replace(['%', '+', 'UNCH'], '', (string) ($secondaryData['XAG=']['change_pct'] ?? '0'));
            $silverChange = (((1.0 + ($silverChangeRaw / 100.0)) * (1.0 + ($dollarChange / 100.0))) - 1.0) * 100.0;
        }
        $items['GRAM_GUMUS'] = self::resolveInstrument(
            'GRAM_GUMUS',
            'gram_silver',
            '',
            2,
            $silverPrice,
            $silverChange,
            null,
            $prevItems['GRAM_GUMUS'] ?? null,
            $seed['GRAM_GUMUS'],
            $liveCount
        );

        // Slot 8: BRENT
        $oilPrice = 0.0;
        $oilChange = 0.0;
        $oilDiff = null;
        if (!empty($secondaryData['@LCO.1']['last'])) {
            $oilPrice = (float) str_replace(',', '', (string) $secondaryData['@LCO.1']['last']);
            $rawChangePct = trim((string) ($secondaryData['@LCO.1']['change_pct'] ?? ''));
            if ($rawChangePct !== '' && $rawChangePct !== 'UNCH') {
                $oilChange = (float) str_replace(['%', '+'], '', $rawChangePct);
                $rawOilDiff = trim((string) ($secondaryData['@LCO.1']['change'] ?? ''));
                if ($rawOilDiff !== '' && $rawOilDiff !== 'UNCH') {
                    $oilDiff = (float) str_replace(['$', ',', '+'], '', $rawOilDiff);
                }
            } elseif (!empty($primaryData['BRENT']['Change'])) {
                $oilChange = (float) $primaryData['BRENT']['Change'];
            }
        }
        $items['BRENT'] = self::resolveInstrument(
            'BRENT',
            'brent',
            '$',
            2,
            $oilPrice,
            $oilChange,
            $oilDiff,
            $prevItems['BRENT'] ?? null,
            $seed['BRENT'],
            $liveCount
        );

        return ['items' => $items];
    }

    /**
     * Resolve a single instrument through sanity bounds and fallback chain.
     */
    private static function resolveInstrument(
        string $code,
        string $i18nKey,
        string $prefix,
        int $decimals,
        float $price,
        float $changePct,
        ?float $diff,
        ?array $previous,
        array $seed,
        int &$liveCount = 0
    ): array {
        $isValid = self::validateSanity($code, $price, $changePct);

        if (!$isValid) {
            if ($previous !== null && self::validateSanity($code, (float) ($previous['price_raw'] ?? 0.0), (float) ($previous['change_pct_raw'] ?? 0.0))) {
                return $previous;
            }
            return $seed;
        }

        $liveCount++;

        if ($diff === null) {
            $prevPrice = $price / (1.0 + ($changePct / 100.0));
            $diff = $price - $prevPrice;
        }

        $direction = 'flat';
        if ($changePct > 0.0001) {
            $direction = 'up';
        } elseif ($changePct < -0.0001) {
            $direction = 'down';
        }

        return [
            'code' => $code,
            'i18n_key' => $i18nKey,
            'prefix' => $prefix,
            'decimals' => $decimals,
            'price_raw' => round($price, $decimals > 0 ? $decimals : 0),
            'change_pct_raw' => round($changePct, 2),
            'diff_raw' => round($diff, $decimals > 0 ? $decimals : 0),
            'direction' => $direction,
        ];
    }

    /**
     * Validate price and change percentage against realistic bounds.
     */
    private static function validateSanity(string $code, float $price, float $changePct): bool
    {
        if ($price <= 0.0) {
            return false;
        }

        if ($changePct < -50.0 || $changePct > 50.0) {
            return false;
        }

        return match ($code) {
            'GRAM_ALTIN' => ($price >= 100.0 && $price <= 100000.0),
            'USD_TRY', 'EUR_TRY', 'GBP_TRY' => ($price >= 1.0 && $price <= 500.0),
            'XU100' => ($price >= 1000.0 && $price <= 200000.0),
            'BTC_USD' => ($price >= 1000.0 && $price <= 2000000.0),
            'GRAM_GUMUS' => ($price >= 1.0 && $price <= 5000.0),
            'BRENT' => ($price >= 10.0 && $price <= 500.0),
            default => true,
        };
    }

    /**
     * Baseline snapshot for cold start deployments.
     */
    public static function getSeedRates(): array
    {
        return [
            'updated_at' => time(),
            'last_attempt_at' => time(),
            'updated_iso' => date('c'),
            'items' => [
                'GRAM_ALTIN' => [
                    'code' => 'GRAM_ALTIN',
                    'i18n_key' => 'gram_gold',
                    'prefix' => '',
                    'decimals' => 2,
                    'price_raw' => 6384.76,
                    'change_pct_raw' => -0.62,
                    'diff_raw' => -39.83,
                    'direction' => 'down',
                ],
                'USD_TRY' => [
                    'code' => 'USD_TRY',
                    'i18n_key' => 'dollar',
                    'prefix' => '',
                    'decimals' => 4,
                    'price_raw' => 49.6918,
                    'change_pct_raw' => 0.08,
                    'diff_raw' => 0.0397,
                    'direction' => 'up',
                ],
                'EUR_TRY' => [
                    'code' => 'EUR_TRY',
                    'i18n_key' => 'euro',
                    'prefix' => '',
                    'decimals' => 4,
                    'price_raw' => 55.7233,
                    'change_pct_raw' => -0.14,
                    'diff_raw' => -0.0781,
                    'direction' => 'down',
                ],
                'GBP_TRY' => [
                    'code' => 'GBP_TRY',
                    'i18n_key' => 'sterling',
                    'prefix' => '',
                    'decimals' => 4,
                    'price_raw' => 66.5508,
                    'change_pct_raw' => -0.14,
                    'diff_raw' => -0.0933,
                    'direction' => 'down',
                ],
                'XU100' => [
                    'code' => 'XU100',
                    'i18n_key' => 'bist100',
                    'prefix' => '',
                    'decimals' => 2,
                    'price_raw' => 11116.99,
                    'change_pct_raw' => -0.92,
                    'diff_raw' => -103.23,
                    'direction' => 'down',
                ],
                'BTC_USD' => [
                    'code' => 'BTC_USD',
                    'i18n_key' => 'bitcoin',
                    'prefix' => '$',
                    'decimals' => 0,
                    'price_raw' => 80533.0,
                    'change_pct_raw' => -1.34,
                    'diff_raw' => -1091.0,
                    'direction' => 'down',
                ],
                'GRAM_GUMUS' => [
                    'code' => 'GRAM_GUMUS',
                    'i18n_key' => 'gram_silver',
                    'prefix' => '',
                    'decimals' => 2,
                    'price_raw' => 86.16,
                    'change_pct_raw' => -1.20,
                    'diff_raw' => -1.05,
                    'direction' => 'down',
                ],
                'BRENT' => [
                    'code' => 'BRENT',
                    'i18n_key' => 'brent',
                    'prefix' => '$',
                    'decimals' => 2,
                    'price_raw' => 116.93,
                    'change_pct_raw' => 0.18,
                    'diff_raw' => 0.21,
                    'direction' => 'up',
                ],
            ],
        ];
    }

    /**
     * Read parsed data from memory cache or JSON file.
     */
    private static function readCache(): ?array
    {
        if (class_exists('RedisCache', false) && RedisCache::isEnabled()) {
            $redisData = RedisCache::get(self::REDIS_KEY);
            if (is_array($redisData) && !empty($redisData['items'])) {
                return $redisData;
            }
        }

        $filePath = self::getCacheDirectory() . self::CACHE_FILENAME;
        if (!file_exists($filePath) || filesize($filePath) === 0) {
            return null;
        }

        $raw = @file_get_contents($filePath);
        if ($raw === false || $raw === '') {
            return null;
        }

        $data = json_decode($raw, true);
        return is_array($data) && !empty($data['items']) ? $data : null;
    }

    /**
     * Atomically write market rates to disk and memory cache.
     */
    private static function writeCache(array $data): bool
    {
        $cacheDir = self::getCacheDirectory();
        $targetFile = $cacheDir . self::CACHE_FILENAME;
        $tempFile = $targetFile . '.tmp.' . getmypid() . '_' . mt_rand(1000, 9999);

        $encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($encoded === false) {
            return false;
        }

        if (@file_put_contents($tempFile, $encoded, LOCK_EX) === false) {
            return false;
        }

        @chmod($tempFile, 0666);

        if (!@rename($tempFile, $targetFile)) {
            @unlink($tempFile);
            return false;
        }

        if (class_exists('RedisCache', false) && RedisCache::isEnabled()) {
            RedisCache::set(self::REDIS_KEY, $data, self::CACHE_TTL + 60);
        }

        return true;
    }

    /**
     * Retrieve cache directory path ensuring writeable permissions.
     */
    private static function getCacheDirectory(): string
    {
        $dir = defined('root_dir') ? root_dir . self::CACHE_SUBDIR : dirname(__DIR__) . '/' . self::CACHE_SUBDIR;
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
            @chmod($dir, 0777);
        }
        return rtrim($dir, '/') . '/';
    }

    /**
     * Format price value according to active language notation.
     */
    private static function formatPrice(float $val, int $decimals, string $prefix, string $lang): string
    {
        $isEnglish = ($lang === 'en');
        $decPoint = $isEnglish ? '.' : ',';
        $thousandsSep = $isEnglish ? ',' : '.';

        $formatted = number_format($val, $decimals, $decPoint, $thousandsSep);
        return $prefix !== '' ? $prefix . $formatted : $formatted;
    }

    /**
     * Format percentage change according to active language notation.
     */
    private static function formatPercentage(float $val, string $lang): string
    {
        $isEnglish = ($lang === 'en');
        $decPoint = $isEnglish ? '.' : ',';
        $formatted = number_format(abs($val), 2, $decPoint, '');

        if ($isEnglish) {
            $sign = $val > 0.0001 ? '+' : ($val < -0.0001 ? '-' : '');
            return $sign . $formatted . '%';
        }

        $sign = $val < -0.0001 ? '-' : ($val > 0.0001 ? '' : '');
        return '%' . $sign . $formatted;
    }

    /**
     * Format point difference according to active language notation.
     */
    private static function formatDifference(float $val, int $decimals, string $prefix, string $lang): string
    {
        $isEnglish = ($lang === 'en');
        $decPoint = $isEnglish ? '.' : ',';
        $thousandsSep = $isEnglish ? ',' : '.';

        $sign = $val > 0.0001 ? '+' : ($val < -0.0001 ? '-' : '');
        $absVal = abs($val);
        $formattedNum = number_format($absVal, $decimals, $decPoint, $thousandsSep);

        if ($prefix !== '') {
            $formatted = '(' . $sign . $prefix . $formattedNum . ')';
        } else {
            $formatted = '(' . $sign . $formattedNum . ')';
        }

        return $formatted;
    }
}
