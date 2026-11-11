# Task Assignment: Worker (Milestone 3 Implementation)

You are the Worker for Milestone 3 (Image Pipeline, mod_news Media Credits & Swarm CLI Wrapper).
Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin
State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/worker_m3_1/

Read the following documents:
- /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/ORIGINAL_REQUEST.md
- /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/orchestrator_1/PROJECT.md
- /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/sub_orch_m3/SCOPE.md
- Blueprint 1: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/explorer_m3_1/handoff.md
- Blueprint 2: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/explorer_m3_2/handoff.md
- Blueprint 3: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/explorer_m3_3/handoff.md

MANDATORY INTEGRITY WARNING:
DO NOT CHEAT. All implementations must be genuine. DO NOT hardcode test results, create dummy/facade implementations, or circumvent the intended task. A teamwork_auditor will independently verify your work. Integrity violations WILL be detected and your work WILL be rejected.

Owned files:
1. .agents/skills/kevanger/scripts/process_news_image.py
2. mod/mod_news/mod_news.sql
3. mod/mod_news/model/mod_news.model.php
4. mod/mod_news/model/newses.model.php
5. mod/mod_news/index.php
6. mod/mod_news/validator/add.validator.php
7. mod/mod_news/validator/edit.validator.php
8. templates/amed/index/mod_news/news.twig
9. templates/admin/mod_news/edit.twig
10. templates/admin/mod_news/add.twig
11. tools/mcp_cli.py
(and lib/McpNewsTools.class.php if adding image_alt / image_credit schema properties to create_news and update_news).

Your implementation tasks:
1. `process_news_image.py`:
   - Implement `unwrap_cdn_image_url(url: str, base_url: str = "") -> str` and `canonical_image_key()`.
   - Separate `<article>` vs `<main>` depth tracking and exclude secondary containers (`<aside>`, `<nav>`, `<footer>`, `<header>`, and class/id matching `(sidebar|related|recommend|most-read|popular|son-haber|widget|ticker|footer|comment)`).
   - Restrict `+600` hero bonus to primary article elements; penalize excluded containers by `-2000`.
   - Cap `srcset` descriptors at `3840w` max (and `1200w` for proxy URLs unless verified by explicit `img` width) and boost candidate score by `+2000` when matching `og:image` / `twitter:image` stem.
   - Implement `--mode master` (default for `--from-article`): output unflattened, clean `>= 1200px` master image (WebP quality 90, <1.5 MB) preserving natural vertical aspect ratios for CMS multi-size generation without double lossy down/upscale, while retaining `--mode inline-16x9` (1280x720, <100 KB).
   - Regional watermark heuristics for top-right red bugs (Rudaw/K24) and bottom ticker/badges (AA/DHA/İHA/MA), with crop percentage logging in output JSON manifest.
   - Browser-grade HTTP headers (`Accept` and `Referer`) integrating `.agents/skills/_shared/editorial_http.py` with secure fallback.

2. `mod_news` Schema & Templates:
   - `mod/mod_news/mod_news.sql`: Add `image_alt VARCHAR(255) DEFAULT NULL` and `image_credit VARCHAR(255) DEFAULT NULL` after `image`.
   - `mod/mod_news/model/mod_news.model.php`: Add `$image_alt` and `$image_credit` properties and default values.
   - `mod/mod_news/model/newses.model.php`: Add self-healing `ensureTableColumns()` and column checks `hasImageAltColumn()` / `hasImageCreditColumn()` so existing environments auto-migrate and rebuild the `newses` view with `mod_news.image_alt` and `mod_news.image_credit`.
   - `mod/mod_news/index.php`: Sanitize with `strip_tags()`, `html_entity_decode()`, truncate to 255 chars, map empty to `null`, and decode entities in `cleanEntityFields()` / `_cleanNewsRow()`.
   - `mod/mod_news/validator/add.validator.php` & `edit.validator.php`: Optional length checks (1–255 chars) and Rule 9 citation update.
   - `templates/admin/mod_news/edit.twig` & `templates/admin/mod_news/add.twig`: Phone-only mobile inputs using `.m3-input-wrapper` and `.m3-input-lg` with 0 inline CSS/JS.
   - `templates/amed/index/mod_news/news.twig`: Upgrade `.detail-hero` to `<figure class="detail-hero">`, render `alt="{{ d.image_alt|default(d.name) }}"`, render `<figcaption class="news-hero-caption">{{ d.image_credit }}</figcaption>` conditionally, add `og:image:alt` and `twitter:image:alt`, and add `caption` and `creditText` to JSON-LD `ImageObject`. Zero inline CSS or JS.
   - (Optional if appropriate: `lib/McpNewsTools.class.php` to accept `image_alt` and `image_credit` in createNews and updateNews).

3. `tools/mcp_cli.py`:
   - Implement the complete, secret-opaque JSON-RPC 2.0 stdio client wrapping `~/.gemini/antigravity/bin/amed_bulletin_mcp.py` per Blueprint 3.
   - Zero credential exposure: no token flags or environment reading; child bridge handles host config; stderr regex scrubbing of tokens.
   - Dual CLI syntax: subcommands (`call <tool>`, `get-record --module <mod>`, `ping`, `list-tools`) and positional syntax (`<server> <tool> [args] [--editorial-safe]`).
   - Editorial safety policy (`--editorial-safe`, default true): unconditionally rejects `delete_*`, blocks `status="y"` publish attempts while forcing `status="n"` draft staging on news, publications, and FAQs, enforces `username="habermerkezi"` on `create_news`, and validates local featured image file existence.
   - Standard response envelope on stdout, `environment.name` printed to stderr, exit code 0 on success, non-zero on failure.
   - Offline testability with `MCP_CLI_BRIDGE` override and `--dry-run` flag.

Verification requirements:
- Run `php -l` on all touched PHP files.
- Run `python3 -m py_compile` on all touched Python files.
- Run `python3 tools/code_separation_guard.py --path templates/amed/index/mod_news/news.twig`.
- Run `python3 tools/i18n_guard.py`.
- Run `python3 tools/module_boundary_guard.py --all`.
- Test `process_news_image.py` against `tests/e2e/fixtures/editorial/mock_nextjs_sidebar_article.html`.
- Test `tools/mcp_cli.py` with `--dry-run` and policy checks.
- Document all verification commands and outputs in your handoff report.

Write your handoff report to `.agents/teamwork/worker_m3_1/handoff.md`.
Notify me via send_message when finished.
