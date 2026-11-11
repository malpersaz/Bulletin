# Scope: Milestone 3 — Image Pipeline, mod_news Media Credits & Swarm CLI Wrapper

## Scope Overview
Implement Phase 3 visual intake, schema enhancements, and CLI bridge:
1. `process_news_image.py`:
   - Unwrap Next.js / CDN proxy URLs (`/_next/image?url=...`) before deduplication so distinct images are not collapsed.
   - Scope hero scoring to `<article>` tags only, avoiding sidebar/nav thumbnail score inflation.
   - Cap responsive `srcset` descriptor score at 3840w.
   - Implement `--mode master` outputting clean, unflattened `>= 1200px` master images for CMS multi-size generation.
2. `mod_news` Schema & Template Modernization:
   - Add `image_alt` (varchar 255) and `image_credit` (varchar 255) to `mod_news.sql`, `mod_news_model.php`, `newses_model.php` (view), and controller.
   - Update admin templates `templates/admin/mod_news/edit.twig` (and `add.twig`) with phone-only mobile-first layout inputs.
   - Update `templates/amed/index/mod_news/news.twig` to render `<figure class="detail-hero">` and `<figcaption class="news-hero-caption">` when `image_credit` or `image_alt` is present.
   - Update JSON-LD schema with `caption` and `creditText` for `ImageObject`.
   - Ensure strict code separation: zero inline CSS (`style="..."`), zero inline JS.
3. `tools/mcp_cli.py`:
   - Create secret-opaque stdio CLI wrapper around `~/.gemini/antigravity/bin/amed_bulletin_mcp.py`.
   - Support `--editorial-safe` flag enforcing draft status (`status: "n"`) on all staging mutations (`create_news`, `batch_create_faqs`).
   - Format tool arguments as JSON on stdin; return standard JSON envelope on stdout; exit 0 on success, non-zero on error.

## Files Owned
- .agents/skills/kevanger/scripts/process_news_image.py
- mod/mod_news/mod_news.sql
- mod/mod_news/model/mod_news_model.php
- mod/mod_news/model/newses_model.php
- mod/mod_news/controller/index.php
- mod/mod_news/validator/add.validator.php
- mod/mod_news/validator/edit.validator.php
- templates/amed/index/mod_news/news.twig
- templates/admin/mod_news/edit.twig
- templates/admin/mod_news/add.twig
- tools/mcp_cli.py

## Acceptance Criteria
- `process_news_image.py` correctly unwraps Next.js proxy URLs, scores article elements above sidebars, caps `srcset` descriptors, and supports `--mode master`.
- `mod_news` database schema and models include `image_alt` and `image_credit`.
- `templates/amed/index/mod_news/news.twig` renders `<figure class="detail-hero">` and `<figcaption>` without inline styles or scripts.
- `tools/mcp_cli.py` works via stdio JSON-RPC and blocks published mutations under `--editorial-safe`.
- All PHP files pass `php -l`.
- All Python files pass `python3 -m py_compile`.
- `tools/code_separation_guard.py` passes (0 inline styles/scripts).
- `tools/i18n_guard.py` passes on touched files.
- `tools/module_boundary_guard.py --all` passes.


## Milestone Status: DONE
- Gate passed on Iteration 2 (Audit CLEAN, Reviewers APPROVE, Challengers APPROVE).
- All acceptance criteria verified empirically.
- Key artifacts:
  - `process_news_image.py`: Next.js unwrap, article scoping, 3840w cap, `--mode master`.
  - `mod_news` schema, models, validators, controllers, templates updated with `image_alt` and `image_credit`.
  - `tools/mcp_cli.py`: Secret-opaque stdio CLI wrapper with `--editorial-safe` policy and positional subcommand routing.
