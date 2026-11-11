# BRIEFING — 2026-11-11T12:40:00Z

## Mission
Implement Milestone 3: Image Pipeline modernizations (`process_news_image.py`), mod_news media credit schema and public/admin templates, and Swarm CLI Wrapper (`tools/mcp_cli.py`).

## 🔒 My Identity
- Archetype: worker
- Roles: implementer, qa, specialist
- Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin
- State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/worker_m3_1/
- Original parent: 1cb4034b-124b-423c-b681-70f423565312
- Milestone: Milestone 3

## 🔒 Key Constraints
- Strict Code Separation: 0 inline styles (`style="..."` or `<style>`), 0 inline scripts (`<script>` or event handlers).
- Mobile-first phone-only admin surface under `templates/admin/`.
- Zero credential exposure in `tools/mcp_cli.py`.
- Preserve English identifiers in code, descriptive English i18n keys in `en.php`.
- Zero cross-repo autonomous writes (strictly isolated repository).
- All changes must pass syntax (`php -l`, `python3 -m py_compile`), `code_separation_guard.py`, `i18n_guard.py`, and `module_boundary_guard.py`.

## Current Parent
- Conversation ID: 1cb4034b-124b-423c-b681-70f423565312
- Updated: 2026-11-11T12:40:00Z

## Task Summary
- **What to build**:
  1. `process_news_image.py`: CDN unwrap, article/main DOM depth scoping, container exclusion, srcset cap, og:image stem anchoring, `--mode master`, regional watermark heuristics, editorial HTTP transport.
  2. `mod_news` Schema & Templates: `image_alt` and `image_credit` columns in SQL and models, self-healing view in `newses.model.php`, controller sanitization, validators, admin edit/add templates, public `news.twig` with `<figure>` / `<figcaption>` and JSON-LD ImageObject.
  3. `tools/mcp_cli.py`: Secret-opaque stdio CLI wrapper with `--editorial-safe`, subcommands, positional syntax, stdout JSON envelope, and dry-run testing.
- **Success criteria**:
  - All automated guards pass.
  - Regression tests on mock article HTML and mcp_cli pass.
  - Full handoff report produced.
- **Interface contracts**: `.agents/teamwork/orchestrator_1/PROJECT.md` & `.agents/teamwork/sub_orch_m3/SCOPE.md`.
- **Code layout**: Root repo layout per `PROJECT.md`.

## Key Decisions Made
- Implemented `--mode master` (default) preserving natural vertical aspect ratio for CMS multi-size derivation without double down/upscaling, and `--mode inline-16x9` for backward compatibility.
- Implemented automatic column check and self-healing view rebuild in `newses.model.php` (`ensureTableColumns()`, `hasImageAltColumn()`, `hasImageCreditColumn()`).
- Updated `lib/McpNewsTools.class.php` to accept `image_alt` and `image_credit` in both schema definitions and mutation handlers.
- Updated all 11 language packs with `image_alt` and `image_credit` column definitions for 100% i18n parity.
- Implemented secret-opaque `tools/mcp_cli.py` with stdio JSON-RPC 2.0 communication, regex stderr token scrubbing, and complete unit test coverage in `tests/test_mcp_cli.py`.

## Artifact Index
- `.agents/skills/kevanger/scripts/process_news_image.py` — Upgraded image extraction and processing pipeline
- `mod/mod_news/mod_news.sql` — SQL DDL with `image_alt` and `image_credit`
- `mod/mod_news/model/mod_news.model.php` — Model properties and defaults
- `mod/mod_news/model/newses.model.php` — Self-healing table columns and view rebuild
- `mod/mod_news/index.php` — Text entity cleaning, truncation, and entity decoding
- `mod/index/helper/mod_news.helper.php` — Public row text cleaning for new fields
- `lib/McpNewsTools.class.php` — MCP tool schema and handling for image_alt/credit
- `mod/mod_news/validator/add.validator.php` — Length validation and AGENTS.md Rule 9 citation
- `mod/mod_news/validator/edit.validator.php` — Length validation and AGENTS.md Rule 9 citation
- `mod/mod_news/lang/*.php` — 11 localized language files with new column labels
- `templates/admin/mod_news/edit.twig` — Phone-only admin inputs
- `templates/admin/partials/wizard_content.twig` — Wizard inputs
- `templates/admin/assets/js/script.js` — Wizard formData collection
- `templates/amed/index/mod_news/news.twig` — Public figure/figcaption and JSON-LD ImageObject
- `templates/amed/static/css/detail-foundation.css` — Hero caption styling
- `tools/mcp_cli.py` — Stdio CLI wrapper
- `tests/test_mcp_cli.py` — Unit test suite for CLI wrapper

## Change Tracker
- **Files modified**:
  - `.agents/skills/kevanger/scripts/process_news_image.py`: CDN unwrapping, DOM scoping, srcset cap, watermark heuristics, master mode.
  - `mod/mod_news/mod_news.sql`: Added `image_alt` and `image_credit` columns.
  - `mod/mod_news/model/mod_news.model.php`: Added `$image_alt` and `$image_credit` properties and defaults.
  - `mod/mod_news/model/newses.model.php`: Added self-healing table columns and view rebuild.
  - `mod/mod_news/index.php`: Added field sanitization, truncation, and entity cleaning.
  - `mod/index/helper/mod_news.helper.php`: Added field entity cleaning in `_cleanNewsRow`.
  - `lib/McpNewsTools.class.php`: Added `image_alt` and `image_credit` to schemas and handlers.
  - `mod/mod_news/validator/add.validator.php`: Length checks and Rule 9 citation.
  - `mod/mod_news/validator/edit.validator.php`: Length checks and Rule 9 citation.
  - `mod/mod_news/lang/{ar,ckb,de,en,fa,fr,ku,ru,sv,tr,zza}.php`: Added localized labels.
  - `templates/admin/mod_news/edit.twig`: Added phone-only inputs.
  - `templates/admin/partials/wizard_content.twig`: Added wizard inputs.
  - `templates/admin/assets/js/script.js`: Added wizard input collection.
  - `templates/amed/index/mod_news/news.twig`: Upgraded hero to `<figure>` and `<figcaption>`, JSON-LD schema.
  - `templates/amed/static/css/detail-foundation.css`: Added caption CSS classes.
  - `tools/mcp_cli.py`: Created stdio CLI wrapper.
  - `tests/test_mcp_cli.py`: Created test suite.
- **Build status**: All checks passed (syntax, py_compile, unit tests, code separation, i18n, module boundary).
- **Pending issues**: None.

## Quality Status
- **Build/test result**: PASS (17/17 CLI tests, 19/19 HTTP stress tests, 5/5 Bug tests, Next.js candidate ranking test).
- **Lint status**: 0 violations (code separation guard 0 violations, i18n guard 0 violations, module boundary guard 0 violations).
- **Tests added/modified**: `tests/test_mcp_cli.py` (17 tests).
