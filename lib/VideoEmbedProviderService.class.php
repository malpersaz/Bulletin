<?php

declare(strict_types=1);

/**
 * VideoEmbedProviderService
 *
 * Unified multi-provider video embed architecture for AF/Bulletin:
 * - Provider detection & URL normalization for X (Twitter), YouTube, and Vimeo
 * - Deterministic base-36 react-tweet syndication token calculation
 * - Cached metadata fetch with tombstone-resilient semantic HTML snapshots
 * - Strict HTML5 iframe construction (zero inline CSS/JS, strict sandbox, dnt=true)
 * - SSRF and DoS hardened outbound HTTP client
 * - Frontend row enrichment (aspect ratio, duration ISO 8601, mapped locales)
 */
class VideoEmbedProviderService
{
    public const ALLOWED_HOSTS = [
        'cdn.syndication.twimg.com',
        'publish.twitter.com',
        'twitter.com',
        'x.com',
        'pbs.twimg.com',
        'video.twimg.com',
        'www.googleapis.com',
        'www.youtube.com',
        'www.youtube-nocookie.com',
        'i.ytimg.com',
        'vimeo.com',
        'player.vimeo.com',
    ];

    /**
     * Detects the provider and extracts video ID from URL or embed iframe snippet.
     *
     * @param string|null $input
     * @return array{provider: string, id: string, canonical_url: string, handle: string|null}|null
     */
    public static function detectProvider(?string $input): ?array
    {
        if ($input === null) {
            return null;
        }

        $raw = trim($input);
        if ($raw === '') {
            return null;
        }

        if (strpos($raw, '&lt;') !== false || strpos($raw, '&gt;') !== false || strpos($raw, '&quot;') !== false) {
            $raw = html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        // 1. X (Twitter) patterns:
        // - https://x.com/handle/status/1234567890
        // - https://twitter.com/handle/status/1234567890
        // - https://x.com/i/status/1234567890
        // - https://x.com/i/web/status/1234567890
        // - https://platform.twitter.com/embed/Tweet.html?id=1234567890
        if (preg_match('/(?:(?:x|twitter)\.com\/(?:([A-Za-z0-9_]{1,15})\/status(?:es)?|i\/(?:web\/)?status)\/(\d{5,25})|platform\.twitter\.com\/embed\/Tweet\.html\?(?:[^"\'\s<>]*&)?id=(\d{5,25}))/i', $raw, $m)) {
            $statusId = !empty($m[2]) ? $m[2] : (!empty($m[3]) ? $m[3] : '');
            $handle = !empty($m[1]) && strtolower($m[1]) !== 'i' ? $m[1] : null;

            if ($statusId !== '') {
                $canonical = 'https://x.com/' . ($handle ?? 'i') . '/status/' . $statusId;
                return [
                    'provider' => 'x',
                    'id' => $statusId,
                    'canonical_url' => $canonical,
                    'handle' => $handle,
                ];
            }
        }

        // 2. YouTube patterns
        $youtubeId = self::extractYouTubeId($raw);
        if ($youtubeId !== null) {
            $startSeconds = self::extractYouTubeStartSeconds($raw);
            $canonical = 'https://www.youtube.com/watch?v=' . $youtubeId;
            if ($startSeconds > 0) {
                $canonical .= '&t=' . $startSeconds . 's';
            }
            return [
                'provider' => 'youtube',
                'id' => $youtubeId,
                'canonical_url' => $canonical,
                'handle' => null,
                'start' => $startSeconds,
            ];
        }

        // 3. Vimeo patterns:
        // - https://vimeo.com/12345678
        // - https://player.vimeo.com/video/12345678
        if (preg_match('/(?:vimeo\.com\/(?:channels\/(?:\w+\/)?|groups\/[^\/]+\/videos\/|album\/\d+\/video\/|video\/|)(\d+)|player\.vimeo\.com\/video\/(\d+))/i', $raw, $m)) {
            $vimeoId = !empty($m[1]) ? $m[1] : (!empty($m[2]) ? $m[2] : '');
            if ($vimeoId !== '') {
                return [
                    'provider' => 'vimeo',
                    'id' => $vimeoId,
                    'canonical_url' => 'https://vimeo.com/' . $vimeoId,
                    'handle' => null,
                ];
            }
        }

        return null;
    }

    /**
     * Extracts an 11-character YouTube video ID.
     */
    public static function extractYouTubeId(string $input): ?string
    {
        $raw = trim($input);
        if ($raw === '') {
            return null;
        }

        if (preg_match('/youtube(?:-nocookie)?\.com\/embed\/([A-Za-z0-9_-]{11})/i', $raw, $m)) {
            return $m[1];
        }
        if (preg_match('/youtube(?:-nocookie)?\.com\/shorts\/([A-Za-z0-9_-]{11})/i', $raw, $m)) {
            return $m[1];
        }
        if (preg_match('/youtube(?:-nocookie)?\.com\/live\/([A-Za-z0-9_-]{11})/i', $raw, $m)) {
            return $m[1];
        }
        if (preg_match('/youtu\.be\/([A-Za-z0-9_-]{11})/i', $raw, $m)) {
            return $m[1];
        }
        if (preg_match('/[?&]v=([A-Za-z0-9_-]{11})/i', $raw, $m)) {
            return $m[1];
        }
        if (preg_match('/^[A-Za-z0-9_-]{11}$/', $raw)) {
            return $raw;
        }

        return null;
    }

    /**
     * Extracts YouTube start timestamp in seconds from ?t= or ?start= parameters.
     * Supports numeric seconds (10, 10s) and HMS format (1h2m3s, 2m30s).
     */
    public static function extractYouTubeStartSeconds(?string $input): int
    {
        if ($input === null || trim($input) === '') {
            return 0;
        }

        $raw = html_entity_decode(trim($input), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (preg_match('/[?&#](?:t|start)=([0-9hms]+)/i', $raw, $m)) {
            $val = strtolower(trim($m[1]));
            if (ctype_digit($val)) {
                return max(0, (int) $val);
            }
            if (preg_match('/^(?:(\d+)h)?(?:(\d+)m)?(?:(\d+)s?)?$/', $val, $hms)) {
                $h = isset($hms[1]) && $hms[1] !== '' ? (int) $hms[1] : 0;
                $min = isset($hms[2]) && $hms[2] !== '' ? (int) $hms[2] : 0;
                $sec = isset($hms[3]) && $hms[3] !== '' ? (int) $hms[3] : 0;
                return max(0, ($h * 3600) + ($min * 60) + $sec);
            }
        }

        return 0;
    }

    /**
     * Deterministic base-36 react-tweet syndication token calculation.
     * Bit-identical to V8 JavaScript:
     * ((Number(id) / 1e15) * Math.PI).toString(36).replace(/(0+|\.)/g, '')
     */
    public static function computeXSyndicationToken(string $statusId): string
    {
        $num = ((float) $statusId / 1e15) * M_PI;
        if ($num <= 0.0 || !is_finite($num)) {
            return 'a';
        }
        while ($num < 1.0) {
            $num *= 36.0;
        }
        $intPart = (int) floor($num);
        $fractionalPart = $num - $intPart;
        $base36Int = base_convert((string) $intPart, 10, 36);
        $fractionDigits = max(0, 11 - strlen($base36Int));
        $scale = 36 ** $fractionDigits;
        $scaledFraction = (int) round($fractionalPart * $scale);
        if ($fractionDigits > 0 && $scaledFraction >= $scale) {
            $intPart++;
            $base36Int = base_convert((string) $intPart, 10, 36);
            $scaledFraction = 0;
        }
        $base36Fraction = $fractionDigits > 0
            ? str_pad(base_convert((string) $scaledFraction, 10, 36), $fractionDigits, '0', STR_PAD_LEFT)
            : '';

        return preg_replace('/(0+|\.)/', '', $base36Int . '.' . $base36Fraction) ?: 'a';
    }

    /**
     * Fetches provider metadata with caching (TTL 3600s).
     *
     * @throws Exception
     */
    public static function fetchMetadata(string $provider, string $id, string $lang = 'en'): array
    {
        $cacheKey = 'video_embed_meta_' . $provider . '_' . $id;

        if (class_exists('Cache', false)) {
            Cache::$ttl = 3600;
            if (Cache::exists($cacheKey)) {
                $cached = Cache::get($cacheKey);
                if (is_array($cached) && !empty($cached['title'])) {
                    return $cached;
                }
            }
        }

        $result = match ($provider) {
            'x' => self::fetchXMetadata($id, $lang),
            'youtube' => self::fetchYouTubeMetadata($id),
            'vimeo' => self::fetchVimeoMetadata($id),
            default => throw new Exception('Unsupported video provider: ' . $provider),
        };

        if (class_exists('Cache', false) && !empty($result['title'])) {
            Cache::set($result, $cacheKey, 3600);
        }

        return $result;
    }

    /**
     * Fetches metadata for an X (Twitter) video post.
     */
    private static function fetchXMetadata(string $statusId, string $lang): array
    {
        $token = self::computeXSyndicationToken($statusId);
        $syndicationUrl = 'https://cdn.syndication.twimg.com/tweet-result?id=' . rawurlencode($statusId) . '&token=' . rawurlencode($token) . '&lang=en';

        $response = self::httpRequest($syndicationUrl, 6, 524288);
        $status = $response['status'];
        $body = $response['body'];
        $json = $body !== '' ? json_decode($body, true) : null;

        if ($status === 404 || (is_array($json) && ($json['__typename'] ?? '') === 'TweetTombstone')) {
            throw new Exception('autofill.tombstone_error');
        }

        if (is_array($json) && $status >= 200 && $status < 300) {
            // Guard: ensure video or animated_gif exists
            $hasVideo = !empty($json['video'])
                || (!empty($json['mediaDetails']) && is_array($json['mediaDetails']) && in_array($json['mediaDetails'][0]['type'] ?? '', ['video', 'animated_gif'], true));

            if (!$hasVideo) {
                throw new Exception('autofill.no_video_in_post');
            }

            $user = $json['user'] ?? [];
            $authorName = trim((string) ($user['name'] ?? ''));
            $authorHandle = trim((string) ($user['screen_name'] ?? ''));

            $rawText = (string) ($json['text'] ?? '');

            // Expand URLs from entities
            if (!empty($json['entities']['urls']) && is_array($json['entities']['urls'])) {
                foreach ($json['entities']['urls'] as $urlEntity) {
                    $short = $urlEntity['url'] ?? '';
                    $expanded = $urlEntity['expanded_url'] ?? '';
                    if ($short !== '' && $expanded !== '') {
                        $rawText = str_replace($short, $expanded, $rawText);
                    }
                }
            }

            // Strip trailing t.co media links
            $cleanText = trim(preg_replace('/\s*https?:\/\/t\.co\/\S+\s*$/i', '', $rawText) ?? $rawText);
            $cleanText = self::stripEmojiAndUnsupportedChars($cleanText);

            $title = Helper::truncateWords($cleanText, 120);
            if ($title === '') {
                $title = ($authorName !== '' ? $authorName : 'X') . ' — Video';
            }

            $description = Helper::truncateWords($cleanText, 200);
            if ($description === '') {
                $description = $title;
            }

            // Duration resolution
            $durationMs = 0;
            if (!empty($json['video']['durationMs'])) {
                $durationMs = (int) $json['video']['durationMs'];
            } elseif (!empty($json['mediaDetails'][0]['video_info']['duration_millis'])) {
                $durationMs = (int) $json['mediaDetails'][0]['video_info']['duration_millis'];
            }

            $duration = '00:00';
            if ($durationMs > 0) {
                $seconds = (int) round($durationMs / 1000);
                $duration = self::formatSecondsToDuration($seconds);
            }

            // Aspect ratio
            $aspectRatio = '16:9';
            $ratioValues = $json['video']['aspectRatio'] ?? ($json['mediaDetails'][0]['video_info']['aspect_ratio'] ?? null);
            if (is_array($ratioValues) && count($ratioValues) === 2 && (int) $ratioValues[1] > 0) {
                $aspectRatio = self::normalizeAspectRatio((int) $ratioValues[0], (int) $ratioValues[1]);
            }

            // High-resolution poster
            $thumbnailUrl = (string) ($json['video']['poster'] ?? ($json['mediaDetails'][0]['media_url_https'] ?? ''));

            // Provenance date handling
            $createdAtUtc = '';
            $createdAtIstanbul = '';
            if (!empty($json['created_at'])) {
                try {
                    $dt = new DateTime($json['created_at']);
                    $dateUtc = clone $dt;
                    $dateUtc->setTimezone(new DateTimeZone('UTC'));
                    $createdAtUtc = $dateUtc->format('Y-m-d\TH:i:s\Z');

                    $dateIstanbul = clone $dt;
                    $dateIstanbul->setTimezone(new DateTimeZone('Europe/Istanbul'));
                    $createdAtIstanbul = $dateIstanbul->format('Y-m-d H:i');
                } catch (Throwable) {
                    $createdAtUtc = '';
                    $createdAtIstanbul = '';
                }
            }

            // Tombstone-proof semantic HTML snapshot
            $contentHtml = '<blockquote><p>"' . htmlspecialchars($cleanText, ENT_QUOTES, 'UTF-8') . '"</p></blockquote>'
                . '<p><strong>Source:</strong> ' . htmlspecialchars($authorName, ENT_QUOTES, 'UTF-8')
                . ' (<bdi dir="ltr">@' . htmlspecialchars($authorHandle, ENT_QUOTES, 'UTF-8') . '</bdi>) — X';
            if ($createdAtUtc !== '') {
                $contentHtml .= ', <time datetime="' . $createdAtUtc . '">' . $createdAtIstanbul . '</time>';
            }
            $contentHtml .= '</p>';

            $canonicalUrl = 'https://x.com/' . ($authorHandle !== '' ? $authorHandle : 'i') . '/status/' . $statusId;

            return [
                'provider' => 'x',
                'id' => $statusId,
                'title' => $title,
                'name' => $title,
                'description' => $description,
                'duration' => $duration,
                'thumbnail_url' => $thumbnailUrl,
                'author_name' => $authorName,
                'author_handle' => $authorHandle,
                'aspect_ratio' => $aspectRatio,
                'created_at' => $createdAtUtc,
                'content_html' => $contentHtml,
                'canonical_url' => $canonicalUrl,
                'source' => 'x_syndication',
                'is_partial' => false,
            ];
        }

        // Secondary Fallback: publish.twitter.com/oembed
        $oembedUrl = 'https://publish.twitter.com/oembed?url=' . rawurlencode('https://twitter.com/i/status/' . $statusId) . '&dnt=true&omit_script=true';
        $oembedResp = self::httpRequest($oembedUrl, 6, 524288);
        $oembedJson = $oembedResp['body'] !== '' ? json_decode($oembedResp['body'], true) : null;

        if (is_array($oembedJson) && !empty($oembedJson['author_name'])) {
            $authorName = trim((string) $oembedJson['author_name']);
            $rawHtml = (string) ($oembedJson['html'] ?? '');
            $cleanText = trim(strip_tags($rawHtml));
            $cleanText = self::stripEmojiAndUnsupportedChars($cleanText);

            $title = Helper::truncateWords($cleanText, 120);
            if ($title === '') {
                $title = $authorName . ' — X Video';
            }
            $description = Helper::truncateWords($cleanText, 200);

            $authorHandle = '';
            if (preg_match('/twitter\.com\/([A-Za-z0-9_]{1,15})/i', (string) ($oembedJson['author_url'] ?? ''), $am)) {
                $authorHandle = $am[1];
            }

            return [
                'provider' => 'x',
                'id' => $statusId,
                'title' => $title,
                'name' => $title,
                'description' => $description,
                'duration' => '00:00',
                'thumbnail_url' => '',
                'author_name' => $authorName,
                'author_handle' => $authorHandle,
                'aspect_ratio' => '16:9',
                'created_at' => '',
                'content_html' => '<p>' . htmlspecialchars($cleanText, ENT_QUOTES, 'UTF-8') . '</p>',
                'canonical_url' => 'https://x.com/' . ($authorHandle ?: 'i') . '/status/' . $statusId,
                'source' => 'x_oembed',
                'is_partial' => true,
            ];
        }

        throw new Exception('autofill.fetch_failed');
    }

    /**
     * Fetches metadata for a YouTube video.
     */
    private static function fetchYouTubeMetadata(string $videoId): array
    {
        $metadata = [
            'provider' => 'youtube',
            'id' => $videoId,
            'title' => '',
            'name' => '',
            'description' => '',
            'duration' => '',
            'thumbnail_url' => '',
            'author_name' => '',
            'author_handle' => null,
            'aspect_ratio' => '16:9',
            'created_at' => '',
            'content_html' => '',
            'canonical_url' => 'https://www.youtube.com/watch?v=' . $videoId,
            'source' => 'youtube_oembed',
            'is_partial' => false,
        ];

        // 1. YouTube Data API v3 if API key is present
        $apiKey = self::getYouTubeApiKey();
        if ($apiKey !== '') {
            $apiUrl = 'https://www.googleapis.com/youtube/v3/videos?id=' . rawurlencode($videoId) . '&part=snippet,contentDetails&key=' . rawurlencode($apiKey);
            $apiResp = self::httpRequest($apiUrl, 6, 524288);
            $apiData = $apiResp['body'] !== '' ? json_decode($apiResp['body'], true) : null;

            if (isset($apiData['items'][0]['snippet']) && is_array($apiData['items'][0]['snippet'])) {
                $snippet = $apiData['items'][0]['snippet'];
                $metadata['title'] = trim((string) ($snippet['title'] ?? ''));
                $metadata['description'] = trim((string) ($snippet['description'] ?? ''));
                $metadata['author_name'] = trim((string) ($snippet['channelTitle'] ?? ''));
                $metadata['thumbnail_url'] = self::pickYouTubeThumbnailUrl($snippet['thumbnails'] ?? []);
                $metadata['source'] = 'youtube_data_api';

                if (!empty($apiData['items'][0]['contentDetails']['duration'])) {
                    $metadata['duration'] = self::formatIso8601Duration((string) $apiData['items'][0]['contentDetails']['duration']);
                }
            }
        }

        // 2. oEmbed fallback
        if ($metadata['title'] === '') {
            $oembedUrl = 'https://www.youtube.com/oembed?url=' . rawurlencode('https://www.youtube.com/watch?v=' . $videoId) . '&format=json';
            $oembedResp = self::httpRequest($oembedUrl, 6, 524288);
            $oembed = $oembedResp['body'] !== '' ? json_decode($oembedResp['body'], true) : null;

            if (is_array($oembed)) {
                $metadata['title'] = trim((string) ($oembed['title'] ?? ''));
                $metadata['author_name'] = trim((string) ($oembed['author_name'] ?? ''));
                $metadata['thumbnail_url'] = trim((string) ($oembed['thumbnail_url'] ?? ''));
                $metadata['source'] = 'youtube_oembed';
            }
        }

        // 3. Watch page scrape if duration is missing
        if ($metadata['duration'] === '') {
            $watchUrl = 'https://www.youtube.com/watch?v=' . $videoId;
            $watchResp = self::httpRequest($watchUrl, 6, 1048576);
            $html = $watchResp['body'];

            if ($html !== '') {
                if (preg_match('/itemprop="duration"\s+content="([^"]+)"/i', $html, $m)) {
                    $metadata['duration'] = self::formatIso8601Duration($m[1]);
                } elseif (preg_match('/"lengthSeconds"\s*:\s*"(\d+)"/i', $html, $m)) {
                    $metadata['duration'] = self::formatSecondsToDuration((int) $m[1]);
                }

                if ($metadata['title'] === '' && preg_match('/<meta\s+name="title"\s+content="([^"]+)"/i', $html, $m)) {
                    $metadata['title'] = html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
                }
                if ($metadata['description'] === '' && preg_match('/<meta\s+name="description"\s+content="([^"]+)"/i', $html, $m)) {
                    $metadata['description'] = html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
                }
            }
        }

        // 4. Upgrade oEmbed thumbnail to high resolution when available, or resolve fallback thumbnail
        if ($metadata['thumbnail_url'] === '') {
            foreach (['maxresdefault', 'sddefault', 'hqdefault', 'mqdefault', 'default'] as $res) {
                $candidate = 'https://i.ytimg.com/vi/' . $videoId . '/' . $res . '.jpg';
                if (self::remoteImageExists($candidate)) {
                    $metadata['thumbnail_url'] = $candidate;
                    break;
                }
            }
        } elseif ($metadata['source'] === 'youtube_oembed') {
            foreach (['maxresdefault', 'sddefault'] as $res) {
                $candidate = 'https://i.ytimg.com/vi/' . $videoId . '/' . $res . '.jpg';
                if (self::remoteImageExists($candidate)) {
                    $metadata['thumbnail_url'] = $candidate;
                    break;
                }
            }
        }

        if ($metadata['title'] === '') {
            throw new Exception('autofill.fetch_failed');
        }

        $metadata['name'] = $metadata['title'];
        $metadata['is_partial'] = empty($metadata['duration']) || empty($metadata['thumbnail_url']);

        return $metadata;
    }

    /**
     * Fetches metadata for a Vimeo video.
     */
    private static function fetchVimeoMetadata(string $vimeoId): array
    {
        $apiUrl = 'https://vimeo.com/api/oembed.json?url=' . rawurlencode('https://vimeo.com/' . $vimeoId);
        $resp = self::httpRequest($apiUrl, 6, 524288);
        $data = $resp['body'] !== '' ? json_decode($resp['body'], true) : null;

        if (!is_array($data) || empty($data['title'])) {
            throw new Exception('autofill.fetch_failed');
        }

        $duration = '00:00';
        if (!empty($data['duration'])) {
            $duration = self::formatSecondsToDuration((int) $data['duration']);
        }

        $title = trim((string) ($data['title'] ?? ''));
        $description = trim((string) ($data['description'] ?? ''));

        return [
            'provider' => 'vimeo',
            'id' => $vimeoId,
            'title' => $title,
            'name' => $title,
            'description' => $description,
            'duration' => $duration,
            'thumbnail_url' => (string) ($data['thumbnail_url'] ?? ''),
            'author_name' => (string) ($data['author_name'] ?? ''),
            'author_handle' => null,
            'aspect_ratio' => '16:9',
            'created_at' => '',
            'content_html' => '',
            'canonical_url' => 'https://vimeo.com/' . $vimeoId,
            'source' => 'vimeo_oembed',
            'is_partial' => false,
        ];
    }

    /**
     * Builds a strict, code-separated HTML5 iframe.
     * Zero inline CSS, zero inline JS, zero frameborder.
     */
    public static function buildEmbedHtml(string $provider, string $id, array $opts = []): string
    {
        $id = trim($id);
        if ($id === '') {
            return '';
        }

        $title = htmlspecialchars((string) ($opts['title'] ?? ($opts['name'] ?? 'Video player')), ENT_QUOTES, 'UTF-8');
        $aspectRatio = in_array($opts['aspect_ratio'] ?? '', ['16:9', '9:16', '1:1', '4:5'], true) ? $opts['aspect_ratio'] : '16:9';

        switch ($provider) {
            case 'x':
                $lang = self::mapLocaleToXLang((string) ($opts['lang'] ?? 'tr'));
                $theme = (!empty($opts['theme']) && in_array($opts['theme'], ['dark', 'light'], true)) ? $opts['theme'] : 'light';
                $src = 'https://platform.twitter.com/embed/Tweet.html?id=' . rawurlencode($id) . '&amp;theme=' . $theme . '&amp;dnt=true&amp;hideThread=true&amp;lang=' . $lang;
                return '<iframe class="video-embed-frame video-embed-frame--x" width="550" height="620" src="' . $src . '" data-provider="x" data-status-id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '" data-aspect-ratio="' . $aspectRatio . '" title="' . $title . '" allow="autoplay; encrypted-media; picture-in-picture; fullscreen" sandbox="allow-scripts allow-same-origin allow-popups allow-popups-to-escape-sandbox allow-presentation" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>';

            case 'youtube':
                $start = isset($opts['start']) ? max(0, (int) $opts['start']) : 0;
                $src = 'https://www.youtube-nocookie.com/embed/' . rawurlencode($id) . '?rel=0';
                if ($start > 0) {
                    $src .= '&amp;start=' . $start;
                }
                return '<iframe class="video-embed-frame video-embed-frame--youtube" width="560" height="315" src="' . $src . '" data-provider="youtube" data-video-id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '" data-aspect-ratio="16:9" title="' . $title . '" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" referrerpolicy="strict-origin-when-cross-origin" loading="lazy" allowfullscreen></iframe>';

            case 'vimeo':
                $src = 'https://player.vimeo.com/video/' . rawurlencode($id);
                return '<iframe class="video-embed-frame video-embed-frame--vimeo" width="640" height="360" src="' . $src . '" data-provider="vimeo" data-video-id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '" data-aspect-ratio="16:9" title="' . $title . '" allow="autoplay; fullscreen; picture-in-picture" referrerpolicy="strict-origin-when-cross-origin" loading="lazy" allowfullscreen></iframe>';

            default:
                return '';
        }
    }

    /**
     * Rebuilds or sanitizes an embed input string into a safe, canonical HTML5 iframe.
     */
    public static function sanitizeOrRebuildEmbed(string $embedInput, ?string $originalUrl = null, array $opts = []): string
    {
        $detectedEmbed = self::detectProvider($embedInput);
        $detectedOriginal = self::detectProvider($originalUrl);
        $detected = $detectedEmbed ?? $detectedOriginal;
        if ($detected !== null) {
            if ($detected['provider'] === 'youtube' && empty($opts['start'])) {
                $start = (int) ($detectedEmbed['start'] ?? 0);
                if ($start <= 0) {
                    $start = (int) ($detectedOriginal['start'] ?? 0);
                }
                if ($start > 0) {
                    $opts['start'] = $start;
                }
            }
            return self::buildEmbedHtml($detected['provider'], $detected['id'], $opts);
        }

        // Custom embed purification via Helper::clear_html()
        $purified = Helper::clear_html($embedInput);
        if (preg_match('/<iframe\b[^>]*\bsrc="([^"]+)"[^>]*>/i', $purified, $m)) {
            $src = $m[1];
            if (preg_match('%^https://(www\.youtube\.com/embed/|www\.youtube-nocookie\.com/embed/|player\.vimeo\.com/video/|platform\.twitter\.com/embed/Tweet\.html)%', $src)) {
                return $purified;
            }
        }

        return '';
    }

    /**
     * Enriches a database record row for public frontend rendering.
     */
    public static function enrichVideoRowForFrontend(array $row, string $siteLang = 'en'): array
    {
        $row['is_embed'] = true;

        $embedHtml = (string) ($row['embed'] ?? '');
        $original = (string) ($row['original'] ?? '');

        $detected = self::detectProvider($embedHtml) ?? (self::detectProvider($original));
        $provider = $detected['provider'] ?? 'embed';
        $row['provider'] = $provider;

        // Extract or build clean embed_src
        $embedSrc = '';
        if (preg_match('/<iframe\b[^>]*\bsrc="([^"]+)"/i', $embedHtml, $m)) {
            $embedSrc = html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
        } elseif ($detected !== null) {
            $built = self::buildEmbedHtml($detected['provider'], $detected['id'], ['lang' => $siteLang]);
            if (preg_match('/<iframe\b[^>]*\bsrc="([^"]+)"/i', $built, $bm)) {
                $embedSrc = html_entity_decode($bm[1], ENT_QUOTES, 'UTF-8');
            }
        }

        // If provider is X, ensure mapped lang is applied
        if ($provider === 'x' && $embedSrc !== '') {
            $xLang = self::mapLocaleToXLang($siteLang);
            if (strpos($embedSrc, 'lang=') !== false) {
                $embedSrc = preg_replace('/([?&])lang=[a-z_]+/i', '$1lang=' . $xLang, $embedSrc) ?? $embedSrc;
            } else {
                $embedSrc .= (strpos($embedSrc, '?') === false ? '?' : '&') . 'lang=' . $xLang;
            }
            if (strpos($embedSrc, 'dnt=') === false) {
                $embedSrc .= '&dnt=true';
            }
        }

        $row['embed_src'] = $embedSrc;

        // Extract aspect ratio
        $aspectRatio = '16:9';
        if (preg_match('/data-aspect-ratio="([^"]+)"/i', $embedHtml, $m)) {
            $aspectRatio = $m[1];
        }
        $row['aspect_ratio'] = in_array($aspectRatio, ['16:9', '9:16', '1:1', '4:5'], true) ? $aspectRatio : '16:9';

        // Author handle
        $authorHandle = $detected['handle'] ?? null;
        if ($authorHandle === null && preg_match('/(?:x|twitter)\.com\/([A-Za-z0-9_]{1,15})\/status/i', $original, $hm)) {
            $authorHandle = $hm[1];
        }
        $row['author_handle'] = $authorHandle;

        $row['iframe_title'] = htmlspecialchars((string) ($row['name'] ?? 'Video player'), ENT_QUOTES, 'UTF-8');

        // Duration checks
        $durationStr = trim((string) ($row['duration'] ?? ''));
        $seconds = self::parseDurationToSeconds($durationStr);
        $hasValidDuration = $seconds > 0 && !in_array($durationStr, ['', '00:00', '00:00:00'], true);

        $row['has_valid_duration'] = $hasValidDuration;
        $row['iso_duration'] = $hasValidDuration ? self::formatSecondsToIso8601($seconds) : '';
        $row['schema_duration'] = $row['iso_duration'];
        $row['is_x'] = ($provider === 'x');
        $row['is_youtube'] = ($provider === 'youtube');

        return $row;
    }

    /**
     * Maps Bulletin's site locales to X iframe chrome supported languages.
     */
    public static function mapLocaleToXLang(string $siteLang): string
    {
        $code = strtolower(trim($siteLang));
        return match ($code) {
            'tr' => 'tr',
            'en' => 'en',
            'ar' => 'ar',
            'de' => 'de',
            'fr' => 'fr',
            'ru' => 'ru',
            'sv' => 'sv',
            'es' => 'es',
            'ku', 'ckb', 'zza' => 'en',
            default => 'en',
        };
    }

    /**
     * Hardened outbound HTTP client preventing SSRF and DoS.
     */
    public static function httpRequest(string $url, int $timeout = 6, int $maxBytes = 524288): array
    {
        $parts = parse_url($url);
        if (!is_array($parts) || ($parts['scheme'] ?? '') !== 'https') {
            return ['status' => 0, 'headers' => [], 'body' => ''];
        }

        $host = strtolower((string) ($parts['host'] ?? ''));
        if ($host === '' || !in_array($host, self::ALLOWED_HOSTS, true)) {
            return ['status' => 0, 'headers' => [], 'body' => ''];
        }

        // SSRF DNS check: ensure resolved IP is public
        $ips = @gethostbynamel($host);
        if (!is_array($ips) || empty($ips)) {
            return ['status' => 0, 'headers' => [], 'body' => ''];
        }
        foreach ($ips as $ip) {
            if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return ['status' => 0, 'headers' => [], 'body' => ''];
            }
        }

        $ch = curl_init($url);
        if ($ch === false) {
            return ['status' => 0, 'headers' => [], 'body' => ''];
        }

        $responseBody = '';
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_HEADER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_WRITEFUNCTION => function ($handle, $data) use (&$responseBody, $maxBytes) {
                $len = strlen($data);
                if (strlen($responseBody) + $len > $maxBytes) {
                    $remaining = $maxBytes - strlen($responseBody);
                    if ($remaining > 0) {
                        $responseBody .= substr($data, 0, $remaining);
                    }
                    return 0; // Abort download once maxBytes exceeded
                }
                $responseBody .= $data;
                return $len;
            },
        ]);

        $execRes = @curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        $headers = [];
        $body = '';
        if ($headerSize > 0 && strlen($responseBody) >= $headerSize) {
            $headerText = substr($responseBody, 0, $headerSize);
            $body = substr($responseBody, $headerSize);
            $headers = self::parseResponseHeaders($headerText);
        } else {
            $body = $responseBody;
        }

        return [
            'status' => $status,
            'headers' => $headers,
            'body' => $body,
        ];
    }

    private static function parseResponseHeaders(string $headerText): array
    {
        $headers = [];
        $lines = explode("\n", str_replace("\r", "", $headerText));
        foreach ($lines as $line) {
            $parts = explode(':', $line, 2);
            if (count($parts) === 2) {
                $headers[strtolower(trim($parts[0]))] = trim($parts[1]);
            }
        }
        return $headers;
    }

    private static function remoteImageExists(string $url): bool
    {
        $res = self::httpRequest($url, 4, 1024);
        $ct = strtolower((string) ($res['headers']['content-type'] ?? ''));
        return $res['status'] >= 200 && $res['status'] < 300 && str_starts_with($ct, 'image/');
    }

    public static function formatSecondsToDuration(int $seconds): string
    {
        $h = (int) floor($seconds / 3600);
        $m = (int) floor(($seconds % 3600) / 60);
        $s = $seconds % 60;
        return $h > 0 ? sprintf('%02d:%02d:%02d', $h, $m, $s) : sprintf('%02d:%02d', $m, $s);
    }

    public static function parseDurationToSeconds(string $duration): int
    {
        $parts = array_map('intval', explode(':', trim($duration)));
        if (count($parts) === 2) {
            return ($parts[0] * 60) + $parts[1];
        }
        if (count($parts) === 3) {
            return ($parts[0] * 3600) + ($parts[1] * 60) + $parts[2];
        }
        return 0;
    }

    public static function formatSecondsToIso8601(int $seconds): string
    {
        if ($seconds <= 0) {
            return '';
        }
        $h = (int) floor($seconds / 3600);
        $m = (int) floor(($seconds % 3600) / 60);
        $s = $seconds % 60;

        $iso = 'PT';
        if ($h > 0) $iso .= $h . 'H';
        if ($m > 0) $iso .= $m . 'M';
        if ($s > 0 || ($h === 0 && $m === 0)) $iso .= $s . 'S';
        return $iso;
    }

    public static function formatIso8601Duration(string $iso): string
    {
        try {
            $interval = new DateInterval($iso);
            $h = (int) $interval->h;
            $m = (int) $interval->i;
            $s = (int) $interval->s;
            return $h > 0 ? sprintf('%02d:%02d:%02d', $h, $m, $s) : sprintf('%02d:%02d', $m, $s);
        } catch (Throwable) {
            return '';
        }
    }

    private static function normalizeAspectRatio(int $w, int $h): string
    {
        if ($w <= 0 || $h <= 0) {
            return '16:9';
        }
        $val = $w / $h;
        if ($val > 1.4) {
            return '16:9';
        }
        if ($val < 0.7) {
            return '9:16';
        }
        if ($val >= 0.9 && $val <= 1.1) {
            return '1:1';
        }
        return '4:5';
    }

    public static function stripEmojiAndUnsupportedChars(string $text): string
    {
        // Strip 4-byte UTF-8 emojis (outside BMP)
        $clean = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $text) ?? $text;
        return trim(preg_replace('/\s+/', ' ', $clean));
    }

    private static function pickYouTubeThumbnailUrl(array $thumbnails): string
    {
        foreach (['maxres', 'standard', 'high', 'medium', 'default'] as $key) {
            if (!empty($thumbnails[$key]['url'])) {
                return (string) $thumbnails[$key]['url'];
            }
        }
        return '';
    }

    private static function getYouTubeApiKey(): string
    {
        $candidates = [
            getenv('YOUTUBE_DATA_API_KEY'),
            getenv('YOUTUBE_API_KEY'),
            $_ENV['YOUTUBE_DATA_API_KEY'] ?? '',
            $_ENV['YOUTUBE_API_KEY'] ?? '',
            $_SERVER['YOUTUBE_DATA_API_KEY'] ?? '',
            $_SERVER['YOUTUBE_API_KEY'] ?? '',
        ];
        foreach ($candidates as $c) {
            $val = trim((string) $c);
            if ($val !== '') {
                return $val;
            }
        }
        return '';
    }
}
