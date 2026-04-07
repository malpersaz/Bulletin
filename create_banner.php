<?php
define('DS', DIRECTORY_SEPARATOR);
define('root_dir', __DIR__ . DS);

if (!extension_loaded('imagick')) {
    die("Imagick extension is not loaded.");
}

$bg_path = root_dir . 'static' . DS . 'img' . DS . 'library_bg.jpg';
$author_path = root_dir . 'r.jpg';
$font_path = '/usr/share/fonts/truetype/noto/NotoSerif-Regular.ttf';
$output_path = root_dir . 'static' . DS . 'img' . DS . 'hero_banner_remzi.jpg';

try {
    // 1. Load background
    $bg = new Imagick($bg_path);
    $bg->thumbnailImage(1920, 640, true);
    
    // Create a dark overlay for better text readability
    $overlay = new Imagick();
    $overlay->newImage(1920, 640, new ImagickPixel('rgba(0,0,0,0.4)'));
    $bg->compositeImage($overlay, Imagick::COMPOSITE_OVER, 0, 0);

    // 2. Load and prepare author image
    $author = new Imagick($author_path);
    $author->thumbnailImage(400, 400, true);
    
    // Create circular mask for author image
    $mask = new Imagick();
    $mask->newImage(400, 400, new ImagickPixel('transparent'));
    $draw = new ImagickDraw();
    $draw->setFillColor(new ImagickPixel('white'));
    $draw->circle(200, 200, 200, 400);
    $mask->drawImage($draw);
    $author->compositeImage($mask, Imagick::COMPOSITE_DSTIN, 0, 0);
    
    // Add a golden border to the author circle
    $border = new ImagickDraw();
    $border->setStrokeColor(new ImagickPixel('#FFD700'));
    $border->setStrokeWidth(5);
    $border->setFillColor(new ImagickPixel('transparent'));
    $border->circle(200, 200, 200, 395);
    $author->drawImage($border);

    // 3. Composite author image on the right
    $bg->compositeImage($author, Imagick::COMPOSITE_OVER, 1350, 120);

    // 4. Add Text 1
    $drawText1 = new ImagickDraw();
    $drawText1->setFont($font_path);
    $drawText1->setFontSize(48);
    $drawText1->setFillColor(new ImagickPixel('#FFD700')); // Gold color
    $drawText1->setTextAntialias(true);
    
    $text1 = "Remzî Zengîn êdî bi Kurmancî, Zazakî, Soranî,\nÎngilîzî, Swêdî, Almanî û Fensî\ndê li Amîda Kurd be.";
    $bg->annotateImage($drawText1, 100, 250, 0, $text1);

    // 5. Add Text 2
    $drawText2 = new ImagickDraw();
    $drawText2->setFont($font_path);
    $drawText2->setFontSize(32);
    $drawText2->setFillColor(new ImagickPixel('#FFFFFF')); // White color
    $drawText2->setTextAntialias(true);
    
    $text2 = "Amîda Kurd di 21 saliya xwe de ji nû ve hiltê.";
    $bg->annotateImage($drawText2, 100, 500, 0, $text2);

    // 6. Save final image
    $bg->setImageFormat('jpg');
    $bg->writeImage($output_path);

    echo "Successfully created banner: " . $output_path . "\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
