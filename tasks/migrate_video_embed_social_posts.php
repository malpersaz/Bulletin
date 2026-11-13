<?php

/**
 * Migration task: move misplaced non-video social posts from `mod_video_embed`
 * into `mod_socialmedia_post`, rewire host `mod_news` / `mod_publication` /
 * `mod_event` records from serialized `video` (`vid:N`) to `[socialpost:M]`
 * inline tokens + `mod_socialmedia_post_link` rows, and delete the legacy rows.
 *
 * True video records (YouTube, Vimeo, Facebook Watch/Reels, and X posts that
 * contain video or animated_gif media) remain untouched in `mod_video_embed`.
 *
 * Usage:
 *   php tasks/migrate_video_embed_social_posts.php --dry-run
 *   php tasks/migrate_video_embed_social_posts.php
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from CLI.\n");
    exit(1);
}

$cliArguments = $_SERVER['argv'] ?? [];
$isDryRun = in_array('--dry-run', is_array($cliArguments) ? $cliArguments : [], true);

$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = 'socialmedia-post-migration';

require dirname(__DIR__) . '/init.php';

if (!class_exists('SocialPostProviderService')) {
    require_once lib_dir . 'SocialPostProviderService.class.php';
}
if (!class_exists('VideoEmbedProviderService')) {
    require_once lib_dir . 'VideoEmbedProviderService.class.php';
}
if (!class_exists('BridgeCleanupService')) {
    require_once lib_dir . 'BridgeCleanupService.class.php';
}

echo '=== migrate_video_embed_social_posts (' . ($isDryRun ? 'DRY-RUN' : 'LIVE') . ") ===\n";

$db = Load::lib('Database');
$pdo = $db->get_connection();
$runtime = Load::lib('ModuleRuntime');

if (!$pdo) {
    fwrite(STDERR, "[FAIL] Database connection unavailable.\n");
    exit(1);
}
SocialPostProviderService::ensureUtf8mb4Connection($db);

foreach (['mod_video_embed', 'mod_socialmedia_post', 'mod_socialmedia_post_link'] as $requiredTable) {
    if (!$runtime->tableExists($requiredTable)) {
        fwrite(STDERR, "[FAIL] Required table '{$requiredTable}' does not exist. Run tasks/bootstrap_socialmedia_post.php first.\n");
        exit(1);
    }
}

$statement = $pdo->query('SELECT id, lang, name, original, embed, content, image, status, created_at, user_id, acl_id FROM `mod_video_embed` ORDER BY id ASC');
$embedRows = $statement ? $statement->fetchAll(\PDO::FETCH_ASSOC) : [];

$hostTables = [];
foreach (['mod_news', 'mod_publication', 'mod_event'] as $candidateTable) {
    if ($runtime->tableExists($candidateTable)) {
        $hostTables[] = $candidateTable;
    }
}

$scannedCount = 0;
$keptVideoCount = 0;
$migratedCount = 0;
$rewiredHostCount = 0;

foreach ($embedRows as $row) {
    $scannedCount++;
    $oldVideoEmbedId = (int) ($row['id'] ?? 0);
    $originalUrl = trim((string) ($row['original'] ?? ''));
    $embedHtml = trim((string) ($row['embed'] ?? ''));
    $sourceText = $originalUrl !== '' ? $originalUrl : $embedHtml;
    $lang = trim((string) ($row['lang'] ?? 'en')) ?: 'en';

    $socialDetected = SocialPostProviderService::detectProvider($originalUrl)
        ?? SocialPostProviderService::detectProvider($embedHtml);

    if ($socialDetected === null) {
        continue;
    }

    $shouldMigrate = false;
    $provider = (string) $socialDetected['provider'];

    if ($provider === 'instagram') {
        $shouldMigrate = true;
    } elseif ($provider === 'facebook') {
        if (
            VideoEmbedProviderService::isNonVideoSocialUrl($originalUrl)
            || VideoEmbedProviderService::isNonVideoSocialUrl($embedHtml)
            || preg_match('/<blockquote\b[^>]*\btwitter-tweet\b/i', $embedHtml) === 1
        ) {
            $shouldMigrate = true;
        } else {
            $facebookVideoDetected = VideoEmbedProviderService::detectProvider($originalUrl)
                ?? VideoEmbedProviderService::detectProvider($embedHtml);
            if ($facebookVideoDetected === null) {
                $shouldMigrate = true;
            }
        }
    } elseif ($provider === 'x') {
        if (
            VideoEmbedProviderService::isNonVideoSocialUrl($originalUrl)
            || VideoEmbedProviderService::isNonVideoSocialUrl($embedHtml)
            || preg_match('/<blockquote\b[^>]*\btwitter-tweet\b/i', $embedHtml) === 1
        ) {
            $shouldMigrate = true;
        } else {
            $xDetected = VideoEmbedProviderService::detectProvider($sourceText);
            if ($xDetected !== null && $xDetected['provider'] === 'x') {
                try {
                    VideoEmbedProviderService::fetchMetadata('x', $xDetected['id'], $lang, [
                        'has_explicit_video_path' => !empty($xDetected['has_explicit_video_path']),
                    ]);
                    $keptVideoCount++;
                    continue;
                } catch (\Throwable $e) {
                    if ($e->getMessage() === 'autofill.no_video_in_post') {
                        $shouldMigrate = true;
                    }
                }
            }
        }
    }

    if (!$shouldMigrate) {
        $keptVideoCount++;
        continue;
    }

    echo sprintf(
        "[MIGRATE] mod_video_embed #%d (%s:%s, lang=%s) -> mod_socialmedia_post\n",
        $oldVideoEmbedId,
        $provider,
        (string) $socialDetected['post_id'],
        $lang
    );

    if ($isDryRun) {
        foreach ($hostTables as $hostTable) {
            $hostQuery = $pdo->prepare("SELECT id, video, content FROM `{$hostTable}` WHERE `video` LIKE ?");
            $hostQuery->execute(['%vid:' . $oldVideoEmbedId . '%']);
            $hostMatches = $hostQuery->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($hostMatches as $hostRow) {
                $videoArray = @unserialize((string) ($hostRow['video'] ?? ''), ['allowed_classes' => false]);
                if (is_array($videoArray) && in_array('vid:' . $oldVideoEmbedId, $videoArray, true)) {
                    echo sprintf("  [DRY-RUN REWIRE] %s #%d\n", $hostTable, (int) $hostRow['id']);
                    $rewiredHostCount++;
                }
            }
        }
        $migratedCount++;
        continue;
    }

    // 1. Reuse existing (provider, post_id, lang) row if present, otherwise insert.
    $existingCheck = $pdo->prepare(
        'SELECT `id` FROM `mod_socialmedia_post` WHERE `provider` = ? AND `post_id` = ? AND `lang` = ? LIMIT 1'
    );
    $existingCheck->execute([$provider, (string) $socialDetected['post_id'], $lang]);
    $existingPostRow = $existingCheck->fetch(\PDO::FETCH_ASSOC);

    if ($existingPostRow && !empty($existingPostRow['id'])) {
        $newPostId = (int) $existingPostRow['id'];
    } else {
        $copiedImage = null;
        $oldImageName = basename(trim((string) ($row['image'] ?? '')));
        if ($oldImageName !== '' && $oldImageName !== '.' && $oldImageName !== '..') {
            $oldFullFile = root_dir . 'static' . DS . 'mod_video_embed' . DS . $oldImageName;
            $oldThumbFile = root_dir . 'static' . DS . 'mod_video_embed' . DS . 'thumb' . DS . $oldImageName;
            $sourceFile = is_file($oldFullFile) ? $oldFullFile : (is_file($oldThumbFile) ? $oldThumbFile : '');
            if ($sourceFile !== '') {
                $destDir = root_dir . 'static' . DS . 'mod_socialmedia_post';
                if (!is_dir($destDir)) {
                    @mkdir($destDir, 0775, true);
                }
                $extension = pathinfo($oldImageName, PATHINFO_EXTENSION) ?: 'jpg';
                $newFileName = Helper::randomkey(18) . '.' . $extension;
                if (@copy($sourceFile, $destDir . DS . $newFileName)) {
                    $copiedImage = $newFileName;
                }
            }
        }

        $safeEmbedHtml = SocialPostProviderService::buildEmbedHtml($provider, (string) $socialDetected['post_id'], [
            'url' => (string) $socialDetected['canonical_url'],
            'media_type' => (string) $socialDetected['media_type'],
            'lang' => $lang,
            'title' => (string) ($row['name'] ?? ''),
        ]);

        $insertPost = $pdo->prepare(
            'INSERT INTO `mod_socialmedia_post` (`lang`, `provider`, `media_type`, `post_id`, `url`, `embed_html`, `name`, `handle`, `content`, `image`, `status`, `created_at`, `user_id`, `acl_id`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $insertPost->execute([
            $lang,
            $provider,
            (string) ($socialDetected['media_type'] ?? 'post'),
            (string) $socialDetected['post_id'],
            (string) $socialDetected['canonical_url'],
            $safeEmbedHtml,
            (string) ($row['name'] ?? ''),
            (string) ($socialDetected['handle'] ?? ''),
            (string) ($row['content'] ?? ''),
            $copiedImage,
            in_array((string) ($row['status'] ?? 'n'), ['y', 'n'], true) ? (string) $row['status'] : 'n',
            (string) ($row['created_at'] ?? date('Y-m-d H:i:s')),
            max(1, (int) ($row['user_id'] ?? 1)),
            max(0, (int) ($row['acl_id'] ?? 0)),
        ]);
        $newPostId = (int) $pdo->lastInsertId();
    }

    // 2. Rewire host module records referencing vid:{oldVideoEmbedId}
    $targetRef = 'vid:' . $oldVideoEmbedId;
    $tokenTag = '[socialpost:' . $newPostId . ']';

    foreach ($hostTables as $hostTable) {
        $hostQuery = $pdo->prepare("SELECT id, video, content FROM `{$hostTable}` WHERE `video` LIKE ?");
        $hostQuery->execute(['%' . $targetRef . '%']);
        $hostMatches = $hostQuery->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($hostMatches as $hostRow) {
            $hostId = (int) ($hostRow['id'] ?? 0);
            $videoArray = @unserialize((string) ($hostRow['video'] ?? ''), ['allowed_classes' => false]);
            if (!is_array($videoArray) || !in_array($targetRef, $videoArray, true)) {
                continue;
            }

            // Link record in mod_socialmedia_post_link
            $linkCheck = $pdo->prepare(
                'SELECT `id` FROM `mod_socialmedia_post_link` WHERE `socialmedia_post_id` = ? AND `mod_name` = ? AND `vid` = ? LIMIT 1'
            );
            $linkCheck->execute([$newPostId, $hostTable, $hostId]);
            if (!$linkCheck->fetch()) {
                $linkInsert = $pdo->prepare(
                    'INSERT INTO `mod_socialmedia_post_link` (`socialmedia_post_id`, `mod_name`, `vid`, `sort_order`, `created_at`) VALUES (?, ?, ?, 0, ?)'
                );
                $linkInsert->execute([$newPostId, $hostTable, $hostId, date('Y-m-d H:i:s')]);
            }

            // Append [socialpost:M] token if absent
            $updatedContent = (string) ($hostRow['content'] ?? '');
            if (strpos($updatedContent, $tokenTag) === false) {
                $updatedContent .= "\n<p>" . $tokenTag . "</p>";
            }

            // Remove migrated vid:N from serialized video column
            $remainingRefs = array_values(array_filter($videoArray, static function ($ref) use ($targetRef) {
                return (string) $ref !== $targetRef;
            }));
            $updatedVideo = $remainingRefs !== [] ? serialize($remainingRefs) : '';

            $updateHost = $pdo->prepare("UPDATE `{$hostTable}` SET `content` = ?, `video` = ? WHERE `id` = ?");
            $updateHost->execute([$updatedContent, $updatedVideo, $hostId]);
            $rewiredHostCount++;
            echo sprintf("  [OK] Rewired %s #%d -> %s\n", $hostTable, $hostId, $tokenTag);
        }
    }

    // 3. Clean up old mod_video_embed bridge relations, images, and row
    BridgeCleanupService::cleanRecord('mod_video_embed', $oldVideoEmbedId);

    $oldImageName = basename(trim((string) ($row['image'] ?? '')));
    if ($oldImageName !== '' && $oldImageName !== '.' && $oldImageName !== '..') {
        $oldFullFile = root_dir . 'static' . DS . 'mod_video_embed' . DS . $oldImageName;
        $oldThumbFile = root_dir . 'static' . DS . 'mod_video_embed' . DS . 'thumb' . DS . $oldImageName;
        if (is_file($oldFullFile)) {
            @unlink($oldFullFile);
        }
        if (is_file($oldThumbFile)) {
            @unlink($oldThumbFile);
        }
    }

    $deleteOld = $pdo->prepare('DELETE FROM `mod_video_embed` WHERE `id` = ?');
    $deleteOld->execute([$oldVideoEmbedId]);
    $migratedCount++;
}

echo sprintf(
    "Summary: scanned=%d, kept_videos=%d, migrated_posts=%d, rewired_hosts=%d, dry_run=%s\n",
    $scannedCount,
    $keptVideoCount,
    $migratedCount,
    $rewiredHostCount,
    $isDryRun ? 'true' : 'false'
);
exit(0);
