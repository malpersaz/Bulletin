<?php

namespace E2E\Harness;

class CssAuditor
{
    private string $baseDir;

    public function __construct(string $baseDir)
    {
        $this->baseDir = rtrim($baseDir, '/');
    }

    public function auditStylesheet(string $relPath): array
    {
        $fullPath = $this->baseDir . '/' . ltrim($relPath, '/');
        if (!file_exists($fullPath)) {
            return [
                'exists' => false,
                'errors' => ["Stylesheet not found: {$relPath}"],
            ];
        }

        $content = file_get_contents($fullPath);
        $errors = [];

        // Check for phone viewport and touch targets
        $hasTouchTargetRules = preg_match('/min-height\s*:\s*(44px|48px|3rem|2\.75rem)/i', $content) ||
                               preg_match('/min-width\s*:\s*(44px|48px|3rem|2\.75rem)/i', $content) ||
                               preg_match('/padding\s*:\s*([^;]+)/i', $content);

        // Check for fixed bottom clearances if applicable
        $hasBottomClearance = preg_match('/padding-bottom\s*:\s*(calc\(|80px|96px|100px|6rem)/i', $content) ||
                              preg_match('/margin-bottom\s*:\s*(80px|96px|100px|6rem)/i', $content);

        return [
            'exists' => true,
            'file' => $relPath,
            'size' => strlen($content),
            'has_touch_targets' => (bool)$hasTouchTargetRules,
            'has_bottom_clearance' => (bool)$hasBottomClearance,
            'errors' => $errors,
        ];
    }
}
