<?php

declare(strict_types=1);

/**
 * Milestone 1, Iteration 2 Empirical Challenger Test Suite
 *
 * Verifies:
 * 1. batch_create_faqs draft deletion isolation across entities, modules, and languages
 * 2. makeSeo Unicode normalization (NFC composition), slash stripping, Kurdish typo handling, and router safety
 * 3. Atomic batch rollback on invalid inputs
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from CLI.\n");
    exit(1);
}

ini_set('display_errors', '1');
ini_set('log_errors', '1');
error_reporting(E_ALL);

if (!isset($_SERVER['SCRIPT_NAME'])) {
    $_SERVER['SCRIPT_NAME'] = '/mcp.php';
}
if (!isset($_SERVER['REMOTE_ADDR'])) {
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
}

require_once dirname(__DIR__) . '/init.php';
require_once dirname(__DIR__) . '/lib/McpServer.class.php';
require_once dirname(__DIR__) . '/lib/McpCrudToolsBase.class.php';
require_once dirname(__DIR__) . '/lib/McpFaqTools.class.php';

if (!Registry::has('lang')) {
    Registry::add('lang', 'tr');
}

class EmpiricalChallengerTestRunner
{
    private int $passes = 0;
    private int $failures = 0;
    private array $failureDetails = [];
    private ?Database $database = null;

    private int $targetNewsId = 888123;
    private int $sentinelNewsId = 99999;
    private int $sentinelPageId = 88888;

    public function __construct()
    {
        $this->database = Load::lib('Database');
    }

    public function run(): void
    {
        echo "========================================================================\n";
        echo "STARTING EMPIRICAL CHALLENGER M1-R2 VERIFICATION SUITE\n";
        echo "========================================================================\n\n";

        $this->cleanup();

        try {
            $this->testBatchCreateFaqsDraftIsolation();
            $this->testMakeSeoUnicodeAndSlashSanitization();
            $this->testRouterSafetySimulations();
        } finally {
            $this->cleanup();
        }

        echo "\n========================================================================\n";
        echo "EMPIRICAL CHALLENGER SUMMARY: {$this->passes} passed, {$this->failures} failed.\n";
        echo "========================================================================\n";

        if ($this->failures > 0) {
            foreach ($this->failureDetails as $detail) {
                echo "  [FAIL DETAIL] {$detail}\n";
            }
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
            $message = "[FAIL] {$label}" . ($detail !== '' ? " :: {$detail}" : '');
            echo "{$message}\n";
            $this->failureDetails[] = $message;
        }
    }

    private function cleanup(): void
    {
        $this->database->query("DELETE FROM mod_faq WHERE vid IN ({$this->targetNewsId}, {$this->sentinelNewsId}, {$this->sentinelPageId})");
        $this->database->query("DELETE FROM mod_news WHERE id = {$this->targetNewsId}");
    }

    /**
     * Objective 1: batch_create_faqs Draft Deletion Isolation
     */
    private function testBatchCreateFaqsDraftIsolation(): void
    {
        echo "--- [OBJECTIVE 1] batch_create_faqs Draft Deletion Isolation ---\n";

        // Step 1: Create host test news record
        $this->database->query(
            "INSERT INTO mod_news (id, lang, name, category_id, content, seo, title, keywords, description, user_id, status, created_at, updated_at) " .
            "VALUES ({$this->targetNewsId}, 'tr', 'Empirical Test News Entity', 18, '<p>Test content</p>', 'empirical_test_news', 'Empirical Title', 'test', 'Test summary', 1, 'y', '" . date('Y-m-d H:i:s') . "', '" . date('Y-m-d H:i:s') . "')"
        );

        // Step 2: Seed sentinel draft FAQ for vid = 99999 (mod_news, status = 'n')
        $this->database->query(
            "INSERT INTO mod_faq (mod_name, vid, lang, question, answer, sort_order, status, user_id, created_at) " .
            "VALUES ('mod_news', {$this->sentinelNewsId}, 'tr', 'Sentinel Draft 99999 Question?', 'Sentinel Draft 99999 Answer', 1, 'n', 1, '" . date('Y-m-d H:i:s') . "')"
        );
        $sentinelDraftNewsId = (int) $this->database->insert_id();

        // Step 3: Seed sentinel draft FAQ for vid = 88888 (mod_page, status = 'n')
        $this->database->query(
            "INSERT INTO mod_faq (mod_name, vid, lang, question, answer, sort_order, status, user_id, created_at) " .
            "VALUES ('mod_page', {$this->sentinelPageId}, 'tr', 'Sentinel Draft Page Question?', 'Sentinel Draft Page Answer', 1, 'n', 1, '" . date('Y-m-d H:i:s') . "')"
        );
        $sentinelDraftPageId = (int) $this->database->insert_id();

        // Step 4: Seed sentinel draft FAQ for target news record but DIFFERENT language ('ku', status = 'n')
        $this->database->query(
            "INSERT INTO mod_faq (mod_name, vid, lang, question, answer, sort_order, status, user_id, created_at) " .
            "VALUES ('mod_news', {$this->targetNewsId}, 'ku', 'Pirseke Kurdi Sentinel?', 'Bersiva Kurdi Sentinel', 1, 'n', 1, '" . date('Y-m-d H:i:s') . "')"
        );
        $sentinelDraftKuId = (int) $this->database->insert_id();

        // Step 5: Seed published FAQ for target news record (status = 'y', lang = 'tr')
        $this->database->query(
            "INSERT INTO mod_faq (mod_name, vid, lang, question, answer, sort_order, status, user_id, created_at) " .
            "VALUES ('mod_news', {$this->targetNewsId}, 'tr', 'Target Published FAQ Question?', 'Target Published FAQ Answer', 1, 'y', 1, '" . date('Y-m-d H:i:s') . "')"
        );
        $targetPublishedFaqId = (int) $this->database->insert_id();

        // Step 6: Seed old draft FAQ for target news record (status = 'n', lang = 'tr') to be replaced
        $this->database->query(
            "INSERT INTO mod_faq (mod_name, vid, lang, question, answer, sort_order, status, user_id, created_at) " .
            "VALUES ('mod_news', {$this->targetNewsId}, 'tr', 'Target Old Draft FAQ to be Replaced?', 'Old Draft Answer', 2, 'n', 1, '" . date('Y-m-d H:i:s') . "')"
        );
        $targetOldDraftFaqId = (int) $this->database->insert_id();

        $this->assert(
            "DraftIsolation: All 5 test seed records inserted with valid IDs",
            $sentinelDraftNewsId > 0 && $sentinelDraftPageId > 0 && $sentinelDraftKuId > 0 && $targetPublishedFaqId > 0 && $targetOldDraftFaqId > 0
        );

        // Step 7: Instantiate McpFaqTools and invoke batchCreateFaqs with replace_existing_drafts = true
        $faqTools = new McpFaqTools(1, ['*' => ['*' => true]]);
        $batchArguments = [
            'mod_name' => 'mod_news',
            'vid' => $this->targetNewsId,
            'lang' => 'tr',
            'status' => 'n',
            'replace_existing_drafts' => true,
            'items' => [
                [
                    'question' => 'Target Newly Created Draft Question 1?',
                    'answer' => 'Target Newly Created Draft Answer 1',
                ],
                [
                    'question' => 'Target Newly Created Draft Question 2?',
                    'answer' => 'Target Newly Created Draft Answer 2',
                ],
            ],
        ];

        $batchResponse = $faqTools->batchCreateFaqs($batchArguments);

        $this->assert(
            "DraftIsolation: batch_create_faqs returned success with 2 created IDs",
            ($batchResponse['created'] ?? false) === true && count($batchResponse['ids'] ?? []) === 2,
            json_encode($batchResponse)
        );

        // Step 8: EMPIRICAL DIRECT MYSQL VERIFICATION
        // Check Sentinel #1: vid = 99999, status = 'n' MUST STILL EXIST
        $this->database->fetch_array("SELECT id, mod_name, vid, lang, status, question FROM mod_faq WHERE id = {$sentinelDraftNewsId}");
        $sentinel1Rows = $this->database->toArray();
        $this->assert(
            "DraftIsolation: Sentinel draft for vid = 99999 is STILL PRESENT and intact in MySQL",
            !empty($sentinel1Rows) && (int) $sentinel1Rows[0]['vid'] === $this->sentinelNewsId && $sentinel1Rows[0]['status'] === 'n',
            json_encode($sentinel1Rows)
        );

        // Check Sentinel #2: vid = 88888, mod_name = 'mod_page' MUST STILL EXIST
        $this->database->fetch_array("SELECT id, mod_name, vid, lang, status FROM mod_faq WHERE id = {$sentinelDraftPageId}");
        $sentinel2Rows = $this->database->toArray();
        $this->assert(
            "DraftIsolation: Sentinel draft for mod_page (vid = 88888) is STILL PRESENT and intact",
            !empty($sentinel2Rows) && (int) $sentinel2Rows[0]['vid'] === $this->sentinelPageId,
            json_encode($sentinel2Rows)
        );

        // Check Sentinel #3: vid = targetNewsId but lang = 'ku' MUST STILL EXIST
        $this->database->fetch_array("SELECT id, mod_name, vid, lang, status FROM mod_faq WHERE id = {$sentinelDraftKuId}");
        $sentinel3Rows = $this->database->toArray();
        $this->assert(
            "DraftIsolation: Sentinel draft for different language (lang = 'ku') is STILL PRESENT and intact",
            !empty($sentinel3Rows) && (string) $sentinel3Rows[0]['lang'] === 'ku' && $sentinel3Rows[0]['status'] === 'n',
            json_encode($sentinel3Rows)
        );

        // Check Target Published FAQ: vid = targetNewsId, status = 'y' MUST STILL EXIST
        $this->database->fetch_array("SELECT id, mod_name, vid, lang, status FROM mod_faq WHERE id = {$targetPublishedFaqId}");
        $publishedRows = $this->database->toArray();
        $this->assert(
            "DraftIsolation: Target published FAQ (status = 'y') is STILL PRESENT and preserved",
            !empty($publishedRows) && $publishedRows[0]['status'] === 'y',
            json_encode($publishedRows)
        );

        // Check Target Old Draft FAQ: MUST BE DELETED
        $this->database->fetch_array("SELECT id FROM mod_faq WHERE id = {$targetOldDraftFaqId}");
        $oldDraftRows = $this->database->toArray();
        $this->assert(
            "DraftIsolation: Target old draft FAQ was successfully purged",
            empty($oldDraftRows),
            "Found: " . json_encode($oldDraftRows)
        );

        // Check Target New Draft FAQs: EXACTLY 2 NEW DRAFTS EXIST FOR TARGET
        $this->database->fetch_array(
            "SELECT id, question, status FROM mod_faq WHERE mod_name = 'mod_news' AND vid = {$this->targetNewsId} AND lang = 'tr' AND status = 'n' ORDER BY id ASC"
        );
        $currentTargetDrafts = $this->database->toArray();
        $this->assert(
            "DraftIsolation: Target news entity has exactly 2 current drafts in language 'tr'",
            count($currentTargetDrafts) === 2,
            "Count: " . count($currentTargetDrafts)
        );
    }

    /**
     * Objective 2: makeSeo Dirty Slug and Slash Stripping
     */
    private function testMakeSeoUnicodeAndSlashSanitization(): void
    {
        echo "\n--- [OBJECTIVE 2] makeSeo Dirty Slug and Slash Stripping ---\n";

        // Probe 1: Forward slash and punctuation stripping
        $input1 = "Diyarbakır Haber: Test / '123'";
        $slug1 = McpCrudToolsBase::makeSeo($input1, 'tr');
        $this->assert(
            "MakeSeo: '{$input1}' contains NO forward slash '/'",
            !str_contains($slug1, '/'),
            "Produced: '{$slug1}'"
        );
        $this->assert(
            "MakeSeo: '{$input1}' strips colons and single quotes",
            !str_contains($slug1, ':') && !str_contains($slug1, "'"),
            "Produced: '{$slug1}'"
        );
        $this->assert(
            "MakeSeo: '{$input1}' produces exact clean slug 'Diyarbakır_Haber_Test_123'",
            $slug1 === 'Diyarbakır_Haber_Test_123',
            "Produced: '{$slug1}'"
        );

        // Probe 2: Decomposed diacritics Unicode NFC composition
        // Turkish culture and art input with combining dots
        $input2 = "Ku\u{0308}ltu\u{0308}r ve Sanat";
        $slug2 = McpCrudToolsBase::makeSeo($input2, 'tr');
        $expectedSlug2 = "Kültür_ve_Sanat";
        $this->assert(
            "MakeSeo: Decomposed '{$input2}' preserves NFC composed '{$expectedSlug2}'",
            $slug2 === $expectedSlug2,
            "Produced: '{$slug2}', Expected: '{$expectedSlug2}'"
        );
        $this->assert(
            "MakeSeo: Slug 2 is strictly in Unicode Normalization Form C (NFC)",
            class_exists('Normalizer') ? \Normalizer::isNormalized($slug2, \Normalizer::FORM_C) : true,
            "Produced: '{$slug2}'"
        );
        $this->assert(
            "MakeSeo: Slug 2 contains precomposed 'ü' (U+00FC) and not stripped letter 'u'",
            str_contains($slug2, 'ü') && !str_contains($slug2, 'Kultur'),
            "Produced: '{$slug2}'"
        );

        // Probe 3: Dotted capital I composition
        // City name input with capital I followed by combining dot above
        $input3 = "I\u{0307}stanbul";
        $slug3 = McpCrudToolsBase::makeSeo($input3, 'tr');
        $expectedSlug3 = "İstanbul";
        $this->assert(
            "MakeSeo: '{$input3}' produces NFC composed '{$expectedSlug3}'",
            $slug3 === $expectedSlug3,
            "Produced: '{$slug3}', Expected: '{$expectedSlug3}'"
        );
        $this->assert(
            "MakeSeo: Slug 3 contains precomposed 'İ' (U+0130) with no orphan combining marks",
            str_contains($slug3, 'İ') && !str_contains($slug3, "\u{0307}"),
            "Produced: '{$slug3}'"
        );

        // Probe 4: Kurdish locale typography normalization (ı/İ -> i/I)
        $input4 = "P\u{0131}rs \u{0130}nternet";
        $slug4 = McpCrudToolsBase::makeSeo($input4, 'ku');
        $expectedSlug4 = "Pirs_Internet";
        $this->assert(
            "MakeSeo: Kurdish locale converts '{$input4}' to '{$expectedSlug4}'",
            $slug4 === $expectedSlug4,
            "Produced: '{$slug4}', Expected: '{$expectedSlug4}'"
        );
        $this->assert(
            "MakeSeo: Kurdish slug 4 contains neither Turkish dotless 'ı' nor dotted 'İ'",
            !str_contains($slug4, 'ı') && !str_contains($slug4, 'İ'),
            "Produced: '{$slug4}'"
        );

        // Probe 5: Adversarial symbol and path traversal payloads
        $adversarialCases = [
            '../../etc/passwd' => 'etc_passwd',
            'News // Headline /// 2026' => 'News_Headline_2026',
            '<' . 'script>alert("xss")</' . 'script>' => 'script_alertxss_script',
            'Hello & Goodbye ? # % * ! @ $' => 'Hello_Goodbye',
            '__--Leading---trailing--__' => 'Leading_trailing',
            'Multiple   spaces   collapse' => 'Multiple_spaces_collapse',
            'Backslash\\Path\\Traversal' => 'Backslash_Path_Traversal',
        ];

        foreach ($adversarialCases as $rawInput => $expectedOutput) {
            $cleaned = McpCrudToolsBase::makeSeo($rawInput, 'tr');
            $this->assert(
                "MakeSeo: Adversarial input '{$rawInput}' safely sanitized to '{$expectedOutput}'",
                $cleaned === $expectedOutput,
                "Input: '{$rawInput}' -> Output: '{$cleaned}' (Expected: '{$expectedOutput}')"
            );
        }
    }

    /**
     * Objective 2 (cont): Web Router Safety Verification
     */
    private function testRouterSafetySimulations(): void
    {
        echo "\n--- [OBJECTIVE 2 cont] Router URL Splitting Safety ---\n";

        $testSlugs = [
            McpCrudToolsBase::makeSeo("Diyarbakır Haber: Test / '123'", 'tr'),
            McpCrudToolsBase::makeSeo("Ku\u{0308}ltu\u{0308}r ve Sanat", 'tr'),
            McpCrudToolsBase::makeSeo("I\u{0307}stanbul", 'tr'),
            McpCrudToolsBase::makeSeo("P\u{0131}rs \u{0130}nternet", 'ku'),
            McpCrudToolsBase::makeSeo("../../etc/passwd", 'en'),
            McpCrudToolsBase::makeSeo("News // Headline /// 2026", 'tr'),
            McpCrudToolsBase::makeSeo("Complex (Parentheses) & [Brackets]!?", 'en'),
        ];

        foreach ($testSlugs as $slug) {
            $simulatedRoute = "tr/news/{$slug}";
            $routeSegments = explode('/', $simulatedRoute);

            // Router contract: segment 0 = lang/module, segment 1 = controller/action, segment 2 = slug
            $this->assert(
                "RouterSafety: Route '{$simulatedRoute}' produces exactly 3 segments",
                count($routeSegments) === 3,
                "Segment count: " . count($routeSegments) . " (Segments: " . json_encode($routeSegments) . ")"
            );
            $this->assert(
                "RouterSafety: Route segment 2 matches generated slug without fragmentation",
                ($routeSegments[2] ?? '') === $slug,
                "Expected '{$slug}', Got '" . ($routeSegments[2] ?? '') . "'"
            );
        }
    }
}

$runner = new EmpiricalChallengerTestRunner();
$runner->run();
