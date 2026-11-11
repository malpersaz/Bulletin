# BRIEFING — 2026-11-11T12:10:00Z

## Mission
Investigate test architecture, process runners, fixture integration, and design Tier 1 test specifications for Features 24 through 36 (Image Processing, Hero Figure & JSON-LD, Swarm CLI, Radar, SEO Auditing, Skill Docs, Code Quality Guards, Regression Smoke Tests, and Version/Release) for Milestone E2E-M2.

## 🔒 My Identity
- Archetype: explorer
- Roles: investigation, test design, architecture synthesis
- Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin
- State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/explorer_e2e_m2_3/
- Original parent: 103189a5-cec8-4f34-b717-470cc29cdb80
- Milestone: E2E-M2

## 🔒 Key Constraints
- Read-only investigation — do NOT implement or modify source code / test files
- Focus strictly on Features 24 to 36 (F24-F36)
- Minimum 5 distinct test cases per feature (minimum 65 test cases total)
- File workspace convention: write only to state folder .agents/teamwork/explorer_e2e_m2_3/

## Current Parent
- Conversation ID: 103189a5-cec8-4f34-b717-470cc29cdb80
- Updated: 2026-11-11T12:10:00Z

## Investigation State
- **Explored paths**:
  - Harness: `tests/e2e/harness/runner.py`, `process.py`, `assertions.py`, `base.py`, `mock_http.py`, `sandbox.py`
  - Fixtures: `tests/e2e/fixtures/images/`, `tests/e2e/fixtures/feeds/`, `tests/e2e/fixtures/editorial/`
  - Implementation & scripts: `process_news_image.py`, `source_catalog.py`, `audit_seo.py`, `news.twig`, `mod_news.sql`, `code_separation_guard.py`, `module_boundary_guard.py`, `tasks/mcp_smoke.php`, `tools/verify_news_pipeline.sh`, `docs/VERSION.md`, `site.config.php`, `SERVER.md`
- **Key findings**:
  - Image intake requires Next.js query parameter unwrapping before deduplication (`split('?')[0]` currently clobbers multiple Next.js URLs).
  - Hero ranking needs DOM scoping to `<article>` and `srcset` width cap at 1200.
  - `--mode master` preserves natural aspect ratio without pre-cropping.
  - `mod_news` and `news.twig` require `image_alt` and `image_credit` columns, `<figure class="detail-hero">`, `<figcaption>`, and JSON-LD `creditText`.
  - `tools/mcp_cli.py` contract specifies secret-opaque stdio bridge and `--editorial-safe` mutation blocking.
  - Radar `--scan-today` parses RSS/sitemap ISO dates with calendar date isolation in Europe/Istanbul.
  - Obsolete AGENTS.md rule references (Rules 2, 7, 10) identified across repo files.
  - Completed concrete test blueprints for 66 test cases across Features 24 to 36.
- **Unexplored areas**: None within the scope of Features 24 to 36.

## Key Decisions Made
- Organized Tier 1 tests into 13 modular test files under `tests/e2e/tier1/` (`test_f24_...py` through `test_f36_...py`), mapping 1:1 to features for granular filtering and discovery.
- Designed 66 distinct test cases (at least 5 per feature, 6 for F28).
- Finalized comprehensive handoff report at `.agents/teamwork/explorer_e2e_m2_3/handoff.md`.

## Artifact Index
- /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/explorer_e2e_m2_3/DISPATCH.md — Incoming dispatches
- /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/explorer_e2e_m2_3/BRIEFING.md — Working memory & state
- /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/explorer_e2e_m2_3/progress.md — Liveness heartbeat
- /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/explorer_e2e_m2_3/handoff.md — Final investigation report
