<?php
/**
 * ImageService - Centralized image handling for all modules
 * Eliminates duplicate image upload/resize/delete code
 * 
 * Amed Framework
 * @author Refactored for better maintainability
 */

class ImageService
{
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
            } else {
                $this->last_error = $image->error;
                return false;
            }
            
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
            } else {
                $this->last_error = $image->error;
                return false;
            }
            
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
        $site = Kayit::getir('site_config');
        return $site['iaddress'] . $module_name . $size . $image_url;
    }
}