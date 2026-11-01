<?php

/**
 * Amed Framework
 * Class: SportsDataService
 * File: SportsDataService.class.php
 * Description: Real-time Turkish Super League standings and matchday service with parallel feed ingestion and atomic caching
 */

class SportsDataService
{
    private const CACHE_SUBDIR = 'cache/sports/';
    private const CACHE_FILENAME = 'super_league.json';
    private const LOCK_FILENAME = 'refresh.lock';
    private const CACHE_TTL = 180;
    private const RETRY_COOLDOWN = 60;
    private const CONNECT_TIMEOUT = 2;
    private const FETCH_TIMEOUT = 4;
    private const REDIS_KEY = 'bulletin_super_league_v1';
    private const TOTAL_MATCHDAYS = 34;
    private const STANDINGS_LIMIT = 5;
    private const MATCHES_LIMIT = 6;

    private static bool $refreshScheduled = false;
    private static array $dictionaryCache = [];

    /**
     * Canonical club mapping for clean display and regional highlighting.
     */
    private const TEAM_MAPPING = [
        '132335' => ['name' => 'Amed SK', 'is_amed' => true],
        '432' => ['name' => 'Galatasaray', 'is_amed' => false],
        '997' => ['name' => 'Trabzonspor', 'is_amed' => false],
        '1895' => ['name' => 'Beşiktaş', 'is_amed' => false],
        '436' => ['name' => 'Fenerbahçe', 'is_amed' => false],
        '132334' => ['name' => 'Çorum FK', 'is_amed' => false],
        '6870' => ['name' => 'Kasımpaşa', 'is_amed' => false],
        '995' => ['name' => 'Kocaelispor', 'is_amed' => false],
        '19267' => ['name' => 'Erzurumspor', 'is_amed' => false],
        '789' => ['name' => 'Göztepe', 'is_amed' => false],
        '9078' => ['name' => 'Alanyaspor', 'is_amed' => false],
        '7914' => ['name' => 'Başakşehir', 'is_amed' => false],
        '7656' => ['name' => 'Rizespor', 'is_amed' => false],
        '20070' => ['name' => 'Gaziantep FK', 'is_amed' => false],
        '7648' => ['name' => 'Konyaspor', 'is_amed' => false],
        '996' => ['name' => 'Gençlerbirliği', 'is_amed' => false],
        '11429' => ['name' => 'Samsunspor', 'is_amed' => false],
        '20729' => ['name' => 'Eyüpspor', 'is_amed' => false],
    ];

    /**
     * Retrieve normalized Super League data for the requested language.
     *
     * @param string $languageCode
     * @return array
     */
    public static function getSuperLeagueData(string $languageCode = 'en'): array
    {
        $cachedData = self::readCache();
        $currentTime = time();

        if ($cachedData === null) {
            $cachedData = self::getSeedData();
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

        $matchdayNumber = (int) ($cachedData['matchday'] ?? 10);
        $totalMatchdays = (int) ($cachedData['total_matchdays'] ?? self::TOTAL_MATCHDAYS);

        $formatTemplate = (string) ($langDictionary['matchday_format'] ?? 'Matchday %d of %d');
        $matchdayLabel = sprintf($formatTemplate, $matchdayNumber, $totalMatchdays);

        $rawStandings = $cachedData['standings'] ?? [];
        $standings = self::buildDisplayStandings($rawStandings);

        $rawMatches = $cachedData['matches'] ?? [];
        $matches = self::buildDisplayMatches($rawMatches, $langDictionary);

        return [
            'updated_at' => (int) ($cachedData['updated_at'] ?? $currentTime),
            'matchday' => $matchdayNumber,
            'total_matchdays' => $totalMatchdays,
            'matchday_label' => $matchdayLabel,
            'standings' => $standings,
            'matches' => $matches,
        ];
    }

    /**
     * Build compact display standings with regional club presence guarantee.
     *
     * @param array $rawStandings
     * @return array
     */
    private static function buildDisplayStandings(array $rawStandings): array
    {
        if (empty($rawStandings)) {
            $seed = self::getSeedData();
            $rawStandings = $seed['standings'];
        }

        $topClubs = array_slice($rawStandings, 0, self::STANDINGS_LIMIT);
        $hasAmed = false;
        foreach ($topClubs as $club) {
            if (!empty($club['is_amed'])) {
                $hasAmed = true;
                break;
            }
        }

        if (!$hasAmed) {
            foreach ($rawStandings as $club) {
                if (!empty($club['is_amed'])) {
                    $topClubs[] = $club;
                    break;
                }
            }
        }

        return $topClubs;
    }

    /**
     * Build localized display match items.
     *
     * @param array $rawMatches
     * @param array $langDictionary
     * @return array
     */
    private static function buildDisplayMatches(array $rawMatches, array $langDictionary): array
    {
        if (empty($rawMatches)) {
            $seed = self::getSeedData();
            $rawMatches = $seed['matches'];
        }

        $displayItems = [];
        foreach ($rawMatches as $match) {
            $state = (string) ($match['state'] ?? 'pre');
            $statusCode = (string) ($match['status_code'] ?? 'SCHED');
            $dayKey = (string) ($match['day_key'] ?? 'today');

            $statusLabel = match ($statusCode) {
                'LIVE' => (string) ($langDictionary['status_live'] ?? 'Live'),
                'HT' => (string) ($langDictionary['status_ht'] ?? 'HT'),
                'FT' => (string) ($langDictionary['status_ft'] ?? 'FT'),
                default => '',
            };

            $dayLabel = match ($dayKey) {
                'today' => (string) ($langDictionary['today'] ?? 'Today'),
                'yesterday' => (string) ($langDictionary['yesterday'] ?? 'Yesterday'),
                'tomorrow' => (string) ($langDictionary['tomorrow'] ?? 'Tomorrow'),
                default => (string) ($match['date_short'] ?? ''),
            };

            $item = $match;
            $item['status_label'] = $statusLabel;
            $item['day_label'] = $dayLabel;
            $displayItems[] = $item;
        }

        return $displayItems;
    }

    /**
     * Load localization dictionary from index sports news pack.
     *
     * @param string $cleanLang
     * @return array
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
            if (isset($lang['index']['mod_news']['sports_news']) && is_array($lang['index']['mod_news']['sports_news'])) {
                $dictionary = $lang['index']['mod_news']['sports_news'];
            }
        }

        self::$dictionaryCache[$cleanLang] = $dictionary;
        return $dictionary;
    }

    /**
     * Refresh Super League data from upstream feeds.
     *
     * @param bool $force
     * @return array
     */
    public static function refreshSuperLeagueData(bool $force = false): array
    {
        $cacheDir = self::getCacheDirectory();
        $lockFile = $cacheDir . self::LOCK_FILENAME;

        $lockHandle = @fopen($lockFile, 'c+');
        if (!$lockHandle) {
            return self::readCache() ?? self::getSeedData();
        }

        if (!@flock($lockHandle, LOCK_EX | LOCK_NB)) {
            @fclose($lockHandle);
            return self::readCache() ?? self::getSeedData();
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
        $standings = self::parseStandingsPayload((string) ($fetchResults['standings'] ?? ''));
        if (empty($standings) && !empty($fetchResults['backup_standings'])) {
            $standings = self::parseBackupStandingsPayload((string) $fetchResults['backup_standings']);
        }

        $allMatches = [];
        foreach ($fetchResults as $key => $body) {
            if (str_starts_with($key, 'scoreboard_') && $body !== '') {
                $dayMatches = self::parseScoreboardPayload($body);
                foreach ($dayMatches as $match) {
                    $matchId = (string) ($match['id'] ?? '');
                    if ($matchId !== '' && !isset($allMatches[$matchId])) {
                        $allMatches[$matchId] = $match;
                    }
                }
            }
        }

        $selectedMatches = self::selectFeaturedMatches(array_values($allMatches));
        if (empty($selectedMatches)) {
            if ($previous !== null && !empty($previous['matches'])) {
                $selectedMatches = $previous['matches'];
            } else {
                $seed = self::getSeedData();
                $selectedMatches = $seed['matches'];
            }
        }

        $isValid = self::validateSanity($standings);
        if (!$isValid) {
            if ($previous !== null && self::validateSanity($previous['standings'] ?? [])) {
                $standings = $previous['standings'];
                if (empty($selectedMatches) && !empty($previous['matches'])) {
                    $selectedMatches = $previous['matches'];
                }
            } else {
                $seed = self::getSeedData();
                $standings = $seed['standings'];
                $selectedMatches = $seed['matches'];
            }
        }

        $matchdayNumber = 10;
        if (!empty($standings)) {
            $playedList = array_column($standings, 'mp');
            if (!empty($playedList)) {
                $matchdayNumber = (int) max($playedList);
            }
        }
        if ($matchdayNumber < 1 || $matchdayNumber > self::TOTAL_MATCHDAYS) {
            $matchdayNumber = 10;
        }

        $normalized = [
            'updated_at' => $currentTime,
            'last_attempt_at' => $currentTime,
            'updated_iso' => date('c', $currentTime),
            'matchday' => $matchdayNumber,
            'total_matchdays' => self::TOTAL_MATCHDAYS,
            'standings' => $standings,
            'matches' => $selectedMatches,
        ];

        self::writeCache($normalized);

        @flock($lockHandle, LOCK_UN);
        @fclose($lockHandle);

        return $normalized;
    }

    /**
     * Schedule deferred refresh after request response flush.
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

            SportsDataService::refreshSuperLeagueData(false);
        });
    }

    /**
     * Execute parallel request across multiple upstream feeds.
     *
     * @return array
     */
    private static function executeParallelFetch(): array
    {
        $urls = [
            'standings' => 'https://site.web.api.espn.com/apis/v2/sports/soccer/tur.1/standings',
            'backup_standings' => 'https://sports.core.api.espn.com/v2/sports/soccer/leagues/tur.1/seasons/2026/types/1/groups/1/standings/0',
        ];

        $timeZone = new DateTimeZone('Europe/Istanbul');
        $now = new DateTime('now', $timeZone);

        $dayOfWeek = (int) $now->format('N');
        $dayOffsets = match ($dayOfWeek) {
            5 => [0, 1, 2, 3],
            6 => [-1, 0, 1, 2],
            7 => [-2, -1, 0, 1],
            1 => [-3, -2, -1, 0],
            2 => [-4, -3, -2, -1],
            3 => [2, 3, 4, 5],
            default => [1, 2, 3, 4],
        };

        foreach ($dayOffsets as $offset) {
            $targetDate = clone $now;
            if ($offset !== 0) {
                $targetDate->modify(($offset > 0 ? '+' : '') . $offset . ' day');
            }
            $dateString = $targetDate->format('Ymd');
            $urls['scoreboard_' . $dateString] = 'https://site.api.espn.com/apis/site/v2/sports/soccer/tur.1/scoreboard?dates=' . $dateString;
        }

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
     * Parse primary standings feed.
     *
     * @param string $json
     * @return array
     */
    private static function parseStandingsPayload(string $json): array
    {
        if ($json === '') {
            return [];
        }
        $data = json_decode($json, true);
        if (!is_array($data) || empty($data['children'][0]['standings']['entries'])) {
            return [];
        }

        $entries = $data['children'][0]['standings']['entries'];
        $standings = [];

        foreach ($entries as $entry) {
            $team = $entry['team'] ?? [];
            $teamId = (string) ($team['id'] ?? '');
            $rawName = (string) ($team['displayName'] ?? ($team['name'] ?? ''));
            $logoUrl = (string) ($team['logos'][0]['href'] ?? '');

            $statsMap = [];
            foreach ($entry['stats'] ?? [] as $stat) {
                $statName = (string) ($stat['name'] ?? '');
                if ($statName !== '') {
                    $statsMap[$statName] = (float) ($stat['value'] ?? 0.0);
                }
            }

            $normalizedTeam = self::normalizeTeam($teamId, $rawName, $logoUrl);

            $rank = (int) ($statsMap['rank'] ?? (count($standings) + 1));
            $gamesPlayed = (int) ($statsMap['gamesPlayed'] ?? 0);
            $wins = (int) ($statsMap['wins'] ?? 0);
            $ties = (int) ($statsMap['ties'] ?? 0);
            $losses = (int) ($statsMap['losses'] ?? 0);
            $pointDiff = (int) ($statsMap['pointDifferential'] ?? 0);
            $points = (int) ($statsMap['points'] ?? 0);

            $standings[] = [
                'id' => $teamId,
                'rank' => $rank,
                'name' => $normalizedTeam['name'],
                'logo' => $normalizedTeam['logo'],
                'is_amed' => $normalizedTeam['is_amed'],
                'mp' => $gamesPlayed,
                'w' => $wins,
                'd' => $ties,
                'l' => $losses,
                'gd' => ($pointDiff > 0 ? '+' : '') . $pointDiff,
                'pts' => $points,
            ];
        }

        usort($standings, static function ($first, $second) {
            if ($first['rank'] === $second['rank']) {
                return $second['pts'] <=> $first['pts'];
            }
            return $first['rank'] <=> $second['rank'];
        });

        return $standings;
    }

    /**
     * Parse backup standings payload.
     *
     * @param string $json
     * @return array
     */
    private static function parseBackupStandingsPayload(string $json): array
    {
        if ($json === '') {
            return [];
        }
        $data = json_decode($json, true);
        if (!is_array($data) || empty($data['standings'])) {
            return [];
        }

        $standings = [];
        foreach ($data['standings'] as $entry) {
            $teamRef = (string) ($entry['team']['$ref'] ?? '');
            $teamId = '';
            if (preg_match('#/teams/(\d+)#', $teamRef, $matches)) {
                $teamId = $matches[1];
            }
            if ($teamId === '') {
                continue;
            }

            $statsMap = [];
            $records = $entry['records'][0]['stats'] ?? [];
            foreach ($records as $stat) {
                $statName = (string) ($stat['name'] ?? '');
                if ($statName !== '') {
                    $statsMap[$statName] = (float) ($stat['value'] ?? 0.0);
                }
            }

            $normalizedTeam = self::normalizeTeam($teamId, $teamId, '');

            $rank = (int) ($statsMap['rank'] ?? (count($standings) + 1));
            $gamesPlayed = (int) ($statsMap['gamesPlayed'] ?? 0);
            $wins = (int) ($statsMap['wins'] ?? 0);
            $ties = (int) ($statsMap['ties'] ?? 0);
            $losses = (int) ($statsMap['losses'] ?? 0);
            $pointDiff = (int) ($statsMap['pointDifferential'] ?? 0);
            $points = (int) ($statsMap['points'] ?? 0);

            $standings[] = [
                'id' => $teamId,
                'rank' => $rank,
                'name' => $normalizedTeam['name'],
                'logo' => $normalizedTeam['logo'],
                'is_amed' => $normalizedTeam['is_amed'],
                'mp' => $gamesPlayed,
                'w' => $wins,
                'd' => $ties,
                'l' => $losses,
                'gd' => ($pointDiff > 0 ? '+' : '') . $pointDiff,
                'pts' => $points,
            ];
        }

        usort($standings, static function ($first, $second) {
            if ($first['rank'] === $second['rank']) {
                return $second['pts'] <=> $first['pts'];
            }
            return $first['rank'] <=> $second['rank'];
        });

        return $standings;
    }

    /**
     * Parse scoreboard JSON payload.
     *
     * @param string $json
     * @return array
     */
    private static function parseScoreboardPayload(string $json): array
    {
        if ($json === '') {
            return [];
        }
        $data = json_decode($json, true);
        if (!is_array($data) || empty($data['events'])) {
            return [];
        }

        $events = $data['events'];
        $matches = [];
        $timeZone = new DateTimeZone('Europe/Istanbul');
        $now = new DateTime('now', $timeZone);
        $todayString = $now->format('Y-m-d');
        $yesterdayString = (clone $now)->modify('-1 day')->format('Y-m-d');
        $tomorrowString = (clone $now)->modify('+1 day')->format('Y-m-d');

        foreach ($events as $event) {
            $eventId = (string) ($event['id'] ?? '');
            $dateRaw = (string) ($event['date'] ?? '');
            $status = $event['status'] ?? [];
            $statusType = $status['type'] ?? [];

            $state = (string) ($statusType['state'] ?? 'pre');
            $detail = (string) ($statusType['detail'] ?? '');
            $displayClock = ($state === 'in') ? (string) ($status['displayClock'] ?? '') : '';

            $statusCode = 'SCHED';
            if ($state === 'in') {
                $statusCode = (stripos($detail, 'Halftime') !== false || stripos($detail, 'HT') !== false) ? 'HT' : 'LIVE';
            } elseif ($state === 'post') {
                $statusCode = 'FT';
            }

            $eventDateTime = new DateTime($dateRaw);
            $eventDateTime->setTimezone($timeZone);
            $eventDateString = $eventDateTime->format('Y-m-d');
            $kickoffTime = $eventDateTime->format('H:i');
            $dateShort = $eventDateTime->format('d.m');
            $timestamp = $eventDateTime->getTimestamp();

            $dayKey = 'date';
            if ($eventDateString === $todayString) {
                $dayKey = 'today';
            } elseif ($eventDateString === $yesterdayString) {
                $dayKey = 'yesterday';
            } elseif ($eventDateString === $tomorrowString) {
                $dayKey = 'tomorrow';
            }

            $competitors = $event['competitions'][0]['competitors'] ?? [];
            $homeCompetitor = null;
            $awayCompetitor = null;

            foreach ($competitors as $competitor) {
                $orderType = (string) ($competitor['homeAway'] ?? '');
                if ($orderType === 'home') {
                    $homeCompetitor = $competitor;
                } elseif ($orderType === 'away') {
                    $awayCompetitor = $competitor;
                }
            }

            if (!$homeCompetitor || !$awayCompetitor) {
                continue;
            }

            $homeTeam = $homeCompetitor['team'] ?? [];
            $awayTeam = $awayCompetitor['team'] ?? [];

            $homeId = (string) ($homeTeam['id'] ?? '');
            $awayId = (string) ($awayTeam['id'] ?? '');

            $homeRawName = (string) ($homeTeam['displayName'] ?? ($homeTeam['name'] ?? ''));
            $awayRawName = (string) ($awayTeam['displayName'] ?? ($awayTeam['name'] ?? ''));

            $homeLogo = (string) ($homeTeam['logo'] ?? ($homeTeam['logos'][0]['href'] ?? ''));
            $awayLogo = (string) ($awayTeam['logo'] ?? ($awayTeam['logos'][0]['href'] ?? ''));

            $homeNorm = self::normalizeTeam($homeId, $homeRawName, $homeLogo);
            $awayNorm = self::normalizeTeam($awayId, $awayRawName, $awayLogo);

            $homeScore = (int) ($homeCompetitor['score'] ?? 0);
            $awayScore = (int) ($awayCompetitor['score'] ?? 0);

            $homeWinner = !empty($homeCompetitor['winner']) || ($state === 'post' && $homeScore > $awayScore);
            $awayWinner = !empty($awayCompetitor['winner']) || ($state === 'post' && $awayScore > $homeScore);

            $matches[] = [
                'id' => $eventId,
                'state' => $state,
                'status_code' => $statusCode,
                'clock_display' => $displayClock,
                'timestamp' => $timestamp,
                'day_key' => $dayKey,
                'date_short' => $dateShort,
                'kickoff_time' => $kickoffTime,
                'home' => [
                    'id' => $homeId,
                    'name' => $homeNorm['name'],
                    'logo' => $homeNorm['logo'],
                    'score' => $homeScore,
                    'is_winner' => $homeWinner,
                    'is_amed' => $homeNorm['is_amed'],
                ],
                'away' => [
                    'id' => $awayId,
                    'name' => $awayNorm['name'],
                    'logo' => $awayNorm['logo'],
                    'score' => $awayScore,
                    'is_winner' => $awayWinner,
                    'is_amed' => $awayNorm['is_amed'],
                ],
            ];
        }

        return $matches;
    }

    /**
     * Map raw team attributes to clean canonical display properties.
     *
     * @param string $teamId
     * @param string $rawName
     * @param string $logoUrl
     * @return array
     */
    private static function normalizeTeam(string $teamId, string $rawName, string $logoUrl): array
    {
        $mapped = self::TEAM_MAPPING[$teamId] ?? null;

        if ($mapped !== null) {
            $cleanName = $mapped['name'];
            $isAmed = $mapped['is_amed'];
        } else {
            $isAmed = (stripos($rawName, 'Amed') !== false);
            $cleanName = $isAmed ? 'Amed SK' : $rawName;
        }

        if ($logoUrl === '' && $teamId !== '') {
            $logoUrl = 'https://a.espncdn.com/i/teamlogos/soccer/500/' . $teamId . '.png';
        }

        return [
            'name' => $cleanName,
            'logo' => $logoUrl,
            'is_amed' => $isAmed,
        ];
    }

    /**
     * Select and rank six featured matchday cards.
     *
     * @param array $allMatches
     * @return array
     */
    private static function selectFeaturedMatches(array $allMatches): array
    {
        if (empty($allMatches)) {
            return [];
        }

        $topClubs = ['Amed SK', 'Galatasaray', 'Fenerbahçe', 'Beşiktaş', 'Trabzonspor'];

        foreach ($allMatches as &$match) {
            $priority = 0;
            $isLive = ($match['state'] === 'in');
            $hasAmed = (!empty($match['home']['is_amed']) || !empty($match['away']['is_amed']));

            $homeName = $match['home']['name'] ?? '';
            $awayName = $match['away']['name'] ?? '';
            $hasTopClub = in_array($homeName, $topClubs, true) || in_array($awayName, $topClubs, true);

            if ($isLive) {
                $priority += 1000;
            }
            if ($hasAmed) {
                $priority += 500;
            }
            if ($match['day_key'] === 'today') {
                $priority += 300;
            } elseif ($match['day_key'] === 'yesterday') {
                $priority += 250;
            } elseif ($match['day_key'] === 'tomorrow') {
                $priority += 200;
            }
            if ($hasTopClub && $match['state'] !== 'pre') {
                $priority += 40;
            }

            $match['priority_score'] = $priority;
        }
        unset($match);

        usort($allMatches, static function ($first, $second) {
            if ($first['priority_score'] === $second['priority_score']) {
                return $first['timestamp'] <=> $second['timestamp'];
            }
            return $second['priority_score'] <=> $first['priority_score'];
        });

        $selected = array_slice($allMatches, 0, self::MATCHES_LIMIT);

        usort($selected, static function ($first, $second) {
            $firstBucket = ($first['state'] === 'in') ? 0 : (($first['state'] === 'post') ? 1 : 2);
            $secondBucket = ($second['state'] === 'in') ? 0 : (($second['state'] === 'post') ? 1 : 2);
            if ($firstBucket !== $secondBucket) {
                return $firstBucket <=> $secondBucket;
            }
            if ($firstBucket === 1) {
                return $second['timestamp'] <=> $first['timestamp'];
            }
            return $first['timestamp'] <=> $second['timestamp'];
        });

        return $selected;
    }

    /**
     * Validate standings collection sanity.
     *
     * @param array $standings
     * @return bool
     */
    private static function validateSanity(array $standings): bool
    {
        if (count($standings) < 5) {
            return false;
        }

        $validCount = 0;
        foreach ($standings as $row) {
            if (!empty($row['name']) && isset($row['pts']) && $row['pts'] >= 0) {
                $validCount++;
            }
        }

        return ($validCount >= 5);
    }

    /**
     * Cold start baseline seed data.
     *
     * @return array
     */
    public static function getSeedData(): array
    {
        $timeZone = new DateTimeZone('Europe/Istanbul');
        $now = new DateTime('now', $timeZone);

        return [
            'updated_at' => time(),
            'last_attempt_at' => time(),
            'updated_iso' => date('c'),
            'matchday' => 10,
            'total_matchdays' => self::TOTAL_MATCHDAYS,
            'standings' => [
                [
                    'id' => '432',
                    'rank' => 1,
                    'name' => 'Galatasaray',
                    'logo' => 'https://a.espncdn.com/i/teamlogos/soccer/500/432.png',
                    'is_amed' => false,
                    'mp' => 10,
                    'w' => 8,
                    'd' => 1,
                    'l' => 1,
                    'gd' => '+11',
                    'pts' => 25,
                ],
                [
                    'id' => '132335',
                    'rank' => 2,
                    'name' => 'Amed SK',
                    'logo' => 'https://a.espncdn.com/i/teamlogos/soccer/500/132335.png',
                    'is_amed' => true,
                    'mp' => 9,
                    'w' => 6,
                    'd' => 2,
                    'l' => 1,
                    'gd' => '+10',
                    'pts' => 20,
                ],
                [
                    'id' => '997',
                    'rank' => 3,
                    'name' => 'Trabzonspor',
                    'logo' => 'https://a.espncdn.com/i/teamlogos/soccer/500/997.png',
                    'is_amed' => false,
                    'mp' => 9,
                    'w' => 6,
                    'd' => 1,
                    'l' => 2,
                    'gd' => '+14',
                    'pts' => 19,
                ],
                [
                    'id' => '1895',
                    'rank' => 4,
                    'name' => 'Beşiktaş',
                    'logo' => 'https://a.espncdn.com/i/teamlogos/soccer/500/1895.png',
                    'is_amed' => false,
                    'mp' => 9,
                    'w' => 6,
                    'd' => 0,
                    'l' => 3,
                    'gd' => '+8',
                    'pts' => 18,
                ],
                [
                    'id' => '436',
                    'rank' => 5,
                    'name' => 'Fenerbahçe',
                    'logo' => 'https://a.espncdn.com/i/teamlogos/soccer/500/436.png',
                    'is_amed' => false,
                    'mp' => 9,
                    'w' => 5,
                    'd' => 1,
                    'l' => 3,
                    'gd' => '+6',
                    'pts' => 16,
                ],
            ],
            'matches' => [
                [
                    'id' => '401888374',
                    'state' => 'in',
                    'status_code' => 'LIVE',
                    'clock_display' => "39'",
                    'timestamp' => time() - 2400,
                    'day_key' => 'today',
                    'date_short' => $now->format('d.m'),
                    'kickoff_time' => '19:00',
                    'home' => [
                        'id' => '436',
                        'name' => 'Fenerbahçe',
                        'logo' => 'https://a.espncdn.com/i/teamlogos/soccer/500/436.png',
                        'score' => 0,
                        'is_winner' => false,
                        'is_amed' => false,
                    ],
                    'away' => [
                        'id' => '789',
                        'name' => 'Göztepe',
                        'logo' => 'https://a.espncdn.com/i/teamlogos/soccer/500/789.png',
                        'score' => 0,
                        'is_winner' => false,
                        'is_amed' => false,
                    ],
                ],
                [
                    'id' => '401888372',
                    'state' => 'post',
                    'status_code' => 'FT',
                    'clock_display' => '',
                    'timestamp' => time() - 14400,
                    'day_key' => 'today',
                    'date_short' => $now->format('d.m'),
                    'kickoff_time' => '16:00',
                    'home' => [
                        'id' => '995',
                        'name' => 'Kocaelispor',
                        'logo' => 'https://a.espncdn.com/i/teamlogos/soccer/500/995.png',
                        'score' => 0,
                        'is_winner' => false,
                        'is_amed' => false,
                    ],
                    'away' => [
                        'id' => '7656',
                        'name' => 'Rizespor',
                        'logo' => 'https://a.espncdn.com/i/teamlogos/soccer/500/7656.png',
                        'score' => 0,
                        'is_winner' => false,
                        'is_amed' => false,
                    ],
                ],
                [
                    'id' => '401888373',
                    'state' => 'post',
                    'status_code' => 'FT',
                    'clock_display' => '',
                    'timestamp' => time() - 25000,
                    'day_key' => 'today',
                    'date_short' => $now->format('d.m'),
                    'kickoff_time' => '13:30',
                    'home' => [
                        'id' => '19267',
                        'name' => 'Erzurumspor',
                        'logo' => 'https://a.espncdn.com/i/teamlogos/soccer/500/19267.png',
                        'score' => 1,
                        'is_winner' => false,
                        'is_amed' => false,
                    ],
                    'away' => [
                        'id' => '132334',
                        'name' => 'Çorum FK',
                        'logo' => 'https://a.espncdn.com/i/teamlogos/soccer/500/132334.png',
                        'score' => 3,
                        'is_winner' => true,
                        'is_amed' => false,
                    ],
                ],
                [
                    'id' => '401888371',
                    'state' => 'post',
                    'status_code' => 'FT',
                    'clock_display' => '',
                    'timestamp' => time() - 86400,
                    'day_key' => 'yesterday',
                    'date_short' => (clone $now)->modify('-1 day')->format('d.m'),
                    'kickoff_time' => '20:00',
                    'home' => [
                        'id' => '7648',
                        'name' => 'Konyaspor',
                        'logo' => 'https://a.espncdn.com/i/teamlogos/soccer/500/7648.png',
                        'score' => 0,
                        'is_winner' => false,
                        'is_amed' => false,
                    ],
                    'away' => [
                        'id' => '432',
                        'name' => 'Galatasaray',
                        'logo' => 'https://a.espncdn.com/i/teamlogos/soccer/500/432.png',
                        'score' => 4,
                        'is_winner' => true,
                        'is_amed' => false,
                    ],
                ],
                [
                    'id' => '401888375',
                    'state' => 'pre',
                    'status_code' => 'SCHED',
                    'clock_display' => '',
                    'timestamp' => time() + 50000,
                    'day_key' => 'tomorrow',
                    'date_short' => (clone $now)->modify('+1 day')->format('d.m'),
                    'kickoff_time' => '13:30',
                    'home' => [
                        'id' => '132335',
                        'name' => 'Amed SK',
                        'logo' => 'https://a.espncdn.com/i/teamlogos/soccer/500/132335.png',
                        'score' => 0,
                        'is_winner' => false,
                        'is_amed' => true,
                    ],
                    'away' => [
                        'id' => '20729',
                        'name' => 'Eyüpspor',
                        'logo' => 'https://a.espncdn.com/i/teamlogos/soccer/500/20729.png',
                        'score' => 0,
                        'is_winner' => false,
                        'is_amed' => false,
                    ],
                ],
                [
                    'id' => '401888376',
                    'state' => 'pre',
                    'status_code' => 'SCHED',
                    'clock_display' => '',
                    'timestamp' => time() + 60000,
                    'day_key' => 'tomorrow',
                    'date_short' => (clone $now)->modify('+1 day')->format('d.m'),
                    'kickoff_time' => '16:00',
                    'home' => [
                        'id' => '7914',
                        'name' => 'Başakşehir',
                        'logo' => 'https://a.espncdn.com/i/teamlogos/soccer/500/7914.png',
                        'score' => 0,
                        'is_winner' => false,
                        'is_amed' => false,
                    ],
                    'away' => [
                        'id' => '11429',
                        'name' => 'Samsunspor',
                        'logo' => 'https://a.espncdn.com/i/teamlogos/soccer/500/11429.png',
                        'score' => 0,
                        'is_winner' => false,
                        'is_amed' => false,
                    ],
                ],
            ],
        ];
    }

    /**
     * Read data from memory cache or JSON file.
     *
     * @return array|null
     */
    private static function readCache(): ?array
    {
        if (class_exists('RedisCache', false) && RedisCache::isEnabled()) {
            $redisData = RedisCache::get(self::REDIS_KEY);
            if (is_array($redisData) && !empty($redisData['standings'])) {
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
        return is_array($data) && !empty($data['standings']) ? $data : null;
    }

    /**
     * Atomically write data to disk and memory cache.
     *
     * @param array $data
     * @return bool
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
     *
     * @return string
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
}
