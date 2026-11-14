<?php
/**
 * Image Processing Module
 *
 * Handles image upload, resizing, format conversion (WebP), and cleanup.
 *
 * @package AF\Bulletin
 * @module  mod_image
 */

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;

class image_mod extends indexMain
{
    public $url;
    public $name;
    public $vid;
    public $error = '';
    public $outputfile;
    public $temp_files = [];
    public $mod;
    public $custom_name = null;
    public $valid_ext = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'ico'];

    public function index()
    {
        $option = [
            '/' => [
                'width' => null,
                'height' => null,
            ],
            '/thumb' => [
                'width' => 150,
                'height' => null,
                'option' => 'width',
            ],
            '/thumb2' => [
                'width' => 250,
                'height' => null,
                'option' => 'width',
            ],
        ];
        print_r($option);
    }

    public function _resize($option)
    {
        $this->temp_files = [];
        $driver = extension_loaded('gd') ? new GdDriver() : new ImagickDriver();
        $manager = new ImageManager($driver);

        if (!isset($_FILES[$this->name]) || !is_array($_FILES[$this->name])) {
            $this->error = 'No file uploaded';
            return false;
        }

        $file = $_FILES[$this->name];
        $ext = strtolower(Helper::extension($file['name']));

        // Force WebP for supported raster formats
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            $target_ext = 'webp';
        } else {
            $target_ext = $ext;
        }

        $url = $this->_generate_url($target_ext);

        if (!in_array($ext, $this->valid_ext, true)) {
            throw new Exception("invalid_extension|" . $ext);
        }

        foreach ($option as $k => $v) {
            $dir = $k === '/' ? root_dir . 'static' . DS . $this->mod . DS : root_dir . 'static' . DS . $this->mod . $k . DS;
            if (!$this->_fix_dir($dir)) {
                $this->_rollback();
                return false;
            }

            if ($ext === 'svg' || $ext === 'ico') {
                $sourceContent = @file_get_contents($file['tmp_name']);
                if ($sourceContent === false || @file_put_contents($dir . $url, $sourceContent) === false) {
                    $this->error = "Can't write image to path. Directory is not writable.";
                    $this->_rollback();
                    return false;
                }
                $this->temp_files[] = $dir . $url;
                continue;
            }

            try {
                $image = $manager->read($file['tmp_name']);
                $quality = isset($v['quality']) ? (int) $v['quality'] : 80;
                if ($quality <= 0 || $quality > 100) {
                    $quality = 80;
                }

                if (isset($v['option'])) {
                    $targetWidth = isset($v['width']) && $v['width'] !== null ? (int) $v['width'] : null;
                    $targetHeight = isset($v['height']) && $v['height'] !== null ? (int) $v['height'] : null;
                    $optionType = (string) $v['option'];

                    if ($optionType === 'cover' || $optionType === 'crop') {
                        if ($targetWidth && $targetHeight) {
                            $image->cover($targetWidth, $targetHeight);
                        } elseif ($targetWidth) {
                            $image->scale(width: $targetWidth);
                        } elseif ($targetHeight) {
                            $image->scale(height: $targetHeight);
                        }
                    } elseif ($optionType === 'coverDown') {
                        if ($targetWidth && $targetHeight) {
                            $image->coverDown($targetWidth, $targetHeight);
                        } elseif ($targetWidth) {
                            $image->scaleDown(width: $targetWidth);
                        } elseif ($targetHeight) {
                            $image->scaleDown(height: $targetHeight);
                        }
                    } elseif ($optionType === 'width') {
                        if ($targetWidth && $image->width() > $targetWidth) {
                            $calcHeight = (int) round($image->height() * ($targetWidth / $image->width()));
                            $image->resize($targetWidth, $calcHeight);
                        }
                    } elseif ($optionType === 'height') {
                        if ($targetHeight && $image->height() > $targetHeight) {
                            $calcWidth = (int) round($image->width() * ($targetHeight / $image->height()));
                            $image->resize($calcWidth, $targetHeight);
                        }
                    } elseif ($optionType === 'original') {
                        // Retain original dimensions
                    } else {
                        if ($targetWidth && $targetHeight) {
                            $image->resize($targetWidth, $targetHeight);
                        }
                    }

                    // Create a transparent canvas with target dimensions for alpha transparency
                    $canvas = $manager->create($image->width(), $image->height());
                    $canvas->place($image);
                    $image = $canvas;
                } else {
                    if ($v['width'] !== null || $v['height'] !== null) {
                        $targetWidth = $v['width'];
                        $targetHeight = $v['height'];

                        if ($targetWidth === null) {
                            $targetWidth = (int) round($image->width() * ($targetHeight / $image->height()));
                        }
                        if ($targetHeight === null) {
                            $targetHeight = (int) round($image->height() * ($targetWidth / $image->width()));
                        }

                        if ($image->width() > $targetWidth || $image->height() > $targetHeight) {
                            $image->resize($targetWidth, $targetHeight);

                            $canvas = $manager->create($image->width(), $image->height());
                            $canvas->place($image);
                            $image = $canvas;
                        }
                    }
                }

                // Preserve format and transparency
                $success = false;
                if ($target_ext === 'webp') {
                    $success = $image->toWebp($quality)->save($dir . $url);
                } elseif ($ext === 'png') {
                    $success = $image->toPng()->save($dir . $url);
                } elseif ($ext === 'gif') {
                    $success = $image->toGif()->save($dir . $url);
                } else {
                    $success = $image->save($dir . $url);
                }

                if ($success === false) {
                    @unlink($dir . $url);
                    $this->error = 'image_save_failed';
                    $this->_rollback();
                    return false;
                }

                $this->temp_files[] = $dir . $url;
            } catch (\Throwable $e) {
                $this->error = $e->getMessage();
                $this->_rollback();
                return false;
            }
        }

        if (class_exists('ImageService') && in_array($this->mod, ImageService::OG_ENABLED_MODULES, true)) {
            ImageService::ensureOpenGraphJpeg((string) $this->mod, (string) $url);
        }

        $this->url = $url;
        return true;
    }

    public function _rollback()
    {
        if (is_array($this->temp_files) && count($this->temp_files) > 0) {
            foreach ($this->temp_files as $file) {
                if (file_exists($file)) {
                    @unlink($file);
                }
            }
            $this->temp_files = [];
        }
    }

    public function _generate_url($ext)
    {
        $final_ext = strtolower($ext);
        if (!in_array($final_ext, ['jpg', 'jpeg', 'png', 'gif', 'svg', 'ico', 'webp'], true)) {
            $final_ext = 'jpg';
        }
        if ($this->custom_name) {
            return $this->custom_name . "." . $final_ext;
        }
        $url = Helper::randomkey(15) . "." . $final_ext;
        $file = root_dir . 'static' . DS . $this->mod . DS . $url;
        if (file_exists($file)) {
            return $this->_generate_url($ext);
        }
        return $url;
    }

    public function _delete_files($option)
    {
        $directories = array_keys($option);
        $count = count($directories) - 1;
        for ($i = 0; $i <= $count; $i++) {
            $dirkey = $directories[$i];
            if ($dirkey === '/') {
                $dir = root_dir . 'static' . DS . $this->mod . $dirkey;
            } else {
                $dir = root_dir . 'static' . DS . $this->mod . $dirkey . DS;
            }
            $this->outputfile = $dir . $this->url;
            if (file_exists($this->outputfile)) {
                if (@unlink($this->outputfile)) {
                    continue;
                }
                $this->error = 'File could not be deleted: ' . $this->outputfile;
                return false;
            }
        }
        if (class_exists('ImageService')) {
            ImageService::deleteOpenGraphJpeg((string) $this->mod, (string) $this->url);
        }
        return true;
    }

    private function _fix_dir($d)
    {
        if (!file_exists($d)) {
            $file = Load::lib('File');
            try {
                $file->newdir($d);
            } catch (FileException $e) {
                $this->error = "Can't write image to path. Directory does not exist.";
                return false;
            }
        }

        if (!is_writable($d)) {
            @chmod($d, 0777);
        }

        if (!is_writable($d)) {
            $this->error = "Can't write image to path. Directory is not writable.";
            return false;
        }

        return true;
    }
}
