# Handoff Report: Modernization Survey for R3 & R5

- **Role**: Codebase & Architecture Explorer (R3 & R5 Focus)
- **Working Directory**: `/home/malpersaz/Desktop/projects/htdocs/AF/Rojname/.agents/explorer_survey_2`
- **Target Codebase**: `/home/malpersaz/Desktop/projects/htdocs/AF/Rojname`
- **Reference Codebase**: `/home/malpersaz/Desktop/projects/htdocs/AF/Business`
- **Date**: 2026-08-23

---

## 1. Observation

Direct codebase scan and pattern comparison between `AF/Rojname` (target) and `AF/Business` (reference) revealed the following exact facts:

### A. R3: Universal Social Share & Document Export Toolbar

1. **Existing Share Component (`templates/default/partials/share.twig`)**:
   - Contains inline CSS (`<style>` block on lines 2–59), violating AGENTS.md Rule 2 (Strict Code Separation Mandate).
   - Contains inline JS (`<script>` block on lines 95–117), violating AGENTS.md Rule 2.
   - Contains inline event handler on line 88: `onclick="navigator.clipboard.writeText(...)"`, violating AGENTS.md Rule 2.
   - Hardcodes `d.name` and fallback `article` in share URLs (`site.saddress ~ (share_url_path|default('article')) ~ '/' ~ d.seo`).
   - Completely lacks TikTok share intent support.

2. **Missing Share Components & Assets**:
   - `templates/default/partials/share_toolbar.twig`: **DOES NOT EXIST** in AF/Rojname. (Present in AF/Business, lines 1–101).
   - `templates/default/static/css/share.css`: **DOES NOT EXIST** in AF/Rojname. (Present in AF/Business, lines 1–199).
   - `templates/default/static/js/share.js`: **DOES NOT EXIST** in AF/Rojname. (Present in AF/Business, lines 1–104).

3. **Broken/Missing Partial Includes in Existing Detail Views**:
   - `templates/default/index/mod_announcements/announcement.twig:25`: `<div class="col-md-6 col-md-6 col-12">{{ include("index/paylas.twig") }}</div>`. The file `templates/default/index/paylas.twig` **does not exist**, triggering runtime template exceptions if rendered.
   - `templates/default/index/mod_imagecategories/gallery.twig:65`: `{{ include("index/paylas.twig") }}` refers to the same non-existent Turkish partial.

4. **Action Toolbars & Print Actions in Detail Views**:
   - `templates/default/index/mod_news/news.twig:234-249`: Legacy `.btn-group` with inline `onclick="window.print();"`, legacy language keys (`lang.index.mod_gallery[1]`, `[2]`, `[3]`, `[4]`).
   - `templates/default/index/mod_publication/publication.twig:191-206`: Legacy `.btn-group` with inline `onclick="window.print();"`. Missing `saveHtml` and `saveAll` ZIP actions.
   - `templates/default/index/mod_content/content.twig:197-212`: Legacy `.btn-group` with inline `onclick="window.print();"`.
   - `templates/default/index/mod_page/page.twig:67-91`: Legacy `.akm_tools` with inline `onclick="$('#akm_icerik').printThis({ pageTitle: '{{ d.name }}' });"`.
   - `templates/default/index/mod_announcements/announcement.twig:27-52`: Legacy `.akm_tools` with inline `onclick="$('#akm_icerik').printThis(...)"`.

5. **Backend Document Export Endpoints (`mod/tools/index.php`)**:
   - Already provides `saveHtml($mod_name, $vid)`, `saveText($mod_name, $vid)`, `saveAll($mod_name, $vid)` (ZIP creation), `saveFile($mod_name, $vid, $id)`, `videoResim()`, `videoFlv()`, `saveVideo()`.
   - Uses non-English/Turkish method names on lines 34, 88, 154, 223, 326: `Run::import('index', '_permission_kontrol', [$content->acl_id])` instead of `_check_permission`.
   - In AF/Business `mod/tools/index.php`, lines 59 and 123 contain a minor variable mismatch (`$output` defined but `echo strip_tags($sonuc)` called). In AF/Rojname, `$output = ...; echo strip_tags($output);` must be used cleanly.

6. **Language Dictionary Keys (`mod/index/lang/*.php`)**:
   - AF/Rojname `mod/index/lang/en.php` (and other 8 language packs) are missing dedicated action keys: `download_html`, `download_text`, `download_all`, `print`, whereas AF/Business contains complete translations across all 9 languages.

---

### B. R5: Brand Placeholder Fallback & Disk Asset Verification

1. **Brand Placeholder Fallback Hierarchy**:
   - Required hierarchy: `Logo -> Symbol -> Radial Gradient Pattern`.
   - In AF/Rojname, detail templates (`news.twig:277`, `publication.twig:241`, `content.twig:240`) have hardcoded fallback `<img src="{{ site.iaddress }}mod_settings/logo.png" ...>`:
     * Does not check if `site.logo_path`, `site.logo_symbol`, or `site.site_icon` are configured.
     * Does not provide the secondary symbol fallback or the tertiary geometric dark radial gradient pattern.
     * Hardcoded `logo.png` will show a broken 404 image if the file name on disk is different (e.g. `logo.svg`, `logo.webp`).
   - In AF/Business, detail templates render:
     ```twig
     {% if d.image is not empty %}
         <div class="detail-hero">
             <a href="{{ site.iaddress }}mod_news/{{ d.image }}" data-bs-toggle="lightbox" data-gallery="news-main-image">
                 <img src="{{ site.iaddress }}mod_news/{{ d.image }}" fetchpriority="high" class="detail-hero-image" alt="{{ d.name }}" title="{{ d.name }}">
             </a>
         </div>
     {% else %}
         <div class="detail-hero detail-hero-placeholder">
             {% if site.logo_path is defined and site.logo_path is not empty %}
                 <img src="{{ site.iaddress }}mod_settings/{{ site.logo_path }}" class="detail-placeholder-logo" alt="{{ site.name }}" title="{{ d.name }}">
             {% elseif site.logo_symbol is defined and site.logo_symbol is not empty %}
                 <img src="{{ site.iaddress }}mod_settings/{{ site.logo_symbol }}" class="detail-placeholder-symbol" alt="{{ site.name }}" title="{{ d.name }}">
             {% elseif site.site_icon is defined and site.site_icon is not empty %}
                 <img src="{{ site.iaddress }}mod_settings/{{ site.site_icon }}" class="detail-placeholder-symbol" alt="{{ site.name }}" title="{{ d.name }}">
             {% endif %}
         </div>
     {% endif %}
     ```
   - `templates/default/static/css/detail-foundation.css`: **DOES NOT EXIST** in AF/Rojname. Classes `.detail-hero-placeholder`, `.detail-media-placeholder`, `.detail-placeholder-logo`, `.detail-placeholder-symbol` are missing from AF/Rojname styles.

2. **Hero Slider Brand Placeholder & Broken Slide Resilience**:
   - In AF/Rojname `templates/default/index/hero_slider.twig:27-31`, fallback is hardcoded to `<img src="{{ site.iaddress }}mod_settings/logo.png">`.
   - In AF/Business `templates/default/index/hero_slider.twig:23-39`, the base DOM structure always renders `.hero-slide__placeholder` with `.hero-slide__placeholder-pattern` and `logo -> symbol` fallback beneath `.hero-slide__cover-img`.
   - In AF/Rojname `templates/default/static/css/hero-news-slider.css`, the placeholder styles (`.hero-slide__placeholder`, `.hero-slide__placeholder-pattern`, `.hero-slide__placeholder-logo`, `.hero-slide__placeholder-symbol`, `.has-failed-image`, `.hero-slide__cover-img`) are missing.

3. **Backend Image Disk Asset Verification (`file_exists()`, `is_file()`)**:
   - `mod/index/helper/mod_content.helper.php:128,134`: Has `file_exists($image_file) && is_file($image_file)` validation.
   - `mod/index/helper/mod_news.helper.php`: **NO disk checks** in `news()` detail or `newses()` list queries.
   - `mod/index/helper/mod_publication.helper.php`: **NO disk checks** in `publication()` detail or `publications()` list queries; also uses legacy `$this->tema` instead of `$this->theme`.
   - `mod_announcements.helper.php`: **NO disk checks**; uses `$this->tema` and Turkish helper methods.
   - `mod_page.helper.php`: **NO disk checks** for `image` in `page()` detail query.
   - `mod_hero_slider.helper.php:280-285`: **NO disk checks** in `normalizeItem()`. It assigns `'image_url' => $image !== '' ? $this->site['iaddress'] . $image_base . $image : ''` blindly without verifying whether the image file actually exists on disk.

4. **Frontend Runtime Client-Side Error Interception**:
   - `templates/default/static/js/app.js`: **DOES NOT EXIST** in AF/Rojname. (In AF/Business, lines 100–180 implement global `document.addEventListener('error', ..., true)` capture phase interception to swap/hide broken images without layout shifts).
   - `templates/default/static/js/hero-news-slider.js`: **DOES NOT EXIST** in AF/Rojname. (In AF/Business, lines 55–69 inspect `img.complete && (img.naturalWidth === 0 || img.naturalHeight === 0)` and attach `error` listeners to add `.has-failed-image` and reveal the placeholder pattern).
   - `templates/default/layouts/base.twig`: Does not load `app.js`.

---

## 2. Logic Chain

1. **Strict Code Separation Mandate (AGENTS.md Rule 2)**:
   - `templates/default/partials/share.twig` currently contains 58 lines of inline `<style>`, 23 lines of inline `<script>`, and inline `onclick` handlers.
   - Detail views contain inline `onclick="window.print();"` and jQuery `.printThis` bindings.
   - *Inference*: To achieve 0 violations under `python3 tools/code_separation_guard.py`, styles must move to `templates/default/static/css/share.css`, script handlers must move to `templates/default/static/js/share.js`, and all print actions must use semantic `<button type="button" data-action="print">`.

2. **Universal Share Toolbar Standardization (R3 Requirement)**:
   - Detail pages across 5 core modules (`mod_news`, `mod_publication`, `mod_content`, `mod_page`, `mod_announcements`) each currently implement fragmented, inconsistent share buttons and tool bars.
   - *Inference*: Introducing `templates/default/partials/share_toolbar.twig` (matching the proven AF/Business pattern) allows all detail views to include a single reusable toolbar passing parameters `share_url_path`, `mod_name`, `item_id`, and `has_gallery`. Compact/sticky header sharing should continue using refactored `templates/default/partials/share.twig`.

3. **Disk Asset Verification to Prevent 404 Requests (R5 Requirement)**:
   - When records in the database reference deleted or missing files, frontend `<img>` tags request non-existent URLs, causing HTTP 404 errors, broken image icons, and poor SEO/Lighthouse scores.
   - *Inference*: Backend helpers in `mod/index/helper/*.helper.php` (`mod_news`, `mod_publication`, `mod_announcements`, `mod_page`, `mod_hero_slider`) must test `file_exists($path) && is_file($path)` before setting `$data['image']` or `$item['image_url']`. If false, normalize to `''` so Twig templates cleanly take the fallback path.

4. **Multi-Tier Brand Placeholder & Runtime Error Interception (R5 Requirement)**:
   - If an item has no image (or backend normalized to empty), Twig should render the brand fallback hierarchy:
     1. `site.logo_path` (Site Logo)
     2. `site.logo_symbol` or `site.site_icon` (Symbol / Favicon)
     3. Radial gradient container
   - If a network failure occurs on the client side after HTML delivery:
     * Global `app.js` error capture listener hides broken `.detail-hero-image` and reveals `.detail-hero-placeholder`.
     * `hero-news-slider.js` intercepts broken slide images, adding `.has-failed-image` to reveal `.hero-slide__placeholder`.

---

## 3. Caveats

1. **Excluded Commercial Modules**:
   - Sibling repository `AF/Business` contains references to `mod_client`, `mod_service`, `mod_product`, and `mod_reference` in its helpers and templates.
   - **Crucial Rule**: In `AF/Rojname`, these commercial modules are strictly forbidden. When porting or adapting patterns from `AF/Business`, DO NOT include any cases or routes for `mod_client`, `mod_service`, `mod_product`, or `mod_reference`.
2. **Frontend Theme Scope**:
   - `AF/Business` contains 4 frontend themes (`default`, `forest`, `furniture`, `stoon`).
   - `AF/Rojname` contains only 1 public frontend theme (`default`). Therefore, frontend changes apply exclusively to `templates/default/` and `templates/base_static/`.
3. **Language Dictionaries**:
   - All 9 languages (`ar`, `ckb`, `de`, `en`, `fr`, `ku`, `sv`, `tr`, `zza`) in `mod/index/lang/` must receive toolbar keys to maintain 100% dictionary completeness.

---

## 4. Conclusion & Required Action Plan

To fully satisfy R3 and R5 in AF/Rojname, the implementation phase must execute the following concrete steps:

### Action Items for R3: Universal Social Share & Document Export Toolbar

1. **Create `templates/default/static/css/share.css`**:
   - Port styling for `.share-toolbar-wrapper`, `.share-toolbar`, `.social-share-btn`, `.tool-action-btn`, social network brand colors (Facebook, X, WhatsApp, TikTok, LinkedIn, Email, Copy), dark mode support, and mobile responsive rules.

2. **Create `templates/default/static/js/share.js`**:
   - Implement event listeners for `[data-action="print"]` (`window.print()`).
   - Implement `[data-share-network="tiktok"]` (Native Share or clipboard copy fallback with tooltip feedback).
   - Implement `.btn-copy, [data-copy-url]` (clipboard writeText with interactive tooltip confirmation `lang.index.link_copied`).
   - Implement mobile `.btn-native-share` with `navigator.share`.
   - Initialize Bootstrap tooltips for `.share-toolbar [data-bs-toggle="tooltip"]`.

3. **Refactor `templates/default/partials/share.twig`**:
   - Remove inline `<style>` and `<script>` blocks.
   - Include `<link rel="stylesheet" href="{{ site.taddress }}{{ site.app_template }}/static/css/share.css...">` and `<script src=".../share.js...">`.
   - Add TikTok button with `data-share-network="tiktok"`.

4. **Create `templates/default/partials/share_toolbar.twig`**:
   - Implement reusable component accepting `share_url_path`, `mod_name`, `item_id`, `has_gallery`.
   - Combine social share list + document action tools (`tools/saveHtml`, `tools/saveText`, `tools/saveAll` ZIP, `data-action="print"`).

5. **Update Detail Templates**:
   - `templates/default/index/mod_news/news.twig`: Replace legacy `.btn-group` and bottom share with `partials/share_toolbar.twig`.
   - `templates/default/index/mod_publication/publication.twig`: Replace legacy `.btn-group` and bottom share with `partials/share_toolbar.twig`.
   - `templates/default/index/mod_content/content.twig`: Replace legacy `.btn-group` and bottom share with `partials/share_toolbar.twig`.
   - `templates/default/index/mod_page/page.twig`: Replace legacy `.akm_tools` and `paylas.twig` with `partials/share_toolbar.twig`.
   - `templates/default/index/mod_announcements/announcement.twig`: Replace broken `index/paylas.twig` and `.akm_tools` with `partials/share_toolbar.twig`.
   - `templates/default/index/mod_imagecategories/gallery.twig`: Replace broken `index/paylas.twig` with `partials/share.twig` or `partials/share_toolbar.twig`.

6. **Modernize `mod/tools/index.php`**:
   - Replace Turkish `Run::import('index', '_permission_kontrol', ...)` with `_check_permission`.
   - Standardize output variable `$output = ...; echo strip_tags($output);`.

7. **Update Language Files (`mod/index/lang/*.php`)**:
   - Add `download_html`, `download_text`, `download_all`, `print` across all 9 languages.

---

### Action Items for R5: Brand Placeholder Fallback & Disk Asset Verification

1. **Add `detail-foundation.css` or integrate into `templates/default/static/css/custom.css`**:
   - Add classes:
     ```css
     .detail-hero-placeholder,
     .detail-media-placeholder {
       display: flex;
       align-items: center;
       justify-content: center;
       min-height: 240px;
       max-height: 380px;
       padding: 2.5rem 1.5rem;
       background:
         radial-gradient(circle at 75% 35%, rgba(30, 41, 59, 0.95), rgba(15, 23, 42, 1)),
         linear-gradient(135deg, #0b0f19 0%, #1e293b 50%, #0f172a 100%);
       border-radius: inherit;
       overflow: hidden;
       position: relative;
     }
     .detail-placeholder-logo {
       max-width: min(320px, 75%);
       max-height: 110px;
       object-fit: contain;
       opacity: 0.92;
       filter: drop-shadow(0 6px 16px rgba(0, 0, 0, 0.45));
     }
     .detail-placeholder-symbol {
       width: 100px;
       height: 100px;
       max-width: 40%;
       max-height: 100px;
       object-fit: contain;
       opacity: 0.88;
       filter: drop-shadow(0 6px 16px rgba(0, 0, 0, 0.45));
     }
     ```

2. **Update Detail Templates for Fallback Hierarchy**:
   - In `news.twig`, `publication.twig`, `content.twig`, `page.twig`, `announcement.twig`:
     Replace hardcoded `logo.png` fallback with `.detail-hero-placeholder` containing `site.logo_path -> site.logo_symbol -> site.site_icon`.

3. **Modernize `templates/default/index/hero_slider.twig` and `hero-news-slider.css`**:
   - Structure `.hero-slide__image` to always include `.hero-slide__placeholder` with `.hero-slide__placeholder-pattern` and `logo -> symbol` fallback under `.hero-slide__cover-img`.
   - Add placeholder CSS rules to `templates/default/static/css/hero-news-slider.css`.

4. **Add Backend Image Disk Verification in Helpers (`mod/index/helper/`)**:
   - `mod_news.helper.php`: Validate `file_exists()` for `$news_data['image']` and `$news_data['user_image']`.
   - `mod_publication.helper.php`: Validate `file_exists()` for `$publication_data['image']` and user image; switch from `$this->tema` to `$this->theme`.
   - `mod_announcements.helper.php`: Validate `file_exists()` for announcement image; switch to `$this->theme`.
   - `mod_page.helper.php`: Validate `file_exists()` for `$pageData['image']`.
   - `mod_hero_slider.helper.php`: In `normalizeItem()`, verify `file_exists() && is_file()` before setting `image_url`, `thumb_url`, `author_image_url`.

5. **Create Client-Side Error Handlers**:
   - Create `templates/default/static/js/app.js`:
     Implement global capture-phase `error` listener for `<img>` elements to hide broken images and reveal `.detail-hero-placeholder`.
   - Include `app.js` in `templates/default/layouts/base.twig:184`.
   - Create `templates/default/static/js/hero-news-slider.js`:
     Implement startup `img.complete` inspection and runtime `error` listener to mark `.has-failed-image`.
   - Include `hero-news-slider.js` in `templates/default/index/hero_slider.twig:122`.

---

## 5. Verification Method

Once changes are implemented, verify using:

1. **Code Separation Audit**:
   ```bash
   python3 tools/code_separation_guard.py --all
   ```
   *Pass criteria*: 0 violations reported across `templates/default/` and `mod/`.

2. **Language & i18n Guard**:
   ```bash
   python3 tools/i18n_guard.py
   ```
   *Pass criteria*: 0 missing keys or syntax errors across all 9 language packs.

3. **PHP Syntax Check**:
   ```bash
   find . -name "*.php" ! -path "./vendor/*" -exec php -l {} +
   ```
   *Pass criteria*: No syntax errors.

4. **JavaScript Syntax Check**:
   ```bash
   node --check templates/default/static/js/share.js
   node --check templates/default/static/js/app.js
   node --check templates/default/static/js/hero-news-slider.js
   ```
   *Pass criteria*: No syntax errors.

5. **Commercial Module Exclusion Check**:
   ```bash
   grep -rnE "(mod_client|mod_service|mod_product|mod_reference)" mod/ templates/default/
   ```
   *Pass criteria*: Zero matches in active frontend routes or helpers.
