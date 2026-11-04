# Timestamp Display Standardization Plan for `mod_news` and `mod_publication`

## 1. Objective

Standardize all public timestamp displays across `mod_news` and `mod_publication` so that:
1. **Listing, Card, Stream, Slider, Search, and Archive Views:**
   - **Within the last 24 hours (`< 86400` seconds):** Display relative time (`... hours ago` / `... minutes ago` / `Just now` via localized `time_ago` dictionary keys, e.g., `3 saat önce`, `25 dakika önce`, `Az önce`).
   - **Older than 24 hours (`>= 86400` seconds):** Display explicit date and clock time formatted with the localized time label (e.g., `04.11.2026 Saat: 03:35` in Turkish, `04.11.2026 Dem: 03:35` in Kurdish, `04.11.2026 Time: 03:35` in English, following `<dd.mm.YYYY> <lang.index.time_label>: <HH:MM>`).
2. **Article / Publication Detail Views (`news.twig` & `publication.twig`):**
   - Always display the full publish date and clock time (`dd.mm.YYYY` + localized time label + `HH:MM`) inside the article/publication content header regardless of whether the content is under 24 hours old, so readers clicking into an item always see the exact day and clock time.
   - Apply the same full date and clock time format to `updated_at` when an editorial update timestamp is present and differs from `created_at`.

---

## 2. Root Cause & Gap Analysis

1. **Unconditional Date-Only Formatting in Listing Templates:**
   - [`newses.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/mod_news/newses.twig#L165) renders `{{ news.created_at|date('d.m.Y') }}` unconditionally, ignoring `news.published_ago` and omitting the clock time. This causes newly published articles (< 24h) on `/newses` and `/newses/{category}` to look outdated with only a calendar date (`05.11.2026` or `04.11.2026`).
   - [`publications.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/mod_publication/publications.twig#L218) renders `{{ publication.created_at|date('d.m.Y') }}` unconditionally.
   - [`sports_news.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/mod_news/sports_news.twig#L53), [`category_news_row.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/mod_news/category_news_row.twig#L61), [`mod_news/archive.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/mod_news/archive.twig#L123), and [`mod_publication/archive.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/mod_publication/archive.twig#L122) also hardcode `|date('d.m.Y')`.
2. **Unbounded `Helper::time_ago()` in Home, Slider & Search Blocks:**
   - [`latest_news.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/mod_news/latest_news.twig#L78), [`featured_news.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/mod_news/featured_news.twig#L78), [`economy_news.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/mod_news/economy_news.twig#L59), and [`hero_slider.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/hero_slider.twig#L79) check `if item.published_ago is not empty`. Because [`mod_news_helper::_cleanNewsRow()`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/mod/index/helper/mod_news.helper.php#L38) always populates `published_ago` via `Helper::time_ago()`, items older than 24 hours still render relative strings (`3 days ago`, `2 months ago`) instead of switching to the explicit date + time format.
   - [`search.helper.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/mod/index/helper/search.helper.php#L337) sets `latest_publication['timeago']` unconditionally via `Helper::time_ago()`, and its `mod_news` (line 96) and `mod_publication` (line 113) search queries omit `created_at` from `SELECT` and cache stale unformatted rows.
3. **Missing Timestamp Renders on Specific Cards:**
   - [`world_news.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/mod_news/world_news.twig#L24-L53) and [`latest_publications.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/mod_publication/latest_publications.twig#L41-L44) receive normalized timestamp data from PHP (`_world_news()` and `_latest_authors()`) but currently omit the timestamp element in Twig.
   - [`news.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/mod_news/news.twig#L443-L449) related articles grid (`detail-related-card`) and [`search.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/search.twig#L200-L245) news/publication result rows do not render item timestamps.
4. **Missing Data Normalization in `mod_publication_helper`:**
   - Unlike `mod_news_helper`, [`mod_publication_helper`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/mod/index/helper/mod_publication.helper.php) does not normalize rows with `is_last_24h` / `published_ago` / `formatted_datetime` across `publication()`, `publications()`, `_latest_publications()`, `_latest_authors()`, `authors()`, `author()`, and `archive()`.
   - In `mod_publication_helper::_latest_publications()` (line 175), the default `$select` string is `'seo,name,image'` (missing `created_at`). It must default to `'seo,name,image,created_at'` so any caller of `_latest_publications()` receives timestamp data.
5. **Missing Clock Time on Detail Views:**
   - [`news.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/mod_news/news.twig#L261) and [`publication.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/mod_publication/publication.twig#L227) only output `|date('d.m.Y')` without the localized time label and `HH:MM` clock time.

---

## 3. Architecture & Guard Compliance Specification

### 3.1. Shared-Core Boundary Protection (`indexBase.php` vs `lib/`)
Per repository synchronization rules (`AGENTS.md`), `lib/` is canonical shared-core with `AF/Business`, whereas `mod/index/indexBase.php` is the base class for all public helper controllers in `AF/Bulletin` (`mod_news_helper`, `mod_publication_helper`, `mod_hero_slider_helper`, `search_helper`) and already loads `$this->lang = Load::lang('index')`.
- Placing `enrichTimestampFields(array $row): array` and `enrichTimestampRows(array $rows): array` directly inside [`mod/index/indexBase.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/mod/index/indexBase.php) avoids introducing shared-core drift in `lib/Traits/PublicHelperTrait.php` while making the method automatically available to every `mod/index/helper/*.php` controller.

### 3.2. Display Rules Matrix

| Surface | Condition: `< 24 Hours` (`0 <= diff < 86400s`) | Condition: `>= 24 Hours` (`diff >= 86400s`) | Icon |
| :--- | :--- | :--- | :--- |
| **Listing / Cards / Streams / Archives / Sliders / Search** (`newses.twig`, `publications.twig`, `latest_news.twig`, `featured_news.twig`, `economy_news.twig`, `sports_news.twig`, `world_news.twig`, `category_news_row.twig`, `latest_publications.twig`, `authors.twig`, `author.twig`, `mod_news/archive.twig`, `mod_publication/archive.twig`, `hero_slider.twig`, `search.twig`) | Relative time via `Helper::time_ago()` (e.g., `3 saat önce`, `25 dakika önce`, `Az önce`) | Full date + localized time label + `HH:MM` (e.g., `04.11.2026 Saat: 03:35`) | `bi-clock-history` (`< 24h`) / `bi-calendar3` (`>= 24h`) |
| **Detail Page Header Byline** (`news.twig`, `publication.twig`) | Full date + localized time label + `HH:MM` (e.g., `04.11.2026 Saat: 03:35`) | Full date + localized time label + `HH:MM` (e.g., `04.11.2026 Saat: 03:35`) | `bi-calendar3` (publish) / `bi-pencil-square` (update) |
| **Detail Page 24h Sidebar** (`news.twig` `#news-24h`) | Relative time (`... saat önce`) | Filtered to last 24h in SQL (`created_at >= now - 86400`) | `bi-clock` |

### 3.3. Timestamp Calculation & Edge-Case Rules
Given `$row['created_at']`:
1. Parse `$ts = strtotime((string) $row['created_at'])`.
2. Compute `$diff = time() - $ts`. Clock-skew tolerance: if `$diff < 0` and `$diff >= -300` (up to 5 minutes of minor clock drift on newly created items), clamp `$diff = 0`.
3. `$isLast24h = ($ts !== false && $ts > 0 && $diff >= 0 && $diff < 86400);`
4. `$timeLabel = $this->lang['index']['time_label'] ?? $this->lang['index']['mod_event']['time'] ?? '';`
5. Fields populated on `$row`:
   - `$row['is_last_24h'] = $isLast24h;`
   - `$row['published_ago'] = $isLast24h ? Helper::time_ago($row['created_at'], $this->lang['index'] ?? null) : '';`
   - `$row['formatted_datetime'] = ($ts !== false && $ts > 0) ? (date('d.m.Y', $ts) . ($timeLabel !== '' ? ' ' . $timeLabel . ': ' : ' ') . date('H:i', $ts)) : '';`
   - `$row['display_time'] = $isLast24h ? $row['published_ago'] : $row['formatted_datetime'];`
   - For `updated_at`: if `!empty($row['updated_at']) && $row['updated_at'] !== $row['created_at']`, parse `$uts = strtotime((string) $row['updated_at'])` and set `$row['formatted_updated_datetime'] = ($uts !== false && $uts > 0) ? (date('d.m.Y', $uts) . ($timeLabel !== '' ? ' ' . $timeLabel . ': ' : ' ') . date('H:i', $uts)) : '';`.

### 3.4. Localization Key Parity (`i18n-guard` Compliance)
All 10 language dictionaries under `mod/index/lang/*.php` (`en`, `tr`, `ku`, `ckb`, `zza`, `de`, `fr`, `ar`, `ru`, `sv`) already define `$lang['index']['time_ago']` and `$lang['index']['mod_event']['time']`.
We will add `'time_label'` at `$lang['index']['time_label']` across all 10 language packs in lockstep:
- `en.php`: `'time_label' => 'Time'`
- `tr.php`: `'time_label' => 'Saat'`
- `ku.php`: `'time_label' => 'Dem'`
- `ckb.php`: `'time_label' => 'کات'`
- `zza.php`: `'time_label' => 'Wext'`
- `de.php`: `'time_label' => 'Uhrzeit'`
- `fr.php`: `'time_label' => 'Heure'`
- `ar.php`: `'time_label' => 'الوقت'`
- `ru.php`: `'time_label' => 'Время'`
- `sv.php`: `'time_label' => 'Tid'`

**Strict Guard Rules:**
- Never use `|default(...)` or `??` on `lang.*` expressions in Twig (`tools/i18n_guard.py` `FORBIDDEN_DEFAULT`).
- Never introduce inline `style="..."`, `<style>`, `onclick="..."`, or `<script>` in Twig or PHP (`tools/code_separation_guard.py`).
- All PHP variable names, methods, and comments must be strictly in English (`tools/i18n_guard.py`).

---

## 4. Implementation Steps

### Phase 1: Localization & Base Normalization (`mod/index/`)

1. **Update All 10 Language Packs (`mod/index/lang/*.php`):**
   - Add `'time_label'` to `$lang['index']` in `ar.php`, `ckb.php`, `de.php`, `en.php`, `fr.php`, `ku.php`, `ru.php`, `sv.php`, `tr.php`, and `zza.php`.
2. **Add Timestamp Normalization to [`mod/index/indexBase.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/mod/index/indexBase.php):**
   - Add protected `enrichTimestampFields(array $row): array` and `enrichTimestampRows(array $rows): array`.
   - Update `_get_category_news()` in `indexBase.php` (line 404) so its returned items also pass through `$this->enrichTimestampFields($item)`.
3. **Wire `mod_news_helper` ([`mod/index/helper/mod_news.helper.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/mod/index/helper/mod_news.helper.php)):**
   - Update `_cleanNewsRow(array $row)` to delegate timestamp calculation to `$this->enrichTimestampFields($row)`.
   - Verify all news queries (`news()`, `newses()`, `_latest_news()`, `_featured_news()`, `_economy_news()`, `_sports_news()`, `_world_news()`, `_category_news()`, `_homepage_categories()`, and `archive()`) pass through `_cleanNewsRow()` / `_cleanNewsRows()`.
4. **Wire `mod_publication_helper` ([`mod/index/helper/mod_publication.helper.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/mod/index/helper/mod_publication.helper.php)):**
   - Add `_cleanPublicationRow(array $row)` and `_cleanPublicationRows(array $rows)` using `$this->enrichTimestampFields($row)`.
   - Update `_latest_publications($limit = 5, $select = 'seo,name,image,created_at')` so `created_at` is always selected and rows are normalized with `_cleanPublicationRows()`.
   - Apply `_cleanPublicationRow()` / `_cleanPublicationRows()` across:
     - `publication($seo)` (`$pub_data`)
     - `publications($category, $page)`
     - `_latest_authors($limit)` (replace manual `Helper::time_ago` on line 245 with `$this->enrichTimestampFields($row)`)
     - `authors($page, $page_limit)`
     - `author($username, $page)` (normalize both `$publications` and `$authorNews`)
     - `archive()` (normalize `$publication->toArray()` on line 673 before `ctx_set('publications', ...)`)
5. **Wire `mod_hero_slider_helper` & `search_helper`:**
   - In [`mod_hero_slider.helper.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/mod/index/helper/mod_hero_slider.helper.php#L323), apply `$this->enrichTimestampFields($item)` so slider items use relative time when `< 24h` and `formatted_datetime` when `>= 24h`.
   - In [`search.helper.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/mod/index/helper/search.helper.php):
     - Add `created_at` to `SELECT` in `mod_news` (`select('summary,name,seo,created_at')`, line 96) and `mod_publication` (`select('summary,name,seo,created_at')`, line 113).
     - Apply `$this->enrichTimestampRows(...)` to both cached and fresh `news` and `publications` search results so cached entries never serve stale relative times.
     - In `searchAuthors()` (line 336), enrich `latest_publication` with `$this->enrichTimestampFields(...)` and set `latest_publication['display_time']` (`published_ago` if `< 24h`, `formatted_datetime` if `>= 24h`).

### Phase 2: Public Twig Templates (`templates/amed/index/`)

1. **News Listing & Homepage Blocks (`templates/amed/index/mod_news/`):**
   - [`newses.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/mod_news/newses.twig#L162-L167): Replace static `{{ news.created_at|date('d.m.Y') }}` with conditional `< 24h` (`news.is_last_24h` -> `bi-clock-history` + `news.published_ago`) vs `>= 24h` (`bi-calendar3` + `news.formatted_datetime`).
   - [`latest_news.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/mod_news/latest_news.twig#L75-L87): Render `news.is_last_24h ? news.published_ago : news.formatted_datetime`.
   - [`featured_news.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/mod_news/featured_news.twig#L75-L87): Render `news.is_last_24h ? news.published_ago : news.formatted_datetime`.
   - [`economy_news.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/mod_news/economy_news.twig#L56-L68): Update both lead card (lines 56-68) and sub-list cards (lines 116-126) to render `item.is_last_24h ? item.published_ago : item.formatted_datetime`.
   - [`sports_news.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/mod_news/sports_news.twig#L50-L56): Replace hardcoded `item.created_at|date('d.m.Y')` with `item.is_last_24h ? item.published_ago : item.formatted_datetime`.
   - [`category_news_row.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/mod_news/category_news_row.twig#L59-L74): Consolidate duplicate date + `published_ago` spans into a single timestamp element that displays `news.published_ago` if `news.is_last_24h`, or `news.formatted_datetime` if `>= 24h`.
   - [`archive.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/mod_news/archive.twig#L121-L124): Display `news.is_last_24h ? news.published_ago : news.formatted_datetime`.
2. **News Detail View (`templates/amed/index/mod_news/news.twig`):**
   - [`news.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/mod_news/news.twig#L258-L269): Update publish date and updated date in the article header byline to display full date and clock time (`{{ d.formatted_datetime }}` and `{{ d.formatted_updated_datetime }}`) with ISO-8601 `datetime="{{ d.created_at|date('c') }}"` attributes.
   - Update the related articles section (`newses` loop at lines 433-451) if populated to also show `news.display_time`.
3. **Publication Listing, Author, and Archive Views (`templates/amed/index/mod_publication/`):**
   - [`publications.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/mod_publication/publications.twig#L215-L220): Replace `publication.created_at|date('d.m.Y')` with `publication.is_last_24h ? publication.published_ago : publication.formatted_datetime`.
   - [`authors.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/mod_publication/authors.twig#L121-L124): Replace `author.created_at|date('d.m.Y H:i')` with `author.is_last_24h ? author.published_ago : author.formatted_datetime`.
   - [`author.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/mod_publication/author.twig#L155-L247): Update all 4 stream blocks (articles tab, news tab, news-only view, articles-only view) to show `published_ago` when `is_last_24h` is true and `formatted_datetime` when `>= 24h`.
   - [`archive.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/mod_publication/archive.twig#L120-L123): Display `publication.is_last_24h ? publication.published_ago : publication.formatted_datetime`.
4. **Publication Detail View (`templates/amed/index/mod_publication/publication.twig`):**
   - [`publication.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/mod_publication/publication.twig#L225-L233): Update publish date and updated date in the detail header bar to render `{{ d.formatted_datetime }}` and `{{ d.formatted_updated_datetime }}` with `datetime="{{ d.created_at|date('c') }}"`.
5. **Hero Slider & Search Views (`templates/amed/index/`):**
   - [`hero_slider.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/hero_slider.twig#L76-L81): Render `item.display_time` (`item.published_ago` when `< 24h`, `item.formatted_datetime` when `>= 24h`).
   - [`search.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/search.twig#L128-L130): Render `author.latest_publication.display_time` in author cards, and display `item.display_time` on `news` (lines 200-215) and `publications` (lines 230-245) search result rows.

---

## 5. Verification & Quality Gates

1. **PHP Syntax Validation:**
   - Run `php -l` on `mod/index/indexBase.php`, `mod/index/helper/mod_news.helper.php`, `mod/index/helper/mod_publication.helper.php`, `mod/index/helper/mod_hero_slider.helper.php`, `mod/index/helper/search.helper.php`, and all 10 `mod/index/lang/*.php` files.
2. **Automated Repository Guards:**
   - `python3 tools/code_separation_guard.py` (verify zero inline CSS or JS in modified Twig/PHP files).
   - `python3 tools/i18n_guard.py` (verify zero hardcoded strings, zero `|default(...)` fallbacks on `lang.*` keys, and English-only identifiers/comments).
   - `python3 tools/module_boundary_guard.py --all`.
3. **Functional Smoke Verification:**
   - Verify items with `created_at` within the last 24 hours render `... saat önce` / `... dakika önce` on `/`, `/newses`, `/publications`, `/authors`, `/author/{username}`, and `/search`.
   - Verify items with `created_at` older than 24 hours render `dd.mm.YYYY Saat: HH:MM` (e.g., `04.11.2026 Saat: 03:35`).
   - Verify clicking into `/news/{seo}` or `/publication/{seo}` always displays the full date and clock time (`dd.mm.YYYY Saat: HH:MM`) in the article/publication header.
