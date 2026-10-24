# Homepage Hero Slider & Left Column Modernization Plan

## 1. Executive Summary & Architectural Objective
This document defines the concrete, repository-grounded implementation plan for modernizing the **Homepage Hero Slider** (`templates/amida/index/hero_slider.twig`) and the **Left Editorial Column** (`#homepageHeroMain`, 8-column / `800 px` desktop surface) in `AF/Bulletin` (`v1.0.4`).

Every specification in this plan is verified against the active codebase (`config/url.config.php`, `mod/mod_news/index.php`, `lib/McpNewsTools.class.php`, `mod/index/index.php`, `mod/index/indexBase.php`, `mod/index/helper/mod_hero_slider.helper.php`, `mod/index/helper/mod_news.helper.php`, `mod/index/helper/mod_publication.helper.php`, `templates/amida/index/index.twig`, `templates/amida/index/hero_slider.twig`, `templates/amida/static/js/hero-news-slider.js`, and `templates/amida/static/css/hero-news-slider.css`) and matches the approved layout blueprint in `docs/plan/homepage-layout-wireframe.html`.

---

## 2. Root-Cause Diagnosis of Current Slider & Layout Issues

### 2.1. Aspect Ratio Mismatch Between Upload Pipeline and Slider Viewport
1. **Admin Upload Pipeline (`mod/mod_news/index.php`):**
   - Full image (`/`): `1280 × 720 px` (`option => 'cover'`, `quality => 80`) — exact **`16:9`** aspect ratio (`1.7778:1`).
   - Thumbnail (`/thumb`): `480 × 270 px` (`option => 'cover'`, `quality => 75`) — exact **`16:9`** aspect ratio (`1.7778:1`).
2. **MCP Upload Pipeline Drift (`lib/McpNewsTools.class.php`, lines 10–13):**
   - Currently defines `NEWS_IMAGE_SIZES` as `width => 1600, height => null, option => 'width'` and `/thumb` as `width => 600, height => null, option => 'width'`, which fails to enforce the `16:9` (`1280 × 720` / `480 × 270`) crop when news articles are created or updated via MCP tools.
3. **Current Frontend Container (`templates/amida/index/index.twig` & `templates/amida/static/css/hero-news-slider.css`):**
   - `index.twig` places `#homepageHeroMain` in `col-md-9` (~`900–960 px` wide) and `.hero-news-swiper` uses a fixed height of `390 px` (`~2.46:1` aspect ratio).
   - Because `1280 × 720` (`16:9`) cannot fill a `2.46:1` box without cropping top/bottom, `.hero-slide__cover-img` (line 185 of `hero-news-slider.css`) is set to `object-fit: contain` over a blurred `.hero-slide__backdrop`, producing artificial side bars and downloading every slide image twice.
   - Furthermore, `templates/amida/index/index.twig` (line 19) loads `hero-news-slider.min.css` rather than `hero-news-slider.css`, and `.hero-news-swiper-pagination` is hidden with `display: none !important;`.
4. **Redundant Double `_hero_slider` Query & Missing ACL Filtering:**
   - `templates/amida/index/index.twig` calls `{{ run({"mod": "index", "method": "_hero_slider", "extra": [6]}) }}` twice (line 5 in `head_preload` and line 36 in `content`).
   - `mod_hero_slider_helper` (`mod/index/helper/mod_hero_slider.helper.php`, lines 50–141) does not enforce `getAllowedAclCondition('')` or `getAllowedCategoryIds(...)`.

### 2.2. Mathematical Solution (`800 × 450 px` Native `16:9` Frame)
- Changing the homepage desktop grid split in `templates/amida/index/index.twig` from `col-md-9` / `col-md-3` to **`col-lg-8` (`800 px`) / `col-lg-4` (`384 px`)** with a `16 px` column gap inside a `1200 px` container sets the left column width to **`800 px`**.
- At `800 px` width, an exact `16:9` container height is:
  $$800 \times \frac{9}{16} = 450\text{ px}$$
- A `1280 × 720 px` source image scales down by an exact factor of **`0.625`** (`1280 × 0.625 = 800`, `720 × 0.625 = 450`), allowing `object-fit: cover` with **zero cropping, zero distortion, and no blurred `.hero-slide__backdrop` duplicate download**.

---

## 3. Left Column Structural Hierarchy (`#homepageHeroMain`)

In `templates/amida/index/index.twig`, the homepage layout is consolidated into a single two-column broadsheet row (`#homepageHeroRow`: left `#homepageHeroMain` `col-lg-8` + right `#homepageHeroSidebar` `col-lg-4`). The left column (`#homepageHeroMain`) renders five vertical blocks in strict editorial sequence with a `16 px` vertical stack gap:

1. **Block L1 — Hero News Slider (`800 × 450 px`, `16:9`):**
   - **Template:** `templates/amida/index/hero_slider.twig`
   - **Data Provider:** `{{ run({"mod": "index", "method": "_hero_slider", "extra": [10]}) }}` -> `ctx_get('hero_slider')` (`mod/index/helper/mod_hero_slider.helper.php`)
2. **Block L2 — Horizontal Authors Strip (3 Columns):**
   - **Template:** `templates/amida/index/mod_publication/latest_authors.twig`
   - **Data Provider:** `{{ run({"mod": "index", "method": "_latest_authors", "extra": [3]}) }}` -> `ctx_get('latest_authors')` (`mod/index/helper/mod_publication.helper.php`)
   - Relocated from `#homepageHeroSidebar` to sit directly below the Hero Slider as a 3-column horizontal strip (`grid-template-columns: repeat(3, minmax(0, 1fr))`).
3. **Block L3 — Latest News Grid (`Haberler`, 6 Cards in `3 × 2` Grid):**
   - **Template:** `templates/amida/index/mod_news/latest_news.twig`
   - **Data Provider:** `{{ run({"mod": "index", "method": "_latest_news", "extra": [6, "id,image,name,seo,keywords,summary,created_at,video,gallery_id,file_id,category_name,category_seo"]}) }}` -> `ctx_get('latest_news')`
   - Uses `/static/mod_news/thumb/{image}` (`480 × 270 px`, `16:9`) rendered at `248 × 140 px` (`aspect-ratio: 16 / 9`), time-ago badge (`news.published_ago`), media type badges (`has_video`, `has_gallery`), date + reading time meta, headline, and 180-character summary.
   - Removes the bottom `.latest-news-footer` (`{{ lang.index.read_more }}`) link block from each card.
4. **Block L4 — Sports News Section (`Spor`, 2 Horizontal Cards in `2 × 1` Grid):**
   - **Template:** `templates/amida/index/mod_news/sports_news.twig`
   - **Data Provider:** `{{ run({"mod": "index", "method": "_sports_news", "extra": [2]}) }}` -> `ctx_get('sports_news')` and `ctx_get('sports_category_url')`
   - Renders 2 horizontal split cards (`160 × 90 px` `16:9` thumbnail on the left from `/static/mod_news/thumb/{{ item.image }}`, headline + 120-character summary on the right).
5. **Block L5 — Video Gallery (`Videolar`, 3 Cards in `3 × 1` Grid):**
   - **Template:** `templates/amida/index/mod_video_embed/latest_videos.twig`
   - **Data Provider:** `{{ run({"mod": "index", "method": "_latest_videos", "extra": [3, "id,image,name,content,keywords,duration,created_at"]}) }}` -> `ctx_get('latest_videos')`

---

## 4. File-by-File Implementation Specifications

### 4.1. Align MCP Image Upload Dimensions (`lib/McpNewsTools.class.php`)
Ensure news images uploaded via MCP match the admin controller (`mod/mod_news/index.php`) `16:9` contract:
- **File:** `lib/McpNewsTools.class.php` (lines 10–13)
- **Change:**
  ```php
  private const NEWS_IMAGE_SIZES = [
      '/' => ['width' => 1280, 'height' => 720, 'option' => 'cover'],
      '/thumb' => ['width' => 480, 'height' => 270, 'option' => 'cover'],
  ];
  ```

### 4.2. Hero Slider ACL Hardening (`mod/index/helper/mod_hero_slider.helper.php`)
- **File:** `mod/index/helper/mod_hero_slider.helper.php`
- **Changes:**
  1. In `loadNewsItems()` (lines 50–95):
     - Enforce `$this->isFeatureReady('news.front')`.
     - Apply `$allowedCatIds = $this->getAllowedCategoryIds('mod_news_category', $currentLang)` and `$aclCondition = $this->getAllowedAclCondition('')` so restricted news items never leak into the public hero slider.
  2. In `loadPublicationItems()` (lines 97–141):
     - Enforce `$this->isFeatureReady('publication.front')`.
     - Apply `$allowedCatIds = $this->getAllowedCategoryIds('mod_publication_category', $currentLang)` and `$aclCondition = $this->getAllowedAclCondition('')`.

### 4.3. Category News Helper & Controller Wiring (`mod/index/helper/mod_news.helper.php` & `mod/index/index.php`)
1. **`mod/index/helper/mod_news.helper.php`:**
   - Update `_latest_news($limit, $select)` (line 350) to query `Load::model('mod_news.newses')` (the SQL view joining `mod_news_category`) so `category_name` and `category_seo` are available when requested in `$select`.
   - Add instance method `public function _category_news(array $categoryCandidates, int $limit = 3, string $select = 'id,image,name,seo,summary,created_at,video,gallery_id,file_id,deck,category_id,category_name,category_seo'): array`:
     - Checks `if (!$this->isFeatureReady('news.latest')) { return ['items' => [], 'category_name' => '', 'category_seo' => '']; }`.
     - Resolves the matching category in `mod_news_category` for `$lang = (string) $this->site['lang']` by matching `seo` or `name` against `$categoryCandidates` within `$allowedCatIds = $this->getAllowedCategoryIds('mod_news_category', $lang)`.
     - Includes child categories via `$this->getCategoryDescendantIds($matchedCategoryId, $lang, 'mod_news_category')`.
     - Queries `Load::model('mod_news.newses')` with `status = 'y'`, `lang = $lang`, `slider = 'n'`, `$this->getAllowedAclCondition('')`, and `category_id IN (...)`, ordered by `created_at DESC`, limited to `$limit`.
     - Passes rows through `$this->_cleanNewsRows()` and computes `summary_short` (truncated to 120 characters with `mb_substr(trim(strip_tags((string) ($row['summary'] ?? ''))), 0, 117) . '...'`).
2. **`mod/index/index.php`:**
   - Add widget method `_sports_news($t, $limit = 2)` to `index_mod`:
     ```php
     public function _sports_news($t, $limit = 2)
     {
         if (!$this->isFeatureReady('news.latest')) {
             $t->ctx_set('sports_news', []);
             $t->ctx_set('sports_category_seo', '');
             return;
         }

         $helper = Load::helper('index.mod_news');
         $result = $helper->_category_news(
             ['Spor', 'Werzîş', 'Werzis', 'Sports', 'Sport', 'رياضة', 'وەرزش'],
             (int) $limit
         );
         $t->ctx_set('sports_news', $result['items']);
         $t->ctx_set('sports_category_seo', $result['category_seo']);
         unset($helper);
     }
     ```
   - Note: All category archive links in Twig MUST use the canonical Bulletin route `{{ site.saddress }}newses/{{ sports_category_seo }}` (or `{{ site.saddress }}newses` if empty), matching `config/url.config.php` (`$url['newses/(:any)'] = 'index/newses/$1'`).

### 4.4. Hero Slider Markup & Styles (`templates/amida/index/hero_slider.twig` & `templates/amida/static/css/hero-news-slider.css`)
1. **`templates/amida/index/hero_slider.twig`:**
   - Remove the redundant `<div class="hero-slide__backdrop">...</div>` block (lines 30–39) so browsers only download a single image per slide.
   - Update `<img class="hero-slide__cover-img">` dimensions (lines 46–48) from `width="1200" height="675" sizes="(max-width: 768px) 100vw, 900px"` to **`width="1280" height="720" sizes="(max-width: 991px) 100vw, 800px"`**.
   - Preserve existing navigation button classes `.hero-news-swiper-button-prev`, `.hero-news-swiper-button-next`, and `.hero-news-swiper-pagination` so `templates/amida/static/js/hero-news-slider.js` (lines 66–70) works without modification.
2. **`templates/amida/static/css/hero-news-slider.css` & `hero-news-slider.min.css`:**
   - Update `templates/amida/index/index.twig` (line 19) to load `hero-news-slider.css` (and synchronize `hero-news-slider.min.css`).
   - Replace fixed `height: 390px` on `.hero-news-swiper` with:
     ```css
     .hero-news-swiper {
         width: 100%;
         aspect-ratio: 16 / 9;
         max-height: 450px;
         height: auto;
     }
     ```
   - Change `.hero-slide__image .hero-slide__cover-img` (line 185) from `object-fit: contain` to `object-fit: cover; object-position: center; filter: none;`.
   - Enable `.hero-news-swiper-pagination` at the bottom center/right of the slider frame (`display: flex !important; gap: 6px; z-index: 6;`).

### 4.5. Horizontal 3-Item Authors Strip (`templates/amida/index/mod_publication/latest_authors.twig` & `templates/amida/static/css/latest-authors.css`)
1. **`templates/amida/index/mod_publication/latest_authors.twig`:**
   - Change line 1 from `{{ run({"mod": "index", "method": "_latest_authors", "extra": [6]}) }}` to accept an optional `limit` variable:
     ```twig
     {% set author_limit = limit|default(3) %}
     {{ run({"mod": "index", "method": "_latest_authors", "extra": [author_limit]}) }}
     ```
2. **`templates/amida/static/css/latest-authors.css`:**
   - Remove the desktop sidebar left border (`padding-left: 1.5rem; border-left: 1px solid var(--authors-line);` at lines 172–177).
   - Convert `#latest-publication-authors .latest-authors-list` (lines 60–66) to a 3-column horizontal grid on tablet/desktop (`>= 768px`):
     ```css
     #latest-publication-authors .latest-authors-list {
         display: grid;
         grid-template-columns: repeat(3, minmax(0, 1fr));
         gap: 0.75rem;
     }
     ```
   - Style each `.latest-authors-item` as a compact bordered card (`padding: 0.75rem`, `border: 1px solid var(--authors-line)`, `border-radius: 8px`) with `.latest-authors-avatar` at `48 × 48 px`, `.latest-authors-name`, and 2-line clamped `.latest-authors-publication`.

### 4.6. Latest News Card Cleanup (`templates/amida/index/mod_news/latest_news.twig` & `templates/amida/static/css/latest-news.css`)
- **File:** `templates/amida/index/mod_news/latest_news.twig`
  - Remove the redundant `<link rel="preload" ... href=".../latest-news.css">` tags (lines 4–5), since `latest-news.css` is already loaded in `templates/amida/index/index.twig` (lines 24–25).
  - Update `<img>` `width="480" height="270"` (lines 38–39) to match the exact `480 × 270 px` dimensions of `/static/mod_news/thumb/`.
  - Remove `<div class="latest-news-footer">...</div>` (lines 104–111) containing `{{ lang.index.read_more }}`.
- **File:** `templates/amida/static/css/latest-news.css`
  - Ensure `.latest-news-media` uses `aspect-ratio: 16 / 9;`.

### 4.7. New Sports Section (`templates/amida/index/mod_news/sports_news.twig`)
- Create `templates/amida/index/mod_news/sports_news.twig` and include it in `templates/amida/index/index.twig` between `latest_news.twig` and `latest_videos.twig`.
- Calls `{{ run({"mod": "index", "method": "_sports_news", "extra": [2]}) }}` and reads `sports_news = ctx_get('sports_news')` and `sports_category_seo = ctx_get('sports_category_seo')`.
- Renders a section header (`{{ lang.index.mod_news.sports_news.title }}` with green accent bar `#16a34a` and `{{ lang.index.mod_news.sports_news.all_items }}` link to `{{ site.saddress }}newses/{{ sports_category_seo }}`) and a 2-column grid (`.sports-news-grid`) where each card displays:
  - Left thumbnail: `160 × 90 px` (`aspect-ratio: 16 / 9`, source `{{ site.iaddress }}mod_news/thumb/{{ item.image }}` with `width="480" height="270"`, matching `.spor-thumb` in `docs/plan/homepage-layout-wireframe.html`).
  - Right body: bold 2-line headline (`{{ site.saddress }}news/{{ item.seo }}`), relative timestamp (`item.published_ago`), and 120-character summary (`item.summary_short`).

---

## 5. Localization Keys (`mod/index/lang/*.php`)
To pass `python3 tools/i18n_guard.py` with zero missing-key errors, add `$lang['index']['mod_news']['sports_news']` across **all 9 dictionaries** in `mod/index/lang/{ar,ckb,de,en,fr,ku,sv,tr,zza}.php`:
- `en.php`: `'sports_news' => ['title' => 'Sports', 'all_items' => 'View All']`
- `tr.php`: `'sports_news' => ['title' => 'Spor', 'all_items' => 'Tümünü Gör']`
- `ku.php`: `'sports_news' => ['title' => 'Werzîş', 'all_items' => 'Hemûyan Bibîne']`
- `ckb.php`: `'sports_news' => ['title' => 'وەرزش', 'all_items' => 'بینینی هەمووی']`
- `zza.php`: `'sports_news' => ['title' => 'Spor', 'all_items' => 'Pêroyî Bivîne']`
- `de.php`: `'sports_news' => ['title' => 'Sport', 'all_items' => 'Alle anzeigen']`
- `fr.php`: `'sports_news' => ['title' => 'Sports', 'all_items' => 'Voir tout']`
- `ar.php`: `'sports_news' => ['title' => 'رياضة', 'all_items' => 'عرض الكل']`
- `sv.php`: `'sports_news' => ['title' => 'Sport', 'all_items' => 'Visa alla']`

---

## 6. Verification & Quality Gates
1. **PHP Syntax Validation:**
   ```bash
   php -l lib/McpNewsTools.class.php
   php -l mod/index/index.php
   php -l mod/index/helper/mod_news.helper.php
   php -l mod/index/helper/mod_hero_slider.helper.php
   for f in mod/index/lang/*.php; do php -l "$f"; done
   ```
2. **Strict Code Separation Guard (Rule 2):**
   ```bash
   python3 tools/code_separation_guard.py --path templates/amida/index/
   ```
   Must report `0 violations` (zero inline `style="..."`, zero `<style>`, zero inline `<script>`).
3. **Localization & Boundary Guards:**
   ```bash
   python3 tools/i18n_guard.py --path mod/index/
   python3 tools/module_boundary_guard.py
   ```
