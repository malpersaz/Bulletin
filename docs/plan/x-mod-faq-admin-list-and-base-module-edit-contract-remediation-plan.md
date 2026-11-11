# Plan X: `mod_faq` Admin Global List Page, `BaseModuleController::_edit` Contract Fatals & Missing Admin Template Remediation

**Target Environment:** `http://localhost:8080` (local Docker development stack: `bulletin_web` nginx 1.31.3 / `bulletin_app` PHP / `bulletin_db` MySQL 8.0, database `bulletin`)  
**Repository:** `AF/Bulletin` (working tree at commit `509e8b29`; production `https://amedbulten.com` runs `v1.0.62` per `docs/VERSION.md`)  
**Reported Entry Point:** `http://localhost:8080/tr/mod_faq/list` (user-reported admin crash)  
**Date:** 2026-11-11  
**Origin:** User-reported admin panel defect at `/tr/mod_faq/list`, reproduced and root-caused with an authenticated `Admin Webmaster` session on the local stack. Investigation expanded across the whole FAQ admin surface and the admin navigation that surrounds it.  
**Architecture Standard:** Amed Framework Core (Strict Code Separation, phone-only admin contract per `templates/admin/AGENTS.md` + `.agents/design/admin/DESIGN.md`, 100% English code and docs, real i18n keys with no fallback copy, `module-modernization-check` baseline)  
**Status:** Planned (not implemented). May ship inside `v1.0.63` or as a standalone hotfix release; version parity via `config/site.config.php`, `docs/SERVER.md`, `docs/VERSION.md` at release time (`github-release` skill).

---

## 0. Skills & Governance Statement (per `.gemini/GEMINI.md`)

All registered skills were reviewed at task start. Relevance decision:

| Skill | Verdict | Reason |
| :-- | :-- | :-- |
| `module-modernization-check` | **Relevant (governing standard)** | Defect F2 is exactly a `BaseModuleController` modernization-contract violation; defect F1/F3 are Admin Templates Aspect failures (`list.twig`/`add.twig`/`edit.twig` presence checks). Its checklist and `tools/audit_modernization.py` define the acceptance baseline for the remediation. Per its operating rule, no unsolicited repo-wide audit was run; only the touched modules were evaluated. |
| `admin-mobile-check` | **Relevant (implementation phase)** | The new `mod_faq/list.twig` must pass the phone-only admin layout contract; validation of that page happens against phone-sized viewports. |
| `task-validation` | **Relevant (implementation phase)** | Post-implementation guard inventory (`i18n_guard.py --path`, comment/emoji checks) for all touched files. |
| `i18n-guard` | **Relevant (implementation phase)** | New `lang.management.*` keys (9 locales) and `lang.mod_faq.*` keys (8 locales, `sv.php` gap) must pass `tools/i18n_guard.py --path`. |
| `kevanger`, `kevtemen`, `kevkolin`, `kevrast`, `kevseo` | Not relevant | Editorial pipeline skills; this plan touches admin UI/controllers only. Cross-references to their MCP-layer findings are in §7. |
| `frontend-theme`, `taste-skill`, `lang-refactor`, `github-release` | Not relevant now | `github-release` applies only at release finalization. |

---

## 1. Executive Summary

The reported URL `/tr/mod_faq/list` is a **real, routed admin surface** — `mod_faq_mod::list()` has a dedicated "global list" branch (`mod/mod_faq/index.php:143-170`) that queries all FAQ rows across all host modules and then renders `mod_faq/list`. That template **has never existed**: `templates/admin/mod_faq/` contains only `modal_list.twig` (the per-record scoped editor). The page dies as a raw Twig `LoaderError` with a full stack trace and absolute server paths.

Investigating that crash exposed a wider, previously undocumented breakage class in the admin panel:

1. **Four modules are compile-time dead** (`mod_faq_mod`, `mod_attachment_mod`, `mod_video`, `mod_page`): their `protected function _edit($id = null)` overrides are signature-incompatible with `BaseModuleController::_edit($id = null, $existingRecord = null)`, so the PHP class cannot load at all — every action (including `list`) fatals. **The fatal fires before any authentication check**, leaking `/var/www/html/...` paths to unauthenticated visitors with HTTP 200.
2. **Three of those four are sidebar-linked** (`Sayfa Yönetimi`, `Videolar`, `Dosya Modülleri`), and **`mod_headline` (also sidebar-linked) has no admin templates at all** — five sidebar entries currently lead to crash pages.
3. The FAQ management surfaces have **no navigation entry** (no sidebar item for `mod_faq` or `mod_faq_mod`), the existing `mod_faq.js` **cannot drive a cross-record global list**, and `modal_list.twig` carries an **inline `<script>`** block (code-separation violation) plus asset-version drift.
4. Framework-level error semantics are inconsistent: unauthenticated admin GETs return **0-byte HTTP 200** (silent blank), the Twig failure returns **500 with a raw trace**, and the PHP fatals return **200 with a raw fatal message**.

Local data shape (why the global list matters): `mod_faq` currently holds 12 rows spread over 5 `mod_news` records (`vid` 5/10/24/81/82); `mod_faq_mod` registers 4 host modules (`mod_news`, `mod_page`, `mod_publication`, `mod_event`, each `mod_key='id'`, `mod_value='name'`). The MCP editorial pipeline (`batch_create_faqs`, `get_news.faq_count` consumed by `tools/audit_content_seo.py`) stages FAQs without any human-reviewable global admin view — this page is that view.

### Priority & Severity Matrix

| ID | Layer | Component / Defect | Severity | Impact Summary |
| :-: | :-- | :-- | :---: | :-- |
| **F1** | `mod_faq` admin | `templates/admin/mod_faq/list.twig` missing → global FAQ management page is a raw Twig 500 | **Critical** | The reported URL is unusable; no global FAQ review surface exists anywhere in the admin |
| **F2** | 4 module controllers | `protected _edit($id)` signature incompatible with `BaseModuleController::_edit($id, $existingRecord)` → class-load fatal on every action | **Critical** | `mod_page`, `mod_video`, `mod_attachment_mod`, `mod_faq_mod` fully dead; 3 sidebar links crash; unauthenticated path disclosure (HTTP 200) |
| **F3** | `mod_headline` admin | `templates/admin/mod_headline/{list,add,edit}.twig` all missing (sidebar-linked `Manşet Yönetimi`) | High | Sidebar-linked headline management renders a raw Twig 500 |
| **F4** | Framework error surfacing | Unhandled exceptions render raw stack traces / fatal messages with absolute paths | Medium | Information disclosure (`/var/www/html/...`), no admin error shell, wrong status codes |
| **F5** | Auth semantics | Unauthenticated admin GET → 0-byte HTTP 200 (no redirect to login) | Medium | Silent blank pages; inconsistent with the AJAX 401+redirect contract from v1.0.60.4 |
| **F6** | Admin navigation | No sidebar entries for `mod_faq` (global list) or `mod_faq_mod` (host registrations) | Medium | FAQ surfaces reachable only by hand-typed URLs |
| **F7** | `mod_faq.js` | `FaqManager` binds a single `.faq-modal-shell[data-mod-name][data-vid]` — cannot drive a global cross-record list | High | Blocks F1; scoped-modal JS cannot be reused as-is |
| **F8** | `modal_list.twig` | Inline `<script>window.FAQ_LANG=...` (code-separation violation) + hard-coded `?v=1.2`/`?v=1.3` asset versions; JS `data-faq-lang` dataset parser is dead code | Low | Violates Strict Code Separation (AGENTS Rule 1); stale-cache risk |
| **F9** | Schema / query hygiene | DB `mod_faq.question varchar(500)` vs 255 enforced by admin validator and MCP (`FAQ_QUESTION_MAX=255`); global branch query unpaginated/unbounded | Low | Limit drift permits rows that no writer can legally produce; unbounded list growth |

---

## 2. Live Evidence (2026-11-11, local stack)

All admin-route evidence was captured with an authenticated `Admin Webmaster` session; unauthenticated rows were captured with plain `curl` (no session).

| Route | Auth | Result |
| :-- | :-- | :-- |
| `GET /tr/mod_faq/list` | admin | **HTTP 500**, 936-byte body: `<h1>Error</h1><p>Unable to find template "mod_faq/list.twig" (looked into: /var/www/html/templates/admin, /var/www/html/templates/amed, /var/www/html/templates/amed, /var/www/html/templates)</p>` + full Twig stack trace (`TwigTemplate.class.php(162)` → `Amed.class.php(787)` → `Amed::__destruct`) |
| `GET /tr/mod_faq/list` | none | **HTTP 200, 0-byte body** (silent blank; no `Location`, no login redirect) |
| `GET /tr/mod_faq_mod/list` | admin & none | **HTTP 200**, raw `<b>Fatal error</b>: Declaration of mod_faq_mod_mod::_edit($id = null) must be compatible with BaseModuleController::_edit($id = null, $existingRecord = null) in /var/www/html/mod/mod_faq_mod/index.php on line 88` — identical unauthenticated (fatal precedes auth) |
| `GET /tr/mod_page/list` | none | HTTP 200, 235-byte raw fatal (same signature error, `mod_page_mod`) |
| `GET /tr/mod_video/list` | none | HTTP 200, 237-byte raw fatal (same signature error, `mod_video_mod`) |
| `GET /tr/mod_attachment_mod/list` | admin | HTTP 200, 250-byte raw fatal (same signature error, `mod_attachment_mod_mod`) |
| `GET /tr/mod_headline/list`, `/add` | admin | HTTP 500 raw Twig `Unable to find template "mod_headline/list.twig"` / `"mod_headline/add.twig"` |
| `GET /tr/mod_faq/list/mod_news/81` | admin | **HTTP 200, healthy** — scoped modal editor renders inside the full admin shell (128 KB) |
| `GET /tr/mod_attachment/list`, `/tr/mod_gallery_mod/list`, `/tr/mod_news_category/list` | admin | HTTP 200, healthy (control group) |

Template-sweep corroboration (all `->tpl()` calls in `mod/*/` checked against `templates/admin/` + theme): the only admin-surface gaps are `mod_faq/list` and the entire `mod_headline/{list,add,edit}` set; public-theme `index/*` and `mod_user/*` targets resolve inside `templates/amed`.

Database corroboration (`bulletin_db`):

```text
mod_faq:      12 rows — mod_news vid 81(5), 5(2), 10(2), 24(2), 82(1); all host records exist (no orphans)
mod_faq_mod:  4 rows — mod_news, mod_page, mod_publication, mod_event (mod_key='id', mod_value='name')
mod_faq cols: question varchar(500) NOT NULL   ← drift vs enforced 255 (F9)
```

---

## 3. Findings & Remediation Blueprints

### F1 (Critical) — Global FAQ Management Page Renders a Missing Template

#### Root Cause
`mod/mod_faq/index.php:143-170` (global branch of `list()`): permission gate → `Run::import('management','_index')` → title → `faqs = all rows (id,lang,mod_name,vid,question,answer,sort_order,status,user_id,created_at,updated_at) ordered by id DESC` → `$this->theme->tpl('mod_faq/list')`. The template was never authored: the repo's single squashed history (`509e8b29`) already contains only `modal_list.twig`. The scoped branch (`list($mod,$vid)`, lines 172-203) is complete and healthy — only the global surface is missing.

#### Remediation Blueprint
1. **[CREATE] `templates/admin/mod_faq/list.twig`** — phone-first global list following the `mod_attachment/list.twig` + `.agents/design/admin/list-workspace.md` pattern:
   - Layout flags: `show_search = true` (client-side question/module search), `show_fab = false`, `hide_contextual_bar = true`; `{% extends 'admin/layout.twig' %}`.
   - Context banner: total FAQ count, distinct host-record count, active lang badge.
   - **Grouped card sections by `(mod_name, vid)`**: each group header shows module badge, host-record title (resolved server-side, see #3), `vid`, per-record count, and an "Open editor" action → `{{ site.saddress }}mod_faq/list/{mod_name}/{vid}` (the proven scoped modal route).
   - Row cards inside groups: question preview, status pill, per-row actions — status toggle (POST `mod_faq/ajax_toggle_status`), delete with confirm (POST `mod_faq/ajax_delete`); both endpoints are **id-scoped with their own owner/moderation permission checks** (`index.php:609-659, 697-756`) and are reused as-is.
   - Localized empty state and search-empty state (real `lang.mod_faq.*` keys, no inline fallback copy).
2. **[CREATE] `templates/admin/assets/js/mod_faq_list.js`** — follows the `mod_faq_mod_list.js` data-attribute config pattern (URLs + messages via `data-*`, zero inline handlers). Does **not** touch `mod_faq.js` (scoped modal stays intact). Handles: search filtering over rendered groups/rows, status toggle, confirm-then-delete, snackbar feedback.
3. **[MODIFY] `mod/mod_faq/index.php` global branch**:
   - Resolve host-record titles server-side: load `mod_faq_mod` registrations once; for each distinct `(mod_name, vid)` fetch the host record's `mod_value` column (mirroring `_check_mod()` lines 99-122), falling back to `'{module} #{vid}'` when the registration or host row is gone (defensive orphan flag).
   - Keep `faqs` ordered by `sort_order ASC, id ASC` per group (matches scoped editor and `ajax_list`), groups ordered by most recent FAQ `id DESC`.
   - Add a bounded ceiling on the initial render (e.g. latest 200 rows + visible "filter to refine" guidance); leave server-side pagination as a documented follow-up if volumes grow (25-question per-entity cap bounds natural growth).
   - Pass `is_global_list = true`, `faq_groups`, `total_count` to the template.
4. **[MODIFY] `templates/admin/assets/css/mod_faq.css`** — global-list section/card/toolbar styles (bump `?v=` per F8 convention).

### F2 (Critical) — `_edit` Signature Fatal Kills Four Modules (Includes Unauthenticated Path Disclosure)

#### Root Cause
`lib/BaseModuleController.class.php:1435` declares `protected function _edit($id = null, $existingRecord = null)` and the public `edit()` action calls `$this->_edit($id, $d)` (line 425) — `$existingRecord` feeds the pre-update menu-URL sync (lines 1456-1460). Four subclasses override `_edit` as `protected` **without** the second parameter, which is a compile-time LSP violation: the class file cannot load, so *every* action of the module (including `list`) fatals before permission checks:

| File | Line | Override | Sidebar-linked? |
| :-- | :-- | :-- | :-- |
| `mod/mod_faq_mod/index.php` | 88 | `protected function _edit($id = null)` | No (see F6) |
| `mod/mod_attachment_mod/index.php` | 88 | `protected function _edit($id = null)` | Yes — `Dosya Modülleri` |
| `mod/mod_video/index.php` | 661 | `protected function _edit($id = NULL)` | Yes — `Videolar` |
| `mod/mod_page/index.php` | 843 | `protected function _edit($id = NULL)` | Yes — `Sayfa Yönetimi` |

All other `_edit` overriders extend the legacy `indexMain` base (unaffected). Note the interplay with the modernization standard (`module-modernization-check`, Mod Aspect): the migration to `BaseModuleController` was recorded as done for these modules, but the contract change was never propagated — a "Partially Modern" state that is fatally broken, not merely degraded.

#### Remediation Blueprint
1. **[MODIFY] the four controllers** — align signatures: `protected function _edit($id = null, $existingRecord = null)`. Child bodies keep their own logic; where the base's menu-URL sync matters (`mod_page` publishes menu-bound pages), prefer forwarding `$existingRecord` to `parent::_edit($id, $existingRecord)` if the child delegates, otherwise accept-and-ignore is safe (the base default is already `null`).
2. **[VERIFY]** `php -l` all four files; authenticated route matrix over `mod_faq_mod/{list,add,edit}`, `mod_attachment_mod/list`, `mod_video/list`, `mod_page/list` (see §6). Unauthenticated re-probe must no longer emit `Fatal error` (it will fall through to the F5 gate behavior until F5 lands).
3. **[PRODUCTION NOTE]** If `amedbulten.com` runs the same controller code, its sidebar `Sayfa Yönetimi` / `Videolar` / `Dosya Modülleri` entries crash identically for everyone, including unauthenticated visitors (path disclosure). MCP page/video management keeps working because MCP tools bypass module controllers. Verify on production after deploy (§6).

### F3 (High) — `mod_headline` Admin Templates Missing Entirely

#### Root Cause
`mod_headline` is a legacy `indexMain` controller whose `add()`/`edit()`/`list()` (lines 32-102) set `lang_lang` (all site languages), `d` (record), and render `mod_headline/{add,edit,list}.twig`. No such templates exist anywhere under `templates/` — dropped during the admin modernization squash while the sidebar link (`partials/sidebar.twig:131-136`, `full_headline_management`, gated by `session.permissions.mod_headline.list`) survived.

#### Remediation Blueprint
1. **[CREATE] `templates/admin/mod_headline/{list,add,edit}.twig`** + **[CREATE] `templates/admin/assets/js/mod_headline.js`** and `assets/css/mod_headline.css` — phone-first forms/list reusing the standard admin shell, wizard partials (`image_uploader.twig`), and localized `lang.mod_headline.*` keys. The controller's writes (`image_remove_image`, `_add`, `_edit`) stay untouched — templates must cover exactly the variables it sets (`lang_lang`, `d`, `site`) and its image-field contract.
2. **[VERIFY]** authenticated `mod_headline/{list,add,edit}` render the admin shell; add→edit→image-remove round-trip on a probe record; delete the probe.
3. **[MODERNIZATION NOTE]** `mod_headline` remains "Kevn (Legacy)" under `module-modernization-check`; migrating it to `BaseModuleController` is **out of scope** here (tracked as a candidate for the next modernization pass, not this remediation).

### F4 (Medium) — Raw Error Surfacing (Stack Traces, Fatal Messages, Wrong Status Codes)

#### Root Cause
Two independent gaps:
- Admin template rendering happens in `Amed::__destruct` (`lib/Amed.class.php:787` → `TwigTemplate::output()` line 162). A `Twig\Error\LoaderError` escapes uncaught: Twig prints its default `<h1>Error</h1>` + trace with loader paths; only this path happens to yield 500.
- PHP compile fatals print the raw engine message (path + line) with **HTTP 200** because output has already started / no handler converts them to a proper response.

#### Remediation Blueprint
1. **[MODIFY] `lib/Amed.class.php`** — wrap the `AdminTemplate`/`TwigTemplate` `output()` calls in try/catch; on `\Throwable` in admin context emit the existing `renderAdminRouteError()` shell (already used for `method`/`private_method` route errors) with HTTP 500; never print exception traces when `DEBUG` is off.
2. **[VERIFY]** with `DEBUG=false`, forcing a missing template yields the admin error shell + 500 and **no** `/var/www/html` substring in the body; with `DEBUG=true` the trace stays available to developers.
3. Keep production `display_errors` off (ops-level; document in `docs/SERVER.md` ops checklist line).

### F5 (Medium) — Unauthenticated Admin GET Returns 0-Byte HTTP 200

#### Root Cause
When `Role::hasPermission('mod_faq','list')` fails for a guest (`$_SESSION['permissions']` empty, `lib/Role.class.php:16-25`), `mod_faq_mod::list()` just `return false` — no template is registered, so `Amed` emits nothing: 0 bytes, HTTP 200, no `Location`. The AJAX path already has a proper contract (401 JSON + `?login=modal&session_expired=1` redirect target, `BaseModuleController.class.php:337-348`, v1.0.60.4); the plain-GET path was never given one.

#### Remediation Blueprint
1. **[MODIFY] `lib/BaseModuleController.class.php`** (or the shared permission helper used by list actions): on non-AJAX permission failure —
   - unauthenticated (`checklogin() === false`) → `302` to `{saddress}?login=modal&session_expired=1` (parity with the AJAX contract);
   - authenticated but unauthorized → existing `management/unauthorized.twig` shell with HTTP 403.
2. **[VERIFY]** unauthenticated `curl -sI` on `/tr/mod_faq/list` returns 302 (never 0-byte 200); authenticated-but-unauthorized user gets 403 shell.

### F6 (Medium) — FAQ Surfaces Absent From Admin Navigation

#### Root Cause
`templates/admin/partials/sidebar.twig` links `mod_gallery_mod` and `mod_attachment_mod` (Media group) but has no entries for `mod_faq` (global FAQ management) or `mod_faq_mod` (FAQ host-module registration). `lang/management` has no FAQ nav keys (only `gallery_modules`, `file_modules`).

#### Remediation Blueprint
1. **[MODIFY] `templates/admin/partials/sidebar.twig`** — Content group: `mod_faq/list` entry gated by `session.permissions.mod_faq.list` (icon `bi-patch-question-fill`, label `lang.management.faq_management`); Media group (mirroring gallery/attachment-module rows): `mod_faq_mod/list` entry gated by `session.permissions.mod_faq_mod.list`, label `lang.management.faq_modules`. Add both to the group `*_active` expressions.
2. **[MODIFY] `mod/management/lang/*.php` (9 locales: ar, ckb, de, en, fr, ku, sv, tr, zza)** — add `faq_management` + `faq_modules` keys.
3. **[VERIFY]** sidebar renders both entries for admin; hidden for a group without the permission; `i18n_guard.py --path mod/management` clean.

### F7 (High) — Scoped-Modal JS Cannot Drive the Global List

#### Root Cause
`templates/admin/assets/js/mod_faq.js` `FaqManager` constructor (lines 19-50) requires exactly one `.faq-modal-shell` carrying one `data-mod-name`/`data-vid` context; save/reorder AJAX calls need that single context (`ajax_save`, `ajax_reorder` validate `mod_name`+`vid` via `_check_mod`). A global list spans many records — incompatible by design.

#### Remediation Blueprint
Covered by F1 #2: a dedicated `mod_faq_list.js` with id-scoped actions only (`ajax_toggle_status`, `ajax_delete`, navigation to the scoped editor). `mod_faq.js` remains untouched for the modal surface. No shared-code refactor — the two surfaces have different action grammars; forcing them together would couple a stable surface to a new one.

### F8 (Low) — `modal_list.twig` Code-Separation & Versioning Drift

#### Root Cause
- `modal_list.twig:127-161` inlines `<script>window.FAQ_LANG = {...}</script>` while `mod_faq.js:30-36` already implements a `data-faq-lang` JSON dataset parser that nothing feeds — a Strict Code Separation violation (AGENTS Rule 1) with dead counterpart code.
- Asset versions are hard-coded (`mod_faq.css?v=1.2`, `mod_faq.js?v=1.3`) while sibling templates use `?v={{ site.version }}` (`mod_attachment.css?v={{ site.version }}`); `templates/admin/AGENTS.md` requires checking layout asset-version parameters whenever admin assets change.

#### Remediation Blueprint
1. **[MODIFY] `templates/admin/mod_faq/modal_list.twig`** — delete the inline script block; pass the same dictionary via `data-faq-lang="{{ {...}|json_encode|e('html_attr') }}"` on `.faq-modal-shell` (activates the existing JS parser; no JS change needed).
2. **[MODIFY] asset version params** on `mod_faq.css` / `mod_faq.js` references (bust caches once, then keep the module's convention consistent — prefer `?v={{ site.version }}` to match the sibling pattern).

### F9 (Low) — Limit Drift & Unbounded Global Query

#### Root Cause
- Schema: `mod_faq.question varchar(500)` (DB + installer SQL) while the admin validator (`lang.mod_faq.validation.question_max` = 255), the modal `maxlength=255`, and MCP (`McpFaqTools::FAQ_QUESTION_MAX = 255`, schema `maxLength: 255`) all enforce 255 — the DB accepts rows no legal writer can produce.
- The global branch selects all rows with no cap (acceptable today at 12 rows; unbounded by contract).

#### Remediation Blueprint
1. **[CREATE] `tasks/tighten_mod_faq_question_column.php`** — idempotent task: verify `MAX(CHAR_LENGTH(question)) <= 255`, then `ALTER TABLE mod_faq MODIFY question varchar(255) NOT NULL`; align `mod/mod_faq` + `installer/mod_faq` SQL files.
2. **[MODIFY] global branch cap** per F1 #3 (bounded initial render).

---

## 4. Implementation Order (Dependency-Sorted Work Packages)

| WP | Findings | Files | Gate |
| :-- | :-- | :-- | :-- |
| **WP1** | F2 | 4 controllers | `php -l`; route matrix green; unauth probe clean of `Fatal error` |
| **WP2** | F1 + F7 | `mod_faq/list.twig`, `mod_faq_list.js`, `mod_faq.css`, `mod/mod_faq/index.php` | `/tr/mod_faq/list` renders grouped list; toggle/delete/open-editor work; phone-viewport pass |
| **WP3** | F6 | `sidebar.twig`, `mod/management/lang/*.php` (9) | Sidebar entries render; `i18n_guard` clean |
| **WP4** | F8 | `modal_list.twig` | Zero inline scripts; modal still works; `data-faq-lang` path exercised |
| **WP5** | F3 | `mod_headline/*.twig`, `mod_headline.js/css` | add→edit→list round-trip on a probe record |
| **WP6** | F4 + F5 | `lib/Amed.class.php`, `lib/BaseModuleController.class.php` | 500-shell on forced template error; unauth 302; unauthorized 403 |
| **WP7** | F9 | `tasks/tighten_mod_faq_question_column.php`, SQL parity | Task idempotent re-run `[SKIP]`; local rows unaffected (max length ≤ 255 today) |

WP1 first: it unblocks `mod_faq_mod/list`, which WP2's verification and the F6 sidebar entry depend on. WP6 last among code work: its try/catch wraps surfaces stabilized by WP1-WP5.

---

## 5. Repository Compliance Checklist (per AGENTS rules + `templates/admin/AGENTS.md`)

1. **Strict Code Separation**: new templates reference only external `templates/admin/assets/{css,js}`; zero inline `style`/`script`/event handlers; F8 removes the existing inline block.
2. **100% English code/comments/docs**; no Turkish/Kurdish comments in touched files.
3. **Localization**: every visible string via real keys; **no `default(...)` fallback copy**; new keys added to all locale files of the owning module (`mod_faq`: 8 locales + assess `sv.php` gap; `management`: 9 locales); `python3 tools/i18n_guard.py --path` on every touched lang directory.
4. **Phone-only admin surface**: new/changed pages validated at phone viewport (e.g. 390×844) per `admin-mobile-check` contract — touch targets, one-hand reach, primary task above the fold.
5. **No compatibility shims**: no legacy aliases left alongside new routes; single canonical global-list implementation.
6. **Workspace isolation**: everything inside `AF/Bulletin`; `biys/` untouched.
7. **Version parity** at release: `config/site.config.php`, `config/site.config.example.php`, `docs/SERVER.md`, `docs/VERSION.md` synchronized; plan file cleaned up post-implementation per the completed-plan convention (as done in v1.0.60.4).

---

## 6. Verification Plan

**Local, authenticated (`Admin Webmaster` on `http://localhost:8080`):**
- Route matrix — expect 200 + admin shell, body contains neither `Fatal error` nor `<h1>Error</h1>`:
  `/tr/mod_faq/list`, `/tr/mod_faq_mod/list`, `/tr/mod_faq_mod/add`, `/tr/mod_faq_mod/edit/1`, `/tr/mod_page/list`, `/tr/mod_video/list`, `/tr/mod_attachment_mod/list`, `/tr/mod_headline/list`, `/tr/mod_headline/add`, `/tr/mod_headline/edit/<probe>`, `/tr/mod_faq/list/mod_news/81` (regression: scoped modal unchanged).
- Functional: on `/tr/mod_faq/list` — group headers show host titles (`mod_news` #81 etc.); status toggle persists (re-query `mod_faq.status`); delete + confirm removes row; "Open editor" navigates to the scoped modal; search filters groups; empty-state key visible with a filtered-out query.
- Phone viewport (390×844): list scanning, touch targets ≥ 44px, toolbar/FAB not overlapping rows.
- `php -l` on all four WP1 controllers + any touched lib file.
- `python3 tools/i18n_guard.py --path mod/mod_faq` and `--path mod/management` → clean.
- `python3 tools/audit_modernization.py` → the four WP1 modules report loadable (no regression in the modernization report).

**Local, unauthenticated (`curl`):**
- `/tr/mod_faq/list` → 302 to `/?login=modal&session_expired=1` (post-F5; pre-F5: not 0-byte-200).
- `/tr/mod_faq_mod/list`, `/tr/mod_page/list`, `/tr/mod_video/list` → no `Fatal error` substring (post-WP1), correct gate behavior.
- No response body anywhere contains `/var/www/html`.

**Production (post-deploy, session-authenticated spot check):**
- `mod_page/list`, `mod_video/list`, `mod_attachment_mod/list`, `mod_headline/list`, `mod_faq/list` return the admin shell; unauthenticated `curl -s` shows no path strings.
- MCP smoke (`tasks/mcp_smoke.php`) unaffected — MCP tools bypass module controllers; FAQ tool contracts unchanged.

---

## 7. Adjacent Documented Defects (Cross-Reference Only — Not Duplicated Here)

- `docs/plan/v1.0.63-kevanger-editorial-pipeline-remediation-and-enhancement-plan.md` §B1: sequential `where_*()` clobbering in `McpFaqTools::applyFaqFilters` (MCP `list_faqs` returns wrong sets) — same FAQ domain, MCP layer; fix independently of this plan.
- Same plan §B2: `McpCrudToolsBase::getRecordAuditMeta()` `mysqli_result::fetch()` fatal → `get_news.faq_count` always 0 — degrades the editorial value of the FAQ surface this plan builds; ship both.
- Cosmetic, non-blocking: `lib/TwigTemplate.class.php:22-27` registers `templates/amed` twice (registry `app_template` + hardcoded fallback) — visible in the loader-path error text; harmless duplication.

## 8. Out of Scope

- `mod_headline` migration to `BaseModuleController` (modernization pass; F3 only restores its templates).
- MCP-layer FAQ fixes (v1.0.63 §B1/§B2).
- Public-theme FAQ rendering (`templates/amed`) — working today.
- Production data repairs (none needed: no orphan FAQ rows, max question length within 255 locally).
