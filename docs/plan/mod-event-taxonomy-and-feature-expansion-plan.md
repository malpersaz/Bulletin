# `mod_event` & `mod_event_category` Taxonomy, Civic Calendar, and Functional Expansion Plan

- **Target Repository:** `AF/Bulletin` (Amed Framework & Bulletin CMS)
- **Target Modules:** `mod_event`, `mod_event_category`, and `mod/index/helper/mod_event.helper.php`
- **Public Theme Target:** `templates/amed/index/mod_event/` (`event.twig`, `events.twig`, `calendar.twig`, `event_agenda.twig`, and `sections/section_*.twig`)
- **Prerequisite Baseline:** `docs/plan/mod-event-modernization-plan.md`
- **Newly Provisioned Categories (via MCP):**
  - `Sınavlar` (`id: 5`, `seo: Sınavlar`, `lang: tr`)
  - `Resmi Tatil` (`id: 6`, `seo: Resmi_Tatil`, `lang: tr`)
  - `Bayramlar` (`id: 7`, `seo: Bayramlar`, `lang: tr`)

---

## 1. Executive Summary & Editorial Vision

Adding **Exams (`Sınavlar`)**, **Official Holidays (`Resmi Tatil`)**, and **Religious, National & Cultural Holidays (`Bayramlar`)** transforms `mod_event` from a simple local venue-listing module into a **comprehensive Civic, Cultural, and Public Service Calendar**.

In digital journalism and regional news publishing, queries such as *"How many days until YKS/KPSS?"*, *"When is the next public holiday?"*, *"When is Newroz / Ramazan Bayramı?"*, and *"What cultural events are happening in Amed this week?"* represent high-intent, recurring evergreen search traffic.

Furthermore, the `/events` hub page (e.g., `http://localhost:8080/tr/events`) must present each category through its own **dedicated Twig template** (`templates/amed/index/mod_event/sections/section_*.twig`). Even when a category has zero scheduled events in the database, its section template must remain visible on the page and display the localized empty-state notice:
> **"Bu kategoride takvime kaydedilmiş bir etkinlik bulunmamaktadır"**

### Non-Negotiable Architectural Mandates
1. **100% English Codebase & Routing (`AGENTS.md` Rule 7 & `tools/i18n_guard.py`):**
   - Every PHP method, variable, SQL column, CSS class, JS identifier, Twig filename (`section_exams.twig`, `section_official_holidays.twig`), translation key (`en.php`), and URL route (`/events`, `/event/{seo}`, `/event-calendar`, `/event-calendar/json`) must be written strictly in English.
2. **Zero Forbidden Fallbacks & Zero SQL Aliases (`tools/i18n_guard.py`):**
   - Never use `|default(...)` in Twig or `??` in PHP on `lang.*` / `$this->lang[...]` expressions (`RE_FORBIDDEN_DEFAULT`).
   - Never use single-line `SELECT ... AS ...`, `FROM ... AS ...`, or `JOIN ... AS ...` in `.php` or `.sql` files (`RE_SQL_ALIAS`).
3. **Strict Code Separation (`AGENTS.md` Rule 2 & `tools/code_separation_guard.py`):**
   - Zero inline CSS (`style="..."` or `<style>`) and zero inline JavaScript (`onclick="..."` or executable `<script>`) in any `.php`, `.twig`, or `.html` template. Only `<script type="application/ld+json">` is permitted.
4. **Wikipedia-Style SEO Slugs (`AGENTS.md` Rule 10):**
   - Preserve original casing and alphabet characters with underscore separators via `Helper::legacy_seo()` (e.g., `Kültür_Sanat`, `Edebiyat_Söyleşi`, `Sınavlar`, `Resmi_Tatil`, `Bayramlar`).
5. **Complete 11-Language Parity:**
   - All new translation keys in `mod/index/lang/` must be synchronized across all 11 supported languages (`ar`, `ckb`, `de`, `en`, `fa`, `fr`, `ku`, `ru`, `sv`, `tr`, `zza`).

---

## 2. Taxonomy & Hierarchy Expansion (`mod_event_category`)

### 2.1 Active Root Categories in `mod_event_category`

| ID | Category Name (`tr`) | Wikipedia-Style Slug (`seo`) | Dedicated Twig Template Key | Scope & Editorial Purpose |
| :--- | :--- | :--- | :--- | :--- |
| `1` | **Kültür & Sanat** | `Kültür_Sanat` | `culture_art` | Theater, concerts, exhibitions, and performing arts. |
| `2` | **Edebiyat & Söyleşi** | `Edebiyat_Söyleşi` | `literature_talk` | Book fairs, author signings, poetry readings, and panels. |
| `3` | **Sinema & Belgesel** | `Sinema_Belgesel` | `cinema_documentary` | Independent cinema, documentary screenings, and director Q&As. |
| `4` | **Atölye & Akademi** | `Atölye_Akademi` | `workshop_academy` | Journalism workshops, photography classes, and training programs. |
| `5` | **Sınavlar** | `Sınavlar` | `exams` | ÖSYM, MEB, university, civil service, and language exam schedules. |
| `6` | **Resmi Tatil** | `Resmi_Tatil` | `official_holidays` | Statutory public holidays, administrative bridge leaves, and school breaks. |
| `7` | **Bayramlar** | `Bayramlar` | `holidays` | Religious holidays, national holidays, and regional/seasonal celebrations. |

### 2.2 Optional Future Root Categories for Regional Journalism

| Proposed Name (`tr`) | Proposed Slug (`seo`) | Proposed Name (`ku` equivalent) | Dedicated Twig Template Key | Editorial Justification |
| :--- | :--- | :--- | :--- | :--- |
| **Festivaller & Şenlikler** | `Festivaller_Şenlikler` | *Festîval & Mîhrîcan* | `default` *(or `festivals`)* | Film festivals, theater festivals, gastronomy/harvest festivals, and municipal fairs. |
| **Spor & Turnuvalar** | `Spor_Turnuvalar` | *Spor & Turnûva* | `default` *(or `sports`)* | Regional football fixtures (e.g., Amedspor matchdays), marathons, and youth tournaments. |
| **Kongre & Sempozyum** | `Kongre_Sempozyum` | *Kongre & Sempozyûm* | `default` *(or `congress`)* | Academic conferences, medical/legal congresses, trade fairs, and chamber summits. |
| **Anma & Özel Günler** | `Anma_Özel_Günler` | *Bîranîn & Rojên Taybet* | `default` *(or `commemorations`)* | International observance days (8 March, 21 February Mother Tongue Day, 1 May, World Press Freedom Day). |

### 2.3 Recommended 2-Level Subcategory Hierarchy (`parent_id`)

1. **Under `Sınavlar` (`parent_id: 5`):**
   - `ÖSYM Sınavları` (`seo: ÖSYM_Sınavları`) — YKS, KPSS, ALES, DGS, MSÜ, TUS.
   - `MEB & Açıköğretim Sınavları` (`seo: MEB_Açıköğretim_Sınavları`) — LGS, İOKBS, AÖL, AÖF, AUZEF.
   - `Yabancı Dil Sınavları` (`seo: Yabancı_Dil_Sınavları`) — YDS, YÖKDİL, e-YDS, TOEFL, IELTS.
   - `Akademik Takvim` (`seo: Akademik_Takvim`) — Dicle University and regional university semester/registration dates.
2. **Under `Bayramlar` (`parent_id: 7`):**
   - `Dini Bayramlar` (`seo: Dini_Bayramlar`) — Ramazan Bayramı, Kurban Bayramı, Aşure, Çarşema Sor, Gağan.
   - `Kültürel & Mevsimsel Bayramlar` (`seo: Kültürel_Mevsimsel_Bayramlar`) — Newroz, Hıdırellez, Kosegeldi.
   - `Milli Bayramlar` (`seo: Milli_Bayramlar`) — Official national holiday ceremonies.
3. **Under `Resmi Tatil` (`parent_id: 6`):**
   - `Kamu & İdari Tatiller` (`seo: Kamu_İdari_Tatiller`) — Statutory holidays and cabinet-declared bridge leaves (*idari izin*).
   - `Okul & Yarıyıl Tatilleri` (`seo: Okul_Yarıyıl_Tatilleri`) — MEB autumn/spring mid-term breaks (*ara tatil*), semester break (*yarıyıl tatili*), and summer vacation calendar.

---

## 3. Comprehensive Codebase Audit: Defects & Edge Cases Identified

A full end-to-end audit of `mod/index/helper/mod_event.helper.php`, `mod/mod_event_category/index.php`, `templates/amed/index/mod_event/*.twig`, and `templates/amed/static/js/event-calendar.js` uncovered **8 concrete technical issues** that must be addressed:

| # | File & Location | Identified Defect / Edge Case | Architectural Impact & Fix Required |
| :--- | :--- | :--- | :--- |
| **1** | `templates/amed/index/mod_event/event.twig` (Line 175) | Renders `{{ d.description\|raw }}` instead of `{{ d.content\|raw }}` inside `.event-content-body`. | **Critical Bug:** `d.description` is the 255-character SEO meta string. The full rich HTML article body (`d.content`) is never rendered on `/event/{seo}`! Must render `{{ d.content\|raw }}`. |
| **2** | `templates/amed/index/mod_event/events.twig` (Lines 69–179) | Uses a single monolithic card grid and hides empty category pills when filtered (`{% if cat.event_count > 0 or not is_filtered %}`). | Must be refactored to include dedicated per-category Twig templates (`sections/section_*.twig`) that **always render visibly** even when empty, showing `"Bu kategoride takvime kaydedilmiş bir etkinlik bulunmamaktadır"`. |
| **3** | `mod/mod_event_category/index.php` (Lines 120–149) vs `mod_event.helper.php` | Admin UI `ensureLanguageRootCategories()` creates a language container `##TR` (`root = 'y'`, `parent_id = 0`) and reparents top-level categories under `##TR`'s ID (`parent_id = $languageRootId`), whereas MCP/seed categories have `parent_id = 0`. | **Hierarchy Edge Case:** In `mod_event.helper.php`, top-level editorial categories must be detected as rows where `root = 'n'` AND (`parent_id == 0` OR parent row has `root = 'y'`), never by `parent_id == 0` alone! |
| **4** | `mod/index/helper/mod_event.helper.php` (`events()`, Lines 254–264) | 1. Does not `rawurldecode($category)` before querying `mod_event_category` (breaks UTF-8 slugs like `K%C3%BClt%C3%BCr_Sanat` or `S%C4%B1navlar`).<br>2. If `$catModel->getSize() === 0`, it neither triggers `_404()` nor filters `category_id`, returning all events.<br>3. Does not shift `$page` when `$category` is numeric (`/events/2`).<br>4. Does not include child subcategory IDs via `getCategoryDescendantIds()`. | Must URL-decode `$category`, shift numeric `$category` to `$page`, trigger `$this->_404()` on unknown category slugs, and query `category_id IN (...)` across all descendant subcategory IDs. |
| **5** | `mod/index/helper/mod_event.helper.php` (`event()`, Lines 293–305) | Queries `where_seo_and_status_and_lang($seo, 'y', $lang)` directly without `rawurldecode()` or numeric ID fallback. | Must use `$this->findItemBySeoOrIdStrict('mod_event.events', $seo, $lang, 'y')` from `PublicHelperTrait` (matching `mod_news` and `mod_publication`). |
| **6** | `mod/index/helper/mod_event.helper.php` (`calendar_json()`, Line 398) & `event-calendar.js` (Lines 80–84) | Queries `start_date >= $monthStart AND start_date <= $monthEnd` and maps events only to `ev.start_day` in JavaScript. | **Multi-Day Holiday Bug:** A 4-day *Bayram* or 9-day *Resmi Tatil* only highlights Day 1 on the calendar grid, and holidays spanning across month boundaries disappear in the second month. |
| **7** | `templates/amed/index/mod_event/calendar.twig` (Lines 83–89) & `event-calendar.js` (Lines 25–28, 167) | Hardcodes English weekday labels (`Mon`..`Sun`), English month names (`January`..`December`), and `'No events found for this date.'` in JS/Twig; lacks category filter pills on `/event-calendar`. | Must localize weekday headers, month names, and empty state via `data-*` attributes from `lang.index.mod_event` and add category filter pills to `calendar.twig`. |
| **8** | `templates/amed/index/mod_event/event.twig` | `mod_event.helper.php` computes `days_until`, `is_today`, `is_tomorrow`, `google_calendar_url`, `map_search_url`, `iso_start`, and `iso_end`, but `event.twig` does not render the countdown badge, Google Calendar / `.ics` buttons, clickable map link, or related news. | Must surface countdown callout, `.ics` / Google Calendar actions, venue map link, and related `mod_news` articles on `event.twig`. |

---

## 4. Technical Enhancement Specification

### Phase 1: Per-Category Dedicated Twig Sections & Always-Visible Empty-State Architecture on `/events`

To transform `/events` (e.g., `http://localhost:8080/tr/events`) into a structured, multi-section editorial calendar hub, each category in `mod_event_category` has its own dedicated Twig partial under `templates/amed/index/mod_event/sections/`.

#### 1.1 Dedicated Twig Files per Category (`templates/amed/index/mod_event/sections/`)

All filenames and code identifiers strictly follow the English-only code rule (`AGENTS.md` Rule 7):

| Category Name (`tr`) | Category Slug (`seo`) | Dedicated Twig Template File | Section Layout & Metadata Focus |
| :--- | :--- | :--- | :--- |
| **Kültür & Sanat** | `Kültür_Sanat` | `templates/amed/index/mod_event/sections/section_culture_art.twig` | `3:4` stage/concert poster cards, venue location, start time, and date seal. |
| **Edebiyat & Söyleşi** | `Edebiyat_Söyleşi` | `templates/amed/index/mod_event/sections/section_literature_talk.twig` | Panel/author talk cards highlighting `participants` / `guests`, venue, and date. |
| **Sinema & Belgesel** | `Sinema_Belgesel` | `templates/amed/index/mod_event/sections/section_cinema_documentary.twig` | Screening poster cards with `subject` genre tag, screening hall, and time. |
| **Atölye & Akademi** | `Atölye_Akademi` | `templates/amed/index/mod_event/sections/section_workshop_academy.twig` | Training/workshop cards showing duration (`start_date` – `end_date`), instructor/participants, and venue. |
| **Sınavlar** | `Sınavlar` | `templates/amed/index/mod_event/sections/section_exams.twig` | Exam schedule cards highlighting **countdown badge** (`X gün kaldı`), exam session date/time, and institution (`subject`: ÖSYM, MEB, etc.). |
| **Resmi Tatil** | `Resmi_Tatil` | `templates/amed/index/mod_event/sections/section_official_holidays.twig` | Official holiday timeline/strip cards emphasizing start–end date span, total holiday days (`duration_days`), and countdown pill. |
| **Bayramlar** | `Bayramlar` | `templates/amed/index/mod_event/sections/section_holidays.twig` | Religious, national, and seasonal celebration cards with eve/holiday date range (`start_date` – `end_date`), duration, and countdown badge. |
| *(Any Future Category)* | *(Dynamic)* | `templates/amed/index/mod_event/sections/section_default.twig` | Fallback section partial so any newly created root category automatically renders a complete section block. |

#### 1.2 Unconditional Section Visibility & Empty-State Message Contract

1. **Always-Visible Section Rule:**
   - On `/events` (`http://localhost:8080/tr/events`), every active top-level category's Twig file (`section_*.twig`) **MUST be rendered in sequence**, even when `section.events` is empty (`section.event_count == 0`).
   - On `/events/{category_seo}` (e.g., `http://localhost:8080/tr/events/Sınavlar`), the selected category's Twig file (`section_exams.twig`) is rendered in single-category mode; if it has zero events, it displays the exact same category section frame and empty-state notice.
2. **Always-Visible Filter Pills:**
   - In `templates/amed/index/mod_event/events.twig`, remove `{% if cat.event_count > 0 or not is_filtered %}` so all category navigation pills (including categories with `0` events) are always visible in the filter bar.
3. **Mandatory Empty-State Message (`lang.index.mod_event.no_category_events_recorded`):**
   - Inside every `section_*.twig` file, when `section.events is empty`, the template renders the category header (`<h2>`, description, badge) followed by:
     ```twig
     <div class="event-section-empty-state rounded p-4 text-center my-3" role="status">
         <i class="bi bi-calendar-x event-section-empty-icon d-block mb-2" aria-hidden="true"></i>
         <p class="event-section-empty-text mb-0">{{ lang.index.mod_event.no_category_events_recorded }}</p>
     </div>
     ```
   - In Turkish (`tr`), `lang.index.mod_event.no_category_events_recorded` is defined as:
     > `"Bu kategoride takvime kaydedilmiş bir etkinlik bulunmamaktadır"`

#### 1.3 Single-Query Batch Hydration & Hierarchy Resolution in `mod/index/helper/mod_event.helper.php`

1. **Root Container (`##TR`) Safe Top-Level Category Detection:**
   - Load all rows for `$lang` from `mod_event_category` ordered by `order ASC, id ASC`.
   - Identify container rows (`$row['root'] === 'y'` or `strpos(trim($row['name']), '##') === 0`) and exclude them from public display.
   - Among non-container allowed categories (`$row['root'] === 'n'` and in `$allowedCatIds`):
     - A category is a **top-level editorial category** if `(int) $row['parent_id'] === 0` OR its `parent_id` points to a container row (`root === 'y'`) OR its `parent_id` does not exist in the non-container category map.
     - Otherwise, it is a **child subcategory**, and its ancestor chain maps it to its owning top-level editorial category.
2. **Category-to-Template Key Resolver (`_resolveCategoryTemplateKey(array $cat): string`):**
   - Normalizes `$cat['seo']` (via `rawurldecode`) and maps:
     - `'Kültür_Sanat'` $\rightarrow$ `'culture_art'`
     - `'Edebiyat_Söyleşi'` $\rightarrow$ `'literature_talk'`
     - `'Sinema_Belgesel'` $\rightarrow$ `'cinema_documentary'`
     - `'Atölye_Akademi'` $\rightarrow$ `'workshop_academy'`
     - `'Sınavlar'` $\rightarrow$ `'exams'`
     - `'Resmi_Tatil'` $\rightarrow$ `'official_holidays'`
     - `'Bayramlar'` $\rightarrow$ `'holidays'`
     - Any other slug $\rightarrow$ `'default'`
   - Sets `$cat['template_key'] = $key` and `$cat['template_path'] = 'index/mod_event/sections/section_' . $key . '.twig'`.
3. **Zero N+1 Query Hydration in `events($category = null, $page = 1)`:**
   - **Numeric Page Shift:** If `$category !== null && is_numeric($category)`, shift `$page = max(1, (int) $category)` and set `$category = null`.
   - **Hub Mode (`$category === null` — `/tr/events`):**
     - Execute a single query fetching all published, ACL-allowed events in `$lang` across `$allowedCatIds`, ordered with upcoming/ongoing events (`COALESCE(end_date, start_date) >= $now`) first (`start_date ASC, id ASC`) followed by past events (`start_date DESC, id DESC`).
     - Group cleaned events in PHP by their owning top-level category ID (including events assigned to child subcategories) and slice up to `6` items per section (`$section['events'] = array_slice($grouped[$topCatId] ?? [], 0, 6)`).
     - Every top-level category is added to `$categorySections` even when `$section['events']` is `[]`.
   - **Filtered Category Mode (`$category !== null` — `/tr/events/{category_seo}`):**
     - Decode `$category = trim(rawurldecode(html_entity_decode((string) $category, ENT_QUOTES, 'UTF-8')))`.
     - Look up the category in `mod_event_category` (`lang = $lang AND root = 'n'`). If not found or not in `$allowedCatIds`, return `$this->_404()`.
     - Collect all descendant subcategory IDs via `$descendantIds = array_intersect($this->getCategoryDescendantIds((int) $activeCategory['id'], $lang, 'mod_event_category'), $allowedCatIds)`.
     - Query paginated events (`$limit = 12`, `$offset = ($page - 1) * $limit`) where `category_id IN ($descendantIdList)`.
     - Build `$activeSection` with `template_path`, `subcategories`, and `events` (which may be `[]`, triggering `"Bu kategoride takvime kaydedilmiş bir etkinlik bulunmamaktadır"` inside the category's Twig template).

---

### Phase 2: Event Detail Page (`event.twig`) Fixes & Civic Utility Widgets

1. **Fix Rich HTML Body Rendering (`templates/amed/index/mod_event/event.twig`):**
   - Replace `{{ d.description|raw }}` at line 175 with `{{ d.content|raw }}`.
   - Guard `d.summary` callout so it respects `d.has_deck` (`{% if d.has_deck and d.summary is defined and d.summary is not empty %}`).
2. **Strict SEO / ID Lookup in `mod/index/helper/mod_event.helper.php::event($seo)`:**
   - Use `$eventModel = $this->findItemBySeoOrIdStrict('mod_event.events', (string) $seo, $lang, 'y');` so URL-encoded slugs and numeric IDs resolve cleanly before running ACL and hierarchy permission checks.
3. **Calculate Multi-Day Duration (`duration_days`) in `_cleanEventRow()`:**
   - When `start_date` and `end_date` are present, compute `$row['duration_days'] = max(1, (int) floor((strtotime(date('Y-m-d', $endTs)) - strtotime(date('Y-m-d', $startTs))) / 86400) + 1);` (e.g., 4 days for *Kurban Bayramı* or 9 days for a bridge holiday).
4. **Add Countdown Callout Banner (`days_until` / `is_today` / `is_tomorrow`):**
   - In `event.twig` header/sidebar, display a prominent status & countdown banner:
     - If `d.is_today`: `{{ lang.index.mod_event.today }}` (Live pulse badge).
     - If `d.is_tomorrow`: `{{ lang.index.mod_event.tomorrow }}`.
     - If `d.is_upcoming and d.days_until > 1`: **`{{ d.days_until }} {{ lang.index.mod_event.days_left }}`** (e.g., *"42 gün kaldı"*).
     - If `not d.is_upcoming and not d.is_today`: `{{ lang.index.mod_event.event_completed }}`.
5. **Add One-Click Calendar Export & Map Actions on `event.twig`:**
   - Add **Google Calendar** (`d.google_calendar_url`) and **Apple / Outlook (`.ics`)** download buttons to `.event-meta-card` in `event.twig` using `data-*` attributes handled by `templates/amed/static/js/event-calendar.js` (zero inline JS).
   - Link `d.location` in `event.twig` to `d.map_search_url` when `d.location` is non-empty.
6. **Related News Integration (`mod_news` Context Bridge):**
   - In `mod/index/helper/mod_event.helper.php::event($seo)`:
     - When `$this->isFeatureReady('news.front')` is true, query up to 4 published news articles (`mod_news`) in the active language matching the event's `subject` or primary keyword token (using safe escaped `LIKE` predicates without SQL aliases), and expose them via `$this->theme->ctx_set('related_news', $relatedNews)`.
   - In `event.twig`:
     - Render a **"Related News"** (`{{ lang.index.mod_event.related_news }}`) card grid below the event body when `related_news is not empty`.

---

### Phase 3: Multi-Day Holiday Spanning & Category Filtering in Interactive Calendar (`calendar.twig` & `event-calendar.js`)

1. **Multi-Day Overlap Query & Category Filter in `calendar_json()` (`mod/index/helper/mod_event.helper.php`):**
   - Accept optional `category` (`seo` or `id`) query parameter in `calendar_json()`. When provided, resolve the category and its descendant subcategory IDs via `getCategoryDescendantIds()` and filter `category_id IN (...)`.
   - Replace the single-point start date check:
     ```php
     $eventModel->addWhere("start_date >= '{$monthStart}' AND start_date <= '{$monthEnd}'");
     ```
     with the index-friendly overlap predicate:
     ```php
     $eventModel->addWhere("start_date <= '{$monthEnd}' AND COALESCE(end_date, start_date) >= '{$monthStart}'");
     ```
   - Compute `span_days` (array of integer day numbers `1..$daysInMonth` within the requested `$year-$month` that the event covers between `start_date` and `COALESCE(end_date, start_date)`):
     ```php
     $spanStartTs = max(strtotime($monthStart), strtotime(date('Y-m-d 00:00:00', strtotime($cleaned['start_date']))));
     $rawEnd = !empty($cleaned['end_date']) ? $cleaned['end_date'] : $cleaned['start_date'];
     $spanEndTs = min(strtotime($monthEnd), strtotime(date('Y-m-d 23:59:59', strtotime($rawEnd))));
     $spanDays = [];
     for ($curTs = $spanStartTs; $curTs <= $spanEndTs; $curTs += 86400) {
         $spanDays[] = (int) date('j', $curTs);
     }
     $spanDays = array_values(array_unique($spanDays));
     ```
   - Include `span_days`, `category_id`, `category_name`, `category_seo`, `days_until`, `is_today`, and `is_upcoming` in each JSON event object.
2. **Category Filter Bar, Localization & Multi-Day Highlighting (`calendar.twig` & `event-calendar.js`):**
   - In `templates/amed/index/mod_event/calendar.twig`:
     - Add category filter pills (`All`, `Kültür & Sanat`, `Edebiyat & Söyleşi`, `Sinema & Belgesel`, `Atölye & Akademi`, `Sınavlar`, `Resmi Tatil`, `Bayramlar`) above the calendar grid using `data-category-seo` attributes.
     - Pass localized weekday headers (`lang.index.mod_event.weekdays_short`), localized month names (`lang.index.mod_event.months_full`), and `data-empty-text="{{ lang.index.mod_event.no_events_found|e('html_attr') }}"` on `#calendarApp` so zero hardcoded English strings remain in `calendar.twig` or `event-calendar.js`.
   - In `templates/amed/static/js/event-calendar.js`:
     - Iterate `ev.span_days` (falling back to `[ev.start_day]`) so multi-day holidays (*Ramazan Bayramı*, *Kurban Bayramı*, *Ara Tatil*) highlight every day of the holiday across the calendar grid.
     - Re-fetch `/event-calendar/json?year=Y&month=M&category=SEO` when a category filter pill is clicked.

---

### Phase 4: Structured FAQ & Milestone Template Pattern for Exams & Holidays

Because `mod_event` already integrates `mod_faq` (`protected $has_faq = true` in `mod/mod_event/index.php` and `{{ faq.schema(faqs) }}` in `event.twig`), editorial workflows (both Admin UI and MCP `create_faq`) can attach structured FAQs directly to exam and holiday records:

- **Standard FAQ Pattern for `Sınavlar` Records (e.g., *2027 YKS*, *2027 KPSS Lisans*):**
  1. *Başvurular ne zaman başlıyor ve bitiyor?* (Application start/end dates)
  2. *Geç başvuru günü ne zaman?* (Late application deadline)
  3. *Sınav hangi tarihte ve saat kaçta yapılacak?* (Exact session date & time)
  4. *Sonuçlar ne zaman açıklanacak?* (Official result announcement date)
- **Standard FAQ Pattern for `Resmi Tatil` & `Bayramlar` Records:**
  1. *Arife günü yarım gün mü, hangi güne denk geliyor?* (Eve date & half-day status)
  2. *Tatil kaç gün sürecek, hafta sonu ile birleşiyor mu?* (Total duration & bridge holiday calculation)
  3. *Kamu kurumları, bankalar ve kargolar açık mı?* (Public service & banking hours)

---

## 5. Complete 11-Language Translation Matrix (`mod/index/lang/*.php`)

To guarantee 100% parity across all 11 supported languages (`ar`, `ckb`, `de`, `en`, `fa`, `fr`, `ku`, `ru`, `sv`, `tr`, `zza`) with zero missing keys in `tools/i18n_guard.py`, the following keys will be added to `$lang['index']['mod_event']`:

| Key | `tr` (Turkish) | `ku` (Kurmanji) | `en` (English) |
| :--- | :--- | :--- | :--- |
| `no_category_events_recorded` | `Bu kategoride takvime kaydedilmiş bir etkinlik bulunmamaktadır` | `Di vê beşê de çalakiyeke ku li salnameyê hatiye tomarkirin tune ye` | `There are no events recorded in the calendar in this category` |
| `event_completed` | `Tamamlandı` | `Bi dawî bû` | `Completed` |
| `related_news` | `İlgili Haberler` | `Nûçeyên Têkildar` | `Related News` |
| `subcategories` | `Alt Kategoriler` | `Binekategorî` | `Subcategories` |
| `duration_days` | `Gün` | `Roj` | `Days` |
| `mon_short` .. `sun_short` | `Pzt, Sal, Çar, Per, Cum, Cmt, Paz` | `Duş, Sêş, Çar, Pên, În, Şem, Yek` | `Mon, Tue, Wed, Thu, Fri, Sat, Sun` |

*(Equivalent translations for `ar`, `ckb`, `de`, `fa`, `fr`, `ru`, `sv`, and `zza` will be populated in lockstep across `mod/index/lang/{ar,ckb,de,fa,fr,ru,sv,zza}.php`).*

---

## 6. Complete File Inventory for Planned Changes

### 6.1 New Twig Files to Create (`templates/amed/index/mod_event/sections/`)
1. `templates/amed/index/mod_event/sections/section_culture_art.twig` (*Kültür & Sanat*)
2. `templates/amed/index/mod_event/sections/section_literature_talk.twig` (*Edebiyat & Söyleşi*)
3. `templates/amed/index/mod_event/sections/section_cinema_documentary.twig` (*Sinema & Belgesel*)
4. `templates/amed/index/mod_event/sections/section_workshop_academy.twig` (*Atölye & Akademi*)
5. `templates/amed/index/mod_event/sections/section_exams.twig` (*Sınavlar*)
6. `templates/amed/index/mod_event/sections/section_official_holidays.twig` (*Resmi Tatil*)
7. `templates/amed/index/mod_event/sections/section_holidays.twig` (*Bayramlar*)
8. `templates/amed/index/mod_event/sections/section_default.twig` *(Fallback template for any additional categories)*

### 6.2 Existing Files to Update During Implementation
1. **Backend Helper:**
   - `mod/index/helper/mod_event.helper.php` (`category_sections` batch hydration, `_resolveCategoryTemplateKey()`, `##TR` container-aware hierarchy resolution, `rawurldecode` + `_404()` category guard, numeric pagination shift, `findItemBySeoOrIdStrict()` in `event()`, `duration_days` calculation, multi-day `calendar_json` overlap + `span_days` + category filter, related news lookup)
2. **Public Theme Templates (`templates/amed/`):**
   - `templates/amed/index/mod_event/events.twig` (unconditional category filter pills and loop/inclusion of per-category `sections/section_*.twig` templates with always-visible empty states)
   - `templates/amed/index/mod_event/event.twig` (`d.content|raw` fix, `d.has_deck` summary guard, countdown callout, Google Calendar / `.ics` export buttons, clickable venue map link, related news section)
   - `templates/amed/index/mod_event/calendar.twig` (category filter pills, localized weekday headers and `data-*` i18n attributes)
3. **Public Theme Static Assets (`templates/amed/static/`):**
   - `templates/amed/static/js/event-calendar.js` (multi-day `span_days` mapping, category filter fetch, localized month/empty-state reading from `data-*`, `.ics` export helper on detail page)
   - `templates/amed/static/css/events.css` & `templates/amed/static/css/events.min.css` (styles for per-category sections, `.event-section-empty-state`, exam/holiday horizontal strips, countdown banners, subcategory chips, calendar category filter pills, and related news cards)
4. **11-Language Packs (`mod/index/lang/`):**
   - `mod/index/lang/{ar,ckb,de,en,fa,fr,ku,ru,sv,tr,zza}.php` (11 files synchronized with `no_category_events_recorded`, `event_completed`, `related_news`, `subcategories`, `duration_days`, and `mon_short`..`sun_short`)

---

## 7. Verification & Quality Gates

Before completing any implementation pass of this plan, all repository guards must pass with zero errors:

```bash
# 1. PHP Syntax Validation
php -l mod/index/helper/mod_event.helper.php
for f in mod/index/lang/*.php; do php -l "$f"; done

# 2. Internationalization, 11-Language Parity, Forbidden Default & SQL Alias Guard
python3 tools/i18n_guard.py

# 3. Strict HTML/CSS/JS Code Separation Guard
python3 tools/code_separation_guard.py

# 4. Minified CSS Bundle Parity Guard
python3 tools/min_css_guard.py --build
python3 tools/min_css_guard.py

# 5. Domain & Module Boundary Guard
python3 tools/module_boundary_guard.py --all

# 6. Module Modernization Audit
python3 tools/audit_modernization.py
```
