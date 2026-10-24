<?php

/**
 * BridgeCleanupService - Centralized cleanup service for cross-cutting module bridges
 * 
 * Manages lifecycle cleanup for:
 * - mod_gallery_mod
 * - mod_attachment_mod
 * - mod_faq_mod
 * - static/<module>/ uploaded images and thumbnails
 * 
 * Safely handles scenarios where optional bridge modules are uninstalled or missing.
 */
class BridgeCleanupService
{
    private static $tableCache = [];

    /**
     * Check if a table exists in the active database connection.
     *
     * @param string $table
     * @return bool
     */
    public static function tableExists(string $table): bool
    {
        if (isset(self::$tableCache[$table])) {
            return self::$tableCache[$table];
        }

        $db = Load::lib('Database');
        $safeTable = addslashes($table);
        $adapter = (string) $db->getAdapterType();

        try {
            if (strpos($adapter, 'PDO_SQLITE') !== false) {
                $sql = "SELECT name FROM sqlite_master WHERE type='table' AND name='" . $safeTable . "'";
            } elseif (strpos($adapter, 'POSTGRESQL') !== false) {
                $sql = "SELECT tablename FROM pg_catalog.pg_tables WHERE schemaname NOT IN ('pg_catalog', 'information_schema') AND tablename = '" . $safeTable . "'";
            } else {
                $escapedLike = str_replace(['\\', '_', '%'], ['\\\\', '\\_', '\\%'], $table);
                $sql = "SHOW TABLES LIKE '" . addslashes($escapedLike) . "'";
            }

            $db->fetch_array($sql);
            self::$tableCache[$table] = ($db->getSize() !== 0);
        } catch (\Throwable $e) {
            self::$tableCache[$table] = false;
        }

        return self::$tableCache[$table];
    }

    /**
     * Clean up bridge records and files associated with a single entity record.
     *
     * @param string $modName
     * @param int $recordId
     * @param string|null $image
     * @param array $imageOptions
     * @return bool
     */
    public static function cleanRecord(string $modName, int $recordId, ?string $image = null, array $imageOptions = []): bool
    {
        $recordId = (int) $recordId;
        if ($recordId <= 0) {
            return false;
        }

        $db = Load::lib('Database');
        $modNameSafe = addslashes($modName);

        if (self::tableExists('mod_gallery_mod') || self::tableExists('mod_gallery')) {
            try {
                if (class_exists('Run')) {
                    Run::import('mod_gallery', '_remove', [$modName, $recordId]);
                } elseif (self::tableExists('mod_gallery')) {
                    $db->query("DELETE FROM `mod_gallery` WHERE `mod_name` = '{$modNameSafe}' AND `vid` = {$recordId}");
                }
            } catch (\Throwable $e) {}
        }

        if (self::tableExists('mod_attachment_mod') || self::tableExists('mod_attachment')) {
            try {
                if (class_exists('Run')) {
                    Run::import('mod_attachment', '_remove', [$modName, $recordId]);
                } elseif (self::tableExists('mod_attachment')) {
                    $db->query("DELETE FROM `mod_attachment` WHERE `mod_name` = '{$modNameSafe}' AND `vid` = {$recordId}");
                }
            } catch (\Throwable $e) {}
        }

        if (self::tableExists('mod_faq_mod') || self::tableExists('mod_faq')) {
            try {
                if (class_exists('Run')) {
                    Run::import('mod_faq', '_remove', [$modName, $recordId]);
                } elseif (self::tableExists('mod_faq')) {
                    $db->query("DELETE FROM `mod_faq` WHERE `mod_name` = '{$modNameSafe}' AND `vid` = {$recordId}");
                }
            } catch (\Throwable $e) {}
        }

        if (!empty($image)) {
            self::deleteImageFile($modName, $image, $imageOptions);
        }

        return true;
    }

    /**
     * Clean up all bridge records and stored files when an entire module is uninstalled.
     *
     * @param string $modName
     * @param bool $cleanStaticFiles
     * @return bool
     */
    public static function cleanModule(string $modName, bool $cleanStaticFiles = true): bool
    {
        if (!preg_match('/^mod_[a-z0-9_]+$/', $modName)) {
            return false;
        }

        $db = Load::lib('Database');
        $modNameSafe = addslashes($modName);

        // 1. Collect gallery modules registered for this module and remove their images
        if (self::tableExists('mod_gallery_mod')) {
            try {
                $db->fetch_array("SELECT id FROM `mod_gallery_mod` WHERE `mod_name` = '{$modNameSafe}'");
                $galleryModRows = $db->toArray() ?: [];
                foreach ($galleryModRows as $gmRow) {
                    $gmId = (int)$gmRow['id'];
                    if ($gmId > 0) {
                        if (class_exists('Run')) {
                            Run::import('mod_gallery', '_remove', ['mod_gallery_mod', $gmId]);
                        } elseif (self::tableExists('mod_gallery')) {
                            $db->query("DELETE FROM `mod_gallery` WHERE `mod_name` = 'mod_gallery_mod' AND `vid` = {$gmId}");
                        }
                    }
                }
            } catch (\Throwable $e) {}
        }

        // 2. Remove module-wide records and files in mod_gallery, mod_attachment, mod_faq
        if (class_exists('Run')) {
            try {
                Run::import('mod_gallery', '_remove', [$modName]);
            } catch (\Throwable $e) {}
            try {
                Run::import('mod_attachment', '_remove', [$modName]);
            } catch (\Throwable $e) {}
            try {
                Run::import('mod_faq', '_remove', [$modName]);
            } catch (\Throwable $e) {}
        } else {
            if (self::tableExists('mod_gallery')) {
                try {
                    $db->query("DELETE FROM `mod_gallery` WHERE `mod_name` = '{$modNameSafe}'");
                } catch (\Throwable $e) {}
            }
            if (self::tableExists('mod_attachment')) {
                try {
                    $db->query("DELETE FROM `mod_attachment` WHERE `mod_name` = '{$modNameSafe}'");
                } catch (\Throwable $e) {}
            }
            if (self::tableExists('mod_faq')) {
                try {
                    $db->query("DELETE FROM `mod_faq` WHERE `mod_name` = '{$modNameSafe}'");
                } catch (\Throwable $e) {}
            }
        }

        // 3. Delete registry rows in mod_gallery_mod, mod_attachment_mod, mod_faq_mod
        if (self::tableExists('mod_gallery_mod')) {
            try {
                $db->query("DELETE FROM `mod_gallery_mod` WHERE `mod_name` = '{$modNameSafe}'");
            } catch (\Throwable $e) {}
        }

        if (self::tableExists('mod_attachment_mod')) {
            try {
                $db->query("DELETE FROM `mod_attachment_mod` WHERE `mod_name` = '{$modNameSafe}'");
            } catch (\Throwable $e) {}
        }

        if (self::tableExists('mod_faq_mod')) {
            try {
                $db->query("DELETE FROM `mod_faq_mod` WHERE `mod_name` = '{$modNameSafe}'");
            } catch (\Throwable $e) {}
        }

        // 4. Delete static uploads directory for the module
        if ($cleanStaticFiles) {
            $staticRoot = realpath(root_dir . 'static');
            $staticDir = root_dir . 'static' . DS . $modName;
            $realStaticDir = realpath($staticDir);
            if ($staticRoot !== false && $realStaticDir !== false && strpos($realStaticDir, $staticRoot) === 0 && $realStaticDir !== $staticRoot) {
                self::deleteDirectoryRecursive($realStaticDir);
            }
        }

        return true;
    }

    /**
     * Delete an image and all its resized variants from static/<modName>/.
     *
     * @param string $modName
     * @param string $imageFilename
     * @param array $imageOptions
     * @return bool
     */
    public static function deleteImageFile(string $modName, string $imageFilename, array $imageOptions = []): bool
    {
        if (!preg_match('/^mod_[a-z0-9_]+$/', $modName)) {
            return false;
        }

        $imageFilename = basename(trim($imageFilename));
        if ($imageFilename === '' || $imageFilename === '.' || $imageFilename === '..') {
            return false;
        }

        $staticDir = root_dir . 'static' . DS . $modName;
        $realStaticDir = realpath($staticDir);
        if ($realStaticDir === false || !is_dir($realStaticDir)) {
            return false;
        }

        $mainFile = $realStaticDir . DS . $imageFilename;
        if (file_exists($mainFile) && is_file($mainFile)) {
            $realMainFile = realpath($mainFile);
            if ($realMainFile !== false && strpos($realMainFile, $realStaticDir) === 0) {
                @unlink($realMainFile);
            }
        }

        if (!empty($imageOptions) && is_array($imageOptions)) {
            foreach ($imageOptions as $path => $config) {
                $subDir = basename(trim($path, '/'));
                if ($subDir !== '' && $subDir !== '.' && $subDir !== '..') {
                    $variantDir = $realStaticDir . DS . $subDir;
                    $variantFile = $variantDir . DS . $imageFilename;
                    if (file_exists($variantFile) && is_file($variantFile)) {
                        $realVariantFile = realpath($variantFile);
                        if ($realVariantFile !== false && strpos($realVariantFile, $realStaticDir) === 0) {
                            @unlink($realVariantFile);
                        }
                    }
                }
            }
        } else {
            $thumbFile = $realStaticDir . DS . 'thumb' . DS . $imageFilename;
            if (file_exists($thumbFile) && is_file($thumbFile)) {
                $realThumbFile = realpath($thumbFile);
                if ($realThumbFile !== false && strpos($realThumbFile, $realStaticDir) === 0) {
                    @unlink($realThumbFile);
                }
            }
        }

        return true;
    }

    /**
     * Recursively delete a directory and its contents, unlinking symbolic links without following them.
     *
     * @param string $dir
     */
    public static function deleteDirectoryRecursive(string $dir): void
    {
        if (is_link($dir)) {
            @unlink($dir);
            return;
        }
        if (!is_dir($dir)) {
            return;
        }
        $items = scandir($dir);
        if ($items === false) {
            return;
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . DS . $item;
            if (is_link($path)) {
                @unlink($path);
            } elseif (is_dir($path)) {
                self::deleteDirectoryRecursive($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}
