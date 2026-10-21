<?php

/**
 * News Image Recompression & Optimization Tool
 * 
 * Recompresses news images to the new responsive 16:9 standard:
 * - Thumbnails: 480x270 WebP @ 75 quality
 * - Main images: 1280x720 WebP @ 80 quality (optional with --main)
 *
 * Usage:
 *   php tasks/recompress_news_images.php              # Optimize thumbnails for all available news images
 *   php tasks/recompress_news_images.php --limit=10   # Only process latest 10 images
 *   php tasks/recompress_news_images.php --main       # Also recompress main images to 1280x720 HD
 *   php tasks/recompress_news_images.php --dry-run    # Preview without writing changes
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from CLI.\n");
    exit(1);
}

$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = 'recompress-news-images';

require dirname(__DIR__) . '/init.php';

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;

$options = getopt('', ['limit::', 'main', 'dry-run', 'help']);

if (isset($options['help'])) {
    echo "Usage: php tasks/recompress_news_images.php [options]\n";
    echo "Options:\n";
    echo "  --limit=N   Process at most N images\n";
    echo "  --main      Also recompress main images to 1280x720 HD\n";
    echo "  --dry-run   Simulate operations without overwriting files\n";
    echo "  --help      Show this help message\n";
    exit(0);
}

$limit = isset($options['limit']) ? (int) $options['limit'] : 0;
$recompressMain = isset($options['main']);
$isDryRun = isset($options['dry-run']);

echo "=== Bulletin News Image Recompression ===\n";
echo "Dry Run: " . ($isDryRun ? "YES (simulation mode)" : "NO (writing files)") . "\n";
echo "Recompress Main Images: " . ($recompressMain ? "YES" : "NO (thumbs only)") . "\n";
if ($limit > 0) {
    echo "Limit: " . $limit . " images\n";
}
echo "----------------------------------------\n";

$driver = extension_loaded('gd') ? new GdDriver() : new ImagickDriver();
$manager = new ImageManager($driver);

$newsDir = root_dir . 'static' . DS . 'mod_news' . DS;
$thumbDir = $newsDir . 'thumb' . DS;

if (!is_dir($thumbDir)) {
    @mkdir($thumbDir, 0777, true);
}

$imageFiles = [];

// 1. Scan disk directory for available images
if (is_dir($newsDir)) {
    $scanned = scandir($newsDir);
    foreach ($scanned as $f) {
        if ($f === '.' || $f === '..' || $f === 'thumb') continue;
        if (preg_match('/\.(webp|jpg|jpeg|png)$/i', $f)) {
            $imageFiles[] = $f;
            if ($limit > 0 && count($imageFiles) >= $limit) {
                break;
            }
        }
    }
}

// 2. If disk is empty or limit not reached, try DB if available
if (empty($imageFiles) && extension_loaded('pdo_mysql')) {
    try {
        $dbConfig = config_dir . 'db.config.php';
        if (file_exists($dbConfig)) {
            $db = Load::lib('Database');
            $query = "SELECT image FROM mod_news WHERE image != '' AND status = 'y' ORDER BY id DESC";
            if ($limit > 0) {
                $query .= " LIMIT " . $limit;
            }
            $db->fetch_array($query);
            $rows = $db->getResult()->toArray();
            foreach ($rows as $r) {
                if (!empty($r['image'])) {
                    $imageFiles[] = $r['image'];
                }
            }
        }
    } catch (\Throwable $e) {
        // DB optional, ignore error
    }
}

if (empty($imageFiles)) {
    echo "[DONE] No news images found to process.\n";
    exit(0);
}

echo "Found " . count($imageFiles) . " images to inspect.\n\n";

$totalSavedBytes = 0;
$processedCount = 0;

foreach ($imageFiles as $filename) {
    $mainPath = $newsDir . $filename;
    $thumbPath = $thumbDir . $filename;

    $sourcePath = file_exists($mainPath) ? $mainPath : (file_exists($thumbPath) ? $thumbPath : null);

    if (!$sourcePath) {
        continue;
    }

    try {
        // 1. Process thumbnail (480x270 @ 75)
        $oldThumbSize = file_exists($thumbPath) ? filesize($thumbPath) : 0;
        
        $image = $manager->read($sourcePath);
        $image->cover(480, 270);
        
        $canvas = $manager->create($image->width(), $image->height());
        $canvas->place($image);
        $encodedThumb = $canvas->toWebp(75);
        $newThumbSize = strlen((string) $encodedThumb);

        if (!$isDryRun) {
            file_put_contents($thumbPath, (string) $encodedThumb);
        }

        $savedThumb = $oldThumbSize > 0 ? ($oldThumbSize - $newThumbSize) : 0;
        if ($savedThumb > 0) {
            $totalSavedBytes += $savedThumb;
        }

        $savedPct = $oldThumbSize > 0 ? round(($savedThumb / $oldThumbSize) * 100, 1) : 0;
        $oldSizeKb = round($oldThumbSize / 1024, 1);
        $newSizeKb = round($newThumbSize / 1024, 1);

        echo sprintf(
            "[THUMB] %-25s | Old: %6.1f KB -> New: %5.1f KB | Saved: %5.1f%% (%d bytes)\n",
            $filename,
            $oldSizeKb,
            $newSizeKb,
            $savedPct,
            $savedThumb
        );

        // 2. Optionally process main image (1280x720 @ 80)
        if ($recompressMain && file_exists($mainPath)) {
            $oldMainSize = filesize($mainPath);
            $mainImg = $manager->read($mainPath);
            if ($mainImg->width() > 1280 || $mainImg->height() > 720) {
                $mainImg->cover(1280, 720);
                $mainCanvas = $manager->create($mainImg->width(), $mainImg->height());
                $mainCanvas->place($mainImg);
                $encodedMain = $mainCanvas->toWebp(80);
                $newMainSize = strlen((string) $encodedMain);

                if (!$isDryRun) {
                    file_put_contents($mainPath, (string) $encodedMain);
                }

                $savedMain = $oldMainSize - $newMainSize;
                if ($savedMain > 0) {
                    $totalSavedBytes += $savedMain;
                }
                echo sprintf(
                    "[MAIN]  %-25s | Old: %6.1f KB -> New: %5.1f KB | Saved: %5.1f%%\n",
                    $filename,
                    round($oldMainSize / 1024, 1),
                    round($newMainSize / 1024, 1),
                    round(($savedMain / $oldMainSize) * 100, 1)
                );
            }
        }

        $processedCount++;
    } catch (\Throwable $e) {
        echo sprintf("[ERROR] %-25s: %s\n", $filename, $e->getMessage());
    }
}

echo "----------------------------------------\n";
echo sprintf("Summary: Processed %d images. Total bandwidth saved: %.2f KB (%.2f MB)\n",
    $processedCount,
    $totalSavedBytes / 1024,
    $totalSavedBytes / (1024 * 1024)
);
echo "=== Done ===\n";
