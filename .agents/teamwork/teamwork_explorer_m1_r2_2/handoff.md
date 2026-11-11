# Handoff Report: Milestone 1, Iteration 2 — Explorer M1-R2-2 Investigation

**Explorer**: Explorer M1-R2-2 (`teamwork_explorer_m1_r2_2`)  
**State Folder**: `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_explorer_m1_r2_2/`  
**Recipient**: Sub-Orchestrator M1 (`sub_orch_m1`, conversation ID `901f7801-31c7-4c08-be51-94ed6f17c21d`)  
**Scope**: Root-cause investigation and exact remediation design for Defect 2 (`McpCrudToolsBase::makeSeo` dirty slug / forward slash bug) and Defect 3 (`updateNews` leaf category check bypass)  
**Date**: 2026-11-11  

---

## 1. Observation

### 1.1 Defect 2: `McpCrudToolsBase::makeSeo` Forward Slash & Dirty Character Preservation
- **Location**: `lib/McpCrudToolsBase.class.php:742–777`
- **Verbatim Existing Code**:
  ```php
  public static function makeSeo(string $text, string $locale = 'en'): string
  {
      if ($text === '') {
          return '';
      }

      $locale = strtolower(trim($locale));
      $isKurdish = in_array($locale, self::KURDISH_LOCALES, true);

      // Remove apostrophes, quotation marks, and punctuation (:;,!? etc.)
      $value = preg_replace('/[\x{0027}\x{0022}\x{2019}\x{201B}\x{201C}\x{201D}\x{201E}\x{201F}:;,!?()\[\]{}]/u', '', $text) ?? '';
      // Whitespace and dashes become underscores
      $value = preg_replace('/[\s-]+/u', '_', $value) ?? '';
      // Wikipedia-style parity: preserve original casing, do not force lowercase
      // Normalize to NFC and strip stray combining dot above (U+0307)
      if (class_exists('Normalizer')) {
          $normalized = \Normalizer::normalize($value, \Normalizer::FORM_C);
          if (is_string($normalized)) {
              $value = $normalized;
          }
      }
      $value = str_replace("\u{0307}", '', $value);
      // Kurdish keyboard typo normalizations
      if ($isKurdish) {
          $value = str_replace(['ı', 'İ'], ['i', 'I'], $value);
      }
      // Collapse repeated underscores and trim
      $value = preg_replace('/_+/', '_', $value) ?? '';
      $value = trim($value, '_');

      if (mb_strlen($value) > 255) {
          $value = mb_substr($value, 0, 255);
      }

      return $value;
  }
  ```

- **Runtime Empirical Reproduction**:
  Executing `McpCrudToolsBase::makeSeo` directly against the running PHP container:
  ```bash
  docker exec bulletin_app php -r '
  require "/var/www/html/init.php";
  require "/var/www/html/lib/McpCrudToolsBase.class.php";
  echo McpCrudToolsBase::makeSeo("Diyarbakır Haber: Test / \x27123\x27", "tr") . PHP_EOL;
  echo McpCrudToolsBase::makeSeo("Gündem / 2025/2026 Sezonu", "tr") . PHP_EOL;
  echo McpCrudToolsBase::makeSeo("A & B <C> #D %E", "tr") . PHP_EOL;
  '
  ```
  **Verbatim Output**:
  ```
  Diyarbakır_Haber_Test_/_123
  Gündem_/_2025/2026_Sezonu
  A_&_B_<C>_#D_%E
  ```
  Literal forward slashes `/`, backslashes `\`, angle brackets `< >`, hashes `#`, ampersands `&`, and percent signs `%` pass through completely unsanitized.

- **Routing Failure Analysis (`lib/Amed.class.php:636–649`)**:
  ```php
  public function parseRoute($route)
  {
      if (strpos($route, '/') !== false) {
          $val = explode('/', $route);
          $this->class = (isset($val['0']) and $val['0'] != '') ? $val['0'] : Registry::get('app_default_mod');
          $this->method = (isset($val['1']) and $val['1'] != '') ? $val['1'] : Registry::get('app_default_function');
          $ext = array_slice($val, 2);
  ```
  Public news URLs are routed as `/{lang}/news/{seo}`. When `{seo}` contains a forward slash (e.g. `tr/news/Diyarbakır_Haber_Test_/_123`), `$val` contains `[0 => 'tr', 1 => 'news', 2 => 'Diyarbakır_Haber_Test_', 3 => '_123']`. The routing parser interprets `Diyarbakır_Haber_Test_` as a controller action or submodule rather than a single slug parameter, resulting in an unrouteable path and an immediate HTTP 404 error.

- **Canonical Canonical Implementations**:
  1. `lib/Helper.class.php:180–191` (`Helper::legacy_seo`):
     ```php
     static function legacy_seo($s)
     {
         $s = preg_replace('/[\x{0027}\x{0022}\x{2019}\x{201B}\x{201C}\x{201D}\x{201E}\x{201F}:;,!?()\[\]{}]/u', '', (string) $s);
         $s = preg_replace('/[^\p{L}\p{N} ]/u', " ", $s);
         $s = trim($s);
         $s = preg_replace('/\s+/', '_', $s);
         for ($i = 0; $i <= 5; $i++) {
             $s = str_replace("__", "_", $s);
         }
         return $s;
     }
     ```
  2. `templates/admin/assets/js/script.js:70–78` (`makeLegacySeoValue`):
     ```javascript
     function makeLegacySeoValue(text) {
         if (!text) return "";
         let clean = String(text).replace(/['’"”„“]/g, "");
         clean = clean.replace(/[^\p{L}\p{N}\s_-]/gu, " ");
         clean = clean.trim();
         clean = clean.replace(/[\s-]+/g, "_");
         clean = clean.replace(/_+/g, "_");
         return clean;
     }
     ```
  Both canonical implementations replace all non-alphanumeric characters with spaces (`[^\p{L}\p{N}\s_-]`), ensuring forward slashes and symbols never enter URL slugs.

---

### 1.2 Defect 3: Leaf Category Guard Incomplete in `updateNews()`
- **Location**: `lib/McpNewsTools.class.php:978–984`
- **Verbatim Existing Code**:
  ```php
  $lang = isset($args['lang']) ? $this->requireActiveLang($args) : (string) $model->lang;
  $categoryId = isset($args['category_id']) ? (int) $args['category_id'] : (int) $model->category_id;

  if ($categoryId !== (int) $model->category_id || $lang !== (string) $model->lang) {
      $this->assertSelectableNewsCategory($categoryId, $lang);
  }
  ```

- **Observed Behavior & Failure Mechanism**:
  1. If an existing article in the database was historically assigned to a structural root category (e.g. `root = 'y'`, category ID 17 `"##Türkçe_Kategoriler"`), `$model->category_id` is `17`.
  2. When a client sends `update_news` with `['id' => $id, 'category_id' => 17]`, `$categoryId` is `17`.
  3. The condition `$categoryId !== (int) $model->category_id` evaluates to `17 !== 17` (`false`).
  4. If `$lang` is unchanged, `$lang !== (string) $model->lang` evaluates to `false`.
  5. The entire condition evaluates to `false`, completely bypassing `assertSelectableNewsCategory()`.
  6. The mutation succeeds, perpetuating the invalid root category association despite the caller explicitly supplying `category_id`.
  7. Furthermore, if `$args['category_id']` is passed as `0` on a legacy record that also has `category_id = 0`, validation is similarly bypassed.

---

## 2. Logic Chain

### 2.1 Logic Chain for Defect 2 (`makeSeo`)
1. **Observation 1.1**: `McpCrudToolsBase::makeSeo` strips only specific punctuation characters `[\x{0027}\x{0022}\x{2019}\x{201B}\x{201C}\x{201D}\x{201E}\x{201F}:;,!?()\[\]{}]`.
2. **Inference**: Any character not matched by that set (including `/`, `\`, `<`, `>`, `#`, `%`, `&`, `+`) survives into `$value`.
3. **Observation 1.1**: `Amed.class.php:639` decomposes routes using `$val = explode('/', $route)`.
4. **Inference**: Slashes inside the `{seo}` component create extra array elements in `$val`, causing the router to misinterpret the slug segment as an action/ext parameter, failing route dispatch and throwing HTTP 404.
5. **Observation 1.1**: `Helper::legacy_seo` and `script.js` replace `[^\p{L}\p{N}\s_-]` with spaces before whitespace-to-underscore collapsing.
6. **Critical Ordering Discovery**:
   - In Unicode, decomposed diacritics (such as combining dot above `\u{0307}` in `"I\u{0307}stanbul"` and combining diaeresis `\u{0308}` in `"Ku\u{0308}ltu\u{0308}r"`) belong to category `\p{Mn}` (Mark, nonspacing), NOT `\p{L}` (Letter).
   - If `preg_replace('/[^\p{L}\p{N}\s_-]/u', ' ', $text)` runs *before* Unicode NFC normalization, the combining marks are stripped or replaced with spaces. Subsequent `[\s-]+` conversion turns those spaces into underscores, resulting in `"I_stanbul"` and `"Ku_ltu_r"`.
   - Therefore, Unicode normalization to NFC (`Normalizer::FORM_C`) must execute **FIRST** on the raw input `$text`. Under `FORM_C`, `"I\u{0307}"` composes into `"İ"` (U+0130, category `\p{L}`) and `"u\u{0308}"` composes into `"ü"` (category `\p{L}`).
   - After normalization and stripping any orphan `\u{0307}`, `preg_replace('/[^\p{L}\p{N}\s_-]/u', ' ', $value)` safely strips slashes and symbols while preserving all composed international and Turkish/Kurdish characters.
7. **Conclusion**: `makeSeo()` must be refactored with the proper operation sequence to eliminate slashes while preserving decomposed Unicode characters.

---

### 2.2 Logic Chain for Defect 3 (`updateNews` Leaf Category Guard)
1. **Observation 1.2**: `assertSelectableNewsCategory()` checks if a category exists, matches content language, and rejects structural root containers (`root = 'y'`).
2. **Observation 1.2**: In `lib/McpNewsTools.class.php:981`, `assertSelectableNewsCategory()` is only called `if ($categoryId !== (int) $model->category_id || $lang !== (string) $model->lang)`.
3. **Inference**: The check tests for *delta* rather than *presence in request*.
4. **Inference**: If a client sends an update payload explicitly containing `'category_id'`, the client is asserting that category assignment. If the record already had that category ID (e.g. legacy root category), no delta is detected, and the invalid root assignment is confirmed without validation.
5. **Inference**: If the condition is updated to `if (isset($args['category_id']) || $lang !== (string) $model->lang)`:
   - Any payload explicitly providing `category_id` (`isset($args['category_id'])`) is strictly validated against `assertSelectableNewsCategory()`.
   - Any payload that changes language (`$lang !== (string) $model->lang`) re-validates the category against the new language.
   - Partial updates that do NOT provide `category_id` and do NOT change language continue to succeed without redundant category queries.
6. **Conclusion**: Replacing the condition with `if (isset($args['category_id']) || $lang !== (string) $model->lang)` completely closes the validation hole.

---

## 3. Caveats

1. **PHP `intl` Extension (`Normalizer`)**:
   - `Normalizer::normalize()` requires `ext-intl`. In the production Docker container (`bulletin_app`), PHP 8.2 has `ext-intl` loaded.
   - For environments without `intl`, the code guards with `if (class_exists('Normalizer'))` and immediately follows with `str_replace("\u{0307}", '', $text)` to ensure orphan combining dots do not leak.
2. **Kurdish Typography Normalization Placement**:
   - Kurdish keyboard typo conversion (`ı`/`İ` -> `i`/`I`) applies exclusively when `$isKurdish` is true.
   - This replacement must execute after Unicode NFC normalization so that composed `İ` is properly matched.
3. **Partial Updates on Legacy Records**:
   - By conditioning on `isset($args['category_id'])` rather than unconditionally re-validating the category on every single update, routine typo fixes or status changes on legacy articles do not fail if an administrator updates only `name` or `content`.

---

## 4. Conclusion & Actionable Fix Strategy

### 4.1 Exact Fix for Defect 2: `lib/McpCrudToolsBase.class.php::makeSeo`

Replace lines 742–777 in `lib/McpCrudToolsBase.class.php` with:

```php
    /**
     * Builds an SEO slug from a name, mirroring the admin makeLegacySeoValue helper.
     * Enforces Wikipedia-style casing, NFC normalization, and strips forward slashes
     * and unsafe symbols that break web routing.
     */
    public static function makeSeo(string $text, string $locale = 'en'): string
    {
        if ($text === '') {
            return '';
        }

        $locale = strtolower(trim($locale));
        $isKurdish = in_array($locale, self::KURDISH_LOCALES, true);

        // Normalize Unicode to NFC first so combining diacritics compose into base letters
        if (class_exists('Normalizer')) {
            $normalized = \Normalizer::normalize($text, \Normalizer::FORM_C);
            if (is_string($normalized)) {
                $text = $normalized;
            }
        }
        $text = str_replace("\u{0307}", '', $text);

        // Remove apostrophes, quotation marks, and punctuation (:;,!? etc.)
        $value = preg_replace('/[\x{0027}\x{0022}\x{2019}\x{201B}\x{201C}\x{201D}\x{201E}\x{201F}:;,!?()\[\]{}]/u', '', $text) ?? '';

        // Replace slashes, backslashes, and non-alphanumeric symbols with spaces (matches Helper::legacy_seo and script.js)
        $value = preg_replace('/[^\p{L}\p{N}\s_-]/u', ' ', $value) ?? '';

        // Kurdish keyboard typo normalizations
        if ($isKurdish) {
            $value = str_replace(['ı', 'İ'], ['i', 'I'], $value);
        }

        // Whitespace and dashes become underscores
        $value = preg_replace('/[\s-]+/u', '_', $value) ?? '';

        // Collapse repeated underscores and trim
        $value = preg_replace('/_+/', '_', $value) ?? '';
        $value = trim($value, '_');

        if (mb_strlen($value) > 255) {
            $value = mb_substr($value, 0, 255);
        }

        return $value;
    }
```

#### Comparison of Behaviors:
| Input | Locale | Existing `makeSeo` (Defective) | Proposed `makeSeo` (Fixed) |
|---|---|---|---|
| `"Diyarbakır Haber: Test / '123'"` | `tr` | `'Diyarbakır_Haber_Test_/_123'` (Contains `/`) | `'Diyarbakır_Haber_Test_123'` (Safe) |
| `"Gündem / 2025/2026 Sezonu"` | `tr` | `'Gündem_/_2025/2026_Sezonu'` (Contains `/`) | `'Gündem_2025_2026_Sezonu'` (Safe) |
| `"A & B <C> #D %E"` | `tr` | `'A_&_B_<C>_#D_%E'` (Symbols) | `'A_B_C_D_E'` (Safe) |
| `"I\u{0307}stanbul Haber Ku\u{0308}ltu\u{0308}r Sanat"` | `tr` | `'İstanbul_Haber_Kültür_Sanat'` | `'İstanbul_Haber_Kültür_Sanat'` (Preserved) |
| `"P\u{0131}rs \u{0130}nternet \u{00dc}niversite"` | `ku` | `'Pirs_Internet_Üniversite'` | `'Pirs_Internet_Üniversite'` (Preserved) |
| `"  Son   Dakika  ---  Gelişmesi  ---  2026  "` | `tr` | `'Son_Dakika_Gelişmesi_2026'` | `'Son_Dakika_Gelişmesi_2026'` (Preserved) |

---

### 4.2 Exact Fix for Defect 3: `lib/McpNewsTools.class.php::updateNews`

In `lib/McpNewsTools.class.php`, change lines 981–983 from:
```php
        if ($categoryId !== (int) $model->category_id || $lang !== (string) $model->lang) {
            $this->assertSelectableNewsCategory($categoryId, $lang);
        }
```
To:
```php
        if (isset($args['category_id']) || $lang !== (string) $model->lang) {
            $this->assertSelectableNewsCategory($categoryId, $lang);
        }
```

---

## 5. Verification Method

### 5.1 Verification for Defect 2 (`makeSeo` Sanitization)
Run the verification script inside the container:
```bash
docker exec bulletin_app php -r '
require "/var/www/html/init.php";
require "/var/www/html/lib/McpCrudToolsBase.class.php";

$tests = [
    "Diyarbakır Haber: Test / \x27123\x27" => "Diyarbakır_Haber_Test_123",
    "Gündem / 2025/2026 Sezonu" => "Gündem_2025_2026_Sezonu",
    "A & B <C> #D %E" => "A_B_C_D_E",
    "I\u{0307}stanbul Haber Ku\u{0308}ltu\u{0308}r Sanat" => "İstanbul_Haber_Kültür_Sanat",
    "P\u{0131}rs \u{0130}nternet \u{00dc}niversite" => "Pirs_Internet_Üniversite",
    "  Son   Dakika  ---  Gelişmesi  ---  2026  " => "Son_Dakika_Gelişmesi_2026",
];

foreach ($tests as $input => $expected) {
    $lang = str_contains($input, "Pirs") ? "ku" : "tr";
    $result = McpCrudToolsBase::makeSeo($input, $lang);
    if ($result !== $expected) {
        echo "FAILED: input \"{$input}\" -> got \"{$result}\", expected \"{$expected}\"\n";
        exit(1);
    }
}
echo "ALL makeSeo ASSERTIONS PASSED!\n";
'
```
*Expected Result*: Output `ALL makeSeo ASSERTIONS PASSED!`, exit code 0.

### 5.2 Verification for Defect 3 (`updateNews` Root Category Guard)
Run inside the container:
```bash
docker exec bulletin_app php -r '
require "/var/www/html/init.php";
require "/var/www/html/lib/McpServer.class.php";
require "/var/www/html/lib/McpCrudToolsBase.class.php";
require "/var/www/html/lib/McpNewsTools.class.php";

$tool = new McpNewsTools(1, ["*" => ["*" => true]]);
$db = Load::lib("Database");
$db->fetch_array("SELECT id, category_id, lang FROM mod_news WHERE status = \x27n\x27 LIMIT 1");
$row = $db->getResultOne()->toArray();
$newsId = (int) $row["id"];

// Explicitly updating with category_id = 17 (root container) must throw McpToolException
try {
    $tool->updateNews(["id" => $newsId, "category_id" => 17]);
    echo "FAILED: updateNews accepted root category 17!\n";
    exit(1);
} catch (\McpToolException $e) {
    if (str_contains($e->getMessage(), "structural root container")) {
        echo "PASSED: updateNews correctly rejected root category 17: " . $e->getMessage() . "\n";
    } else {
        echo "FAILED: unexpected exception message: " . $e->getMessage() . "\n";
        exit(1);
    }
}
'
```
*Expected Result*: Output `PASSED: updateNews correctly rejected root category 17...`, exit code 0.

### 5.3 Automated Test Suites
1. **Challenger Stress Test**:
   ```bash
   docker exec bulletin_app php tasks/challenger_stress_test.php
   ```
   *Expected Result*: Exit code 0, 48 passed, 0 failed (Dimension 4 passes).
2. **MCP Smoke Suite**:
   ```bash
   docker exec bulletin_app php tasks/mcp_smoke.php
   ```
   *Expected Result*: Exit code 0, 82 passed, 0 failed.
3. **Quality Guard Audits**:
   ```bash
   python3 tools/code_separation_guard.py --path lib/McpCrudToolsBase.class.php
   python3 tools/code_separation_guard.py --path lib/McpNewsTools.class.php
   python3 tools/module_boundary_guard.py
   ```
   *Expected Result*: All guards report `[PASS]` / `✅ PASSED` with exit code 0.
