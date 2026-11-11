<?php

declare(strict_types=1);

/**
 * Milestone 1 (M1-2) Adversarial Challenge & Empirical Stress Test Suite
 *
 * Empirical verification of:
 * 1. check_duplicate_news (identical, Jaccard > 0.55, distinct, exact SEO collisions, exclude_id, time window, threshold)
 * 2. list_faqs include_answer projection (omitted, false, true, pagination, payload optimization)
 * 3. list_faq_modules discovery, search filtering, SQL injection resilience, and pagination
 * 4. McpHeadlineTools & McpSurveyTools type safety and robust payload handling
 * 5. Clean teardown and verification
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
require_once dirname(__DIR__) . '/lib/McpNewsTools.class.php';
require_once dirname(__DIR__) . '/lib/McpFaqTools.class.php';
require_once dirname(__DIR__) . '/lib/McpHeadlineTools.class.php';
require_once dirname(__DIR__) . '/lib/McpSurveyTools.class.php';

if (!Registry::has('lang')) {
    Registry::add('lang', 'tr');
}

class ChallengerM1TestRunner
{
    private int $passes = 0;
    private int $failures = 0;
    private array $createdNewsIds = [];
    private array $createdHeadlineIds = [];
    private array $createdSurveyIds = [];
    private array $createdFaqIds = [];
    private string $testLang = 'tr';
    private int $selectableCatId = 18;

    public function run(): void
    {
        echo "========================================================================\n";
        echo "STARTING M1-2 EMPIRICAL CHALLENGE & ADVERSARIAL STRESS TEST SUITE\n";
        echo "========================================================================\n\n";

        $this->setup();

        try {
            $this->testDuplicateNews();
            $this->testListFaqsIncludeAnswer();
            $this->testListFaqModules();
            $this->testHeadlineTypeSafety();
            $this->testSurveyTypeSafety();
        } finally {
            $this->teardown();
        }

        echo "\n========================================================================\n";
        echo "CHALLENGER SUITE COMPLETE: {$this->passes} passed, {$this->failures} failed.\n";
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

    private function setup(): void
    {
        $db = Load::lib('Database');
        // Clean any leftover test records
        $db->query("DELETE FROM mod_news WHERE name LIKE 'CHALLENGE_%' OR name LIKE 'Diyarbakır Sur Tarihi Taş%'");
        $db->query("DELETE FROM mod_headline WHERE name LIKE 'CHALLENGE_%'");
        $db->query("DELETE FROM mod_survey WHERE question LIKE 'CHALLENGE_%'");
        $db->query("DELETE FROM mod_faq WHERE question LIKE 'CHALLENGE_%'");

        // Determine selectable category
        $db->fetch_array("SELECT id FROM mod_news_category WHERE lang = '{$this->testLang}' AND root != 'y' LIMIT 1");
        $row = $db->toArray();
        if (!empty($row[0]['id'])) {
            $this->selectableCatId = (int) $row[0]['id'];
        }
    }

    private function teardown(): void
    {
        $db = Load::lib('Database');
        if (!empty($this->createdNewsIds)) {
            $ids = implode(',', array_map('intval', $this->createdNewsIds));
            $db->query("DELETE FROM mod_news WHERE id IN ({$ids})");
        }
        if (!empty($this->createdHeadlineIds)) {
            $ids = implode(',', array_map('intval', $this->createdHeadlineIds));
            $db->query("DELETE FROM mod_headline WHERE id IN ({$ids})");
        }
        if (!empty($this->createdSurveyIds)) {
            $ids = implode(',', array_map('intval', $this->createdSurveyIds));
            $db->query("DELETE FROM mod_survey WHERE id IN ({$ids})");
            $db->query("DELETE FROM mod_surveyselect WHERE survey_id IN ({$ids})");
        }
        if (!empty($this->createdFaqIds)) {
            $ids = implode(',', array_map('intval', $this->createdFaqIds));
            $db->query("DELETE FROM mod_faq WHERE id IN ({$ids})");
        }

        $db->query("DELETE FROM mod_news WHERE name LIKE 'CHALLENGE_%' OR name LIKE 'Diyarbakır Sur Tarihi Taş%'");
        $db->query("DELETE FROM mod_headline WHERE name LIKE 'CHALLENGE_%'");
        $db->query("DELETE FROM mod_survey WHERE question LIKE 'CHALLENGE_%'");
        $db->query("DELETE FROM mod_faq WHERE question LIKE 'CHALLENGE_%'");
    }

    private function getPermissions(): array
    {
        return ['*' => ['*' => true]];
    }

    // ------------------------------------------------------------------------
    // Objective 1: check_duplicate_news Adversarial Tests
    // ------------------------------------------------------------------------
    private function testDuplicateNews(): void
    {
        echo "\n--- Objective 1: check_duplicate_news Adversarial Probes ---\n";

        $newsTools = new McpNewsTools(1, $this->getPermissions());
        $baseHeadline = "Diyarbakır Sur Tarihi Taş Konak Restorasyonu Tamamlandı";

        // Seed a published news article
        $seedRes = $newsTools->createNews([
            'name' => $baseHeadline,
            'category_id' => $this->selectableCatId,
            'summary' => 'Diyarbakır Sur ilçesinde yürütülen tarihi taş konak restorasyon projesi tamamlanarak turizme açıldı.',
            'content' => '<p>Diyarbakır Sur ilçesinde yer alan tarihi taş konak restorasyon projesi tamamlandı.</p>',
            'lang' => $this->testLang,
            'status' => 'y',
        ]);
        $seedId = (int) $seedRes['id'];
        $seedSeo = (string) ($seedRes['item']['seo'] ?? '');
        $this->createdNewsIds[] = $seedId;

        $this->assert('DuplicateNews: Seed article created', $seedId > 0, "Seed ID: {$seedId}");

        // 1.1 Identical headline probe
        $duplicateIdentical = $newsTools->checkDuplicateNews([
            'name' => $baseHeadline,
            'lang' => $this->testLang,
        ]);
        $this->assert('DuplicateNews: Identical headline flagged as duplicate',
            $duplicateIdentical['has_duplicate'] === true && $duplicateIdentical['duplicate_count'] >= 1
        );
        $firstMatch = $duplicateIdentical['duplicates'][0] ?? [];
        $this->assert('DuplicateNews: Identical headline similarity is 1.0',
            isset($firstMatch['similarity']) && (float) $firstMatch['similarity'] === 1.0,
            'Similarity: ' . ($firstMatch['similarity'] ?? 'null')
        );
        $this->assert('DuplicateNews: Identical headline reasons contain title_similarity and exact_seo_match',
            in_array('title_similarity', $firstMatch['match_reasons'] ?? [], true) &&
            in_array('exact_seo_match', $firstMatch['match_reasons'] ?? [], true),
            json_encode($firstMatch['match_reasons'] ?? [])
        );

        // 1.2 Slight variation above 0.55 Jaccard similarity
        // Original tokens: `diyarbakır`, `sur`, `tarihi`, `taş`, `konak`, `restorasyonu`, `tamamlandı` (7 tokens)
        // Variation: `Diyarbakır Sur Tarihi Taş Restorasyonu Tamamlandı` (missing `konak`, 6 shared out of 7 union = 6/7 = 0.857)
        $varAboveHeadline = "Diyarbakır Sur Tarihi Taş Restorasyonu Tamamlandı";
        $duplicateVariation = $newsTools->checkDuplicateNews([
            'name' => $varAboveHeadline,
            'lang' => $this->testLang,
        ]);
        $this->assert('DuplicateNews: High similarity variation (>0.55) flagged as duplicate',
            $duplicateVariation['has_duplicate'] === true && !empty($duplicateVariation['duplicates'])
        );
        $simVal = (float) ($duplicateVariation['duplicates'][0]['similarity'] ?? 0);
        $this->assert('DuplicateNews: Variation similarity >= 0.55',
            $simVal >= 0.55,
            "Similarity: {$simVal}"
        );
        $this->assert('DuplicateNews: Variation flagged specifically with title_similarity',
            in_array('title_similarity', $duplicateVariation['duplicates'][0]['match_reasons'] ?? [], true)
        );

        // 1.3 Boundary similarity test: variation below 0.55
        // `Diyarbakır Sur Tarihi Park Alanı Düzenlendi`
        // Shared: `diyarbakır`, `sur`, `tarihi` (3 tokens). Union: 10 tokens. 3/10 = 0.30 < 0.55
        $duplicateBelow = $newsTools->checkDuplicateNews([
            'name' => "Diyarbakır Sur Tarihi Park Alanı Düzenlendi",
            'lang' => $this->testLang,
        ]);
        $this->assert('DuplicateNews: Variation below 0.55 Jaccard similarity NOT flagged',
            $duplicateBelow['has_duplicate'] === false && $duplicateBelow['duplicate_count'] === 0,
            json_encode($duplicateBelow)
        );

        // 1.4 Completely different headline
        $duplicateDifferent = $newsTools->checkDuplicateNews([
            'name' => "Ankara Başkent Şehir Hastanesinde Yeni Onkoloji Ünitesi Açıldı",
            'lang' => $this->testLang,
        ]);
        $this->assert('DuplicateNews: Completely different headline NOT flagged',
            $duplicateDifferent['has_duplicate'] === false && $duplicateDifferent['duplicate_count'] === 0
        );

        // 1.5 Exact SEO match collision with completely different headline text
        $duplicateSeoOnly = $newsTools->checkDuplicateNews([
            'name' => "Farklı Metin Ama Aynı Özel SEO Bağlantısı Belirlendi",
            'seo' => $seedSeo,
            'lang' => $this->testLang,
        ]);
        $this->assert('DuplicateNews: Exact SEO match flagged even when headline text differs',
            $duplicateSeoOnly['has_duplicate'] === true && !empty($duplicateSeoOnly['duplicates'])
        );
        $seoMatch = $duplicateSeoOnly['duplicates'][0] ?? [];
        $this->assert('DuplicateNews: SEO match reason contains exact_seo_match',
            in_array('exact_seo_match', $seoMatch['match_reasons'] ?? [], true),
            json_encode($seoMatch)
        );

        // 1.6 exclude_id probe (updating existing article ignores itself)
        $duplicateExclude = $newsTools->checkDuplicateNews([
            'name' => $baseHeadline,
            'lang' => $this->testLang,
            'exclude_id' => $seedId,
        ]);
        $this->assert('DuplicateNews: exclude_id successfully excludes self-record',
            $duplicateExclude['has_duplicate'] === false && $duplicateExclude['duplicate_count'] === 0
        );

        // 1.7 Lookback hours boundary probe
        // Seed an older news item 72 hours in the past
        $pastDate = date('Y-m-d H:i:s', time() - (72 * 3600));
        $db = Load::lib('Database');
        $oldHeadline = "CHALLENGE_OLD Üç Gün Önce Yayınlanan Eski Haber Başlığı";
        $db->query(
            "INSERT INTO mod_news (category_id, name, summary, content, seo, title, keywords, description, lang, status, created_at, user_id) " .
            "VALUES ({$this->selectableCatId}, '{$oldHeadline}', 'Eski özet açıklaması.', '<p>Eski metin.</p>', " .
            "'CHALLENGE_Old_News_Slug', '{$oldHeadline}', 'eski,haber', 'Eski açıklama.', '{$this->testLang}', 'y', '{$pastDate}', 1)"
        );
        $oldId = (int) $db->insert_id();
        $this->createdNewsIds[] = $oldId;

        // Query with default 48h lookback -> should NOT find it
        $duplicateOld48 = $newsTools->checkDuplicateNews([
            'name' => $oldHeadline,
            'lang' => $this->testLang,
            'hours' => 48,
        ]);
        $this->assert('DuplicateNews: 72h-old article not flagged within 48h lookback window',
            $duplicateOld48['has_duplicate'] === false && $duplicateOld48['duplicate_count'] === 0
        );

        // Query with 96h lookback -> SHOULD find it
        $duplicateOld96 = $newsTools->checkDuplicateNews([
            'name' => $oldHeadline,
            'lang' => $this->testLang,
            'hours' => 96,
        ]);
        $this->assert('DuplicateNews: 72h-old article flagged when lookback extended to 96h',
            $duplicateOld96['has_duplicate'] === true && $duplicateOld96['duplicate_count'] >= 1
        );

        // 1.8 Threshold tuning parameter probe
        // Test custom threshold
        $duplicateCustomThreshFail = $newsTools->checkDuplicateNews([
            'name' => $varAboveHeadline,
            'lang' => $this->testLang,
            'threshold' => 0.95, // Higher than the similarity ~0.875
        ]);
        $this->assert('DuplicateNews: High threshold (0.95) does not flag 0.875 similarity',
            $duplicateCustomThreshFail['has_duplicate'] === false
        );

        // 1.9 Adversarial inputs: empty name
        $emptyNameCaught = false;
        try {
            $newsTools->checkDuplicateNews([
                'name' => '   ',
                'lang' => $this->testLang,
            ]);
        } catch (McpToolException $e) {
            $emptyNameCaught = true;
        }
        $this->assert('DuplicateNews: Empty/whitespace name throws McpToolException', $emptyNameCaught);
    }

    // ------------------------------------------------------------------------
    // Objective 2: list_faqs include_answer Projection Adversarial Tests
    // ------------------------------------------------------------------------
    private function testListFaqsIncludeAnswer(): void
    {
        echo "\n--- Objective 2: list_faqs include_answer Adversarial Probes ---\n";

        $faqTools = new McpFaqTools(1, $this->getPermissions());

        // Seed 2 FAQ items for our news seed
        $hostId = $this->createdNewsIds[0] ?? 1;
        $q1 = "CHALLENGE_FAQ Soru Bir: Proje ne zaman başlayacak?";
        $a1 = "Proje önümüzdeki ayın başında resmi olarak başlayacaktır.";
        $q2 = "CHALLENGE_FAQ Soru İki: Katılım şartları nelerdir?";
        $a2 = "Tüm vatandaşlarımız için katılım tamamen ücretsiz ve açıktır.";

        $batchRes = $faqTools->batchCreateFaqs([
            'mod_name' => 'mod_news',
            'vid' => $hostId,
            'lang' => $this->testLang,
            'status' => 'y',
            'items' => [
                ['question' => $q1, 'answer' => $a1],
                ['question' => $q2, 'answer' => $a2],
            ],
        ]);
        $this->assert('ListFaqs: Batch created 2 FAQs for projection testing',
            $batchRes['created'] === true && count($batchRes['ids']) === 2
        );
        foreach ($batchRes['ids'] as $cid) {
            $this->createdFaqIds[] = $cid;
        }

        // 2.1 include_answer: false (explicit)
        $resFalse = $faqTools->listFaqs([
            'mod_name' => 'mod_news',
            'vid' => $hostId,
            'lang' => $this->testLang,
            'include_answer' => false,
        ]);
        $this->assert('ListFaqs: include_answer: false returns items', !empty($resFalse['items']));
        $hasAnswerFalse = false;
        foreach ($resFalse['items'] as $item) {
            if (array_key_exists('answer', $item)) {
                $hasAnswerFalse = true;
                break;
            }
        }
        $this->assert('ListFaqs: include_answer: false strictly OMITS answer key from all items', !$hasAnswerFalse);

        // 2.2 include_answer omitted (default should be false)
        $resDefault = $faqTools->listFaqs([
            'mod_name' => 'mod_news',
            'vid' => $hostId,
            'lang' => $this->testLang,
        ]);
        $hasAnswerDefault = false;
        foreach ($resDefault['items'] as $item) {
            if (array_key_exists('answer', $item)) {
                $hasAnswerDefault = true;
                break;
            }
        }
        $this->assert('ListFaqs: Default (omitted include_answer) strictly OMITS answer key', !$hasAnswerDefault);

        // 2.3 include_answer: true (explicit)
        $resTrue = $faqTools->listFaqs([
            'mod_name' => 'mod_news',
            'vid' => $hostId,
            'lang' => $this->testLang,
            'include_answer' => true,
        ]);
        $this->assert('ListFaqs: include_answer: true returns items', !empty($resTrue['items']));
        $allHaveAnswerTrue = true;
        $answerContentMatches = true;
        foreach ($resTrue['items'] as $item) {
            if (!array_key_exists('answer', $item) || !is_string($item['answer']) || $item['answer'] === '') {
                $allHaveAnswerTrue = false;
            }
            if ($item['question'] === $q1 && $item['answer'] !== $a1) {
                $answerContentMatches = false;
            }
        }
        $this->assert('ListFaqs: include_answer: true INCLUDES answer key in every item', $allHaveAnswerTrue);
        $this->assert('ListFaqs: include_answer: true preserves exact full text answer', $answerContentMatches);

        // 2.4 False values coercion test
        $resCoerce = $faqTools->listFaqs([
            'mod_name' => 'mod_news',
            'vid' => $hostId,
            'lang' => $this->testLang,
            'include_answer' => 0,
        ]);
        $hasAnswerCoerced = false;
        foreach ($resCoerce['items'] as $item) {
            if (array_key_exists('answer', $item)) {
                $hasAnswerCoerced = true;
                break;
            }
        }
        $this->assert('ListFaqs: include_answer: 0 coerced safely to false (no answer key)', !$hasAnswerCoerced);
    }

    // ------------------------------------------------------------------------
    // Objective 3: list_faq_modules Adversarial Tests
    // ------------------------------------------------------------------------
    private function testListFaqModules(): void
    {
        echo "\n--- Objective 3: list_faq_modules Adversarial Probes ---\n";

        $faqTools = new McpFaqTools(1, $this->getPermissions());

        // 3.1 Base discovery list
        $baseList = $faqTools->listFaqModules([]);
        $this->assert('ListFaqModules: Returns items array', isset($baseList['items']) && is_array($baseList['items']));
        $this->assert('ListFaqModules: Total registered modules >= 4', ($baseList['total'] ?? 0) >= 4);

        $firstModule = $baseList['items'][0] ?? [];
        $expectedKeys = ['id', 'name', 'mod_name', 'mod_key', 'mod_value'];
        $hasAllKeys = true;
        foreach ($expectedKeys as $k) {
            if (!array_key_exists($k, $firstModule)) {
                $hasAllKeys = false;
                break;
            }
        }
        $this->assert('ListFaqModules: Item contains id, name, mod_name, mod_key, mod_value', $hasAllKeys);

        // 3.2 Search filtering by mod_name
        $searchRes = $faqTools->listFaqModules(['search' => 'mod_news']);
        $this->assert('ListFaqModules: Search "mod_news" returns results', !empty($searchRes['items']));
        $foundModNews = false;
        foreach ($searchRes['items'] as $m) {
            if (($m['mod_name'] ?? '') === 'mod_news') {
                $foundModNews = true;
                break;
            }
        }
        $this->assert('ListFaqModules: Search "mod_news" correctly isolates mod_news registration', $foundModNews);

        // 3.3 Search filtering by human name
        $searchName = $faqTools->listFaqModules(['search' => 'Event']);
        $this->assert('ListFaqModules: Search "Event" matches Event FAQs', !empty($searchName['items']));
        $this->assert('ListFaqModules: Event module is mod_event', ($searchName['items'][0]['mod_name'] ?? '') === 'mod_event');

        // 3.4 Search non-existent
        $searchNone = $faqTools->listFaqModules(['search' => 'non_existent_module_xyz_99999']);
        $this->assert('ListFaqModules: Search non-existent returns 0 items',
            ($searchNone['total'] ?? -1) === 0 && empty($searchNone['items'])
        );

        // 3.5 Pagination
        $page1 = $faqTools->listFaqModules(['limit' => 2, 'offset' => 0]);
        $page2 = $faqTools->listFaqModules(['limit' => 2, 'offset' => 2]);
        $this->assert('ListFaqModules: Page 1 returns 2 items', count($page1['items']) === 2);
        $this->assert('ListFaqModules: Page 2 returns items', count($page2['items']) >= 1);
        $this->assert('ListFaqModules: Page 1 and Page 2 items are disjoint',
            $page1['items'][0]['id'] !== $page2['items'][0]['id']
        );
        $this->assert('ListFaqModules: Total count invariant across pagination',
            $page1['total'] === $page2['total'] && $page1['total'] === $baseList['total']
        );

        // 3.6 SQL Injection resilience in search parameter
        $sqlInjectionSearch = $faqTools->listFaqModules(['search' => "' OR '1'='1' -- "]);
        $this->assert('ListFaqModules: SQL injection payload safely escaped (returns 0 or valid search, no exception)',
            is_array($sqlInjectionSearch['items'])
        );
    }

    // ------------------------------------------------------------------------
    // Objective 4: McpHeadlineTools & McpSurveyTools Type Safety Tests
    // ------------------------------------------------------------------------
    private function testHeadlineTypeSafety(): void
    {
        echo "\n--- Objective 4.1: McpHeadlineTools Type Safety Probes ---\n";

        $headlineTools = new McpHeadlineTools(1, $this->getPermissions());

        // 4.1.1 createHeadline with standard payload
        $headlineName = "CHALLENGE_HEADLINE Güncel Diyarbakır Kültür ve Sanat Etkinlikleri";
        $typeErrorCaught = false;
        $createRes = null;
        try {
            $createRes = $headlineTools->createHeadline([
                'name' => $headlineName,
                'lang' => $this->testLang,
                'status' => 'y',
            ]);
        } catch (\TypeError $te) {
            $typeErrorCaught = true;
        }
        $this->assert('Headline: createHeadline executes without TypeError', !$typeErrorCaught);
        $this->assert('Headline: createHeadline returns created=true and valid id',
            isset($createRes['created']) && $createRes['created'] === true && !empty($createRes['id'])
        );
        $headlineId = (int) ($createRes['id'] ?? 0);
        if ($headlineId > 0) {
            $this->createdHeadlineIds[] = $headlineId;
        }

        // 4.1.2 updateHeadline with updated name
        $updatedHeadlineName = "CHALLENGE_HEADLINE Güncellenen Diyarbakır Kültür Etkinlikleri Listesi";
        $updateTypeError = false;
        $updateRes = null;
        try {
            $updateRes = $headlineTools->updateHeadline([
                'id' => $headlineId,
                'name' => $updatedHeadlineName,
                'status' => 'n',
            ]);
        } catch (\TypeError $te) {
            $updateTypeError = true;
        }
        $this->assert('Headline: updateHeadline executes without TypeError', !$updateTypeError);
        $this->assert('Headline: updateHeadline updates name and status',
            isset($updateRes['updated']) && $updateRes['updated'] === true &&
            ($updateRes['item']['name'] ?? '') === $updatedHeadlineName &&
            ($updateRes['item']['status'] ?? '') === 'n'
        );

        // 4.1.3 Edge case: name validation boundaries
        $shortNameCaught = false;
        try {
            $headlineTools->createHeadline([
                'name' => 'A', // Too short (min 2)
                'lang' => $this->testLang,
            ]);
        } catch (McpToolException $e) {
            $shortNameCaught = true;
        }
        $this->assert('Headline: Single character name throws McpToolException', $shortNameCaught);
    }

    private function testSurveyTypeSafety(): void
    {
        echo "\n--- Objective 4.2: McpSurveyTools Type Safety Probes ---\n";

        $surveyTools = new McpSurveyTools(1, $this->getPermissions());

        // 4.2.1 createSurvey with standard payload
        $question = "CHALLENGE_SURVEY Diyarbakır genelinde toplu taşıma hizmetlerinden memnun musunuz?";
        $options = [
            ['option_text' => "Evet, son derece memnunum"],
            ['option_text' => "Kısmen memnunum, iyileştirilmeli"],
            ['option_text' => "Hayır, kesinlikle memnun değilim"],
        ];

        $surveyTypeError = false;
        $createRes = null;
        try {
            $createRes = $surveyTools->createSurvey([
                'question' => $question,
                'options' => $options,
                'lang' => $this->testLang,
                'status' => 'y',
            ]);
        } catch (\TypeError $te) {
            $surveyTypeError = true;
        }
        $this->assert('Survey: createSurvey executes without TypeError', !$surveyTypeError);
        $this->assert('Survey: createSurvey returns created=true and valid id',
            isset($createRes['created']) && $createRes['created'] === true && !empty($createRes['id'])
        );
        $surveyId = (int) ($createRes['id'] ?? 0);
        if ($surveyId > 0) {
            $this->createdSurveyIds[] = $surveyId;
        }

        // 4.2.2 updateSurvey with modified question and options
        $updatedQuestion = "CHALLENGE_SURVEY Diyarbakır toplu taşıma araçlarını hangi sıklıkla kullanıyorsunuz?";
        $updateSurveyTypeError = false;
        $updateRes = null;
        try {
            $updateRes = $surveyTools->updateSurvey([
                'id' => $surveyId,
                'question' => $updatedQuestion,
                'status' => 'n',
            ]);
        } catch (\TypeError $te) {
            $updateSurveyTypeError = true;
        }
        $this->assert('Survey: updateSurvey executes without TypeError', !$updateSurveyTypeError);
        $this->assert('Survey: updateSurvey updates question and status',
            isset($updateRes['updated']) && $updateRes['updated'] === true &&
            ($updateRes['item']['question'] ?? '') === $updatedQuestion &&
            ($updateRes['item']['status'] ?? '') === 'n'
        );

        // 4.2.3 Edge case: fewer than 2 options
        $badOptionsCaught = false;
        try {
            $surveyTools->createSurvey([
                'question' => "CHALLENGE_SURVEY Yetersiz seçenekli anket sorusu?",
                'options' => [['option_text' => "Yalnızca tek seçenek"]],
                'lang' => $this->testLang,
            ]);
        } catch (McpToolException $e) {
            $badOptionsCaught = true;
        }
        $this->assert('Survey: createSurvey with < 2 options throws McpToolException', $badOptionsCaught);
    }
}

// Instantiate and execute runner
$runner = new ChallengerM1TestRunner();
$runner->run();
