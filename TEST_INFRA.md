# Comprehensive E2E Testing Infrastructure: Kevanger Editorial Pipeline Remediation (v1.0.63)

**Target Domain:** `https://amedbulten.com`  
**Repository:** `AF/Bulletin`  
**Target Release:** `v1.0.63`  
**Architecture Standard:** Amed Framework Core (Opaque-Box Testing, Dual-Track Quality Guard, Wikipedia-Style URL Parity, Strict Code Separation)

---

## 1. Executive Overview & Testing Philosophy

The v1.0.63 release of `AF/Bulletin` delivers crucial architectural fixes and functional enhancements across the entire journalism editorial pipeline. This spans MCP CRUD tools, ORM conjunctive filtering, verified TLS HTTP transport, same-day freshness sentinels, fact-checking and entity anchoring, Next.js image unwrapping, mod_news media credit schemas, Swarm CLI stdio bridges, and RSS news radar.

To guarantee zero regression across the system and absolute architectural integrity, testing is executed under the **Dual Track Project Pattern**:
- **Track A (Implementation Track)**: Engineers and agents implement features strictly against architectural contracts.
- **Track B (Testing Track)**: Engineers and agents construct an independent, completely opaque-box test suite covering all 36 features across 4 testing tiers.
- **Opaque-Box Contract**: Tests interact with components exclusively through their public surfaces (CLI commands, HTTP requests, MCP JSON-RPC envelopes, Twig render outputs, and database state). Tests never monkey-patch internal methods, tamper with private state, or bypass validation gates.

---

## 2. Complete 36-Feature Inventory & Coverage Matrix

| # | Feature Key | Layer / Component | Description | Tier 1 (Happy Path) | Tier 2 (Boundaries) | Tier 3 (Cross-Module) | Tier 4 (Workflows) |
|:---:|:---|:---|:---|:---:|:---:|:---:|:---:|
| **1** | `orm_conjunctive_filters` | `Orm.php`, `McpCrudToolsBase` | Conjunctive filtering via `$model->addWhere()` avoiding B1 filter clobbering | ≥5 tests | ≥5 tests | ✓ | ✓ |
| **2** | `mcpserver_db_fetch` | `McpServer.class.php` | `resolveEnvironment()` uses `Database::fetch_array()` instead of undefined `fetch()` | ≥5 tests | ≥5 tests | ✓ | ✓ |
| **3** | `audit_meta_draft_faqs` | `McpCrudToolsBase.class.php` | `getRecordAuditMeta()` uses `Database::fetch_array()` and counts draft FAQs (`status='n'`) | ≥5 tests | ≥5 tests | ✓ | ✓ |
| **4** | `headline_type_assertion` | `McpHeadlineTools.class.php` | Pass `$name` as string to `assertContentMatchesLang` to prevent TypeError | ≥5 tests | ≥5 tests | ✓ | - |
| **5** | `leaf_category_check` | `McpNewsTools.class.php` | Rejection of root categories (`root === 'y'`) during news staging | ≥5 tests | ≥5 tests | ✓ | ✓ |
| **6** | `mandatory_make_seo` | `McpNewsTools.class.php` | Mandatory `makeSeo()` with Kurdish Latin preservation and `U+0307` dot-above strip | ≥5 tests | ≥5 tests | ✓ | ✓ |
| **7** | `get_news_enrichment` | `McpNewsTools.class.php` | Return `username`, `author_fullname`, `category_name`, and `category_seo` | ≥5 tests | ≥5 tests | ✓ | ✓ |
| **8** | `check_duplicate_news` | `McpNewsTools.class.php` | Pre-staging duplicate news check within last 48 hours based on title and content | ≥5 tests | ≥5 tests | ✓ | ✓ |
| **9** | `batch_create_faqs` | `McpFaqTools.class.php` | Atomic creation of up to 25 FAQs for host record | ≥5 tests | ≥5 tests | ✓ | ✓ |
| **10** | `list_faq_modules` | `McpFaqTools.class.php` | List valid FAQ host modules from `mod_faq_mod` | ≥5 tests | ≥5 tests | ✓ | - |
| **11** | `list_faqs_include_answer` | `McpFaqTools.class.php` | Support `include_answer: bool` parameter to return full FAQ answers | ≥5 tests | ≥5 tests | ✓ | ✓ |
| **12** | `nginx_well_known` | `docker/nginx/prod.conf` | `location ^~ /.well-known/` passthrough preceding `location ~ /\. ` for RFC 8414/9728 | ≥5 tests | ≥5 tests | ✓ | - |
| **13** | `editorial_http_client` | `editorial_http.py` | Verified TLS, SSRF guard, browser headers, Retry-After backoff, 10m cache | ≥5 tests | ≥5 tests | ✓ | ✓ |
| **14** | `freshness_tls_ssrf` | `check_freshness.py` | Integration with `editorial_http` for verified TLS and private IP SSRF blocking | ≥5 tests | ≥5 tests | ✓ | ✓ |
| **15** | `freshness_iso_fractional` | `check_freshness.py` | Parsing fractional seconds `(?:[.,]\d+)?` preserving UTC / timezone offset | ≥5 tests | ≥5 tests | ✓ | - |
| **16** | `freshness_tag_strip` | `check_freshness.py` | Non-content tag stripping (`<script>`, `<style>`, `<aside>`, `<nav>`, `<footer>`) | ≥5 tests | ≥5 tests | ✓ | ✓ |
| **17** | `freshness_earliest_date` | `check_freshness.py` | Earliest main entity publication date selection and audit URL deduplication | ≥5 tests | ≥5 tests | ✓ | ✓ |
| **18** | `freshness_near_midnight` | `check_freshness.py` | Structured `age_hours` and near-midnight diagnostic attributes in JSON output | ≥5 tests | ≥5 tests | ✓ | ✓ |
| **19** | `facts_all_paragraphs` | `verify_facts_and_links.py` | Evaluate all citing paragraphs across the draft in `audit_draft()` | ≥5 tests | ≥5 tests | ✓ | ✓ |
| **20** | `facts_clean_numbers` | `verify_facts_and_links.py` | Strip non-content tags before R3; enforce exact normalized numeric equality | ≥5 tests | ≥5 tests | ✓ | ✓ |
| **21** | `facts_vendor_allowlist` | `verify_facts_and_links.py` | Allow defined single-segment vendor download/release pages under criteria | ≥5 tests | ≥5 tests | ✓ | ✓ |
| **22** | `facts_cross_language` | `verify_facts_and_links.py` | Support `source_lang` and two-sided `source_anchors` in `dossier.schema.json` | ≥5 tests | ≥5 tests | ✓ | ✓ |
| **23** | `facts_turkish_morphology`| `verify_facts_and_links.py` | `tr_fold()` Unicode folding and institutional suffix stemmer; expanded attribution | ≥5 tests | ≥5 tests | ✓ | ✓ |
| **24** | `image_nextjs_unwrap` | `process_news_image.py` | Unwrap Next.js `/_next/image?url=...` and CDN proxies before deduplication | ≥5 tests | ≥5 tests | ✓ | ✓ |
| **25** | `image_hero_scoring` | `process_news_image.py` | Restrict hero bonus to primary article elements; cap inflated srcset at 3840w | ≥5 tests | ≥5 tests | ✓ | ✓ |
| **26** | `image_mode_master` | `process_news_image.py` | Clean `>=1200px` unflattened master image output preserving vertical aspect | ≥5 tests | ≥5 tests | ✓ | ✓ |
| **27** | `mod_news_image_columns` | `mod_news`, `newses.model` | Add `image_alt` and `image_credit` columns to schema, model, and controllers | ≥5 tests | ≥5 tests | ✓ | ✓ |
| **28** | `twig_hero_figure_jsonld` | `news.twig` | Render `<figure class="detail-hero">` + `<figcaption>` and JSON-LD schema credits | ≥5 tests | ≥5 tests | ✓ | ✓ |
| **29** | `mcp_cli_bridge` | `tools/mcp_cli.py` | Secret-opaque stdio CLI bridge with `--editorial-safe` mutation guards | ≥5 tests | ≥5 tests | ✓ | ✓ |
| **30** | `source_catalog_scan_today`| `source_catalog.py` | Live RSS/Atom/sitemap scanning for same-day local primary discovery | ≥5 tests | ≥5 tests | ✓ | ✓ |
| **31** | `audit_seo_alignment` | `audit_seo.py` | Enforce headline 40-70, summary 90-120, description sync, heading hierarchy | ≥5 tests | ≥5 tests | ✓ | ✓ |
| **32** | `skill_docs_sync` | `SKILL.md` (5 skills) | Synchronize Kevanger, Kevtemen, Kevkolin, Kevrast, Kevseo SKILL.md docs | ≥5 tests | ≥5 tests | - | - |
| **33** | `agents_rule_citations` | Repo Guards, `AGENTS.md` | Fix citations in `kevseo/SKILL.md`, `audit_seo.py`, `code_separation_guard.py` | ≥5 tests | ≥5 tests | - | - |
| **34** | `regression_suite_smoke` | `tasks/mcp_smoke.php` | Update `tasks/mcp_smoke.php` and `tools/verify_news_pipeline.sh` Stage 7 | ≥5 tests | ≥5 tests | ✓ | ✓ |
| **35** | `version_doc_update` | `docs/VERSION.md` | Update `docs/VERSION.md` under v1.0.63 and synchronize version parity | ≥5 tests | ≥5 tests | - | ✓ |
| **36** | `git_release_tag_push` | Git release process | Commit changes, tag release v1.0.63, and push directly to GitHub | ≥5 tests | ≥5 tests | - | ✓ |

---

## 3. Test Runner Specification (`tests/e2e/run_e2e_tests.py`)

### Usage & Syntax
```bash
python3 tests/e2e/run_e2e_tests.py [OPTIONS]
```

### CLI Arguments
- `--tier <1|2|3|4|all>`: Filter tests by tier level (default: `all`).
  - `1`: Tier 1 Feature Coverage (Happy path, ≥5 tests per feature).
  - `2`: Tier 2 Boundary & Corner Cases (Negative, stress, edge conditions).
  - `3`: Tier 3 Cross-Feature Combinations (Pairwise and cross-module interactions).
  - `4`: Tier 4 Real-World Application Workflows (Full pipeline intake-to-staging scenarios).
  - `all`: Runs tiers 1 through 4 sequentially.
- `--feature <id>` / `-f <id>`: Filter by feature number (1 through 36, or comma-separated list `1,2,5`, or range `1-12`).
- `--format <console|json|tap|junit>`: Output format (default: `console`).
  - `console`: Rich colorized terminal summary with progress indicator, table, and failure traces.
  - `tap`: TAP (Test Anything Protocol) version 13 format.
  - `junit`: Standard JUnit XML report for CI/CD integration.
  - `json`: Structured JSON result envelope.
- `--output <path>` / `-o <path>`: Write formatted output report to specified file instead of stdout.
- `--verbose` / `-v`: Display verbose logging, subprocess streams, and diagnostic traces.
- `--fail-fast` / `-x`: Abort execution immediately upon the first assertion failure.
- `--no-network`: Run tests in offline mode using mock HTTP server and cached fixtures.
- `--timeout <sec>`: Maximum execution duration per test case (default: 30s).
- `--list`: List all discovered test cases without executing them.
- `--help` / `-h`: Show usage information.

### Exit Codes
- `0`: All executed test cases PASSED.
- `1`: One or more test cases FAILED or errored.
- `2`: CLI argument error or test harness initialization failure.

---

## 4. Test Directory Layout

```
tests/e2e/
├── run_e2e_tests.py                         # Top-level executable test runner
├── harness/                                 # Shared harness components
│   ├── __init__.py                          # Package exports
│   ├── base.py                              # Base test case, metadata decorators, custom assertions
│   ├── runner.py                            # Test discovery, scheduler, execution engine, timeouts
│   ├── reporters.py                         # Console, TAP, JUnit XML, JSON formatters
│   ├── process.py                           # Subprocess execution wrapper (CLI, Python, PHP)
│   ├── php_bridge.py                        # PHP execution bridge (loads McpServer, ORM, evaluations)
│   ├── mock_http.py                         # Embedded mock HTTP server for deterministic offline tests
│   ├── sandbox.py                           # Temporary filesystem sandbox manager
│   └── assertions.py                        # Domain-specific assertions
├── fixtures/                                # Static and dynamic test fixtures
│   ├── editorial/                           # Editorial drafts, dossiers, mock HTML
│   ├── images/                              # Test images for intake pipeline
│   ├── feeds/                               # RSS / Sitemap feed XML fixtures
│   ├── mcp/                                 # MCP JSON-RPC mock payloads
│   └── php_seeds/                           # PHP Database mock fixtures
├── tier1/                                   # Tier 1: Feature Coverage (36 test modules, ≥5 tests / feature)
├── tier2/                                   # Tier 2: Boundary & Corner Cases (7 test modules, ≥5 tests / feature)
├── tier3/                                   # Tier 3: Cross-Feature Combinations (5 integration test modules)
└── tier4/                                   # Tier 4: Real-World Scenarios (5 full workflow test modules)
```

---

## 5. Opaque-Box Component Interface Contracts

1. **Python Editorial Pipeline Scripts**:
   - `check_freshness.py`: Subprocess execution. Asserts exit code `0` (PASSED) vs `1` (BLOCKED). Asserts JSON attributes (`age_hours`, `is_yesterday_late_evening`). Asserts zero SSRF to private IP ranges.
   - `verify_facts_and_links.py`: Subprocess execution with `--file` and `--dossier`. Asserts all citing paragraphs are audited. Asserts R2 vendor single-segment allowlist with version string. Asserts R3 two-sided cross-language anchoring (`source_lang` + `source_anchors`). Asserts Turkish institutional morphology (`Belediyesine` -> `Belediyesi`). Asserts exit code `1` on hallucination in Paragraph 4.
   - `process_news_image.py`: Subprocess execution with `--from-article` and `--mode master`. Asserts `/_next/image` unwrapping before ranking. Asserts hero score beats inflated `3840w` sidebar thumbnail. Asserts master image `>=1200px` without pre-flattening.
   - `source_catalog.py`: Subprocess execution with `--scan-today`. Asserts output JSON contains verified same-day articles from RSS/sitemap feeds.
   - `audit_seo.py`: Subprocess execution with `--file`, `--headline`, `--summary`. Asserts exit code `0` when headline 40-70, summary 90-120, `>=3 <h2>`. Asserts exit code `1` on violation.
   - `tools/mcp_cli.py`: Subprocess execution. Asserts stdio bridge communication without credential leaks. Asserts `--editorial-safe` blocks `delete_*` and enforces `status="n"` on staging.

2. **PHP Backend & MCP / ORM**:
   - Tested through `php_bridge.py` invoking PHP CLI test scripts.
   - `McpCrudToolsBase::addEqFilter` & `addLikeSearchFilter`: Asserts conjunctive `AND` chaining via `$model->addWhere()` without clobbering.
   - `McpServer::resolveEnvironment`: Asserts `Database::fetch_array()` execution without undefined `fetch()` error.
   - `McpCrudToolsBase::getRecordAuditMeta`: Asserts draft FAQ counting (`recordStatus = 'n'`).
   - `McpNewsTools`: Asserts root category rejection (`root === 'y'`), mandatory `makeSeo()` with Kurdish Latin preservation and `U+0307` strip, enriched `get_news` payload (`username`, `author_fullname`, `category_name`, `category_seo`), and `check_duplicate_news`.
   - `McpFaqTools`: Asserts `batch_create_faqs`, `list_faq_modules`, and `list_faqs(include_answer=true)`.

3. **Templates & Views**:
   - `news.twig`: Rendered with Twig test harness. Asserts `<figure class="detail-hero">`, `<figcaption class="news-hero-caption">{{ d.image_credit }}</figcaption>`, and JSON-LD `NewsArticle` schema credits.
   - Quality guards: `python3 tools/code_separation_guard.py` asserts zero inline styles or scripts (`AGENTS.md` Rule 1).

4. **Nginx Production Configuration**:
   - `docker/nginx/prod.conf`: Static parser asserts `location ^~ /.well-known/` precedes `location ~ /\.`. Asserts RFC 8414/9728 endpoints are reachable.

---

## 6. Determinism, Mocking & Sandboxing

- **Network Isolation (`--no-network`)**: An embedded Python HTTP server (`tests/e2e/harness/mock_http.py`) runs on localhost, serving static HTML fixtures, Next.js image payloads, and simulated rate-limiting (`429` with `Retry-After: 2`). Tests execute offline in CI/CD without external HTTP requests.
- **Filesystem Isolation (`tests/e2e/harness/sandbox.py`)**: All temporary files, generated dossiers, resized WebP images, and JSON reports are generated inside isolated sandbox directories (`/tmp/af_e2e_sandbox_XXXX/`) and automatically wiped on test teardown, preventing any repository contamination.
- **Database Isolation (`tests/e2e/harness/MockDb.php`)**: PHP tests evaluate model queries and MCP CRUD operations in-memory or via SQLite, removing MySQL container startup dependencies.

---

## 7. Quality Thresholds & Acceptance Criteria

- **Tier 1 (Feature Coverage)**: ≥5 test cases per feature across all 36 features = **≥180 tests** (Happy Path).
- **Tier 2 (Boundary & Corner Cases)**: ≥5 test cases per feature across all 36 features = **≥180 tests** (Negative & Boundary).
- **Tier 3 (Cross-Feature Combinations)**: ≥10 comprehensive integration tests verifying cross-module data flow.
- **Tier 4 (Real-World Scenarios)**: ≥5 complete end-to-end editorial workflow scenarios.
- **Total Test Count**: **≥375 tests**.
- **Exit Code**: `0` on all tests passing.
- **Quality Guards**: 0 violations on `code_separation_guard.py`, `i18n_guard.py`, `module_boundary_guard.py`, `min_css_guard.py`.

---

## 8. Execution Recipes & Verification Commands

```bash
# 1. Run all tests across all tiers in console mode
python3 tests/e2e/run_e2e_tests.py

# 2. Run Tier 1 Feature Coverage tests
python3 tests/e2e/run_e2e_tests.py --tier 1

# 3. Run Tier 2 Boundary & Corner tests
python3 tests/e2e/run_e2e_tests.py --tier 2

# 4. Run tests for a specific feature (e.g. Feature 24: Next.js image unwrap)
python3 tests/e2e/run_e2e_tests.py --feature 24

# 5. Run tests offline with TAP output format
python3 tests/e2e/run_e2e_tests.py --no-network --format tap

# 6. Generate JUnit XML for CI pipeline
python3 tests/e2e/run_e2e_tests.py --format junit --output tests/e2e/report.xml

# 7. Generate structured JSON report
python3 tests/e2e/run_e2e_tests.py --format json --output tests/e2e/report.json
```
