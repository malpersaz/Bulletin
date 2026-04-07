<?php
define('DS', DIRECTORY_SEPARATOR);
define('root_dir', __DIR__ . DS);

if (!extension_loaded('imagick')) {
    die("Imagick extension is not loaded.");
}

$bg_path = root_dir . 'static' . DS . 'img' . DS . 'library_bg.jpg';
$author_path = root_dir . 'r.jpg';
$output_path = root_dir . 'static' . DS . 'img' . DS . 'hero_banner_remzi.jpg';

try {
    // 1. Load background
    $bg = new Imagick($bg_path);
    // 1200x500 is a better aspect ratio for the current slider as seen in the screenshot
    $bg->thumbnailImage(1200, 500, true);
    
    // Create a stronger gradient overlay on the left side for text readability
    // (Since the website adds its own text on the left)
    $gradient = new Imagick();
    $gradient->newPseudoImage(1200, 500, "gradient:rgba(0,0,0,0.8)-transparent");
    // Rotate gradient to go from left to right
    $gradient->rotateImage(new ImagickPixel('none'), -90);
    $bg->compositeImage($gradient, Imagick::COMPOSITE_OVER, 0, 0);

    // 2. Load and prepare author image
    $author = new Imagick($author_path);
    $author->thumbnailImage(350, 350, true);
    
    // Create circular mask for author image
    $mask = new Imagick();
    $mask->newImage(350, 350, new ImagickPixel('transparent'));
    $draw = new ImagickDraw();
    $draw->setFillColor(new ImagickPixel('white'));
    $draw->circle(175, 175, 175, 350);
    $mask->drawImage($draw);
    $author->compositeImage($mask, Imagick::COMPOSITE_DSTIN, 0, 0);
    
    // Add a subtle white glow/border
    $border = new ImagickDraw();
    $border->setStrokeColor(new ImagickPixel('rgba(255,255,255,0.4)'));
    $border->setStrokeWidth(4);
    $border->setFillColor(new ImagickPixel('transparent'));
    $border->circle(175, 175, 175, 345);
    $author->drawImage($border);

    // 3. Composite author image on the right
    // Position it hinekî bi kûrahî (centered vertically, aligned right)
    $bg->compositeImage($author, Imagick::COMPOSITE_OVER, 800, 75);

    // 4. Save final image (NO TEXT this time)
    $bg->setImageFormat('jpg');
    $bg->writeImage($output_path);

    echo "Successfully created banner (v2, no text): " . $output_path . "\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
