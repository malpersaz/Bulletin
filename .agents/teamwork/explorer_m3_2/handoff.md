# Milestone 3 Explorer 2 Handoff Report: mod_news Media Credits & Templates

## 1. Observation

### 1.1 Database Schema (`mod/mod_news/mod_news.sql`)
Direct inspection of `mod/mod_news/mod_news.sql` lines 18–22:
```sql
  `gallery_id` int(11) DEFAULT 0,
  `file_id` int(11) DEFAULT 0,
  `image` varchar(255) DEFAULT NULL,
  `acl_id` int(11) DEFAULT 0,
  `video` text DEFAULT NULL,
```
There are no `image_alt` or `image_credit` columns in `mod_news.sql`. Furthermore, executing a database inspection inside the running container (`docker exec bulletin_app php -r '... SHOW COLUMNS FROM mod_news ...'`) confirmed that `mod_news` in MySQL currently contains 23 columns (indices 0 to 22), where column index 18 is `image`, followed immediately by index 19 `acl_id`.

### 1.2 Model Files (`mod/mod_news/model/*`)
1. **`mod/mod_news/model/mod_news.model.php`** (lines 20–30 & 46–52):
   ```php
   public $gallery_id;
   public $file_id;
   public $image;
   public $acl_id;
   ```
   And in `getDefaults()`:
   ```php
   'image' => null,
   'video' => null,
   'summary' => null,
   'updated_at' => null,
   ```
   Properties `$image_alt` and `$image_credit` are absent from the ORM model and default value dictionary.

2. **`mod/mod_news/model/newses.model.php`** (lines 22–25, 46–50, 71–74):
   ```php
   public $deck;
   public $image;
   public $lang;
   ```
   In `ensureViewExists()`:
   ```php
   if ($this->viewExists('newses') && $this->hasUserImageColumn() && $this->hasViewCountColumn() && $this->hasCategoryAclIdColumn() && $this->hasUpdatedAtColumn() && $this->hasShowOnHomepageColumn()) {
       return;
   }
   ```
   And in the `CREATE VIEW newses AS SELECT`:
   ```sql
   mod_news.deck,
   mod_news.image,
   mod_news.file_id AS file_id,
   mod_news.lang,
   ```
   Executing `SHOW COLUMNS FROM newses` inside Docker confirmed 33 columns; neither `image_alt` nor `image_credit` exists in the view. If the view is not dropped when new columns are added, it will never expose `image_alt` or `image_credit`.

### 1.3 Controller (`mod/mod_news/index.php`) & Base Controller (`lib/BaseModuleController.class.php`)
1. `mod/mod_news/index.php` extends `BaseModuleController`. In `BaseModuleController::processFieldData($model)` (lines 1162–1168):
   ```php
   if (property_exists($model, $field)) {
       $val = Req::Post_Safe($field);
       $model->$field = $val;
   }
   ```
2. In `mod/mod_news/index.php::cleanNewsTextEntities($model)` (lines 108–133):
   ```php
   $textFields = ['name', 'title', 'description', 'keywords'];
   foreach ($textFields as $field) {
       ...
       $val = trim(strip_tags((string) $raw));
       $val = html_entity_decode(html_entity_decode($val, ENT_QUOTES | ENT_HTML5, 'UTF-8'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
       ...
   }
   ```
   `image_alt` and `image_credit` are not in `$textFields`.
3. In `mod/mod_news/index.php`, `cleanEntityFields(array $row)` is not overridden. `BaseModuleController::cleanEntityFields` only decodes `name`, `title`, `summary`, `description`, `keywords`, and `category_name`.

### 1.4 MCP Tool Implementation (`lib/McpNewsTools.class.php`)
1. In `create_news` JSON schema (lines 225–246) and `update_news` JSON schema (lines 344–364), only `image` object is defined; `image_alt` and `image_credit` are not accepted in tool parameter schemas.
2. In `createNews()` (lines 922–945) and `updateNews()` (lines 1108–1127), `image_alt` and `image_credit` are never assigned to `$model`.
3. In `getNews()` (lines 722–770), `$row = $model->toArray();` returns fields directly from the model/view.

### 1.5 Validators (`mod/mod_news/validator/add.validator.php` & `edit.validator.php`)
1. Lines 141 in `add.validator.php` and 140 in `edit.validator.php` contain:
   `// Normalize SEO slug (Rule 10)` (citation refers to old Rule 10, whereas current AGENTS.md establishes Rule 9 for URL generation).
2. Neither validator checks `image_alt` or `image_credit`. Optional fields with length checks (like `keywords`, lines 192–207) validate `min: 1, max: 255` when non-empty.

### 1.6 Admin Templates (`templates/admin/mod_news/`)
1. **`templates/admin/mod_news/edit.twig`**:
   Card 3 (Media & Relations, lines 189–250) has:
   ```twig
   <div class="form-row">
       <label class="field-section-label">{{ lang.mod_news.admin.columns.image }}</label>
       {% include 'admin/partials/image_uploader.twig' with { ... } %}
   </div>
   ```
   Followed immediately by `gallery_id` selection on line 253. There are no inputs for `image_alt` or `image_credit`.
2. **`templates/admin/partials/wizard_content.twig`**:
   Step 7 (Image, lines 150–195) renders `image_uploader.twig` for the wizard.
   In `templates/admin/assets/js/script.js` (lines 3098–3103), the wizard collects `image` but not `image_alt` or `image_credit`.
3. **`templates/admin/mod_news/add.twig`**:
   This is a 28-line shell `#newsAddShell` activating the mobile wizard overlay (`#contentWizard`).

### 1.7 Public Template (`templates/amed/index/mod_news/news.twig`)
1. **Hero image container** (lines 411–417):
   ```twig
   {% if d.image is defined and d.image is not empty and d.image != '#' %}
       <div class="detail-hero">
           <a href="{{ site.iaddress }}mod_news/{{ d.image }}" class="glightbox" data-bs-toggle="lightbox" data-gallery="news-main-image" aria-label="{{ d.name|escape('html_attr') }}">
               <img src="{{ site.iaddress }}mod_news/{{ d.image }}" width="1280" height="720" fetchpriority="high" class="detail-hero-image" alt="{{ d.name }}" title="{{ d.name }}">
           </a>
       </div>
   {% endif %}
   ```
   Renders bare `<div>`, lacks `<figure>`, uses `alt="{{ d.name }}"`, and lacks `<figcaption>`.
2. **Schema.org JSON-LD `ImageObject`** (lines 158–177):
   ```twig
   "image": [
     {% if d.image is not empty %}
     {
       "@type": "ImageObject",
       "url": {{ (d.image_16x9|default(site.iaddress ~ 'mod_news/' ~ d.image))|json_ld|raw }},
       "width": 1280,
       "height": 720
     },
     {
       "@type": "ImageObject",
       "url": {{ (d.image_4x3|default(site.iaddress ~ 'mod_news/4x3/' ~ d.image))|json_ld|raw }},
       "width": 1200,
       "height": 900
     },
     {
       "@type": "ImageObject",
       "url": {{ (d.image_1x1|default(site.iaddress ~ 'mod_news/1x1/' ~ d.image))|json_ld|raw }},
       "width": 1200,
       "height": 1200
     }
   ```
   Lacks `"caption"` and `"creditText"`.
3. **OpenGraph & Twitter meta tags** (lines 73–80, 105–106):
   Lacks `og:image:alt` and `twitter:image:alt`.
4. **Public Controller Helper (`mod/index/helper/mod_news.helper.php`)**:
   In `_cleanNewsRow` (line 32), text sanitization runs only on `['name', 'title', 'summary', 'description', 'keywords', 'category_name']`.

### 1.8 Quality Guards & Standards
1. `tools/code_separation_guard.py` passed with 0 violations across repository files.
2. `tools/i18n_guard.py --path mod/mod_news` passed with 16 clean files. Rule 4 forbids `|default(...)` on localization keys, but allows `default(...)` on dynamic data variables like `d.image_alt|default(d.name)`. Translation keys must exist across core packs (`en`, `tr`, `ku`).
3. `tools/module_boundary_guard.py --all` passed with 0 violations across 10,018 files.

---

## 2. Logic Chain

1. **Schema Consistency (Observation 1.1)**:
   Adding `image_alt VARCHAR(255) DEFAULT NULL` and `image_credit VARCHAR(255) DEFAULT NULL` directly after `image` in `mod/mod_news/mod_news.sql` aligns the foundational DDL with other metadata columns (`title`, `keywords`, `description`).

2. **Self-Healing View Lifecycle (Observation 1.2)**:
   `newses.model.php` manages the `newses` view. Currently, `ensureViewExists()` returns early if `newses` exists and has `updated_at`, `view_count`, etc.
   Therefore, if `newses.model.php` does not check for `hasImageAltColumn()` and `hasImageCreditColumn()`, any existing database will never rebuild `newses`, and queries against `newses` for `image_alt`/`image_credit` will omit the data or fail.
   Additionally, if `CREATE VIEW newses` runs before `ALTER TABLE mod_news`, MySQL throws `Error 1054: Unknown column 'mod_news.image_alt' in 'field list'`.
   Therefore, an auto-migration helper `ensureTableColumns()` must verify `SHOW COLUMNS FROM mod_news LIKE 'image_alt'` and execute `ALTER TABLE mod_news ADD COLUMN ...` before executing `CREATE VIEW`.

3. **Backend Sanitization & Truncation (Observation 1.3 & 1.4)**:
   `BaseModuleController::processFieldData` automatically maps POST fields if property exists on `$model`.
   In `mod/mod_news/index.php`, `cleanNewsTextEntities` must include `image_alt` and `image_credit` in its sanitization loop:
   - Strip all HTML tags with `strip_tags()`.
   - Decode double HTML entities (`html_entity_decode`).
   - Truncate cleanly to 255 characters via `mb_substr($val, 0, 255)`.
   - Convert empty string `''` to `null`.
   In `lib/McpNewsTools.class.php`, `createNews` and `updateNews` must perform identical sanitization when `image_alt` and `image_credit` are passed in `$args`.

4. **Validation Contract (Observation 1.5)**:
   In `add.validator.php` and `edit.validator.php`, `image_alt` and `image_credit` are optional. When non-empty, they must pass length validation `1–255` characters using `admin.columns.image_alt` and `admin.columns.image_credit` translation keys.
   Citations on line 141 must be corrected from `Rule 10` to `Rule 9` per PROJECT.md Feature 33.

5. **Admin Mobile-First Surface Contract (Observation 1.6 & Skill admin-mobile-check)**:
   Admin panel views are phone-only mobile viewports.
   In `templates/admin/mod_news/edit.twig`, fields must be placed inside `Card 3: Media & Relations` directly below the image uploader, using `.m3-input-wrapper`, `.m3-input-lg`, and `.m3-floating-label`.
   In `templates/admin/partials/wizard_content.twig`, fields must be placed in `wizardStep7` under `{% if wizard_lang_key == 'mod_news' %}`, and collected in `script.js`.
   Strict code separation prohibits inline `style="..."` or inline `<script>`.

6. **Semantic HTML, SEO & Schema.org Parity (Observation 1.7 & Remediation Plan B5)**:
   In `templates/amed/index/mod_news/news.twig`:
   - Upgrade `.detail-hero` from `<div>` to `<figure class="detail-hero">`.
   - The hero `<img>` must render `alt="{{ d.image_alt|default(d.name) }}"`.
   - When `d.image_credit` is non-empty, render `<figcaption class="news-hero-caption">{{ d.image_credit }}</figcaption>`.
   - In Schema.org JSON-LD `ImageObject` (all 3 responsive crops: 16:9, 4:3, 1:1), add `"caption": {{ (d.image_alt|default(d.name))|json_ld|raw }}` and conditionally `"creditText": {{ d.image_credit|json_ld|raw }}`.
   - In OpenGraph & Twitter tags, populate `og:image:alt` and `twitter:image:alt`.
   - Add `.news-hero-caption` CSS to `templates/amed/static/css/detail-foundation.css` to prevent inline styles.

---

## 3. Caveats

1. **Inline Images in Article Body**:
   This blueprint strictly addresses the featured/hero news image (`mod_news.image`). Secondary inline images embedded in `content` (`articleBody`) via TinyMCE or secondary HTML blocks are governed by TinyMCE markup, not `mod_news.image_alt` / `mod_news.image_credit` columns.
2. **Backward Compatibility**:
   Existing articles will have `image_alt = NULL` and `image_credit = NULL`. The public template uses `d.image_alt|default(d.name)` and `{% if d.image_credit is defined and d.image_credit is not empty %}` to ensure 100% backward visual and semantic compatibility with older articles.
3. **No Sibling Repositories**:
   In strict compliance with `AGENTS.md Rule 3`, this blueprint applies strictly within `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin`.

---

## 4. Conclusion & Implementation Blueprint

### Component 1: `mod/mod_news/mod_news.sql`
Add lines 21–22 after `image`:
```sql
  `file_id` int(11) DEFAULT 0,
  `image` varchar(255) DEFAULT NULL,
  `image_alt` varchar(255) DEFAULT NULL,
  `image_credit` varchar(255) DEFAULT NULL,
  `acl_id` int(11) DEFAULT 0,
```

### Component 2: Models
#### A. `mod/mod_news/model/mod_news.model.php`
- Add properties (after line 25):
  ```php
  public $image;
  public $image_alt;
  public $image_credit;
  public $acl_id;
  ```
- Add to `getDefaults()` (lines 47–48):
  ```php
  'image' => null,
  'image_alt' => null,
  'image_credit' => null,
  'video' => null,
  ```

#### B. `mod/mod_news/model/newses.model.php`
- Add properties (after line 23):
  ```php
  public $image;
  public $image_alt;
  public $image_credit;
  public $lang;
  ```
- Update `ensureViewExists()` (lines 46–54):
  ```php
  private function ensureViewExists()
  {
      if ($this->viewExists('newses') && $this->hasUserImageColumn() && $this->hasViewCountColumn() && $this->hasCategoryAclIdColumn() && $this->hasUpdatedAtColumn() && $this->hasShowOnHomepageColumn() && $this->hasImageAltColumn() && $this->hasImageCreditColumn()) {
          return;
      }

      $this->ensureTableColumns();
      $this->dropViewIfExists('newses');

      $sql = "CREATE VIEW newses AS SELECT
    mod_news.id,
      mod_news.name,
      mod_news.created_at,
      mod_news.updated_at,
      mod_news.status,
      mod_news.slider,
      mod_news.show_on_homepage,
      mod_news.content,
      mod_news.summary,
      mod_news.seo,
      mod_news.title,
      mod_news.keywords,
      mod_news.description,
      mod_news.gallery_id AS gallery_id,
      mod_news.acl_id AS acl_id,
      mod_news.deck,
      mod_news.image,
      mod_news.image_alt,
      mod_news.image_credit,
      mod_news.file_id AS file_id,
      mod_news.lang,
      ...
  ```
- Add helper methods (around line 125):
  ```php
  private function hasImageAltColumn()
  {
      return $this->hasViewColumn('image_alt');
  }

  private function hasImageCreditColumn()
  {
      return $this->hasViewColumn('image_credit');
  }

  private function ensureTableColumns()
  {
      try {
          if (!$this->hasTableColumn('mod_news', 'image_alt')) {
              $this->query("ALTER TABLE `mod_news` ADD COLUMN `image_alt` varchar(255) DEFAULT NULL AFTER `image`");
          }
          if (!$this->hasTableColumn('mod_news', 'image_credit')) {
              $this->query("ALTER TABLE `mod_news` ADD COLUMN `image_credit` varchar(255) DEFAULT NULL AFTER `image_alt`");
          }
      } catch (\Throwable $e) {
          // Graceful fallback if SQLite or already added
      }
  }

  private function hasTableColumn($tableName, $columnName)
  {
      try {
          if ($this->getAdapterType() && strpos($this->getAdapterType(), 'PDO_SQLITE') !== false) {
              $safeTable = addslashes($tableName);
              $this->fetch_array("PRAGMA table_info('{$safeTable}')");
              if (is_array($this->_result)) {
                  foreach ($this->_result as $col) {
                      if (isset($col['name']) && $col['name'] === $columnName) {
                          return true;
                      }
                  }
              }
              return false;
          }

          $safeTable = addslashes($tableName);
          $safeCol = addslashes($columnName);
          $this->fetch_array("SHOW COLUMNS FROM `{$safeTable}` LIKE '{$safeCol}'");
          return $this->getSize() !== 0;
      } catch (\Throwable $e) {
          return false;
      }
  }
  ```

### Component 3: Controller & Helper
#### A. `mod/mod_news/index.php`
- In `cleanNewsTextEntities($model)` (lines 108–135):
  ```php
  $textFields = ['name', 'title', 'description', 'keywords', 'image_alt', 'image_credit'];
  foreach ($textFields as $field) {
      if (Req::is_Post($field)) {
          $raw = Req::Post($field);
      } elseif (isset($model->$field)) {
          $raw = $model->$field;
      } else {
          continue;
      }
      $val = trim(strip_tags((string) $raw));
      $val = html_entity_decode(
          html_entity_decode($val, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
          ENT_QUOTES | ENT_HTML5,
          'UTF-8'
      );
      if ($field === 'description') {
          $val = Helper::truncateWords($val, 255);
      }
      if ($field === 'title') {
          $val = trim((string) preg_replace('/\s*[|–—-]\s*Amed\s*B[üu]lten(?:i)?\s*$/iu', '', $val));
          if ($val === '') {
              $val = (string) $model->name;
          }
      }
      if ($field === 'image_alt' || $field === 'image_credit') {
          $val = ($val !== '') ? mb_substr($val, 0, 255) : null;
      }
      $model->$field = $val;
  }
  ```
- Override `cleanEntityFields(array $row): array` in `mod_news_mod`:
  ```php
  protected function cleanEntityFields(array $row): array
  {
      $row = parent::cleanEntityFields($row);
      foreach (['image_alt', 'image_credit'] as $field) {
          if (isset($row[$field]) && is_string($row[$field])) {
              $row[$field] = html_entity_decode(
                  html_entity_decode($row[$field], ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                  ENT_QUOTES | ENT_HTML5,
                  'UTF-8'
              );
          }
      }
      return $row;
  }
  ```

#### B. `mod/index/helper/mod_news.helper.php`
- In `_cleanNewsRow(array $row): array` (lines 32–36):
  ```php
  foreach (['name', 'title', 'summary', 'description', 'keywords', 'category_name', 'image_alt', 'image_credit'] as $field) {
      if (isset($row[$field]) && is_string($row[$field])) {
          $row[$field] = $this->_cleanText($row[$field]);
      }
  }
  ```

### Component 4: `lib/McpNewsTools.class.php`
- In `create_news` JSON schema properties (line 224):
  ```php
  'image_alt' => [
      'type' => 'string',
      'description' => 'Optional descriptive alt text for featured image (max 255 chars). Defaults to article name if omitted.',
  ],
  'image_credit' => [
      'type' => 'string',
      'description' => 'Optional photo credit or provenance attribution (max 255 chars, e.g. "Fotoğraf: Rûdaw / Arşiv").',
  ],
  ```
- In `update_news` JSON schema properties (line 343):
  ```php
  'image_alt' => [
      'type' => 'string',
      'nullable' => true,
      'description' => 'Descriptive alt text for featured image (max 255 chars). Pass null or empty string to clear.',
  ],
  'image_credit' => [
      'type' => 'string',
      'nullable' => true,
      'description' => 'Photo credit or provenance attribution (max 255 chars). Pass null or empty string to clear.',
  ],
  ```
- In `createNews(array $args): array` (lines 860–870):
  ```php
  $model->image_alt = isset($args['image_alt']) && trim((string) $args['image_alt']) !== ''
      ? mb_substr(trim(strip_tags((string) $args['image_alt'])), 0, 255)
      : null;
  $model->image_credit = isset($args['image_credit']) && trim((string) $args['image_credit']) !== ''
      ? mb_substr(trim(strip_tags((string) $args['image_credit'])), 0, 255)
      : null;
  ```
- In `updateNews(array $args): array` (lines 1106–1108):
  ```php
  if (array_key_exists('image_alt', $args)) {
      $model->image_alt = $args['image_alt'] !== null && trim((string) $args['image_alt']) !== ''
          ? mb_substr(trim(strip_tags((string) $args['image_alt'])), 0, 255)
          : null;
  }
  if (array_key_exists('image_credit', $args)) {
      $model->image_credit = $args['image_credit'] !== null && trim((string) $args['image_credit']) !== ''
          ? mb_substr(trim(strip_tags((string) $args['image_credit'])), 0, 255)
          : null;
  }
  ```

### Component 5: Validators (`mod/mod_news/validator/add.validator.php` & `edit.validator.php`)
- Line 141 (in `add.validator.php`) & Line 140 (in `edit.validator.php`):
  Update comment: `// Normalize SEO slug (AGENTS.md Rule 9)`.
- Length checks (after `description` or `keywords` check):
  ```php
  // image_alt Length (optional)
  if (trim((string) Req::Request('image_alt')) !== '') {
      $this->valid->add(
          [
              'name' => 'image_alt',
              'func' => 'length',
              'arg' => [
                  'min' => '1',
                  'max' => '255'
              ],
              'message' => sprintf($this->lang['mod_news']['validator']['length'], $this->lang['mod_news']['admin']['columns']['image_alt'], '1', '255')
          ]
      );
      if ($this->_check() === True) {
          return True;
      }
  }
  // image_credit Length (optional)
  if (trim((string) Req::Request('image_credit')) !== '') {
      $this->valid->add(
          [
              'name' => 'image_credit',
              'func' => 'length',
              'arg' => [
                  'min' => '1',
                  'max' => '255'
              ],
              'message' => sprintf($this->lang['mod_news']['validator']['length'], $this->lang['mod_news']['admin']['columns']['image_credit'], '1', '255')
          ]
      );
      if ($this->_check() === True) {
          return True;
      }
  }
  ```

### Component 6: Language Files (`mod/mod_news/lang/`)
Add to `'admin' => [ 'columns' => [ ... ] ]`:
- `en.php`:
  ```php
  'image_alt' => 'Image Alt Text',
  'image_credit' => 'Image Credit / Source',
  ```
- `tr.php`:
  ```php
  'image_alt' => 'Görsel Açıklaması (Alt Metin)',
  'image_credit' => 'Fotoğraf / Kaynak',
  ```
- `ku.php`:
  ```php
  'image_alt' => 'Daxuyaniya Wêneyê (Nivîsa Alt)',
  'image_credit' => 'Çavkaniya Wêneyê / Maf',
  ```
- And matching definitions in `sv.php`, `fa.php`, `ar.php`, `ckb.php`, `ru.php`, `fr.php`, `zza.php`, `de.php`.

### Component 7: Admin Templates & Wizard
#### A. `templates/admin/mod_news/edit.twig`
Inside Card 3 (Media & Relations, line 249 directly below `image_uploader.twig`):
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

#### B. `templates/admin/partials/wizard_content.twig`
In `wizardStep7` (Image step, after line 194):
```twig
            {% if wizard_lang_key == 'mod_news' %}
            <div class="form-row mt-3">
                <div class="m3-input-wrapper">
                    <input type="text" class="m3-input-lg" name="image_alt" id="wizardImageAltInput" placeholder=" " autocomplete="off" maxlength="255">
                    <label class="m3-floating-label">{{ wizard_lang.admin.columns.image_alt }}</label>
                </div>
            </div>
            <div class="form-row">
                <div class="m3-input-wrapper">
                    <input type="text" class="m3-input-lg" name="image_credit" id="wizardImageCreditInput" placeholder=" " autocomplete="off" maxlength="255">
                    <label class="m3-floating-label">{{ wizard_lang.admin.columns.image_credit }}</label>
                </div>
            </div>
            {% endif %}
```

#### C. `templates/admin/assets/js/script.js`
In the wizard submission handler (around line 3103):
```javascript
    const altInput = document.getElementById('wizardImageAltInput');
    if (altInput && altInput.value.trim() !== '') {
        formData.append('image_alt', altInput.value.trim());
    }
    const creditInput = document.getElementById('wizardImageCreditInput');
    if (creditInput && creditInput.value.trim() !== '') {
        formData.append('image_credit', creditInput.value.trim());
    }
```

### Component 8: Public News Template (`templates/amed/index/mod_news/news.twig`)
1. **OpenGraph & Twitter meta tags** (lines 75 & 106):
   ```twig
   {% if d.image is not empty %}
       <meta property="og:image:width" content="1280">
       <meta property="og:image:height" content="720">
       <meta property="og:image:alt" content="{{ (d.image_alt|default(d.name))|escape('html_attr') }}">
   {% else %}
   ...
   <meta name="twitter:image" content="{{ news_share_image }}">
   <meta name="twitter:image:alt" content="{{ (d.image_alt|default(d.name))|escape('html_attr') }}">
   ```

2. **Schema.org JSON-LD `ImageObject`** (lines 160–177):
   ```twig
   "image": [
     {% if d.image is not empty %}
     {
       "@type": "ImageObject",
       "url": {{ (d.image_16x9|default(site.iaddress ~ 'mod_news/' ~ d.image))|json_ld|raw }},
       "width": 1280,
       "height": 720,
       "caption": {{ (d.image_alt|default(d.name))|json_ld|raw }}{% if d.image_credit is defined and d.image_credit is not empty %},
       "creditText": {{ d.image_credit|json_ld|raw }}{% endif %}
     },
     {
       "@type": "ImageObject",
       "url": {{ (d.image_4x3|default(site.iaddress ~ 'mod_news/4x3/' ~ d.image))|json_ld|raw }},
       "width": 1200,
       "height": 900,
       "caption": {{ (d.image_alt|default(d.name))|json_ld|raw }}{% if d.image_credit is defined and d.image_credit is not empty %},
       "creditText": {{ d.image_credit|json_ld|raw }}{% endif %}
     },
     {
       "@type": "ImageObject",
       "url": {{ (d.image_1x1|default(site.iaddress ~ 'mod_news/1x1/' ~ d.image))|json_ld|raw }},
       "width": 1200,
       "height": 1200,
       "caption": {{ (d.image_alt|default(d.name))|json_ld|raw }}{% if d.image_credit is defined and d.image_credit is not empty %},
       "creditText": {{ d.image_credit|json_ld|raw }}{% endif %}
     }
   ```

3. **Hero Figure & Caption** (lines 411–417):
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

### Component 9: Theme CSS (`templates/amed/static/css/detail-foundation.css`)
Add lines following `.detail-hero-image` (around line 214):
```css
.news-hero-caption {
  padding: 0.5rem 1rem;
  font-size: 0.825rem;
  line-height: 1.4;
  color: var(--bs-secondary-color, #64748b);
  background: rgba(0, 0, 0, 0.025);
  border-top: 1px solid rgba(0, 0, 0, 0.06);
  font-style: italic;
  margin: 0;
}

[data-bs-theme="dark"] .news-hero-caption {
  color: #94a3b8;
  background: rgba(255, 255, 255, 0.03);
  border-top: 1px solid rgba(255, 255, 255, 0.08);
}
```

---

## 5. Verification Method

Once implemented, the changes must be verified using the following concrete steps:

1. **PHP Syntax Validation**:
   ```bash
   php -l mod/mod_news/mod_news.sql # verify SQL syntax via mysql or dry-run
   php -l mod/mod_news/model/mod_news.model.php
   php -l mod/mod_news/model/newses.model.php
   php -l mod/mod_news/index.php
   php -l mod/index/helper/mod_news.helper.php
   php -l lib/McpNewsTools.class.php
   php -l mod/mod_news/validator/add.validator.php
   php -l mod/mod_news/validator/edit.validator.php
   for f in mod/mod_news/lang/*.php; do php -l "$f"; done
   ```

2. **Repository Quality Guard Validation**:
   ```bash
   python3 tools/code_separation_guard.py --path templates/admin/mod_news/
   python3 tools/code_separation_guard.py --path templates/admin/partials/wizard_content.twig
   python3 tools/code_separation_guard.py --path templates/amed/index/mod_news/news.twig
   python3 tools/code_separation_guard.py --path templates/amed/static/css/detail-foundation.css
   python3 tools/i18n_guard.py --path mod/mod_news/
   python3 tools/module_boundary_guard.py --all
   ```
   **Expected**: 0 violations across all quality guards.

3. **Database Schema & View Invalidation Verification**:
   Execute inside `bulletin_app` container:
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
   **Expected**: All 4 checks report `EXISTS`.

4. **MCP Tool Integration Verification**:
   Run MCP smoke test or call via `tools/mcp_cli.py`:
   - Call `create_news` passing `"image_alt": "Alt text test"`, `"image_credit": "AA / Arşiv"`.
   - Call `get_news` by created ID and verify:
     - `response.item.image_alt === "Alt text test"`
     - `response.item.image_credit === "AA / Arşiv"`
   - Call `update_news` modifying `image_credit` and verify update succeeds.

5. **Public Template Render Verification**:
   Inspect generated HTML for an article with image credit:
   - Verify `<figure class="detail-hero">` wraps the hero image.
   - Verify `<img ... alt="Alt text test">`.
   - Verify `<figcaption class="news-hero-caption">AA / Arşiv</figcaption>`.
   - Verify `<script type="application/ld+json">` contains `"caption": "Alt text test"` and `"creditText": "AA / Arşiv"`.
