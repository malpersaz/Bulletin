# `mod_event` & `mod_event_category` Architecture, Modernization, and Integration Plan

- **Legacy Source Reference:** `AF/biys/mod/mod_etkinlik` and `AF/biys/mod/mod_etkinlik_kategori` (2015 `indexMain` architecture)
- **Target Repository:** `AF/Bulletin` (Amed Framework & Bulletin CMS)
- **Target Module Identifiers:** `mod_event` and `mod_event_category`
- **Public Theme Target:** `templates/amed/` (Canonical public theme; `templates/amida/` is deprecated and must never be used)
- **Homepage Widget Target:** `templates/amed/index/mod_event/event_agenda.twig` (*Right Sidebar `#homepageHeroSidebar` — Block R4, directly below `market_rates.twig` per `docs/plan/homepage-layout-wireframe.html`*)
- **Reference Implementations:** `mod_news`, `mod_news_category`, `mod_publication`, `mod_announcements`, `BaseModuleController`, `DataTableService`, `ImageService`, `McpCrudToolsBase`

---

## 1. Executive Summary & Architectural Mandates

This specification defines the end-to-end engineering plan to introduce **`mod_event`** and **`mod_event_category`** into `AF/Bulletin`. It replaces the legacy Turkish-named `mod_etkinlik` and `mod_etkinlik_kategori` modules with a 100% English, dual-database (MySQL + SQLite), mobile-first admin, and broadsheet-ready public module pair.

### Core Non-Negotiable Mandates
1. **100% English Codebase & Routing (`AGENTS.md` Rule 7 & `tools/i18n_guard.py`):**
   - Every table name, column name, PHP class/method/variable/property, Twig variable, JavaScript identifier, CSS class, translation key (`en.php` baseline), code comment, and URL route (`/events`, `/event/{seo}`, `/event-calendar`, `/event-calendar/json`, `/eventSitemap.xml`) must be written in standard English.
2. **Zero Forbidden Fallbacks & Zero SQL Aliases (`tools/i18n_guard.py`):**
   - Never use `|default(...)` in Twig or `??` in PHP on `lang.*` / `$this->lang[...]` expressions (`RE_FORBIDDEN_DEFAULT`).
   - Never write single-line `SELECT ... AS ...`, `FROM ... AS ...`, or `JOIN ... AS ...` in `.php` or `.sql` files (`RE_SQL_ALIAS`).
3. **Strict Code Separation (`AGENTS.md` Rule 2 & `tools/code_separation_guard.py`):**
   - Zero inline CSS (`style="..."` or `<style>`) and zero inline JavaScript (`onclick="..."`, `onmouseover="..."`, or inline `<script>`) in any `.php`, `.twig`, or `.html` file. Only `<script type="application/ld+json">` is permitted in templates.
4. **Phone-Only Admin Surface (`AGENTS.md` Rule 6 & `tools/audit_modernization.py`):**
   - All views under `templates/admin/mod_event/` and `templates/admin/mod_event_category/` target phone viewports exclusively using the Material 3 mobile list, bottom action toolbar (`#contentToolbar`), speed-dial FAB, and 9-step full-screen wizard shell (`#eventWizardShell`).
5. **Canonical Public Theme (`templates/amed/`):**
   - All public Twig templates and static assets reside under `templates/amed/` and are referenced dynamically via `{{ site.taddress }}{{ site.app_template }}/static/...`.
6. **Wikipedia-Style SEO Slugs (`AGENTS.md` Rule 10):**
   - Slugs (`seo` column) preserve original alphabet characters and casing while replacing spaces with underscores (`_`) via `Helper::legacy_seo()`. Both `seo` slug and numeric `id` lookup are supported on public routes.
7. **Complete 11-Language Parity:**
   - All module, frontend, admin, and installer language packs must cover all 11 supported languages: `ar`, `ckb`, `de`, `en`, `fa`, `fr`, `ku`, `ru`, `sv`, `tr`, `zza`.

---

## 2. Legacy Audit (`mod_etkinlik` $\rightarrow$ `mod_event`)

| Layer | Legacy File (`AF/biys`) | Legacy Defects & Technical Debt | Modern Target (`AF/Bulletin`) |
| :--- | :--- | :--- | :--- |
| **Event Table** | `mod/mod_etkinlik/mod_etkinlik.sql` | Turkish columns (`ad`, `konu`, `tarih`, `etarih`, `yayin` `'e'/'h'`, `katilimcilar`, `davetliler`, `icerik`, `yer`, `resim`); missing `seo`, `summary`, `deck`, `slider`, `gallery_id`, `file_id`, `video`, `view_count`, `created_at`, `updated_at`; no indexes. | `installer/mod_event/mod_event.sql` & `mod/mod_event/mod_event.sql` |
| **Category Table** | `mod/mod_etkinlik_kategori/mod_etkinlik_kategori.sql` | Turkish columns (`ad`, `alt`, `sira`, `aciklama`, `resim`), `'e'/'h'` enums, missing `show_on_homepage` and `acl_id`. | `installer/mod_event_category/mod_event_category.sql` & `mod/mod_event_category/mod_event_category.sql` |
| **SQL View** | *(None)* | N+1 queries required to fetch category metadata, author details, and gallery image counts. | `installer/mod_event/events.sql` & `mod/mod_event/events.sql` |
| **Admin Controller** | `mod/mod_etkinlik/index.php` (721 lines) | Extends legacy `indexMain`; Turkish action names (`ekle`, `duzenle`, `listele`, `sil`, `yayinla`, `yayinkaldir`); inline `onclick` HTML inside `datatable_json()`; legacy `mod_resim` / `mod_dosya` calls. | `mod/mod_event/index.php` (extends `BaseModuleController`) |
| **Category Controller** | `mod/mod_etkinlik_kategori/index.php` (496 lines) | Extends `indexMain`; lacks `##` per-language root container management; Turkish methods (`tasi`, `dragdrop_sil`). | `mod/mod_event_category/index.php` (modeled on `mod_news_category`) |
| **ORM Models** | `mod_etkinlik.model.php`, `datatable.model.php` | Turkish property mappings; obsolete Flexigrid `datatable.model.php`. | `mod_event.model.php`, `events.model.php`, `mod_event_category.model.php` (no `datatable.model.php`) |
| **Frontend Helper** | `mod/index/helper/mod_etkinlik.helper.php` | Uses `SELECT DAYOFMONTH(tarih) AS gun` (violates `FORBIDDEN_ALIAS`), `YEAR()`/`MONTH()` functions (breaks SQLite and disables B-Tree index usage), unescaped SQL string concatenation, and dead debug methods (`etkinlik2`). | `mod/index/helper/mod_event.helper.php` |
| **Admin Views** | `templates/admin/mod_etkinlik/*.twig` | Desktop Bootstrap 3 portlets, inline `<script>` and `<style>`, raw file inputs. | `templates/admin/mod_event/{list,add,edit}.twig` + external CSS/JS |
| **Public Views** | `templates/default/index/mod_etkinlik/*.twig` | Inline `onmouseover="showEtkinlikResim(...)"`, numeric language keys (`lang.index.mod_etkinlik.22`), jQuery OwlCarousel v1 / FullCalendar v2 dependencies. | `templates/amed/index/mod_event/{event_agenda,event,events,calendar}.twig` + external CSS/JS |

---

## 3. Database Schema & View Specification

### 3.1 `mod_event` Table Schema (`installer/mod_event/mod_event.sql` & `mod/mod_event/mod_event.sql`)

| Legacy Column | Modern Column | Type & Constraints | Purpose |
| :--- | :--- | :--- | :--- |
| `id` | `id` | `int(11) NOT NULL AUTO_INCREMENT` | Primary key |
| `lang` | `lang` | `varchar(11) NOT NULL` | Language code (`tr`, `ku`, `en`, `ckb`, `zza`, `ar`, `de`, `fa`, `fr`, `ru`, `sv`) |
| `kat_id` | `category_id` | `int(11) NOT NULL DEFAULT 0` | Foreign key to `mod_event_category.id` |
| `ad` | `name` | `varchar(255) NOT NULL` | Event title (1–70 characters in wizard) |
| `konu` | `subject` | `varchar(255) NOT NULL` | Event type / subject badge (e.g., *Theater, Conference, Concert, Cinema, Panel*) |
| `tarih` | `start_date` | `datetime NOT NULL` | Event start date and time (`YYYY-MM-DD HH:MM:SS`) |
| `etarih` | `end_date` | `datetime DEFAULT NULL` | Optional event end date and time (`YYYY-MM-DD HH:MM:SS`) |
| `yayin` (`e`/`h`) | `status` | `enum('y','n') NOT NULL DEFAULT 'y'` | Publication state (`y` = Published, `n` = Draft) |
| *(None)* | `slider` | `enum('y','n') NOT NULL DEFAULT 'n'` | Featured in homepage hero slider (`y`/`n`) |
| *(None)* | `deck` | `enum('y','n') NOT NULL DEFAULT 'n'` | Display summary lead/deck on detail page (`y`/`n`) |
| *(None)* | `summary` | `text DEFAULT NULL` | Event lead summary and meta description fallback (90–160 chars) |
| `icerik` | `content` | `text NOT NULL` | Rich HTML event body |
| `yer` | `location` | `text DEFAULT NULL` | Venue, hall, or address |
| `katilimcilar` | `participants` | `text DEFAULT NULL` | Speakers, artists, or participants |
| `davetliler` | `guests` | `text DEFAULT NULL` | Guests of honor / invitees |
| *(None)* | `seo` | `varchar(255) NOT NULL` | Wikipedia-style URL slug (`legacy_seo`) |
| *(None)* | `title` | `varchar(255) NOT NULL` | SEO meta `<title>` |
| *(None)* | `keywords` | `varchar(255) NOT NULL` | SEO meta keywords |
| *(None)* | `description` | `varchar(255) NOT NULL` | SEO meta description |
| `resim` | `image` | `varchar(255) DEFAULT NULL` | Poster image filename under `static/mod_event/` |
| *(None)* | `gallery_id` | `int(11) NOT NULL DEFAULT 0` | Associated `mod_gallery` ID |
| *(None)* | `file_id` | `int(11) NOT NULL DEFAULT 0` | Associated `mod_attachment` ID |
| *(None)* | `video` | `text DEFAULT NULL` | Serialized video tokens (`video:ID`, `vid:ID`) |
| `acl_id` | `acl_id` | `int(11) NOT NULL DEFAULT 0` | Access control group (`0` = Public) |
| *(None)* | `view_count` | `int(11) NOT NULL DEFAULT 0` | Public page view counter |
| *(None)* | `created_at` | `timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP` | Record creation timestamp |
| *(None)* | `updated_at` | `datetime DEFAULT NULL` | Last modification timestamp |
| `user_id` | `user_id` | `int(11) NOT NULL` | Creator user ID (`mod_user.id`) |

**Indexes (each defined on its own line for `SqliteSqlTransformer::extractInlineIndexes()` compatibility):**
```sql
  PRIMARY KEY (`id`),
  INDEX `idx_event_lang_seo` (`lang`, `seo`),
  INDEX `idx_event_lang_status` (`lang`, `status`),
  INDEX `idx_event_start_date` (`start_date`),
  INDEX `idx_event_category` (`category_id`),
  INDEX `idx_event_slider` (`slider`),
  INDEX `idx_event_user` (`user_id`)
```

### 3.2 `mod_event_category` Table Schema (`installer/mod_event_category/mod_event_category.sql` & `mod/mod_event_category/mod_event_category.sql`)

Matches `mod_news_category.sql` 1:1:
```sql
CREATE TABLE `mod_event_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lang` varchar(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `parent_id` int(11) NOT NULL DEFAULT 0,
  `order` int(11) NOT NULL DEFAULT 0,
  `root` enum('y','n') NOT NULL DEFAULT 'n',
  `seo` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `keywords` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `menu` int(11) NOT NULL DEFAULT 0,
  `show_on_homepage` enum('y','n') NOT NULL DEFAULT 'n',
  `acl_id` int(11) NOT NULL DEFAULT 0,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_eventcat_lang_seo` (`lang`, `seo`),
  INDEX `idx_eventcat_parent` (`parent_id`),
  INDEX `idx_eventcat_root` (`root`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 3.3 `events` SQL View (`installer/mod_event/events.sql` & `mod/mod_event/events.sql`)

Designed for full parity with `newses.sql` and `publications.sql`, while strictly avoiding table aliases and single-line `SELECT ... AS` patterns:

```sql
CREATE OR REPLACE VIEW events AS
SELECT
  mod_event.id,
  mod_event.lang,
  mod_event.category_id AS category_id,
  mod_event.name,
  mod_event.subject,
  mod_event.start_date,
  mod_event.end_date,
  mod_event.status,
  mod_event.slider,
  mod_event.deck,
  mod_event.summary,
  mod_event.content,
  mod_event.location,
  mod_event.participants,
  mod_event.guests,
  mod_event.seo,
  mod_event.title,
  mod_event.keywords,
  mod_event.description,
  mod_event.image,
  mod_event.gallery_id AS gallery_id,
  mod_event.file_id AS file_id,
  mod_event.video,
  mod_event.acl_id AS acl_id,
  mod_event.view_count,
  mod_event.created_at,
  mod_event.updated_at,
  mod_event.user_id AS user_id,
  mod_event_category.name AS category_name,
  mod_event_category.seo AS category_seo,
  mod_event_category.image AS category_image,
  mod_event_category.acl_id AS category_acl_id,
  mod_user.username,
  mod_user.name AS u_name,
  mod_user.last_name AS u_last_name,
  mod_user.email,
  mod_user.image AS user_image,
  mod_user_group.name AS group_name,
  (
    SELECT COUNT(mod_gallery.id)
    FROM mod_gallery
    WHERE mod_event.id = mod_gallery.vid
      AND mod_gallery.mod_name = 'mod_event'
  ) AS gallery_count
FROM mod_event
LEFT JOIN mod_user ON mod_event.user_id = mod_user.id
LEFT JOIN mod_user_group ON mod_user.group_id = mod_user_group.group_id
LEFT JOIN mod_event_category ON mod_event.category_id = mod_event_category.id;
```

> [!IMPORTANT]
> - `mod_user_group` uses column `name` (`mod_user_group.name AS group_name`), never `group_name`.
> - In `data/sqlite/bulletin.sql`, define the view using `/*!50001 CREATE VIEW \`events\` AS SELECT ... */` (without `OR REPLACE`, as raw SQLite does not support `CREATE OR REPLACE VIEW`). During live installer runs, `SqliteSqlTransformer::transformViews()` automatically rewrites `CREATE OR REPLACE VIEW` into `DROP VIEW IF EXISTS` + `CREATE VIEW`.

---

## 4. URL Routing Contract (`config/url.config.php`)

All routes use pure English nouns and consistent singular/plural conventions matching `mod_news` and `mod_publication`:

```php
// Sitemaps
$url['eventSitemap.xml'] = 'index/eventSitemap';

// Events and Event Category
$url['event/detail/(:any)'] = 'index/event/$1';
$url['event/(:any)'] = 'index/event/$1';
$url['events'] = 'index/events';
$url['events/'] = 'index/events';
$url['events/(:num)'] = 'index/events/$1';
$url['events/(:any)'] = 'index/events/$1';
$url['events/(:any)/(:num)'] = 'index/events/$1/$2';
$url['event-calendar'] = 'index/event_calendar';
$url['event-calendar/'] = 'index/event_calendar';
$url['event-calendar/json'] = 'index/event_calendar_json';
$url['event-calendar/(:any)'] = 'index/event_calendar/$1';
```

- **Pagination Resolution (`events/(:num)`):** Just like `mod_news::newses($category = null, $page = 1)`, when `$category` is numeric (`is_numeric($category)`), `mod_event.helper.php::events()` shifts `$page = (int) $category` and resets `$category = null`.
- **Route Ordering:** `$url['event-calendar/json']` must be placed **above** `$url['event-calendar/(:any)']` in `config/url.config.php` so the literal `/json` endpoint is matched before the wildcard category parameter.

---

## 5. Layer-by-Layer Implementation Specification

### 5.1 Installer, Dependency Graph & Seed Data (`installer/` & `lib/ModuleRegistry.class.php`)

1. **`installer/installerbase.php`:**
   - Add `'events'` to `installer_category_starter_labels()` across all 11 languages:
     ```php
     'events' => array(
         'ar' => 'الفعاليات',
         'de' => 'Veranstaltungen',
         'en' => 'Events',
         'fa' => 'رویدادها',
         'fr' => 'Événements',
         'ku' => 'Çalakî',
         'ckb' => 'چالاکییەکان',
         'sv' => 'Evenemang',
         'tr' => 'Etkinlikler',
         'zza' => 'Çalakî',
         'ru' => 'Мероприятия',
     ),
     ```
   - Update `drop_table($table)` so MySQL views (such as `events`, `newses`, `publications`) are dropped via `DROP VIEW IF EXISTS` when `SHOW FULL TABLES LIKE '$table'` reports `Table_type === 'VIEW'`, preventing MySQL `ERROR 1347` during `uninstall()`.
2. **`installer/app/InstallerModuleCatalog.php`:**
   - Register `mod_event_category` (`dependencies: []`, `supports_sample_content: true`, `sample_content_mode: 'module_bootstrap_optional'`) and `mod_event` (`dependencies: ['mod_event_category', 'mod_gallery', 'mod_user']`, `supports_sample_content: true`, `sample_content_mode: 'module_bootstrap_optional'`).
   - Insert `mod_event_category` after `mod_publication_category` and `mod_event` after `mod_publication` in `$installOrder`.
3. **`lib/ModuleRegistry.class.php`:**
   - Add feature definitions to `$featureMap`:
     - `'event.front' => ['modules' => ['mod_event', 'mod_event_category'], 'tables' => ['mod_event', 'mod_event_category']]`
     - `'event.latest' => ['modules' => ['mod_event'], 'tables' => ['mod_event']]`
     - `'event.calendar' => ['modules' => ['mod_event', 'mod_event_category'], 'tables' => ['mod_event', 'mod_event_category']]`
   - Add `'mod_event'` to `$installDependencies` requiring `['mod_event_category', 'mod_gallery', 'mod_user']`.
   - Add `'mod_event_category' => ['mod_event']` to `$uninstallDependents`.
4. **`installer/mod_event_category/index.php`:**
   - `$this->mod = 'mod_event_category';`
   - `$this->tables = array('mod_event_category');`
   - `$this->roles = array('add', 'edit', 'delete', 'list', 'moderate_edit', 'moderate_delete');`
   - Calls `$this->installer_seed_category_bootstrap('mod_event_category', 'events')` during `install()`.
5. **`installer/mod_event/index.php`:**
   - `$this->mod = 'mod_event';`
   - `$this->tables = array('mod_event', 'events');`
   - `$this->roles = array('add', 'edit', 'delete', 'list', 'gallery', 'file', 'faq', 'video', 'edit_status', 'change_status', 'approval', 'moderate_edit', 'moderate_delete', 'video_download');`
   - Creates static upload directories: `static/mod_event/`, `static/mod_event/thumb/`, `static/mod_event/original/`, `static/mod_gallery/mod_event/original/`, and `static/mod_gallery/mod_event/thumb/`.
   - Seeds sample upcoming events (with future `start_date` and `end_date` relative to installation time) when `installer_sample_content_enabled()` is true, resolving category IDs dynamically via `installer_resolve_category_starter_label('events', $lang)`.
6. **Bridge Modules & Menu Seeding:**
   - `installer/mod_gallery_mod/index.php`: Register `['name' => 'Event Galleries', 'mod_name' => 'mod_event', 'mod_key' => 'id', 'mod_value' => 'name', 'user_id' => 1]`.
   - `installer/mod_attachment_mod/index.php`: Register `['name' => 'Event Files', 'mod_name' => 'mod_event', 'mod_key' => 'id', 'mod_value' => 'name', 'user_id' => 1]`.
   - `installer/mod_faq_mod/index.php`: Register `['name' => 'Event FAQs', 'mod_name' => 'mod_event', 'mod_key' => 'id', 'mod_value' => 'name', 'user_id' => 1]`.
   - `installer/mod_menu/index.php`: Add `'url_suffix' => 'events'` starter menu item in all 11 languages to `add_default_slot_roots()`.
   - `installer/lang/{ar,ckb,de,en,fa,fr,ku,ru,sv,tr,zza}.php`: Add `module_labels['mod_event_category']` and `module_labels['mod_event']` across all 11 installer language files (creating `installer/lang/ru.php` to match `InstallerLang::availableLanguages()`).

---

### 5.2 Backend Controllers, Models & Validators (`mod/mod_event/` & `mod/mod_event_category/`)

#### A. `mod/mod_event/index.php` (`extends BaseModuleController`)
- **Declarative Properties:**
  - `protected $has_gallery = true;`
  - `protected $has_files = true;`
  - `protected $has_faq = true;`
  - `protected $need_simple_data = ['lang' => 'lang_lang', 'mod_acl' => 'mod_acl_acl_id'];`
  - `protected $need_nested_data = ['mod_event_category' => 'select_nested_category_id_result', 'mod_menu' => 'select_nested_menu_result'];`
  - `protected $menu_url_pattern = '{lang}/event/{seo}';`
- **Poster Image Configuration (`$image_fields`):**
  - Event posters use a canonical **`3:4` vertical poster aspect ratio**:
    - Main poster (`/`, `800 x 1066 px`)
    - Thumbnail (`/thumb`, `400 x 533 px`)
    - Original archive (`/original`)
  - Implemented via `ImageService` and `list_upload_image()` (quick poster upload from the mobile list sheet).
- **Special Fields (`$special_fields`):**
  - `'deck' => ['type' => 'safe']`, `'summary' => ['type' => 'html']`, `'content' => ['type' => 'html']`.
- **Lifecycle Callbacks (`beforeSave` & `beforeUpdate`):**
  - Normalize `start_date` and `end_date` to `YYYY-MM-DD HH:MM:SS` (`end_date` set to `null` if empty).
  - Auto-generate Wikipedia-style `seo` slug from `name` via `Helper::legacy_seo()` if empty, ensuring uniqueness.
  - Auto-populate `title`, `keywords`, and `description` from `name`, `subject`, and `summary` when omitted.
  - Serialize attached video references via `$this->serializeVideos(Req::Post_Safe('video'))`.
- **Toolbar Toggle Endpoints:**
  - Implement `toggle_status()`, `toggle_hero_slider()`, `toggle_deck()`, and `toggleFlag($column)` returning JSON responses identical to `mod_news/index.php` (without any `??` fallback on `$this->lang[...]`).
- **`lib/BaseModuleController.class.php` Integration:**
  - Add `'mod_event'` to the module array in `BaseModuleController::echoSuccessResponse()`.

#### B. `mod/mod_event_category/index.php`
- Mirrors `mod_news_category/index.php`:
  - Automatic per-language root container (`##`) initialization (`ensureLanguageRootCategories()`).
  - Nested drag-and-drop ordering (`move`, `_change_order`), deletion (`dragdrop_delete`), `show_on_homepage` toggle, and menu synchronization.

#### C. Validators (`mod/mod_event/validator/{add,edit}.validator.php`)
- Validate required fields: `lang`, `category_id` (must exist and not be a `root = 'y'` container), `name` (1–70 chars), `subject` (2–120 chars), `start_date` (valid datetime), `summary` (90–160 chars), `content`.
- Validate date order: if `end_date` is provided, enforce `strtotime($end_date) >= strtotime($start_date)`.
- Validate enum values: `status`, `slider`, and `deck` must be in `['y', 'n']`.

#### D. Management & Control Panel Integration (`mod/management/index.php` & `mod/mod_user/index.php`)
- `mod/management/index.php`:
  - Add `'mod_event'` to `$toggleableModules`.
  - Add `'mod_event' => $this->isFeatureReady('event.front')` to `$sharedModules`.
  - Add `'mod_event'` and `'mod_event_category'` to `$sharedModuleHealth`.
- `mod/mod_user/index.php`:
  - Add `mod_event` (with links to `mod_event/list`, `mod_event/add`, and `mod_event_category/list`) to `_build_panel_categories()` under the `content_and_page` section.

---

### 5.3 Phone-Only Admin Surface (`templates/admin/`)

1. **Navigation & Quick Actions (`sidebar.twig` & `fab.twig`):**
   - `templates/admin/partials/sidebar.twig`: Add `or mod == 'mod_event' or mod == 'mod_event_category'` to `content_active` and add drawer items for `mod_event/list` and `mod_event_category/list`.
   - `templates/admin/partials/fab.twig`: Add speed-dial entry `fab-row-event` (`{{ site.saddress }}mod_event/add`) guarded by `{% if shared_admin_modules is defined and shared_admin_modules.mod_event %}`.
2. **Mobile List View (`templates/admin/mod_event/list.twig`):**
   - **Stat Cards Contract:** Because `BaseModuleController::list()` computes `$suffix = str_replace('mod_', '', $this->module_name)` (`'event'`), `list.twig` **must** bind to singular variable names:
     - `{{ total_event }}`
     - `{{ published_event }}`
     - `{{ draft_event }}`
   - **Card List & Image Modal:** Mobile infinite-scroll card list (`#content-list-container`), pull-to-refresh spinner (`#ptr-spinner`), filter sheet integration, and bottom-sheet quick poster uploader (`#eventImageModal` including `admin/partials/image_uploader.twig` with `3:4` crop ratio).
   - **Fixed Bottom Toolbar (`#contentToolbar`):** Must include all 9 action buttons with exact IDs required by `tools/audit_modernization.py` and `mod_event.js`:
     - `#toolbarStatus` (`data-action="toggle-status"`)
     - `#toolbarDeck` (`data-action="toggle-deck"`)
     - `#toolbarSlider` (`data-action="toggle-slider"`)
     - `#toolbarVideo` (`data-action="open-video"`)
     - `#toolbarGallery` (`data-action="open-gallery"`)
     - `#toolbarFiles` (`data-action="open-files"`)
     - `#toolbarFaq` (`data-action="open-faq"`)
     - `#toolbarEdit` (`data-action="edit-content"`)
     - `#toolbarDelete` (`data-action="delete-content"`)
3. **9-Step Mobile Wizard (`templates/admin/mod_event/add.twig` & `edit.twig`):**
   - Standalone full-screen mobile wizard (`#eventWizardShell`) with zero inline CSS/JS and **zero `|default(...)` filters on `lang.*` keys** (`data-wizard-lang="{{ lang.mod_event.wizard|json_encode|e('html_attr') }}"`):
     - **Step 1:** Language (`.wizard-selection-card` iterating `lang_lang`) & Category (`select_nested_category_id_result` filtered by selected language).
     - **Step 2:** Event Title (`name`, live counter `0 / 70`) & Event Subject/Type (`subject`, e.g., *Theater, Conference, Concert, Cinema*).
     - **Step 3:** Schedule & Venue (`start_date`, optional `end_date`, and `location`).
     - **Step 4:** Participants & Guests (`participants` and `guests`).
     - **Step 5:** Summary & Lead Toggle (`summary` 90–160 char counter + `deck` `.wizard-selection-card`).
     - **Step 6:** Detailed Description (`content` editor).
     - **Step 7:** Event Poster (`admin/partials/image_uploader.twig` configured for `3:4` poster aspect ratio).
     - **Step 8:** Media, Menu & Access Control (Video picker, menu parent selector, `acl_id` `.wizard-selection-card`).
     - **Step 9:** Review Summary Card, Hero Slider (`slider`), and Publication Status (`status`).

---

### 5.4 Public Frontend (`templates/amed/` & `mod/index/helper/mod_event.helper.php`)

#### A. `mod/index/helper/mod_event.helper.php` (Index-Friendly Dual-DB Queries)
- **Zero SQL Alias & Zero `YEAR()`/`MONTH()`/`strftime()` Table Scans:**
  - Never use `SELECT ... AS ...` in helper SQL queries.
  - Never use `YEAR(start_date)` or `strftime('%Y', start_date)`. Instead, compute explicit ISO-8601 date boundaries in PHP:
    ```php
    $monthStart = sprintf('%04d-%02d-01 00:00:00', $year, $month);
    $monthEnd = date('Y-m-t 23:59:59', strtotime($monthStart));
    ```
    Query using index-friendly range predicates:
    `start_date <= '{$monthEnd}' AND COALESCE(end_date, start_date) >= '{$monthStart}'`
    This executes identically on both MySQL and SQLite while utilizing `idx_event_start_date`.
- **Deterministic Agenda Ordering (`_event_agenda($limit = 10)`):**
  - Compute `$now = date('Y-m-d H:i:s');` in PHP.
  - **Primary Query (Upcoming & Ongoing Events):** Fetch published events in active language where `COALESCE(end_date, start_date) >= '{$now}'` ordered by **`start_date ASC, id ASC`** (soonest upcoming event first) up to `$limit`.
  - **Backfill Query (Recent Past Events):** If fewer than `$limit` upcoming events exist, fetch the remaining `$remaining = $limit - count($upcoming)` published events where `COALESCE(end_date, start_date) < '{$now}'` ordered by **`start_date DESC, id DESC`**.
  - Verify poster image existence via `_verify_image_asset()` and expose `poster_url` and `thumb_url`.

#### B. Homepage Right Sidebar Widget (`templates/amed/index/mod_event/event_agenda.twig`)
- Placed in `templates/amed/index/index.twig` inside `<aside id="homepageHeroSidebar">` as **Block R4**, directly below `{% include 'index/mod_news/market_rates.twig' %}`.
- **Uniform Hide-When-Empty Policy:**
  - Invokes `{{ run({"mod": "index", "method": "_event_agenda", "extra": [10]}) }}` and reads `{% set agenda_items = ctx_get('event_agenda') %}`.
  - Renders `<aside class="box sidebar-block-agenda" id="eventAgendaWidget">` **only** when `agenda_items is not empty`.
- **Wireframe Layout (`docs/plan/homepage-layout-wireframe.html` Block 3):**
  - `.section-head`: Title (`{{ lang.index.mod_event.agenda_title }}`) + `.agenda-controls` (All Events link `{{ site.saddress }}events`, Calendar link `{{ site.saddress }}event-calendar`, Previous `<`, Next `>`).
  - `.agenda-body` (`148px` + `1fr` grid):
    - Left `.agenda-poster` (`148px` wide, `3:4` aspect ratio): Displays active event poster `<img>`, subject badge, and date pill, linking to `{{ site.saddress }}event/{{ first_event.seo }}`.
    - Right `.agenda-list`: Pages of 5 `.agenda-item` rows. Hovering, focusing, or tapping an item updates the left poster via `data-poster-url`, `data-event-url`, `data-event-title`, and `data-event-subject` attributes handled by `templates/amed/static/js/event-agenda.js`.
  - `.agenda-dots`: Pagination indicator dots for 5-item pages.

#### C. Public Page Templates (`templates/amed/index/mod_event/`)
1. **`event.twig` (Single Event Detail Page — `/event/{seo}`):**
   - Breadcrumb (`Home > Events > Category > Event Name`).
   - Share & print toolbar (`partials/share_toolbar.twig`).
   - **Structured Event Meta Card:** Displays `subject`, `start_date`, `end_date`, `location`, `participants`, and `guests` alongside the `3:4` event poster.
   - Lead summary (`summary` when `deck == 'y'`), rich HTML `content|raw`, photo gallery (`partials/mod_gallery.twig`), embedded videos, FAQs (`macros/faq.twig`), and downloadable attachments.
   - **Rich `Schema.org/Event` JSON-LD:** Includes `@type: "Event"`, `name`, `startDate` (ISO-8601), `endDate`, `eventStatus` (`https://schema.org/EventScheduled`), `eventAttendanceMode` (`https://schema.org/OfflineEventAttendanceMode`), `location` (`Place` with `name` and `address`), `image`, `description`, `organizer` (`Organization`), and `performer`.
2. **`events.twig` (Category & Archive Listing — `/events`, `/events/{category}`):**
   - Category filter bar (`_event_categories`), responsive `3:4` poster card grid with date badges, subject tags, venue labels, and pagination.
3. **`calendar.twig` (Interactive Monthly Event Calendar — `/event-calendar`):**
   - Category, year, and month filter controls + monthly calendar grid and selected-day event list powered by `templates/amed/static/js/event-calendar.js` and `/event-calendar/json`.

#### D. Hero Slider, Site Search, XML Sitemap & `llms.txt` Integration
1. **Hero Slider (`mod/index/helper/mod_hero_slider.helper.php` & `templates/amed/static/css/hero-news-slider.css`):**
   - Add `loadEventItems($limit)` to `mod_hero_slider.helper.php` (`status = 'y' AND slider = 'y'`).
   - Because event posters have a `3:4` vertical ratio while the hero slider stage is `800 x 450 px` (`16:9`), slides with `type === 'event'` render with a CSS class `.hero-news-slide--event` that centers the `3:4` poster cleanly over a subtle dark blurred backdrop without stretching or cropping faces/text.
2. **Global Search (`mod/index/helper/search.helper.php` & `templates/amed/index/search.twig`):**
   - Add text search on `mod_event` (`name`, `subject`, `location`, `summary`, `content`) cached under `$keyword . "-search-mod_event"`.
   - Extend gallery search (`$modName === 'mod_event'`) and image search in `search.helper.php` so event posters and event galleries appear in image/media search tabs.
   - Render the **Events** results section in `templates/amed/index/search.twig`.
3. **XML Sitemap (`mod/index/index.php`):**
   - Register `eventSitemap.xml` in `sitemapIndex()` (guarded by `$this->isFeatureReady('event.latest')`) and implement `eventSitemap($page = 1)`.
4. **AI Discovery Feed (`lib/LlmsService.class.php`):**
   - Add `getActiveEvents($lang, 15)` and render `## Events & Cultural Agenda` in `/llms.txt` and `/llms-full.txt` when `$this->runtime->isModuleReady('mod_event')` is true.

---

### 5.5 MCP Server & Resource Integration (`lib/McpEventTools.class.php`)

1. **`lib/McpEventTools.class.php` (extends `McpCrudToolsBase`):**
   - Event CRUD tools: `list_events`, `get_event`, `create_event`, `update_event`, `delete_event`.
   - Category CRUD tools: `list_event_categories`, `get_event_category`, `create_event_category`, `update_event_category`, `delete_event_category`.
2. **`lib/McpServer.class.php`:**
   - Require `McpEventTools.class.php` and register `'event' => McpEventTools::class` in `DOMAIN_CLASSES`.
3. **`lib/McpResourceManager.class.php`:**
   - Register URI template `cms://event/{id}` in `listResourceTemplates()` and route `#^cms://event/(\d+)$#` in `readResource()`.
4. **`tasks/mcp_smoke.php`:**
   - Add smoke assertions for `list_events`, `list_event_categories`, and `cms://event/{id}`.

---

## 6. Complete File Inventory

### 6.1 Files to Create
- **Installer (`installer/`):**
  - `installer/mod_event/index.php`
  - `installer/mod_event/mod_event.sql`
  - `installer/mod_event/events.sql`
  - `installer/mod_event_category/index.php`
  - `installer/mod_event_category/mod_event_category.sql`
  - `installer/lang/ru.php`
- **Backend Modules (`mod/`):**
  - `mod/mod_event/index.php`
  - `mod/mod_event/mod_event.sql`
  - `mod/mod_event/events.sql`
  - `mod/mod_event/model/mod_event.model.php`
  - `mod/mod_event/model/events.model.php`
  - `mod/mod_event/validator/add.validator.php`
  - `mod/mod_event/validator/edit.validator.php`
  - `mod/mod_event/lang/{ar,ckb,de,en,fa,fr,ku,ru,sv,tr,zza}.php` (11 files)
  - `mod/mod_event_category/index.php`
  - `mod/mod_event_category/mod_event_category.sql`
  - `mod/mod_event_category/model/mod_event_category.model.php`
  - `mod/mod_event_category/validator/add.validator.php`
  - `mod/mod_event_category/validator/edit.validator.php`
  - `mod/mod_event_category/lang/{ar,ckb,de,en,fa,fr,ku,ru,sv,tr,zza}.php` (11 files)
  - `mod/index/helper/mod_event.helper.php`
- **MCP Layer (`lib/`):**
  - `lib/McpEventTools.class.php`
- **Phone-Only Admin Templates & Assets (`templates/admin/`):**
  - `templates/admin/mod_event/list.twig`
  - `templates/admin/mod_event/add.twig`
  - `templates/admin/mod_event/edit.twig`
  - `templates/admin/mod_event_category/list.twig`
  - `templates/admin/mod_event_category/add.twig`
  - `templates/admin/mod_event_category/edit.twig`
  - `templates/admin/assets/css/mod_event.css`
  - `templates/admin/assets/css/mod_event_wizard.css`
  - `templates/admin/assets/css/mod_event_category.css`
  - `templates/admin/assets/js/mod_event.js`
  - `templates/admin/assets/js/mod_event_wizard.js`
  - `templates/admin/assets/js/mod_event_category.js`
- **Public Templates & Assets (`templates/amed/`):**
  - `templates/amed/index/mod_event/event_agenda.twig`
  - `templates/amed/index/mod_event/event.twig`
  - `templates/amed/index/mod_event/events.twig`
  - `templates/amed/index/mod_event/calendar.twig`
  - `templates/amed/static/css/event-agenda.css`
  - `templates/amed/static/css/events.css`
  - `templates/amed/static/js/event-agenda.js`
  - `templates/amed/static/js/event-calendar.js`

### 6.2 Existing Files to Update
- `config/url.config.php`
- `lib/ModuleRegistry.class.php`
- `lib/BaseModuleController.class.php`
- `lib/LlmsService.class.php`
- `lib/McpServer.class.php`
- `lib/McpResourceManager.class.php`
- `installer/installerbase.php`
- `installer/app/InstallerModuleCatalog.php`
- `installer/mod_menu/index.php`
- `installer/mod_gallery_mod/index.php`
- `installer/mod_attachment_mod/index.php`
- `installer/mod_faq_mod/index.php`
- `installer/lang/{ar,ckb,de,en,fa,fr,ku,sv,tr,zza}.php` (10 existing files + new `ru.php` = 11)
- `data/sqlite/bulletin.sql`
- `mod/management/index.php`
- `mod/management/lang/{ar,ckb,de,en,fa,fr,ku,ru,sv,tr,zza}.php` (11 files)
- `mod/mod_user/index.php`
- `mod/mod_user/lang/{ar,ckb,de,en,fa,fr,ku,ru,sv,tr,zza}.php` (11 files)
- `templates/admin/partials/sidebar.twig`
- `templates/admin/partials/fab.twig`
- `mod/index/index.php`
- `mod/index/helper/index.php`
- `mod/index/helper/mod_hero_slider.helper.php`
- `mod/index/helper/search.helper.php`
- `mod/index/lang/{ar,ckb,de,en,fa,fr,ku,ru,sv,tr,zza}.php` (11 files)
- `templates/amed/index/index.twig`
- `templates/amed/index/search.twig`
- `templates/amed/static/css/hero-news-slider.css` & `templates/amed/static/css/hero-news-slider.min.css` (rebuilt via `python3 tools/min_css_guard.py --build`)
- `tools/audit_modernization.py` (add `'mod_event'` to `MODULES` and allow `start_date` in `add_history` check)
- `tools/audit_content_seo.py` (add `'event': 'mod_event'` and `'mod_event'` to `MODULE_ALIASES` / `MODULES_WITH_SEO_META`)
- `tasks/mcp_smoke.php`

---

## 7. Phased Execution & Quality Gate Verification

### Execution Phases
1. **Phase 1 — Database Schema, Installer, Bridges & Models:**
   - Create `installer/mod_event_category`, `installer/mod_event` (`mod_event.sql`, `events.sql`), ORM models, validators, and update `installerbase.php`, `InstallerModuleCatalog.php`, `ModuleRegistry.class.php`, bridge installers, and `data/sqlite/bulletin.sql`.
2. **Phase 2 — 11-Language Packs & Phone-Only Admin Surface:**
   - Create/update all 11-language files across `mod_event`, `mod_event_category`, `management`, `mod_user`, `index`, and `installer`.
   - Implement `mod/mod_event/index.php`, `mod/mod_event_category/index.php`, `templates/admin/mod_event/{list,add,edit}.twig`, `templates/admin/mod_event_category/{list,add,edit}.twig`, and external CSS/JS assets.
3. **Phase 3 — Public Theme (`templates/amed/`), Homepage Agenda Widget, Search, Slider, SEO & MCP:**
   - Implement `mod/index/helper/mod_event.helper.php`, `templates/amed/index/mod_event/{event_agenda,event,events,calendar}.twig`, and external CSS/JS assets.
   - Wire `mod/index/index.php`, `mod_hero_slider.helper.php`, `search.helper.php`, `LlmsService.class.php`, and `McpEventTools.class.php`.
4. **Phase 4 — Automated Quality Gates:**
   - Run all verification guards until every check passes with zero warnings or errors.

### Mandatory Verification Commands
```bash
# 1. PHP Syntax Validation
find mod/mod_event* installer/mod_event* lib/McpEventTools.class.php mod/index/helper/mod_event.helper.php -name "*.php" -exec php -l {} +

# 2. Internationalization, Lexicon, Forbidden Default & SQL Alias Guard
python3 tools/i18n_guard.py

# 3. Strict HTML/CSS/JS Code Separation Guard
python3 tools/code_separation_guard.py

# 4. Minified CSS Bundle Parity Guard
python3 tools/min_css_guard.py

# 5. Domain & Module Boundary Guard
python3 tools/module_boundary_guard.py --all

# 6. Module Modernization Audit
python3 tools/audit_modernization.py
```
