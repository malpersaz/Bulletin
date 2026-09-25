<?php

namespace E2E\Harness;

class TwigAuditor
{
    private string $baseDir;

    public function __construct(string $baseDir)
    {
        $this->baseDir = rtrim($baseDir, '/');
    }

    public function auditTemplate(string $relPath): array
    {
        $fullPath = $this->baseDir . '/' . ltrim($relPath, '/');
        if (!file_exists($fullPath)) {
            return [
                'exists' => false,
                'errors' => ["Template file not found: {$relPath}"],
            ];
        }

        $content = file_get_contents($fullPath);
        $errors = [];

        // Check for template inheritance if admin template
        $isSubTemplate = str_starts_with($relPath, 'templates/admin/') && !str_contains($relPath, 'partials/') && !str_contains($relPath, 'macros/');
        if ($isSubTemplate) {
            if (!preg_match('/\{%\s*extends\s+[\'"]admin\/layout\.twig[\'"]\s*%\}/', $content) &&
                !preg_match('/\{%\s*extends\s+[\'"]@admin\/layout\.twig[\'"]\s*%\}/', $content) &&
                !str_contains($content, 'extends')) {
                // Not extending layout - check if modal or partial
                if (!str_contains($relPath, 'modal') && !str_contains($relPath, 'sheet')) {
                    $errors[] = "Admin template does not extend admin/layout.twig";
                }
            }
        }

        // Check block integrity
        $blocks = [];
        if (preg_match_all('/\{%\s*block\s+([a-zA-Z0-9_-]+)\s*%\}/', $content, $matches)) {
            $blocks = $matches[1];
        }

        return [
            'exists' => true,
            'file' => $relPath,
            'blocks' => $blocks,
            'has_content_block' => in_array('content', $blocks, true),
            'has_scripts_block' => in_array('scripts', $blocks, true) || in_array('admin_scripts', $blocks, true),
            'errors' => $errors,
        ];
    }

    public function auditMacro(string $relPath, array $expectedMacros = ['render', 'schema']): array
    {
        $fullPath = $this->baseDir . '/' . ltrim($relPath, '/');
        if (!file_exists($fullPath)) {
            return [
                'exists' => false,
                'errors' => ["Macro file not found: {$relPath}"],
            ];
        }

        $content = file_get_contents($fullPath);
        $foundMacros = [];
        if (preg_match_all('/\{%\s*macro\s+([a-zA-Z0-9_-]+)\s*\(/', $content, $matches)) {
            $foundMacros = $matches[1];
        }

        $missing = array_diff($expectedMacros, $foundMacros);
        return [
            'exists' => true,
            'file' => $relPath,
            'macros' => $foundMacros,
            'missing' => array_values($missing),
            'valid' => count($missing) === 0,
        ];
    }
}
