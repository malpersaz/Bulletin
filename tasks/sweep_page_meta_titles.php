<?php
/**
 * Task: Sweep Page Meta Titles (Title Hygiene)
 * Strips trailing brand suffixes ('| Amed Bülten', '- Amed Bülten') from mod_page.title
 * to prevent duplicate branding in SERPs and template headers.
 *
 * Amed Framework / Bulletin CMS
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../init.php';

$db = new Database();

echo "Starting mod_page meta title sweep...\n";

try {
    $rows = $db->select('*')->from('mod_page')->fetchAll();
} catch (\Throwable $e) {
    echo "Database error: " . $e->getMessage() . "\n";
    exit(1);
}

if (empty($rows)) {
    echo "No pages found in mod_page.\n";
    exit(0);
}

$updatedCount = 0;
$pattern = '/\s*[|–—-]\s*Amed\s*B[üu]lten(?:i)?\s*$/iu';

foreach ($rows as $row) {
    $id = (int) $row['id'];
    $currentTitle = trim((string) ($row['title'] ?? ''));
    if ($currentTitle === '') {
        continue;
    }

    $cleanedTitle = trim((string) preg_replace($pattern, '', $currentTitle));
    if ($cleanedTitle === '') {
        $cleanedTitle = trim((string) ($row['name'] ?? ''));
    }

    if ($cleanedTitle !== $currentTitle) {
        $db->update('mod_page', ['title' => $cleanedTitle])->where('id', $id)->execute();
        echo sprintf("Updated page [%d] '%s':\n  Before: %s\n  After:  %s\n", $id, $row['name'], $currentTitle, $cleanedTitle);
        $updatedCount++;
    }
}

echo sprintf("Sweep complete. Updated %d page(s).\n", $updatedCount);
