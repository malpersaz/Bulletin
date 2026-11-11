<?php

/**
 * Challenger M1-1 Empirical Stress Test Suite
 * Run via: docker exec bulletin_app php tasks/challenger_stress_test.php
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
if (!isset($_SERVER['HTTP_USER_AGENT'])) {
    $_SERVER['HTTP_USER_AGENT'] = 'mcp-challenger';
}

require_once dirname(__DIR__) . '/init.php';
require_once dirname(__DIR__) . '/lib/McpServer.class.php';
require_once dirname(__DIR__) . '/lib/McpCrudToolsBase.class.php';

if (!Registry::has('lang')) {
    Registry::add('lang', 'tr');
}

class ChallengerMcpServer extends McpServer
{
    private string $body = '';
    public int $lastStatusCode = 200;

    public function setBody(string $body): void
    {
        $this->body = $body;
    }

    protected function readRequestBody(): string
    {
        return $this->body;
    }

    protected function sendJson($payload, int $statusCode): void
    {
        $this->lastStatusCode = $statusCode;
        parent::sendJson($payload, $statusCode);
    }

    protected function sendEmptyStatus(int $statusCode): void
    {
        $this->lastStatusCode = $statusCode;
        parent::sendEmptyStatus($statusCode);
    }
}

$passCount = 0;
$failCount = 0;
$findings = [];

function stress_assert(string $label, bool $condition, string $detail = ''): bool
{
    global $passCount, $failCount, $findings;
    if ($condition) {
        $passCount++;
        echo "  [PASS] {$label}\n";
        return true;
    } else {
        $failCount++;
        $msg = "[FAIL] {$label}" . ($detail !== '' ? " | Detail: {$detail}" : '');
        echo "  {$msg}\n";
        $findings[] = $msg;
        return false;
    }
}

function callRpc(array $payload, string $bearerKey): array
{
    $server = new ChallengerMcpServer();
    $savedServer = $_SERVER;
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['CONTENT_TYPE'] = 'application/json';
    $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $bearerKey;

    $server->setBody(json_encode($payload));

    ob_start();
    try {
        $server->handle();
        $_SERVER = $savedServer;
        $raw = ob_get_clean();
        $decoded = json_decode($raw, true);
        if ($decoded === null) {
            return ['__raw' => $raw, '__status' => $server->lastStatusCode];
        }
        if (!array_is_list($decoded)) {
            $decoded['__status'] = $server->lastStatusCode;
        }
        return $decoded;
    } catch (\Throwable $e) {
        $_SERVER = $savedServer;
        ob_end_clean();
        return ['__exception' => get_class($e) . ': ' . $e->getMessage(), '__status' => 500];
    }
}

function callTool(string $tool, array $args, string $bearerKey): array
{
    $req = [
        'jsonrpc' => '2.0',
        'id' => mt_rand(1000, 999999),
        'method' => 'tools/call',
        'params' => [
            'name' => $tool,
            'arguments' => $args,
        ],
    ];
    $resp = callRpc($req, $bearerKey);
    $isError = (bool) ($resp['result']['isError'] ?? false);
    $text = $resp['result']['content'][0]['text'] ?? '';
    $parsed = json_decode($text, true);

    return [
        'isError' => $isError,
        'status' => $resp['__status'] ?? 200,
        'text' => $text,
        'data' => is_array($parsed) ? $parsed : null,
        'raw_resp' => $resp,
    ];
}

echo "=================================================================\n";
echo "CHALLENGER M1-1 EMPIRICAL STRESS TEST HARNESS\n";
echo "=================================================================\n\n";

// 0. Setup: ephemeral key & cleanup
$db = Load::lib('Database');
$testLang = 'tr';
$db->fetch_array("SELECT seo FROM lang WHERE status = 'y' ORDER BY is_default DESC LIMIT 1");
$langRows = $db->toArray();
if (!empty($langRows[0]['seo'])) {
    $testLang = (string) $langRows[0]['seo'];
}

$plaintextKey = 'amcp_' . bin2hex(random_bytes(24));
$keyHash = hash('sha256', $plaintextKey);
$keyPrefix = substr($plaintextKey, 0, 12);
$db->query("DELETE FROM mod_mcp_api_key WHERE name = 'Challenger test key'");
$db->query(
    "INSERT INTO mod_mcp_api_key (name, api_key_hash, key_prefix, group_id, user_id, status, created_at) " .
    "VALUES ('Challenger test key', '{$keyHash}', '{$keyPrefix}', 1, 1, 'y', NOW())"
);

// Find a valid leaf category
$db->fetch_array("SELECT id FROM mod_news_category WHERE lang = '{$testLang}' AND root = 'n' ORDER BY id ASC LIMIT 1");
$leafCatRows = $db->toArray();
$leafCatId = (int) ($leafCatRows[0]['id'] ?? 0);
if ($leafCatId === 0) {
    $catRes = callTool('create_news_category', [
        'name' => 'Challenger Seed Leaf Category',
        'lang' => $testLang,
    ], $plaintextKey);
    $leafCatId = (int) ($catRes['data']['id'] ?? 0);
}

// Find a root category (root = 'y')
$db->fetch_array("SELECT id, name FROM mod_news_category WHERE lang = '{$testLang}' AND root = 'y' ORDER BY id ASC LIMIT 1");
$rootCatRows = $db->toArray();
$rootCatId = (int) ($rootCatRows[0]['id'] ?? 0);
$rootCatName = (string) ($rootCatRows[0]['name'] ?? '');

echo "Test Environment Context:\n";
echo "  - Test Language: {$testLang}\n";
echo "  - Leaf Category ID: {$leafCatId}\n";
echo "  - Root Category ID: {$rootCatId} ({$rootCatName})\n\n";

// Track entities for teardown
$cleanupNewsIds = [];
$cleanupFaqIds = [];

// =========================================================================
// OBJECTIVE 1: Conjunctive Filtering Stress Test
// =========================================================================
echo "--- [TEST 1] Conjunctive Filtering Stress Test ---\n";

// Create News Article A
$newsARes = callTool('create_news', [
    'name' => 'Challenger Filter News Alpha',
    'lang' => $testLang,
    'category_id' => $leafCatId,
    'summary' => 'Diyarbakir genelinde yapilan yeni calismalar kapsaminda altyapi projelerinin ilerlemesi surduruluyor.',
    'content' => '<p>Altyapi projeleri sehir genelinde devam etmekte ve planlanan surecler takip edilmektedir.</p>',
    'seo' => 'challenger_filter_news_alpha',
    'status' => 'n',
], $plaintextKey);
$newsAId = (int) ($newsARes['data']['id'] ?? 0);
if ($newsAId > 0) $cleanupNewsIds[] = $newsAId;

// Create News Article B
$newsBRes = callTool('create_news', [
    'name' => 'Challenger Filter News Beta',
    'lang' => $testLang,
    'category_id' => $leafCatId,
    'summary' => 'Diyarbakir genelinde yapilan ikinci calisma ile kentsel donusum alanlari denetlenmektedir.',
    'content' => '<p>Kentsel donusum surecleri kapsaminda yeni tespitler ve degerlendirmeler yapilmaktadir.</p>',
    'seo' => 'challenger_filter_news_beta',
    'status' => 'n',
], $plaintextKey);
$newsBId = (int) ($newsBRes['data']['id'] ?? 0);
if ($newsBId > 0) $cleanupNewsIds[] = $newsBId;

stress_assert('Created News A and News B for filter testing', $newsAId > 0 && $newsBId > 0, "A: {$newsAId}, B: {$newsBId}");

// Create 5 distinct FAQs across mod_name, vid, lang, status, search keywords
// FAQ 1: mod_news, vid=A, lang=tr, status=y, question has `Karpuz`
$f1 = callTool('create_faq', [
    'mod_name' => 'mod_news',
    'vid' => $newsAId,
    'lang' => $testLang,
    'status' => 'y',
    'question' => 'Diyarbakir karpuz festivali ne zaman yapilacak?',
    'answer' => '<p>Festival her yil sonbahar basinda geleneksel olarak duzenlenmektedir.</p>',
], $plaintextKey);
$faq1Id = (int) ($f1['data']['id'] ?? 0);
if ($faq1Id > 0) $cleanupFaqIds[] = $faq1Id;

// FAQ 2: mod_news, vid=A, lang=tr, status=n (draft), question has `Sur`
$f2 = callTool('create_faq', [
    'mod_name' => 'mod_news',
    'vid' => $newsAId,
    'lang' => $testLang,
    'status' => 'n',
    'question' => 'Diyarbakir sur restorasyonlari ne durumda?',
    'answer' => '<p>Restorasyon calismalari planlanan etaplar halinde titizlikle surduruluyor.</p>',
], $plaintextKey);
$faq2Id = (int) ($f2['data']['id'] ?? 0);
if ($faq2Id > 0) $cleanupFaqIds[] = $faq2Id;

// FAQ 3: mod_news, vid=B, lang=tr, status=y, question has `OnGozlu`
$f3 = callTool('create_faq', [
    'mod_name' => 'mod_news',
    'vid' => $newsBId,
    'lang' => $testLang,
    'status' => 'y',
    'question' => 'Diyarbakir on gozlu kopru ulasimi nasil saglanir?',
    'answer' => '<p>Toplu tasima ve ozel araclarla Dicle vadisine ulasim saglanabilmektedir.</p>',
], $plaintextKey);
$faq3Id = (int) ($f3['data']['id'] ?? 0);
if ($faq3Id > 0) $cleanupFaqIds[] = $faq3Id;

// FAQ 4: mod_news, vid=B, lang=tr, status=n (draft), question has `Karpuz`
$f4 = callTool('create_faq', [
    'mod_name' => 'mod_news',
    'vid' => $newsBId,
    'lang' => $testLang,
    'status' => 'n',
    'question' => 'Diyarbakir karpuz yetistiriciligi nasil gelistirilir?',
    'answer' => '<p>Sulama ve tohum destekleme projeleri ile uretim verimi artirilmaktadir.</p>',
], $plaintextKey);
$faq4Id = (int) ($f4['data']['id'] ?? 0);
if ($faq4Id > 0) $cleanupFaqIds[] = $faq4Id;

// FAQ 5: mod_page, vid=8, lang=tr, status=y, question has `Karpuz`
$f5 = callTool('create_faq', [
    'mod_name' => 'mod_page',
    'vid' => 8,
    'lang' => $testLang,
    'status' => 'y',
    'question' => 'Diyarbakir karpuz bulteni ne zaman yayinlanir?',
    'answer' => '<p>Aylik bulten seklinde kamuoyuna basilip dagitilmaktadir.</p>',
], $plaintextKey);
$faq5Id = (int) ($f5['data']['id'] ?? 0);
if ($faq5Id > 0) $cleanupFaqIds[] = $faq5Id;

stress_assert('Created 5 controlled test FAQs', count($cleanupFaqIds) === 5, 'Created IDs: ' . implode(',', $cleanupFaqIds));

// Step 1: Base query with only mod_name = 'mod_news'
$qMod = callTool('list_faqs', ['mod_name' => 'mod_news', 'limit' => 100], $plaintextKey);
$cMod = (int) ($qMod['data']['total'] ?? 0);
$itemsMod = $qMod['data']['items'] ?? [];
$allMatchMod = true;
foreach ($itemsMod as $it) {
    if (($it['mod_name'] ?? '') !== 'mod_news') $allMatchMod = false;
}
stress_assert('list_faqs(mod_name=mod_news) filters strictly by mod_name', $cMod >= 4 && $allMatchMod, "Count: {$cMod}");

// Step 2: Add vid = $newsAId
$qModVid = callTool('list_faqs', ['mod_name' => 'mod_news', 'vid' => $newsAId, 'limit' => 100], $plaintextKey);
$cModVid = (int) ($qModVid['data']['total'] ?? 0);
$itemsModVid = $qModVid['data']['items'] ?? [];
$allMatchVid = true;
foreach ($itemsModVid as $it) {
    if (($it['mod_name'] ?? '') !== 'mod_news' || (int) ($it['vid'] ?? 0) !== $newsAId) $allMatchVid = false;
}
stress_assert(
    'list_faqs(mod_name, vid=A) narrows results and preserves mod_name condition',
    $cModVid <= $cMod && $cModVid === 2 && $allMatchVid,
    "Count: {$cModVid} (expected 2: faq1, faq2)"
);

// Step 3: Add status = 'y'
$qModVidStatus = callTool('list_faqs', ['mod_name' => 'mod_news', 'vid' => $newsAId, 'status' => 'y', 'limit' => 100], $plaintextKey);
$cModVidStatus = (int) ($qModVidStatus['data']['total'] ?? 0);
$itemsModVidStatus = $qModVidStatus['data']['items'] ?? [];
$allMatchStatus = true;
foreach ($itemsModVidStatus as $it) {
    if (($it['mod_name'] ?? '') !== 'mod_news' || (int) ($it['vid'] ?? 0) !== $newsAId || ($it['status'] ?? '') !== 'y') {
        $allMatchStatus = false;
    }
}
stress_assert(
    'list_faqs(mod_name, vid=A, status=y) narrows results and excludes draft status=n',
    $cModVidStatus <= $cModVid && $cModVidStatus === 1 && $allMatchStatus && (int) ($itemsModVidStatus[0]['id'] ?? 0) === $faq1Id,
    "Count: {$cModVidStatus} (expected 1: faq1)"
);

// Step 4: Add search = `Karpuz` (matches faq1)
$qSearchMatch = callTool('list_faqs', [
    'mod_name' => 'mod_news',
    'vid' => $newsAId,
    'status' => 'y',
    'search' => 'karpuz',
    'limit' => 100,
], $plaintextKey);
$cSearchMatch = (int) ($qSearchMatch['data']['total'] ?? 0);
stress_assert(
    'list_faqs(mod_name, vid=A, status=y, search=karpuz) returns the 1 matching item',
    $cSearchMatch === 1 && (int) ($qSearchMatch['data']['items'][0]['id'] ?? 0) === $faq1Id,
    "Count: {$cSearchMatch}"
);

// Step 5: Adversarial filter clash - search = `Sur` (exists in vid=A, but has status='n')
$qSearchClash = callTool('list_faqs', [
    'mod_name' => 'mod_news',
    'vid' => $newsAId,
    'status' => 'y',
    'search' => 'sur',
    'limit' => 100,
], $plaintextKey);
$cSearchClash = (int) ($qSearchClash['data']['total'] ?? 0);
stress_assert(
    'list_faqs(mod_name, vid=A, status=y, search=sur) returns 0 (status=y and search=sur are disjoint)',
    $cSearchClash === 0,
    "Count: {$cSearchClash} (if > 0, status=y was clobbered by search!)"
);

// Step 6: Adversarial filter clash - search = `OnGozlu` (exists in mod_news, but has vid=B)
$qSearchClashVid = callTool('list_faqs', [
    'mod_name' => 'mod_news',
    'vid' => $newsAId,
    'search' => 'gozlu',
    'limit' => 100,
], $plaintextKey);
$cSearchClashVid = (int) ($qSearchClashVid['data']['total'] ?? 0);
stress_assert(
    'list_faqs(mod_name, vid=A, search=gozlu) returns 0 (vid=A and search=gozlu are disjoint)',
    $cSearchClashVid === 0,
    "Count: {$cSearchClashVid} (if > 0, vid=A was clobbered by search!)"
);

// Step 7: Adversarial filter clash - mod_name = 'mod_page' with search = `Sur`
$qSearchClashMod = callTool('list_faqs', [
    'mod_name' => 'mod_page',
    'search' => 'sur',
    'limit' => 100,
], $plaintextKey);
$cSearchClashMod = (int) ($qSearchClashMod['data']['total'] ?? 0);
stress_assert(
    'list_faqs(mod_name=mod_page, search=sur) returns 0 (mod_page has no sur)',
    $cSearchClashMod === 0,
    "Count: {$cSearchClashMod} (if > 0, mod_name was clobbered by search!)"
);

echo "\n";

// =========================================================================
// OBJECTIVE 2: Draft FAQ Auditing on Draft News
// =========================================================================
echo "--- [TEST 2] Draft FAQ Auditing on Draft News ---\n";

// Create a standalone draft news article
$draftNewsRes = callTool('create_news', [
    'name' => 'Challenger Draft News For Audit',
    'lang' => $testLang,
    'category_id' => $leafCatId,
    'summary' => 'Diyarbakirda kultur sanat etkinlikleri cercevesinde yeni tiyatro sezonunun acilisi gerceklestirildi.',
    'content' => '<p>Kultur ve Turizm Bakanligi destekli yeni sezon oyunlari sahnelenmeye baslamistir.</p>',
    'seo' => 'challenger_draft_news_audit',
    'status' => 'n',
], $plaintextKey);
$draftNewsId = (int) ($draftNewsRes['data']['id'] ?? 0);
if ($draftNewsId > 0) $cleanupNewsIds[] = $draftNewsId;

stress_assert('Created draft news article (status=n)', $draftNewsId > 0, "ID: {$draftNewsId}");

// Initial get_news audit on empty draft news
$getNewsEmpty = callTool('get_news', ['id' => $draftNewsId], $plaintextKey);
$metaEmpty = $getNewsEmpty['data']['item'] ?? [];
stress_assert(
    'get_news on fresh draft news reports faq_count=0, faq_draft_count=0, faq_published_count=0',
    ($metaEmpty['faq_count'] ?? -1) === 0
    && ($metaEmpty['faq_draft_count'] ?? -1) === 0
    && ($metaEmpty['faq_published_count'] ?? -1) === 0
    && ($metaEmpty['status'] ?? '') === 'n',
    json_encode(['faq_count' => $metaEmpty['faq_count'] ?? null, 'status' => $metaEmpty['status'] ?? null])
);

// Create 1 draft FAQ attached to the draft news
$draftFaqRes = callTool('create_faq', [
    'mod_name' => 'mod_news',
    'vid' => $draftNewsId,
    'lang' => $testLang,
    'status' => 'n',
    'question' => 'Tiyatro biletleri nereden temin edilebilir?',
    'answer' => '<p>Biletler sehir tiyatrosu gisesinden ve cevrimici platformlardan temin edilebilir.</p>',
], $plaintextKey);
$draftFaqId = (int) ($draftFaqRes['data']['id'] ?? 0);
if ($draftFaqId > 0) $cleanupFaqIds[] = $draftFaqId;

stress_assert('Created draft FAQ (status=n) attached to draft news', $draftFaqId > 0, "FAQ ID: {$draftFaqId}");

// Call get_news and assert draft FAQ auditing
$getNewsWithDraft = callTool('get_news', ['id' => $draftNewsId], $plaintextKey);
$metaDraft = $getNewsWithDraft['data']['item'] ?? [];
stress_assert(
    'get_news on draft news reflects faq_count=1 and faq_draft_count=1 while status=n',
    ($metaDraft['status'] ?? '') === 'n'
    && ($metaDraft['faq_count'] ?? 0) === 1
    && ($metaDraft['faq_draft_count'] ?? 0) === 1
    && ($metaDraft['faq_published_count'] ?? 0) === 0
    && count($metaDraft['faq_items'] ?? []) === 1,
    json_encode([
        'status' => $metaDraft['status'] ?? null,
        'faq_count' => $metaDraft['faq_count'] ?? null,
        'faq_draft_count' => $metaDraft['faq_draft_count'] ?? null,
        'faq_published_count' => $metaDraft['faq_published_count'] ?? null,
    ])
);

// Add 1 published FAQ to the draft news
$pubFaqRes = callTool('create_faq', [
    'mod_name' => 'mod_news',
    'vid' => $draftNewsId,
    'lang' => $testLang,
    'status' => 'y',
    'question' => 'Oyunlar hangi gunler sahnelenmektedir?',
    'answer' => '<p>Her hafta cuma ve cumartesi aksamlari saat sekizde sahnelenmektedir.</p>',
], $plaintextKey);
$pubFaqId = (int) ($pubFaqRes['data']['id'] ?? 0);
if ($pubFaqId > 0) $cleanupFaqIds[] = $pubFaqId;

// Re-check get_news: should show total=2, published=1, draft=1
$getNewsWithBoth = callTool('get_news', ['id' => $draftNewsId], $plaintextKey);
$metaBoth = $getNewsWithBoth['data']['item'] ?? [];
stress_assert(
    'get_news on draft news correctly aggregates mixed published and draft FAQs (total=2, pub=1, draft=1)',
    ($metaBoth['faq_count'] ?? 0) === 2
    && ($metaBoth['faq_draft_count'] ?? 0) === 1
    && ($metaBoth['faq_published_count'] ?? 0) === 1,
    json_encode([
        'faq_count' => $metaBoth['faq_count'] ?? null,
        'draft' => $metaBoth['faq_draft_count'] ?? null,
        'pub' => $metaBoth['faq_published_count'] ?? null,
    ])
);

// Now change news status to 'y' (published) in DB directly, and verify that draft FAQs are suppressed
$db->query("UPDATE mod_news SET status = 'y' WHERE id = {$draftNewsId}");
$getNewsPubNews = callTool('get_news', ['id' => $draftNewsId], $plaintextKey);
$metaPubNews = $getNewsPubNews['data']['item'] ?? [];
stress_assert(
    'get_news on published news (status=y) suppresses draft FAQs from audit count (faq_count=1, draft=0, pub=1)',
    ($metaPubNews['status'] ?? '') === 'y'
    && ($metaPubNews['faq_count'] ?? 0) === 1
    && ($metaPubNews['faq_draft_count'] ?? 0) === 0
    && ($metaPubNews['faq_published_count'] ?? 0) === 1,
    json_encode([
        'status' => $metaPubNews['status'] ?? null,
        'faq_count' => $metaPubNews['faq_count'] ?? null,
        'draft' => $metaPubNews['faq_draft_count'] ?? null,
        'pub' => $metaPubNews['faq_published_count'] ?? null,
    ])
);

echo "\n";

// =========================================================================
// OBJECTIVE 3: Category Root Guard
// =========================================================================
echo "--- [TEST 3] Category Root Guard ---\n";

if ($rootCatId > 0) {
    // 3.1: create_news with root category
    $resRootCreate = callTool('create_news', [
        'name' => 'Challenger Root Guard Test News',
        'lang' => $testLang,
        'category_id' => $rootCatId,
        'summary' => 'Diyarbakir genelinde yapilan yeni calismalar kapsaminda altyapi projelerinin ilerlemesi surduruluyor.',
        'content' => '<p>Altyapi projeleri sehir genelinde devam etmekte ve planlanan surecler takip edilmektedir.</p>',
        'seo' => 'challenger_root_guard_test_slug',
    ], $plaintextKey);

    $errMsgCreate = $resRootCreate['text'];
    $hasStructuralNotice = stripos($errMsgCreate, 'structural root container') !== false;
    $hasChildHints = stripos($errMsgCreate, 'Selectable child categories') !== false || stripos($errMsgCreate, 'Please select a leaf category') !== false;

    stress_assert(
        'create_news with root category throws McpToolException (isError=true)',
        $resRootCreate['isError'] === true,
        $errMsgCreate
    );
    stress_assert(
        'create_news root category error contains actionable message and child hints',
        $hasStructuralNotice && $hasChildHints,
        $errMsgCreate
    );

    // 3.2: update_news changing category to root category
    if ($newsAId > 0) {
        $resRootUpdate = callTool('update_news', [
            'id' => $newsAId,
            'category_id' => $rootCatId,
        ], $plaintextKey);
        stress_assert(
            'update_news with root category is also rejected by assertSelectableNewsCategory',
            $resRootUpdate['isError'] === true && stripos($resRootUpdate['text'], 'structural root container') !== false,
            $resRootUpdate['text']
        );
    }
} else {
    stress_assert('Category Root Guard test skipped (no root=y category found)', false, 'No root category in DB');
}

// 3.3: Non-existent category ID
$resNonExistent = callTool('create_news', [
    'name' => 'Challenger Non Existent Cat News',
    'lang' => $testLang,
    'category_id' => 9999999,
    'summary' => 'Diyarbakir genelinde yapilan yeni calismalar kapsaminda altyapi projelerinin ilerlemesi surduruluyor.',
    'content' => '<p>Altyapi projeleri sehir genelinde devam etmekte ve planlanan surecler takip edilmektedir.</p>',
], $plaintextKey);
stress_assert(
    'create_news with non-existent category ID throws record not found error',
    $resNonExistent['isError'] === true && stripos($resNonExistent['text'], 'not found') !== false,
    $resNonExistent['text']
);

echo "\n";

// =========================================================================
// OBJECTIVE 4: Dirty SEO Slug Stress Test
// =========================================================================
echo "--- [TEST 4] Dirty SEO Slug Stress Test ---\n";

$seoTestCases = [
    'combining_dot' => [
        'input' => "I\u{0307}stanbul Haber Ku\u{0308}ltu\u{0308}r Sanat",
        'lang' => 'tr',
        'desc' => 'Combining dot above (U+0307) and decomposed diacritics',
    ],
    'spaces_and_quotes' => [
        'input' => "Diyarbakır Haber: Test / '123'",
        'lang' => 'tr',
        'desc' => 'Diverse punctuation: colons, quotes, slashes, spaces',
    ],
    'kurdish_typos' => [
        'input' => "P\u{0131}rs \u{0130}nternet \u{00dc}niversite",
        'lang' => 'ku',
        'desc' => 'Kurdish typography: Turkish dotless i and dotted I conversion',
    ],
    'dashes_and_whitespace' => [
        'input' => "  Son   Dakika  ---  Gelişmesi  ---  2026  ",
        'lang' => 'tr',
        'desc' => 'Multiple consecutive dashes and leading/trailing whitespace',
    ],
];

foreach ($seoTestCases as $key => $tc) {
    $computedSeo = McpCrudToolsBase::makeSeo($tc['input'], $tc['lang']);

    // Check invariants
    $hasRawSpaces = str_contains($computedSeo, ' ');
    $hasCombiningDot = str_contains($computedSeo, "\u{0307}");
    $hasRepeatedUnderscores = str_contains($computedSeo, '__');
    $hasLeadingTrailingUnderscore = str_starts_with($computedSeo, '_') || str_ends_with($computedSeo, '_');
    $hasSlash = str_contains($computedSeo, '/');

    stress_assert(
        "makeSeo [{$key}] ({$tc['desc']}): no raw spaces",
        !$hasRawSpaces,
        "Result: '{$computedSeo}'"
    );
    stress_assert(
        "makeSeo [{$key}]: no combining dot U+0307",
        !$hasCombiningDot,
        "Result: '{$computedSeo}'"
    );
    stress_assert(
        "makeSeo [{$key}]: no repeated underscores",
        !$hasRepeatedUnderscores,
        "Result: '{$computedSeo}'"
    );
    stress_assert(
        "makeSeo [{$key}]: no leading or trailing underscores",
        !$hasLeadingTrailingUnderscore,
        "Result: '{$computedSeo}'"
    );

    // Adversarial verification: forward slash
    if ($key === 'spaces_and_quotes') {
        stress_assert(
            "makeSeo [{$key}]: forward slash '/' stripped from URL slug",
            !$hasSlash,
            "Result: '{$computedSeo}' contains literal '/' which breaks web routing if unencoded!"
        );
    }
}

// Empirical create_news invocation with dirty SEO slug
$dirtyExplicitSeo = "Diyarbakır Haber: Test / '123'";
$resDirtyCreate = callTool('create_news', [
    'name' => 'Challenger Dirty SEO Article Title',
    'lang' => $testLang,
    'category_id' => $leafCatId,
    'summary' => 'Diyarbakirda yapilan calismalar kapsaminda kultur ve sanat faaliyetleri basariyla surdurulmektedir.',
    'content' => '<p>Kultur ve sanat calismalari tum hiziyla devam etmektedir.</p>',
    'seo' => $dirtyExplicitSeo,
    'status' => 'n',
], $plaintextKey);

$dirtyCreatedId = (int) ($resDirtyCreate['data']['id'] ?? 0);
if ($dirtyCreatedId > 0) $cleanupNewsIds[] = $dirtyCreatedId;
$storedSeo = (string) ($resDirtyCreate['data']['item']['seo'] ?? '');

stress_assert(
    'create_news accepts dirty SEO input and normalizes via makeSeo',
    $resDirtyCreate['isError'] === false && $dirtyCreatedId > 0,
    "ID: {$dirtyCreatedId}"
);
stress_assert(
    'create_news stored SEO slug contains no raw spaces',
    !str_contains($storedSeo, ' '),
    "Stored SEO: '{$storedSeo}'"
);

echo "\n";

// =========================================================================
// OBJECTIVE 5: batch_create_faqs Transaction Integrity & Rollback
// =========================================================================
echo "--- [TEST 5] batch_create_faqs Transaction Integrity & Rollback ---\n";

if ($newsAId > 0) {
    // 5.1: Count existing FAQs for newsA
    $db->fetch_array("SELECT id FROM mod_faq WHERE mod_name = 'mod_news' AND vid = {$newsAId}");
    $initialFaqCount = count($db->toArray());

    // Test 5.1: 1 Valid item + 1 Invalid item (cross-language Kurdish contamination in item 1 on Turkish entity)
    $resBatchInvalidLang = callTool('batch_create_faqs', [
        'mod_name' => 'mod_news',
        'vid' => $newsAId,
        'lang' => $testLang,
        'status' => 'n',
        'items' => [
            [
                'question' => 'Gecerli Turkce Soru: Proje ne zaman basliyor?',
                'answer' => '<p>Proje onumuzdeki ay resmi olarak baslayacaktir.</p>',
            ],
            [
                'question' => 'Ev pirs ji bo çi hatiye amadekirin li ser vê xebatê?',
                'answer' => '<p>Ev bersiv ji bo agahdariya giştî hatiye amadekirin û tê parvekirin.</p>',
            ],
        ],
    ], $plaintextKey);

    stress_assert(
        'batch_create_faqs with cross-language item throws McpToolException (isError=true)',
        $resBatchInvalidLang['isError'] === true && stripos($resBatchInvalidLang['text'], 'Content language mismatch') !== false,
        $resBatchInvalidLang['text']
    );

    // Verify database count has not changed by even 1 row
    $db->fetch_array("SELECT id FROM mod_faq WHERE mod_name = 'mod_news' AND vid = {$newsAId}");
    $afterFaqCount = count($db->toArray());
    stress_assert(
        'batch_create_faqs committed ZERO items on language validation error (database intact)',
        $afterFaqCount === $initialFaqCount,
        "Before: {$initialFaqCount}, After: {$afterFaqCount}"
    );

    // Test 5.2: Within-batch duplicate question validation
    $resBatchDuplicate = callTool('batch_create_faqs', [
        'mod_name' => 'mod_news',
        'vid' => $newsAId,
        'lang' => $testLang,
        'status' => 'n',
        'items' => [
            [
                'question' => 'Ayni Soru Metni: Proje butcesi ne kadardir?',
                'answer' => '<p>Cevap A: Butce belediye tarafindan karsilanmaktadir.</p>',
            ],
            [
                'question' => 'ayni soru metni: proje butcesi ne kadardir?',
                'answer' => '<p>Cevap B: Farkli cevap metni fakat ayni soru.</p>',
            ],
        ],
    ], $plaintextKey);

    stress_assert(
        'batch_create_faqs rejects intra-batch duplicate questions (case-insensitive)',
        $resBatchDuplicate['isError'] === true && stripos($resBatchDuplicate['text'], 'Duplicate question within batch') !== false,
        $resBatchDuplicate['text']
    );

    $db->fetch_array("SELECT id FROM mod_faq WHERE mod_name = 'mod_news' AND vid = {$newsAId}");
    $afterDuplicateCount = count($db->toArray());
    stress_assert(
        'batch_create_faqs committed ZERO items on intra-batch duplicate error',
        $afterDuplicateCount === $initialFaqCount,
        "Before: {$initialFaqCount}, After: {$afterDuplicateCount}"
    );

    // Test 5.3: Empty question or answer
    $resBatchEmpty = callTool('batch_create_faqs', [
        'mod_name' => 'mod_news',
        'vid' => $newsAId,
        'lang' => $testLang,
        'status' => 'n',
        'items' => [
            [
                'question' => 'Gecerli Soru: Proje alani neresidir?',
                'answer' => '<p>Proje alani merkez ilcedir.</p>',
            ],
            [
                'question' => '',
                'answer' => '<p>Cevap var fakat soru bos.</p>',
            ],
        ],
    ], $plaintextKey);

    stress_assert(
        'batch_create_faqs rejects empty question in batch',
        $resBatchEmpty['isError'] === true,
        $resBatchEmpty['text']
    );

    $db->fetch_array("SELECT id FROM mod_faq WHERE mod_name = 'mod_news' AND vid = {$newsAId}");
    $afterEmptyCount = count($db->toArray());
    stress_assert(
        'batch_create_faqs committed ZERO items on empty question error',
        $afterEmptyCount === $initialFaqCount,
        "Before: {$initialFaqCount}, After: {$afterEmptyCount}"
    );

    // Test 5.4: Successful atomic batch creation with 3 items
    $resBatchSuccess = callTool('batch_create_faqs', [
        'mod_name' => 'mod_news',
        'vid' => $newsAId,
        'lang' => $testLang,
        'status' => 'n',
        'items' => [
            [
                'question' => 'Atomik Soru Bir: Birinci test sorusu nedir?',
                'answer' => '<p>Birinci atomik soru icin hazirlanmis yanittir.</p>',
            ],
            [
                'question' => 'Atomik Soru Iki: Ikinci test sorusu nedir?',
                'answer' => '<p>Ikinci atomik soru icin hazirlanmis yanittir.</p>',
            ],
            [
                'question' => 'Atomik Soru Uc: Ucuncu test sorusu nedir?',
                'answer' => '<p>Ucuncu atomik soru icin hazirlanmis yanittir.</p>',
            ],
        ],
    ], $plaintextKey);

    $batchSuccessData = $resBatchSuccess['data'] ?? [];
    $createdBatchIds = $batchSuccessData['ids'] ?? [];
    foreach ($createdBatchIds as $createdBatchId) {
        $cleanupFaqIds[] = (int) $createdBatchId;
    }

    stress_assert(
        'batch_create_faqs atomically created all 3 valid items',
        $resBatchSuccess['isError'] === false
        && ($batchSuccessData['created_count'] ?? 0) === 3
        && count($createdBatchIds) === 3,
        json_encode($batchSuccessData)
    );

    $db->fetch_array("SELECT id FROM mod_faq WHERE mod_name = 'mod_news' AND vid = {$newsAId}");
    $afterSuccessCount = count($db->toArray());
    stress_assert(
        'Database count increased by exactly 3 after successful batch creation',
        $afterSuccessCount === $initialFaqCount + 3,
        "Expected: " . ($initialFaqCount + 3) . ", Actual: {$afterSuccessCount}"
    );

    // Test 5.5: replace_existing_drafts functionality
    $resReplaceDrafts = callTool('batch_create_faqs', [
        'mod_name' => 'mod_news',
        'vid' => $newsAId,
        'lang' => $testLang,
        'status' => 'n',
        'replace_existing_drafts' => true,
        'items' => [
            [
                'question' => 'Yenilenmis Soru Tek: Butun taslaklar yenilendi mi?',
                'answer' => '<p>Evet, onceki taslaklar temizlendi ve sadece bu soru kaydedildi.</p>',
            ],
        ],
    ], $plaintextKey);

    $replaceData = $resReplaceDrafts['data'] ?? [];
    $replaceIds = $replaceData['ids'] ?? [];
    foreach ($replaceIds as $rid) {
        $cleanupFaqIds[] = (int) $rid;
    }

    stress_assert(
        'batch_create_faqs with replace_existing_drafts=true succeeded',
        $resReplaceDrafts['isError'] === false && ($replaceData['created_count'] ?? 0) === 1,
        json_encode($replaceData)
    );

    // Check that previous draft questions were deleted but published questions remain
    $db->fetch_array("SELECT id, status, question FROM mod_faq WHERE mod_name = 'mod_news' AND vid = {$newsAId}");
    $remainingFaqs = $db->toArray();
    $pubCount = 0;
    $draftCount = 0;
    foreach ($remainingFaqs as $rf) {
        if (($rf['status'] ?? '') === 'y') $pubCount++;
        if (($rf['status'] ?? '') === 'n') $draftCount++;
    }
    stress_assert(
        'replace_existing_drafts purged older drafts and left exactly 1 new draft while preserving published FAQs',
        $draftCount === 1 && $pubCount >= 1,
        "Published: {$pubCount}, Draft: {$draftCount}"
    );
}

echo "\n";

// =========================================================================
// CLEANUP & TEARDOWN
// =========================================================================
echo "--- Teardown & Cleanup ---\n";
foreach ($cleanupNewsIds as $cleanupNewsId) {
    $db->query("DELETE FROM mod_news WHERE id = {$cleanupNewsId}");
}
foreach ($cleanupFaqIds as $cleanupFaqId) {
    $db->query("DELETE FROM mod_faq WHERE id = {$cleanupFaqId}");
}
$db->query("DELETE FROM mod_mcp_api_key WHERE name = 'Challenger test key'");

echo "Cleaned up " . count($cleanupNewsIds) . " news articles and " . count($cleanupFaqIds) . " FAQs.\n\n";

echo "=================================================================\n";
echo "CHALLENGER SUMMARY: {$passCount} passed, {$failCount} failed.\n";
if (!empty($findings)) {
    echo "\nFAILURES / FINDINGS ENCOUNTERED:\n";
    foreach ($findings as $idx => $f) {
        echo "  " . ($idx + 1) . ". {$f}\n";
    }
}
echo "=================================================================\n";

exit($failCount > 0 ? 1 : 0);
