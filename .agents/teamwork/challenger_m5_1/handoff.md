# Milestone 5 Adversarial Challenger Report: Verification, Quality Guards & Release Integrity

## Verdict: `REQUEST_CHANGES`

**Overall Risk Assessment**: MEDIUM

A critical release-integrity defect was identified empirically: git tag `v1.0.63` was created as a **lightweight tag** (`type commit`) instead of an **annotated tag** (`type tag`), which violates the repository release contract and causes 3 test failures in `tests/e2e/tier1/test_f36_git_release_tag_push.py` and causes `python3 tests/e2e/run_e2e_tests.py` to exit with failure code 1.

---

## 1. Observation

### 1.1 Git Tag Type Failure (`test_f36_git_release_tag_push.py`)
- **Command**:
  ```bash
  PYTHONPATH=tests/e2e python3 -m unittest tests/e2e/tier1/test_f36_git_release_tag_push.py
  ```
- **Verbatim Error**:
  ```text
  .FFF.
  ======================================================================
  FAIL: test_f36_git_release_tag_v1_0_63_readiness (tests.e2e.tier1.test_f36_git_release_tag_push.TestF36GitReleaseTagPush.test_f36_git_release_tag_v1_0_63_readiness)
  Validates that v1.0.63 tag is either prepared or pending Milestone M5 release.
  ----------------------------------------------------------------------
  Traceback (most recent call last):
    File "/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/tests/e2e/tier1/test_f36_git_release_tag_push.py", line 77, in test_f36_git_release_tag_v1_0_63_readiness
      self.assertEqual(type_res.stdout.strip(), "tag")
  AssertionError: 'commit' != 'tag'
  - commit
  + tag

  ======================================================================
  FAIL: test_f36_git_tag_annotated (tests.e2e.tier1.test_f36_git_release_tag_push.TestF36GitReleaseTagPush.test_f36_git_tag_annotated)
  Release tag is an annotated Git tag object (type 'tag'), not a lightweight ref.
  ----------------------------------------------------------------------
  Traceback (most recent call last):
    File "/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/tests/e2e/tier1/test_f36_git_release_tag_push.py", line 45, in test_f36_git_tag_annotated
      self.assertEqual(res.stdout.strip(), "tag")
  AssertionError: 'commit' != 'tag'
  - commit
  + tag

  ======================================================================
  FAIL: test_f36_git_tag_message_contains_version_summary (tests.e2e.tier1.test_f36_git_release_tag_push.TestF36GitReleaseTagPush.test_f36_git_tag_message_contains_version_summary)
  Annotated release tag includes tagger details and release message.
  ----------------------------------------------------------------------
  Traceback (most recent call last):
    File "/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/tests/e2e/tier1/test_f36_git_release_tag_push.py", line 62, in test_f36_git_tag_message_contains_version_summary
      self.assertIn("tagger", content)
  AssertionError: 'tagger' not found in 'tree 90850b87c477cee4adfb229df369b60af6519252\nparent f6a914e137b7622aea2e123008e691b90231afb7\nauthor Rêzan Karaozan <a.karaozan@gmail.com> 1794405561 +0300\ncommitter Rêzan Karaozan <a.karaozan@gmail.com> 1794405561 +0300\n\nRelease v1.0.63: Kevanger editorial pipeline remediation and enhancement\n'

  ----------------------------------------------------------------------
  Ran 5 tests in 0.099s
  FAILED (failures=3)
  ```
- **Git Object Inspection**:
  - `git cat-file -t v1.0.63` -> `commit` (lightweight tag pointing directly to commit).
  - Contrasted with predecessor tag: `git cat-file -t v1.0.62` -> `tag` (annotated tag with tagger details, timestamp, and release message).

### 1.2 Full E2E Test Suite Execution (`tests/e2e/run_e2e_tests.py`)
- **Command**: `python3 tests/e2e/run_e2e_tests.py`
- **Output**:
  ```text
  === Amed Framework Bulletin E2E Test Suite Run ===
  --- Tier Summary ---
    Tier 1: Total=187 | Passed=184 | Failed=  3 | Skipped=  0
  OVERALL STATUS: FAILED (Total: 187, Failed: 3, Errors: 0, Passed: 184, Duration: 39.82s)
  ```
- All 3 failures are isolated strictly to `test_f36_git_release_tag_push.py`.

### 1.3 Version Parity & PHP CLI Configuration Extraction
- **Command**: `PYTHONPATH=tests/e2e python3 -m unittest tests/e2e/tier1/test_f35_version_doc_update.py`
  - Output: Ran 5 tests in 0.181s -> `OK` (100% pass).
- **Direct PHP CLI extraction**:
  - Initial naive command from dispatch (`php -r "require 'config/site.config.php'; echo \$site['version'];"`):
    - Output: `:)`
    - Root cause in `config/site.config.local.php:8`: `if (defined('root_dir') === False) exit(':)');`.
    - In addition, `config/site.config.php:130-131` stores `$site` into `Registry::add('site_config', $site)` and then executes `unset($site);`.
  - Framework-compliant CLI command:
    ```bash
    php -r "define('root_dir', __DIR__ . '/'); require 'lib/Registry.class.php'; require 'config/site.config.php'; echo Registry::get('site_config')['version'];"
    ```
    - Output: `1.0.63` (Exact match).
  - Example config CLI extraction:
    ```bash
    php -r "define('root_dir', __DIR__ . '/'); require 'lib/Registry.class.php'; require 'config/site.config.example.php'; echo Registry::get('site_config')['version'];"
    ```
    - Output: `1.0.63` (Exact match).
  - Version file: `docs/VERSION.md:3` defines `## [v1.0.63] - 2026-11-11` with comprehensive notes for all Phases 1–5.
  - Server documentation: `docs/SERVER.md:3` defines `- **Version:** `v1.0.63` (2026-11-11)`.
  - Regex stress test against `^##\s*\[?v?([0-9]+(?:\.[0-9]+)+)` passed on 10 boundary cases (ignoring `(Planned)`, `(In Progress)`).

### 1.4 Quality Guard Execution
- **`tools/i18n_guard.py`**:
  - Tested on `mod/mod_news`: 16 files audited, 0 violations, 100% clean.
- **`tools/code_separation_guard.py`**:
  - Tested on milestone views:
    - `templates/admin/mod_news/edit.twig`: 0 violations.
    - `templates/admin/partials/wizard_content.twig`: 0 violations.
    - `templates/amed/index/mod_news/news.twig`: 0 violations.
    - `templates/admin/mod_news`: 0 violations across 3 files.
    - `templates/amed/index/mod_news`: 0 violations across 13 files.
- **`tools/module_boundary_guard.py --all`**:
  - 11,017 files scanned, 18 forbidden modules checked, 0 violations, `PASSED`.
- **`tools/min_css_guard.py`**:
  - 4 files checked (custom, hero-news-slider, homepage-bundle, homepage-all), 0 missing or stale rules, `[OK]`.

### 1.5 Pipeline & Smoke Verification
- **Docker MCP Smoke (`tasks/mcp_smoke.php`)**:
  - Command: `docker exec bulletin_app php tasks/mcp_smoke.php`
  - Output: 83 passed, 0 failed (100%).
- **MCP Health Check (`tools/mcp_health_check.py`)**:
  - Command: `python3 tools/mcp_health_check.py --endpoint http://localhost:8080/mcp.php`
  - Output: 24 passed, 0 failed (100%), including RFC 8414 and RFC 9728 discovery unmasked.
- **Regression Suite Smoke (`test_f34_regression_suite_smoke.py`)**:
  - Command: `PYTHONPATH=tests/e2e python3 -m unittest tests/e2e/tier1/test_f34_regression_suite_smoke.py`
  - Output: 5 passed, 0 failed.
- **Observation on Worker Handoff Command**:
  - Worker's handoff listed a manual command referencing `--draft tests/pipeline_valid_draft.html`. That file does not exist on disk as a static fixture (it is created inside a temporary sandbox by `test_f34`).

---

## 2. Logic Chain

1. **Observation 1.1** proves that git tag `v1.0.63` is a lightweight commit reference (`git cat-file -t v1.0.63` returns `commit`), whereas repository release policy and test contract `test_f36_git_release_tag_push.py` require an annotated tag (`type tag`).
2. Because `v1.0.63` is lightweight, it does not store tagger metadata (`tagger`) or an annotated release summary.
3. This causes 3 tests in `test_f36_git_release_tag_push.py` to fail, which in turn causes the comprehensive test suite `python3 tests/e2e/run_e2e_tests.py` to fail with exit code 1 (**Observation 1.2**).
4. All other milestone deliverables (**Observations 1.3, 1.4, 1.5**) are structurally sound and verified empirically:
   - Version parity at `1.0.63` is consistent across all 4 files (`docs/VERSION.md`, `config/site.config.php`, `config/site.config.example.php`, `docs/SERVER.md`).
   - All quality guards pass with 0 errors across affected scopes.
   - All 83 MCP smoke tests in Docker pass.
   - MCP health check passes 24/24 stages.
5. In accordance with the Review-Only constraint, the Challenger does not mutate the git repository or force-tag remote branches. Therefore, the Challenger issues an actionable `REQUEST_CHANGES` verdict specifying the exact remediation needed to make the release 100% compliant and green.

---

## 3. Caveats

- The git working tree has unstaged files solely within `.agents/teamwork/` metadata directories, which is expected during multi-agent team execution. Zero tracked source or configuration files are dirty.
- Running `code_separation_guard.py` without arguments when git status is clean triggers a fallback scan of legacy files under `templates/admin`, reporting historical violations outside the scope of Milestone 5. Scoped execution on files touched in v1.0.63 confirmed 100% compliance.
- No other caveats.

---

## 4. Conclusion

**Verdict**: `REQUEST_CHANGES`

Milestone 5 is 95% complete and high quality, but blocked on release tagging integrity.
To resolve this defect, the following single remediation step is required:

### Actionable Remediation:
Re-tag `v1.0.63` as an **annotated tag** with release message, and force update the remote tag:
```bash
git tag -a v1.0.63 -m "Release v1.0.63: Kevanger editorial pipeline remediation and enhancement" -f
git push origin v1.0.63 -f
```
Once executed:
- `PYTHONPATH=tests/e2e python3 -m unittest tests/e2e/tier1/test_f36_git_release_tag_push.py` will pass 5/5.
- `python3 tests/e2e/run_e2e_tests.py` will pass 187/187 (100% PASS).

---

## 5. Verification Method

To verify the failure independently:
```bash
# 1. Reproduce failing tag tests
PYTHONPATH=tests/e2e python3 -m unittest tests/e2e/tier1/test_f36_git_release_tag_push.py

# 2. Inspect git object type (returns 'commit', expects 'tag')
git cat-file -t v1.0.63

# 3. Verify framework version extraction in PHP CLI
php -r "define('root_dir', __DIR__ . '/'); require 'lib/Registry.class.php'; require 'config/site.config.php'; echo Registry::get('site_config')['version'];"
```
