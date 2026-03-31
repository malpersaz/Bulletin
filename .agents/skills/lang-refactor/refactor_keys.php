<?php
/**
 * Language Key Refactor Script
 * Usage: php refactor_keys.php <module> <old_key> <new_key>
 */

if ($argc < 4) {
    die("Usage: php refactor_keys.php <module> <old_key> <new_key>\n");
}

$module = $argv[1];
$oldKey = $argv[2];
$newKey = $argv[3];

$rootDir = realpath(__DIR__ . '/../../../');
$langDir = "{$rootDir}/mod/{$module}/lang";

if (!is_dir($langDir)) {
    die("Error: Language directory not found at {$langDir}\n");
}

echo "Refactoring '{$oldKey}' to '{$newKey}' in module '{$module}'...\n";

// 1. Update Language Files
$langFiles = glob("{$langDir}/*.php");
foreach ($langFiles as $file) {
    $content = file_get_contents($file);
    
    // 1. Handle flat keys: '9' => or ['9'] = (using word boundaries or exact quotes)
    $patternFlat = "/(['\"])(" . preg_quote($oldKey, '/') . ")(['\"])\s*(=>|=)/";
    $replacementFlat = "$1{$newKey}$3 $4";
    $content = preg_replace($patternFlat, $replacementFlat, $content);

    // 2. Handle nested assignments with precision: ['admin']['settings']['1'] =
    $parts = explode('.', $oldKey);
    $newParts = explode('.', $newKey);
    
    // Pattern that matches the specific nested sequence with optional whitespace
    $patternNested = "";
    foreach ($parts as $p) {
        $patternNested .= "\[\s*['\"]" . preg_quote($p, '/') . "['\"]\s*\]\s*";
    }
    // Anchor to the assignment
    $patternNested = "/" . $patternNested . "(=)/";
    
    // Replacement string
    $replacementNested = "";
    foreach ($newParts as $np) {
        $replacementNested .= "['$np']";
    }
    $replacementNested .= " =";
    
    $content = preg_replace($patternNested, $replacementNested, $content);

    if ($content !== file_get_contents($file)) {
        file_put_contents($file, $content);
        echo "Updated language file: " . basename($file) . "\n";
    }
}

// 2. Update Code Usage (Recursive Search & Replace)
$searchDirs = [
    "{$rootDir}/mod/{$module}",
    "{$rootDir}/templates/admin/{$module}",
    "{$rootDir}/templates/admin/mod_{$module}" // some follow this pattern
];

foreach ($searchDirs as $dir) {
    if (!is_dir($dir)) continue;
    
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($iterator as $file) {
        if ($file->isDir()) continue;
        
        $filePath = $file->getPathname();
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);
        
        if (!in_array($ext, ['php', 'twig', 'js'])) continue;
        
        $content = file_get_contents($filePath);
        $changed = false;
        
        // PHP: Handle both single and nested keys: $lang['module']['key1']['key2']
        $keyParts = explode('.', $oldKey);
        $phpOldKeyPattern = "";
        foreach ($keyParts as $part) {
            $phpOldKeyPattern .= "\[\s*['\"]" . preg_quote($part, '/') . "['\"]\s*\]\s*";
        }
        
        $newKeyParts = explode('.', $newKey);
        $phpNewKeyReplacement = "";
        foreach ($newKeyParts as $part) {
            $phpNewKeyReplacement .= "['{$part}']";
        }

        $phpPattern = "/(\\\$lang\s*\[\s*['\"]" . preg_quote($module, '/') . "['\"]\s*\]\s*)" . str_replace('\[', '\\\[', str_replace('\]', '\\\]', $phpOldKeyPattern)) . "/";
        $newContent = preg_replace($phpPattern, "$1{$phpNewKeyReplacement}", $content);
        if ($newContent !== $content) { $content = $newContent; $changed = true; }

        // Twig: lang.module.old_key
        $twigPattern = "/(lang\." . preg_quote($module, '/') . "\.)(" . preg_quote($oldKey, '/') . ")/";
        $newContent = preg_replace($twigPattern, "$1{$newKey}", $content);
        if ($newContent !== $content) { $content = $newContent; $changed = true; }
        
        // General: ['old_key'] - risky but sometimes needed for nested keys
        // Let's be careful with this. For now, only if it follows the module context.
        
        if ($changed) {
            file_put_contents($filePath, $content);
            echo "Updated code file: " . str_replace($rootDir, '', $filePath) . "\n";
        }
    }
}

echo "Done.\n";
