<?php
/**
 * ImageService - Centralized image handling for all modules
 * Eliminates duplicate image upload/resize/delete code
 * 
 * Amed Framework
 * @author Refactored for better maintainability
 */

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;

class ImageService
{
    public const OG_IMAGE_WIDTH = 1200;
    public const OG_IMAGE_HEIGHT = 630;
    public const OG_MAX_BYTES = 280000;
    public const OG_ENABLED_MODULES = [
        'mod_news',
        'mod_publication',
        'mod_event',
        'mod_announcements',
        'mod_page',
        'mod_video_embed',
        'mod_video',
    ];

    private $last_error = '';
    private $uploaded_url = '';
    
    /**
     * Upload and resize image with given options
     * 
     * @param string $module_name The module name (e.g., 'mod_announcements')
     * @param string $field_name The form field name (usually 'image')
     * @param array $resize_options Array of resize options
     * @return bool Success status
     */
    public function uploadImage($module_name, $field_name = 'image', $resize_options = [])
    {
        try {
            // Check if file was uploaded
            if (!isset($_FILES[$field_name]['name']) || $_FILES[$field_name]['name'] == '') {
                $this->last_error = 'No file uploaded';
                return false;
            }
            
            // Load the image module
            $image = Load::mod('image');
            $image->mod = $module_name;
            $image->name = $field_name;
            
            // Set default resize options if none provided
            if (empty($resize_options)) {
                $resize_options = $this->getDefaultResizeOptions();
            }
            
            // Perform resize operation
            if ($image->_resize($resize_options)) {
                $this->uploaded_url = $image->url;
                $this->last_error = '';
                return true;
            }
            $this->last_error = $image->error;
            return false;
            
        } catch (Exception $e) {
            $this->last_error = 'Image upload failed: ' . $e->getMessage();
            return false;
        }
    }
    
    /**
     * Delete image with given options
     * 
     * @param string $module_name The module name (e.g., 'mod_announcements')
     * @param string $image_url The image URL to delete
     * @param array $resize_options Array of resize options (for determining which files to delete)
     * @return bool Success status
     */
    public function deleteImage($module_name, $image_url, $resize_options = [])
    {
        try {
            if (empty($image_url)) {
                return true; // Nothing to delete
            }
            
            // Load the image module
            $image = Load::mod('image');
            $image->mod = $module_name;
            $image->url = $image_url;
            
            // Set default resize options if none provided
            if (empty($resize_options)) {
                $resize_options = $this->getDefaultResizeOptions();
            }
            
            // Perform delete operation
            if ($image->_delete_files($resize_options)) {
                $this->last_error = '';
                return true;
            }
            $this->last_error = $image->error;
            return false;
            
        } catch (Exception $e) {
            $this->last_error = 'Image deletion failed: ' . $e->getMessage();
            return false;
        }
    }
    
    /**
     * Get the URL of the last uploaded image
     * 
     * @return string The uploaded image URL
     */
    public function getUploadedUrl()
    {
        return $this->uploaded_url;
    }
    
    /**
     * Get the last error message
     * 
     * @return string The last error message
     */
    public function getLastError()
    {
        return $this->last_error;
    }
    
    /**
     * Get default resize options
     * 
     * @return array Default resize options
     */
    private function getDefaultResizeOptions()
    {
        return [
            '/' => [
                'width' => 200,
                'height' => null,
                'option' => 'width',
            ],
            '/thumb' => [
                'width' => 100,
                'height' => null,
                'option' => 'width',
            ],
        ];
    }
    
    /**
     * Create resize options for common image sizes
     * 
     * @param int $main_width Main image width
     * @param int $thumb_width Thumbnail width
     * @param int|null $main_height Main image height (null for auto)
     * @param int|null $thumb_height Thumbnail height (null for auto)
     * @return array Resize options array
     */
    public static function createResizeOptions($main_width = 200, $thumb_width = 100, $main_height = null, $thumb_height = null)
    {
        return [
            '/' => [
                'width' => $main_width,
                'height' => $main_height,
                'option' => $main_height ? 'resize' : 'width',
            ],
            '/thumb' => [
                'width' => $thumb_width,
                'height' => $thumb_height,
                'option' => $thumb_height ? 'resize' : 'width',
            ],
        ];
    }
    
    /**
     * Create resize options for galleries (typically larger images)
     * 
     * @return array Resize options for gallery images
     */
    public static function createGalleryResizeOptions()
    {
        return [
            '/' => [
                'width' => 800,
                'height' => null,
                'option' => 'width',
            ],
            '/thumb' => [
                'width' => 200,
                'height' => null,
                'option' => 'width',
            ],
        ];
    }
    
    /**
     * Create resize options for news/content images
     * 
     * @return array Resize options for content images
     */
    public static function createContentResizeOptions()
    {
        return [
            '/' => [
                'width' => 600,
                'height' => null,
                'option' => 'width',
            ],
            '/thumb' => [
                'width' => 200,
                'height' => null,
                'option' => 'width',
            ],
        ];
    }
    
    /**
     * Rollback uploaded image (useful when database save fails)
     * 
     * @param string $module_name The module name
     * @param string $image_url The image URL to rollback
     * @param array $resize_options Resize options used during upload
     * @return bool Success status
     */
    public function rollbackUpload($module_name, $image_url, $resize_options = [])
    {
        return $this->deleteImage($module_name, $image_url, $resize_options);
    }
    
    /**
     * Check if an image file exists for a given module and URL
     * 
     * @param string $module_name The module name
     * @param string $image_url The image URL to check
     * @return bool True if image exists
     */
    public function imageExists($module_name, $image_url)
    {
        if (empty($image_url)) {
            return false;
        }
        
        // This would depend on how the framework stores images
        // For now, just return true if URL is not empty
        return true;
    }
    
    /**
     * Get image path for a given module and URL
     * 
     * @param string $module_name The module name
     * @param string $image_url The image URL
     * @param string $size Size variant ('/' for main, '/thumb' for thumbnail)
     * @return string Full image path
     */
    public function getImagePath($module_name, $image_url, $size = '/')
    {
        if (empty($image_url)) {
            return '';
        }
        
        // Construct image path based on module structure
        $site = Registry::get('site_config');
        return $site['iaddress'] . $module_name . $size . $image_url;
    }

    /**
     * Resolves or generates a 1200x630 companion JPEG for social crawlers (WhatsApp/Meta/X).
     *
     * @param string $moduleName Module directory under static/ (e.g., mod_news)
     * @param string|null $imageField Stored image filename
     * @param string $imageBaseUrl Base static asset URL (site.iaddress)
     * @return array<string, int|string>|null Metadata array or null when unavailable
     */
    public static function ensureOpenGraphJpeg(string $moduleName, ?string $imageField, string $imageBaseUrl = ''): ?array
    {
        $cleanModule = preg_replace('/[^a-z0-9_]/i', '', $moduleName) ?? '';
        $cleanFile = self::extractRasterFilename($imageField);
        if ($cleanModule === '' || $cleanFile === '') {
            return null;
        }

        $baseRoot = defined('root_dir') ? rtrim((string) root_dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR : dirname(__DIR__) . DIRECTORY_SEPARATOR;
        $sourcePath = self::resolveOpenGraphSourcePath($baseRoot, $cleanModule, $cleanFile);
        if ($sourcePath === null) {
            return null;
        }

        $ogDir = $baseRoot . 'static' . DIRECTORY_SEPARATOR . $cleanModule . DIRECTORY_SEPARATOR . 'og' . DIRECTORY_SEPARATOR;
        $baseName = (string) pathinfo($cleanFile, PATHINFO_FILENAME);
        if ($baseName === '') {
            return null;
        }

        $ogFile = $baseName . '.jpg';
        $outputPath = $ogDir . $ogFile;
        $meta = self::buildOpenGraphMeta($cleanModule, $ogFile, $outputPath, $imageBaseUrl);

        if (is_file($outputPath)) {
            $existingSize = (int) @filesize($outputPath);
            $sourceModifiedTime = (int) @filemtime($sourcePath);
            $ogModifiedTime = (int) @filemtime($outputPath);
            if ($existingSize > 0 && $existingSize <= (self::OG_MAX_BYTES + 15000) && $ogModifiedTime >= $sourceModifiedTime) {
                return $meta;
            }
        }

        if (!is_dir($ogDir)) {
            @mkdir($ogDir, 0775, true);
            @chmod($ogDir, 0775);
        }
        if (!is_dir($ogDir) || !is_writable($ogDir)) {
            return null;
        }

        $bytes = self::encodeOpenGraphCanvas($sourcePath);
        if ($bytes === null || $bytes === '') {
            return null;
        }

        $tmpPath = $ogDir . '.' . $ogFile . '.' . uniqid('', true) . '.tmp';
        if (@file_put_contents($tmpPath, $bytes) === false) {
            @unlink($tmpPath);
            return null;
        }
        @chmod($tmpPath, 0664);
        if (!@rename($tmpPath, $outputPath)) {
            @unlink($tmpPath);
            return null;
        }

        return $meta;
    }

    /**
     * Deletes the generated 1200x630 Open Graph companion JPEG when a source image is removed.
     */
    public static function deleteOpenGraphJpeg(string $moduleName, ?string $imageField): void
    {
        $cleanModule = preg_replace('/[^a-z0-9_]/i', '', $moduleName) ?? '';
        $cleanFile = basename(trim((string) $imageField));
        if ($cleanModule === '' || $cleanFile === '') {
            return;
        }

        $baseName = (string) pathinfo($cleanFile, PATHINFO_FILENAME);
        if ($baseName === '') {
            return;
        }

        $baseRoot = defined('root_dir') ? rtrim((string) root_dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR : dirname(__DIR__) . DIRECTORY_SEPARATOR;
        $ogPath = $baseRoot . 'static' . DIRECTORY_SEPARATOR . $cleanModule . DIRECTORY_SEPARATOR . 'og' . DIRECTORY_SEPARATOR . $baseName . '.jpg';
        if (is_file($ogPath)) {
            @unlink($ogPath);
        }
    }

    private static function extractRasterFilename(?string $imageField): string
    {
        $rawField = trim((string) $imageField);
        if ($rawField === '' || $rawField === '#') {
            return '';
        }

        $pathOnly = parse_url($rawField, PHP_URL_PATH);
        $cleanFile = basename($pathOnly !== false && $pathOnly !== null ? $pathOnly : $rawField);
        $ext = strtolower((string) pathinfo($cleanFile, PATHINFO_EXTENSION));
        if ($cleanFile === '' || in_array($ext, ['svg', 'ico'], true)) {
            return '';
        }

        return $cleanFile;
    }

    private static function resolveOpenGraphSourcePath(string $baseRoot, string $cleanModule, string $cleanFile): ?string
    {
        $primary = $baseRoot . 'static' . DIRECTORY_SEPARATOR . $cleanModule . DIRECTORY_SEPARATOR . $cleanFile;
        if (is_file($primary) && (int) @filesize($primary) > 0) {
            return $primary;
        }

        if ($cleanModule === 'mod_video') {
            $localVideoPoster = $baseRoot . 'static' . DIRECTORY_SEPARATOR . 'mod_video' . DIRECTORY_SEPARATOR . 'image' . DIRECTORY_SEPARATOR . $cleanFile;
            if (is_file($localVideoPoster) && (int) @filesize($localVideoPoster) > 0) {
                return $localVideoPoster;
            }
        }

        return null;
    }

    private static function buildOpenGraphMeta(string $cleanModule, string $ogFile, string $outputPath, string $imageBaseUrl): array
    {
        $relPath = $cleanModule . '/og/' . $ogFile;
        $publicUrl = $imageBaseUrl !== '' ? rtrim($imageBaseUrl, '/') . '/' . $relPath : '';
        if ($publicUrl !== '' && class_exists('Helper')) {
            $publicUrl = Helper::sitemap_url($publicUrl);
        }

        return [
            'path' => $outputPath,
            'file' => $ogFile,
            'relative_path' => $relPath,
            'url' => $publicUrl,
            'mime' => 'image/jpeg',
            'width' => self::OG_IMAGE_WIDTH,
            'height' => self::OG_IMAGE_HEIGHT,
        ];
    }

    private static function encodeOpenGraphCanvas(string $sourcePath): ?string
    {
        if (extension_loaded('gd')) {
            $driver = new GdDriver();
        } elseif (extension_loaded('imagick')) {
            $driver = new ImagickDriver();
        } else {
            return null;
        }

        try {
            $manager = new ImageManager($driver);
            $img = $manager->read($sourcePath);
            $img->cover(self::OG_IMAGE_WIDTH, self::OG_IMAGE_HEIGHT);

            // Flatten alpha channels against opaque white so PNG/WebP transparency never renders black in JPEG
            $canvas = $manager->create(self::OG_IMAGE_WIDTH, self::OG_IMAGE_HEIGHT)->fill('ffffff');
            $canvas->place($img);

            $encoded = null;
            foreach ([82, 74, 66, 58, 50] as $quality) {
                $candidate = $canvas->toJpeg(quality: $quality, progressive: true, strip: true);
                $encoded = $candidate;
                if ($candidate->size() <= self::OG_MAX_BYTES) {
                    break;
                }
            }

            return $encoded !== null ? $encoded->toString() : null;
        } catch (\Throwable $e) {
            error_log('ImageService::ensureOpenGraphJpeg Error: ' . $e->getMessage());
            return null;
        }
    }
}
