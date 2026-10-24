# Homepage Hero Slider & Left Column Modernization Plan

## 1. Executive Summary & Architectural Objective
This document defines the concrete implementation plan for modernizing the **Homepage Hero Slider** (`templates/amida/index/hero_slider.twig`) and the **Left Editorial Column** (`#homepageHeroMain`, 8-column / `800 px` desktop surface) in `AF/Bulletin`.

Every specification in this plan is grounded in the active codebase (`mod/mod_news/index.php`, `mod/index/index.php`, `mod/index/helper/mod_hero_slider.helper.php`, `mod/index/helper/mod_news.helper.php`, `mod/index/helper/mod_publication.helper.php`, `templates/amida/index/index.twig`, and `templates/amida/static/css/hero-news-slider.css`) and matches the approved layout blueprint in `docs/homepage-layout-wireframe.html`.

---

## 2. Root-Cause Diagnosis of Current Slider & Layout Issues

### 2.1. Aspect Ratio Mismatch Between Upload Pipeline and Slider Viewport
1. **Admin Upload Pipeline (`mod/mod_news/index.php`, lines 30–48):**
   - Full image (`/`): `1280 × 720 px` (`option => 'cover'`, `quality => 80`) — exact **`16:9`** aspect ratio (`1.7778:1`).
   - Thumbnail (`/thumb`): `480 × 270 px` (`option => 'cover'`, `quality => 75`) — exact **`16:9`** aspect ratio (`1.7778:1`).
2. **MCP Upload Pipeline Drift (`lib/McpNewsTools.class.php`, lines 10–13):**
   - Currently defines `NEWS_IMAGE_SIZES` as `width => 1600, option => 'width'` and `/thumb` as `width => 600, option => 'width'`, which does not enforce the `16:9` crop when news articles are created via MCP tools.
3. **Current Frontend Container (`templates/amida/index/index.twig` & `templates/amida/static/css/hero-news-slider.css`):**
   - `index.twig` places `#homepageHeroMain` in `col-md-9` (~`960 px` wide) and `.hero-news-swiper` uses a fixed height of `390 px` (`960 × 390` = `2.46:1` aspect ratio).
   - Because `1280 × 720` (`16:9`) cannot fill a `2.46:1` box without cropping top/bottom, `.hero-slide__cover-img` was set to `object-fit: contain` over a blurred `.hero-slide__backdrop`, producing artificial side bars and visual clutter.
   - Furthermore, `.hero-news-swiper-pagination` in `hero-news-slider.css` (line 206) is forced to `display: none !important;`, hiding the bottom pagination indicators.

### 2.2. Mathematical Solution (`800 × 450 px` Native `16:9` Frame)
- Changing the homepage desktop grid split from `col-md-9` / `col-md-3` to **`col-lg-8` (`800 px`) / `col-lg-4` (`384 px`)** with a `16 px` column gap inside a `1200 px` container sets the left column width to **`800 px`**.
- At `800 px` width, an exact `16:9` container height is:
  $$800 \times \frac{9}{16} = 450\text{ px}$$
- A `1280 × 720 px` source image scales down by an exact factor of **`0.625`** (`1280 × 0.625 = 800`, `720 × 0.625 = 450`), allowing `object-fit: cover` with **zero cropping, zero distortion, and no blurred backdrop workaround**.

---

## 3. Left Column Structural Hierarchy (`#homepageHeroMain`)

In `templates/amida/index/index.twig`, the left 8-column container (`#homepageHeroMain`) will render five vertical blocks in strict editorial sequence:

1. **Block L1 — Hero News Slider (`800 × 450 px`, `16:9`):**
   - Template: `templates/amida/index/hero_slider.twig`
   - Data provider: `_hero_slider($t, 12)` (`mod/index/helper/mod_hero_slider.helper.php`)
2. **Block L2 — Horizontal Authors Strip (3 Columns):**
   - Template: `templates/amida/index/mod_publication/latest_authors.twig`
   - Data provider: `_latest_authors($t, 3)` (`mod/index/helper/mod_publication.helper.php` — updated default/call limit from `6` to `3`)
   - Relocated from the right sidebar (`#homepageHeroSidebar`) to sit directly below the Hero Slider as a 3-column horizontal strip (`grid-template-columns: repeat(3, minmax(0, 1fr))`).
3. **Block L3 — Latest News Grid (`Haberler`, 6 Cards in 3×2 Grid):**
   - Template: `templates/amida/index/mod_news/latest_news.twig`
   - Data provider: `_latest_news($t, 6, 'id,name,seo,summary,image,category_id,category_name,category_seo,created_at')`
   - Uses real `/static/mod_news/thumb/{image}` (`480 × 270 px`, `16:9`) rendered at `248 × 140 px` (`aspect-ratio: 16 / 9`), category pill, `created_at|time_ago`, title, and 2-line clamped summary.
   - Removes the bottom `.latest-news-footer` ("Devamını Oku" / `read_more` link and arrow icon) from each card.
4. **Block L4 — Sports News Section (`Spor`, 2 Horizontal Cards):**
   - Template: `templates/amida/index/mod_news/sports_news.twig` (new partial included in `index.twig`)
   - Data provider: `_category_news($t, ['Spor'], 2, 'id,name,seo,summary,image,category_id,category_name,category_seo,created_at')`
   - Queries real category `id: 30` (`name: 'Spor'`, `seo: 'Spor'` in `tr`; mapped by localized category slug/ID).
   - Renders 2 horizontal split cards (`180 × 101 px` `16:9` thumbnail on the left, category badge + headline + 120-character summary on the right).
5. **Block L5 — Video Gallery (`Videolar`, 3 Cards in 3×1 Grid):**
   - Template: `templates/amida/index/mod_video_embed/latest_videos.twig`
   - Data provider: `_latest_videos($t, 3, 'id,name,seo,video_id,embed_url,image,category_name,category_seo')` (updated limit from `6` to `3` on the homepage so it forms a clean single-row 3-card block at the bottom of the left column).

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

### 4.2. Controller & Helper Updates (`mod/index/index.php` & `mod/index/helper/mod_news.helper.php`)
1. **`mod/index/helper/mod_news.helper.php`:**
   - Add `public static function _category_news($category_seos, $limit = 3, $select = '...')` to `mod_news_helper`.
   - Must enforce the exact same security and visibility filters as `_latest_news()`:
     - `status = 'y'`
     - `lang = $lang`
     - `slider = 'n'` (prevents duplicating active slider stories)
     - Allowed category filter (`self::getAllowedCategoryIds($lang, $user_id)`)
     - Item-level ACL check (`mod_acl::check_Read_Permission('mod_news', $val['id'], $user_id)`)
   - Formats `summary_short` to 120 characters via `mb_substr(trim(strip_tags((string) $val['summary'])), 0, 120)`.
2. **`mod/index/index.php`:**
   - Update `_index()` call list:
     - `self::_latest_authors($this->template, 3);` (changed from `6` to `3`)
     - `self::_latest_news($this->template, 6, 'id,name,seo,summary,image,category_id,category_name,category_seo,created_at');`
     - `self::_sports_news($this->template, 2);` (assigns `sports_news` and `sports_category_url` to Twig)
     - `self::_latest_videos($this->template, 3, 'id,name,seo,video_id,embed_url,image,category_name,category_seo');` (changed from `6` to `3`)

### 4.3. Hero Slider Markup & CSS (`templates/amida/index/hero_slider.twig` & `templates/amida/static/css/hero-news-slider.css`)
1. **`templates/amida/index/hero_slider.twig`:**
   - Set explicit `width="1280" height="720"` attributes on `.hero-slide__cover-img` to eliminate Cumulative Layout Shift (CLS).
   - Remove the redundant `.hero-slide__backdrop` `<img>` element (which downloaded and painted every slide image twice for the blur effect).
   - Keep `.hero-slide__badge` (top-left category pill), `.hero-slide__overlay` (bottom dark gradient), `.hero-slide__title` (`clamp(1.25rem, 2.2vw, 1.65rem)`), and `.hero-slide__deck` (2-line clamp).
   - Keep `.hero-news-swiper-prev`, `.hero-news-swiper-next`, and `.hero-news-swiper-pagination`.
2. **`templates/amida/static/css/hero-news-slider.css`:**
   - Replace fixed `height: 390px` on `.hero-news-swiper` with:
     ```css
     .hero-news-swiper {
         width: 100%;
         aspect-ratio: 16 / 9;
         max-height: 450px;
     }
     ```
   - Change `.hero-slide__cover-img` from `object-fit: contain` to `object-fit: cover; object-position: center;`.
   - Enable `.hero-news-swiper-pagination` (`display: flex` at bottom-right of the slider frame with active pill indicator `width: 20px; border-radius: 999px; background: #e11d48;`).

### 4.4. Horizontal 3-Item Authors Strip (`templates/amida/index/mod_publication/latest_authors.twig` & `templates/amida/static/css/latest-authors.css`)
- Move `{% include "index/mod_publication/latest_authors.twig" %}` inside `#homepageHeroMain` directly below `hero_slider.twig`.
- Update `.latest-authors-list` in `templates/amida/static/css/latest-authors.css`:
  - Desktop (`>= 768px`): `display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0.75rem;`
  - Mobile (`< 768px`): `grid-template-columns: 1fr;`
  - Avatar `.latest-authors-avatar`: `48 × 48 px` rounded square (`border-radius: 50%` or `10px`), author name (`.latest-authors-item-author`), and 2-line article title (`.latest-authors-item-publication`).

### 4.5. Latest News Card Cleanup (`templates/amida/index/mod_news/latest_news.twig` & `templates/amida/static/css/latest-news.css`)
- **File:** `templates/amida/index/mod_news/latest_news.twig` (lines 70–75)
- **Change:** Remove the `<div class="latest-news-footer">...</div>` block containing `{{ lang.index.read_more }}` ("Devamını Oku") and the right-arrow SVG.
- **File:** `templates/amida/static/css/latest-news.css`
- **Change:** Ensure `.latest-news-media` uses `aspect-ratio: 16 / 9;` (matching the `480 × 270 px` `/static/mod_news/thumb/` image) instead of `16 / 10`.

### 4.6. New Sports Section (`templates/amida/index/mod_news/sports_news.twig`)
- Create `templates/amida/index/mod_news/sports_news.twig` and include it in `templates/amida/index/index.twig` between `latest_news.twig` and `latest_videos.twig`.
- Renders a section header (`Spor` with green accent bar `#16a34a` and "Tümünü Gör" link to `/{{ lang.lang }}/news/category/Spor`) and a 2-column grid (`.sports-news-grid`) where each card displays:
  - Left thumbnail (`180 × 101 px`, `aspect-ratio: 16 / 9`, source `/static/mod_news/thumb/{{ item.image }}` with `width="480" height="270"`).
  - Right body: category badge + relative timestamp (`item.created_at|time_ago`), bold 2-line headline, and 120-character summary.

---

## 5. Localization Keys (`mod/index/lang/*.php`)
Add the following descriptive English keys to `mod/index/lang/en.php` and their translations in `tr.php`, `ku.php`, and `de.php`:
- `$lang['index']['mod_news']['sports_news'] = ['title' => 'Sports', 'all_items' => 'View All'];`
  - `tr.php`: `'title' => 'Spor', 'all_items' => 'Tümünü Gör'`
  - `ku.php`: `'title' => 'Werzîş', 'all_items' => 'Hemûyan Bibîne'`
  - `de.php`: `'title' => 'Sport', 'all_items' => 'Alle anzeigen'`

---

## 6. Verification & Quality Gates
1. **PHP Syntax Validation:**
   ```bash
   php -l lib/McpNewsTools.class.php
   php -l mod/index/index.php
   php -l mod/index/helper/mod_news.helper.php
   php -l mod/index/lang/en.php mod/index/lang/tr.php mod/index/lang/ku.php mod/index/lang/de.php
   ```
2. **Strict Code Separation Guard (Rule 2):**
   ```bash
   python3 tools/code_separation_guard.py
   ```
   Must report `0 violations` (zero inline `style="..."`, zero `<style>`, zero inline `<script>`).
3. **Localization & Boundary Guards:**
   ```bash
   python3 tools/i18n_guard.py
   python3 tools/module_boundary_guard.py
   ```
