# Architectural Specification & Plan: `mod_socialmedia_post` Inline Social Post Embed Bridge & `mod_video_embed` Scope Correction (v1.0.69)

**Target Environment:** Local Docker stack (`bulletin_app` PHP 8.5.12, `bulletin_web` nginx 1.31.3, `bulletin_db` MySQL 8.0, database `bulletin`) & Host Linux (`PHP 8.5.4`)
**Repository:** `AF/Bulletin` (`/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin`)
**Date:** 2026-11-12
**Target Release:** `v1.0.69` (current repo version: `v1.0.66`; assumes queued `v1.0.67` and `v1.0.68` land first)
**Governing Standard:** Amed Framework Core & Task Quality Guards (Phone-Only Admin Contract, Strict Code Separation, Real i18n Keys across all 11 locales, Wikipedia-style UTF-8 URL Parity, English code/docs)
**Status:** Planned / Ready for Implementation

---

## 0. Skills & Governance Statement (per `.gemini/GEMINI.md` & `AGENTS.md`)

| Skill / Quality Guard | Relevance | Governance Requirement |
| :--- | :--- | :--- |
| `module-modernization-check` | **Governing Standard** | New module must follow `BaseModuleController` / `installerbase` patterns, `ModuleRegistry` & `InstallerModuleCatalog` registration, `BridgeCleanupService` cleanup, and modern AF conventions. |
| `admin-mobile-check` | **Governing Standard** | All admin screens must strictly adhere to the phone-only mobile contract (max 430px viewport, >= 44px touch targets, bottom-sheet patterns, no horizontal scrollbars). |
| `i18n-guard` | **Governing Standard** | All user-facing strings must have descriptive English keys in `en.php` with 100% parity across all 11 active locales (`ar, ckb, de, en, fa, fr, ku, ru, sv, tr, zza`). |
| `Strict Code Separation` | **Governing Standard** | Zero inline CSS/JS. Provider facades, resize observers and theme sync live in external JS (`static/js/`); styling in external CSS. |
| `task-validation` | **Post-Implementation Gate** | Validate acceptance criteria end-to-end before release tagging. |

---

## 1. Problem Definition

### 1.1 Misplaced Responsibility in `mod_video_embed`
`mod_video_embed` was designed for **video** embeds (YouTube, Vimeo, X **video** posts) rendered in the dedicated `#mod_video` section of detail pages (`templates/amed/index/mod_news/news.twig:447`). During the X-provider rollout, its URL detector (`VideoEmbedProviderService::detectProvider`) started accepting **any** X status URL — including text/photo-only posts. This:

- Pollutes the semantic "Video" section with non-video content (the `lang.index.mod_video_embed.title` heading says "Videos").
- Breaks consumer expectations: a tweet renders at 550px inside a 16:9-oriented video grid.
- **Live evidence:** production `mod_video_embed` record **ID 2** (Sundar Pichai text-only Gemini 4 Hydrogen announcement, status `n`) is stored in the wrong module today.

### 1.2 No First-Class Home for Inline Social Posts
Editors need X / Facebook / Instagram posts rendered **inline at an exact position in article prose** — like `map_embed_code` for maps, but per-record and reusable. Raw HTML in `content` is rejected as primitive: unmanaged, unsearchable, unattributable.

### 1.3 Strategic Decision
Introduce **`mod_socialmedia_post`**: a social post embed module using a **reusable bridge link table (`mod_socialmedia_post_link`) + host-module registry (`mod_socialmedia_post_mod`)** and the **same technology as `mod_video_embed`** (`VideoEmbedProviderService`-style provider detection, cached metadata fetch, strict HTML5 sandboxed iframe construction). Simultaneously **restrict `mod_video_embed` write/import paths to video URLs only** (e.g. `https://x.com/Google/status/2120941984148041901/video/1` or bare X status URLs whose syndication metadata contains a video entity, while rejecting text/photo-only X posts).

---

## 2. System Architecture

```
 Admin (mod_news add/edit)                Host detail render (news.twig, page.twig, ...)
 ┌────────────────────────────┐           ┌─────────────────────────────────────────┐
 │ Action bar: "Add Social    │           │ Host helper computes metrics BEFORE     │
 │ Post" → bottom-sheet:      │           │ token expansion; then calls             │
 │ paste URL →                │           │ SocialPostProviderService::renderTokens │
 │ SocialPostProviderService  │           │ ([socialpost:7] → sandboxed provider    │
 │ detect → fetch meta        │           │  iframe <figure> at exact paragraph;    │
 │ → create mod_socialmedia_  │           │  unreferenced bound rows appended in    │
 │   post + link row          │           │  fallback section)                      │
 │ → insert [socialpost:7]    │           │ static/js/social-post.js: theme sync +   │
 │   at TinyMCE cursor        │           │ origin-checked postMessage auto-height  │
 └────────────────────────────┘           │ static/css/social-post.css              │
                                          └─────────────────────────────────────────┘
```

### 2.1 Module Boundaries (after this plan)

| Concern | Module | Accepts |
| :--- | :--- | :--- |
| Video content (dedicated `#mod_video` section) | `mod_video_embed` | YouTube / YouTube Shorts / Vimeo URLs; X **video** URLs (`/status/{id}/video/{n}` or status URLs verified at write/import time to contain a video entity); Facebook **video** URLs (`facebook.com/plugins/video.php` or `facebook.com/watch/?v={id}`). Bare non-video X/FB/IG post URLs are **rejected** at write/import time with an explanatory validation error. |
| Social posts (inline in prose, any media type) | `mod_socialmedia_post` | X status URLs, Facebook post/video URLs, Instagram post/reel URLs. If the post contains video it is still valid here (renders the **post card**, not a bare player). |

---

## 3. Database Schema

### 3.1 `installer/mod_socialmedia_post/mod_socialmedia_post.sql`
```sql
CREATE TABLE `mod_socialmedia_post` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `lang` VARCHAR(11) NOT NULL,
  `provider` ENUM('x','facebook','instagram') NOT NULL,
  `post_id` VARCHAR(64) NOT NULL,          -- provider-native id (status id / FB post token / IG shortcode)
  `url` VARCHAR(500) NOT NULL,             -- canonical post URL (https://x.com/Google/status/2120941984148041901)
  `embed_html` TEXT NOT NULL,              -- provider iframe built by SocialPostProviderService::buildEmbedHtml()
  `name` VARCHAR(255) NOT NULL,            -- author / account label ("Google (@Google)")
  `handle` VARCHAR(64) NOT NULL DEFAULT '',
  `content` TEXT,                          -- cached post text snapshot (searchable, utf8mb4 for emojis)
  `image` VARCHAR(255) DEFAULT NULL,       -- cached provider thumbnail (fallback poster)
  `status` ENUM('y','n') NOT NULL DEFAULT 'y',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_id` INT(11) NOT NULL,
  `acl_id` INT(11) NOT NULL DEFAULT 0,
  UNIQUE KEY `uq_provider_post` (`provider`, `post_id`, `lang`),
  KEY `idx_lang` (`lang`),
  KEY `idx_user` (`user_id`),
  KEY `idx_smp_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 3.2 Host Bridge & Registry Tables

#### A. Host-Module Registry — `installer/mod_socialmedia_post_mod/mod_socialmedia_post_mod.sql` (mirrors `mod_attachment_mod` / `mod_gallery_mod`)
Registers which host modules are social-post-capable (1 row per host module, where `mod_key = 'id'` and `mod_value = 'name'` — the host display column, matching `mod_attachment_mod`):

```sql
CREATE TABLE `mod_socialmedia_post_mod` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,            -- human label, seeded per host module
  `mod_name` VARCHAR(255) NOT NULL,        -- host module: mod_news, mod_page, mod_publication, mod_event, mod_announcements
  `mod_key` VARCHAR(255) NOT NULL,         -- host PK column name: 'id'
  `mod_value` VARCHAR(255) NOT NULL,       -- host display column name: 'name'
  `user_id` INT(11) NOT NULL,
  UNIQUE KEY `uq_smp_mod_name` (`mod_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### B. Per-Record Many-to-Many Link Table — `installer/mod_socialmedia_post/mod_socialmedia_post_link.sql`
Because `installerbase::create_tables()` iterates `$this->tables` and loads `$table . '.sql'` per entry, `mod_socialmedia_post_link` must live in its own `.sql` file (`installer/mod_socialmedia_post/mod_socialmedia_post_link.sql`), and `installer/mod_socialmedia_post/index.php` must list both `'mod_socialmedia_post'` and `'mod_socialmedia_post_link'` in `$this->tables` so both install and `uninstall()` (`remove_tables()`) work cleanly.

Because `mod_socialmedia_post` rows are reusable across records (`uq_provider_post`) and host tables (`mod_news`, `mod_page`, `mod_publication`, `mod_event`, `mod_announcements`) do not carry a `socialpost_id` column, per-record bindings live in `mod_socialmedia_post_link`:

```sql
CREATE TABLE `mod_socialmedia_post_link` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `socialmedia_post_id` INT NOT NULL,
  `mod_name` VARCHAR(64) NOT NULL,         -- host module: mod_news, mod_page, mod_publication, mod_event, mod_announcements
  `vid` INT(11) NOT NULL,                  -- host record id
  `sort_order` INT(11) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_smp_link` (`socialmedia_post_id`, `mod_name`, `vid`),
  KEY `idx_smp_host` (`mod_name`, `vid`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

`mod_news` is seeded first in `installer/mod_socialmedia_post_mod/index.php` (via a module seed helper mirroring `add_file_modules()` in `installer/mod_attachment_mod/index.php`); `mod_page`, `mod_publication`, `mod_event`, and `mod_announcements` follow the same pattern.

### 3.3 Inline Positioning Token
The host `content` field carries an ordered, human-safe placeholder token:

```
[socialpost:7]
```

- `7` = `mod_socialmedia_post.id` bound to this host record via `mod_socialmedia_post_link` (and having `status = 'y'` for public rendering).
- Tokens are plain text → survive `Helper::clear_html()` sanitization untouched (no matching HTML tag to strip).
- **Canonical Rendering API:** `SocialPostProviderService::renderTokens(string $html, array $boundRows, bool $appendUnreferenced = true): string` replaces each `[socialpost:{id}]` token with the record's `embed_html` wrapped in a `<figure class="social-post-embed" data-provider="...">` shell + `<figcaption>`. Unknown, unpublished (`status = 'n'`), or unbound IDs render nothing (silent skip, logged) — never a visible broken token. If `$appendUnreferenced` is `true`, any bound published rows in `$boundRows` not referenced by a token in `$html` are appended in a `<section class="social-post-fallback">` block after the prose.

---

## 4. Service Layer — `lib/SocialPostProviderService.class.php`

Register `SocialPostProviderService` in `config/autoload.config.php` (alongside `VideoEmbedProviderService`). Shares HTTP client, cache, and sanitizer conventions with `VideoEmbedProviderService`, with a distinct provider matrix:

| Method | Responsibility |
| :--- | :--- |
| `detectProvider(?string $input): ?array` | Canonical detection: `x.com\|twitter.com/{handle}/status(es)?/{id}` (optionally `/photo/{n}`, `/video/{n}` — normalized to bare status), `facebook.com/{page}/posts/{id}` & `fb.watch/{id}` & `facebook.com/share/{token}`, `instagram.com/{p,reel}/{shortcode}`. |
| `fetchMetadata(provider, id, lang)` | **X:** existing base-36 syndication token pipeline (`cdn.syndication.twimg.com/tweet-result`). **Facebook:** `graph.facebook.com/v21.0/oembed_post` (or `oembed_video`). **Instagram:** `graph.facebook.com/v21.0/instagram_oembed` (`api.instagram.com/oembed` is deprecated/shut down). Add `graph.facebook.com` to `ALLOWED_HOSTS`. Because Meta Graph API v21.0+ deprecated `author_name`, `author_url`, and `thumbnail_url` from oEmbed payloads (and tokenless calls are capped at 1,000 req/hr), support an optional Meta App/Client token from config and implement an OpenGraph/public metadata fallback to populate `name`, `handle`, and `image`. TTL 3600s cache, tombstone handling, 512 KB response cap. |
| `buildEmbedHtml(provider, id, opts)` | Strict HTML5 iframes, zero inline CSS/JS: **X** `platform.twitter.com/embed/Tweet.html?id=…&theme={site theme}&dnt=true&hideThread=false&lang=…` (post card shows thread context, unlike the video variant); **Facebook** `www.facebook.com/plugins/post.php?href=…&show_text=true&locale=…`; **Instagram** `www.instagram.com/p/{shortcode}/embed/captioned/`. All: `sandbox="allow-scripts allow-same-origin allow-popups allow-popups-to-escape-sandbox allow-presentation"`, `width="550"`, `loading="lazy"`, `referrerpolicy="strict-origin-when-cross-origin"`, `data-provider`, `data-status-id`, sensible initial heights (X 620, FB 658, IG 812) awaiting JS resize. |
| `renderTokens(string $html, array $boundRows, bool $appendUnreferenced = true): string` | Token → `<figure>` replacement (and optional fallback block for unreferenced bound rows), used by host front helpers and admin preview. |

### 4.1 Sanitizer & `Helper::clear_html()` Alignment
- `URI.SafeIframeRegexp` in `lib/Helper.class.php` gains the social plugin hosts (src-prefix anchored, HTTPS only):
  ```
  |^https://(www\.facebook\.com/plugins/(post|video)\.php|www\.instagram\.com/(p|reel)/[A-Za-z0-9_-]+/embed)|
  ```
  (`platform.twitter.com/embed/Tweet.html` is already whitelisted.)
- Use `data-status-id` (already whitelisted on `<iframe>` in `Helper::clear_html()`) rather than `data-post-id`, or explicitly whitelist `iframe.data-post-id` in `lib/Helper.class.php`.
- Because `<figure>` and `<figcaption>` are not in `Helper::clear_html()`'s allowed `$element` list, `renderTokens()` **must only run at render time** on already-sanitized `d.content` (never pass the `<figure>`/`<figcaption>` output back through `clear_html()`).

---

## 5. Frontend Layer

### 5.1 `templates/amed/static/js/social-post.js`
- Facade pattern similar to `video-embed.js`: click-to-activate poster facade (cached `image` field) → mounts iframe.
- **Security hardening (do not copy `video-embed.js` verbatim):**
  - Preserve the `sandbox` attribute (`allow-scripts allow-same-origin allow-popups allow-popups-to-escape-sandbox allow-presentation`) when mounting iframes.
  - Validate `event.origin` on `postMessage` auto-height resize events (accept only `https://platform.twitter.com`, `https://syndication.twitter.com`, `https://www.instagram.com`, `https://instagram.com` — never resize from arbitrary origins); Facebook falls back to fixed height + internal scroll.
- Theme sync (`data-bs-theme` light/dark) rewrites `theme=` param on X iframes; `MutationObserver` for runtime theme switch, mirroring `video-embed.js` behavior.

### 5.2 `templates/amed/static/css/social-post.css`
- `.social-post-embed`: `max-width: 550px; margin-inline: auto; border-radius: var(--radius, 16px);` figure shell, caption row (attribution from `name` + provider icon), focus-visible outlines, reduced-motion facade.
- `.social-post-fallback`: spacing/stacking for unreferenced bound posts rendered after prose.
- Zero inline styles (Strict Code Separation).

### 5.3 Host Helpers & Templates
- Host templates (`news.twig`, and later `page.twig`, `publication.twig`, `event.twig`, `announcement.twig`) load `social-post.js` and `social-post.css`.
- **Metric & Twig `readingTime` calculation order:** Host helpers (e.g. `mod_news.helper.php`, `mod_page.helper.php`, `mod_announcements.helper.php`) must compute `word_count`, `char_count`, JSON-LD `articleBody`, and `readingTime` **before** calling `SocialPostProviderService::renderTokens()` (or strip `[socialpost:N]` tokens for metrics). Furthermore, because `announcement.twig`, `page.twig`, and `publication.twig` compute `readingTime` in Twig from `d.content|striptags|length` (and `announcement.twig` falls back to `d.content|striptags` for meta/JSON-LD descriptions), either expose the token-expanded HTML as `d.rendered_content` (keeping `d.content` unexpanded for Twig metrics/meta) or precompute `readingTime` and meta descriptions in PHP before token expansion. (Note: `mod_page.helper.php` and `mod_announcements.helper.php` set `d` via `$this->tema->ctx_set('d', ...)`.)
- When using `d.rendered_content` (or precomputed metrics + `d.content`), `renderTokens(..., appendUnreferenced: true)` handles both inline tokens and the post-prose fallback block, keeping template changes minimal.

---

## 6. Admin Layer (Phone-Only Contract)

### 6.1 `mod_socialmedia_post` Admin Module
List / add / edit / delete screens following `mod_video_embed` admin structure (max 430px viewport, `>= 44px` touch targets): provider badge (X / Facebook / Instagram), auto-filled metadata from URL paste, live preview iframe, draft/publish `status` toggle (`y`/`n`), ACL binding. Add `mod_socialmedia_post` and `mod_socialmedia_post_mod` to `templates/admin/partials/sidebar.twig` (`media_active` and management navigation links alongside `mod_attachment_mod`) so admins can reach them from the phone menu.

### 6.2 Host Editor Integration (`templates/admin/mod_news/edit.twig`, `add.twig`, `list.twig` + `templates/admin/assets/js/mod_news.js`)
- **TinyMCE 7.5.1 Phone-Viewport Toolbar:** `mod_news` initializes TinyMCE via `window.initRichEditor(selector, height)` (`templates/admin/assets/js/script.js`), which hardcodes `mobile: { menubar: false, toolbar: 'undo redo | bold italic | bullist numlist | link | code' }` and ignores desktop `toolbar` on `<= 430px` viewports. Therefore, implement **"Add Social Post"** either by:
  1. Adding an external phone-friendly action button (`>= 44px` touch target) above `.mod-news-editor-wrap` (and in `wizard_content.twig` if enabled) that opens the bottom-sheet and inserts `[socialpost:{id}]` via `tinymce.activeEditor.insertContent(...)`, **or**
  2. Extending `window.initRichEditor()` to accept custom mobile toolbar buttons.
- **Bottom-sheet flow:** URL paste field → live detection badge → metadata preview → "Insert" creates/links the `mod_socialmedia_post` + `mod_socialmedia_post_link` rows and inserts `[socialpost:{id}]` at the editor cursor.
- **Non-token attachment management:** Follow the repo's actual `mod_news` relation-picker pattern (`openMediaRelationPicker(contentId, type)` in `mod_news.js` / `list.twig`, plus a social-post link picker in `edit.twig`) writing to `mod_socialmedia_post_link` — do not invent a non-existent `socialpost_id` column on host tables.

### 6.3 MCP Tools (`lib/McpSocialPostTools.class.php`)
- Register `McpSocialPostTools` in `lib/McpServer.class.php` (`require_once`, `TOOLSET_GROUPS['media']`, `DOMAIN_CLASSES`) and `config/autoload.config.php`.
- Tools: `create_socialmedia_post`, `update_socialmedia_post`, `delete_socialmedia_post`, `get_socialmedia_post`, `list_socialmedia_posts`, `link_socialmedia_post` / `unlink_socialmedia_post` — validation identical in spirit to `McpVideoTools` (role checks, language match, uniqueness `(provider, post_id, lang)`), plus link args (`mod_name`, `vid`, `sort_order`) writing to `mod_socialmedia_post_link`.
- Editorial-safe defaults: news staging stays draft-only; `status` on `mod_socialmedia_post` defaults to `'n'` when staged via MCP unless explicitly published.

---

## 7. `mod_video_embed` Scope Correction

### 7.1 Validation Hardening (Write/Import Time Only)
- **Keep `VideoEmbedProviderService::detectProvider()` pure and synchronous:** `detectProvider()` is called on every frontend render via `enrichVideoRowForFrontend()` (`mod/index/indexBase.php`, `mod_video_embed.helper.php`). Requiring `/video/{n}` or network I/O inside `detectProvider()` would break existing X video rows (whose `original` lacks `/video/{n}`) and cause outbound HTTP on page loads.
- **Enforce video-entity presence at write/import time:**
  - `lib/McpVideoTools.class.php`: in `createVideoEmbed()` (currently swallows `Throwable` from `fetchMetadata()`) and `updateVideoEmbed()` (currently skips `fetchMetadata()`), require `fetchMetadata()` to confirm a video entity for X URLs unless `/video/{n}` is present; reject non-video X posts with localized guidance error (`err_no_video_use_social_post`: *"This X post contains no video. Use mod_socialmedia_post for social posts."*).
  - `mod/mod_video_embed/validator/add.validator.php` and `edit.validator.php`: call `fetchMetadata()` video-entity verification (currently only call `sanitizeOrRebuildEmbed()`).
  - Admin JS: update both `templates/admin/assets/js/mod_video_embed_edit.js` **and** `templates/admin/partials/wizard_video_embed.twig` (globally included in `templates/admin/layout.twig`).
- **Facebook video support in `VideoEmbedProviderService`:** Currently `VideoEmbedProviderService` has no Facebook branch (`ALLOWED_HOSTS`, `detectProvider`, `fetchMetadata`, `buildEmbedHtml`, `sanitizeOrRebuildEmbed` all lack Facebook). Add the `facebook` video branch accepting only `facebook.com/plugins/video.php` or `facebook.com/watch/?v={id}` so Section 2.1 is satisfied.
- **i18n parity fix:** `mod/mod_video_embed/lang/` currently has 11 files (`ar, ckb, de, en, es, fr, ku, ru, sv, tr, zza`) with `fa.php` missing; add `mod/mod_video_embed/lang/fa.php` alongside `err_no_video_use_social_post` across all 11 active locales.

### 7.2 Migration of Misplaced Records
1. Scan `mod_video_embed` rows whose `original`/`embed` matches `platform.twitter.com/embed/Tweet.html` **without** a video entity (metadata re-fetch): production row **ID 2** (Pichai announcement) is the known case.
2. Migrate each into `mod_socialmedia_post` (provider `x`, same status id, name, lang, status, user).
3. Re-point host bindings across **all** host tables with a `video` column (`mod_news.video`, `mod_publication.video`, `mod_event.video`): each stores a **PHP-serialized array** (`serialize(['vid:2', ...])`). Unserialize safely (`unserialize($val, ['allowed_classes' => false])`), remove `'vid:{old}'`, re-serialize (or set `NULL` if empty), create the `mod_socialmedia_post_link` row, and append `[socialpost:{new}]` at the end of the host `content`.
4. Delete the source row from `mod_video_embed`; record the migration details in `docs/VERSION.md` release notes.
5. Because `installer/index.php` and `installer/installerbase.php` have no built-in upgrade-step runner, ship the migration as an explicit idempotent task script (e.g. `tasks/migrate_video_embed_social_posts.php`) guarded by `mod_socialmedia_post` table existence and row checks.

---

## 8. Registry, Catalog, Cleanup, Roles & i18n

- `lib/ModuleRegistry.class.php`: register `mod_socialmedia_post` in `$installDependencies` with **both** `'modules' => ['mod_socialmedia_post_mod', 'mod_user']` and `'tables' => ['mod_socialmedia_post_mod', 'mod_user']` (`mod_socialmedia_post_mod` has no dependencies, matching `mod_attachment_mod`/`mod_gallery_mod`), and add `'mod_socialmedia_post_mod' => ['mod_socialmedia_post']` to `uninstallDependents`. (Bridge-only modules do not require a `$featureMap` entry unless a public route is exposed.)
- `installer/app/InstallerModuleCatalog.php`: register `mod_socialmedia_post_mod` and `mod_socialmedia_post` in `$modules` and `$installOrder`, placing `mod_socialmedia_post_mod` **before** `mod_socialmedia_post` in `$installOrder`.
- `templates/admin/partials/sidebar.twig`: add `mod_socialmedia_post` and `mod_socialmedia_post_mod` to `media_active` and the navigation menu (with `lang.management.*` keys).
- `lib/BridgeCleanupService.class.php`: update `cleanRecord()` and `cleanModule()` to delete `mod_socialmedia_post_link` bindings when a host record or module is deleted.
- Roles (`installerbase` / seed): `add, edit, edit_status, delete, list, moderate_edit, moderate_delete`.
- i18n: `installer/lang/*.php` module-name entries + admin/error keys (`provider_x`, `provider_facebook`, `provider_instagram`, `err_no_video_use_social_post`, `add_social_post`, …) across all 11 active locales (`ar, ckb, de, en, fa, fr, ku, ru, sv, tr, zza`), plus `mod/mod_video_embed/lang/fa.php`.

---

## 9. Acceptance Criteria & Verification

1. **X inline:** paste `https://x.com/sundarpichai/status/2120940236805169182` in news editor → toolbar/action-bar flow creates `mod_socialmedia_post` + `mod_socialmedia_post_link`, inserts `[socialpost:N]`; published news renders the tweet card at that exact paragraph, auto-height (origin-checked), dark-mode correct, without leaking `<figcaption>` into `articleBody` or `readingTime`.
2. **Facebook & Instagram inline:** same flow with a FB post URL and IG reel URL; both render inline with sandboxed iframes (Meta oEmbed + OpenGraph fallback).
3. **mod_video_embed rejection:** pasting a bare (non-video) X status URL into `mod_video_embed` admin (add/edit/wizard) or MCP (`createVideoEmbed`/`updateVideoEmbed`) yields the localized guidance error; pasting `https://x.com/Google/status/2120941984148041901/video/1` succeeds, and existing X video rows continue rendering normally.
4. **Migration:** `tasks/migrate_video_embed_social_posts.php` migrates production `mod_video_embed` ID 2 to `mod_socialmedia_post`; `mod_news` (and any `mod_publication`/`mod_event`) serialized `video` field no longer references `vid:2`; `mod_socialmedia_post_link` row exists and `[socialpost:N]` renders after prose.
5. **Sanitizer round-trip:** `[socialpost:N]` tokens and plugin iframes (including IG `/reel/`) survive `Helper::clear_html()` with `data-status-id`; inline `style=`/`<script>` still stripped.
6. **Phone-only admin:** all new admin screens pass 430px viewport audit (`admin-mobile-check`).
7. **i18n parity:** key diff across 11 locales returns zero missing (including `mod/mod_video_embed/lang/fa.php`).
8. **task-validation** gate run before `v1.0.69` tag; `docs/VERSION.md` updated with the release entry.

---

## 10. Out of Scope (explicitly deferred)

- Threads / Bluesky / TikTok providers (provider enum extension is non-breaking; add on demand).
- Social post **auto-discovery** from article body URLs (no implicit conversion; explicit editor action only).
- Syndication of host articles to social platforms (covered by v1.0.67 RSS bridge plan).
