# Scope: E2E Testing Track (v1.0.63)

## Architecture
- Opaque-box requirement-driven test suite covering all 36 features across MCP, ORM, HTTP Client, Editorial Sentinel, Image Intake, Mod News, Swarm CLI, Radar, SEO, and Release Verifications.
- Test runner: Unified E2E test runner executing tests across Tiers 1-4 with clear pass/fail exit codes.
- Test case directory layout: `tests/e2e/tier1/`, `tests/e2e/tier2/`, `tests/e2e/tier3/`, `tests/e2e/tier4/`.
- Publishes `TEST_INFRA.md` and `TEST_READY.md` at project root.

## Feature Inventory
| # | Feature | Description | Milestone | Source |
|---|---------|-------------|-----------|--------|
| 1 | `McpCrudToolsBase::addEqFilter` & `addLikeSearchFilter` | Conjunctive filtering via `$model->addWhere()` avoiding B1 filter clobbering | E2E-M1..M5 | PROJECT.md F1 |
| 2 | `McpServer::resolveEnvironment` DB fetch | Use `Database::fetch_array()` instead of undefined PDO `fetch()` on `mysqli_result` | E2E-M1..M5 | PROJECT.md F2 |
| 3 | `McpCrudToolsBase::getRecordAuditMeta` DB fetch & draft FAQs | Use `Database::fetch_array()` and support draft FAQ counting (`recordStatus = 'n'`) | E2E-M1..M5 | PROJECT.md F3 |
| 4 | `McpHeadlineTools` string argument fix | Pass `$name` as string to `assertContentMatchesLang` to prevent TypeError | E2E-M1..M5 | PROJECT.md F4 |
| 5 | `McpNewsTools` leaf category check | Reject root categories (`root === 'y'`) in news staging | E2E-M1..M5 | PROJECT.md F5 |
| 6 | `McpNewsTools` mandatory `makeSeo()` | Enforce Wikipedia-style slug normalization and `U+0307` strip on all slugs | E2E-M1..M5 | PROJECT.md F6 |
| 7 | `McpNewsTools::getNews` enrichment | Return `username`, `author_fullname`, `category_name`, and `category_seo` | E2E-M1..M5 | PROJECT.md F7 |
| 8 | `McpNewsTools::checkDuplicateNews` | Add pre-staging duplicate news check within last 48 hours | E2E-M1..M5 | PROJECT.md F8 |
| 9 | `McpFaqTools::batchCreateFaqs` | Atomic creation of up to 25 FAQs for host record | E2E-M1..M5 | PROJECT.md F9 |
| 10 | `McpFaqTools::listFaqModules` | List valid FAQ host modules from `mod_faq_mod` | E2E-M1..M5 | PROJECT.md F10 |
| 11 | `McpFaqTools::listFaqs` `include_answer` | Add `include_answer: bool` parameter to return full FAQ answers | E2E-M1..M5 | PROJECT.md F11 |
| 12 | Nginx `.well-known` passthrough | Add `location ^~ /.well-known/` to `docker/nginx/prod.conf` for RFC discovery | E2E-M1..M5 | PROJECT.md F12 |
| 13 | Shared HTTP Client `editorial_http.py` | Verified TLS, SSRF guard, standard browser headers, Retry-After backoff, 10m cache | E2E-M1..M5 | PROJECT.md F13 |
| 14 | `check_freshness.py` TLS & SSRF integration | Use `editorial_http` with verified TLS and SSRF protection | E2E-M1..M5 | PROJECT.md F14 |
| 15 | `check_freshness.py` ISO fractional seconds | Parse fractional seconds `(?:[.,]\d+)?` preserving UTC timezone | E2E-M1..M5 | PROJECT.md F15 |
| 16 | `check_freshness.py` non-content tag stripping | Strip `<script>`, `<style>`, `<aside>`, `<nav>`, `<footer>` before date scanning | E2E-M1..M5 | PROJECT.md F16 |
| 17 | `check_freshness.py` earliest main date & deduplication | Select earliest main entity publication date; deduplicate audit URLs | E2E-M1..M5 | PROJECT.md F17 |
| 18 | `check_freshness.py` near-midnight diagnostics | Provide structured `age_hours` and near-midnight diagnostic attributes | E2E-M1..M5 | PROJECT.md F18 |
| 19 | `verify_facts_and_links.py` all-paragraph verification | Evaluate all citing paragraphs across the draft in `audit_draft()` | E2E-M1..M5 | PROJECT.md F19 |
| 20 | `verify_facts_and_links.py` clean number & entity extraction | Strip non-content tags before R3; enforce exact normalized numeric equality | E2E-M1..M5 | PROJECT.md F20 |
| 21 | `verify_facts_and_links.py` vendor single-segment allowlist | Allow defined single-segment vendor download/release pages under criteria | E2E-M1..M5 | PROJECT.md F21 |
| 22 | `verify_facts_and_links.py` cross-language anchoring | Support `source_lang` and `source_anchors` in `dossier.schema.json` | E2E-M1..M5 | PROJECT.md F22 |
| 23 | `verify_facts_and_links.py` Turkish morphology & attribution | Add `tr_fold()` and institutional suffix stemmer; expand attribution phrases | E2E-M1..M5 | PROJECT.md F23 |
| 24 | `process_news_image.py` Next.js CDN proxy unwrap | Unwrap Next.js `/_next/image?url=...` before deduplication | E2E-M1..M5 | PROJECT.md F24 |
| 25 | `process_news_image.py` hero scoring & srcset cap | Restrict hero bonus to primary article elements; cap inflated srcset at 3840w | E2E-M1..M5 | PROJECT.md F25 |
| 26 | `process_news_image.py` `--mode master` | Support clean `>= 1200px` master image output | E2E-M1..M5 | PROJECT.md F26 |
| 27 | `mod_news` `image_alt` & `image_credit` columns | Add schema columns to `mod_news`, model, controller, views | E2E-M1..M5 | PROJECT.md F27 |
| 28 | `news.twig` hero figure & JSON-LD | Render `<figure class="detail-hero">` and `<figcaption>` with schema credits | E2E-M1..M5 | PROJECT.md F28 |
| 29 | `tools/mcp_cli.py` Swarm CLI wrapper | Provide secret-opaque stdio bridge with `--editorial-safe` mutation guards | E2E-M1..M5 | PROJECT.md F29 |
| 30 | `source_catalog.py` `--scan-today` | Support live RSS/Atom/sitemap scanning for same-day local primary recovery | E2E-M1..M5 | PROJECT.md F30 |
| 31 | `audit_seo.py` alignment | Enforce headline 40-70, summary 90-120, description sync, heading hierarchy | E2E-M1..M5 | PROJECT.md F31 |
| 32 | Skill documentation synchronization | Update Kevanger, Kevtemen, Kevkolin, Kevrast, Kevseo SKILL.md docs | E2E-M1..M5 | PROJECT.md F32 |
| 33 | Correct AGENTS.md rule number citations | Fix citations in `kevseo/SKILL.md`, `audit_seo.py`, `code_separation_guard.py`, validators, and `CODE_QUALITY_AGENT_STRATEGY.md` | E2E-M1..M5 | PROJECT.md F33 |
| 34 | Full Regression Suite & MCP Smoke Tests | Update `tasks/mcp_smoke.php` and `tools/verify_news_pipeline.sh` Stage 7 | E2E-M1..M5 | PROJECT.md F34 |
| 35 | Version Documentation Update | Update `docs/VERSION.md` under v1.0.63 and synchronize version parity | E2E-M1..M5 | PROJECT.md F35 |
| 36 | Git Release Tagging & Push | Commit changes, tag release v1.0.63, and push directly to GitHub | E2E-M1..M5 | PROJECT.md F36 |

## Milestones
| # | Name | Scope | Dependencies | Status |
|---|------|-------|-------------|--------|
| E2E-M1 | Test Infrastructure & Framework Setup | Runner harness, test runner script, directory layout, TEST_INFRA.md | none | DONE (TEST_INFRA.md published, run_e2e_tests.py, harness, 38 tests pass) |
| E2E-M2 | Tier 1 Feature Coverage Suite | >=5 test cases per feature across all 36 features (happy path) | E2E-M1 | DONE (187 tests in tests/e2e/tier1/, 186 passed / 1 skipped, Gate PASS) |
| E2E-M3 | Tier 2 Boundary & Corner Case Suite | >=5 test cases per feature at boundaries/error conditions | E2E-M1 | IN_PROGRESS |
| E2E-M4 | Tier 3 Cross-Feature Combination Suite | Pairwise and cross-module interaction test cases | E2E-M2, E2E-M3 | PLANNED |
| E2E-M5 | Tier 4 Real-World Application Suite & TEST_READY | Full workflow scenarios, end-to-end integration, publish TEST_READY.md | E2E-M4 | PLANNED |

## Interface Contracts
### Test Runner Interface
- Invocation: `python3 tests/e2e/run_e2e_tests.py [--tier 1|2|3|4|all]`
- Exit code: 0 on all tests passing, non-zero on any failure
- Output: Standard TAP/JUnit or structured summary with pass/fail counts per tier and per feature
- Requirement: Completely opaque-box, requirement-driven, test harness independence.
