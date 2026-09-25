<?php

class BrandAssetGenerator
{
    private $output_dir;

    private $generated_files = [
        'favicon_path' => 'favicon.ico',
        'favicon_96' => 'favicon-96x96.png',
        'apple_touch_icon' => 'apple-touch-icon.png',
        'site_webmanifest' => 'site.webmanifest',
        'manifest_192' => 'web-app-manifest-192x192.png',
        'manifest_512' => 'web-app-manifest-512x512.png',
    ];

    public function __construct()
    {
        $this->output_dir = root_dir . 'static' . DS . 'mod_settings' . DS;
    }

    public function generateFromUpload($input_name, array $site_meta = [], array $existing_files = [])
    {
        if (!extension_loaded('imagick')) {
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
        if (!extension_loaded('imagick')) {
            throw new Exception('requires_imagick');
        }

        if (!file_exists($filePath)) {
            throw new Exception('missing_upload');
        }

        return $this->_generate($filePath, $site_meta, $existing_files);
    }

    private function _generate($sourcePath, array $site_meta = [], array $existing_files = [])
    {
        $source = new Imagick($sourcePath);
        $source->setImageBackgroundColor(new ImagickPixel('transparent'));
        $source = $source->mergeImageLayers(Imagick::LAYERMETHOD_MERGE);

        $width = $source->getImageWidth();
        $height = $source->getImageHeight();

        if ($width < 512 || $height < 512) {
            throw new Exception('master_logo_too_small');
        }

        $this->ensureOutputDirectory();
        $this->deleteManagedFiles($existing_files);

        $this->writeSquarePng($source, 96, $this->generated_files['favicon_96']);
        $this->writeSquarePng($source, 180, $this->generated_files['apple_touch_icon']);
        $this->writeSquarePng($source, 192, $this->generated_files['manifest_192']);
        $this->writeSquarePng($source, 512, $this->generated_files['manifest_512']);
        $this->writeFaviconIco($source);
        $this->writeManifest($site_meta);

        $source->clear();
        $source->destroy();

        return $this->generated_files;
    }

    public function deleteManagedFiles(array $extra_files = [])
    {
        $known_files = array_values($this->generated_files);
        $files_to_delete = array_unique(array_merge($known_files, $extra_files));

        foreach ($files_to_delete as $file_name) {
            if (empty($file_name)) {
                continue;
            }

            $path = $this->output_dir . $file_name;
            if (file_exists($path)) {
                @unlink($path);
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

    private function writeSquarePng(Imagick $source, $size, $file_name)
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
        $canvas->writeImage($this->output_dir . $file_name);

        $icon->clear();
        $icon->destroy();
        $canvas->clear();
        $canvas->destroy();
    }

    private function writeFaviconIco(Imagick $source)
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
