# Progress — worker_m3_1

Last visited: 2026-11-11T12:41:00Z
Status: Completed

## Completed
- Track 1: `process_news_image.py` enhancements:
  - `unwrap_cdn_image_url`, `extract_filename_stem`, `canonical_image_key`.
  - DOM depth tracking (<article> vs <main>), container exclusion (`EXCLUDE_CONTAINER_RE`).
  - Srcset descriptor cap (`3840w`, `1200w` for proxies), `og:image` stem cross-anchoring (+2000 bonus).
  - `--mode master` (default) preserving natural vertical aspect ratio (>= 1200px width, max 1920px width, WebP quality 90, <1.5 MB) and `--mode inline-16x9` (1280x720, <100 KB).
  - Regional watermark heuristics (Rudaw/K24 top-right red bug, AA/DHA/İHA bottom ticker bar).
  - Shared `editorial_http` integration with fallback.
- Track 2: `mod_news` Schema & Templates modernization:
  - Database schema (`mod/mod_news/mod_news.sql`): `image_alt` and `image_credit` columns.
  - ORM model (`mod_news.model.php`): `$image_alt` and `$image_credit` properties and defaults.
  - View model (`newses.model.php`): `ensureTableColumns()`, self-healing view migration.
  - Controller (`mod_news/index.php`): HTML tag stripping, entity decoding, length truncation (255 chars), null mapping, `cleanEntityFields` override.
  - Public helper (`mod_news.helper.php`): text cleaning for new fields.
  - MCP tool (`lib/McpNewsTools.class.php`): accepted in `create_news` and `update_news` schemas and handlers.
  - Validators (`add.validator.php` & `edit.validator.php`): length validation and AGENTS.md Rule 9 citation update.
  - Language files (11 files in `mod/mod_news/lang/`): localized labels.
  - Admin templates (`edit.twig`, `wizard_content.twig`, `script.js`): phone-only inputs.
  - Public template (`news.twig`): upgraded to `<figure class="detail-hero">`, `<figcaption class="news-hero-caption">`, `og:image:alt`, `twitter:image:alt`, JSON-LD `ImageObject` with `caption` and `creditText`.
  - Theme CSS (`detail-foundation.css`): `.news-hero-caption` styling for light/dark modes.
- Track 3: `tools/mcp_cli.py` & Tests:
  - Secret-opaque JSON-RPC 2.0 stdio client wrapping `amed_bulletin_mcp.py`.
  - Stderr regex redaction for API tokens (`amcp_*`).
  - Dual syntax support (subcommands and positional `<server> <tool> [args]`).
  - Editorial safety policy gate (`--editorial-safe`): block `delete_*`, enforce draft `status="n"`, enforce author `username="habermerkezi"`, validate image existence.
  - `--dry-run` flag and offline mock bridge support via `MCP_CLI_BRIDGE`.
  - Unit test suite (`tests/test_mcp_cli.py`) with 17 passing tests.
- All verification guards passed: `php -l`, `python3 -m py_compile`, `code_separation_guard.py`, `i18n_guard.py`, `module_boundary_guard.py --all`.
- Live container DB verification: all 4 column checks report `EXISTS`.
