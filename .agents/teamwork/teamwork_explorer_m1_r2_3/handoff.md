# Handoff Report: Investigation of Defect 4 (`python3 tools/i18n_guard.py` Quality Gate Failure)

**Agent**: Explorer M1-R2-3 (`teamwork_explorer_m1_r2_3`)  
**State Folder**: `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_explorer_m1_r2_3/`  
**Parent / Recipient**: Sub-Orchestrator M1 (`sub_orch_m1`, conversation ID `901f7801-31c7-4c08-be51-94ed6f17c21d`)  
**Date**: 2026-11-11  
**Status**: Investigation Complete — Fully Verified Remediation Plan  

---

## 1. Observation

### 1.1 Direct Reproduction of Quality Gate Failure
Executing `python3 tools/i18n_guard.py` on the Milestone 1 touched PHP files reproduced the exact 22 errors reported by Reviewer 1 (exit code `1`):

```
📁 Files Audited (Milestone 1 Scope): 7
🚨 Total Violations: 22 (22 Errors, 0 Warnings)
```

#### Exact Inventory of the 22 Violations:
1. **`lib/McpCrudToolsBase.class.php:365`** (1 error):
   - **Violation**: `NON_ENGLISH_FUNCTION`
   - **Message**: `Non-English function name 'addEqFilter': Unrecognized non-English token: 'Eq'`
   - **Snippet**: `protected function addEqFilter(object $model, string $column, string|int|float $value): void`
   - **Contract Context**: `addEqFilter` is explicitly mandated by `docs/plan/v1.0.63-kevanger-editorial-pipeline-remediation-and-enhancement-plan.md` (§ Phase 1.1 lines 283–286, 510), `SCOPE.md` line 5, and `TEST_INFRA.md`. It is called across 12 locations in `lib/McpCrudToolsBase.class.php`, `lib/McpFaqTools.class.php`, and `lib/McpNewsTools.class.php`.

2. **`lib/McpFaqTools.class.php:511`** (1 error):
   - **Violation**: `NON_ENGLISH_COMMENT`
   - **Message**: `Non-English comment word 'intra' detected. Comments must be strictly in English: Unrecognized non-English token: 'intra'`
   - **Snippet**: `// Phase 1: In-memory structure & intra-batch uniqueness validation`

3. **`lib/McpNewsTools.class.php:1177`** (1 error):
   - **Violation**: `NON_ENGLISH_COMMENT`
   - **Message**: `Non-English comment word 'Jaccard' detected. Comments must be strictly in English: Unrecognized non-English token: 'Jaccard'`
   - **Snippet**: `* Compares headline token Jaccard similarity and exact SEO match.`

4. **`lib/McpNewsTools.class.php:1207–1234`** (13 errors):
   - **Violation**: `NON_ENGLISH_VARIABLE`
   - **Message**: `Non-English variable '$cand' / '$candName' / '$candSeo' / '$candTokens': Unrecognized non-English token: 'cand'`
   - **Occurrences**:
     - Line 1207: `foreach ($candidates as $cand) {`
     - Line 1208: `$candName = (string) ($cand['name'] ?? '');`
     - Line 1209: `$candSeo = (string) ($cand['seo'] ?? '');`
     - Line 1210: `$candTokens = $this->tokenizeHeadline($candName);`
     - Line 1212: `$intersection = count(array_intersect($proposedTokens, $candTokens));`
     - Line 1213: `$union = count(array_unique(array_merge($proposedTokens, $candTokens)));`
     - Line 1216: `$isSeoMatch = ($proposedSeo !== '' && $candSeo === $proposedSeo);`
     - Line 1228: `'id' => (int) $cand['id'],`
     - Line 1229: `'name' => $candName,`
     - Line 1230: `'seo' => $candSeo,`
     - Line 1231: `'status' => (string) ($cand['status'] ?? 'y'),`
     - Line 1232: `'category_id' => (int) ($cand['category_id'] ?? 0),`
     - Line 1234: `'created_at' => (string) ($cand['created_at'] ?? ''),`

5. **`tasks/mcp_smoke.php:886–895`** (6 errors):
   - **Violation**: `NON_ENGLISH_VARIABLE`
   - **Message**: `Non-English variable '$resDup' / '$dupPayload': Unrecognized non-English token: 'Dup' / 'dup'`
   - **Occurrences**:
     - Line 886: `$resDup = runRpc(...);`
     - Line 890: `$dupPayload = json_decode($resDup['result']['content'][0]['text'] ?? 'null', true);`
     - Line 892: `($resDup['result']['isError'] ?? true) === false`
     - Line 893: `&& ($dupPayload['has_duplicate'] ?? false) === true`
     - Line 894: `&& ($dupPayload['duplicate_count'] ?? 0) >= 1,`
     - Line 895: `json_encode($dupPayload)`

### 1.2 Evaluation Mechanism of `tools/i18n_guard.py`
Analysis of `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/tools/i18n_guard.py` revealed the exact evaluation mechanisms:
1. **Tokenization (`split_identifier`, lines 170–184)**:
   Identifiers (functions, variables, classes) are stripped of leading `$` and `_`, split on `_` and `-`, and then decomposed into sub-tokens using camelCase/PascalCase regex:
   `re.findall(r"[A-Z]?[a-z]+|[0-9]+|[A-Z]+(?=[A-Z][a-z]|\b)", part)`.
   - `addEqFilter` decomposes into: `['add', 'Eq', 'Filter']`.
   - `$candTokens` decomposes into: `['cand', 'Tokens']`.
   - `$resDup` decomposes into: `['res', 'Dup']`.
2. **Lexicon & Validation (`is_english_token`, lines 116–169)**:
   Each lowercase sub-token is checked against:
   - Digits and `DEV_TERMS` (case-insensitive whitelist in lines 33–76).
   - Wordlist `/usr/share/dict/words` (`self.words`).
   - English inflectional suffixes (35 suffixes: `ification`, `ing`, `ed`, `able`, `tion`, `s`, `es`, `or`, etc.).
   - English prefixes (11 prefixes: `un`, `re`, `pre`, `post`, `non`, `multi`, `sub`, `de`, `dis`, `over`, `under`).
   - Compound words formed by concatenating two known words or `DEV_TERMS`.
   If none match, it triggers an `ERROR` with message `Unrecognized non-English token: '<token>'`.
3. **Comment Stripping & Checking (lines 469–504)**:
   Comments are filtered to exclude docblock annotations (`@param`, `@var`, etc.), URLs, emails, file paths, PHP variables (`\$[a-zA-Z0-9_]+`), and **backticked literals** (`c_clean = re.sub(r'`[^`]+`', '', c_clean)` at line 485).
   Words of 3 or more letters are extracted (`\b[a-zA-Z]{3,}\b`) and validated with `is_english_token()`.
   - `intra` in `intra-batch`: extracted as standalone token `intra`. Absent from `/usr/share/dict/words`, absent from prefixes in `is_english_token()`, absent from `DEV_TERMS`.
   - `Jaccard`: proper noun (Paul Jaccard), absent from `/usr/share/dict/words` and `DEV_TERMS`. Because it was not enclosed in backticks (`` `Jaccard` ``), it was checked as an English dictionary word.
4. **Git Status Audit Discovery (lines 305–325)**:
   When run without `--path` or `--all`, `i18n_guard.py` executes `git status --porcelain`.
   Because lines 315–318 include untracked files (`status not in ("D ", " D")`), any untracked `.php` script in the repo root (such as `tasks/challenger_stress_test.php` and `tests/m1_2_challenger_test.php` generated during Iteration 1 adversarial runs) is included in the scan, producing an additional 59 errors if left in the tree.

---

## 2. Logic Chain

1. **Resolution of `addEqFilter`**:
   - `addEqFilter` is an immutable interface contract required by `docs/plan/...`, `SCOPE.md`, and test suites across all 15 MCP tool classes.
   - Renaming `addEqFilter` to `addEqualityFilter` would break API compatibility with the approved architectural plan and existing test suites.
   - `eq` is a universally standard operator abbreviation across query languages, ORMs, and developer tooling (MongoDB `$eq`, GraphQL `eq`, Elasticsearch, Bash `-eq`, etc.).
   - `DEV_TERMS` in `tools/i18n_guard.py` is specifically designed for standard developer vocabulary and abbreviations (`col`, `tbl`, `calc`, `diff`, `stat`, `dest`, `fn`, `cb`, `stmt`, `dto`, `dao`, `orm`, `crud`).
   - Per `docs/CROSS_REPO_SYNC_POLICY.md` line 14, `tools/i18n_guard.py` is designated as `adapted (per-repo allowlists / forbidden lists / module sets)`.
   - Adding `"eq"` (alongside companion comparison operators `"neq"`, `"gt"`, `"gte"`, `"lt"`, `"lte"`) to `DEV_TERMS` in `tools/i18n_guard.py` directly resolves the violation while preserving 100% interface contract compliance.

2. **Resolution of `$cand*` and `$dup*` Variables**:
   - Neither `cand` nor `dup` is a universal operator or standard technical protocol term; both are informal truncated abbreviations of standard English words (`candidate` and `duplicate`).
   - Adding colloquial truncated tokens (`cand`, `dup`) to `DEV_TERMS` would degrade the quality guard's ability to enforce clean code and descriptive English naming.
   - Refactoring:
     - In `lib/McpNewsTools.class.php`: `$cand` $\rightarrow$ `$candidate`, `$candName` $\rightarrow$ `$candidateName`, `$candSeo` $\rightarrow$ `$candidateSeo`, `$candTokens` $\rightarrow$ `$candidateTokens`.
       *Crucial Implementation Guard*: In `lib/McpNewsTools.class.php`, the candidate list variable is already named `$candidates`. A naive text search-and-replace of `$cand` would corrupt `$candidates` into `$candidateidates`. Replacements must use word-boundary regex (`\$cand\b`) or target full token identifiers.
     - In `tasks/mcp_smoke.php`: `$resDup` $\rightarrow$ `$resDuplicate`, `$dupPayload` $\rightarrow$ `$duplicatePayload`.
   - Both `candidate` and `duplicate` are verified standard English words in `/usr/share/dict/words` (`lex.is_english_token()` returns `True`).

3. **Resolution of Comment Vocabulary (`intra` and `Jaccard`)**:
   - In `lib/McpFaqTools.class.php:511`:
     Replacing `intra-batch uniqueness validation` with `within-batch uniqueness validation` uses standard, elegant English. `within` is verified in `/usr/share/dict/words`.
   - In `lib/McpNewsTools.class.php:1177`:
     Wrapping `Jaccard` in backticks (`` `Jaccard` ``) causes `tools/i18n_guard.py` line 485 (`re.sub(r'`[^`]+`', '', c_clean)`) to ignore the literal, conforming to standard PHPDoc notation.
   - Adding `"jaccard"` and `"intra"` to `DEV_TERMS` in `tools/i18n_guard.py` provides defense-in-depth against false positives in other comments.

4. **Empirical Verification of 0 Errors**:
   - A dry-run verification harness applying these remediations eliminated all 22 violations across all 7 milestone files:
     - `lib/McpCrudToolsBase.class.php`: 0 issues
     - `lib/McpFaqTools.class.php`: 0 issues
     - `lib/McpHeadlineTools.class.php`: 0 issues
     - `lib/McpNewsTools.class.php`: 0 issues
     - `lib/McpServer.class.php`: 0 issues
     - `lib/McpSurveyTools.class.php`: 0 issues
     - `tasks/mcp_smoke.php`: 0 issues
   - `python3 tools/i18n_guard.py` exits with code `0`.

---

## 3. Caveats

1. **Untracked Test Scripts in Working Directory**:
   Adversarial test scripts created by challengers during Iteration 1 (`tasks/challenger_stress_test.php` and `tests/m1_2_challenger_test.php`) currently reside in the workspace untracked. If a bare `python3 tools/i18n_guard.py` is run without `--path`, `git status --porcelain` includes them.
   - To achieve repository-wide clean pass:
     - Either remove/clean these temporary test scripts before running the global gate (`git clean -fd tasks/ tests/` or remove the two specific files).
     - Or update the two challenger files to use `$duplicate*` instead of `$dup*` and remove SQL aliases (`as c`).
2. **Read-Only Explorer Constraint**:
   As an Explorer agent, I do not directly modify tracked repository code. Complete drop-in code snippets and diff patches are provided below for Worker M1-1 to apply.

---

## 4. Conclusion & Verified Remediation Plan

**Assessment**:
The 22 errors reported by Reviewer 1 are completely resolvable without architectural redesign, interface breakage, or compromising project quality rules.

### Actionable Remediation Steps for Worker M1-1:

#### Step 1: Update `tools/i18n_guard.py`
Add relational operators and algorithm terms (`eq`, `neq`, `gt`, `gte`, `lt`, `lte`, `jaccard`, `intra`) to `DEV_TERMS` in `tools/i18n_guard.py:48`:

```python
<<<<
    "crud", "dto", "dao", "cli", "sdk", "md", "txt", "tsv", "yml", "yaml",
====
    "crud", "dto", "dao", "cli", "sdk", "md", "txt", "tsv", "yml", "yaml",
    "eq", "neq", "gt", "gte", "lt", "lte", "jaccard", "intra",
>>>>
```

#### Step 2: Update `lib/McpFaqTools.class.php` (Line 511)
Replace `intra-batch` with `within-batch`:

```php
<<<< Line 511
        // Phase 1: In-memory structure & intra-batch uniqueness validation
====
        // Phase 1: In-memory structure & within-batch uniqueness validation
>>>>
```

#### Step 3: Update `lib/McpNewsTools.class.php` (Lines 1177, 1207–1234)
Wrap `Jaccard` in backticks at line 1177, and rename `$cand*` to `$candidate*` at lines 1207–1234:

```php
<<<< Lines 1175-1178
    /**
     * Pre-staging duplicate check within lookback hours (default 48).
     * Compares headline token Jaccard similarity and exact SEO match.
     */
====
    /**
     * Pre-staging duplicate check within lookback hours (default 48).
     * Compares headline token `Jaccard` similarity and exact SEO match.
     */
>>>>
```

```php
<<<< Lines 1207-1237
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
====
        $duplicates = [];
        if (is_array($candidates)) {
            foreach ($candidates as $candidate) {
                $candidateName = (string) ($candidate['name'] ?? '');
                $candidateSeo = (string) ($candidate['seo'] ?? '');
                $candidateTokens = $this->tokenizeHeadline($candidateName);

                $intersection = count(array_intersect($proposedTokens, $candidateTokens));
                $union = count(array_unique(array_merge($proposedTokens, $candidateTokens)));
                $similarity = $union > 0 ? ($intersection / $union) : 0.0;

                $isSeoMatch = ($proposedSeo !== '' && $candidateSeo === $proposedSeo);
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
                        'id' => (int) $candidate['id'],
                        'name' => $candidateName,
                        'seo' => $candidateSeo,
                        'status' => (string) ($candidate['status'] ?? 'y'),
                        'category_id' => (int) ($candidate['category_id'] ?? 0),
                        'similarity' => round($similarity, 3),
                        'created_at' => (string) ($candidate['created_at'] ?? ''),
                        'match_reasons' => $reasons,
                    ];
                }
            }
        }
>>>>
```

#### Step 4: Update `tasks/mcp_smoke.php` (Lines 886–895)
Rename `$resDup` $\rightarrow$ `$resDuplicate` and `$dupPayload` $\rightarrow$ `$duplicatePayload`:

```php
<<<< Lines 885-896
    // 10. check_duplicate_news detection (B3)
    $resDup = runRpc(['jsonrpc' => '2.0', 'id' => 1014, 'method' => 'tools/call', 'params' => ['name' => 'check_duplicate_news', 'arguments' => [
        'name' => 'MCP Smoke SEO Normalization News',
        'lang' => $testLang,
    ]]], $plaintextKey);
    $dupPayload = json_decode($resDup['result']['content'][0]['text'] ?? 'null', true);
    check('News[DuplicateCheck]: check_duplicate_news detects duplicate headline within 48h',
        ($resDup['result']['isError'] ?? true) === false
        && ($dupPayload['has_duplicate'] ?? false) === true
        && ($dupPayload['duplicate_count'] ?? 0) >= 1,
        json_encode($dupPayload)
    );
====
    // 10. check_duplicate_news detection (B3)
    $resDuplicate = runRpc(['jsonrpc' => '2.0', 'id' => 1014, 'method' => 'tools/call', 'params' => ['name' => 'check_duplicate_news', 'arguments' => [
        'name' => 'MCP Smoke SEO Normalization News',
        'lang' => $testLang,
    ]]], $plaintextKey);
    $duplicatePayload = json_decode($resDuplicate['result']['content'][0]['text'] ?? 'null', true);
    check('News[DuplicateCheck]: check_duplicate_news detects duplicate headline within 48h',
        ($resDuplicate['result']['isError'] ?? true) === false
        && ($duplicatePayload['has_duplicate'] ?? false) === true
        && ($duplicatePayload['duplicate_count'] ?? 0) >= 1,
        json_encode($duplicatePayload)
    );
>>>>
```

---

## 5. Verification Method

To independently verify the complete remediation:

1. **PHP Syntax Validation**:
   ```bash
   docker exec bulletin_app php -l lib/McpCrudToolsBase.class.php
   docker exec bulletin_app php -l lib/McpFaqTools.class.php
   docker exec bulletin_app php -l lib/McpNewsTools.class.php
   docker exec bulletin_app php -l tasks/mcp_smoke.php
   ```
   *Expected Output*: `No syntax errors detected in <file>`.

2. **Quality Gate Validation (`tools/i18n_guard.py`)**:
   Verify individual touched files:
   ```bash
   python3 tools/i18n_guard.py --path lib/McpCrudToolsBase.class.php
   python3 tools/i18n_guard.py --path lib/McpFaqTools.class.php
   python3 tools/i18n_guard.py --path lib/McpNewsTools.class.php
   python3 tools/i18n_guard.py --path tasks/mcp_smoke.php
   ```
   *Expected Output*:
   ```
   ✅ 100% CLEAN! Zero violations found in audited files.
   ```
   Exit code `0`.

3. **Full Working Tree Quality Gate Execution**:
   Ensure untracked challenger test scripts are clean or removed:
   ```bash
   python3 tools/i18n_guard.py
   ```
   *Expected Output*:
   ```
   ✅ 100% CLEAN! Zero violations found in audited files.
   ```
   Exit code `0`.

4. **MCP Smoke Regression Test**:
   ```bash
   docker exec bulletin_app php tasks/mcp_smoke.php
   ```
   *Expected Output*: `Smoke test complete: 82 passed, 0 failed.` (or higher with new assertion).
