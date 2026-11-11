# Milestone 3 Handoff Report: Image Pipeline, mod_news Media Credits & Swarm CLI Wrapper

**Milestone**: M3 (Features 24–29)  
**Status**: **DONE (Gate Result: PASS)**  
**Date**: 2026-11-11  
**Author**: Sub-Orchestrator M3 (`1cb4034b-124b-423c-b681-70f423565312`)  
**Parent**: Top-Level Project Orchestrator (`20708bfb-3a2e-4377-b059-f0289f40dff6`)  

---

## 1. Observation

All 6 features assigned to Milestone 3 have been implemented, empirically challenged, reviewed, and forensically audited with zero defects remaining:

1. **Feature 24 & 25 & 26 (`process_news_image.py`)**:
   - `unwrap_cdn_image_url()`: Recursively unwraps Next.js (`/_next/image?url=...`), Vercel (`/_vercel/image`), WordPress Photon (`i0.wp.com`), and Cloudflare (`/cdn-cgi/image/`) targets before deduplication and ranking.
   - `canonical_image_key()`: Strips dynamic sizing/formatting parameters (`w`, `q`, `width`, `height`, `fit`, `auto`) while preserving distinguishing identifiers (`?id=`, `?token=`).
   - DOM Scoping: `ArticleImageCandidateExtractor` separates `<article>` depth from `<main>` depth, applies a `-2000` penalty to excluded containers (`<aside>`, `<nav>`, `<footer>`, `<header>`, and class/id matching `(sidebar|related|recommend|most-read|popular|son-haber|widget|ticker|footer|comment)`), and awards a `+600` hero bonus strictly to lead article images.
   - `srcset` descriptor cap: Hard-capped at `3840w`, and capped at `1200w` on proxy URLs unless corroborated by explicit image dimensions.
   - Metadata stem cross-anchoring: Stems from `<meta property="og:image">` and `twitter:image` cross-reference candidate image stems, awarding a `+2000` boost to guarantee the authentic hero image wins over sidebar cards.
   - Modernized `--mode master` (default for `--from-article`): Scales to `max_width = 1920px` while preserving natural vertical aspect ratios without pre-flattening to 16:9, encoding at WebP quality 90 (<1.5 MB) so CMS `ImageService::_resize` generates crisp, non-upscaled `/` (`1280x720`), `/4x3` (`1200x900`), and `/1x1` (`1200x1200`) variants. Retains `--mode inline-16x9` (1280x720, <100 KB) for secondary inline figures.
   - Watermark heuristics: Identifies top-right red bugs (Rûdaw/K24) and bottom ticker luminance bands (AA/DHA/İHA), logging crop percentages in output JSON.
   - Browser-grade transport: Emits standard browser `Accept` and `Referer` headers via `editorial_http` integration with fallback.

2. **Feature 27 & 28 (`mod_news` Media Credits & Templates)**:
   - Database schema: `mod/mod_news/mod_news.sql` updated with `image_alt varchar(255) DEFAULT NULL` and `image_credit varchar(255) DEFAULT NULL`.
   - ORM models: `mod_news.model.php` exposes `$image_alt` and `$image_credit`.
   - Self-healing view migration: `newses.model.php` includes `ensureTableColumns()` which detects missing columns, alters `mod_news` if necessary, and recreates the `newses` view on the fly. Verified live in Docker container `bulletin_app`.
   - Controllers & Helpers: `mod_news/index.php` and `mod_news.helper.php` clean HTML entities, truncate to 255 chars, and map empty strings to `null`.
   - MCP tools: `lib/McpNewsTools.class.php` accepts `image_alt` and `image_credit` in `create_news` and `update_news`.
   - Validators: `add.validator.php` and `edit.validator.php` enforce string length (1–255 chars) and cite AGENTS.md Rule 9.
   - Localizations: Updated in all 11 language packs (`ar`, `ckb`, `de`, `en`, `fa`, `fr`, `ku`, `ru`, `sv`, `tr`, `zza`).
   - Admin mobile templates: `templates/admin/mod_news/edit.twig`, `add.twig`, `wizard_content.twig`, and `script.js` provide phone-only mobile inputs using `.m3-input-wrapper` and `.m3-floating-label` with zero inline CSS/JS.
   - Public template: `templates/amed/index/mod_news/news.twig` renders `<figure class="detail-hero">`, conditional `<figcaption class="news-hero-caption">{{ d.image_credit }}</figcaption>`, `alt="{{ d.image_alt|default(d.name) }}"`, `og:image:alt`, `twitter:image:alt`, and Schema.org JSON-LD `ImageObject` with `caption` and `creditText`. Zero inline styles or scripts (`tools/code_separation_guard.py` passed with 0 violations).

3. **Feature 29 (`tools/mcp_cli.py`)**:
   - Secret-opaque stdio CLI wrapper around `~/.gemini/antigravity/bin/amed_bulletin_mcp.py`.
   - Zero credential exposure: no token arguments; stderr regex scrubbing of `amcp_*` tokens.
   - Dual CLI syntax: supports subcommands (`call`, `get-record`, `ping`, `list-tools`) AND positional server syntax (`python3 tools/mcp_cli.py amed-bulletin-mcp ping`, `list-tools`, etc.).
   - Strict editorial safety policy (`--editorial-safe` default): blocks `delete_*` tools, rejects `status="y"`, enforces draft `status="n"` on all staging mutations, forces author `username="habermerkezi"` on `create_news`, and validates `image.file_path` on disk for both `create_news` and `update_news`.
   - Test suite: `tests/test_mcp_cli.py` (17 tests) and empirical stress suite `tests/test_m3_2_empirical_stress.py` (24 tests) all pass.

---

## 2. Logic Chain

1. **Image Selection & Upscaling Fix**:
   - *Problem*: Next.js query stripping collapsed 54 candidates into 1, `<main>` inclusion awarded sidebar cards an article bonus, and 3840w descriptors trumped 1024px `og:image`. Center-cropping to 1200x675 caused double lossy upscaling in the CMS (`675px -> 900px/1200px`).
   - *Solution*: Unwrapping Next.js origin URLs before deduplicating, scoping depth to `<article>`, capping descriptors, boosting `og:image` stem matches (+2000), and outputting full-height masters (`--mode master`) directly solved the photo hijack and visual degradation.
2. **Media Credits & Strict Code Separation**:
   - *Problem*: Photo credits were previously pasted into `articleBody` HTML, corrupting word counts and lacking Schema.org structured attribution.
   - *Solution*: Native `image_alt` and `image_credit` columns across database, ORM, controllers, views, admin forms, and public templates with pure CSS class styling (`.news-hero-caption`), preserving 100% strict code separation.
3. **Subagent CLI Bridge & Policy Gate**:
   - *Problem*: Subagents faced sandbox context blocks on MCP tool execution and lacked safe CLI access.
   - *Solution*: `tools/mcp_cli.py` provides secret-opaque, policy-enforced stdio execution for subagents and CI pipelines with positional and subcommand syntax.

---

## 3. Caveats

- Live Docker testing confirmed self-healing view recreation in `bulletin_app`. If deployed to production environments with pre-existing tables, `newses_model::__construct()` will execute dynamic migration automatically upon first model instantiation.
- Network fetches in `process_news_image.py` default to verified TLS via `editorial_http`. If testing against mock URLs offline, pass `--no-network` or local file paths.

---

## 4. Conclusion

**Gate Verdict**: **PASS** (Iteration 2).
- Reviewer verdicts: APPROVE (reviewer_m3_1, reviewer_m3_2, reviewer_m3_3).
- Challenger verdicts: APPROVE (challenger_m3_1, challenger_m3_3).
- Auditor verdicts: CLEAN (auditor_m3_1, auditor_m3_2).
- Quality guards: 100% clean (`code_separation_guard.py`, `i18n_guard.py`, `module_boundary_guard.py --all`).
- Tests: 100% passing (17 unit tests in `test_mcp_cli.py`, 24 empirical tests in `test_m3_2_empirical_stress.py`, 15 empirical tests in `test_m3_image_pipeline_empirical.py`, 83 container smoke tests in `tasks/mcp_smoke.php`).

Milestone 3 is complete and ready for handoff to the Project Orchestrator to proceed with Milestone 5 (Release).

---

## 5. Verification Method

To verify Milestone 3 independently:
```bash
# 1. Verify syntax
php -l mod/mod_news/model/mod_news.model.php mod/mod_news/model/newses.model.php mod/mod_news/index.php mod/mod_news/validator/add.validator.php mod/mod_news/validator/edit.validator.php lib/McpNewsTools.class.php
python3 -m py_compile .agents/skills/kevanger/scripts/process_news_image.py tools/mcp_cli.py tests/test_mcp_cli.py tests/test_m3_image_pipeline_empirical.py tests/test_m3_2_empirical_stress.py

# 2. Run quality guards
python3 tools/code_separation_guard.py --path templates/amed/index/mod_news/news.twig
python3 tools/i18n_guard.py
python3 tools/module_boundary_guard.py --all

# 3. Run unit & empirical test suites
python3 -m unittest tests/test_mcp_cli.py
python3 -m unittest tests/test_m3_image_pipeline_empirical.py
python3 -m unittest tests/test_m3_2_empirical_stress.py

# 4. Verify positional CLI commands
python3 tools/mcp_cli.py amed-bulletin-mcp ping
python3 tools/mcp_cli.py amed-bulletin-mcp list-tools
```
