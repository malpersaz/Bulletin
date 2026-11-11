# Handoff Report: Milestone 1 — Features 5 through 11 Investigation

- **Author**: Explorer M1-2 (`teamwork_explorer_m1_2`)
- **Recipient**: Sub-Orchestrator M1 (`sub_orch_m1`, conversation ID `901f7801-31c7-4c08-be51-94ed6f17c21d`)
- **Scope**: Features 5, 6, 7, 8, 9, 10, 11 (McpNewsTools & McpFaqTools Enhancements)
- **Status**: Investigation Complete — Ready for Implementation

---

## 1. Observation

### Feature 5: `McpNewsTools` Root Category Check Bypass
- **File**: `lib/McpNewsTools.class.php`
- **Lines**: 707–711 (`createNews`), 868–873 (`updateNews`)
- **Direct Code Quote**:
  ```php
  // lib/McpNewsTools.class.php:707-711
  $categoryId = (int) ($args['category_id'] ?? 0);
  if ($categoryId <= 0) {
      throw new McpToolException("Field 'category_id' is required.");
  }
  $this->assertRecordMatchesLang('mod_news_category', $categoryId, $lang, 'Category');
  ```
  ```php
  // lib/McpNewsTools.class.php:868-873
  $categoryId = isset($args['category_id']) ? (int) $args['category_id'] : (int) $model->category_id;

  if ($categoryId !== (int) $model->category_id || $lang !== (string) $model->lang) {
      $this->assertRecordMatchesLang('mod_news_category', $categoryId, $lang, 'Category');
  }
  ```
- **Admin Validator Contrast**:
  In `lib/Traits/ModuleValidatorTrait.php:77–88` (used by `mod/mod_news/validator/add.validator.php:273` via `validateCategoryRelation()`):
  ```php
  $catModel->sync();

  if (isset($catModel->root) && $catModel->root === 'y') {
      $msg = $this->lang[$mod]['validator']['category_root_forbidden'] 
          ?? ($this->lang['validator']['category_root_forbidden'] ?? ($this->lang['management']['error'] ?? ''));
      $this->appendError($msg);
      return false;
  }
  ```
- **Database Schema**:
  In `mod/mod_news_category/mod_news_category.sql:7`:
  `root ENUM('y', 'n') NOT NULL DEFAULT 'n'`
  When `root === 'y'`, the category acts purely as a navigation parent container. Frontends (`mod/index/helper/mod_news.helper.php:139`) query `where("... root='n'")` and the admin news form (`mod/mod_news/index.php:83`) filters out root categories via `$mod_news_category->addWhere("root != 'y'")`. News assigned to a `root = 'y'` category becomes unreachable on the website.

---

### Feature 6: `McpNewsTools` Mandatory `makeSeo()` Normalization
- **File**: `lib/McpNewsTools.class.php`
- **Lines**: 725–734 (`createNews`), 894–906 (`updateNews`)
- **Direct Code Quote**:
  ```php
  // lib/McpNewsTools.class.php:725-728
  $seo = trim((string) ($args['seo'] ?? ''));
  if ($seo === '') {
      $seo = self::makeSeo($name, $lang);
  }
  if ($seo === '' || mb_strlen($seo) > 255) {
      throw new McpToolException("Field 'seo' must be 1-255 characters.");
  }
  if ($this->recordSeoExists('mod_news', $seo, $lang)) {
      throw new McpToolException("Field 'seo' must be unique per language: '{$seo}' is already used.");
  }
  ```
  ```php
  // lib/McpNewsTools.class.php:894-898
  if (isset($args['seo'])) {
      $seo = trim((string) $args['seo']);
      if ($seo === '') {
          $seo = self::makeSeo((string) $model->name, $lang);
      }
      if ($seo === '' || mb_strlen($seo) > 255) {
          throw new McpToolException("Field 'seo' must be 1-255 characters.");
      }
      if ($this->recordSeoExists('mod_news', $seo, $lang, $id)) {
          throw new McpToolException("Field 'seo' must be unique per language: '{$seo}' is already used.");
      }
      $model->seo = $seo;
  }
  ```
- **Observed Defect**:
  When an explicit `seo` parameter is provided (e.g. `'Diyarbakır Belediyesi: Yeni Karar'` or containing spaces, colons, quotes, combining `\u{0307}`, or hyphens), `McpNewsTools` skips `self::makeSeo()` entirely and saves unnormalized characters directly into `mod_news.seo`. This violates the Wikipedia-style URL contract (`AGENTS.md` Rule 9) and breaks clean URL routing.

---

### Feature 7: `McpNewsTools::getNews` Envelope & Author/Category Enrichment
- **File**: `lib/McpNewsTools.class.php`
- **Lines**: 670–686
- **Direct Code Quote**:
  ```php
  public function getNews(array $args): array
  {
      $model = $this->loadRecordByIdOrSeo('mod_news', $args, 'News');

      $row = $model->toArray();
      if (!is_array($row) || !isset($row['id'])) {
          throw new McpToolException('News record not found.');
      }

      $auditMeta = $this->getRecordAuditMeta('mod_news', (int) $row['id'], (string) ($row['lang'] ?? 'tr'));
      $row['lang_status'] = $auditMeta['lang_status'];
      $row['faq_count'] = $auditMeta['faq_count'];
      $row['faq_items'] = $auditMeta['faq_items'];

      $row['item'] = $row;
      return $row;
  }
  ```
- **Observed Defects**:
  1. `getNews` loads strictly from `mod_news` and does not enrich the row with `username`, `author_fullname` (from `mod_user`), `category_name`, or `category_seo` (from `mod_news_category`). Downstream verifiers (`audit_content_seo.py`, `@verifier`) cannot confirm author attribution (`habermerkezi`) or category assignment from `get_news` alone.
  2. `getRecordAuditMeta` is called without passing the record's `$row['status']`. By default, `getRecordAuditMeta` filters by `AND status = 'y'`, making draft FAQs staged on draft news items invisible (`faq_count = 0`).
  3. `$row['item'] = $row` creates a self-nested duplicate key inside the returned payload instead of capturing a clean snapshot after enrichments.

---

### Feature 8: Duplicate News Staging Precheck
- **File**: `lib/McpNewsTools.class.php`
- **Lines**: 28–260 (tool declarations), 730–735 (`createNews`)
- **Direct Code Quote**:
  `McpNewsTools` currently checks only exact `(seo, lang)` collisions:
  ```php
  if ($this->recordSeoExists('mod_news', $seo, $lang)) {
      throw new McpToolException("Field 'seo' must be unique per language: '{$seo}' is already used.");
  }
  ```
- **Observed Defect**:
  There is no headline similarity check or dedicated pre-staging tool. Two independent agents reporting on the same event with slightly varying titles will stage duplicate draft stories within 48 hours without warning.

---

### Feature 9: `McpFaqTools::batchCreateFaqs`
- **File**: `lib/McpFaqTools.class.php`
- **Lines**: 98–162 (`create_faq` tool definition), 299–341 (`createFaq` handler)
- **Direct Code Quote**:
  Currently, `create_faq` only creates one FAQ per call:
  ```php
  public function createFaq(array $args): array
  ```
  Staging 6 FAQs for news article `#128` required 6 round-trips. By comparison, `lib/McpMenuTools.class.php:331, 903–934` (`batchUpdateMenuItems`) demonstrates atomic batching within a `Database::start_transaction()` block.

---

### Feature 10: `McpFaqTools::listFaqModules`
- **File**: `lib/McpFaqTools.class.php`
- **Lines**: 18–244 (definitions), 474–477 (`assertEntity`)
- **Direct Code Quote**:
  ```php
  $bridgeModel = $this->freshModel('mod_faq_mod');
  if ($bridgeModel->exists_mod_name($modName) !== true) {
      throw new McpToolException("Module '{$modName}' is not registered for FAQ.");
  }
  ```
- **Observed Defect**:
  `McpFaqTools` validates against `mod_faq_mod`, but provides no discovery tool. In contrast, `lib/McpGalleryTools.class.php:247–281, 636–661` provides `list_gallery_modules` querying `mod_gallery_mod`. External callers must guess which modules are valid FAQ hosts.

---

### Feature 11: `McpFaqTools::listFaqs` `include_answer: bool`
- **File**: `lib/McpFaqTools.class.php`
- **Lines**: 23, 260–264
- **Direct Code Quote**:
  ```php
  // lib/McpFaqTools.class.php:260-264
  $model = $this->freshModel(self::FAQ_MODULE);
  $model->select('id, lang, mod_name, vid, question, sort_order, status, created_at, updated_at');
  $this->applyFaqFilters($model, $args);
  ```
- **Observed Defect**:
  `listFaqs` omits `answer` from the select projection. Verifying 6 staged FAQ answers requires 1 `list_faqs` call plus 6 individual `get_faq` calls (7 round-trips).

---

## 2. Logic Chain

```
[Observation 5.1: createNews & updateNews only call assertRecordMatchesLang]
       │
       ▼
[Logic Step 5.2: assertRecordMatchesLang verifies record existence and lang, but ignores $record->root]
       │
       ▼
[Logic Step 5.3: Category with root === 'y' is a navigation container filtered out by frontend & admin]
       │
       ▼
[Conclusion 5.4: Implement assertSelectableNewsCategory() helper checking root !== 'y' and listing child categories]
```

```
[Observation 6.1: $seo = self::makeSeo($name, $lang) only executes when $seo === '']
       │
       ▼
[Logic Step 6.2: Explicit $seo containing spaces, colons, Kurdish characters, or U+0307 enters raw]
       │
       ▼
[Logic Step 6.3: McpCrudToolsBase::makeSeo() already handles NFC, Kurdish characters, U+0307 strip, and Wikipedia style]
       │
       ▼
[Conclusion 6.4: Unconditionally pipe explicit $seo through self::makeSeo($rawSeo !== '' ? $rawSeo : $name, $lang)]
```

```
[Observation 7.1: getNews only loads mod_news, lacks username, author_fullname, category_name, category_seo]
       │
       ▼
[Logic Step 7.2: mod_user holds username, name, last_name; mod_news_category holds name, seo]
       │
       ▼
[Logic Step 7.3: getRecordAuditMeta default $recordStatus = 'y' excludes draft FAQs on draft news]
       │
       ▼
[Conclusion 7.4: Enrich getNews with author/category lookups, pass $row['status'] to getRecordAuditMeta, set $row['item']]
```

```
[Observation 8.1: No duplicate check exists beyond exact (seo, lang) match in mod_news]
       │
       ▼
[Logic Step 8.2: 48-hour window query against mod_news with Jaccard index (threshold >= 0.55) catches near-duplicates]
       │
       ▼
[Conclusion 8.3: Add check_duplicate_news tool and attach duplicate_warnings to createNews response]
```

```
[Observation 9.1: Staging FAQs one-by-one requires N sequential calls and lacks atomic rollback]
       │
       ▼
[Logic Step 9.2: batch_create_faqs can pre-validate up to 25 items and insert within a Database transaction]
       │
       ▼
[Conclusion 9.3: Add batch_create_faqs with replace_existing_drafts, intra-batch deduplication, and atomic commit]
```

```
[Observation 10.1: McpGalleryTools has list_gallery_modules, but McpFaqTools has no counterpart for mod_faq_mod]
       │
       ▼
[Logic Step 10.2: mod_faq_mod table contains id, name, mod_name, mod_key, mod_value]
       │
       ▼
[Conclusion 10.3: Add list_faq_modules tool querying mod_faq_mod with search and pagination]
```

```
[Observation 11.1: listFaqs hardcodes select without answer, forcing N additional get_faq calls]
       │
       ▼
[Logic Step 11.2: Adding optional include_answer: bool appends ', answer' to select query when requested]
       │
       ▼
[Conclusion 11.3: Update list_faqs schema and listFaqs handler to conditionally include answer column]
```

---

## 3. Concrete Fix Proposals (Diff Specifications)

### Feature 5 & Feature 6: Changes to `lib/McpNewsTools.class.php`

```diff
--- a/lib/McpNewsTools.class.php
+++ b/lib/McpNewsTools.class.php
@@ -707,7 +707,7 @@ class McpNewsTools extends McpCrudToolsBase
         $categoryId = (int) ($args['category_id'] ?? 0);
         if ($categoryId <= 0) {
             throw new McpToolException("Field 'category_id' is required.");
         }
-        $this->assertRecordMatchesLang('mod_news_category', $categoryId, $lang, 'Category');
+        $this->assertSelectableNewsCategory($categoryId, $lang);
 
         $content = $this->sanitizeBodyHtml((string) ($args['content'] ?? ''));
@@ -724,9 +724,7 @@ class McpNewsTools extends McpCrudToolsBase
         $this->assertContentMatchesLang($lang, $name, $summary, $content);
 
-        $seo = trim((string) ($args['seo'] ?? ''));
-        if ($seo === '') {
-            $seo = self::makeSeo($name, $lang);
-        }
+        $rawSeo = trim((string) ($args['seo'] ?? ''));
+        $seo = self::makeSeo($rawSeo !== '' ? $rawSeo : $name, $lang);
         if ($seo === '' || mb_strlen($seo) > 255) {
             throw new McpToolException("Field 'seo' must be 1-255 characters.");
@@ -868,7 +866,7 @@ class McpNewsTools extends McpCrudToolsBase
         $categoryId = isset($args['category_id']) ? (int) $args['category_id'] : (int) $model->category_id;
 
         if ($categoryId !== (int) $model->category_id || $lang !== (string) $model->lang) {
-            $this->assertRecordMatchesLang('mod_news_category', $categoryId, $lang, 'Category');
+            $this->assertSelectableNewsCategory($categoryId, $lang);
         }
 
         if (isset($args['name'])) {
@@ -894,9 +892,7 @@ class McpNewsTools extends McpCrudToolsBase
         if (isset($args['seo'])) {
-            $seo = trim((string) $args['seo']);
-            if ($seo === '') {
-                $seo = self::makeSeo((string) $model->name, $lang);
-            }
+            $rawSeo = trim((string) $args['seo']);
+            $seo = self::makeSeo($rawSeo !== '' ? $rawSeo : (string) $model->name, $lang);
             if ($seo === '' || mb_strlen($seo) > 255) {
                 throw new McpToolException("Field 'seo' must be 1-255 characters.");
             }
```

#### New Helper in `lib/McpNewsTools.class.php`:
```php
    /**
     * Asserts that a category exists, matches content language, and is not a root container
     */
    protected function assertSelectableNewsCategory(int $categoryId, string $lang): object
    {
        $category = $this->assertRecordMatchesLang('mod_news_category', $categoryId, $lang, 'Category');
        if (((string) ($category->root ?? 'n')) === 'y') {
            $childModel = $this->freshModel('mod_news_category');
            $childModel->where_parent_id_and_lang($categoryId, $lang);
            $childModel->addWhere("root != 'y'");
            $childModel->order('order', 'ASC')->all();
            $children = $childModel->toArray();
            $childHints = [];
            if (is_array($children)) {
                foreach ($children as $c) {
                    if (isset($c['id'], $c['name'])) {
                        $childHints[] = "ID {$c['id']} ({$c['name']})";
                    }
                }
            }
            $hintStr = !empty($childHints)
                ? ' Selectable child categories: ' . implode(', ', $childHints) . '.'
                : ' Please select a leaf category (root != \'y\').';
            throw new McpToolException("Category '{$categoryId}' ('{$category->name}') is a structural root container (root='y') and cannot contain news directly.{$hintStr}");
        }
        return $category;
    }
```
*Note: In `lib/McpCrudToolsBase.class.php:263`, update `assertRecordMatchesLang` return signature from `: void` to `: object` returning `$record`.*

---

### Feature 7: Changes to `McpNewsTools::getNews`

```diff
--- a/lib/McpNewsTools.class.php
+++ b/lib/McpNewsTools.class.php
@@ -674,13 +674,43 @@ class McpNewsTools extends McpCrudToolsBase
         if (!is_array($row) || !isset($row['id'])) {
             throw new McpToolException('News record not found.');
         }
 
-        $auditMeta = $this->getRecordAuditMeta('mod_news', (int) $row['id'], (string) ($row['lang'] ?? 'tr'));
+        $auditMeta = $this->getRecordAuditMeta('mod_news', (int) $row['id'], (string) ($row['lang'] ?? 'tr'), (string) ($row['status'] ?? 'y'));
         $row['lang_status'] = $auditMeta['lang_status'];
         $row['faq_count'] = $auditMeta['faq_count'];
         $row['faq_items'] = $auditMeta['faq_items'];
+        $row['faq_published_count'] = $auditMeta['faq_published_count'] ?? $auditMeta['faq_count'];
+        $row['faq_draft_count'] = $auditMeta['faq_draft_count'] ?? 0;
+
+        // Author attribution enrichment from mod_user
+        $userId = (int) ($row['user_id'] ?? 0);
+        $row['username'] = '';
+        $row['author_fullname'] = '';
+        if ($userId > 0) {
+            $db = Load::lib('Database');
+            $db->fetch_array("SELECT username, name, last_name FROM mod_user WHERE id = {$userId} LIMIT 1");
+            if ($db->getSize() > 0) {
+                $uRow = $db->getResultOne()->toArray();
+                $row['username'] = (string) ($uRow['username'] ?? '');
+                $fullName = trim(((string) ($uRow['name'] ?? '')) . ' ' . ((string) ($uRow['last_name'] ?? '')));
+                $row['author_fullname'] = ($fullName !== '') ? $fullName : $row['username'];
+            }
+        }
+
+        // Category enrichment from mod_news_category
+        $categoryId = (int) ($row['category_id'] ?? 0);
+        $row['category_name'] = '';
+        $row['category_seo'] = '';
+        if ($categoryId > 0) {
+            $catModel = $this->freshModel('mod_news_category');
+            $catModel->where_id($categoryId)->limit(1)->getResultOne(true, true);
+            if ($catModel->getSize() > 0) {
+                $catRow = $catModel->toArray();
+                $row['category_name'] = (string) ($catRow['name'] ?? '');
+                $row['category_seo'] = (string) ($catRow['seo'] ?? '');
+            }
+        }
 
-        $row['item'] = $row;
+        $item = $row;
+        $row['item'] = $item;
         return $row;
     }
```

---

### Feature 8: Changes for `checkDuplicateNews` in `lib/McpNewsTools.class.php`

#### Tool Definition in `McpNewsTools::definitions()`:
```php
            'check_duplicate_news' => [
                'module' => 'mod_news',
                'role' => 'list',
                'domain' => 'news',
                'handler' => 'checkDuplicateNews',
                'description' => 'Pre-staging duplicate check within the last 48 hours for a given language. Compares headline token similarity (Jaccard index >= threshold) and exact SEO slug matches.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => [
                            'type' => 'string',
                            'description' => 'Proposed news headline to check against recent stories.',
                        ],
                        'lang' => [
                            'type' => 'string',
                            'description' => 'Language code of active site language (e.g. tr, ku, en).',
                        ],
                        'seo' => [
                            'type' => 'string',
                            'description' => 'Optional proposed SEO slug to check for exact slug collision.',
                        ],
                        'hours' => [
                            'type' => 'integer',
                            'default' => 48,
                            'minimum' => 1,
                            'maximum' => 168,
                            'description' => 'Lookback window in hours (default 48).',
                        ],
                        'threshold' => [
                            'type' => 'number',
                            'default' => 0.55,
                            'minimum' => 0.1,
                            'maximum' => 1.0,
                            'description' => 'Jaccard similarity threshold for headline tokens (default 0.55).',
                        ],
                        'exclude_id' => [
                            'type' => 'integer',
                            'description' => 'Optional news ID to exclude from comparison (e.g. when updating).',
                        ],
                    ],
                    'required' => [
                        'name',
                        'lang',
                    ],
                ],
                'title' => 'Check Duplicate News',
                'annotations' => [
                    'readOnlyHint' => true,
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                ],
            ],
```

#### Handler and Tokenizer Implementation:
```php
    public function checkDuplicateNews(array $args): array
    {
        $name = $this->cleanTextEntities($args['name'] ?? '');
        if ($name === '') {
            throw new McpToolException("Field 'name' is required.");
        }
        $lang = $this->requireActiveLang($args);
        $hours = isset($args['hours']) ? max(1, min(168, (int) $args['hours'])) : 48;
        $threshold = isset($args['threshold']) ? max(0.1, min(1.0, (float) $args['threshold'])) : 0.55;
        $excludeId = isset($args['exclude_id']) ? (int) $args['exclude_id'] : 0;

        $rawSeo = trim((string) ($args['seo'] ?? ''));
        $proposedSeo = self::makeSeo($rawSeo !== '' ? $rawSeo : $name, $lang);

        $cutoff = date('Y-m-d H:i:s', time() - ($hours * 3600));

        $db = Load::lib('Database');
        $safeLang = Obuilder::cc($lang);
        $safeCutoff = Obuilder::cc($cutoff);
        $excludeSql = $excludeId > 0 ? " AND id != {$excludeId}" : '';

        $db->fetch_array("SELECT id, name, seo, status, category_id, created_at FROM mod_news WHERE lang = '{$safeLang}' AND created_at >= '{$safeCutoff}'{$excludeSql} ORDER BY id DESC");
        $candidates = $db->toArray();

        $proposedTokens = $this->tokenizeHeadline($name);

        $duplicates = [];
        if (is_array($candidates)) {
            foreach ($candidates as $cand) {
                $candName = (string) ($cand['name'] ?? '');
                $candSeo = (string) ($cand['seo'] ?? '');
                $candTokens = $this->tokenizeHeadline($candName);

                $intersection = count(array_intersect($proposedTokens, $candTokens));
                $union = count(array_unique(array_merge($proposedTokens, $candTokens)));
                $similarity = $union > 0 ? ($intersection / $union) : 0.0;

                $isSeoMatch = ($proposedSeo !== '' && $candSeo === $proposedSeo);
                $isTitleMatch = ($similarity >= $threshold);

                if ($isSeoMatch || $isTitleMatch) {
                    $reasons = [];
                    if ($isSeoMatch) {
                        $reasons[] = 'exact_seo_match';
                    }
                    if ($isTitleMatch) {
                        $reasons[] = 'title_similarity';
                    }
                    $duplicates[] = [
                        'id' => (int) $cand['id'],
                        'name' => $candName,
                        'seo' => $candSeo,
                        'status' => (string) ($cand['status'] ?? 'y'),
                        'category_id' => (int) ($cand['category_id'] ?? 0),
                        'similarity' => round($similarity, 3),
                        'created_at' => (string) ($cand['created_at'] ?? ''),
                        'match_reasons' => $reasons,
                    ];
                }
            }
        }

        return [
            'has_duplicate' => !empty($duplicates),
            'duplicate_count' => count($duplicates),
            'duplicates' => $duplicates,
            'hours_window' => $hours,
            'threshold' => $threshold,
            'proposed_tokens' => array_values($proposedTokens),
        ];
    }

    private function tokenizeHeadline(string $text): array
    {
        $normalized = mb_strtolower($text, 'UTF-8');
        $clean = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $normalized) ?? '';
        $words = preg_split('/\s+/u', trim($clean));
        if (!is_array($words)) {
            return [];
        }
        $tokens = [];
        foreach ($words as $w) {
            $w = trim($w);
            if (mb_strlen($w, 'UTF-8') >= 2) {
                $tokens[] = $w;
            }
        }
        return array_values(array_unique($tokens));
    }
```

---

### Features 9, 10, 11: Changes to `lib/McpFaqTools.class.php`

#### Tool Definitions to Add/Modify in `McpFaqTools::definitions()`:
```php
            // Feature 11: list_faqs include_answer property
            'list_faqs' => [
                'module' => 'mod_faq',
                'role' => 'list',
                'domain' => 'faq',
                'handler' => 'listFaqs',
                'description' => 'List FAQ records with optional filters, ordered by sort_order then id. Returns paginated items (id, lang, mod_name, vid, question, sort_order, status, created_at, updated_at, and optionally answer if include_answer is true) plus the total count.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'mod_name' => ['type' => 'string', 'description' => 'Filter by host module name.'],
                        'vid' => ['type' => 'integer', 'description' => 'Filter by host record ID.'],
                        'lang' => ['type' => 'string', 'description' => 'Filter by language code.'],
                        'status' => ['type' => 'string', 'enum' => ['y', 'n'], 'description' => 'Filter by publish status.'],
                        'search' => ['type' => 'string', 'description' => 'Search term matched against question.'],
                        'include_answer' => [
                            'type' => 'boolean',
                            'default' => false,
                            'description' => 'Whether to include the full answer body in returned items. Defaults to false.',
                        ],
                        'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 50, 'default' => 10],
                        'offset' => ['type' => 'integer', 'minimum' => 0, 'default' => 0],
                    ],
                ],
                'title' => 'List FAQs',
                'annotations' => ['readOnlyHint' => true, 'destructiveHint' => false, 'idempotentHint' => true],
            ],

            // Feature 9: batch_create_faqs
            'batch_create_faqs' => [
                'module' => 'mod_faq',
                'role' => 'add',
                'domain' => 'faq',
                'handler' => 'batchCreateFaqs',
                'description' => 'Atomic batch creation of FAQ records for a single host record. Validates all items, host registration, language, and uniqueness before inserting any record. Accepts up to 25 items.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'mod_name' => ['type' => 'string', 'description' => 'Host module name (e.g. mod_news, mod_publication, mod_page).'],
                        'vid' => ['type' => 'integer', 'description' => 'ID of existing host module record.'],
                        'lang' => ['type' => 'string', 'description' => 'Active language code (e.g. tr, ku, en).'],
                        'status' => ['type' => 'string', 'enum' => ['y', 'n'], 'default' => 'n', 'description' => 'Publish status for batch items (default n for safe staging).'],
                        'replace_existing_drafts' => ['type' => 'boolean', 'default' => false, 'description' => 'If true, existing draft FAQs (status=n) for this entity/lang are deleted before inserting the new batch.'],
                        'items' => [
                            'type' => 'array',
                            'minItems' => 1,
                            'maxItems' => 25,
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'question' => ['type' => 'string', 'maxLength' => 255, 'description' => 'Question text (1-255 characters).'],
                                    'answer' => ['type' => 'string', 'maxLength' => 5000, 'description' => 'Answer text (1-5000 characters).'],
                                    'sort_order' => ['type' => 'integer', 'minimum' => 0, 'description' => 'Optional explicit sort order.'],
                                ],
                                'required' => ['question', 'answer'],
                            ],
                            'description' => 'Array of FAQ items to create (1-25 items).',
                        ],
                    ],
                    'required' => ['mod_name', 'vid', 'lang', 'items'],
                ],
                'title' => 'Batch Create FAQs',
                'annotations' => ['readOnlyHint' => false, 'destructiveHint' => false, 'idempotentHint' => false],
            ],

            // Feature 10: list_faq_modules
            'list_faq_modules' => [
                'module' => 'mod_faq',
                'role' => 'list',
                'domain' => 'faq',
                'handler' => 'listFaqModules',
                'description' => 'List the modules registered as FAQ-capable in the FAQ bridge (id, name, mod_name, mod_key, mod_value), ordered by id descending.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'search' => ['type' => 'string', 'description' => 'Search term matched against name, mod_name, mod_key, and mod_value.'],
                        'limit' => ['type' => 'integer', 'default' => 10, 'maximum' => 50, 'description' => 'Maximum registrations to return (1-50).'],
                        'offset' => ['type' => 'integer', 'default' => 0, 'description' => 'Registrations to skip.'],
                    ],
                ],
                'title' => 'List FAQ Modules',
                'annotations' => ['readOnlyHint' => true, 'destructiveHint' => false, 'idempotentHint' => true],
            ],
```

#### Handler Implementations in `lib/McpFaqTools.class.php`:

```php
    // Feature 11: include_answer support in listFaqs
    public function listFaqs(array $args): array
    {
        $limit = $this->normalizeLimit($args);
        $offset = $this->normalizeOffset($args);

        $selectCols = 'id, lang, mod_name, vid, question, sort_order, status, created_at, updated_at';
        if (!empty($args['include_answer'])) {
            $selectCols .= ', answer';
        }

        $model = $this->freshModel(self::FAQ_MODULE);
        $model->select($selectCols);
        $this->applyFaqFilters($model, $args);
        $model->order('sort_order', 'ASC')->order('id', 'ASC')->limit($limit, $offset)->getResult(true, true);
        $items = $model->toArray();

        $countModel = $this->freshModel(self::FAQ_MODULE);
        $countModel->select('id');
        $this->applyFaqFilters($countModel, $args);
        $countModel->getResult(true, true);
        $total = $countModel->getSize();

        return [
            'items' => is_array($items) ? $items : [],
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset
        ];
    }

    // Feature 9: batchCreateFaqs
    public function batchCreateFaqs(array $args): array
    {
        $modName = trim((string) ($args['mod_name'] ?? ''));
        $vid = (int) ($args['vid'] ?? 0);
        $this->assertEntity($modName, $vid);

        $lang = $this->requireActiveLang($args);
        $status = $this->optionalEnum($args, 'status', 'n', ['y', 'n']);
        $replaceDrafts = !empty($args['replace_existing_drafts']);

        $rawItems = $args['items'] ?? null;
        if (!is_array($rawItems) || empty($rawItems)) {
            throw new McpToolException("Field 'items' must be a non-empty array of FAQ objects.");
        }
        if (count($rawItems) > self::FAQ_MAX_PER_ENTITY) {
            throw new McpToolException("Field 'items' exceeds maximum batch size of " . self::FAQ_MAX_PER_ENTITY . " items.");
        }

        // Phase 1: In-memory structure & intra-batch uniqueness validation
        $validItems = [];
        $batchQuestions = [];
        foreach ($rawItems as $idx => $item) {
            if (!is_array($item)) {
                throw new McpToolException("Item at index {$idx} must be an object.");
            }
            $question = trim((string) ($item['question'] ?? ''));
            if ($question === '' || mb_strlen($question, 'UTF-8') > self::FAQ_QUESTION_MAX) {
                throw new McpToolException("Item {$idx}: Field 'question' is required (1-" . self::FAQ_QUESTION_MAX . " characters).");
            }

            $answer = trim((string) ($item['answer'] ?? ''));
            if ($answer === '' || mb_strlen($answer, 'UTF-8') > self::FAQ_ANSWER_MAX) {
                throw new McpToolException("Item {$idx}: Field 'answer' is required (1-" . self::FAQ_ANSWER_MAX . " characters).");
            }

            $this->assertContentMatchesLang($lang, $question, '', $answer);

            $normQ = mb_strtolower($question, 'UTF-8');
            if (isset($batchQuestions[$normQ])) {
                throw new McpToolException("Duplicate question within batch: '{$question}'.");
            }
            $batchQuestions[$normQ] = true;

            $validItems[] = [
                'question' => $question,
                'answer' => $answer,
                'sort_order' => isset($item['sort_order']) ? (int) $item['sort_order'] : null,
            ];
        }

        // Phase 2: Check database state (ceiling & uniqueness)
        $existingModel = $this->freshModel(self::FAQ_MODULE);
        $existingModel->where_mod_name_and_vid_and_lang($modName, $vid, $lang)->getResult(true, true);
        $existingList = $existingModel->toArray();
        if (!is_array($existingList)) {
            $existingList = [];
        }

        $activeExisting = [];
        foreach ($existingList as $ex) {
            if ($replaceDrafts && ($ex['status'] ?? 'y') === 'n') {
                continue; // Drafts will be purged
            }
            $activeExisting[] = $ex;
        }

        $totalProjected = count($activeExisting) + count($validItems);
        if ($totalProjected > self::FAQ_MAX_PER_ENTITY) {
            throw new McpToolException("Total FAQs would exceed ceiling of " . self::FAQ_MAX_PER_ENTITY . " (current: " . count($activeExisting) . ", batch: " . count($validItems) . ").");
        }

        foreach ($validItems as $vItem) {
            $normQ = mb_strtolower($vItem['question'], 'UTF-8');
            foreach ($activeExisting as $ex) {
                if (mb_strtolower(trim((string) $ex['question']), 'UTF-8') === $normQ) {
                    throw new McpToolException("Question already exists for this entity and language: '{$vItem['question']}'.");
                }
            }
        }

        // Phase 3: Atomic database operations
        $db = Load::lib('Database');
        $db->start_transaction();
        $createdIds = [];

        try {
            if ($replaceDrafts) {
                $draftModel = $this->freshModel(self::FAQ_MODULE);
                $draftModel->where_mod_name_and_vid_and_lang($modName, $vid, $lang);
                $draftModel->where_status('n');
                $draftModel->delete();
            }

            $nextSort = $this->nextEntitySortOrder($modName, $vid, $lang);

            foreach ($validItems as $vItem) {
                $faqModel = $this->freshModel(self::FAQ_MODULE);
                $faqModel->lang = $lang;
                $faqModel->mod_name = $modName;
                $faqModel->vid = $vid;
                $faqModel->question = $vItem['question'];
                $faqModel->answer = $vItem['answer'];
                $faqModel->sort_order = $vItem['sort_order'] !== null ? $vItem['sort_order'] : $nextSort++;
                $faqModel->status = $status;
                $faqModel->user_id = $this->actingUserId;
                $faqModel->created_at = date('Y-m-d H:i:s');

                if (!$faqModel->save(true)) {
                    throw new McpToolException("Failed to save FAQ item: '{$vItem['question']}'.");
                }
                $createdIds[] = (int) $faqModel->id;
            }

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollback();
            throw new McpToolException('Batch creation failed and was rolled back: ' . $e->getMessage());
        }

        // Load created item envelopes
        $createdItems = [];
        foreach ($createdIds as $cid) {
            $createdItems[] = $this->getFaq(['id' => $cid])['item'];
        }

        return [
            'created' => true,
            'count' => count($createdIds),
            'ids' => $createdIds,
            'items' => $createdItems,
        ];
    }

    // Feature 10: listFaqModules
    public function listFaqModules(array $args): array
    {
        $this->assertPermission(self::FAQ_MODULE, 'list');

        $limit = $this->normalizeLimit($args);
        $offset = $this->normalizeOffset($args);

        $model = $this->freshModel('mod_faq_mod');
        $model->select('id, name, mod_name, mod_key, mod_value');
        if (isset($args['search']) && trim((string) $args['search']) !== '') {
            $search = Obuilder::cc(trim((string) $args['search']));
            $model->addWhere("(name LIKE '%{$search}%' OR mod_name LIKE '%{$search}%' OR mod_key LIKE '%{$search}%' OR mod_value LIKE '%{$search}%')");
        }
        $model->order('id', 'DESC')->limit($limit, $offset)->getResult(true, true);
        $items = $model->toArray();

        $countModel = $this->freshModel('mod_faq_mod');
        $countModel->select('id');
        if (isset($args['search']) && trim((string) $args['search']) !== '') {
            $search = Obuilder::cc(trim((string) $args['search']));
            $countModel->addWhere("(name LIKE '%{$search}%' OR mod_name LIKE '%{$search}%' OR mod_key LIKE '%{$search}%' OR mod_value LIKE '%{$search}%')");
        }
        $countModel->getResult(true, true);
        $total = $countModel->getSize();

        return [
            'items' => is_array($items) ? $items : [],
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
        ];
    }
```

---

## 4. Caveats

1. **Coordination with Feature 3 (`getRecordAuditMeta`)**:
   - Feature 7 calls `getRecordAuditMeta(..., (string) ($row['status'] ?? 'y'))`.
   - Feature 3 (assigned to Explorer M1-1) modifies `getRecordAuditMeta` in `lib/McpCrudToolsBase.class.php:780` to accept the 4th `$recordStatus` parameter.
   - If Feature 7 is executed before Feature 3, PHP allows passing an extra argument without error, but the draft FAQs will only become visible once Feature 3 is implemented.
2. **`check_duplicate_news` vs Automated Tests**:
   - Automated test suites (e.g. `tasks/mcp_smoke.php`) might run repeatedly without resetting the database. `check_duplicate_news` is kept as a dedicated query tool and non-blocking warning on `create_news` to prevent test suites from being blocked by their own previous test runs.
3. **Database Transactions on SQLite in Test Harness**:
   - If testing in an environment where SQLite is used (e.g. lightweight unit test mocks), `Database::start_transaction()`, `commit()`, and `rollback()` are properly handled by `Adapter_Pdo_Sqlite` or fallback gracefully.

---

## 5. Conclusion

1. **Feature 5 (Root category check)**: Fully resolved by replacing `assertRecordMatchesLang` with `assertSelectableNewsCategory()` in `createNews()` and `updateNews()`. Prevents news staging into structural containers (`root = 'y'`).
2. **Feature 6 (Mandatory `makeSeo()`)**: Fully resolved by routing explicit `$seo` parameters through `self::makeSeo($rawSeo !== '' ? $rawSeo : $name, $lang)`. Eliminates invalid characters, preserves Wikipedia-style casing, and strips decomposed Unicode `\u{0307}`.
3. **Feature 7 (`getNews` enrichment)**: Enriches output with `username`, `author_fullname`, `category_name`, `category_seo`, and draft FAQ counts. Fixes the canonical non-recursive `item` envelope.
4. **Feature 8 (`checkDuplicateNews`)**: Implements pre-staging duplicate checks with Jaccard headline token overlap (>= 0.55) and exact SEO collision detection within 48 hours.
5. **Feature 9 (`batchCreateFaqs`)**: Provides atomic creation of up to 25 FAQs per host entity, with optional draft replacement and full pre-commit validation.
6. **Feature 10 (`listFaqModules`)**: Adds discovery for valid FAQ host modules from `mod_faq_mod` mirroring `McpGalleryTools::listGalleryModules`.
7. **Feature 11 (`listFaqs` `include_answer`)**: Allows fetching full FAQ answers in a single paginated round-trip by passing `include_answer: true`.

---

## 6. Verification Method

### 1. PHP Syntax Verification
Run:
```bash
php -l lib/McpNewsTools.class.php
php -l lib/McpFaqTools.class.php
php -l lib/McpCrudToolsBase.class.php
```
*Expected*: `No syntax errors detected in <file>`.

### 2. Code Quality Guards Verification
Run:
```bash
python3 tools/code_separation_guard.py
python3 tools/module_boundary_guard.py
python3 tools/i18n_guard.py --path lib/McpNewsTools.class.php lib/McpFaqTools.class.php
```
*Expected*: All guards pass with 0 violations.

### 3. Functional MCP Smoke Assertions in `tasks/mcp_smoke.php`
Add the following assertions into `tasks/mcp_smoke.php`:
1. **Root Category Block**:
   ```php
   $resRoot = runRpc(['jsonrpc' => '2.0', 'id' => 101, 'method' => 'tools/call', 'params' => ['name' => 'create_news', 'arguments' => [
       'name' => 'Test Root Cat News',
       'lang' => 'tr',
       'category_id' => $rootCategoryId, // category with root = 'y'
       'summary' => $validDeckSummary105,
       'content' => '<p>Valid content body.</p>',
   ]]], $plaintextKey);
   check('McpNews: create_news blocks root category', ($resRoot['result']['isError'] ?? false) === true && str_contains($resRoot['result']['content'][0]['text'] ?? '', 'structural root container'));
   ```
2. **SEO Normalization**:
   ```php
   $resSeo = runRpc(['jsonrpc' => '2.0', 'id' => 102, 'method' => 'tools/call', 'params' => ['name' => 'create_news', 'arguments' => [
       'name' => 'Test SEO News',
       'lang' => 'tr',
       'category_id' => $leafCategoryId,
       'summary' => $validDeckSummary105,
       'content' => '<p>Valid content body.</p>',
       'seo' => 'Diyarbakır Belediyesi: Yeni Karar 2026',
   ]]], $plaintextKey);
   $seoItem = json_decode($resSeo['result']['content'][0]['text'] ?? 'null', true);
   check('McpNews: explicit seo is normalized via makeSeo', ($seoItem['seo'] ?? '') === 'Diyarbakır_Belediyesi_Yeni_Karar_2026');
   ```
3. **Enriched `get_news` Envelope**:
   ```php
   $resGet = runRpc(['jsonrpc' => '2.0', 'id' => 103, 'method' => 'tools/call', 'params' => ['name' => 'get_news', 'arguments' => [
       'id' => (int) $seoItem['id'],
   ]]], $plaintextKey);
   $getItem = json_decode($resGet['result']['content'][0]['text'] ?? 'null', true);
   check('McpNews: get_news returns enriched fields', !empty($getItem['username']) && !empty($getItem['category_name']) && isset($getItem['item']['username']));
   ```
4. **Duplicate News Check**:
   ```php
   $resDup = runRpc(['jsonrpc' => '2.0', 'id' => 104, 'method' => 'tools/call', 'params' => ['name' => 'check_duplicate_news', 'arguments' => [
       'name' => 'Test SEO News',
       'lang' => 'tr',
   ]]], $plaintextKey);
   $dupData = json_decode($resDup['result']['content'][0]['text'] ?? 'null', true);
   check('McpNews: check_duplicate_news detects matching title within 48h', ($dupData['has_duplicate'] ?? false) === true);
   ```
5. **Batch Create FAQs & Include Answer**:
   ```php
   $resBatch = runRpc(['jsonrpc' => '2.0', 'id' => 105, 'method' => 'tools/call', 'params' => ['name' => 'batch_create_faqs', 'arguments' => [
       'mod_name' => 'mod_news',
       'vid' => (int) $seoItem['id'],
       'lang' => 'tr',
       'status' => 'n',
       'items' => [
           ['question' => 'Soru Bir: Bu nedir?', 'answer' => 'Cevap Bir: Bu bir test cevabıdır.'],
           ['question' => 'Soru İki: Ne zaman?', 'answer' => 'Cevap İki: Bugün gerçekleşti.'],
       ],
   ]]], $plaintextKey);
   $batchData = json_decode($resBatch['result']['content'][0]['text'] ?? 'null', true);
   check('McpFaq: batch_create_faqs creates items atomically', ($batchData['count'] ?? 0) === 2);

   $resListFaqs = runRpc(['jsonrpc' => '2.0', 'id' => 106, 'method' => 'tools/call', 'params' => ['name' => 'list_faqs', 'arguments' => [
       'mod_name' => 'mod_news',
       'vid' => (int) $seoItem['id'],
       'include_answer' => true,
   ]]], $plaintextKey);
   $faqsList = json_decode($resListFaqs['result']['content'][0]['text'] ?? 'null', true);
   check('McpFaq: list_faqs with include_answer returns answer text', isset($faqsList['items'][0]['answer']));
   ```
6. **List FAQ Modules**:
   ```php
   $resFaqMods = runRpc(['jsonrpc' => '2.0', 'id' => 107, 'method' => 'tools/call', 'params' => ['name' => 'list_faq_modules', 'arguments' => []]], $plaintextKey);
   $modsData = json_decode($resFaqMods['result']['content'][0]['text'] ?? 'null', true);
   check('McpFaq: list_faq_modules returns registered host modules', ($modsData['total'] ?? 0) > 0);
   ```

### 4. Invalidation Conditions
- A `root = 'y'` category is allowed to have news staged under it.
- An explicit slug with unencoded spaces or colons is saved verbatim without `makeSeo()` normalization.
- `get_news` payload omits `username` or `category_name`.
- `batch_create_faqs` half-commits when an error occurs on item 2 of 3.
- `list_faqs` with `include_answer: true` does not include the `answer` key in the items list.
