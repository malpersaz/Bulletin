<?php
/**
 * Standalone Cache Management Script for Amed Framework
 * Provides direct maintenance access without requiring administrative authentication.
 */

// Directory separator slash (Windows \ vs Linux /)
define('DS', '/');
// Root application directory path
define('root_dir', __DIR__ . DS);

// Load language file based on URL parameter or default to Kurdish (ku) or English (en)
$lang_param = $_GET['lang'] ?? 'ku';
$lang_file = root_dir . 'mod/index/lang/' . $lang_param . '.php';

// Baseline fallback in English
include_once root_dir . 'mod/index/lang/en.php';
if ($lang_param !== 'en' && file_exists($lang_file)) {
    include_once $lang_file;
}

/**
 * Recursively scans a directory and retrieves all file paths.
 *
 * @param string $dir
 * @return array
 */
function all_files(string $dir): array
{
    $allFiles = [];
    $dir = rtrim($dir, DS);
    $absoluteDir = strpos($dir, root_dir) === 0 ? $dir : root_dir . $dir;

    if (!is_dir($absoluteDir)) {
        return $allFiles;
    }

    $handler = opendir($absoluteDir);
    if ($handler === false) {
        return $allFiles;
    }

    while (($file = readdir($handler)) !== false) {
        if ($file !== '.' && $file !== '..' && $file !== '.gitkeep' && $file !== '.gitignore' && $file !== 'Thumb.db' && $file !== 'Thumbs.db') {
            $path = $absoluteDir . DS . $file;
            if (is_file($path)) {
                $allFiles[] = $path;
            } elseif (is_dir($path)) {
                $allFiles = array_merge($allFiles, all_files($path));
            }
        }
    }

    closedir($handler);
    return $allFiles;
}

/**
 * Clears all files within the given directory and renders status alerts.
 *
 * @param string $dir
 * @param array $lang
 * @return array
 */
function clear_directory(string $dir, array &$lang): array
{
    $files = all_files($dir);
    $deletedCount = 0;
    $errorCount = 0;
    $result = [
        'directory' => $dir,
        'deleted' => 0,
        'errors' => 0,
        'total' => count($files)
    ];

    if (empty($files)) {
        $noFilesMsg = $lang['index']['cache']['no_cache_files'] ?? 'No cache files to clear';
        echo "<div class='alert alert-info'><i class='bi bi-info-circle me-2'></i><strong>" . htmlspecialchars($dir, ENT_QUOTES, 'UTF-8') . ":</strong> " . htmlspecialchars($noFilesMsg, ENT_QUOTES, 'UTF-8') . "</div>";
        return $result;
    }

    $deletedPattern = $lang['index']['cache']['file_deleted'] ?? '%s deleted successfully';
    $deleteErrorPattern = $lang['index']['cache']['file_delete_error'] ?? 'Error deleting %s';

    foreach ($files as $file) {
        if (@unlink($file)) {
            echo "<div class='alert alert-success'><i class='bi bi-check-circle me-2'></i>" . sprintf($deletedPattern, htmlspecialchars(basename($file), ENT_QUOTES, 'UTF-8')) . "</div>";
            $deletedCount++;
        } else {
            echo "<div class='alert alert-danger'><i class='bi bi-exclamation-circle me-2'></i>" . sprintf($deleteErrorPattern, htmlspecialchars(basename($file), ENT_QUOTES, 'UTF-8')) . "</div>";
            $errorCount++;
        }
    }

    $result['deleted'] = $deletedCount;
    $result['errors'] = $errorCount;

    if ($errorCount === 0) {
        $successMsg = $lang['index']['cache']['cache_cleared_success'] ?? 'Cache cleared successfully!';
        echo "<div class='alert alert-success'><strong><i class='bi bi-check-circle-fill me-2'></i>" . htmlspecialchars($dir, ENT_QUOTES, 'UTF-8') . ":</strong> " . htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8') . " (" . $deletedCount . "/" . count($files) . ")</div>";
    } else {
        $partialMsg = $lang['index']['cache']['cache_cleared_partial'] ?? 'Cache operation completed';
        echo "<div class='alert alert-warning'><strong><i class='bi bi-exclamation-triangle me-2'></i>" . htmlspecialchars($dir, ENT_QUOTES, 'UTF-8') . ":</strong> " . htmlspecialchars($partialMsg, ENT_QUOTES, 'UTF-8') . " (" . $deletedCount . "/" . count($files) . ", " . $errorCount . " error)</div>";
    }

    return $result;
}

/**
 * Backwards compatibility alias matching Amed Framework standard.
 *
 * @param string $dir
 * @param array $lang
 * @return array
 */
function c(string $dir, array &$lang): array
{
    return clear_directory($dir, $lang);
}

/**
 * Renders high-level cleanup summary statistics.
 *
 * @param array $results
 * @return void
 */
function render_cleanup_summary(array $results): void
{
    if (empty($results)) {
        return;
    }

    $directories = count($results);
    $filesFound = 0;
    $filesDeleted = 0;
    $filesFailed = 0;

    foreach ($results as $result) {
        $filesFound += $result['total'];
        $filesDeleted += $result['deleted'];
        $filesFailed += $result['errors'];
    }

    $summaryClass = $filesFailed > 0 ? 'warning' : 'success';
    $summaryIcon = $filesFailed > 0 ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill';

    echo "<div class='alert alert-" . $summaryClass . "'>";
    echo "<strong><i class='bi " . $summaryIcon . " me-2'></i>Cleanup Summary</strong><br>";
    echo "Directories scanned: " . $directories . "<br>";
    echo "Files found: " . $filesFound . "<br>";
    echo "Files deleted: " . $filesDeleted . "<br>";
    echo "Files failed: " . $filesFailed;
    echo "</div>";
}

$directories = [
    'templates_c',
    'cache/css',
    'cache/js',
    'cache/sql',
    'cache/test_sql',
    'cache/img',
    'cache/video',
    'cache/video_tmp',
    'cache/video_log',
    'cache/llms',
    'cache/captcha'
];

$c = $_GET['cache'] ?? '';
$pageTitle = $lang['index']['cache']['title'] ?? 'Cache Management';
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang_param, ENT_QUOTES, 'UTF-8'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- External Custom CSS -->
    <link href="templates/base_static/css/cache-clear.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="cache-container">
                    <div class="page-header">
                        <h1><i class="bi bi-speedometer2 me-2"></i><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></h1>
                        <p>Optimize your website performance by clearing cache files</p>
                    </div>
                    <div class="cache-content">
                        <?php
                        $current_url = "?lang=" . htmlspecialchars($lang_param, ENT_QUOTES, 'UTF-8');
                        $cleanupResults = [];

                        if ($c === 'js') {
                            echo "<h2 class='section-title'><i class='bi bi-filetype-js me-2'></i>" . htmlspecialchars($lang['index']['cache']['cache_js'] ?? 'Javascript Cache', ENT_QUOTES, 'UTF-8') . "</h2>";
                            $cleanupResults[] = clear_directory('cache/js', $lang);
                        } elseif ($c === 'css') {
                            echo "<h2 class='section-title'><i class='bi bi-filetype-css me-2'></i>" . htmlspecialchars($lang['index']['cache']['cache_css'] ?? 'CSS Cache', ENT_QUOTES, 'UTF-8') . "</h2>";
                            $cleanupResults[] = clear_directory('cache/css', $lang);
                        } elseif ($c === 'img') {
                            echo "<h2 class='section-title'><i class='bi bi-image me-2'></i>" . htmlspecialchars($lang['index']['cache']['cache_image'] ?? 'Image Cache', ENT_QUOTES, 'UTF-8') . "</h2>";
                            $cleanupResults[] = clear_directory('cache/img', $lang);
                        } elseif ($c === 'video') {
                            echo "<h2 class='section-title'><i class='bi bi-play-circle me-2'></i>" . htmlspecialchars($lang['index']['cache']['cache_video'] ?? 'Video Cache', ENT_QUOTES, 'UTF-8') . "</h2>";
                            $cleanupResults[] = clear_directory('cache/video', $lang);
                            $cleanupResults[] = clear_directory('cache/video_tmp', $lang);
                            $cleanupResults[] = clear_directory('cache/video_log', $lang);
                        } elseif ($c === 'sql') {
                            echo "<h2 class='section-title'><i class='bi bi-database me-2'></i>" . htmlspecialchars($lang['index']['cache']['cache_sql'] ?? 'SQL Cache', ENT_QUOTES, 'UTF-8') . "</h2>";
                            $cleanupResults[] = clear_directory('cache/sql', $lang);
                            $cleanupResults[] = clear_directory('cache/test_sql', $lang);
                        } elseif ($c === 'templates') {
                            echo "<h2 class='section-title'><i class='bi bi-file-earmark-code me-2'></i>" . htmlspecialchars($lang['index']['cache']['cache_templates'] ?? 'Templates Cache', ENT_QUOTES, 'UTF-8') . "</h2>";
                            $cleanupResults[] = clear_directory('templates_c', $lang);
                        } elseif ($c === 'llms') {
                            $llmsTitle = $lang['index']['cache']['cache_llms'] ?? 'LLMs AI Cache';
                            echo "<h2 class='section-title'><i class='bi bi-cpu me-2'></i>" . htmlspecialchars($llmsTitle, ENT_QUOTES, 'UTF-8') . "</h2>";
                            $cleanupResults[] = clear_directory('cache/llms', $lang);
                        } elseif ($c === 'captcha') {
                            $captchaTitle = $lang['index']['cache']['cache_captcha'] ?? 'Captcha Cache';
                            echo "<h2 class='section-title'><i class='bi bi-shield-check me-2'></i>" . htmlspecialchars($captchaTitle, ENT_QUOTES, 'UTF-8') . "</h2>";
                            $cleanupResults[] = clear_directory('cache/captcha', $lang);
                        } elseif ($c === 'all') {
                            echo "<h2 class='section-title'><i class='bi bi-lightning-charge me-2'></i>" . htmlspecialchars($lang['index']['cache']['cache_all'] ?? 'ALL JS CSS SQL TEMPLATES Cache', ENT_QUOTES, 'UTF-8') . "</h2>";
                            foreach ($directories as $dirItem) {
                                $cleanupResults[] = clear_directory($dirItem, $lang);
                            }
                        } else {
                            ?>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <a href="<?php echo $current_url; ?>&amp;cache=img" class="list-group-item list-group-item-action">
                                        <div class="cache-icon img">
                                            <i class="bi bi-image"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h5 class="mb-1"><?php echo htmlspecialchars($lang['index']['cache']['cache_image'] ?? 'Image Cache', ENT_QUOTES, 'UTF-8'); ?></h5>
                                            <small class="text-muted">Clear image cache files</small>
                                        </div>
                                        <i class="bi bi-chevron-right text-muted"></i>
                                    </a>
                                </div>
                                <div class="col-md-6">
                                    <a href="<?php echo $current_url; ?>&amp;cache=js" class="list-group-item list-group-item-action">
                                        <div class="cache-icon js">
                                            <i class="bi bi-filetype-js"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h5 class="mb-1"><?php echo htmlspecialchars($lang['index']['cache']['cache_js'] ?? 'Javascript Cache', ENT_QUOTES, 'UTF-8'); ?></h5>
                                            <small class="text-muted">Clear JavaScript cache files</small>
                                        </div>
                                        <i class="bi bi-chevron-right text-muted"></i>
                                    </a>
                                </div>
                                <div class="col-md-6">
                                    <a href="<?php echo $current_url; ?>&amp;cache=video" class="list-group-item list-group-item-action">
                                        <div class="cache-icon video">
                                            <i class="bi bi-play-circle"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h5 class="mb-1"><?php echo htmlspecialchars($lang['index']['cache']['cache_video'] ?? 'Video Cache', ENT_QUOTES, 'UTF-8'); ?></h5>
                                            <small class="text-muted">Clear video cache files</small>
                                        </div>
                                        <i class="bi bi-chevron-right text-muted"></i>
                                    </a>
                                </div>
                                <div class="col-md-6">
                                    <a href="<?php echo $current_url; ?>&amp;cache=css" class="list-group-item list-group-item-action">
                                        <div class="cache-icon css">
                                            <i class="bi bi-filetype-css"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h5 class="mb-1"><?php echo htmlspecialchars($lang['index']['cache']['cache_css'] ?? 'CSS Cache', ENT_QUOTES, 'UTF-8'); ?></h5>
                                            <small class="text-muted">Clear CSS cache files</small>
                                        </div>
                                        <i class="bi bi-chevron-right text-muted"></i>
                                    </a>
                                </div>
                                <div class="col-md-6">
                                    <a href="<?php echo $current_url; ?>&amp;cache=sql" class="list-group-item list-group-item-action">
                                        <div class="cache-icon sql">
                                            <i class="bi bi-database"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h5 class="mb-1"><?php echo htmlspecialchars($lang['index']['cache']['cache_sql'] ?? 'SQL Cache', ENT_QUOTES, 'UTF-8'); ?></h5>
                                            <small class="text-muted">Clear SQL cache files</small>
                                        </div>
                                        <i class="bi bi-chevron-right text-muted"></i>
                                    </a>
                                </div>
                                <div class="col-md-6">
                                    <a href="<?php echo $current_url; ?>&amp;cache=templates" class="list-group-item list-group-item-action">
                                        <div class="cache-icon template">
                                            <i class="bi bi-file-earmark-code"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h5 class="mb-1"><?php echo htmlspecialchars($lang['index']['cache']['cache_templates'] ?? 'Templates Cache', ENT_QUOTES, 'UTF-8'); ?></h5>
                                            <small class="text-muted">Clear template cache files</small>
                                        </div>
                                        <i class="bi bi-chevron-right text-muted"></i>
                                    </a>
                                </div>
                                <div class="col-md-6">
                                    <a href="<?php echo $current_url; ?>&amp;cache=llms" class="list-group-item list-group-item-action">
                                        <div class="cache-icon llms">
                                            <i class="bi bi-cpu"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h5 class="mb-1"><?php echo htmlspecialchars($lang['index']['cache']['cache_llms'] ?? 'LLMs AI Cache', ENT_QUOTES, 'UTF-8'); ?></h5>
                                            <small class="text-muted">Clear LLMs index cache files</small>
                                        </div>
                                        <i class="bi bi-chevron-right text-muted"></i>
                                    </a>
                                </div>
                                <div class="col-md-6">
                                    <a href="<?php echo $current_url; ?>&amp;cache=captcha" class="list-group-item list-group-item-action">
                                        <div class="cache-icon captcha">
                                            <i class="bi bi-shield-check"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h5 class="mb-1"><?php echo htmlspecialchars($lang['index']['cache']['cache_captcha'] ?? 'Captcha Cache', ENT_QUOTES, 'UTF-8'); ?></h5>
                                            <small class="text-muted">Clear temporary captcha files</small>
                                        </div>
                                        <i class="bi bi-chevron-right text-muted"></i>
                                    </a>
                                </div>
                                <div class="col-12">
                                    <a href="<?php echo $current_url; ?>&amp;cache=all" class="list-group-item list-group-item-action primary-action">
                                        <div class="cache-icon all">
                                            <i class="bi bi-lightning-charge"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h5 class="mb-1"><?php echo htmlspecialchars($lang['index']['cache']['cache_all'] ?? 'ALL JS CSS SQL TEMPLATES Cache', ENT_QUOTES, 'UTF-8'); ?></h5>
                                            <small>Clear all cache files at once</small>
                                        </div>
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </div>
                            </div>
                            <?php
                        }

                        if ($c !== '') {
                            render_cleanup_summary($cleanupResults);
                        }

                        if ($c !== '') {
                            $backLabel = $lang['index']['cache']['back_to_management'] ?? 'Back to Cache Management';
                            ?>
                            <a href="<?php echo $current_url; ?>" class="back-link">
                                <i class="bi bi-arrow-left me-2"></i>
                                <?php echo htmlspecialchars($backLabel, ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                            <?php
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
