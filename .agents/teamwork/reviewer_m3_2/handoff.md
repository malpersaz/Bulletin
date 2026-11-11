# Milestone 3 Review & Adversarial Challenge Report: Schema, Mobile Admin & Public Template Parity

**Reviewer**: Reviewer 2 (`reviewer_m3_2`)  
**Verdict**: **APPROVE**  
**Overall Risk Assessment**: **LOW**

---

## 1. Observation

Direct observations and command executions conducted in workspace `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin`:

### 1.1 Database Schema & View Parity
1. **Schema Definition**:
   `mod/mod_news/mod_news.sql` lines 21-22 define:
   ```sql
   `image_alt` varchar(255) DEFAULT NULL,
   `image_credit` varchar(255) DEFAULT NULL,
   ```
   placed immediately after `image`.

2. **ORM & View Models**:
   - `mod/mod_news/model/mod_news.model.php` lines 26-27 & 50-51 define public properties `$image_alt` and `$image_credit` with default `null`.
   - `mod/mod_news/model/newses.model.php` lines 24-25 & 75-76 include `mod_news.image_alt` and `mod_news.image_credit` in the `newses` view select query.
   - `newses.model.php` lines 50 & 116-128 implement self-healing view recreation and migration (`ensureTableColumns()` executing `ALTER TABLE mod_news ADD COLUMN ...` with fallback for SQLite).

3. **Live Container Inspection**:
   Executed in `bulletin_app` container:
   ```bash
   docker exec bulletin_app php -r '
   require_once "/var/www/html/init.php";
   Load::model("mod_news.newses");
   $db = Load::lib("Database");
   $db->fetch_array("SHOW COLUMNS FROM mod_news LIKE \"image_alt\"");
   echo "mod_news image_alt: " . ($db->getSize() > 0 ? "EXISTS\n" : "MISSING\n");
   $db->fetch_array("SHOW COLUMNS FROM mod_news LIKE \"image_credit\"");
   echo "mod_news image_credit: " . ($db->getSize() > 0 ? "EXISTS\n" : "MISSING\n");
   $db->fetch_array("SHOW COLUMNS FROM newses LIKE \"image_alt\"");
   echo "newses image_alt: " . ($db->getSize() > 0 ? "EXISTS\n" : "MISSING\n");
   $db->fetch_array("SHOW COLUMNS FROM newses LIKE \"image_credit\"");
   echo "newses image_credit: " . ($db->getSize() > 0 ? "EXISTS\n" : "MISSING\n");
   '
   ```
   Output:
   ```
   mod_news image_alt: EXISTS
   mod_news image_credit: EXISTS
   newses image_alt: EXISTS
   newses image_credit: EXISTS
   ```
   Both columns verified as `varchar(255) DEFAULT NULL` across table and view.

### 1.2 Admin Templates (Phone-Only Mobile-First Layout & Code Separation)
1. **Edit Template (`templates/admin/mod_news/edit.twig`)**:
   Lines 251–265 implement:
   ```twig
   {# Image Alt Text #}
   <div class="form-row">
       <div class="m3-input-wrapper">
           <input type="text" class="m3-input-lg" name="image_alt" id="editImageAltInput" placeholder=" " autocomplete="off" value="{{ d.image_alt|default('') }}" maxlength="255">
           <label class="m3-floating-label">{{ lang.mod_news.admin.columns.image_alt }}</label>
       </div>
   </div>

   {# Image Credit / Source #}
   <div class="form-row">
       <div class="m3-input-wrapper">
           <input type="text" class="m3-input-lg" name="image_credit" id="editImageCreditInput" placeholder=" " autocomplete="off" value="{{ d.image_credit|default('') }}" maxlength="255">
           <label class="m3-floating-label">{{ lang.mod_news.admin.columns.image_credit }}</label>
       </div>
   </div>
   ```
   Uses `.m3-input-wrapper`, `.m3-input-lg` (56px minimum touch target, single column, full width), and floating labels. Zero inline `style="..."` attributes or inline scripts.
2. **Add Template & Wizard (`templates/admin/mod_news/add.twig` & `templates/admin/partials/wizard_content.twig`)**:
   `add.twig` serves as the dynamic wizard shell. In `wizard_content.twig` lines 195–208, Step 7 conditionally renders `image_alt` and `image_credit` inputs for `wizard_lang_key == 'mod_news'`.
   `templates/admin/assets/js/script.js` lines 3103–3110 captures these inputs into `formData` on wizard submission.
3. **Localization Completeness**:
   All 11 language packs in `mod/mod_news/lang/` (`ar`, `ckb`, `de`, `en`, `fa`, `fr`, `ku`, `ru`, `sv`, `tr`, `zza`) define `image_alt` and `image_credit` under `$lang['mod_news']['admin']['columns']`.

### 1.3 Public Template (`templates/amed/index/mod_news/news.twig`)
1. **Hero Figure & Figcaption**:
   Lines 418–427 render:
   ```twig
   {% if d.image is defined and d.image is not empty and d.image != '#' %}
       <figure class="detail-hero">
           <a href="{{ site.iaddress }}mod_news/{{ d.image }}" class="glightbox" data-bs-toggle="lightbox" data-gallery="news-main-image" aria-label="{{ (d.image_alt|default(d.name))|escape('html_attr') }}">
               <img src="{{ site.iaddress }}mod_news/{{ d.image }}" width="1280" height="720" fetchpriority="high" class="detail-hero-image" alt="{{ d.image_alt|default(d.name) }}" title="{{ d.name }}">
           </a>
           {% if d.image_credit is defined and d.image_credit is not empty %}
               <figcaption class="news-hero-caption">{{ d.image_credit }}</figcaption>
           {% endif %}
       </figure>
   {% endif %}
   ```
2. **JSON-LD Schema Parity**:
   Lines 160–185 include `caption` and conditional `creditText` for all 3 standard aspect ratio `ImageObject` entries (16:9, 4:3, 1:1):
   ```twig
   "caption": {{ (d.image_alt|default(d.name))|json_ld|raw }}{% if d.image_credit is defined and d.image_credit is not empty %},
   "creditText": {{ d.image_credit|json_ld|raw }}{% endif %}
   ```
   Validated live using Twig runtime in PHP:
   - When `image_credit` is present: valid JSON-LD output with `caption` and `creditText`.
   - When `image_credit` is null: valid JSON-LD output with `caption` falling back to `d.name` and `creditText` cleanly omitted without trailing comma syntax errors.
3. **OpenGraph & Twitter Cards**:
   `og:image:alt` and `twitter:image:alt` meta tags populated with `{{ (d.image_alt|default(d.name))|escape('html_attr') }}`.
4. **CSS Foundation**:
   `templates/amed/static/css/detail-foundation.css` lines 215–231 define `.news-hero-caption` for both light and dark themes.

### 1.4 AGENTS.md Rule Citations
1. `mod/mod_news/validator/add.validator.php` line 141:
   `// Normalize SEO slug (AGENTS.md Rule 9)`
2. `mod/mod_news/validator/edit.validator.php` line 140:
   `// Normalize SEO slug (AGENTS.md Rule 9)`
   Confirmed citation matches AGENTS.md Rule 9 ("URL Generation Parity (Wikipedia Style)").

### 1.5 Quality Guard Audits
- `python3 tools/code_separation_guard.py`: 0 violations across all 25 files.
- `python3 tools/i18n_guard.py`: 0 violations across 32 files (100% clean).
- `python3 tools/module_boundary_guard.py --all`: 0 violations across 10,100 scanned files.

### 1.6 Supporting Test Suites
- MCP Smoke Tests: `docker exec bulletin_app php /var/www/html/tasks/mcp_smoke.php` -> 83 passed, 0 failed.
- CLI Wrapper Unit Tests: `python3 -m unittest tests/test_mcp_cli.py` -> 17 tests passed in 0.065s.
- Image Pipeline Tests: `python3 tests/test_m3_image_pipeline_empirical.py` -> 15 tests passed in 8.938s.

---

## 2. Logic Chain

1. **Schema & Model Integrity**:
   Observation 1.1 shows that `image_alt` and `image_credit` are defined in the canonical SQL schema (`mod_news.sql`), the Active Record ORM class (`mod_news.model.php`), and the read-optimized database view (`newses.model.php`). Because the view model implements dynamic column checks and auto-migration, any deployment to older database snapshots automatically adds the columns and recreates the view without administrative downtime.
2. **Mobile Admin Usability (AGENTS.md Rule 5)**:
   Observation 1.2 demonstrates that the new form fields in `edit.twig` and `wizard_content.twig` adhere strictly to the Material 3 phone-only mobile design system (`.m3-input-wrapper`, `.m3-input-lg`, `.m3-floating-label`), featuring minimum 56px touch heights, full-width single-column layout, and 16px font size to prevent mobile browser zooming.
3. **Strict Code Separation (AGENTS.md Rule 1)**:
   Observations 1.2 and 1.3 show no inline style tags or attributes were introduced. Observation 1.5 confirms `code_separation_guard.py` ran with zero violations across all templates.
4. **Semantic HTML & JSON-LD Parity**:
   Observation 1.3 demonstrates that the public template was upgraded from a generic `<div>` to a semantic `<figure class="detail-hero">` with conditional `<figcaption>`, providing proper accessibility for screen readers. The Schema.org JSON-LD structured data mirrors this hierarchy via `caption` and `creditText` on `ImageObject`.
5. **Adversarial Resiliency & Security**:
   Direct Twig rendering in PHP under hostile test strings (XSS attempts containing quotes, `<script>` tags, and unescaped ampersands) confirmed that:
   - Twig HTML auto-escaping neutralizes inline HTML inside `<figcaption>`.
   - The `json_ld` filter escapes quotes and ASCII control characters, preserving valid JSON-LD structure.
   - The controller sanitizes input via `cleanNewsTextEntities()`, stripping HTML tags and constraining lengths to 255 characters.
6. **No Integrity Violations Detected**:
   Source code review confirms all logic is implemented with genuine functionality rather than mock facades or hardcoded values.

---

## 3. Caveats

1. **Lead/Featured Image Scope**:
   `image_alt` and `image_credit` metadata attach to the primary featured article image (`mod_news.image`). Secondary images embedded inside the article body HTML (`mod_news.content`) are managed by TinyMCE inline formatting.
2. **No Caveats on Implementation**:
   All requirements from SCOPE.md, PROJECT.md, and DISPATCH.md are fully satisfied.

---

## 4. Conclusion

Milestone 3 successfully achieves database schema and view parity, phone-only mobile admin compliance, semantic public template rendering with complete Schema.org JSON-LD parity, and exact AGENTS.md rule citations.

**Verdict**: **APPROVE**

---

## 5. Verification Method

To independently verify this report, execute the following commands in the workspace root:

```bash
# 1. Syntax & Compilation Checks
php -l mod/mod_news/model/mod_news.model.php
php -l mod/mod_news/model/newses.model.php
php -l mod/mod_news/index.php
php -l mod/index/helper/mod_news.helper.php
php -l lib/McpNewsTools.class.php
php -l mod/mod_news/validator/add.validator.php
php -l mod/mod_news/validator/edit.validator.php
for f in mod/mod_news/lang/*.php; do php -l "$f" || exit 1; done
python3 -m py_compile .agents/skills/kevanger/scripts/process_news_image.py tools/mcp_cli.py tests/test_mcp_cli.py

# 2. Database Column Verification in Docker
docker exec bulletin_app php -r '
require_once "/var/www/html/init.php";
Load::model("mod_news.newses");
$db = Load::lib("Database");
$db->fetch_array("SHOW COLUMNS FROM mod_news LIKE \"image_alt\"");
echo "mod_news image_alt: " . ($db->getSize() > 0 ? "EXISTS\n" : "MISSING\n");
$db->fetch_array("SHOW COLUMNS FROM mod_news LIKE \"image_credit\"");
echo "mod_news image_credit: " . ($db->getSize() > 0 ? "EXISTS\n" : "MISSING\n");
$db->fetch_array("SHOW COLUMNS FROM newses LIKE \"image_alt\"");
echo "newses image_alt: " . ($db->getSize() > 0 ? "EXISTS\n" : "MISSING\n");
$db->fetch_array("SHOW COLUMNS FROM newses LIKE \"image_credit\"");
echo "newses image_credit: " . ($db->getSize() > 0 ? "EXISTS\n" : "MISSING\n");
'

# 3. Quality Guards
python3 tools/code_separation_guard.py
python3 tools/i18n_guard.py
python3 tools/module_boundary_guard.py --all

# 4. Test Suites
python3 -m unittest tests/test_mcp_cli.py
python3 tests/test_m3_image_pipeline_empirical.py
docker exec bulletin_app php /var/www/html/tasks/mcp_smoke.php
```

Invalidation conditions:
- Any failure in `code_separation_guard.py` (inline styles or scripts).
- Any missing column in `mod_news` or `newses` view.
- Any syntax error in PHP or Python files.
- Any failure in the 83 MCP smoke tests or unit suites.
