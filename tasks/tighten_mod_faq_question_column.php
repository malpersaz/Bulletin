<?php
/**
 * Task: Tighten mod_faq.question Column (Schema Limit Parity)
 * Aligns the mod_faq.question column with the enforced writer limit (255)
 * used by the admin validator, the modal editor, and McpFaqTools.
 * Idempotent: safe to re-run, reports [SKIP] when already tightened.
 *
 * Amed Framework / Bulletin CMS
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../init.php';

$db = new Database();

echo "Starting mod_faq.question column tightening...\n";

try {
    $db->fetch_array(
        "SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mod_faq' AND COLUMN_NAME = 'question' LIMIT 1"
    );
    $columnRows = $db->getResult()->toArray();
} catch (\Throwable $e) {
    echo "Database error while reading the column type: " . $e->getMessage() . "\n";
    exit(1);
}

if (empty($columnRows)) {
    echo "[SKIP] mod_faq.question column not found; nothing to tighten.\n";
    exit(0);
}

$currentType = strtoupper(trim((string) $columnRows[0]['COLUMN_TYPE']));
if ($currentType === 'VARCHAR(255)') {
    echo "[SKIP] mod_faq.question is already varchar(255).\n";
    exit(0);
}

echo "Current column type: {$currentType}\n";

try {
    $db->fetch_array("SELECT MAX(CHAR_LENGTH(question)) AS max_length FROM mod_faq");
    $lengthRows = $db->getResult()->toArray();
} catch (\Throwable $e) {
    echo "Database error while measuring question lengths: " . $e->getMessage() . "\n";
    exit(1);
}

$maxLength = (int) ($lengthRows[0]['max_length'] ?? 0);
if ($maxLength > 255) {
    echo "[ABORT] {$maxLength} character question(s) found (limit 255). Shorten these rows first:\n";
    try {
        $db->fetch_array("SELECT id, CHAR_LENGTH(question) AS length FROM mod_faq WHERE CHAR_LENGTH(question) > 255 ORDER BY length DESC");
        foreach ($db->getResult()->toArray() as $row) {
            echo sprintf("  - FAQ #%d (%d characters)\n", (int) $row['id'], (int) $row['length']);
        }
    } catch (\Throwable $e) {
        echo "  (Could not list offending rows: " . $e->getMessage() . ")\n";
    }
    exit(1);
}

echo "Max stored question length: {$maxLength} (within 255). Altering column...\n";

try {
    $db->fetch_array("ALTER TABLE mod_faq MODIFY question VARCHAR(255) NOT NULL");
} catch (\Throwable $e) {
    echo "Database error while altering the column: " . $e->getMessage() . "\n";
    exit(1);
}

try {
    $db->fetch_array(
        "SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mod_faq' AND COLUMN_NAME = 'question' LIMIT 1"
    );
    $verifyRows = $db->getResult()->toArray();
} catch (\Throwable $e) {
    echo "Database error while verifying the column type: " . $e->getMessage() . "\n";
    exit(1);
}

if (strtoupper(trim((string) ($verifyRows[0]['COLUMN_TYPE'] ?? ''))) !== 'VARCHAR(255)') {
    echo "[FAIL] Column alteration did not produce varchar(255).\n";
    exit(1);
}

echo "[OK] mod_faq.question tightened to varchar(255) NOT NULL.\n";
exit(0);
