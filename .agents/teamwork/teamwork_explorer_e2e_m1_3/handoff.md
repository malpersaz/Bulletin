# Handoff Report: E2E Test Suite Architecture & Test Runner Design

**State Folder**: `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_explorer_e2e_m1_3/`  
**Date**: 2026-11-11  
**Milestone**: E2E-M1 (Test Infrastructure & Framework Setup)  
**Agent**: `teamwork_explorer_e2e_m1_3` (Explorer for Test Architecture & Runner Design)

---

## 1. Observation

Direct examination of the workspace, codebase, dependencies, and configuration revealed the following ground truths:

1. **System & Runtime Environment**:
   - Python: `Python 3.14.4` (`python3 --version`).
   - PHP: `PHP 8.5.4 (cli) (built: Oct 19 2026 14:35:18) (NTS)` with Zend OPcache v8.5.4.
   - Python packages: `PIL/Pillow` is installed and functional; `pytest 9.0.2` is installed (`python3 -m pytest --version`).
   - Database/Container: Amed Framework runs against MySQL via `Database` / `Adapter_Mysqli` or in-memory MockDb (`tests/e2e/harness/MockDb.php`).

2. **Existing Testing Artifacts**:
   - `tests/e2e/run.php`: A legacy PHP runner created for v1.0.60 covering 21 features across 6 tiers (`Tier0QualityContractsTest.php` through `Tier5AdversarialHardeningTest.php`).
   - `docs/TEST_INFRA.md`: Documents the legacy v1.0.60 21-feature inventory. No `TEST_INFRA.md` or `TEST_READY.md` currently exists at the project root (`TEST_INFRA.md: No such file or directory` at root).
   - `tasks/mcp_smoke.php`: 710 lines of CLI JSON-RPC smoke testing utilizing `SmokeMcpServer extends McpServer` with injectable body and mock request environments.
   - `tools/verify_news_pipeline.sh`: 168 lines orchestrating a 4-stage pipeline check (`check_freshness.py` -> `audit_seo.py` -> `verify_facts_and_links.py` -> `audit_content_seo.py`).
   - `tools/mcp_health_check.py`: Automated stdio-bridge probe for MCP server health.

3. **Component Distribution for v1.0.63**:
   - **Python CLI Tools & Pipeline Scripts**:
     - `.agents/skills/kevtemen/scripts/check_freshness.py`
     - `.agents/skills/kevrast/scripts/verify_facts_and_links.py`
     - `.agents/skills/kevanger/scripts/process_news_image.py`
     - `.agents/skills/kevkolin/scripts/source_catalog.py`
     - `.agents/skills/kevseo/scripts/audit_seo.py`
     - `.agents/skills/_shared/editorial_http.py` (M2 component)
     - `tools/mcp_cli.py` (M3 component)
     - `tools/audit_content_seo.py`, `tools/code_separation_guard.py`, `tools/i18n_guard.py`, `tools/module_boundary_guard.py`, `tools/min_css_guard.py`.
   - **PHP Backend & MCP / ORM Layer**:
     - MCP Classes: Located at `lib/Mcp*.class.php` (specifically `lib/McpServer.class.php`, `lib/McpCrudToolsBase.class.php`, `lib/McpNewsTools.class.php`, `lib/McpHeadlineTools.class.php`, `lib/McpFaqTools.class.php`, `lib/McpMenuTools.class.php`, etc. — note: `PROJECT.md` line 4 references `lib/mcp/`, but files reside in `lib/Mcp*.class.php`).
     - Core Database & ORM: `lib/Database/`, `lib/Orm/Orm.php`, `mod/mod_news/`.
   - **Templates & Views**:
     - `templates/amed/index/mod_news/news.twig`, `templates/admin/mod_news/`.
   - **Infrastructure**:
     - `docker/nginx/prod.conf`.

4. **Scope & Inventory Requirements (`e2e_orch/SCOPE.md` & `PROJECT.md`)**:
   - 36 inventoried features requiring end-to-end opaque-box verification across 4 distinct tiers:
     - Tier 1: Feature Coverage (happy path, >=5 tests per feature across all 36 features = >=180 tests).
     - Tier 2: Boundary & Corner Cases (stress, edge values, negative conditions, >=5 tests per feature = >=180 tests).
     - Tier 3: Cross-Feature Combinations (pairwise interactions across modules).
     - Tier 4: Real-World Scenarios (complete editorial workflows from intake to staging and SEO audit).
   - Unified runner invocation: `python3 tests/e2e/run_e2e_tests.py [--tier 1|2|3|4|all] [--feature <id>]`.
   - Output specifications: TAP (Test Anything Protocol), JUnit XML, structured JSON, and console summaries.
   - Exit code: 0 on all tests passing, non-zero on any failure.
   - Publish `TEST_INFRA.md` and `TEST_READY.md` at project root.

---

## 2. Logic Chain

1. **Dual Track Architecture & Test Independence**:
   - The editorial remediation in v1.0.63 involves a heterogeneous stack: Python stdlib scripts for the editorial pipeline, PHP 8.5 for the Amed Framework MCP/ORM backend, Twig for templates, and Nginx for routing.
   - A Python-based unified test runner (`python3 tests/e2e/run_e2e_tests.py`) is the ideal orchestrator because it can natively execute Python pipeline tests, invoke CLI commands, control external processes (`process.py`), spawn embedded mock HTTP servers (`mock_http.py`), run PHP CLI bridge scripts (`php_bridge.py`), and format TAP/JUnit/JSON outputs without requiring any heavy third-party framework or modifying production code.
2. **Opaque-Box Requirement-Driven Contract**:
   - Tests must treat every component as a black box:
     - Pipeline scripts are invoked via CLI subprocesses (`python3 ...`), testing exit codes (`0`, `1`, `2`), stdout JSON envelopes, and output file artifacts.
     - Backend MCP and ORM methods are invoked via PHP CLI test bridges or `tools/mcp_cli.py`, asserting against returned JSON-RPC envelopes and database row states, never inspecting private class state.
     - Twig templates are rendered and evaluated for structural HTML semantics (`<figure>`, `<figcaption>`, JSON-LD Schema.org) and verified with `code_separation_guard.py` for zero inline CSS/JS (`AGENTS.md` Rule 1).
3. **Deterministic Offline Execution**:
   - External media sites (e.g. `rudaw.net`, `diyarbakir.bel.tr`, `antigravity.google`) can experience network latency, DNS failures, or rate-limiting (`429`).
   - The test harness must include an embedded, zero-dependency Python HTTP mock server (`mock_http.py`) and static fixtures (`fixtures/editorial/`) so all 36 features can be tested offline deterministically (`--no-network`).
4. **Project Root Artifact Contract**:
   - Per Project Pattern requirements, `TEST_INFRA.md` must be drafted and published at the project root (`TEST_INFRA.md`) as the authoritative contract before test suites (Tiers 1-4) are written by Worker agents.

---

## 3. Caveats

- **PHP File Path Discrepancy**: `PROJECT.md` line 4 references `lib/mcp/McpCrudToolsBase.php` and `lib/mcp/McpServer.php`, but actual repository files reside in `lib/McpCrudToolsBase.class.php` and `lib/McpServer.class.php`. The test harness PHP bridge must target `lib/Mcp*.class.php`.
- **Database Dependency in MCP Tests**: While `tasks/mcp_smoke.php` uses `init.php` and connects to MySQL, unit and boundary tests in the E2E suite should leverage `MockDb` (`tests/e2e/harness/MockDb.php`) or SQLite in-memory fixtures so tests can execute without requiring an active MySQL daemon.
- **Read-Only Scope**: This agent is strictly read-only and does not create or modify files in `tests/` or `TEST_INFRA.md`. The blueprints and draft content provided below are designed for the Worker agent to implement directly.

---

## 4. Conclusion & Architecture Proposal

### 4.1. Unified Test Runner Architecture (`tests/e2e/run_e2e_tests.py`)

#### Command-Line Interface Specification
```bash
python3 tests/e2e/run_e2e_tests.py [OPTIONS]
```

**Options**:
- `--tier <1|2|3|4|all>`: Filter tests by tier level. Default: `all`.
  - `1`: Tier 1 Feature Coverage (happy path, >=5 tests per feature).
  - `2`: Tier 2 Boundary & Corner Cases (edge values, error paths, negative inputs).
  - `3`: Tier 3 Cross-Feature Combination Suite (pairwise interactions).
  - `4`: Tier 4 Real-World Application Workflows (end-to-end editorial scenarios).
  - `all`: Runs tiers 1 through 4 sequentially.
- `--feature <id>` / `-f <id>`: Filter execution to specific feature ID(s) (1 to 36). Supports comma-separated lists (`--feature 1,2,5`) or ranges (`--feature 1-12`).
- `--format <console|json|tap|junit>`: Output format. Default: `console`.
  - `console`: Rich colorized terminal output with progress dots, summary table, and detailed error traces.
  - `tap`: TAP (Test Anything Protocol) version 13 format (`1..N`, `ok 1 - ...`, `not ok 2 - ...`).
  - `junit`: Standard JUnit XML format for CI/CD pipeline integration.
  - `json`: Machine-readable JSON summary for programmatic consumption.
- `--output <path>` / `-o <path>`: Write the formatted output report to a file instead of stdout.
- `--verbose` / `-v`: Display verbose logging, subprocess stdout/stderr, and assertion details.
- `--fail-fast` / `-x`: Abort the test execution immediately upon the first assertion failure.
- `--no-network`: Run tests in strict offline mode using local fixtures and the embedded mock HTTP server.
- `--timeout <seconds>`: Maximum execution duration per test case (default: 30 seconds).
- `--list`: List all discovered test cases without executing them.
- `--help` / `-h`: Display command-line usage and exit.

#### Exit Code Contract
- `0`: All executed test cases passed successfully.
- `1`: One or more test cases failed or encountered an error.
- `2`: CLI argument error, configuration error, or initialization failure.

#### Runner Execution Lifecycle
```
[ CLI Argument Parsing ]
        │
        ▼
[ Environment Validation & Sandbox Init ]
        │
        ▼
[ Test Discovery & Filtering (tier, feature) ]
        │
        ▼
[ Embedded Mock HTTP Server Startup (if --no-network or mock routes) ]
        │
        ▼
[ Test Suite Execution Loop ]
   ├── Setup test sandbox / mock routes
   ├── Execute test case with timeout protection (30s)
   ├── Capture assertions, exit codes, process output
   └── Teardown sandbox
        │
        ▼
[ Embedded Mock HTTP Server Shutdown ]
        │
        ▼
[ Result Aggregation & Metrics Calculation ]
        │
        ▼
[ Reporter Output (Console / TAP / JUnit / JSON) ]
        │
        ▼
[ Process Exit (0 on success, 1 on failure, 2 on error) ]
```

---

### 4.2. Test Directory Layout

The E2E test suite directory structure must be organized as follows:

```
tests/e2e/
├── run_e2e_tests.py                         # Top-level executable test runner
├── harness/                                 # Shared test harness utilities
│   ├── __init__.py                          # Package export
│   ├── base.py                              # E2ETestCase base class, metadata decorators, assertions
│   ├── runner.py                            # Discovery engine, scheduler, timeout manager
│   ├── reporters.py                         # Console, TAP, JUnit XML, and JSON formatters
│   ├── process.py                           # Subprocess execution wrapper (run_cli, run_python, run_php)
│   ├── php_bridge.py                        # PHP execution bridge (loads McpServer, ORM, evaluates JSON-RPC)
│   ├── mock_http.py                         # Embedded Python HTTP server for deterministic offline tests
│   ├── sandbox.py                           # Temporary filesystem sandbox manager for test artifacts
│   └── assertions.py                        # Domain-specific assertions (exit codes, JSON envelopes, SEO tags)
├── fixtures/                                # Static and dynamic test fixtures
│   ├── __init__.py
│   ├── editorial/                           # Editorial drafts, dossiers, mock HTML
│   │   ├── draft_today_diyarbakir_park.html
│   │   ├── draft_with_all_paragraphs_valid.html
│   │   ├── draft_with_p4_hallucinated_num.html
│   │   ├── draft_with_unlinked_attribution.html
│   │   ├── draft_with_turkish_morphology.html
│   │   ├── dossier_today_rudaw_park.json
│   │   ├── dossier_yesterday_rudaw_park.json
│   │   ├── dossier_cross_lang_english_docs.json
│   │   ├── mock_rudaw_article_1287295.html
│   │   ├── mock_nextjs_sidebar_article.html
│   │   ├── mock_vendor_single_segment_download.html
│   │   ├── mock_news_section_front.html
│   │   └── mock_antigravity_docs_swarm.html
│   ├── images/                              # Test images for intake pipeline
│   │   ├── rudaw_true_hero_1920x1080.jpg
│   │   ├── nextjs_3840w_inflated.jpg
│   │   ├── corner_watermark_rudaw.png
│   │   └── sample_valid_master_1600x900.webp
│   ├── feeds/                               # RSS / Sitemap feed XML fixtures
│   │   ├── rss_diyarbakir_today.xml
│   │   ├── rss_diyarbakir_stale.xml
│   │   └── sitemap_news_today.xml
│   ├── mcp/                                 # MCP JSON-RPC mock payloads
│   │   ├── req_create_news_valid.json
│   │   ├── req_create_news_root_cat.json
│   │   ├── req_batch_create_faqs.json
│   │   ├── req_list_faqs_filter.json
│   │   └── req_update_news_status.json
│   └── php_seeds/                           # PHP Database mock fixtures
│       ├── MockNewsData.php
│       └── MockFaqData.php
├── tier1/                                   # Tier 1: Feature Coverage (Happy Path, >=5 tests / feature)
│   ├── __init__.py
│   ├── test_f01_orm_conjunctive_filters.py
│   ├── test_f02_mcpserver_db_fetch.py
│   ├── test_f03_audit_meta_draft_faqs.py
│   ├── test_f04_headline_type_assertion.py
│   ├── test_f05_leaf_category_check.py
│   ├── test_f06_mandatory_make_seo.py
│   ├── test_f07_get_news_enrichment.py
│   ├── test_f08_check_duplicate_news.py
│   ├── test_f09_batch_create_faqs.py
│   ├── test_f10_list_faq_modules.py
│   ├── test_f11_list_faqs_include_answer.py
│   ├── test_f12_nginx_well_known.py
│   ├── test_f13_editorial_http_client.py
│   ├── test_f14_freshness_tls_ssrf.py
│   ├── test_f15_freshness_iso_fractional_seconds.py
│   ├── test_f16_freshness_non_content_tag_strip.py
│   ├── test_f17_freshness_earliest_main_date.py
│   ├── test_f18_freshness_near_midnight_diagnostics.py
│   ├── test_f19_facts_all_paragraph_verification.py
│   ├── test_f20_facts_clean_number_extraction.py
│   ├── test_f21_facts_vendor_single_segment.py
│   ├── test_f22_facts_cross_language_anchoring.py
│   ├── test_f23_facts_turkish_morphology.py
│   ├── test_f24_image_nextjs_unwrap.py
│   ├── test_f25_image_hero_scoring_srcset_cap.py
│   ├── test_f26_image_mode_master.py
│   ├── test_f27_mod_news_image_columns.py
│   ├── test_f28_twig_hero_figure_jsonld.py
│   ├── test_f29_mcp_cli_bridge.py
│   ├── test_f30_source_catalog_scan_today.py
│   ├── test_f31_audit_seo_alignment.py
│   ├── test_f32_skill_documentation_sync.py
│   ├── test_f33_agents_rule_citations.py
│   ├── test_f34_regression_suite_smoke.py
│   ├── test_f35_version_documentation_parity.py
│   └── test_f36_git_release_tag_push.py
├── tier2/                                   # Tier 2: Boundary & Corner Cases (>=5 tests / feature)
│   ├── __init__.py
│   ├── test_t2_f01_to_f04_mcp_orm_boundaries.py
│   ├── test_t2_f05_to_f08_news_staging_boundaries.py
│   ├── test_t2_f09_to_f12_faqs_nginx_boundaries.py
│   ├── test_t2_f13_to_f18_http_freshness_boundaries.py
│   ├── test_t2_f19_to_f23_fact_checking_boundaries.py
│   ├── test_t2_f24_to_f28_image_template_boundaries.py
│   └── test_t2_f29_to_f36_cli_radar_release_boundaries.py
├── tier3/                                   # Tier 3: Cross-Feature Combinations
│   ├── __init__.py
│   ├── test_t3_x01_mcp_orm_conjunctive_draft_faqs.py
│   ├── test_t3_x02_freshness_to_fact_checking_pipeline.py
│   ├── test_t3_x03_image_unwrap_to_template_rendering.py
│   ├── test_t3_x04_mcp_cli_to_audit_seo_rubric.py
│   └── test_t3_x05_radar_scan_to_dossier_init.py
└── tier4/                                   # Tier 4: Real-World Application Workflows
    ├── __init__.py
    ├── test_t4_scenario_01_rudaw_park_intake_full_cycle.py
    ├── test_t4_scenario_02_near_midnight_recovery_pipeline.py
    ├── test_t4_scenario_03_foreign_english_doc_intake.py
    ├── test_t4_scenario_04_duplicate_news_prevention.py
    └── test_t4_scenario_05_swarm_subagent_mcp_cli_workflow.py
```

---

### 4.3. Opaque-Box Interaction Mechanisms

| Target Component | Interaction Interface | Verification Target | Error/Negative Detection |
|---|---|---|---|
| **Editorial Sentinel (`check_freshness.py`)** | Subprocess CLI (`run_cli([python, check_freshness.py, ...])`) | Exit code `0` on same-day article; stdout contains `Status: PASSED` | Exit code `1` on yesterday/stale article (`Status: BLOCKED`); SSRF URLs blocked |
| **Fact Checker (`verify_facts_and_links.py`)** | Subprocess CLI with `--file` & `--dossier` | Exit code `0` when all claims anchored; R2 vendor single-segment with version | Exit code `1` when Paragraph 4 contains hallucinated number; exit code `1` on unlinked attribution |
| **Image Ingestion (`process_news_image.py`)** | Subprocess CLI with `--from-article` & `--mode master` | Exit code `0`; unwrapped `/_next/image` URL chosen; master image `>=1200px` created | Sidebar photo penalized; inflated `3840w` capped; invalid image binary rejected |
| **Local News Radar (`source_catalog.py`)** | Subprocess CLI with `--scan-today` | Exit code `0`; output JSON contains verified same-day articles from RSS/sitemap feeds | Stale feeds filtered out; malformed XML handled without crash |
| **SEO Audit (`audit_seo.py`)** | Subprocess CLI with `--file`, `--headline`, `--summary` | Exit code `0` when headline 40-70, summary 90-120, `>=3 <h2>` | Exit code `1` on short headline (`<40`), missing summary, or heading jump |
| **Swarm CLI Bridge (`tools/mcp_cli.py`)** | Subprocess CLI (`python3 tools/mcp_cli.py call <tool>`) | Exit code `0`; secret-opaque JSON-RPC output; `status="n"` enforced | Mutation without `status="n"` rejected; `delete_*` tools hard-blocked |
| **MCP CRUD Filters (`McpCrudToolsBase`)** | PHP Bridge (`php tests/e2e/harness/php_eval.php`) | `$model->addWhere()` produces conjunctive `AND` clauses; earlier clauses retained | Multiple filters do not overwrite `$this->_where`; SQL injection sanitized |
| **DB Fetch Fixes (`McpServer`, `AuditMeta`)** | PHP Bridge | `Database::fetch_array()` called on `mysqli_result`; draft FAQs counted | No `Fatal error: Call to undefined method mysqli_result::fetch()` |
| **News Slug & Leaf Category (`McpNewsTools`)** | PHP Bridge / `mcp_cli.py` | Kurdish Latin letters preserved (`ç, ê, î, ş, û`); `U+0307` stripped; leaf category accepted | Root category (`root='y'`) throws validation error |
| **News Template (`news.twig`)** | PHP Twig Renderer & Static Auditor | `<figure class="detail-hero">`, `<figcaption>`, Schema.org `ImageObject` rendered | Zero inline `style="..."` or `<script>` (`code_separation_guard.py` clean) |
| **Nginx Routing (`docker/nginx/prod.conf`)** | Nginx Config Parser / Mock Request | `location ^~ /.well-known/` precedes `location ~ /\.`; returns HTTP 200/404 | Hidden files (`/.git`, `/.env`) return HTTP 403; `.well-known` not blocked |
| **Quality Guards (`tools/*_guard.py`)** | Subprocess CLI | Exit code `0` across all files in repository | Exits non-zero on inline styling, non-English comments, or foreign module imports |

---

### 4.4. Complete Draft Content for `TEST_INFRA.md`

Below is the complete text and blueprint for `TEST_INFRA.md` to be written to the project root:

```markdown
# Comprehensive E2E Testing Infrastructure: Kevanger Editorial Pipeline Remediation (v1.0.63)

**Target Domain:** `https://amedbulten.com`  
**Repository:** `AF/Bulletin`  
**Target Release:** `v1.0.63`  
**Architecture Standard:** Amed Framework Core (Opaque-Box Testing, Dual-Track Quality Guard, Wikipedia-Style URL Parity, Strict Code Separation)

---

## 1. Executive Overview & Testing Philosophy

The v1.0.63 release of `AF/Bulletin` resolves critical defects and delivers key capability enhancements across the entire editorial pipeline — spanning MCP CRUD tools, ORM conjunctive filtering, verified TLS HTTP transport, same-day freshness sentinels, fact-checking and entity anchoring, Next.js image unwrapping, mod_news media credit schemas, Swarm CLI stdio bridges, and RSS news radar.

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
- `--feature <id>` / `-f <id>`: Filter by feature number (1 through 36, or comma-separated `1,2,5`).
- `--format <console|json|tap|junit>`: Output format (default: `console`).
- `--output <file>` / `-o <file>`: Write output report to specified file.
- `--verbose` / `-v`: Display verbose logging, subprocess streams, and diagnostic traces.
- `--fail-fast` / `-x`: Abort execution immediately upon the first failure.
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
├── tier1/                                   # Tier 1: Feature Coverage (36 test modules, >=5 tests / feature)
├── tier2/                                   # Tier 2: Boundary & Corner Cases (7 test modules, >=5 tests / feature)
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
```

---

## 5. Verification Method

To independently verify this architectural blueprint and its compliance:

1. **Verify State Folder and Documentation Deliverables**:
   ```bash
   ls -la /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_explorer_e2e_m1_3/
   # Confirm BRIEFING.md, DISPATCH.md, progress.md, and handoff.md are present and fully populated.
   ```
2. **Verify Python & PHP Environments**:
   ```bash
   python3 --version  # Must report Python 3.8+ (current: 3.14.4)
   php -v             # Must report PHP 8.0+ (current: 8.5.4)
   python3 -c "import PIL, unittest, http.server, json, subprocess; print('All required runner dependencies available')"
   ```
3. **Verify Existing Codebase Paths & Conventions**:
   ```bash
   ls -la lib/McpServer.class.php lib/McpCrudToolsBase.class.php lib/McpNewsTools.class.php
   ls -la .agents/skills/kevtemen/scripts/check_freshness.py .agents/skills/kevrast/scripts/verify_facts_and_links.py
   python3 tools/code_separation_guard.py
   ```
4. **Invalidation Conditions**:
   - If the test runner requires external packages not present in the workspace virtualenv, the architecture is invalid.
   - If any test case modifies production files or database state without sandboxing, the opaque-box contract is violated.
   - If fewer than 5 test cases per feature are specified for Tier 1 and Tier 2, the coverage threshold is invalidated.
