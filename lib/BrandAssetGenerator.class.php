<?php

class BrandAssetGenerator
{
    private $output_dir;
    private $thumb_dir;

    private $generated_files = [
        'favicon_path' => 'favicon.ico',
        'favicon_96' => 'favicon-96x96.png',
        'apple_touch_icon' => 'apple-touch-icon.png',
        'site_webmanifest' => 'site.webmanifest',
        'manifest_192' => 'web-app-manifest-192x192.png',
        'manifest_512' => 'web-app-manifest-512x512.png',
    ];

    private $thumb_files = [
        'logo_symbol' => 'logo_symbol.png',
        'favicon_96' => 'favicon-96x96.png',
        'apple_touch_icon' => 'apple-touch-icon.png',
    ];

    public function __construct()
    {
        $this->output_dir = root_dir . 'static' . DS . 'mod_settings' . DS;
        $this->thumb_dir = root_dir . 'static' . DS . 'mod_settings' . DS . 'thumb' . DS;
    }

    public function generateFromUpload($input_name, array $site_meta = [], array $existing_files = [])
    {
        if (!extension_loaded('imagick') && !extension_loaded('gd')) {
            throw new Exception('requires_imagick');
        }

        if (!isset($_FILES[$input_name]['tmp_name']) || empty($_FILES[$input_name]['tmp_name'])) {
            throw new Exception('missing_upload');
        }

        $file = $_FILES[$input_name];
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($extension !== 'png') {
            throw new Exception('invalid_master_extension');
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            throw new Exception('invalid_uploaded_file');
        }

        return $this->_generate($file['tmp_name'], $site_meta, $existing_files);
    }

    public function generateFromLocalFile($filePath, array $site_meta = [], array $existing_files = [])
    {
        if (!extension_loaded('imagick') && !extension_loaded('gd')) {
            throw new Exception('requires_imagick');
        }

        if (!file_exists($filePath)) {
            throw new Exception('missing_upload');
        }

        return $this->_generate($filePath, $site_meta, $existing_files);
    }

    private function _generate($sourcePath, array $site_meta = [], array $existing_files = [])
    {
        $this->ensureOutputDirectory();
        $this->ensureThumbDirectory();
        $this->deleteManagedFiles($existing_files);

        if (extension_loaded('imagick')) {
            try {
                $source = new Imagick($sourcePath);
                $source->setImageBackgroundColor(new ImagickPixel('transparent'));
                $source = $source->mergeImageLayers(Imagick::LAYERMETHOD_MERGE);

                $width = $source->getImageWidth();
                $height = $source->getImageHeight();

                if ($width < 64 || $height < 64) {
                    throw new Exception('master_logo_too_small');
                }

                // Write master manifest and favicon assets
                $this->writeSquarePngImagick($source, 96, $this->output_dir . $this->generated_files['favicon_96']);
                $this->writeSquarePngImagick($source, 180, $this->output_dir . $this->generated_files['apple_touch_icon']);
                $this->writeSquarePngImagick($source, 192, $this->output_dir . $this->generated_files['manifest_192']);
                $this->writeSquarePngImagick($source, 512, $this->output_dir . $this->generated_files['manifest_512']);
                $this->writeFaviconIconImagick($source);
                $this->writeManifest($site_meta);

                // Write thumb synchronized assets for favicon/symbol only
                $this->writeSquarePngImagick($source, 96, $this->thumb_dir . $this->thumb_files['favicon_96']);
                $this->writeSquarePngImagick($source, 180, $this->thumb_dir . $this->thumb_files['apple_touch_icon']);
                $this->writeSquarePngImagick($source, 64, $this->thumb_dir . $this->thumb_files['logo_symbol']);

                $source->clear();
                $source->destroy();

                return $this->generated_files;
            } catch (Exception $e) {
                if ($e->getMessage() === 'master_logo_too_small') {
                    throw $e;
                }
                // Fall back to GD if Imagick throws an internal error
            }
        }

        // GD Fallback
        $this->generateSquarePngGd($sourcePath, $this->output_dir . $this->generated_files['favicon_96'], 96);
        $this->generateSquarePngGd($sourcePath, $this->output_dir . $this->generated_files['apple_touch_icon'], 180);
        $this->generateSquarePngGd($sourcePath, $this->output_dir . $this->generated_files['manifest_192'], 192);
        $this->generateSquarePngGd($sourcePath, $this->output_dir . $this->generated_files['manifest_512'], 512);
        $this->generateFaviconIconGd($sourcePath, $this->output_dir . $this->generated_files['favicon_path']);
        $this->writeManifest($site_meta);

        $this->generateSquarePngGd($sourcePath, $this->thumb_dir . $this->thumb_files['favicon_96'], 96);
        $this->generateSquarePngGd($sourcePath, $this->thumb_dir . $this->thumb_files['apple_touch_icon'], 180);
        $this->generateSquarePngGd($sourcePath, $this->thumb_dir . $this->thumb_files['logo_symbol'], 64);

        return $this->generated_files;
    }

    public function generateMultiSizeThumbnails($squareSourcePath)
    {
        if (!file_exists($squareSourcePath)) {
            return false;
        }

        $this->ensureThumbDirectory();

        $this->generateSquarePng($squareSourcePath, $this->thumb_dir . 'logo_32x32.png', 32);
        $this->generateSquarePng($squareSourcePath, $this->thumb_dir . 'logo_42x42.png', 42);
        $this->generateSquarePng($squareSourcePath, $this->thumb_dir . 'logo_64x64.png', 64);
        $this->generateSquarePng($squareSourcePath, $this->thumb_dir . 'logo_square.png', 84);

        return true;
    }

    public function generateRectThumbnail($rectSourcePath, $maxWidth = 320, $maxHeight = 107)
    {
        if (!file_exists($rectSourcePath)) {
            return false;
        }

        $this->ensureThumbDirectory();
        return $this->generateRectPng($rectSourcePath, $this->thumb_dir . 'logo.png', $maxWidth, $maxHeight);
    }

    public function generateSquarePng($sourcePath, $targetPath, $size = 84)
    {
        if (extension_loaded('imagick')) {
            try {
                $source = new Imagick($sourcePath);
                $source->setImageBackgroundColor(new ImagickPixel('transparent'));
                $source = $source->mergeImageLayers(Imagick::LAYERMETHOD_MERGE);
                $this->writeSquarePngImagick($source, $size, $targetPath);
                $source->clear();
                $source->destroy();
                return true;
            } catch (Exception $e) {
                // Fall back to GD
            }
        }

        return $this->generateSquarePngGd($sourcePath, $targetPath, $size);
    }

    public function generateRectPng($sourcePath, $targetPath, $targetWidth = 320, $targetHeight = 107)
    {
        if (extension_loaded('imagick')) {
            try {
                $source = new Imagick($sourcePath);
                $source->setImageBackgroundColor(new ImagickPixel('transparent'));
                $source = $source->mergeImageLayers(Imagick::LAYERMETHOD_MERGE);

                $canvas = new Imagick();
                $canvas->newImage($targetWidth, $targetHeight, new ImagickPixel('transparent'));
                $canvas->setImageFormat('png');
                $canvas->setImageAlphaChannel(Imagick::ALPHACHANNEL_SET);

                $icon = clone $source;
                $icon->setImageFormat('png');
                $icon->setImageAlphaChannel(Imagick::ALPHACHANNEL_SET);
                $icon->thumbnailImage($targetWidth, $targetHeight, true, true);

                $x = (int) floor(($targetWidth - $icon->getImageWidth()) / 2);
                $y = (int) floor(($targetHeight - $icon->getImageHeight()) / 2);

                $canvas->compositeImage($icon, Imagick::COMPOSITE_OVER, $x, $y);
                $canvas->setImageCompressionQuality(95);
                $canvas->writeImage($targetPath);

                $icon->clear();
                $icon->destroy();
                $canvas->clear();
                $canvas->destroy();
                $source->clear();
                $source->destroy();
                return true;
            } catch (Exception $e) {
                // Fall back to GD
            }
        }

        return $this->generateRectPngGd($sourcePath, $targetPath, $targetWidth, $targetHeight);
    }

    public function ensureThumbnailsExist()
    {
        $this->ensureThumbDirectory();

        $squareMaster = $this->output_dir . 'logo_square.png';
        if (file_exists($squareMaster)) {
            $this->generateMultiSizeThumbnails($squareMaster);
        } else {
            foreach (['logo_square.png', 'logo_32x32.png', 'logo_42x42.png', 'logo_64x64.png'] as $st) {
                if (file_exists($this->thumb_dir . $st)) {
                    @unlink($this->thumb_dir . $st);
                }
            }
        }

        $rectMaster = $this->output_dir . 'logo.png';
        if (file_exists($rectMaster)) {
            $this->generateRectThumbnail($rectMaster);
        } else {
            if (file_exists($this->thumb_dir . 'logo.png')) {
                @unlink($this->thumb_dir . 'logo.png');
            }
        }

        $symbolMaster = $this->output_dir . 'logo_symbol.png';
        if (file_exists($symbolMaster)) {
            $this->generateSquarePng($symbolMaster, $this->thumb_dir . 'logo_symbol.png', 64);
        } else {
            if (file_exists($this->thumb_dir . 'logo_symbol.png')) {
                @unlink($this->thumb_dir . 'logo_symbol.png');
            }
        }

        $favicon96Master = $this->output_dir . 'favicon-96x96.png';
        if (file_exists($favicon96Master)) {
            if (!file_exists($this->thumb_dir . 'favicon-96x96.png')) {
                $this->generateSquarePng($favicon96Master, $this->thumb_dir . 'favicon-96x96.png', 96);
            }
        } else {
            if (file_exists($this->thumb_dir . 'favicon-96x96.png')) {
                @unlink($this->thumb_dir . 'favicon-96x96.png');
            }
        }

        $appleMaster = $this->output_dir . 'apple-touch-icon.png';
        if (file_exists($appleMaster)) {
            if (!file_exists($this->thumb_dir . 'apple-touch-icon.png')) {
                $this->generateSquarePng($appleMaster, $this->thumb_dir . 'apple-touch-icon.png', 180);
            }
        } else {
            if (file_exists($this->thumb_dir . 'apple-touch-icon.png')) {
                @unlink($this->thumb_dir . 'apple-touch-icon.png');
            }
        }
    }

    public function deleteManagedFiles(array $extra_files = [])
    {
        $known_files = array_values($this->generated_files);
        $thumb_known = [
            'favicon-96x96.png',
            'apple-touch-icon.png',
        ];
        $files_to_delete = array_unique(array_merge($known_files, $thumb_known, $extra_files));

        foreach ($files_to_delete as $file_name) {
            if (empty($file_name)) {
                continue;
            }

            // Strict safety check: never delete master logos (rectangle, square, symbol) or square thumbnails
            if ($file_name === 'logo.png' || $file_name === 'logo_square.png' || $file_name === 'logo_symbol.png' || in_array($file_name, ['logo_32x32.png', 'logo_42x42.png', 'logo_64x64.png'])) {
                continue;
            }

            $path = $this->output_dir . $file_name;
            if (file_exists($path)) {
                @unlink($path);
            }

            $thumb_path = $this->thumb_dir . $file_name;
            if (file_exists($thumb_path)) {
                @unlink($thumb_path);
            }
        }
    }

    private function ensureOutputDirectory()
    {
        if (!is_dir($this->output_dir)) {
            mkdir($this->output_dir, 0755, true);
        }

        if (!is_writable($this->output_dir)) {
            throw new Exception('output_dir_not_writable');
        }
    }

    private function ensureThumbDirectory()
    {
        if (!is_dir($this->thumb_dir)) {
            mkdir($this->thumb_dir, 0755, true);
        }

        if (!is_writable($this->thumb_dir)) {
            throw new Exception('output_dir_not_writable');
        }
    }

    private function writeSquarePngImagick(Imagick $source, $size, $target_file)
    {
        $canvas = new Imagick();
        $canvas->newImage($size, $size, new ImagickPixel('transparent'));
        $canvas->setImageFormat('png');
        $canvas->setImageAlphaChannel(Imagick::ALPHACHANNEL_SET);

        $icon = clone $source;
        $icon->setImageFormat('png');
        $icon->setImageAlphaChannel(Imagick::ALPHACHANNEL_SET);
        $icon->thumbnailImage($size, $size, true, true);

        $x = (int) floor(($size - $icon->getImageWidth()) / 2);
        $y = (int) floor(($size - $icon->getImageHeight()) / 2);

        $canvas->compositeImage($icon, Imagick::COMPOSITE_OVER, $x, $y);
        $canvas->setImageCompressionQuality(95);
        $canvas->writeImage($target_file);

        $icon->clear();
        $icon->destroy();
        $canvas->clear();
        $canvas->destroy();
    }

    private function writeFaviconIconImagick(Imagick $source)
    {
        $icon = new Imagick();
        $icon->newImage(32, 32, new ImagickPixel('transparent'));
        $icon->setImageFormat('png');
        $icon->setImageAlphaChannel(Imagick::ALPHACHANNEL_SET);

        $resized = clone $source;
        $resized->setImageFormat('png');
        $resized->setImageAlphaChannel(Imagick::ALPHACHANNEL_SET);
        $resized->thumbnailImage(32, 32, true, true);

        $x = (int) floor((32 - $resized->getImageWidth()) / 2);
        $y = (int) floor((32 - $resized->getImageHeight()) / 2);

        $icon->compositeImage($resized, Imagick::COMPOSITE_OVER, $x, $y);
        $icon->setImageFormat('ico');
        $icon->writeImage($this->output_dir . $this->generated_files['favicon_path']);

        $resized->clear();
        $resized->destroy();
        $icon->clear();
        $icon->destroy();
    }

    private function generateSquarePngGd($sourcePath, $targetPath, $size)
    {
        $src = @imagecreatefromstring(file_get_contents($sourcePath));
        if (!$src) {
            return false;
        }

        $originalWidth = imagesx($src);
        $originalHeight = imagesy($src);

        $dst = imagecreatetruecolor($size, $size);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
        imagefilledrectangle($dst, 0, 0, $size, $size, $transparent);

        $srcRatio = $originalWidth / $originalHeight;
        if ($srcRatio > 1) {
            $newW = $size;
            $newH = (int) round($size / $srcRatio);
        } else {
            $newH = $size;
            $newW = (int) round($size * $srcRatio);
        }
        $dstX = (int) floor(($size - $newW) / 2);
        $dstY = (int) floor(($size - $newH) / 2);

        imagecopyresampled($dst, $src, $dstX, $dstY, 0, 0, $newW, $newH, $originalWidth, $originalHeight);
        imagepng($dst, $targetPath, 9);

        unset($dst, $src);
        return true;
    }

    private function generateRectPngGd($sourcePath, $targetPath, $targetWidth, $targetHeight)
    {
        $src = @imagecreatefromstring(file_get_contents($sourcePath));
        if (!$src) {
            return false;
        }

        $originalWidth = imagesx($src);
        $originalHeight = imagesy($src);

        $dst = imagecreatetruecolor($targetWidth, $targetHeight);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
        imagefilledrectangle($dst, 0, 0, $targetWidth, $targetHeight, $transparent);

        $srcRatio = $originalWidth / $originalHeight;
        $dstRatio = $targetWidth / $targetHeight;

        if ($srcRatio > $dstRatio) {
            $newW = $targetWidth;
            $newH = (int) round($targetWidth / $srcRatio);
        } else {
            $newH = $targetHeight;
            $newW = (int) round($targetHeight * $srcRatio);
        }
        $dstX = (int) floor(($targetWidth - $newW) / 2);
        $dstY = (int) floor(($targetHeight - $newH) / 2);

        imagecopyresampled($dst, $src, $dstX, $dstY, 0, 0, $newW, $newH, $originalWidth, $originalHeight);
        imagepng($dst, $targetPath, 9);

        unset($dst, $src);
        return true;
    }

    private function generateFaviconIconGd($sourcePath, $targetPath)
    {
        // For basic icon via GD, generate 32x32 PNG structure or copy
        $this->generateSquarePngGd($sourcePath, $targetPath, 32);
    }

    public function ensureManifestExists(array $site_meta = [])
    {
        $manifestFile = $this->output_dir . $this->generated_files['site_webmanifest'];
        if (!file_exists($manifestFile) || filesize($manifestFile) < 5) {
            $this->ensureOutputDirectory();
            $this->writeManifest($site_meta);
        }
    }

    public function writeManifest(array $site_meta = [])
    {
        $name = isset($site_meta['title']) && trim($site_meta['title']) !== ''
            ? trim($site_meta['title'])
            : (isset($site_meta['name']) && trim($site_meta['name']) !== ''
                ? trim($site_meta['name'])
                : (isset($site_meta['site_name']) && trim($site_meta['site_name']) !== ''
                    ? trim($site_meta['site_name'])
                    : 'Website'));

        $short_name = mb_strlen($name) > 24 ? mb_substr($name, 0, 24) : $name;

        $manifest = [
            'name' => $name,
            'short_name' => $short_name,
            'icons' => [
                [
                    'src' => 'web-app-manifest-192x192.png',
                    'sizes' => '192x192',
                    'type' => 'image/png',
                ],
                [
                    'src' => 'web-app-manifest-512x512.png',
                    'sizes' => '512x512',
                    'type' => 'image/png',
                ],
            ],
            'theme_color' => '#ffffff',
            'background_color' => '#ffffff',
            'display' => 'standalone',
        ];

        $this->ensureOutputDirectory();
        file_put_contents(
            $this->output_dir . $this->generated_files['site_webmanifest'],
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }
}
