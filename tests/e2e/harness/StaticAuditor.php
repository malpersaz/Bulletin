<?php

namespace E2E\Harness;

class StaticAuditor
{
    private string $baseDir;

    public function __construct(string $baseDir)
    {
        $this->baseDir = rtrim($baseDir, '/');
    }

    /**
     * Finds all files matching extensions within given relative directory.
     * @return string[]
     */
    public function scanDirectory(string $relDir, array $extensions = ['php', 'twig']): array
    {
        $dir = $this->baseDir . '/' . ltrim($relDir, '/');
        if (!is_dir($dir)) {
            return [];
        }

        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && in_array($file->getExtension(), $extensions, true)) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /**
     * Audits Twig and PHP templates for inline style/script violations.
     */
    public function checkCodeSeparation(array $files): array
    {
        $violations = [];

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $relPath = str_replace($this->baseDir . '/', '', $file);

            // Check for inline style="..." attribute
            if (preg_match_all('/<[a-z0-9_-]+\s+[^>]*\bstyle\s*=\s*["\'][^"\']*["\']/i', $content, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $violations[] = [
                        'file' => $relPath,
                        'type' => 'inline_style_attribute',
                        'snippet' => substr($match[0], 0, 80),
                        'line' => substr_count(substr($content, 0, $match[1]), "\n") + 1,
                    ];
                }
            }

            // Check for <style> tags (excluding valid comments or documentation)
            if (preg_match_all('/<style\b[^>]*>(.*?)<\/style>/is', $content, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $violations[] = [
                        'file' => $relPath,
                        'type' => 'inline_style_block',
                        'snippet' => substr($match[0], 0, 80),
                        'line' => substr_count(substr($content, 0, $match[1]), "\n") + 1,
                    ];
                }
            }

            // Check for inline event handlers (onclick, onload, onchange, etc.)
            if (preg_match_all('/<[a-z0-9_-]+\s+[^>]*\bon[a-z]+\s*=\s*["\'][^"\']*["\']/i', $content, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $violations[] = [
                        'file' => $relPath,
                        'type' => 'inline_event_handler',
                        'snippet' => substr($match[0], 0, 80),
                        'line' => substr_count(substr($content, 0, $match[1]), "\n") + 1,
                    ];
                }
            }
        }

        return $violations;
    }

    /**
     * Checks for references to deprecated modules.
     */
    public function checkDeprecatedModuleReferences(array $files, array $deprecatedModules = ['mod_client', 'mod_service', 'mod_product', 'mod_reference', 'mod_service_category', 'mod_product_category', 'mod_reference_category']): array
    {
        $violations = [];

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $relPath = str_replace($this->baseDir . '/', '', $file);

            // Skip test files, changelog, and agents directory
            if (str_starts_with($relPath, 'tests/') || str_starts_with($relPath, '.agents/') || str_starts_with($relPath, 'docs/')) {
                continue;
            }

            foreach ($deprecatedModules as $dep) {
                if (stripos($content, $dep) !== false) {
                    $violations[] = [
                        'file' => $relPath,
                        'deprecated_module' => $dep,
                    ];
                }
            }
        }

        return $violations;
    }

    /**
     * Checks translation files for missing keys.
     */
    public function checkLanguageParity(string $enFile, string $targetLangFile): array
    {
        if (!file_exists($enFile) || !file_exists($targetLangFile)) {
            return ['status' => 'missing_file', 'missing_keys' => []];
        }

        $en = include $enFile;
        $target = include $targetLangFile;

        if (!is_array($en) || !is_array($target)) {
            return ['status' => 'invalid_return_type', 'missing_keys' => []];
        }

        $missing = array_diff_key($en, $target);
        return [
            'status' => count($missing) === 0 ? 'ok' : 'missing_keys',
            'missing_keys' => array_keys($missing),
        ];
    }
}
