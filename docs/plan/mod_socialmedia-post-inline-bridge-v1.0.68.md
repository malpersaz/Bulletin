# Architectural Specification & Plan: `mod_socialmedia_post` Inline Social Post Embed Bridge & `mod_video_embed` Scope Correction (v1.0.68)

**Target Environment:** Local Docker stack (`bulletin_app` PHP 8.5.12, `bulletin_web` nginx 1.31.3, `bulletin_db` MySQL 8.0, database `bulletin`) & Host Linux (`PHP 8.5.4`)
**Repository:** `AF/Bulletin` (`/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin`)
**Date:** 2026-11-13
**Target Release:** `v1.0.68` (current shipped GitHub/repo version: `v1.0.67` at tag `v1.0.67` / `$site['version'] = '1.0.67'`)
**Governing Standard:** Amed Framework Core & Task Quality Guards (Phone-Only Admin Contract, Strict Code Separation, Real i18n Keys across all 11 active locales, Wikipedia-style UTF-8 URL Parity, SQLite/MySQL Dual-Adapter Parity, English code/docs)
**Status:** Planned / Ready for Implementation

---

## 0. Skills & Governance Statement (per `AGENTS.md`, `/.agents/INDEX.md`, `/docs/CODE_QUALITY_AGENT_STRATEGY.md` & `.gemini/GEMINI.md`)

| Skill / Quality Guard | Relevance | Governance Requirement |
| :--- | :--- | :--- |
| `module-modernization-check` | **Governing Standard** | New modules must follow `BaseModuleController` (`$has_social_posts`, `ensureBridgeIntegrations()`, `echoSuccessResponse()`), `installerbase` patterns, `ModuleRegistry` & `InstallerModuleCatalog` registration, `BridgeCleanupService` + `McpCrudToolsBase::removeBridgeRelations()` cleanup, and SQLite-compatible `.sql` schemas (`SqliteSqlTransformer`). |
| `admin-mobile-check` | **Governing Standard** | All admin screens must strictly adhere to the phone-only mobile contract (max 430px viewport, `>= 44px` touch targets, bottom-sheet patterns, no horizontal scrollbars) and register in both the Control Panel launcher (`mod/mod_user/index.php`) and `templates/admin/partials/sidebar.twig`. |
| `i18n-guard` | **Governing Standard** | All user-facing strings must have descriptive English keys in `en.php` with 100% parity across all 11 active locales (`ar, ckb, de, en, fa, fr, ku, ru, sv, tr, zza`), plus missing locale files where shared admin dictionaries (`mod/management/lang/`, `mod/mod_user/lang/`, `mod/mod_video_embed/lang/`) are touched. |
| `Strict Code Separation` | **Governing Standard** | Zero inline CSS (`style="..."`, `<style>`) and zero inline JS (`<script>`, `on*=`). Provider facades, resize listeners (mutating the HTML `height` attribute, never `element.style`), and theme sync live in external JS (`static/js/`); styling in external CSS (`static/css/`). |
| `boundary-enforcer` | **Governing Standard** | Preserve Bulletin journalism domain isolation; zero foreign commercial/assessment modules (`tools/module_boundary_guard.py`). |
| `github-release` & `task-validation` | **Post-Implementation Gate** | Track active work under `## v1.0.68 (In Progress)` in `docs/VERSION.md`; before tagging `v1.0.68`, run quality guards (`code_separation_guard.py`, `module_boundary_guard.py`, `php -l`) and synchronize `docs/VERSION.md` (`## [v1.0.68] - YYYY-MM-DD`) with `$site['version'] = '1.0.68'` in `config/site.config.php`. |

---

## 1. Problem Definition

### 1.1 Misplaced Responsibility & Bypass Paths in `mod_video_embed`
`mod_video_embed` was designed for **video** embeds (YouTube, Vimeo, X **video** posts) rendered in the dedicated `#mod_video` surfaces of detail pages (`templates/amed/index/mod_news/news.twig:369` hero video container and `news.twig:485` inline video container). Although `VideoEmbedProviderService::fetchXMetadata()` (`lib/VideoEmbedProviderService.class.php:252-258`) already throws `autofill.no_video_in_post` when syndication JSON reports no video, **non-video X status URLs still enter `mod_video_embed`** through five concrete gaps:

1. `VideoEmbedProviderService::fetchXMetadata()` (`lines 253-254`) inspects only `$json['mediaDetails'][0]['type']` instead of scanning **all** `$json['mediaDetails']` entries (misclassifying mixed-media tweets whose first attachment is a photo and second is a video).
2. When `cdn.syndication.twimg.com` returns non-2xx/429, `fetchXMetadata()` (`lines 364-403`) falls back to `publish.twitter.com/oembed` and returns `'is_partial' => true` **without** verifying video presence, allowing text/photo tweets through `fetchMetadata()`.
3. `McpVideoTools::createVideoEmbed()` (`lib/McpVideoTools.class.php:1343`) catches `Throwable` from `fetchMetadata()` and still builds the X iframe (`line 1396`), while `McpVideoTools::updateVideoEmbed()` (`line 1517`) never calls `fetchMetadata()` when `url` is updated.
4. `mod/mod_video_embed/validator/add.validator.php` and `edit.validator.php` (`line 222`) only invoke `sanitizeOrRebuildEmbed()` and do not enforce video-entity presence when required form fields are submitted directly.
5. The quick-add video wizard (`templates/admin/partials/wizard_video_embed.twig`, conditionally included at `templates/admin/layout.twig:81`, driven by `prepareAndSubmitVideoEmbedWizard` in `templates/admin/assets/js/script.js:4354-4455`) posts directly to `mod_video_embed/add` without calling `autofill_metadata`.

Consequences:
- Pollutes the semantic "Videos" (`lang.index.mod_video_embed.title`) surfaces with text/photo social posts.
- Renders a 550px-wide tweet card inside a 16:9-oriented video player layout.
- **Historical incident context:** `mod_video_embed` record **ID 2** (Sundar Pichai text-only announcement, `status = 'n'`) entered via this path (already removed from the local `bulletin` dev database where only YouTube row `id = 3` remains, but deployed `v1.0.67` databases may still hold legacy non-video X rows).

### 1.2 No First-Class Home for Inline Social Posts
Editors need X / Facebook / Instagram posts rendered **inline at an exact paragraph position in article prose** — reusable across records, searchable, attributable, and governed by RBAC/ACL. Raw HTML in `content` is rejected: unmanaged, unsearchable, and incompatible with `Helper::clear_html()` figure semantics.

### 1.3 Strategic Decision
Introduce **`mod_socialmedia_post`**: a social post embed module backed by a **reusable bridge link table (`mod_socialmedia_post_link`) + host-module registry (`mod_socialmedia_post_mod`)** and a dedicated provider/token service (`SocialPostProviderService`). Simultaneously **close all five non-video bypasses in `mod_video_embed`** so `mod_video_embed` accepts only verified video URLs (e.g. `https://x.com/Google/status/2120941984148041901/video/1`, bare X status URLs whose syndication payload contains a `video`/`animated_gif` media entity, YouTube/Vimeo videos, and Facebook video URLs).

---

## 2. System Architecture

```
 Admin (mod_news add/edit/wizard)          Host detail render & syndication surfaces
 ┌──────────────────────────────┐          ┌──────────────────────────────────────────────┐
 │ Action bar: "Add Social      │          │ 1. Strip [socialpost:N] via stripTokens()    │
 │ Post" → bottom-sheet:        │          │    for metrics, JSON-LD articleBody, meta/OG │
 │ paste URL →                  │          │    descriptions, RSS, rss/social.xml,        │
 │ SocialPostProviderService    │          │    tools/saveText, and LlmsService           │
 │ detect → fetch meta          │          │ 2. Expose d.rendered_content via             │
 │ → create mod_socialmedia_    │          │    renderTokens(): unwrap <p>[socialpost:7]  │
 │   post (and link row on edit,│          │    </p> → <figure class="social-post-embed"> │
 │   or queue social_post_ids[] │          │    (preserving news.twig paragraph splits);  │
 │   for afterSave() on add)    │          │    append unreferenced bound rows after prose│
 │ → insert [socialpost:7]      │          │ 3. static/js/social-post.js: facade + theme  │
 │   at TinyMCE cursor          │          │    sync + origin-checked height attribute    │
 └──────────────────────────────┘          │ 4. static/css/social-post.css                │
                                           └──────────────────────────────────────────────┘
```

### 2.1 Module Boundaries (after this plan)

| Concern | Module | Accepts |
| :--- | :--- | :--- |
| Video content (dedicated `#mod_video` hero & inline video surfaces) | `mod_video_embed` | YouTube / YouTube Shorts / Vimeo URLs; X **video** URLs (`/status/{id}/video/{n}` or status URLs verified via syndication metadata to contain a `video` or `animated_gif` entity across any `mediaDetails` item); Facebook **video** URLs (`facebook.com/plugins/video.php`, `facebook.com/watch/?v={id}`, `facebook.com/{page}/videos/{id}`, `fb.watch/{token}`). Non-video X/FB/IG post URLs are **rejected** at write/import time with `err_no_video_use_social_post`. |
| Social posts (inline in prose or post-prose fallback, any media type) | `mod_socialmedia_post` | X status URLs, Facebook post/video URLs, Instagram post (`/p/`) and reel (`/reel/`) URLs. Posts containing video are also valid here when the editor wants the **full social post card** rather than a standalone video player. |

---

## 3. Database Schema (MySQL 8.0 & SQLite Dual-Adapter Compatible)

All `.sql` files under `installer/` MUST use `CREATE TABLE IF NOT EXISTS` and contain **zero inline `--` SQL comments** inside `CREATE TABLE (...)` blocks so `installer/app/SqliteSqlTransformer.php` and `installerbase::install_sql_file()` transform and split statements cleanly on both MySQL and SQLite. Any raw SQL queries in controllers/services must respect `$this->db->getAdapterType()`.

### 3.1 `installer/mod_socialmedia_post/mod_socialmedia_post.sql`
- `post_id` is `VARCHAR(128)` (not `VARCHAR(64)`) because Facebook `pfbid0...` tokens and share identifiers routinely reach 65–75+ characters and would fail under MySQL 8 strict mode at 64 chars.
- `media_type` (`VARCHAR(16)`, default `'post'`) records `'post'`, `'reel'`, or `'video'` so Instagram `/reel/{shortcode}` embeds preserve `/reel/` instead of hardcoding `/p/`.
- `utf8mb4_unicode_ci` preserves 4-byte UTF-8 emojis in `name` and `content`.

```sql
CREATE TABLE IF NOT EXISTS `mod_socialmedia_post` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lang` varchar(11) NOT NULL,
  `provider` enum('x','facebook','instagram') NOT NULL,
  `media_type` varchar(16) NOT NULL DEFAULT 'post',
  `post_id` varchar(128) NOT NULL,
  `url` varchar(500) NOT NULL,
  `embed_html` text NOT NULL,
  `name` varchar(255) NOT NULL,
  `handle` varchar(64) NOT NULL DEFAULT '',
  `content` text,
  `image` varchar(255) DEFAULT NULL,
  `status` enum('y','n') NOT NULL DEFAULT 'y',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_id` int(11) NOT NULL,
  `acl_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_provider_post` (`provider`, `post_id`, `lang`),
  KEY `idx_smp_lang` (`lang`),
  KEY `idx_smp_user` (`user_id`),
  KEY `idx_smp_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 3.2 Host Bridge & Registry Tables

#### A. Host-Module Registry — `installer/mod_socialmedia_post_mod/mod_socialmedia_post_mod.sql` (mirrors `mod_attachment_mod` / `mod_faq_mod`)
Registers which host modules are social-post-capable (`mod_key = 'id'`, `mod_value = 'name'`):

```sql
CREATE TABLE IF NOT EXISTS `mod_socialmedia_post_mod` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `mod_name` varchar(255) NOT NULL,
  `mod_key` varchar(255) NOT NULL,
  `mod_value` varchar(255) NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_smp_mod_name` (`mod_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### B. Per-Record Many-to-Many Link Table — `installer/mod_socialmedia_post/mod_socialmedia_post_link.sql`
Because `installerbase::create_tables()` iterates `$this->tables` and loads `$table . '.sql'` per entry, `mod_socialmedia_post_link` lives in its own `.sql` file (`installer/mod_socialmedia_post/mod_socialmedia_post_link.sql`), and `installer/mod_socialmedia_post/index.php` lists `['mod_socialmedia_post', 'mod_socialmedia_post_link']` in `$this->tables` so both install and `uninstall()` (`remove_tables()`) work cleanly:

```sql
CREATE TABLE IF NOT EXISTS `mod_socialmedia_post_link` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `socialmedia_post_id` int(11) NOT NULL,
  `mod_name` varchar(64) NOT NULL,
  `vid` int(11) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_smp_link` (`socialmedia_post_id`, `mod_name`, `vid`),
  KEY `idx_smp_host` (`mod_name`, `vid`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

`installer/mod_socialmedia_post_mod/index.php` seeds `mod_news`, `mod_page`, `mod_publication`, `mod_event`, and `mod_announcements` (guarded by `ModuleRuntime` / table existence, mirroring `add_file_modules()` in `installer/mod_attachment_mod/index.php`), and `BaseModuleController::ensureBridgeIntegrations()` self-heals registration when `$this->has_social_posts = true`.

### 3.3 Inline Positioning Token & HTML5 Paragraph Unwrapping
The host `content` field carries an ordered, human-safe placeholder token:

```
[socialpost:7]
```

- `7` = `mod_socialmedia_post.id` bound to this host record via `mod_socialmedia_post_link` (with `status = 'y'` for public rendering).
- Tokens are plain ASCII text and survive `Helper::clear_html()` untouched.
- **HTML5 Paragraph Unwrapping (Mandatory):** TinyMCE wraps standalone lines as `<p>[socialpost:7]</p>`. Naive replacement would emit `<p><figure>...</figure></p>`, which is invalid HTML5 (browsers auto-close `<p>` before `<figure>`, leaving empty `<p></p>` elements and orphaned `</p>` tags). `SocialPostProviderService::renderTokens()` MUST:
  1. Unwrap standalone paragraph tokens (`<p(?:\s[^>]*)?>\s*(?:&nbsp;|\xc2\xa0|\s)*\[socialpost:(\d+)\]\s*(?:&nbsp;|\xc2\xa0|\s)*</p>`) directly into `<figure class="social-post-embed" ...>...</figure>`.
  2. Split mid-paragraph tokens (`<p>...text [socialpost:7] more...</p>`) cleanly into `<p>...text</p><figure ...>...</figure><p>more...</p>`.
  3. Never emit `<p>` tags inside `<figcaption>` (use `<span>` / `<cite>` / `<a>` only) so downstream paragraph splitters (`news.twig:534-564`) never split inside a `<figure>`.
- **Canonical Rendering & Stripping APIs:**
  - `SocialPostProviderService::renderTokens(string $html, array $boundRows, bool $appendUnreferenced = true): string` — expands valid bound published tokens into `<figure class="social-post-embed" data-provider="...">` + `<figcaption>`. Unknown, unpublished (`status = 'n'`), or unbound IDs render nothing (silent skip, stripping any empty wrapper `<p></p>`). When `$appendUnreferenced` is `true`, bound published rows in `$boundRows` not referenced by any token in `$html` are appended in `<section class="social-post-fallback">`.
  - `SocialPostProviderService::stripTokens(string $html): string` — removes all `[socialpost:\d+]` tokens (and empty wrapper `<p></p>` blocks) for plain-text, metric, JSON-LD, meta description, RSS, text-export, and LLM feed consumers.

---

## 4. Service Layer — `lib/SocialPostProviderService.class.php`

Register `SocialPostProviderService` in `config/autoload.config.php` (alongside `VideoEmbedProviderService`). Shares SSRF-safe HTTP client, file cache (`cache/social_post/`), and strict iframe construction conventions with `VideoEmbedProviderService`:

| Method | Responsibility |
| :--- | :--- |
| `detectProvider(?string $input): ?array` | Returns `['provider' => ..., 'post_id' => ..., 'media_type' => 'post'\|'reel'\|'video', 'canonical_url' => ..., 'handle' => ...]`. Supports `x.com\|twitter.com/{handle}/status(es)?/{id}` (plus `/photo/{n}`, `/video/{n}` normalized to canonical status URL), `facebook.com/{page}/posts/{pfbid\|id}`, `facebook.com/permalink.php?story_fbid=...&id=...`, `facebook.com/{page}/videos/{id}`, `facebook.com/watch/?v={id}`, `fb.watch/{token}`, `facebook.com/share/{p\|v\|r}/{token}`, and `instagram.com/(p\|reel)/{shortcode}` (preserving `media_type = 'post'` vs `'reel'`). |
| `fetchMetadata(string $provider, string $postId, string $canonicalUrl, string $lang = 'en'): array` | **X:** base-36 syndication token pipeline (`cdn.syndication.twimg.com/tweet-result`) with `publish.twitter.com/oembed` fallback (valid here because `mod_socialmedia_post` accepts text/photo/video tweets alike). **Facebook:** `graph.facebook.com/v21.0/oembed_post` (or `oembed_video` when `media_type === 'video'`). **Instagram:** `graph.facebook.com/v21.0/instagram_oembed` (`api.instagram.com/oembed` is shut down). Whitelist `graph.facebook.com` in `ALLOWED_HOSTS`. Because Meta Graph API v21.0+ requires an App/Client token (`{app_id}\|{client_token}` from site/env config) and omits `author_name`/`thumbnail_url` in restricted responses, implement a public OpenGraph (`og:title`, `og:description`, `og:image`) fallback with 512 KB cap, strict SSRF host validation, tombstone detection, and 3600s cache TTL. Optionally cache remote thumbnails locally under `static/mod_socialmedia_post/`. |
| `buildEmbedHtml(string $provider, string $postId, array $opts = []): string` | Requires `$opts['url']` (canonical post URL) and `$opts['media_type']` (`'post'\|'reel'\|'video'`). Builds strict HTML5 iframes with **zero inline CSS/JS**: **X** `https://platform.twitter.com/embed/Tweet.html?id={postId}&theme={light\|dark}&dnt=true&hideThread=false&lang={lang}`; **Facebook** `https://www.facebook.com/plugins/{post\|video}.php?href={rawurlencode($url)}&show_text=true&width=500&locale={locale}`; **Instagram** `https://www.instagram.com/{p\|reel}/{postId}/embed/captioned/` (selected via `$opts['media_type'] === 'reel' ? 'reel' : 'p'`). All include `sandbox="allow-scripts allow-same-origin allow-popups allow-popups-to-escape-sandbox allow-presentation"`, `width="550"`, `height="{620\|658\|812}"`, `loading="lazy"`, `referrerpolicy="strict-origin-when-cross-origin"`, `data-provider`, and `data-status-id`. |
| `sanitizeOrRebuildEmbed(string $inputUrl, ?string $rawEmbed = null, array $opts = []): array` | Validates URL/iframe against `detectProvider()` and rebuilds canonical `embed_html` server-side (never trusting arbitrary user-supplied iframe attributes). |
| `renderTokens(string $html, array $boundRows, bool $appendUnreferenced = true): string` | Paragraph-safe `[socialpost:{id}]` → `<figure>` expansion + optional `<section class="social-post-fallback">` block. |
| `stripTokens(string $html): string` | Strips `[socialpost:\d+]` tokens and empty wrapper `<p></p>` tags for non-embed consumers. |

### 4.1 Sanitizer & `Helper::clear_html()` Alignment
- Update `URI.SafeIframeRegexp` in `lib/Helper.class.php:172` (HTTPS-only, src-prefix anchored) to add Facebook post/video plugins and Instagram `/p/` + `/reel/` embeds:
  ```
  %^https://(www\.youtube(-nocookie)?\.com/embed/|player\.vimeo\.com/video/|platform\.twitter\.com/embed/Tweet\.html|(www\.)?google\.com/maps/embed|(maps\.)?google\.com/maps|www\.openstreetmap\.org/export/embed\.html|www\.facebook\.com/plugins/(post|video)\.php|www\.instagram\.com/(p|reel)/[A-Za-z0-9_-]+/embed)%
  ```
- Keep `data-status-id` (already whitelisted on `<iframe>` in `lib/Helper.class.php:174`) as the canonical ID attribute.
- Because `<figure>` and `<figcaption>` are not in `Helper::clear_html()`'s `$element` allowlist (`lib/Helper.class.php:166`), `renderTokens()` **must only run at render time** on already-sanitized `content` — never pass `renderTokens()` output back into `Helper::clear_html()`.

---

## 5. Frontend & Feed Layer

### 5.1 `templates/amed/static/js/social-post.js`
- Click-to-activate poster facade (using cached `image` when available, or direct lazy iframe mount) → mounts provider `<iframe>`.
- **Security & Strict Code Separation hardening (do not copy `video-embed.js` verbatim):**
  - Preserve the `sandbox` attribute (`allow-scripts allow-same-origin allow-popups allow-popups-to-escape-sandbox allow-presentation`) when mounting iframes.
  - Validate `event.origin` and `event.source === iframe.contentWindow` on `postMessage` resize events (`https://platform.twitter.com`, `https://syndication.twitter.com`, `https://www.instagram.com`, `https://instagram.com`); clamp height (`220`–`2400` px) and write `iframe.setAttribute('height', String(px))` — **never** `iframe.style.height` (preserving zero `style="..."` attributes in DOM). Facebook falls back to its HTML `height` attribute + internal scroll.
- Theme sync (`document.documentElement.getAttribute('data-bs-theme')` light/dark) rewrites `theme=` on X iframes with a `MutationObserver`, mirroring `video-embed.js`.

### 5.2 `templates/amed/static/css/social-post.css`
- `.social-post-embed`: `max-width: 550px; margin-inline: auto;` editorial figure card, caption attribution row (`<span>` / `<cite>` / external post link, provider badge), `>= 44px` facade trigger button, `:focus-visible` outlines, `prefers-reduced-motion` support.
- `.social-post-fallback`: vertical rhythm for unreferenced bound posts rendered after prose.
- Zero inline styles (`tools/code_separation_guard.py` compliant).

### 5.3 Host Helpers, `news.twig` Paragraph Splitter & Multi-Surface Token Stripping
1. **Host detail helpers (`mod/index/helper/mod_news.helper.php`, `mod_page.helper.php`, `mod_publication.helper.php`, `mod_event.helper.php`, `mod_announcements.helper.php`):**
   - Query bound published `mod_socialmedia_post` rows (`status = 'y'`, matching ACL check `(acl_id = 0 OR ...)` via `indexBase` ACL helper) joined through `mod_socialmedia_post_link` (`WHERE mod_name = ... AND vid = ... ORDER BY sort_order ASC, id ASC`).
   - Compute `d['rendered_content'] = SocialPostProviderService::renderTokens($rawContent, $boundRows, true)`.
   - Overwrite `d['content'] = SocialPostProviderService::stripTokens($rawContent)` **before** computing `word_count`, `char_count`, `readingTime`, meta descriptions, or passing `d` to Twig (`$this->tema->ctx_set('d', ...)` on `mod_page` / `mod_announcements`). This guarantees `news.twig:164` (`"articleBody": {{ d.content|striptags|trim|json_ld|raw }}`), `news.twig:330` (`readingTime`), and `announcement.twig:37,53,86,105` never leak literal `[socialpost:7]` tokens or `<figcaption>` text!
2. **`templates/amed/index/mod_news/news.twig` (`lines 534-564`) Paragraph-Splitter Compatibility:**
   - Switch prose body rendering in `news.twig` (and `page.twig`, `publication.twig`, `event.twig`, `announcement.twig`) from `d.content|raw` to `(d.rendered_content is defined ? d.rendered_content : d.content)|raw`.
   - Because `renderTokens()` unwraps `<p>[socialpost:N]</p>` into top-level `<figure>...</figure>` and emits zero `</p>` tags inside `<figure>`, `news.twig`'s `|split('</p>')` paragraph counter counts only real `<p>...</p>` prose paragraphs and never splits inside a `<figure>`.
3. **Syndication, Export & AI Feed Surfaces (Token Stripping Mandatory):**
   - `mod/index/index.php`: strip tokens via `SocialPostProviderService::stripTokens()` in both `rss()` (`line 758`) and `rssSocial()` (`line 829+`, shipped in `v1.0.67`).
   - `mod/tools/index.php`: call `SocialPostProviderService::stripTokens()` in `saveText()` (`line 39`) and `saveHtml()` (`line 63`).
   - `lib/LlmsService.class.php`: call `SocialPostProviderService::stripTokens()` before generating `llms.txt` item summaries and `llms-full.txt` Markdown bodies (`htmlToCleanMarkdown()`, `lines 248, 259+`).

---

## 6. Admin Layer (Phone-Only Contract) & MCP Tools

### 6.1 `mod_socialmedia_post` & `mod_socialmedia_post_mod` Admin Modules
- `mod/mod_socialmedia_post/index.php` + `templates/admin/mod_socialmedia_post/{list,add,edit}.twig` + external `templates/admin/assets/js/mod_socialmedia_post.js` & CSS: phone-only (`<= 430px`, `>= 44px` touch targets), provider badge (X / Facebook / Instagram), `autofill_metadata` endpoint on URL paste, live sandboxed preview, `status` (`y`/`n`) toggle, ACL selector.
- **Emoji preservation:** Unlike `mod_video_embed` (legacy 3-byte `utf8`), `mod_socialmedia_post` uses `utf8mb4`; its validators (`add.validator.php`, `edit.validator.php`) MUST NOT strip or reject 4-byte UTF-8 emojis in `name` or `content` (strip NUL/control characters only).
- `mod/mod_socialmedia_post_mod/index.php`: host-module bridge registry CRUD mirroring `mod/mod_faq_mod` / `mod/mod_attachment_mod`.
- **Admin Navigation (Both Surfaces Required):**
  1. **Control Panel Launcher (`mod/mod_user/index.php`):** Register `mod_socialmedia_post` and `mod_socialmedia_post_mod` inside `_build_panel_categories()` under the `video_and_images` category (`lines 1803-1839`, sourcing labels from `Load::lang('mod_socialmedia_post')` and `Load::lang('mod_socialmedia_post_mod')`, matching `mod_faq` / `mod_faq_mod` at `lines 1782-1788`) and add required key assertions in `_assert_panel_language_keys()` (`lines 1947-2010`).
  2. **Drawer Sidebar (`templates/admin/partials/sidebar.twig`):** Add `mod_socialmedia_post` and `mod_socialmedia_post_mod` to `media_active` and navigation links alongside `mod_attachment_mod`.

### 6.2 Host Editor Integration (`mod_news` first, extensible to `mod_page`, `mod_publication`, `mod_event`, `mod_announcements`)
- **TinyMCE Phone-Viewport Toolbar Reality:** `window.initRichEditor(selector, height)` (`templates/admin/assets/js/script.js:4666-4704`) hardcodes `mobile: { menubar: false, toolbar: 'undo redo | bold italic | bullist numlist | link | code' }`, ignoring desktop `toolbar` on `<= 430px` viewports. Provide an external phone-friendly **"Add Social Post"** action button (`>= 44px` touch target) above the editor surface in `templates/admin/mod_news/edit.twig`, `add.twig`, and `partials/wizard_content.twig` (coordinating cleanly with any `v1.0.68` `mod_news/add` UI layout updates).
- **Bottom-sheet & Add-vs-Edit Link Persistence:**
  - Paste URL → `mod_socialmedia_post/autofill_metadata` preview → **Insert** creates (or reuses existing `(provider, post_id, lang)`) `mod_socialmedia_post` row and inserts `<p>[socialpost:{id}]</p>` at the TinyMCE cursor (`tinymce.activeEditor.insertContent(...)`).
  - **On `edit` (`vid > 0`):** immediately upserts the `mod_socialmedia_post_link` row (`mod_name`, `vid`, `socialmedia_post_id`).
  - **On `add` / wizard (`vid` not yet assigned):** appends `socialmedia_post_id` to hidden `social_post_ids[]` inputs; in host `afterSave($model)` (and `afterUpdate($model, $id)`), synchronize `mod_socialmedia_post_link` rows from both `social_post_ids[]` and any `[socialpost:(\d+)]` tokens present in `$model->content` so newly created articles never lose their link bindings!
- **List/Edit Relation Picker:** Extend `openMediaRelationPicker(contentId, type)` (`templates/admin/assets/js/mod_news.js:1471`, `list.twig`, `edit.twig`) to support `type === 'social_posts'` backed by `mod_socialmedia_post_link` AJAX endpoints — never adding a `socialpost_id` column to host tables.

### 6.3 MCP Tools (`lib/McpSocialPostTools.class.php`)
- **CRITICAL Autoload Rule:** Register `McpSocialPostTools` **ONLY** in `lib/McpServer.class.php` (`require_once`, `TOOLSET_GROUPS['media']`, `DOMAIN_CLASSES['socialmedia_post']`). **NEVER** add `McpSocialPostTools` (or any `Mcp*Tools` subclass of `McpCrudToolsBase`) to `config/autoload.config.php`, because `autoload.config.php` loads globally on every web request where `McpCrudToolsBase` is not loaded, which would cause an immediate site-wide `Fatal error: Class "McpCrudToolsBase" not found`.
- **Emoji Rule:** Do **NOT** call `McpCrudToolsBase::assertNoUnsupportedCharacters()` on `mod_socialmedia_post` `name` or `content` (`utf8mb4` table); validate control characters and field lengths directly.
- **Tools:** `create_socialmedia_post`, `update_socialmedia_post`, `delete_socialmedia_post`, `get_socialmedia_post`, `list_socialmedia_posts`, `link_socialmedia_post`, `unlink_socialmedia_post` — enforcing RBAC permissions, bridge registration in `mod_socialmedia_post_mod`, language existence, and `(provider, post_id, lang)` idempotent reuse/uniqueness.
- **Editorial-safe defaults:** When staged via MCP, `status` defaults to `'n'` unless explicitly set to `'y'`.

---

## 7. `mod_video_embed` Scope Correction

### 7.1 Validation Hardening (Write/Import Time Only)
- **Keep `VideoEmbedProviderService::detectProvider()` pure and synchronous:** `detectProvider()` runs on frontend renders via `enrichVideoRowForFrontend()` (`mod/index/indexBase.php`, `mod_video_embed.helper.php`). Requiring `/video/{n}` or network I/O inside `detectProvider()` would break existing X video rows whose stored URL lacks `/video/{n}` and trigger outbound HTTP during page loads.
- **Close all five non-video X bypasses at write/import time:**
  1. `VideoEmbedProviderService::fetchXMetadata()` (`lib/VideoEmbedProviderService.class.php:252-265, 364-403`): iterate **all** items in `$json['mediaDetails']` (accepting `'video'` or `'animated_gif'` on any item, not only `[0]`), AND in the `publish.twitter.com/oembed` fallback reject X URLs unless the input URL explicitly carries `/video/{n}` (since oEmbed HTML cannot prove video presence on bare status URLs).
  2. `lib/McpVideoTools.class.php`: in `createVideoEmbed()` (`line 1343`), rethrow/translate `autofill.no_video_in_post` into localized `err_no_video_use_social_post` instead of swallowing `Throwable`; in `updateVideoEmbed()` (`line 1517`), run the same `fetchMetadata()` video-entity check whenever `url` changes to an X URL without `/video/{n}`.
  3. `mod/mod_video_embed/validator/add.validator.php` and `edit.validator.php` (`line 222`): enforce `VideoEmbedProviderService::assertVideoPostUrl()` / `fetchMetadata()` video-entity verification on X URLs before accepting manual form submissions.
  4. Admin JS: surface `err_no_video_use_social_post` in **both** `templates/admin/assets/js/mod_video_embed_edit.js` **and** the wizard handler in `templates/admin/assets/js/script.js` (`wizard_video_embed.twig`, conditionally loaded via `templates/admin/layout.twig:81`).
- **Facebook video support in `VideoEmbedProviderService`:** Add the `facebook` video branch across `ALLOWED_HOSTS` (`www.facebook.com`, `facebook.com`, `fb.watch`, `graph.facebook.com`), `detectProvider()` (accepting only Facebook video URLs: `facebook.com/plugins/video.php`, `facebook.com/watch/?v={id}`, `facebook.com/{page}/videos/{id}`, `fb.watch/{token}` — rejecting non-video `posts/` permalinks with `err_no_video_use_social_post`), `fetchMetadata()`, `buildEmbedHtml()`, `sanitizeOrRebuildEmbed()`, `enrichVideoRowForFrontend()`, and `templates/amed/static/js/video-embed.js`.

### 7.2 Existing-Deployment Bootstrap & Misplaced-Record Migration Tasks
Because deployed `v1.0.67` sites do not re-run `/installer/`:
1. **`tasks/bootstrap_socialmedia_post.php` (Idempotent CLI Bootstrap, mirroring `tasks/mcp_bootstrap.php` & `tasks/seed_event_roles_perms.php`):**
   - Creates `mod_socialmedia_post_mod`, `mod_socialmedia_post`, and `mod_socialmedia_post_link` tables if missing.
   - Registers `mod_socialmedia_post_mod` and `mod_socialmedia_post` in `mods`, installs RBAC roles (`add, edit, delete, list, moderate_edit, moderate_delete`) in `mod_user_role`, grants permissions to `group_id = 1` in `mod_user_perm`, and seeds `mod_socialmedia_post_mod` rows for `mod_news`, `mod_page`, `mod_publication`, `mod_event`, and `mod_announcements`.
2. **`tasks/migrate_video_embed_social_posts.php` (Zero-Row-Safe Idempotent Migration):**
   - Supports `--dry-run`. Requires `mod_socialmedia_post` and `mod_socialmedia_post_link` tables to exist.
   - Scans `mod_video_embed` rows matching X/Twitter URLs whose syndication metadata confirms **no** video entity (note: local `bulletin` DB currently has 0 such rows — only YouTube `id = 3` — so the script must exit `0` cleanly as a verified no-op when none exist, while migrating any legacy rows such as historical ID 2 on deployed databases).
   - Inserts/reuses the target `mod_socialmedia_post` row (`provider = 'x'`, preserving `lang`, `name`, `status`, `user_id`, `acl_id`).
   - Scans only the active CMS host tables that store serialized `vid:{id}` arrays (`mod_news.video`, `mod_publication.video`, `mod_event.video` — explicitly skipping unrelated `mod_popup.video` enum, `mod_video.video` filename, and legacy `newses`/`publications`/`events` tables): safely unserializes (`unserialize($val, ['allowed_classes' => false])`), removes `'vid:{old}'`, re-serializes (or sets `''`/`NULL` matching column defaults), inserts the `mod_socialmedia_post_link` row, and appends `<p>[socialpost:{new}]</p>` to host `content` if not already present.
   - Deletes the migrated row from `mod_video_embed`.

---

## 8. Registry, Catalog, Bridge Lifecycle, Roles & i18n Parity

- **`lib/ModuleRegistry.class.php`:** Register `mod_socialmedia_post` in `$installDependencies` with `'modules' => ['mod_socialmedia_post_mod', 'mod_user']` and `'tables' => ['mod_socialmedia_post_mod', 'mod_user', 'mod_socialmedia_post_link']` (`mod_socialmedia_post_mod` requires `['modules' => ['mod_user'], 'tables' => ['mod_user']]`), and add `'mod_socialmedia_post_mod' => ['mod_socialmedia_post']` to `$uninstallDependents`.
- **`installer/app/InstallerModuleCatalog.php`:** Register `mod_socialmedia_post_mod` and `mod_socialmedia_post` in `$modules` and `$installOrder`, placing `mod_socialmedia_post_mod` **before** `mod_socialmedia_post`.
- **`lib/BaseModuleController.class.php`:**
  - Add `protected $has_social_posts = false;` (set `true` in `mod_news`, `mod_page`, `mod_publication`, `mod_event`, `mod_announcements`).
  - In `ensureBridgeIntegrations()` (`line 773`), call `$this->ensureBridgeModule('mod_socialmedia_post_mod', 'Social Posts', 'id', 'name')` when `$this->has_social_posts` is true.
  - In `delete()` (`line 1546`), call `$this->removeRelatedSocialPosts($model->id)` → `Run::import('mod_socialmedia_post', '_remove', [$this->module_name, $record_id])`.
  - Add `'mod_socialmedia_post'` to `echoSuccessResponse()` (`line 1696`) so AJAX status actions (`activate`/`deactivate`) return `'Success'` matching `mod_video_embed`.
- **`lib/McpCrudToolsBase.class.php` (`line 561`):** Add `'mod_socialmedia_post_mod' => 'mod_socialmedia_post'` to `removeBridgeRelations()` so MCP host deletions clean up `mod_socialmedia_post_link` rows automatically.
- **`lib/BridgeCleanupService.class.php`:**
  - `cleanRecord($modName, $recordId)`: delete from `mod_socialmedia_post_link` where `mod_name = ... AND vid = ...`.
  - `cleanModule($modName)`: delete from `mod_socialmedia_post_link` where `mod_name = ...` **and** delete from `mod_socialmedia_post_mod` where `mod_name = ...`.
  - `mod_socialmedia_post::afterDelete($id)` & `McpSocialPostTools::deleteSocialmediaPost()`: delete all `mod_socialmedia_post_link` rows where `socialmedia_post_id = $id` and remove any cached local thumbnail under `static/mod_socialmedia_post/`.
- **RBAC Roles (`installerbase` & `bootstrap_socialmedia_post.php`):** `['add', 'edit', 'delete', 'list', 'moderate_edit', 'moderate_delete']` on both `mod_socialmedia_post` and `mod_socialmedia_post_mod` (exact 6-role parity with `mod_video_embed`, `mod_faq`, `mod_faq_mod`, and `mod_attachment_mod`; `BaseModuleController::activate()`/`deactivate()` use `'edit'`, not `'edit_status'`).
- **Exact Locale & Dictionary Parity (`i18n-guard`):**
  - Active site/installer locales (`installer/lang/*.php`): **11** (`ar, ckb, de, en, fa, fr, ku, ru, sv, tr, zza`) — add module labels for `mod_socialmedia_post` and `mod_socialmedia_post_mod` across all 11 files, and create all 11 locale files in `mod/mod_socialmedia_post/lang/` and `mod/mod_socialmedia_post_mod/lang/`.
  - `mod/mod_video_embed/lang/`: currently has 11 files because legacy `es.php` is present while `fa.php` is missing; add `mod/mod_video_embed/lang/fa.php` and add `err_no_video_use_social_post` across all **12** files (`11` active locales + `es.php`) so zero locale files drift.
  - Shared admin navigation dictionaries: `mod/management/lang/` currently has **9** files (missing `fa.php` and `ru.php`) and `mod/mod_user/lang/` has **10** files (missing `fa.php`). Add missing `fa.php` / `ru.php` files (or synchronize any new `management` / `mod_user` keys across all 11 active locales) so `sidebar.twig` and `mod_user/panel` never emit undefined-key warnings under any active language.

---

## 9. Acceptance Criteria & Verification

1. **X inline & paragraph-split safety:** Paste an X status URL in `mod_news` (`add`, `edit`, and wizard) → creates `mod_socialmedia_post` + `mod_socialmedia_post_link`, inserts `[socialpost:N]`; published news unwraps `<p>[socialpost:N]</p>` into a valid top-level `<figure class="social-post-embed">`, preserves `news.twig` inline media paragraph splits, auto-resizes via HTML `height` attribute (origin-checked, zero `style=` attributes), syncs dark/light theme, and leaks **zero** `[socialpost:N]` tokens or `<figcaption>` text into JSON-LD `articleBody`, `readingTime`, meta/OG tags, `rss.xml`, `rss/social.xml`, `tools/saveText`, or `llms-full.txt`.
2. **Facebook (`pfbid`) & Instagram (`/p/` and `/reel/`) inline:** Paste a Facebook `pfbid0...` post URL (`> 64` chars) and an Instagram Reel URL (`/reel/{shortcode}`); both persist cleanly in `VARCHAR(128)` `post_id` with correct `media_type`, and render sandboxed iframes (`/plugins/post.php?href=...` and `/reel/{shortcode}/embed/captioned/`).
3. **`mod_video_embed` non-video rejection across all 5 paths:** Pasting a text/photo-only X status URL (including when syndication falls back to oEmbed) into `mod_video_embed` admin (`add`/`edit`/`wizard_video_embed`) or MCP (`create_video_embed`/`update_video_embed`) fails with localized `err_no_video_use_social_post`; mixed-media tweets containing a video at `mediaDetails[1]` and explicit `/video/1` URLs succeed; Facebook video URLs work while Facebook text/photo post URLs are rejected; existing rows (including local YouTube row `id = 3`) render unchanged.
4. **Bootstrap & Migration verification:** `php tasks/bootstrap_socialmedia_post.php` provisions tables, roles, permissions, and bridge seeds idempotently on `v1.0.67`; `php tasks/migrate_video_embed_social_posts.php` exits `0` on zero-match DBs and, when tested against a seeded non-video X `mod_video_embed` fixture bound to `mod_news.video = serialize(['vid:{fixture}'])`, migrates the record to `mod_socialmedia_post`, cleans `mod_news.video`, creates `mod_socialmedia_post_link`, appends `[socialpost:{new}]`, and deletes the fixture from `mod_video_embed`.
5. **Sanitizer & Emoji round-trip:** `[socialpost:N]` tokens, 4-byte UTF-8 emojis in `mod_socialmedia_post.content`/`name` (admin + MCP), and Facebook/Instagram `/p/` + `/reel/` iframes survive validation and `Helper::clear_html()` with `data-status-id`, while inline `style=`/`<script>` are stripped.
6. **Web & CLI Autoload health:** Both frontend/admin HTTP requests (without `McpServer` loaded) and MCP tool calls succeed with zero `McpCrudToolsBase` autoload errors.
7. **Phone-only admin & Guards:** All new admin views pass 430px viewport audit (`admin-mobile-check`), `python3 tools/code_separation_guard.py`, `python3 tools/module_boundary_guard.py`, `php -l` on all touched PHP files, and 11-locale `i18n-guard` key parity before updating `docs/VERSION.md` and `config/site.config.php` for `v1.0.68`.

---

## 10. Out of Scope (explicitly deferred)

- Threads / Bluesky / TikTok providers (provider enum extension is non-breaking; add on demand).
- Automatic conversion of plain social URLs pasted directly into prose without using the "Add Social Post" action or MCP link tools (explicit editor/agent action only).
- Outbound syndication of Bulletin articles to social accounts (already shipped via the `v1.0.65` Social Outbox and `v1.0.67` Dedicated Social RSS Bridge `/rss/social.xml`).
