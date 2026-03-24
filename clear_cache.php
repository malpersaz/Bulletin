<?php
// Klasorler Arasındaki slash işareti win \ Linux /
define ('DS', '/');
// Sitenin bulunduğu Dizin
define ('root_dir', DIRNAME(__FILE__) . DS);

// Load language file based on URL parameter or default to English
$lang_param = $_GET['lang'] ?? 'en';
$lang_file = root_dir . 'mod/index/lang/' . $lang_param . '.php';

if (file_exists($lang_file)) {
    include_once $lang_file;
} else {
    include_once root_dir . 'mod/index/lang/en.php';
}

function all_files($dir)
{
    $filelar = array();
    $dir = rtrim($dir, DS);
    $absoluteDir = strpos($dir, root_dir) === 0 ? $dir : root_dir . $dir;

    if (!is_dir($absoluteDir)) {
        return $filelar;
    }

    $handler = opendir($absoluteDir);
    while (($f = readdir($handler)) !== FALSE) {
        if ($f != "." && $f != ".." && $f != "Thumb.db" && $f != "Thumbs.db") {
            $path = $absoluteDir . DS . $f;
            if (is_file($path)) {
                $filelar[] = $path;
            } elseif (is_dir($path)) {
                $filelar = array_merge($filelar, all_files($path));
            }
        }
    }

    closedir($handler);
    return $filelar;
}

function c($dizin, &$lang)
{
    $files = all_files($dizin);
    $deletedCount = 0;
    $errorCount = 0;
    $result = [
        'directory' => $dizin,
        'deleted' => 0,
        'errors' => 0,
        'total' => count($files)
    ];

    if (empty($files)) {
        echo "<div class='alert alert-info'><i class='bi bi-info-circle me-2'></i><strong>" . htmlspecialchars($dizin) . ":</strong> " . $lang['index']['cache']['no_cache_files'] . "</div>";
        return $result;
    }

    foreach ($files as $file) {
        if (unlink($file)) {
            echo "<div class='alert alert-success'><i class='bi bi-check-circle me-2'></i>" . sprintf($lang['index']['cache']['file_deleted'], basename($file)) . "</div>";
            $deletedCount++;
        } else {
            echo "<div class='alert alert-danger'><i class='bi bi-exclamation-circle me-2'></i>" . sprintf($lang['index']['cache']['file_delete_error'], basename($file)) . "</div>";
            $errorCount++;
        }
    }

    $result['deleted'] = $deletedCount;
    $result['errors'] = $errorCount;

    if ($errorCount === 0) {
        echo "<div class='alert alert-success'><strong><i class='bi bi-check-circle-fill me-2'></i>" . htmlspecialchars($dizin) . ":</strong> " . $lang['index']['cache']['cache_cleared_success'] . " (" . $deletedCount . "/" . count($files) . ")</div>";
    } else {
        echo "<div class='alert alert-warning'><strong><i class='bi bi-exclamation-triangle me-2'></i>" . htmlspecialchars($dizin) . ":</strong> " . $lang['index']['cache']['cache_cleared_partial'] . " (" . $deletedCount . "/" . count($files) . ", " . $errorCount . " error)</div>";
    }

    return $result;
}

function render_cleanup_summary($results)
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

$dizinler = array(
    'templates_c',
    'cache/css',
    'cache/js',
    'cache/sql',
    'cache/img',
    'cache/video',
    'cache/video_tmp',
    'cache/video_log'
);
$c = $_GET ['cache'] ?? '';
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang_param); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($lang['index']['cache']['title']); ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .cache-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .page-header {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            border-radius: 20px 20px 0 0;
            padding: 2rem;
            text-align: center;
        }
        .page-header h1 {
            margin: 0;
            font-weight: 600;
            font-size: 2rem;
        }
        .page-header p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
        }
        .cache-content {
            padding: 2rem;
        }
        .list-group-item {
            border: none;
            border-radius: 15px !important;
            margin-bottom: 0.75rem;
            padding: 1.25rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
        }
        .list-group-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        .cache-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            font-size: 1.25rem;
            color: white;
        }
        .cache-icon.css { background: #264de4; }
        .cache-icon.js { background: #f7df1e; color: #000; }
        .cache-icon.img { background: #ff6b6b; }
        .cache-icon.video { background: #4ecdc4; }
        .cache-icon.sql { background: #00758f; }
        .cache-icon.template { background: #8e44ad; }
        .cache-icon.all { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .alert {
            border: none;
            border-radius: 15px;
            margin-bottom: 1rem;
            padding: 1rem 1.5rem;
            font-weight: 500;
        }
        .alert-success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
        }
        .alert-danger {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
        }
        .alert-info {
            background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
            color: #0c5460;
        }
        .section-title {
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 3px solid #e9ecef;
        }
        .back-link {
            color: #6c757d;
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            margin-top: 1rem;
        }
        .back-link:hover {
            color: #495057;
        }
        .primary-action {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            color: white !important;
            font-weight: 600;
        }
        .primary-action:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%) !important;
            color: white !important;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="cache-container">
                    <div class="page-header">
                        <h1><i class="bi bi-speedometer2 me-2"></i><?php echo htmlspecialchars($lang['index']['cache']['title']); ?></h1>
                        <p>Optimize your website performance by clearing cache files</p>
                    </div>
                    <div class="cache-content">
                        <?php
                        $current_url = "?lang=" . htmlspecialchars($lang_param);

                        $cleanupResults = [];

                        if ($c == 'js') {
                            echo "<h2 class='section-title'><i class='bi bi-filetype-js me-2'></i>" . $lang['index']['cache']['cache_js'] . "</h2>";
                            $cleanupResults[] = c('cache/js', $lang);
                        } elseif ($c == 'css') {
                            echo "<h2 class='section-title'><i class='bi bi-filetype-css me-2'></i>" . $lang['index']['cache']['cache_css'] . "</h2>";
                            $cleanupResults[] = c('cache/css', $lang);
                        } elseif ($c == 'img') {
                            echo "<h2 class='section-title'><i class='bi bi-image me-2'></i>" . $lang['index']['cache']['cache_image'] . "</h2>";
                            $cleanupResults[] = c('cache/img', $lang);
                        } elseif ($c == 'video') {
                            echo "<h2 class='section-title'><i class='bi bi-play-circle me-2'></i>" . $lang['index']['cache']['cache_video'] . "</h2>";
                            $cleanupResults[] = c('cache/video', $lang);
                            $cleanupResults[] = c('cache/video_tmp', $lang);
                            $cleanupResults[] = c('cache/video_log', $lang);
                        } elseif ($c == 'sql') {
                            echo "<h2 class='section-title'><i class='bi bi-database me-2'></i>" . $lang['index']['cache']['cache_sql'] . "</h2>";
                            $cleanupResults[] = c('cache/sql', $lang);
                        } elseif ($c == 'templates') {
                            echo "<h2 class='section-title'><i class='bi bi-file-earmark-code me-2'></i>" . $lang['index']['cache']['cache_templates'] . "</h2>";
                            $cleanupResults[] = c('templates_c', $lang);
                        } elseif ($c == 'all') {
                            echo "<h2 class='section-title'><i class='bi bi-lightning-charge me-2'></i>" . $lang['index']['cache']['cache_all'] . "</h2>";
                            foreach ($dizinler as $dizin) {
                                $cleanupResults[] = c($dizin, $lang);
                            }
                        } else {
                            ?>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <a href="<?php echo $current_url; ?>&cache=img" class="list-group-item list-group-item-action">
                                        <div class="cache-icon img">
                                            <i class="bi bi-image"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h5 class="mb-1"><?php echo $lang['index']['cache']['cache_image']; ?></h5>
                                            <small class="text-muted">Clear image cache files</small>
                                        </div>
                                        <i class="bi bi-chevron-right text-muted"></i>
                                    </a>
                                </div>
                                <div class="col-md-6">
                                    <a href="<?php echo $current_url; ?>&cache=js" class="list-group-item list-group-item-action">
                                        <div class="cache-icon js">
                                            <i class="bi bi-filetype-js"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h5 class="mb-1"><?php echo $lang['index']['cache']['cache_js']; ?></h5>
                                            <small class="text-muted">Clear JavaScript cache files</small>
                                        </div>
                                        <i class="bi bi-chevron-right text-muted"></i>
                                    </a>
                                </div>
                                <div class="col-md-6">
                                    <a href="<?php echo $current_url; ?>&cache=video" class="list-group-item list-group-item-action">
                                        <div class="cache-icon video">
                                            <i class="bi bi-play-circle"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h5 class="mb-1"><?php echo $lang['index']['cache']['cache_video']; ?></h5>
                                            <small class="text-muted">Clear video cache files</small>
                                        </div>
                                        <i class="bi bi-chevron-right text-muted"></i>
                                    </a>
                                </div>
                                <div class="col-md-6">
                                    <a href="<?php echo $current_url; ?>&cache=css" class="list-group-item list-group-item-action">
                                        <div class="cache-icon css">
                                            <i class="bi bi-filetype-css"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h5 class="mb-1"><?php echo $lang['index']['cache']['cache_css']; ?></h5>
                                            <small class="text-muted">Clear CSS cache files</small>
                                        </div>
                                        <i class="bi bi-chevron-right text-muted"></i>
                                    </a>
                                </div>
                                <div class="col-md-6">
                                    <a href="<?php echo $current_url; ?>&cache=sql" class="list-group-item list-group-item-action">
                                        <div class="cache-icon sql">
                                            <i class="bi bi-database"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h5 class="mb-1"><?php echo $lang['index']['cache']['cache_sql']; ?></h5>
                                            <small class="text-muted">Clear SQL cache files</small>
                                        </div>
                                        <i class="bi bi-chevron-right text-muted"></i>
                                    </a>
                                </div>
                                <div class="col-md-6">
                                    <a href="<?php echo $current_url; ?>&cache=templates" class="list-group-item list-group-item-action">
                                        <div class="cache-icon template">
                                            <i class="bi bi-file-earmark-code"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h5 class="mb-1"><?php echo $lang['index']['cache']['cache_templates']; ?></h5>
                                            <small class="text-muted">Clear template cache files</small>
                                        </div>
                                        <i class="bi bi-chevron-right text-muted"></i>
                                    </a>
                                </div>
                                <div class="col-12">
                                    <a href="<?php echo $current_url; ?>&cache=all" class="list-group-item list-group-item-action primary-action">
                                        <div class="cache-icon all">
                                            <i class="bi bi-lightning-charge"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h5 class="mb-1"><?php echo $lang['index']['cache']['cache_all']; ?></h5>
                                            <small>Clear all cache files at once</small>
                                        </div>
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </div>
                            </div>
                            <?php
                        }

                        if ($c != '') {
                            render_cleanup_summary($cleanupResults);
                        }

                        if ($c != '') {
                            ?>
                            <a href="<?php echo $current_url; ?>" class="back-link">
                                <i class="bi bi-arrow-left me-2"></i>
                                <?php echo $lang['index']['cache']['back_to_management']; ?>
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
