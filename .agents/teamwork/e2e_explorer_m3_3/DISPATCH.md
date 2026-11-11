# Dispatch: Explorer 3 (Milestone E2E-M3 - Tier 2 Boundary & Corner Cases: Features 24-36)

**Working Directory**: `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin`  
**State Folder**: `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/e2e_explorer_m3_3/`  
**Target Milestone**: `E2E-M3` (Tier 2 Boundary & Corner Cases)  
**Assigned Scope**: Features 24 through 36 in `PROJECT.md § Feature Inventory`  

## Mandatory Inputs to Read
1. `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/ORIGINAL_REQUEST.md` (MUST read first)
2. `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/orchestrator_1/PROJECT.md`
3. `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/e2e_orch_gen2/SCOPE.md`
4. `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/TEST_INFRA.md`
5. Existing Tier 1 tests in `tests/e2e/tier1/test_f24_*.py` through `test_f36_*.py` and test harness under `tests/e2e/harness/`

## Objective
Analyze boundary conditions, edge cases, error-prone inputs, limits, empty inputs, max sizes, invalid types, zero/negative values, and domain-specific extremes for Features 24-36.
Design concrete, detailed test blueprints (≥5 per feature, target ≥65 test cases) for:
- Feature 24: `process_news_image.py` Next.js CDN proxy unwrap (double encoded proxy URLs, missing `url=` param, invalid internal origin, non-image content-type)
- Feature 25: `process_news_image.py` hero scoring & srcset cap (hero candidate outside `<article>`, tiny images < 300px gaining bogus hero scores, srcset width > 3840w clamped strictly to 3840, aspect ratio distortion > 2.5:1)
- Feature 26: `process_news_image.py` `--mode master` (exactly 1200px width boundary, 1199px rejection/upscaling, corrupt JPEG headers, huge 50MB files, format conversion WebP/AVIF to master)
- Feature 27: `mod_news` `image_alt` & `image_credit` columns (max length limits 255/500 chars, null vs empty string, HTML entities / tags in alt text, SQL injection payloads)
- Feature 28: `news.twig` hero figure & JSON-LD (missing image_credit fallback, missing image_alt fallback, quotation marks in credits breaking JSON-LD, missing image URL)
- Feature 29: `tools/mcp_cli.py` Swarm CLI wrapper (empty arguments, dangerous mutations blocked without `--editorial-safe`, non-zero exit code propagation, secret masking in stderr/stdout)
- Feature 30: `source_catalog.py` `--scan-today` (empty feed / 0 items, malformed RSS/Atom XML, feed items with missing publication dates, sitemaps with >50,000 URLs)
- Feature 31: `audit_seo.py` alignment (headline exactly 39 vs 40 vs 70 vs 71 chars, summary exactly 89 vs 90 vs 120 vs 121 chars, H1/H2 hierarchy skipping H1 -> H3, empty body text)
- Feature 32: Skill documentation synchronization (missing mandatory sections, invalid frontmatter YAML, broken skill relative links)
- Feature 33: Correct AGENTS.md rule number citations (citation check across tools and docs against actual 9 AGENTS.md rules, detecting off-by-one or obsolete rule references)
- Feature 34: Full Regression Suite & MCP Smoke Tests (`tasks/mcp_smoke.php` timeout handling, non-zero return on smoke test failure, pipe errors in `verify_news_pipeline.sh`)
- Feature 35: Version Documentation Update (`docs/VERSION.md` missing v1.0.63, mismatched version string in `site.config.php`, unreleased changes header format)
- Feature 36: Git Release Tagging & Push (dirty working tree check, existing v1.0.63 tag handling, push failure recovery, verifying tag points to HEAD commit)

## Output Requirements
Produce a comprehensive blueprint report at `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/e2e_explorer_m3_3/handoff.md`.
Document:
1. Feature-by-feature boundary analysis
2. Exact test case names, target file paths (`tests/e2e/tier2/test_f24_*.py` .. `test_f36_*.py`), inputs, expected outcomes, and assertion mechanisms
3. Opaque-box execution commands
4. Send a completion message when done. DO NOT modify any code.


## 2026-11-11T14:05:05Z
You are Explorer 3 for Milestone E2E-M3 (Tier 2 Boundary & Corner Cases: Features 24-36).
Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin
State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/e2e_explorer_m3_3/

Read your dispatch file at:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/e2e_explorer_m3_3/DISPATCH.md
and ORIGINAL_REQUEST.md at:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/ORIGINAL_REQUEST.md

Your role is read-only exploration and test case blueprint design for Features 24-36 under tests/e2e/tier2/.
Write your comprehensive blueprint report to:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/e2e_explorer_m3_3/handoff.md
DO NOT modify source code or tests.
When complete, notify me with send_message.
