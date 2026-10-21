<?php
ob_start();

// Initialize framework for language support
require_once('vendor/autoload.php');
require_once('init.php');

$langData = Load::lang('index');
$tLang = isset($langData['index']['template']) ? $langData['index']['template'] : array();

$valid_templates = array('default','artist','soul');

$message = "";
if(isset($_GET['template']) && in_array($_GET['template'], $valid_templates)){
    setcookie("tema", $_GET['template'], time() + 3600);  /* expire in 1 hour */
    $active_theme = htmlspecialchars($_GET['template']);
    $msg_format = isset($tLang['activated']) ? $tLang['activated'] : 'Theme %s activated! Redirecting...';
    $message = str_replace('%s', "<b>{$active_theme}</b>", $msg_format);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Amed Framework - <?php echo isset($tLang['title']) ? $tLang['title'] : 'Theme Gallery'; ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="templates/base_static/css/template-gallery.css">
</head>
<body>
    <div class="container py-4">
        
        <div class="d-flex justify-content-between align-items-center mb-5 pb-3 border-bottom">
            <div>
                <h2 class="mb-0 text-primary fw-bold">Amed Framework</h2>
                <p class="text-secondary mb-0"><?php echo isset($tLang['desc']) ? $tLang['desc'] : 'Theme Setup and Control'; ?></p>
            </div>
            <a href="/" class="btn btn-outline-dark fw-semibold px-4 shadow-sm">
                <i class="bi bi-house-door me-2"></i> <?php echo isset($tLang['go_home']) ? $tLang['go_home'] : 'Go to Homepage'; ?>
            </a>
        </div>
        
        <?php if($message): ?>
            <div class="alert alert-success fw-semibold shadow-sm mb-4 d-flex align-items-center">
                <i class="bi bi-check-circle fs-4 me-3"></i> 
                <div><?php echo $message; ?></div>
            </div>
            <script src="templates/base_static/js/template-gallery.js" defer></script>
        <?php endif; ?>

        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php foreach($valid_templates as $tema): 
                $active_cookie_tema = isset($_COOKIE['tema']) ? $_COOKIE['tema'] : 'none';
                $isActive = ($active_cookie_tema == $tema) || ($active_cookie_tema == 'none' && $tema == 'default');
            ?>
                <div class="col">
                    <div class="card theme-card <?php echo $isActive ? 'border border-primary border-2 ring-2 ring-primary' : ''; ?>">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title theme-title mb-0 fs-4">
                                    <i class="bi bi-palette text-muted me-2"></i> <?php echo $tema; ?>
                                </h5>
                                <?php if($isActive): ?>
                                    <span class="badge bg-primary rounded-pill px-3 py-2 fw-normal">
                                        <i class="bi bi-check-circle-fill me-1"></i> <?php echo isset($tLang['active']) ? $tLang['active'] : 'Active'; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <p class="card-text text-muted mb-4">
                                <?php 
                                $desc_format = isset($tLang['theme_desc']) ? $tLang['theme_desc'] : 'Interface for %s.';
                                echo str_replace('%s', "<strong>{$tema}</strong>", $desc_format); 
                                ?>
                            </p>
                            
                            <?php if(!$isActive): ?>
                                <a href="template.php?template=<?php echo $tema; ?>" class="btn btn-outline-primary w-100 fw-semibold py-2">
                                    <?php echo isset($tLang['activate_btn']) ? $tLang['activate_btn'] : 'Activate This Theme'; ?>
                                </a>
                            <?php else: ?>
                                <button class="btn btn-primary w-100 fw-semibold py-2" disabled>
                                    <?php echo isset($tLang['in_use']) ? $tLang['in_use'] : 'Currently In Use'; ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
    </div>
</body>
</html>