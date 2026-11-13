<?php

/**
 * `SocialPostProviderService`
 *
 * Provider detection, SSRF-hardened metadata enrichment, canonical iframe
 * construction, `[socialpost:N]` token rendering (`<figure>` paragraph unwrapping),
 * and token stripping for `mod_socialmedia_post`.
 */
class SocialPostProviderService
{
    public const SUPPORTED_PROVIDERS = ['x', 'facebook', 'instagram'];

    private const ALLOWED_HOSTS = [
        'cdn.syndication.twimg.com',
        'publish.twitter.com',
        'twitter.com',
        'www.twitter.com',
        'x.com',
        'www.x.com',
        'pbs.twimg.com',
        'graph.facebook.com',
        'www.facebook.com',
        'facebook.com',
        'm.facebook.com',
        'fb.watch',
        'www.instagram.com',
        'instagram.com',
        'scontent.cdninstagram.com',
    ];

    private const CACHE_TTL_SECONDS = 3600;
    private const MAX_REMOTE_BYTES = 524288;

    /**
     * Ensures the active MySQL database connection uses utf8mb4 for 4-byte UTF-8 emojis
     * even when legacy database configurations specify 'encoding' => 'utf8' (utf8mb3).
     */
    public static function ensureUtf8mb4Connection($database = null): void
    {
        static $upgradedIds = [];
        try {
            if ($database === null && class_exists('Load')) {
                $database = Load::lib('Database');
            }
            if (!is_object($database) || !method_exists($database, 'get_connection')) {
                return;
            }
            $connection = $database->get_connection();
            if ($connection instanceof \PDO) {
                $objectId = spl_object_id($connection);
                if (isset($upgradedIds[$objectId])) {
                    return;
                }
                $driver = strtolower((string) $connection->getAttribute(\PDO::ATTR_DRIVER_NAME));
                if ($driver === 'mysql') {
                    $connection->exec('SET NAMES utf8mb4');
                }
                $upgradedIds[$objectId] = true;
            } elseif ($connection instanceof \mysqli) {
                $objectId = spl_object_id($connection);
                if (isset($upgradedIds[$objectId])) {
                    return;
                }
                $connection->set_charset('utf8mb4');
                $upgradedIds[$objectId] = true;
            }
        } catch (\Throwable $e) {
            // Non-blocking fallback
        }
    }

    /**
     * Detects a supported social post provider from a URL or iframe snippet.
     * Pure and synchronous: performs zero network I/O.
     */
    public static function detectProvider(?string $input): ?array
    {
        $raw = trim((string) $input);
        if ($raw === '') {
            return null;
        }

        $decoded = html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Unwrap pasted `<iframe src="...">` markup if present.
        if (preg_match('/<iframe\b[^>]*\bsrc=["\']([^"\']+)["\']/i', $decoded, $iframeMatch)) {
            $decoded = trim(html_entity_decode($iframeMatch[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        // Handle Facebook plugin iframe URLs by inspecting the inner `href` query parameter.
        if (preg_match('/^https?:\/\/(?:www\.)?facebook\.com\/plugins\/(post|video)\.php\?([^#\s]+)/i', $decoded, $pluginMatch)) {
            $pluginType = strtolower($pluginMatch[1]);
            parse_str($pluginMatch[2], $queryParams);
            if (!empty($queryParams['href'])) {
                $innerDetected = self::detectProvider((string) $queryParams['href']);
                if ($innerDetected !== null) {
                    if ($pluginType === 'video' && $innerDetected['provider'] === 'facebook') {
                        $innerDetected['media_type'] = 'video';
                    }
                    return $innerDetected;
                }
            }
        }

        // X / Twitter status or Tweet embed iframe.
        if (preg_match(
            '/(?:https?:\/\/)?(?:www\.)?(?:x|twitter)\.com\/(?:([A-Za-z0-9_]{1,15})\/status(?:es)?|i\/(?:web\/)?status)\/(\d{5,25})(?:\/(photo|video)\/\d+)?(?=[\/?#\s]|$)/i',
            $decoded,
            $xMatch
        )) {
            $handle = !empty($xMatch[1]) ? $xMatch[1] : 'i';
            $postId = $xMatch[2];
            $suffix = isset($xMatch[3]) ? strtolower($xMatch[3]) : '';
            $mediaType = ($suffix === 'video') ? 'video' : 'post';
            $canonicalHandle = ($handle === 'i') ? 'i/web' : $handle;

            return [
                'provider' => 'x',
                'post_id' => $postId,
                'media_type' => $mediaType,
                'canonical_url' => 'https://x.com/' . $canonicalHandle . '/status/' . $postId,
                'handle' => ($handle === 'i') ? '' : $handle,
            ];
        }

        if (preg_match(
            '/https?:\/\/platform\.twitter\.com\/embed\/Tweet\.html\?(?:[^"\'\s<>]*&)?id=(\d{5,25})/i',
            $decoded,
            $xEmbedMatch
        )) {
            $postId = $xEmbedMatch[1];
            return [
                'provider' => 'x',
                'post_id' => $postId,
                'media_type' => 'post',
                'canonical_url' => 'https://x.com/i/web/status/' . $postId,
                'handle' => '',
            ];
        }

        // Instagram `/p/{shortcode}` or `/reel/{shortcode}` (or `/reels/{shortcode}`).
        if (preg_match(
            '/(?:https?:\/\/)?(?:www\.)?instagram\.com\/(?:([A-Za-z0-9._]{1,30})\/)?(p|reel|reels)\/([A-Za-z0-9_-]{5,64})(?:\/embed(?:\/captioned)?)?(?=[\/?#\s]|$)/i',
            $decoded,
            $instagramMatch
        )) {
            $handle = (!empty($instagramMatch[1]) && !in_array(strtolower($instagramMatch[1]), ['p', 'reel', 'reels'], true)) ? $instagramMatch[1] : '';
            $rawKind = strtolower($instagramMatch[2]);
            $mediaType = ($rawKind === 'reel' || $rawKind === 'reels') ? 'reel' : 'post';
            $postId = $instagramMatch[3];
            $segment = ($mediaType === 'reel') ? 'reel' : 'p';

            return [
                'provider' => 'instagram',
                'post_id' => $postId,
                'media_type' => $mediaType,
                'canonical_url' => 'https://www.instagram.com/' . $segment . '/' . $postId . '/',
                'handle' => $handle,
            ];
        }

        // Facebook `permalink.php?story_fbid=...&id=...`
        if (preg_match('/(?:https?:\/\/)?(?:www\.|m\.)?facebook\.com\/permalink\.php\?([^#\s]+)/i', $decoded, $facebookLinkMatch)) {
            parse_str($facebookLinkMatch[1], $queryParams);
            $storyId = preg_replace('/[^A-Za-z0-9_-]/', '', (string) ($queryParams['story_fbid'] ?? ''));
            $pageId = preg_replace('/[^A-Za-z0-9_.-]/', '', (string) ($queryParams['id'] ?? ''));
            if ($storyId !== '' && $pageId !== '') {
                $combinedId = self::clampPostId($pageId . '_' . $storyId);
                return [
                    'provider' => 'facebook',
                    'post_id' => $combinedId,
                    'media_type' => 'post',
                    'canonical_url' => 'https://www.facebook.com/permalink.php?story_fbid=' . rawurlencode($storyId) . '&id=' . rawurlencode($pageId),
                    'handle' => '',
                ];
            }
        }

        // Facebook `{page}/posts/{pfbid|id}`
        if (preg_match(
            '/(?:https?:\/\/)?(?:www\.|m\.)?facebook\.com\/([A-Za-z0-9._-]{1,80})\/posts\/([A-Za-z0-9._-]{4,128})(?=[\/?#\s]|$)/i',
            $decoded,
            $facebookPostMatch
        )) {
            $handle = $facebookPostMatch[1];
            $postId = self::clampPostId($facebookPostMatch[2]);
            return [
                'provider' => 'facebook',
                'post_id' => $postId,
                'media_type' => 'post',
                'canonical_url' => 'https://www.facebook.com/' . $handle . '/posts/' . $postId,
                'handle' => $handle,
            ];
        }

        // Facebook `{page}/videos/{id}` or `/watch/?v={id}` or `fb.watch/{token}`
        if (preg_match(
            '/(?:https?:\/\/)?(?:www\.|m\.)?facebook\.com\/([A-Za-z0-9._-]{1,80})\/videos\/(?:[^\/\s?#]+\/)?(\d{5,32})(?=[\/?#\s]|$)/i',
            $decoded,
            $facebookVideoMatch
        )) {
            $handle = $facebookVideoMatch[1];
            $postId = self::clampPostId($facebookVideoMatch[2]);
            return [
                'provider' => 'facebook',
                'post_id' => $postId,
                'media_type' => 'video',
                'canonical_url' => 'https://www.facebook.com/' . $handle . '/videos/' . $postId . '/',
                'handle' => $handle,
            ];
        }

        if (preg_match('/(?:https?:\/\/)?(?:www\.|m\.)?facebook\.com\/watch\/?\?(?:[^#\s]*&)?v=(\d{5,32})/i', $decoded, $facebookWatchMatch)) {
            $postId = self::clampPostId($facebookWatchMatch[1]);
            return [
                'provider' => 'facebook',
                'post_id' => $postId,
                'media_type' => 'video',
                'canonical_url' => 'https://www.facebook.com/watch/?v=' . $postId,
                'handle' => '',
            ];
        }

        if (preg_match('/(?:https?:\/\/)?fb\.watch\/([A-Za-z0-9_-]{4,64})(?=[\/?#\s]|$)/i', $decoded, $facebookShortMatch)) {
            $token = $facebookShortMatch[1];
            return [
                'provider' => 'facebook',
                'post_id' => self::clampPostId($token),
                'media_type' => 'video',
                'canonical_url' => 'https://fb.watch/' . $token . '/',
                'handle' => '',
            ];
        }

        // Facebook `share/p/{token}`, `share/v/{token}`, `share/r/{token}`
        if (preg_match(
            '/(?:https?:\/\/)?(?:www\.|m\.)?facebook\.com\/share\/([pvr])\/([A-Za-z0-9_-]{4,100})(?=[\/?#\s]|$)/i',
            $decoded,
            $facebookShareMatch
        )) {
            $kind = strtolower($facebookShareMatch[1]);
            $token = $facebookShareMatch[2];
            $mediaType = ($kind === 'p') ? 'post' : (($kind === 'r') ? 'reel' : 'video');
            return [
                'provider' => 'facebook',
                'post_id' => self::clampPostId('share_' . $kind . '_' . $token),
                'media_type' => $mediaType,
                'canonical_url' => 'https://www.facebook.com/share/' . $kind . '/' . $token . '/',
                'handle' => '',
            ];
        }

        // Facebook reel URL `/reel/{id}`
        if (preg_match('/(?:https?:\/\/)?(?:www\.|m\.)?facebook\.com\/reel\/(\d{5,32})(?=[\/?#\s]|$)/i', $decoded, $facebookReelMatch)) {
            $postId = self::clampPostId($facebookReelMatch[1]);
            return [
                'provider' => 'facebook',
                'post_id' => $postId,
                'media_type' => 'reel',
                'canonical_url' => 'https://www.facebook.com/reel/' . $postId,
                'handle' => '',
            ];
        }

        return null;
    }

    /**
     * Fetches metadata for a social post with 3600s file caching.
     */
    public static function fetchMetadata(string $provider, string $postId, string $canonicalUrl, string $lang = 'en', string $mediaType = 'post'): array
    {
        $provider = strtolower(trim($provider));
        if (!in_array($provider, self::SUPPORTED_PROVIDERS, true)) {
            throw new Exception('autofill.invalid_source');
        }

        $cacheKey = sha1($provider . '|' . $postId . '|' . $mediaType . '|' . $lang);
        $cached = self::readCache($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        if ($provider === 'x') {
            $metadata = self::fetchXMetadata($postId, $canonicalUrl, $lang, $mediaType);
        } elseif ($provider === 'facebook') {
            $metadata = self::fetchFacebookMetadata($postId, $canonicalUrl, $lang, $mediaType);
        } else {
            $metadata = self::fetchInstagramMetadata($postId, $canonicalUrl, $lang, $mediaType);
        }

        self::writeCache($cacheKey, $metadata);
        return $metadata;
    }

    /**
     * Builds canonical, zero-inline-style, sandboxed provider `<iframe>` HTML.
     */
    public static function buildEmbedHtml(string $provider, string $postId, array $options = []): string
    {
        $provider = strtolower(trim($provider));
        $lang = preg_replace('/[^a-zA-Z_-]/', '', (string) ($options['lang'] ?? 'en'));
        if ($lang === '') {
            $lang = 'en';
        }
        $theme = (isset($options['theme']) && $options['theme'] === 'dark') ? 'dark' : 'light';
        $mediaType = (string) ($options['media_type'] ?? 'post');
        $canonicalUrl = trim((string) ($options['url'] ?? ''));
        $title = trim((string) ($options['title'] ?? ''));

        if ($provider === 'x') {
            if ($canonicalUrl === '') {
                $canonicalUrl = 'https://x.com/i/web/status/' . rawurlencode($postId);
            }
            $src = 'https://platform.twitter.com/embed/Tweet.html?id=' . rawurlencode($postId)
                . '&theme=' . rawurlencode($theme)
                . '&dnt=true&hideThread=false&lang=' . rawurlencode($lang);
            $height = 620;
            $fallbackTitle = $title !== '' ? $title : 'X Post ' . $postId;
        } elseif ($provider === 'facebook') {
            if ($canonicalUrl === '') {
                $canonicalUrl = 'https://www.facebook.com/';
            }
            $plugin = ($mediaType === 'video') ? 'video' : 'post';
            $locale = self::mapFacebookLocale($lang);
            $src = 'https://www.facebook.com/plugins/' . $plugin . '.php?href=' . rawurlencode($canonicalUrl)
                . '&show_text=true&width=500&locale=' . rawurlencode($locale);
            $height = 658;
            $fallbackTitle = $title !== '' ? $title : 'Facebook Post ' . $postId;
        } elseif ($provider === 'instagram') {
            $segment = ($mediaType === 'reel') ? 'reel' : 'p';
            $src = 'https://www.instagram.com/' . $segment . '/' . rawurlencode($postId) . '/embed/captioned/';
            $height = 812;
            $fallbackTitle = $title !== '' ? $title : 'Instagram Post ' . $postId;
        } else {
            return '';
        }

        $safeSrc = htmlspecialchars($src, ENT_QUOTES, 'UTF-8');
        $safeTitle = htmlspecialchars($fallbackTitle, ENT_QUOTES, 'UTF-8');
        $safeProvider = htmlspecialchars($provider, ENT_QUOTES, 'UTF-8');
        $safePostId = htmlspecialchars($postId, ENT_QUOTES, 'UTF-8');

        return '<iframe class="social-post-frame social-post-frame--' . $safeProvider . '"'
            . ' width="550" height="' . $height . '"'
            . ' src="' . $safeSrc . '"'
            . ' title="' . $safeTitle . '"'
            . ' data-provider="' . $safeProvider . '"'
            . ' data-status-id="' . $safePostId . '"'
            . ' sandbox="allow-scripts allow-same-origin allow-popups allow-popups-to-escape-sandbox allow-presentation"'
            . ' loading="lazy"'
            . ' referrerpolicy="strict-origin-when-cross-origin"'
            . ' allowfullscreen></iframe>';
    }

    /**
     * Validates a URL or embed snippet and rebuilds canonical `embed_html` server-side.
     */
    public static function sanitizeOrRebuildEmbed(string $inputUrl, ?string $rawEmbed = null, array $options = []): array
    {
        $detected = self::detectProvider($inputUrl);
        if ($detected === null && $rawEmbed !== null && trim($rawEmbed) !== '') {
            $detected = self::detectProvider($rawEmbed);
        }
        if ($detected === null) {
            return [];
        }

        $mediaType = !empty($options['media_type']) ? (string) $options['media_type'] : $detected['media_type'];
        $canonicalUrl = !empty($options['url']) ? (string) $options['url'] : $detected['canonical_url'];

        $embedHtml = self::buildEmbedHtml($detected['provider'], $detected['post_id'], [
            'url' => $canonicalUrl,
            'media_type' => $mediaType,
            'lang' => $options['lang'] ?? 'en',
            'theme' => $options['theme'] ?? 'light',
            'title' => $options['title'] ?? '',
        ]);

        return [
            'provider' => $detected['provider'],
            'post_id' => $detected['post_id'],
            'media_type' => $mediaType,
            'canonical_url' => $canonicalUrl,
            'handle' => $detected['handle'],
            'embed_html' => $embedHtml,
        ];
    }

    /**
     * Extracts ordered `mod_socialmedia_post.id` values from `[socialpost:N]` tokens.
     */
    public static function extractTokenIds(string $html): array
    {
        if ($html === '' || strpos($html, '[socialpost:') === false) {
            return [];
        }
        if (!preg_match_all('/\[socialpost:(\d+)\]/i', $html, $matches)) {
            return [];
        }
        $ids = [];
        foreach ($matches[1] as $rawId) {
            $numericId = (int) $rawId;
            if ($numericId > 0 && !in_array($numericId, $ids, true)) {
                $ids[] = $numericId;
            }
        }
        return $ids;
    }

    /**
     * Strips all `[socialpost:N]` tokens and resulting empty wrapper `<p></p>` blocks.
     */
    public static function stripTokens(string $html): string
    {
        if ($html === '' || strpos($html, '[socialpost:') === false) {
            return $html;
        }

        $cleaned = preg_replace(
            '/<p(?:\s[^>]*)?>\s*(?:&nbsp;|\xc2\xa0|\s)*\[socialpost:\d+\]\s*(?:&nbsp;|\xc2\xa0|\s)*<\/p>/iu',
            '',
            $html
        );
        $cleaned = preg_replace('/\[socialpost:\d+\]/i', '', (string) $cleaned);
        $cleaned = preg_replace('/<p(?:\s[^>]*)?>\s*(?:&nbsp;|\xc2\xa0|\s)*<\/p>/iu', '', (string) $cleaned);

        return trim((string) $cleaned);
    }

    /**
     * Expands valid published `[socialpost:N]` tokens into HTML5 `<figure>` embeds
     * with clean paragraph unwrapping and optional fallback rendering for unbound rows.
     */
    public static function renderTokens(string $html, array $boundRows, array $uiLabels = [], bool $appendUnreferenced = true): string
    {
        $rowsById = [];
        foreach ($boundRows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $rowId = (int) ($row['id'] ?? 0);
            if ($rowId <= 0) {
                continue;
            }
            if (isset($row['status']) && $row['status'] !== 'y') {
                continue;
            }
            $rowsById[$rowId] = $row;
        }

        $referencedIds = [];

        // 1. Unwrap standalone `<p>[socialpost:N]</p>` directly into `<figure>`.
        $rendered = preg_replace_callback(
            '/<p(?:\s[^>]*)?>\s*(?:&nbsp;|\xc2\xa0|\s)*\[socialpost:(\d+)\]\s*(?:&nbsp;|\xc2\xa0|\s)*<\/p>/iu',
            static function (array $match) use ($rowsById, $uiLabels, &$referencedIds): string {
                $postId = (int) $match[1];
                if (!isset($rowsById[$postId])) {
                    return '';
                }
                $referencedIds[$postId] = true;
                return self::buildPublicFigureHtml($rowsById[$postId], $uiLabels);
            },
            $html
        );

        // 2. Split mid-paragraph `<p>before [socialpost:N] after</p>` safely around `<figure>`.
        $rendered = preg_replace_callback(
            '/<p(\s[^>]*)?>((?:(?!<\/p>).)*?\[socialpost:\d+\](?:(?!<\/p>).)*?)<\/p>/isu',
            static function (array $match) use ($rowsById, $uiLabels, &$referencedIds): string {
                $pAttrs = $match[1] ?? '';
                $innerHtml = $match[2];
                $parts = preg_split('/(\[socialpost:\d+\])/i', $innerHtml, -1, PREG_SPLIT_DELIM_CAPTURE);
                if (!is_array($parts)) {
                    return $match[0];
                }

                $output = '';
                foreach ($parts as $part) {
                    if (preg_match('/^\[socialpost:(\d+)\]$/i', $part, $tokenMatch)) {
                        $postId = (int) $tokenMatch[1];
                        if (isset($rowsById[$postId])) {
                            $referencedIds[$postId] = true;
                            $output .= self::buildPublicFigureHtml($rowsById[$postId], $uiLabels);
                        }
                    } else {
                        $trimmedCheck = trim((string) preg_replace('/(?:&nbsp;|\xc2\xa0|<br\s*\/?>)/i', '', $part));
                        if ($trimmedCheck !== '') {
                            $output .= '<p' . $pAttrs . '>' . trim($part) . '</p>';
                        }
                    }
                }
                return $output;
            },
            (string) $rendered
        );

        // 3. Replace any bare `[socialpost:N]` outside `<p>` elements.
        $rendered = preg_replace_callback(
            '/\[socialpost:(\d+)\]/i',
            static function (array $match) use ($rowsById, $uiLabels, &$referencedIds): string {
                $postId = (int) $match[1];
                if (!isset($rowsById[$postId])) {
                    return '';
                }
                $referencedIds[$postId] = true;
                return self::buildPublicFigureHtml($rowsById[$postId], $uiLabels);
            },
            (string) $rendered
        );

        // 4. Append unreferenced bound rows in a post-prose fallback section (zero `<p>` tags inside!).
        if ($appendUnreferenced && !empty($rowsById)) {
            $fallbackFigures = '';
            foreach ($rowsById as $rowId => $row) {
                if (!isset($referencedIds[$rowId])) {
                    $fallbackFigures .= self::buildPublicFigureHtml($row, $uiLabels);
                }
            }
            if ($fallbackFigures !== '') {
                $rendered .= '<section class="social-post-fallback">' . $fallbackFigures . '</section>';
            }
        }

        return (string) $rendered;
    }

    /**
     * Builds the public `<figure class="social-post-embed">` block with click-to-load facade
     * and zero `<p>` tags inside `<figcaption>` so downstream paragraph split helpers remain safe.
     */
    public static function buildPublicFigureHtml(array $row, array $uiLabels = []): string
    {
        $provider = strtolower(trim((string) ($row['provider'] ?? 'x')));
        $mediaType = trim((string) ($row['media_type'] ?? 'post'));
        $canonicalUrl = trim((string) ($row['url'] ?? ''));
        $name = self::decodeEntities((string) ($row['name'] ?? ''));
        $handle = ltrim(self::decodeEntities((string) ($row['handle'] ?? '')), '@');
        $excerpt = self::decodeEntities((string) ($row['content'] ?? ''));
        $image = trim((string) ($row['image'] ?? ''));

        if ($canonicalUrl === '' && $excerpt === '' && $name === '' && $image === '') {
            return '';
        }

        $providerLabel = self::providerDisplayName($provider, $mediaType);
        $viewSourceLabel = self::resolveSourceLinkLabel($provider, $uiLabels);

        $safeProvider = htmlspecialchars($provider, ENT_QUOTES, 'UTF-8');
        $safeTitle = htmlspecialchars($name !== '' ? $name : $providerLabel, ENT_QUOTES, 'UTF-8');
        $safeUrl = htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8');
        $safeProviderLabel = htmlspecialchars($providerLabel, ENT_QUOTES, 'UTF-8');
        $safeViewSourceLabel = htmlspecialchars($viewSourceLabel, ENT_QUOTES, 'UTF-8');

        $thumbHtml = '';
        if ($image !== '') {
            $safeImageSrc = htmlspecialchars(self::resolvePublicImageUrl($image), ENT_QUOTES, 'UTF-8');
            $thumbHtml = '<div class="social-post-thumb-wrap">'
                . '<img class="social-post-thumb" src="' . $safeImageSrc . '" alt="' . $safeTitle . '" loading="lazy">'
                . '</div>';
        }

        $handleHtml = '';
        if ($handle !== '') {
            $safeHandle = htmlspecialchars('@' . $handle, ENT_QUOTES, 'UTF-8');
            $handleHtml = '<bdi dir="ltr" class="social-post-handle">' . $safeHandle . '</bdi>';
        }

        // The excerpt is the post body; the title is only shown when no body exists to avoid duplicated text.
        $bodyHtml = '';
        if ($excerpt !== '') {
            $trimmedExcerpt = mb_strlen($excerpt, 'UTF-8') > 280 ? mb_substr($excerpt, 0, 277, 'UTF-8') . '...' : $excerpt;
            $bodyHtml = '<div class="social-post-excerpt">' . htmlspecialchars($trimmedExcerpt, ENT_QUOTES, 'UTF-8') . '</div>';
        } elseif ($name !== '') {
            $bodyHtml = '<div class="social-post-facade-title">' . $safeTitle . '</div>';
        }

        $iconClass = ($provider === 'x') ? 'bi-twitter-x' : (($provider === 'facebook') ? 'bi-facebook' : 'bi-instagram');

        $cardHtml = '<div class="social-post-facade">'
            . '<div class="social-post-facade-head">'
            . '<span class="social-post-provider-pill"><i class="bi ' . $iconClass . '" aria-hidden="true"></i><span>' . $safeProviderLabel . '</span></span>'
            . $handleHtml
            . '</div>'
            . $bodyHtml
            . $thumbHtml
            . '</div>';

        $captionParts = [];
        $captionParts[] = '<span class="social-post-caption-source"><i class="bi ' . $iconClass . '" aria-hidden="true"></i><cite>' . $safeProviderLabel . '</cite>' . ($handleHtml !== '' ? ' · ' . $handleHtml : '') . '</span>';
        if ($safeUrl !== '') {
            $captionParts[] = '<a class="social-post-external-link" href="' . $safeUrl . '" target="_blank" rel="noopener noreferrer">'
                . '<span>' . $safeViewSourceLabel . '</span>'
                . '<i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>'
                . '</a>';
        }

        return '<figure class="social-post-embed social-post-embed--' . $safeProvider . '" data-social-post data-provider="' . $safeProvider . '">'
            . $cardHtml
            . '<figcaption class="social-post-caption">' . implode('', $captionParts) . '</figcaption>'
            . '</figure>';
    }

    /**
     * Strips only unsafe ASCII control characters while preserving 4-byte UTF-8 emojis.
     */
    public static function sanitizeUtf8Text(?string $value): string
    {
        $text = (string) $value;
        if ($text === '') {
            return '';
        }
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);
        return trim((string) $text);
    }

    /**
     * Downloads and caches an external social thumbnail locally under `static/mod_socialmedia_post/`.
     */
    public static function importRemoteThumbnail(string $remoteUrl): string
    {
        $remoteUrl = trim($remoteUrl);
        if ($remoteUrl === '') {
            return '';
        }
        try {
            $response = self::httpRequest($remoteUrl, 6, 2097152);
        } catch (Exception $e) {
            return '';
        }

        if (($response['status'] ?? 0) < 200 || ($response['status'] ?? 0) >= 300 || empty($response['body'])) {
            return '';
        }

        $body = (string) $response['body'];
        $imageInfo = @getimagesizefromstring($body);
        if (!is_array($imageInfo) || empty($imageInfo['mime'])) {
            return '';
        }

        $mimeToExtension = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        ];
        $extension = $mimeToExtension[strtolower((string) $imageInfo['mime'])] ?? '';
        if ($extension === '') {
            return '';
        }

        $baseRoot = defined('root_dir') ? root_dir : dirname(__DIR__) . DIRECTORY_SEPARATOR;
        $targetDirectory = rtrim($baseRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'static' . DIRECTORY_SEPARATOR . 'mod_socialmedia_post';
        if (!is_dir($targetDirectory)) {
            @mkdir($targetDirectory, 0775, true);
        }
        if (!is_dir($targetDirectory) || !is_writable($targetDirectory)) {
            return '';
        }

        $filename = 'sp_' . substr(sha1($remoteUrl), 0, 20) . '.' . $extension;
        $fullPath = $targetDirectory . DIRECTORY_SEPARATOR . $filename;
        if (@file_put_contents($fullPath, $body) === false) {
            return '';
        }

        $thumbDirectory = $targetDirectory . DIRECTORY_SEPARATOR . 'thumb';
        if (!is_dir($thumbDirectory)) {
            @mkdir($thumbDirectory, 0775, true);
        }
        if (is_dir($thumbDirectory) && is_writable($thumbDirectory)) {
            @file_put_contents($thumbDirectory . DIRECTORY_SEPARATOR . $filename, $body);
        }

        return $filename;
    }

    /**
     * Deletes a cached local thumbnail under `static/mod_socialmedia_post/`.
     */
    public static function deleteLocalThumbnail(?string $filename): void
    {
        $safeName = basename(trim((string) $filename));
        if ($safeName === '' || $safeName === '.' || $safeName === '..') {
            return;
        }
        $baseRoot = defined('root_dir') ? root_dir : dirname(__DIR__) . DIRECTORY_SEPARATOR;
        $moduleRoot = rtrim($baseRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'static' . DIRECTORY_SEPARATOR . 'mod_socialmedia_post';
        $targetPath = $moduleRoot . DIRECTORY_SEPARATOR . $safeName;
        if (is_file($targetPath)) {
            @unlink($targetPath);
        }
        $thumbPath = $moduleRoot . DIRECTORY_SEPARATOR . 'thumb' . DIRECTORY_SEPARATOR . $safeName;
        if (is_file($thumbPath)) {
            @unlink($thumbPath);
        }
    }

    private static function fetchXMetadata(string $postId, string $canonicalUrl, string $lang, string $mediaType): array
    {
        $token = self::computeXSyndicationToken($postId);
        $syndicationUrl = 'https://cdn.syndication.twimg.com/tweet-result?id=' . rawurlencode($postId)
            . '&token=' . rawurlencode($token) . '&lang=' . rawurlencode($lang !== '' ? $lang : 'en');

        try {
            $response = self::httpRequest($syndicationUrl, 6);
            $status = (int) ($response['status'] ?? 0);
            if ($status === 404) {
                throw new Exception('autofill.tombstone_error');
            }
            if ($status >= 200 && $status < 300) {
                $json = json_decode((string) $response['body'], true);
                if (is_array($json)) {
                    if (($json['__typename'] ?? '') === 'TweetTombstone') {
                        throw new Exception('autofill.tombstone_error');
                    }
                    $screenName = trim((string) ($json['user']['screen_name'] ?? ''));
                    $authorName = trim((string) ($json['user']['name'] ?? ''));
                    $tweetText = self::sanitizeUtf8Text((string) ($json['text'] ?? ''));
                    $resolvedMediaType = $mediaType;
                    $thumbnailUrl = '';

                    if (!empty($json['mediaDetails']) && is_array($json['mediaDetails'])) {
                        foreach ($json['mediaDetails'] as $mediaDetail) {
                            if (!is_array($mediaDetail)) {
                                continue;
                            }
                            $detailType = (string) ($mediaDetail['type'] ?? '');
                            if (in_array($detailType, ['video', 'animated_gif'], true)) {
                                $resolvedMediaType = 'video';
                            }
                            if ($thumbnailUrl === '' && !empty($mediaDetail['media_url_https'])) {
                                $thumbnailUrl = (string) $mediaDetail['media_url_https'];
                            }
                        }
                    }
                    if ($thumbnailUrl === '' && !empty($json['video']['poster'])) {
                        $thumbnailUrl = (string) $json['video']['poster'];
                    }

                    $displayTitle = $authorName !== '' ? $authorName : ($screenName !== '' ? '@' . $screenName : 'X Post');
                    if ($tweetText !== '') {
                        $snippet = mb_strlen($tweetText, 'UTF-8') > 90 ? mb_substr($tweetText, 0, 87, 'UTF-8') . '...' : $tweetText;
                        $displayTitle = ($screenName !== '' ? '@' . $screenName . ': ' : '') . $snippet;
                    }

                    return [
                        'provider' => 'x',
                        'post_id' => $postId,
                        'media_type' => $resolvedMediaType,
                        'canonical_url' => $screenName !== '' ? 'https://x.com/' . $screenName . '/status/' . $postId : $canonicalUrl,
                        'name' => self::clampTitle($displayTitle),
                        'handle' => $screenName,
                        'content' => $tweetText,
                        'thumbnail_url' => $thumbnailUrl,
                        'is_partial' => false,
                        'source' => 'x_syndication',
                    ];
                }
            }
        } catch (Exception $e) {
            if ($e->getMessage() === 'autofill.tombstone_error') {
                throw $e;
            }
        }

        // Fallback to `publish.twitter.com/oembed` when syndication fails or throttles.
        $oembedEndpoint = 'https://publish.twitter.com/oembed?url=' . rawurlencode($canonicalUrl) . '&dnt=true&omit_script=true';
        try {
            $oembedResponse = self::httpRequest($oembedEndpoint, 6);
            if (($oembedResponse['status'] ?? 0) >= 200 && ($oembedResponse['status'] ?? 0) < 300) {
                $oembedJson = json_decode((string) $oembedResponse['body'], true);
                if (is_array($oembedJson)) {
                    $authorName = self::sanitizeUtf8Text((string) ($oembedJson['author_name'] ?? ''));
                    $authorUrl = (string) ($oembedJson['author_url'] ?? '');
                    $handle = '';
                    if (preg_match('/(?:x|twitter)\.com\/([A-Za-z0-9_]{1,15})/i', $authorUrl, $handleMatch)) {
                        $handle = $handleMatch[1];
                    }
                    $plainText = '';
                    if (!empty($oembedJson['html']) && preg_match('/<p\b[^>]*>(.*?)<\/p>/is', (string) $oembedJson['html'], $pMatch)) {
                        $plainText = self::sanitizeUtf8Text(html_entity_decode(strip_tags($pMatch[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                    }
                    $title = $authorName !== '' ? $authorName : ($handle !== '' ? '@' . $handle : 'X Post');
                    if ($plainText !== '') {
                        $snippet = mb_strlen($plainText, 'UTF-8') > 90 ? mb_substr($plainText, 0, 87, 'UTF-8') . '...' : $plainText;
                        $title = ($handle !== '' ? '@' . $handle . ': ' : '') . $snippet;
                    }

                    return [
                        'provider' => 'x',
                        'post_id' => $postId,
                        'media_type' => $mediaType,
                        'canonical_url' => $canonicalUrl,
                        'name' => self::clampTitle($title),
                        'handle' => $handle,
                        'content' => $plainText,
                        'thumbnail_url' => '',
                        'is_partial' => true,
                        'source' => 'x_oembed',
                    ];
                }
            }
        } catch (Exception $e) {
            // Fall through to graceful partial metadata below.
        }

        return [
            'provider' => 'x',
            'post_id' => $postId,
            'media_type' => $mediaType,
            'canonical_url' => $canonicalUrl,
            'name' => 'X Post #' . $postId,
            'handle' => '',
            'content' => '',
            'thumbnail_url' => '',
            'is_partial' => true,
            'source' => 'x_fallback',
        ];
    }

    private static function fetchFacebookMetadata(string $postId, string $canonicalUrl, string $lang, string $mediaType): array
    {
        $metaToken = self::resolveMetaAccessToken();
        if ($metaToken !== '') {
            $endpointName = ($mediaType === 'video') ? 'oembed_video' : 'oembed_post';
            $graphUrl = 'https://graph.facebook.com/v21.0/' . $endpointName
                . '?url=' . rawurlencode($canonicalUrl)
                . '&omitscript=true&access_token=' . rawurlencode($metaToken);
            try {
                $graphResp = self::httpRequest($graphUrl, 6);
                if (($graphResp['status'] ?? 0) >= 200 && ($graphResp['status'] ?? 0) < 300) {
                    $graphJson = json_decode((string) $graphResp['body'], true);
                    if (is_array($graphJson) && empty($graphJson['error'])) {
                        $authorName = self::sanitizeUtf8Text((string) ($graphJson['author_name'] ?? ''));
                        $title = $authorName !== '' ? $authorName : 'Facebook Post';
                        return [
                            'provider' => 'facebook',
                            'post_id' => $postId,
                            'media_type' => $mediaType,
                            'canonical_url' => $canonicalUrl,
                            'name' => self::clampTitle($title),
                            'handle' => '',
                            'content' => '',
                            'thumbnail_url' => (string) ($graphJson['thumbnail_url'] ?? ''),
                            'is_partial' => true,
                            'source' => 'facebook_graph_oembed',
                        ];
                    }
                }
            } catch (Exception $e) {
                // Fall back to OpenGraph scrape.
            }
        }

        $ogMeta = self::fetchOpenGraphMetadata($canonicalUrl);
        $handle = '';
        if (preg_match('/facebook\.com\/([A-Za-z0-9._-]+)\/(?:posts|videos)\//i', $canonicalUrl, $handleMatch)) {
            $handle = $handleMatch[1];
        }
        $title = $ogMeta['title'] !== '' ? $ogMeta['title'] : ($handle !== '' ? 'Facebook: ' . $handle : 'Facebook Post');

        return [
            'provider' => 'facebook',
            'post_id' => $postId,
            'media_type' => $mediaType,
            'canonical_url' => $canonicalUrl,
            'name' => self::clampTitle($title),
            'handle' => $handle,
            'content' => $ogMeta['description'],
            'thumbnail_url' => $ogMeta['image'],
            'is_partial' => ($ogMeta['title'] === '' && $ogMeta['description'] === ''),
            'source' => $ogMeta['title'] !== '' ? 'facebook_opengraph' : 'facebook_fallback',
        ];
    }

    private static function fetchInstagramMetadata(string $postId, string $canonicalUrl, string $lang, string $mediaType): array
    {
        $metaToken = self::resolveMetaAccessToken();
        if ($metaToken !== '') {
            $graphUrl = 'https://graph.facebook.com/v21.0/instagram_oembed'
                . '?url=' . rawurlencode($canonicalUrl)
                . '&omitscript=true&access_token=' . rawurlencode($metaToken);
            try {
                $graphResp = self::httpRequest($graphUrl, 6);
                if (($graphResp['status'] ?? 0) >= 200 && ($graphResp['status'] ?? 0) < 300) {
                    $graphJson = json_decode((string) $graphResp['body'], true);
                    if (is_array($graphJson) && empty($graphJson['error'])) {
                        $authorName = self::sanitizeUtf8Text((string) ($graphJson['author_name'] ?? ''));
                        $title = self::sanitizeUtf8Text((string) ($graphJson['title'] ?? ''));
                        $displayTitle = $title !== '' ? $title : ($authorName !== '' ? '@' . ltrim($authorName, '@') : 'Instagram Post');
                        return [
                            'provider' => 'instagram',
                            'post_id' => $postId,
                            'media_type' => $mediaType,
                            'canonical_url' => $canonicalUrl,
                            'name' => self::clampTitle($displayTitle),
                            'handle' => ltrim($authorName, '@'),
                            'content' => $title,
                            'thumbnail_url' => (string) ($graphJson['thumbnail_url'] ?? ''),
                            'is_partial' => ($title === ''),
                            'source' => 'instagram_graph_oembed',
                        ];
                    }
                }
            } catch (Exception $e) {
                // Fall back to OpenGraph scrape.
            }
        }

        $ogMeta = self::fetchOpenGraphMetadata($canonicalUrl);
        $handle = '';
        if ($ogMeta['title'] !== '' && preg_match('/^([^:@]+?)\s+on\s+Instagram/i', $ogMeta['title'], $instagramAuthorMatch)) {
            $handle = trim($instagramAuthorMatch[1]);
        }
        $fallbackLabel = ($mediaType === 'reel') ? 'Instagram Reel ' : 'Instagram Post ';
        $title = $ogMeta['title'] !== '' ? $ogMeta['title'] : ($fallbackLabel . $postId);

        return [
            'provider' => 'instagram',
            'post_id' => $postId,
            'media_type' => $mediaType,
            'canonical_url' => $canonicalUrl,
            'name' => self::clampTitle($title),
            'handle' => $handle,
            'content' => $ogMeta['description'],
            'thumbnail_url' => $ogMeta['image'],
            'is_partial' => ($ogMeta['title'] === '' && $ogMeta['description'] === ''),
            'source' => $ogMeta['title'] !== '' ? 'instagram_opengraph' : 'instagram_fallback',
        ];
    }

    private static function fetchOpenGraphMetadata(string $url): array
    {
        $result = ['title' => '', 'description' => '', 'image' => ''];
        try {
            $response = self::httpRequest($url, 6, self::MAX_REMOTE_BYTES, true);
            $status = (int) ($response['status'] ?? 0);
            if ($status < 200 || $status >= 300 || empty($response['body'])) {
                return $result;
            }
            $html = (string) $response['body'];

            $ogTitle = self::extractMetaProperty($html, 'og:title');
            if ($ogTitle === '') {
                $ogTitle = self::extractMetaName($html, 'twitter:title');
            }
            $ogDesc = self::extractMetaProperty($html, 'og:description');
            if ($ogDesc === '') {
                $ogDesc = self::extractMetaName($html, 'description');
            }
            $ogImage = self::extractMetaProperty($html, 'og:image');

            // Reject generic Meta login walls from polluting editorial titles.
            $lowerTitle = strtolower($ogTitle);
            if ($lowerTitle === 'log in or sign up to view' || $lowerTitle === 'facebook' || $lowerTitle === 'instagram' || strpos($lowerTitle, 'login') === 0) {
                $ogTitle = '';
            }

            $result['title'] = self::sanitizeUtf8Text(html_entity_decode($ogTitle, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $result['description'] = self::sanitizeUtf8Text(html_entity_decode($ogDesc, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if ($ogImage !== '' && stripos($ogImage, 'https://') === 0) {
                $result['image'] = html_entity_decode($ogImage, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
        } catch (Exception $e) {
            return $result;
        }
        return $result;
    }

    private static function extractMetaProperty(string $html, string $property): string
    {
        $quoted = preg_quote($property, '/');
        if (preg_match('/<meta\b[^>]*property=["\']' . $quoted . '["\'][^>]*content=["\']([^"\']*)["\']/i', $html, $match)) {
            return trim($match[1]);
        }
        if (preg_match('/<meta\b[^>]*content=["\']([^"\']*)["\'][^>]*property=["\']' . $quoted . '["\']/i', $html, $match)) {
            return trim($match[1]);
        }
        return '';
    }

    private static function extractMetaName(string $html, string $name): string
    {
        $quoted = preg_quote($name, '/');
        if (preg_match('/<meta\b[^>]*name=["\']' . $quoted . '["\'][^>]*content=["\']([^"\']*)["\']/i', $html, $match)) {
            return trim($match[1]);
        }
        if (preg_match('/<meta\b[^>]*content=["\']([^"\']*)["\'][^>]*name=["\']' . $quoted . '["\']/i', $html, $match)) {
            return trim($match[1]);
        }
        return '';
    }

    private static function computeXSyndicationToken(string $statusId): string
    {
        $numeric = (float) $statusId;
        $scaled = ($numeric / 1e15) * M_PI;
        $integerPart = (int) floor($scaled);
        $fraction = $scaled - $integerPart;
        $base36Int = base_convert((string) $integerPart, 10, 36);
        $fractionDigits = '';
        for ($step = 0; $step < 6; $step++) {
            $fraction *= 36;
            $digit = (int) floor($fraction);
            $fractionDigits .= base_convert((string) $digit, 10, 36);
            $fraction -= $digit;
        }
        $token = str_replace(['0', '.'], '', $base36Int . $fractionDigits);
        return $token !== '' ? $token : '0';
    }

    private static function resolveMetaAccessToken(): string
    {
        $envToken = getenv('META_OEMBED_TOKEN');
        if (is_string($envToken) && trim($envToken) !== '') {
            return trim($envToken);
        }
        if (class_exists('Registry', false) && Registry::has('site')) {
            $siteConfig = Registry::get('site');
            if (is_array($siteConfig) && !empty($siteConfig['meta_oembed_token'])) {
                return trim((string) $siteConfig['meta_oembed_token']);
            }
        }
        return '';
    }

    /**
     * Performs an SSRF-validated HTTPS request with manual redirect hop verification.
     */
    private static function httpRequest(string $url, int $timeout = 6, int $maxBytes = self::MAX_REMOTE_BYTES, bool $browserHeaders = false): array
    {
        $currentUrl = $url;
        $hops = 0;

        while ($hops <= 3) {
            $parsed = parse_url($currentUrl);
            $scheme = strtolower((string) ($parsed['scheme'] ?? ''));
            $host = strtolower((string) ($parsed['host'] ?? ''));

            if ($scheme !== 'https' || $host === '' || !in_array($host, self::ALLOWED_HOSTS, true)) {
                throw new Exception('Disallowed remote host');
            }

            $ips = @gethostbynamel($host);
            if (!is_array($ips) || empty($ips)) {
                throw new Exception('Unable to resolve remote host');
            }
            foreach ($ips as $ipAddress) {
                if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                    throw new Exception('Blocked private or reserved IP address');
                }
            }

            $handle = curl_init($currentUrl);
            if ($handle === false) {
                throw new Exception('HTTP client initialization failed');
            }

            $bodyBuffer = '';
            $redirectLocation = '';
            $headers = $browserHeaders
                ? [
                    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language: en-US,en;q=0.9',
                ]
                : ['Accept: application/json,text/plain,*/*'];

            curl_setopt_array($handle, [
                CURLOPT_RETURNTRANSFER => false,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_USERAGENT => $browserHeaders
                    ? 'Mozilla/5.0 (compatible; BulletinBot/1.0; +https://amed.news)'
                    : 'AmedBulletin/1.0 (+https://amed.news)',
                CURLOPT_HEADERFUNCTION => static function ($ch, $headerLine) use (&$redirectLocation): int {
                    if (stripos($headerLine, 'Location:') === 0) {
                        $redirectLocation = trim(substr($headerLine, 9));
                    }
                    return strlen($headerLine);
                },
                CURLOPT_WRITEFUNCTION => static function ($ch, $chunk) use (&$bodyBuffer, $maxBytes): int {
                    $len = strlen($chunk);
                    if (strlen($bodyBuffer) + $len > $maxBytes) {
                        return 0;
                    }
                    $bodyBuffer .= $chunk;
                    return $len;
                },
            ]);

            curl_exec($handle);
            $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
            $contentType = (string) curl_getinfo($handle, CURLINFO_CONTENT_TYPE);
            if (PHP_VERSION_ID < 80000) {
                curl_close($handle);
            }

            if ($status >= 300 && $status < 400 && $redirectLocation !== '') {
                $currentUrl = self::resolveRedirectUrl($currentUrl, $redirectLocation);
                $hops++;
                continue;
            }

            return [
                'status' => $status,
                'body' => $bodyBuffer,
                'content_type' => $contentType,
            ];
        }

        throw new Exception('Too many redirects');
    }

    private static function resolveRedirectUrl(string $baseUrl, string $location): string
    {
        if (preg_match('/^https?:\/\//i', $location)) {
            return $location;
        }
        $parsed = parse_url($baseUrl);
        $scheme = $parsed['scheme'] ?? 'https';
        $host = $parsed['host'] ?? '';
        if (strpos($location, '//') === 0) {
            return $scheme . ':' . $location;
        }
        if (strpos($location, '/') === 0) {
            return $scheme . '://' . $host . $location;
        }
        return $scheme . '://' . $host . '/' . ltrim($location, '/');
    }

    private static function readCache(string $cacheKey): ?array
    {
        $path = self::cacheFilePath($cacheKey);
        if (!is_file($path)) {
            return null;
        }
        $modifiedTime = @filemtime($path);
        if ($modifiedTime === false || (time() - $modifiedTime) > self::CACHE_TTL_SECONDS) {
            @unlink($path);
            return null;
        }
        $raw = @file_get_contents($path);
        if (!is_string($raw) || $raw === '') {
            return null;
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : null;
    }

    private static function writeCache(string $cacheKey, array $payload): void
    {
        $path = self::cacheFilePath($cacheKey);
        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        if (is_dir($dir) && is_writable($dir)) {
            @file_put_contents($path, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }
    }

    private static function cacheFilePath(string $cacheKey): string
    {
        $baseRoot = defined('root_dir') ? root_dir : dirname(__DIR__) . DIRECTORY_SEPARATOR;
        return rtrim($baseRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'social_post' . DIRECTORY_SEPARATOR . $cacheKey . '.json';
    }

    private static function clampPostId(string $postId): string
    {
        $clean = trim($postId);
        if (mb_strlen($clean, 'UTF-8') > 128) {
            return mb_substr($clean, 0, 128, 'UTF-8');
        }
        return $clean;
    }

    private static function clampTitle(string $title): string
    {
        $clean = self::sanitizeUtf8Text($title);
        if (mb_strlen($clean, 'UTF-8') > 250) {
            return mb_substr($clean, 0, 247, 'UTF-8') . '...';
        }
        return $clean;
    }

    private static function decodeEntities(string $value): string
    {
        return html_entity_decode(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private static function mapFacebookLocale(string $lang): string
    {
        $map = [
            'tr' => 'tr_TR',
            'en' => 'en_US',
            'de' => 'de_DE',
            'fr' => 'fr_FR',
            'ar' => 'ar_AR',
            'fa' => 'fa_IR',
            'ru' => 'ru_RU',
            'sv' => 'sv_SE',
            'ku' => 'tr_TR',
            'ckb' => 'ar_AR',
            'zza' => 'tr_TR',
        ];
        return $map[strtolower($lang)] ?? 'en_US';
    }

    private static function providerDisplayName(string $provider, string $mediaType): string
    {
        if ($provider === 'x') {
            return 'X';
        }
        if ($provider === 'facebook') {
            return ($mediaType === 'video') ? 'Facebook Video' : 'Facebook';
        }
        return ($mediaType === 'reel') ? 'Instagram Reel' : 'Instagram';
    }

    private static function resolveSourceLinkLabel(string $provider, array $uiLabels): string
    {
        if ($provider === 'x' && !empty($uiLabels['view_on_x'])) {
            return (string) $uiLabels['view_on_x'];
        }
        if ($provider === 'facebook' && !empty($uiLabels['view_on_facebook'])) {
            return (string) $uiLabels['view_on_facebook'];
        }
        if ($provider === 'instagram' && !empty($uiLabels['view_on_instagram'])) {
            return (string) $uiLabels['view_on_instagram'];
        }
        return (string) ($uiLabels['view_original_post'] ?? 'View Original Post');
    }

    private static function resolvePublicImageUrl(string $image): string
    {
        if (preg_match('/^https?:\/\//i', $image)) {
            return $image;
        }
        $imageBaseUrl = '/static/';
        if (class_exists('Registry', false) && Registry::has('site')) {
            $siteConfig = Registry::get('site');
            if (is_array($siteConfig) && !empty($siteConfig['iaddress'])) {
                $imageBaseUrl = rtrim((string) $siteConfig['iaddress'], '/') . '/';
            }
        }
        return $imageBaseUrl . 'mod_socialmedia_post/' . ltrim($image, '/');
    }
}
