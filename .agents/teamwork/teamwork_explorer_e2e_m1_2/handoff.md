# Handoff Report: Existing Test Infrastructure & Verification Tooling Investigation

**Investigator**: teamwork_explorer (`teamwork_explorer_e2e_m1_2`)  
**Mission**: Milestone 1 - Test Harness & Existing Verification Investigation  
**Working Directory**: `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin`  
**State Folder**: `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_explorer_e2e_m1_2/`  

---

## 1. Observation

Direct empirical observations from codebase inspection, tool executions, and environment probes:

### 1.1 Existing Test Harnesses and Directory Structure (`tests/`)
- **Root test runner script**: `tests/run_e2e_tests.sh` lines 1-7:
  ```bash
  #!/usr/bin/env bash
  set -e
  DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" >/dev/null 2>&1 && pwd)"
  ROOT_DIR="$(dirname "$DIR")"
  php "$ROOT_DIR/tests/run_e2e_tests.php" "$@"
  ```
- **PHP entrypoint**: `tests/run_e2e_tests.php` lines 1-5 simply delegates:
  ```php
  require_once __DIR__ . '/e2e/run.php';
  ```
- **Custom PHP Test Runner**: `tests/e2e/run.php` lines 1-98:
  - Invocation: `php tests/e2e/run.php [--tier=<0-5>] [--filter=<string>] [--json] [--help]`.
  - Instantiates `E2E\Harness\TestRunner`.
  - Runs six test suites:
    1. `Tier0QualityContractsTest` (5 tests)
    2. `Tier1FeatureCoverageTest` (105 tests)
    3. `Tier2BoundariesCornersTest` (105 tests)
    4. `Tier3CrossFeatureTest` (8 tests)
    5. `Tier4RealWorldScenariosTest` (5 tests)
    6. `Tier5AdversarialHardeningTest` (8 tests)
- **Harness Infrastructure**: `tests/e2e/harness/`:
  - `Assertions.php` (lines 1-221): 28 custom assertion methods (`assertEquals`, `assertSame`, `assertTrue`, `assertFalse`, `assertNull`, `assertNotNull`, `assertContains`, `assertNotContains`, `assertStringStartsWith`, `assertStringEndsWith`, `assertMatchesRegularExpression`, `assertCount`, `assertArrayHasKey`, `assertFileExists`, `assertThrows`, etc.) tracking cumulative assertion count.
  - `TestCase.php` (lines 1-129): Abstract base test case with `getTier()`, `getName()`, `setUp()`, `tearDown()`, and wrapper assert methods.
  - `TestSuite.php` (lines 1-70): Uses PHP Reflection (`ReflectionClass`) to discover all methods starting with `test` and execute them, recording timing and exceptions into `TestResult`.
  - `TestRunner.php` (lines 1-128): Manages suite execution, handles tier and pattern filtering, calculates pass rates and execution time, prints ANSI color output or JSON envelope, exits 0 on all passes and 1 on failure.
  - `MockDb.php` (lines 1-631): In-memory SQL emulator! Emulates PDO/SQL operations (`SELECT`, `INSERT`, `UPDATE`, `DELETE`, `WHERE`, transactions, table auto-increments). Allows running database tests on host without live MySQL.
  - `MockSession.php` & `MockRequest.php`: In-memory HTTP request and session mocks.
  - `StaticAuditor.php`, `TwigAuditor.php`, `CssAuditor.php`: AST and regex scanners auditing code separation, Twig macro structures, and touch target rules.
- **Fixtures**:
  - `tests/e2e/fixtures/`: `ContentFixtures.php`, `FaqFixtures.php`, `LanguageFixtures.php`, `ModuleFixtures.php`, `NewsletterFixtures.php`, `SeoFixtures.php`, `SettingsFixtures.php`.
  - `tests/fixtures/editorial/`: `sample_draft.html` (2.5 KB), `sample_news_dossier.json` (34 lines), `sample_evergreen_dossier.json` (44 lines).
- **Standalone Challenge and Unit Test Scripts in `tests/`**:
  - `tests/twig_template_adversarial_test.php` (296 lines): 62 assertions testing `TwigTemplate`, `TemplateContext`, `AdminTemplate`, `BaseModuleController`, `Req`, `Cache`. Runs on host, exits 0: `TEST RESULTS: Passed=62, Failed=0`.
  - `tests/m2_toggle_permission_adversarial_simulator.php` (347 lines): 26 assertions testing ACL toggle, transactions, and injection resilience. Runs on host, exits 0: `SIMULATION RESULTS: Total=26+0, Passed=26, Failed=0`.
  - `tests/module_management_runtime_test.php` (172 lines): 6 assertions testing `ModuleRuntime`. Runs on host, exits 0.
  - `tests/m1_adversarial_challenge_test.php` (703 lines): Tests `Helper::legacy_seo`, `Obuilder`, `Orm`, `BaseModuleController`. Runs on host; exits 1 (4 legacy expectation mismatches).
  - `tests/m2_css_touch_target_auditor.py` (103 lines): Scans CSS touch targets (`>= 44px`).
  - `tests/registry_adversarial_stress_test.php` (139 lines): Tests `Registry` edge cases.

### 1.2 Verification Tools in `tools/` and `tasks/`
- **`tasks/mcp_smoke.php`** (710 lines):
  - Comprehensive live smoke test suite for the `amed-bulletin` MCP JSON-RPC server.
  - Line 8: `if (PHP_SAPI !== 'cli') exit(1);`.
  - Creates subclass `SmokeMcpServer extends McpServer` overriding `readRequestBody()` and `sendJson()` to simulate in-memory JSON-RPC requests without HTTP.
  - Tests 67 distinct assertions across 16 categories:
    1. Infrastructure self-healing (tables `mod_mcp_api_key`, `mods` registration, roles)
    2. Transport: method restrictions (GET rejected), content-type (`application/json` enforced), auth (missing/invalid API keys rejected)
    3. Key lifecycle: SHA-256 key hashing, prefix extraction
    4. Protocol handshake: `initialize`, `serverInfo.name === 'amed-bulletin-mcp'`, `ping`
    5. Tool discovery: `tools/list` returns tools, validates Bulletin domain isolation (`list_services`, `list_products`, `list_appointments` absent)
    6. CRUD tools: `list_news`, `get_news`, `list_news_categories`
    7. Resource and template discovery: `resources/list`, `resources/templates/list`
    8. Prompt discovery: `prompts/list`, `seo_meta_optimizer`
    9. Modernization contracts: `structuredContent`, protocol version batch handling
    10. Jobs interface: `createJob`, `getJob`, `cancelJob`
    11. Curator quality gates: summary length (90-120 chars), `sanitizeBodyHtml`
    12. SEO slug resolution & 301 auto-redirects
    13. Google settings API key masking
    14. Active languages & cross-language prose gate
    15. Security pinning: `X-MCP-Expected-Server` and `X-MCP-Expected-Resource` trigger HTTP 421 on mismatch; loopback port pinning
    16. Audit logging and environment metadata (`_meta.environment`)
  - Execution result in Docker container: `docker exec bulletin_app php /var/www/html/tasks/mcp_smoke.php` completed in 4.1s with output:
    `Smoke test complete: 67 passed, 0 failed.`
- **`tools/verify_news_pipeline.sh`** (168 lines):
  - 4-stage automated validation chain:
    - Stage 1: `kevtemen` (`check_freshness.py --file ... --dossier ... [--no-network]`)
    - Stage 2: `kevseo` (`audit_seo.py --file ... --headline ... --summary ... [--seo ...]`)
    - Stage 3: `kevrast` (`verify_facts_and_links.py --file ... --dossier ... --explain [--no-network]`)
    - Stage 4: `audit_content_seo.py --module mod_news --profile editorial --record-json ... --lang-status ...`
  - Exit code 0 if all stages pass, exit code 1 or 2 on error.
- **`tools/mcp_health_check.py`** (666 lines):
  - Multi-stage health checker.
  - Supports `--preflight`, `--endpoint`, `--verbose`, `--json`.
  - Discovers endpoint from `config/site.config.php` and `~/.gemini/antigravity/bin/amed_bulletin_mcp.py`.
  - Runs 7 verification stages:
    - Stage 1: Workspace Auto-Discovery
    - Stage 2: Coexistence & Isolation in Global MCP Configs
    - Stage 3: Bridge Files Parity
    - Stage 4: Live MCP Transport & Handshake
    - Stage 5: Adversarial Misdirected-Request Probe (HTTP 421)
    - Stage 6: Tool Contract & Image Schema (v1.0.58+ Parity)
    - Stage 7: RFC 8414 & RFC 9728 OAuth Metadata Discovery
  - Execution result: `python3 tools/mcp_health_check.py --endpoint http://localhost:8080/mcp.php --preflight` succeeded (`Authenticated: true`, `Environment: development`).
- **`tools/audit_content_seo.py`** (647 lines):
  - 100-point rubric evaluator for CMS records (`mod_news`, `mod_publication`, `mod_page`, `mod_announcements`).
  - Evaluates Zone 1 (Headline 40-70 chars, Deck 90-120 chars), Zone 2 (Semantic HTML structure, h1->h2 downgrading, tables, figures), Zone 3 (Metadata synchronization between title/name, seo, description), Zone 4 (Active-language parity and zero inline styles/scripts).
- **`tools/code_separation_guard.py`** (189 lines):
  - Enforces AGENTS.md Rule 1 (Strict Code Separation).
  - Flags `<style>` tags (`INLINE_STYLE_TAG`), `style="..."` attributes (`INLINE_STYLE_ATTR`), and inline event handlers `on*` (`INLINE_EVENT_HANDLER`).
  - Audits git modified files by default or `--path <path>`. Verified: `python3 tools/code_separation_guard.py --path templates/amed/index/mod_news/news.twig` passed with 0 violations.
- **`tools/module_boundary_guard.py`** (246 lines):
  - Guards 18 forbidden modules (`mod_content`, `mod_product`, `mod_service`, `mod_client`, `mod_test`, etc.).
  - Execution result: `python3 tools/module_boundary_guard.py` passed with 0 violations.
- **`tools/i18n_guard.py`** (520 lines):
  - Audits language completeness across all 9 supported languages (en, ku, ckb, tr, ar, de, fr, es, zza) and flags non-English comments/keys.
- **`tools/min_css_guard.py`** (247 lines):
  - Verifies CSS and minified `.min.css` rule block parity.
  - Execution result: `python3 tools/min_css_guard.py` passed with all rule blocks matching.

### 1.3 Test Frameworks, Libraries and Runtime Environments
- **PHP Test Runners**:
  - PHPUnit is **NOT** installed (`vendor/bin/` contains only `phpstan`, `phpstan.phar`, `rector`; `which phpunit` returns non-zero).
  - Primary PHP test framework is the repository's custom harness under `tests/e2e/harness/` (`TestRunner.php`, `TestCase.php`, `Assertions.php`).
- **Python Test Runners**:
  - `pytest 9.0.2` is installed and functional on the host environment (`python3 -m pytest --version` output: `pytest 9.0.2`).
  - `unittest` is available in Python 3.12 standard library.
- **Host PHP vs Container PHP Runtime Differences**:
  - Host CLI: `PHP 8.5.4 (cli)`. Modules installed: `Core`, `ctype`, `curl`, `json`, `mbstring`, `openssl`, `pcntl`, `pcre`, `PDO`, `Phar`, `Reflection`, `session`, `tokenizer`, `Zend OPcache`, `zlib`.
    - **CRITICAL GAP**: Neither `mysqli` nor `pdo_mysql` is present in host PHP (`php -m | grep -i mysql` is empty).
    - Running any PHP script on the host that calls `Load::lib('Database')` (which instantiates PDO with `PDO_MYSQL`) throws `DatabaseException: Connection Problem! ... could not find driver`.
  - Docker Container `bulletin_app`: `PHP 8.5.12 (cli)`. Modules installed include `mysqlnd`, `pdo_mysql`, `pdo_sqlite`, `sqlite3`, `curl`, `gd`, `imagick`, `intl`, `mbstring`, `dom`, `SimpleXML`, `xmlreader`, `xmlwriter`, `zip`.
- **Database Connectivity & Port Isolation**:
  - `docker ps` shows 4 containers running:
    - `bulletin_web` (`nginx:alpine`): `0.0.0.0:8080->80/tcp` (reachable from host)
    - `bulletin_app` (`bulletin-app`): `9000/tcp` (internal to Docker bridge)
    - `bulletin_pma` (`phpmyadmin/phpmyadmin`): `0.0.0.0:8081->80/tcp`
    - `bulletin_db` (`mysql:8.0`): `3306/tcp, 33060/tcp` (INTERNAL ONLY: `docker inspect bulletin_db` confirms `{"3306/tcp": null}`)
  - Host cannot connect directly to MySQL on `localhost:3306`.
  - `bulletin_app` connects to MySQL via Docker network DNS name `db` (configured in `config/db.config.local.php`: `'host' => 'db'`, `'user' => 'bulletin'`, `'pass' => 'bulletin_password'`, `'database' => 'bulletin'`).
  - MCP JSON-RPC server is reachable from host over HTTP at `http://localhost:8080/mcp.php`.

### 1.4 E2E Test Suite Scope & Requirements (`e2e_orch/SCOPE.md`)
- The E2E test track specification mandates:
  - Invocation: `python3 tests/e2e/run_e2e_tests.py [--tier 1|2|3|4|all]`
  - Test case directories: `tests/e2e/tier1/`, `tests/e2e/tier2/`, `tests/e2e/tier3/`, `tests/e2e/tier4/`
  - Coverage: All 36 features of v1.0.63 (`PROJECT.md` F1 to F36)
  - Completely opaque-box, requirement-driven, test harness independence
  - Output: Standard TAP/JUnit or structured summary with pass/fail counts per tier and per feature
  - Exit code: 0 on all tests passing, non-zero on any failure
  - Publishing `TEST_INFRA.md` and `TEST_READY.md` at project root.

---

## 2. Logic Chain

1. **Test Runner Selection & Architecture**:
   - *From Observation 1.3*: Host environment has Python 3.12 with `pytest 9.0.2` and `unittest` natively available. In PHP, `phpunit` does not exist; only the custom harness `tests/e2e/run.php` exists.
   - *From Observation 1.4*: The required interface in `SCOPE.md` explicitly specifies `python3 tests/e2e/run_e2e_tests.py [--tier 1|2|3|4|all]`.
   - *Therefore*: Building the new E2E test runner in Python (`tests/e2e/run_e2e_tests.py`) directly satisfies the interface contract and leverages Python's built-in `unittest` or a custom modular runner capable of generating TAP/JUnit/JSON outputs.

2. **Database Access & Execution Strategy**:
   - *From Observation 1.3*: Host PHP 8.5.4 lacks MySQL drivers (`could not find driver`), and MySQL port 3306 is not exposed to host (`NetworkSettings.Ports["3306/tcp"] = null`).
   - *From Observation 1.2 & 1.3*: `bulletin_web` is published on port 8080 (`http://localhost:8080/mcp.php`) with full database access, authenticated via API key `amcp_f8718fa73029f417ecd7399f4156e5aa6a529a3fc58c1060`. Moreover, `docker exec bulletin_app php ...` can execute any PHP command directly inside the container with full DB access.
   - *Therefore*: The E2E test runner running on the host has three viable execution channels depending on test requirement:
     1. **HTTP/JSON-RPC Channel**: Make direct POST requests to `http://localhost:8080/mcp.php` for opaque-box testing of MCP tools, ORM queries, and API filters (Features 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11).
     2. **Subprocess/CLI Channel**: Directly invoke workspace Python scripts (`check_freshness.py`, `verify_facts_and_links.py`, `process_news_image.py`, `source_catalog.py`, `audit_seo.py`, `tools/mcp_cli.py`) and bash scripts (`tools/verify_news_pipeline.sh`) to test editorial gates and CLI bridges (Features 13-26, 29-33).
     3. **Container Exec Channel**: When deep PHP CLI verification is needed (such as `tasks/mcp_smoke.php`), execute `docker exec bulletin_app php ...` with zero driver issues.
     4. **Static Inspection Channel**: Parse and validate configuration and templates directly on host filesystem (e.g. `docker/nginx/prod.conf` for Feature 12, `mod_news.sql` for Feature 27, `news.twig` for Feature 28, `docs/VERSION.md` for Feature 35).

3. **Directory Layout & Existing PHP Suite Coexistence**:
   - *From Observation 1.1*: Existing tests reside in `tests/e2e/suites/Tier0...Tier5`, and `tests/run_e2e_tests.sh` runs `tests/e2e/run.php`.
   - *From Observation 1.4*: The new track requires `tests/e2e/tier1/`, `tests/e2e/tier2/`, `tests/e2e/tier3/`, `tests/e2e/tier4/` managed by `tests/e2e/run_e2e_tests.py`.
   - *Therefore*: The new Python E2E runner can be introduced cleanly in `tests/e2e/run_e2e_tests.py` with modular test packages `tests/e2e/tier1/`, `tier2/`, `tier3/`, `tier4/` without conflicting with legacy suites in `tests/e2e/suites/`. `tests/run_e2e_tests.sh` can either be updated to delegate to `run_e2e_tests.py` or provide a unified dispatch.

4. **Fixture Reuse**:
   - *From Observation 1.1*: `tests/fixtures/editorial/` already has `sample_draft.html`, `sample_news_dossier.json`, `sample_evergreen_dossier.json`.
   - *Therefore*: Tier 1-4 editorial and pipeline tests can directly reuse and extend fixtures in `tests/fixtures/editorial/` and `tests/e2e/fixtures/`.

---

## 3. Caveats

1. **Sibling Repositories**: Cross-repository sync tool `tools/cross_repo_sync.py` references `/path/to/AF/Business`, but per `AGENTS.md` Rule 3, cross-repo inspection is forbidden during this task. All investigations were strictly local to `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin`.
2. **Features Under Active Development**: Features 1-12 (M1) and 13-23 (M2) are currently being implemented by concurrent sub-orchestrators (`901f7801` and `5c7538a8`). E2E test cases created in upcoming milestones will observe these features transitions from failing to passing as implementations land.
3. **Container Dependency for Live Database**: If Docker containers are stopped, live HTTP MCP tests against `localhost:8080` will fail. The E2E runner should detect Docker container liveness in preflight (similar to `mcp_health_check.py --preflight`) and report an informative message.

---

## 4. Conclusion

The testing infrastructure investigation is complete. The repository has a rich set of testing and verification tools:
1. **PHP Layer**: Custom reflection-based harness in `tests/e2e/harness/` with `MockDb` and 236 existing tests across Tiers 0-5. Host PHP 8.5 lacks MySQL drivers, but Docker container `bulletin_app` runs PHP 8.5.12 with complete MySQL drivers, passing all 67 checks in `tasks/mcp_smoke.php`.
2. **Python Layer**: Python 3.12 with `pytest 9.0.2` and standard `unittest` is ready on host.
3. **Verification Tooling**: `tasks/mcp_smoke.php`, `tools/verify_news_pipeline.sh`, `tools/mcp_health_check.py`, `tools/audit_content_seo.py`, `tools/code_separation_guard.py`, `tools/module_boundary_guard.py`, `tools/min_css_guard.py`, and `tools/i18n_guard.py` provide extensive, battle-tested assertions.
4. **Runner Architecture for E2E-M1**: Building `tests/e2e/run_e2e_tests.py` in Python with directories `tests/e2e/tier1/` through `tests/e2e/tier4/` is optimal. It can invoke opaque-box tests across HTTP JSON-RPC (`http://localhost:8080/mcp.php`), CLI subprocesses for editorial scripts, Docker execution for containerized PHP scripts, and filesystem AST/regex inspection for templates and configurations.

---

## 5. Verification Method

To independently verify the facts and findings documented in this report:

1. **Verify Custom PHP Test Runner & Harness**:
   ```bash
   php tests/run_e2e_tests.php --tier=0
   php tests/run_e2e_tests.php --help
   ```
   *Expected*: Tier 0 passes (5 tests, 23 assertions, exit code 0).

2. **Verify Docker Container Liveness & Live MCP Smoke Test**:
   ```bash
   docker ps --filter "name=bulletin"
   docker exec bulletin_app php /var/www/html/tasks/mcp_smoke.php
   ```
   *Expected*: All 4 containers `Up`. `mcp_smoke.php` outputs: `Smoke test complete: 67 passed, 0 failed.` with exit code 0.

3. **Verify Host PHP MySQL Driver Absence**:
   ```bash
   php -m | grep -i mysql
   ```
   *Expected*: Empty output (neither `mysqli` nor `pdo_mysql` is present in host CLI PHP).

4. **Verify MCP Health Check & Local Endpoint**:
   ```bash
   python3 tools/mcp_health_check.py --endpoint http://localhost:8080/mcp.php --preflight
   ```
   *Expected*: `MCP Preflight Check: PASSED`, `Authenticated: true`, `Environment: development`.

5. **Verify Quality Guards**:
   ```bash
   python3 tools/module_boundary_guard.py
   python3 tools/code_separation_guard.py --path templates/amed/index/mod_news/news.twig
   python3 tools/min_css_guard.py
   ```
   *Expected*: All exit 0 with pass verdicts.

6. **Verify Python Testing Libraries on Host**:
   ```bash
   python3 -m pytest --version
   python3 -c "import unittest; print('unittest ready')"
   ```
   *Expected*: `pytest 9.0.2` and `unittest ready`.
