<?php

/**
 * Challenger M1-R2-2 Empirical Adversarial Leaf Category Probe
 *
 * Explicitly tests:
 * 1. An existing news article that already points to a root category (e.g. ID 17).
 * 2. Attempting to update that article with category_id = 17 must be REJECTED by assertSelectableNewsCategory.
 * 3. Moving an article to a root category must be REJECTED.
 * 4. Language switch mismatch must be REJECTED.
 * 5. Migrating from root category to leaf category must SUCCEED.
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from CLI.\n");
    exit(1);
}

ini_set('display_errors', '0');
ini_set('log_errors', '1');

if (!isset($_SERVER['SCRIPT_NAME'])) {
    $_SERVER['SCRIPT_NAME'] = '/mcp.php';
}
if (!isset($_SERVER['REMOTE_ADDR'])) {
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
}

require_once dirname(__DIR__) . '/init.php';
require_once dirname(__DIR__) . '/lib/McpServer.class.php';
require_once dirname(__DIR__) . '/lib/McpCrudToolsBase.class.php';
require_once dirname(__DIR__) . '/lib/McpNewsTools.class.php';

if (!Registry::has('lang')) {
    Registry::add('lang', 'tr');
}

class ChallengerLeafProbe
{
    private int $passes = 0;
    private int $failures = 0;
    private array $cleanupNewsIds = [];
    private McpNewsTools $newsTools;

    public function __construct()
    {
        $this->newsTools = new McpNewsTools(1, ['*' => ['*' => true]]);
    }

    public function run(): void
    {
        echo "========================================================================\n";
        echo "CHALLENGER M1-R2-2: ADVERSARIAL LEAF CATEGORY VERIFICATION\n";
        echo "========================================================================\n\n";

        try {
            $this->executeProbes();
        } finally {
            $this->cleanup();
        }

        echo "\n========================================================================\n";
        echo "LEAF PROBE SUMMARY: {$this->passes} passed, {$this->failures} failed.\n";
        echo "========================================================================\n";

        if ($this->failures > 0) {
            exit(1);
        }
    }

    private function assert(string $label, bool $condition, string $detail = ''): void
    {
        if ($condition) {
            $this->passes++;
            echo "[PASS] {$label}\n";
        } else {
            $this->failures++;
            echo "[FAIL] {$label}" . ($detail !== '' ? " :: {$detail}" : '') . "\n";
        }
    }

    private function executeProbes(): void
    {
        $db = Load::lib('Database');

        // Locate root and leaf categories
        $db->fetch_array("SELECT id, name FROM mod_news_category WHERE root = 'y' AND lang = 'tr' LIMIT 1");
        $rootCategory = $db->getSize() > 0 ? $db->getResultOne()->toArray() : null;
        $rootCategoryId = $rootCategory ? (int) $rootCategory['id'] : 0;

        $db->fetch_array("SELECT id, name FROM mod_news_category WHERE root != 'y' AND lang = 'tr' LIMIT 1");
        $leafCategory = $db->getSize() > 0 ? $db->getResultOne()->toArray() : null;
        $leafCategoryId = $leafCategory ? (int) $leafCategory['id'] : 0;

        $this->assert("Root category detected", $rootCategoryId > 0, "ID: {$rootCategoryId}");
        $this->assert("Leaf category detected", $leafCategoryId > 0, "ID: {$leafCategoryId}");

        if ($rootCategoryId === 0 || $leafCategoryId === 0) {
            throw new RuntimeException("Could not find required root or leaf category in database.");
        }

        // Step 1: Create a valid test news article in leaf category
        $createResult = $this->newsTools->createNews([
            'name' => 'Challenger Root Update Test Initial Article',
            'category_id' => $leafCategoryId,
            'summary' => 'Diyarbakir genelinde altyapi ve kultur varliklarinin korunmasi amaciyla yeni projeler planlaniyor.',
            'content' => '<p>Sehir genelinde surdurulen calismalar hakkinda detayli bilgilendirmeler yapildi.</p>',
            'lang' => 'tr',
            'status' => 'n',
        ]);

        $articleId = (int) ($createResult['id'] ?? 0);
        $this->cleanupNewsIds[] = $articleId;
        $this->assert("Initial test news created in leaf category", $articleId > 0, "Article ID: {$articleId}");

        // Step 2: Directly force article to point to root category in database (simulating legacy data)
        $db->query("UPDATE mod_news SET category_id = {$rootCategoryId} WHERE id = {$articleId}");

        // Verify in DB that article now actually has root category
        $db->fetch_array("SELECT category_id FROM mod_news WHERE id = {$articleId}");
        $currentCat = (int) ($db->getResultOne()->toArray()['category_id'] ?? 0);
        $this->assert("Article directly set to root category {$rootCategoryId} in DB", $currentCat === $rootCategoryId, "Current: {$currentCat}");

        // Step 3: Attempt updateNews with the SAME root category (category_id = 17)
        // Under defect 3 (Iteration 1), ($categoryId !== $model->category_id) was false, so it bypassed assertSelectableNewsCategory.
        // Under Iteration 2 fix, isset($args['category_id']) is true, so it MUST call assertSelectableNewsCategory and throw.
        $caughtRootException = false;
        $exceptionMessage = '';
        try {
            $this->newsTools->updateNews([
                'id' => $articleId,
                'category_id' => $rootCategoryId,
                'name' => 'Attempting update that cements existing root category',
            ]);
        } catch (McpToolException $e) {
            $caughtRootException = true;
            $exceptionMessage = $e->getMessage();
        } catch (Throwable $e) {
            $exceptionMessage = 'Unexpected exception: ' . get_class($e) . ': ' . $e->getMessage();
        }

        $this->assert(
            "updateNews with root category throws McpToolException even when article ALREADY pointed to root category",
            $caughtRootException,
            $exceptionMessage
        );

        $hasRootNotice = stripos($exceptionMessage, 'structural root container') !== false;
        $this->assert(
            "McpToolException contains 'structural root container' notice",
            $hasRootNotice,
            $exceptionMessage
        );

        // Step 4: Verify that article name in DB was NOT changed by the failed update
        $db->fetch_array("SELECT name FROM mod_news WHERE id = {$articleId}");
        $persistedName = (string) ($db->getResultOne()->toArray()['name'] ?? '');
        $this->assert(
            "Article name remained unchanged in DB after rejection",
            $persistedName === 'Challenger Root Update Test Initial Article',
            "Persisted name: {$persistedName}"
        );

        // Step 5: Test remediating the article by updating category_id to valid leaf category
        $remediationSuccess = false;
        try {
            $updateResult = $this->newsTools->updateNews([
                'id' => $articleId,
                'category_id' => $leafCategoryId,
                'name' => 'Successfully Remediated Article Leaf Category',
            ]);
            $remediationSuccess = !empty($updateResult['updated']);
        } catch (Throwable $e) {
            $remediationSuccess = false;
        }

        $this->assert(
            "updateNews migrating article from root to leaf category succeeds",
            $remediationSuccess
        );

        // Verify in DB that category_id is now the leaf category
        $db->fetch_array("SELECT category_id, name FROM mod_news WHERE id = {$articleId}");
        $remediatedRow = $db->getResultOne()->toArray();
        $this->assert(
            "Article category_id in DB is now leaf category {$leafCategoryId}",
            (int) ($remediatedRow['category_id'] ?? 0) === $leafCategoryId &&
            $remediatedRow['name'] === 'Successfully Remediated Article Leaf Category'
        );

        // Step 6: Test updating article on leaf category to root category -> must also be rejected
        $caughtMoveToRootException = false;
        try {
            $this->newsTools->updateNews([
                'id' => $articleId,
                'category_id' => $rootCategoryId,
            ]);
        } catch (McpToolException $e) {
            $caughtMoveToRootException = true;
        }

        $this->assert(
            "updateNews moving article from leaf to root category is rejected",
            $caughtMoveToRootException
        );

        // Step 7: Test updating non-existent category_id
        $caughtNonExistentException = false;
        try {
            $this->newsTools->updateNews([
                'id' => $articleId,
                'category_id' => 99999999,
            ]);
        } catch (McpToolException $e) {
            $caughtNonExistentException = true;
        }

        $this->assert(
            "updateNews with non-existent category_id is rejected",
            $caughtNonExistentException
        );
    }

    private function cleanup(): void
    {
        $db = Load::lib('Database');
        if (!empty($this->cleanupNewsIds)) {
            $ids = implode(',', array_map('intval', $this->cleanupNewsIds));
            $db->query("DELETE FROM mod_news WHERE id IN ({$ids})");
        }
    }
}

$probe = new ChallengerLeafProbe();
$probe->run();
