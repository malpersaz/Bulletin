# Post-v1.0.52 Deployment Audit — Unified Remediation Plan

**Target Domain:** `https://amedbulten.com`
**Repository:** `AF/Bulletin`
**Target Version:** `v1.0.53 (In Progress)`
**Date:** 2026-11-08
**Source Audit:** Unified live production audit of `v1.0.52` (`amedbulten.com` desktop & `390x844` mobile DevTools emulation, Lighthouse mobile audit, MCP `amed-bulletin` database inspection, `2fbaab2f` git diff verification, and codebase quality guards) combining valid OMP CLI findings with Antigravity deep-inspection discoveries.

---

## 1. Unified Audit Findings Baseline

| # | Finding | Severity | Status | Evidence |
|---|---------|----------|--------|----------|
| **F1** | **Hero Image (`16:9`) Vertical Distortion on Mobile & Desktop**: `.detail-hero-image` renders at `327x720px` (ratio `0.45`) on `390px` mobile and `720x720px` (`1:1`) on desktop because `height="720"` HTML attribute overrides `aspect-ratio: 16 / 9` when `height: auto` is absent in CSS | **P0 (Critical UI/CWV)** | **OPEN** | Live Chrome DevTools (`390x844` & `1440x900`); `templates/amed/index/mod_news/news.twig:388` sets `width="1280" height="720"`; `templates/amed/static/css/detail-foundation.css:207-211` omits `height: auto; display: block;` |
| **F2** | **SEO Metadata (`title`, `description`, `keywords`) Punctuation Stripping & Brand-Suffix Duplication**: (a) `NewsEditManager::initTitleSync()` in `mod_news.js` runs `sanitizeText()` (`/[^\p{L}\p{N}\s]+/gu`) on `#title` and `#description` and overwrites `#keywords` without checking `dataset.touched`, turning `id=91` `<title>` into `"Galatasaray 4 3 Amedspor Maç çekişmeli geçti - Amed Bülten"` and `<meta name="description">` into `"Galatasaray Süper Lig in 11 haftasında Amedspor u 4 3 yendi..."`; (b) `id=92` `title` carries a baked-in `" \| Amed Bülten"` suffix, producing `"... \| Amed Bülten - Amed Bülten"`; (c) `author.twig` renders `item.title` instead of `item.name` on `/tr/author/habermerkezi` | **P0 (Critical SEO/SERP)** | **OPEN** | Live HTML & MCP `get_news` (`id=91`, `id=92`); `templates/admin/assets/js/mod_news.js:1960-1981`; `templates/admin/assets/js/script.js:3-10`; `templates/amed/index/mod_publication/author.twig:249,290` |
| **F3** | **Fatal SQL Crash on Real Author (`Person`) News Articles**: `mod_news.helper.php:255` runs `SELECT link FROM mod_user_social WHERE user_id = {$userId} AND status = 'y'`, but `mod_user_social` columns are `url` and `is_active` (`mod_user_social.model.php:45-55`), and `Database::query()` calls `$e->show(); exit();` on `DatabaseException` | **P0 (Critical Runtime Crash)** | **OPEN** | `mod/index/helper/mod_news.helper.php:250-268` vs `mod/mod_user/model/mod_user_social.model.php:45-55` & `lib/Database/Database.php:127-131`. Also `news.twig:169-188` omits `"@id"` in `author` (`NewsMediaOrganization`) and `worksFor` |
| **F4** | **Static & Assets Subdomains Return HTTP 500 on Missing Files**: `https://static.amedbulten.com/...` and `https://assets.amedbulten.com/...` inherit root `/.htaccess:52-54` (`RewriteRule ^(.*)$ index.php [QSA,L]`), triggering a 10-redirect loop (`AH00124`) to a non-existent `index.php` inside `/static/` and `/templates/` | **P1 (Infrastructure / SEO)** | **OPEN** | Live curl header probe confirms root `/.htaccess:57-61` security headers on `static.amedbulten.com` 500 responses; `static/.htaccess` and `templates/.htaccess` lack `RewriteEngine Off` |
| **F5** | **Lighthouse Mobile Accessibility Failures (`98/100`) & Outbound Link Diversity on `id=91`**: (a) `<h6 class="detail-related-title">` under `<h2>` in `news.twig:530` & `announcement.twig:333` fails `heading-order`; (b) `.news-byline-avatar-initials` inside `aria-label` link in `news.twig:319` fails `label-content-name-mismatch`; (c) `id=91` cites the same outbound URL twice instead of 2 distinct authority sources | **P1 (A11y / Editorial SEO)** | **OPEN** | Lighthouse mobile audit (`report.json`); `templates/amed/index/mod_news/news.twig:319,530`; `templates/amed/index/mod_announcements/announcement.twig:333` |
| **F6** | **Native 4-Derivative Upload Pipeline Verification & Tooling Edge Cases**: (a) No new article uploaded since `v1.0.52` deploy; (b) `tasks/recompress_news_images.php:140` re-encodes all `/thumb/` files unconditionally on `--ratios`; (c) `mod/image/index.php:217` (`_generate_url`) omits `'static' . DS`; (d) `.deployignore:44` excludes `tasks/recompress_*` | **P1 (Media Pipeline)** | **OPEN** | `mod/image/index.php:217`; `tasks/recompress_news_images.php:139-152`; `.deployignore:44` |
| **F7** | **`quality_meter` i18n Dictionary Gap across 8 Locales**: `quality_meter` (10 leaf keys) exists in `en`, `ku`, `tr` but is missing in `ar`, `ckb`, `de`, `fa`, `fr`, `ru`, `sv`, `zza` | **P2 (i18n Parity)** | **OPEN** | `python3 tools/i18n_guard.py --path mod/mod_news/lang` (`REQUIRED_LOCALES` checks all 11 files including `fa.php`) |
| **F8** | **`code_separation_guard.py` Violations in `templates/admin/mod_imagecategories/list.twig`**: `list.twig` contains 10 guard violations (lines `15, 16, 18, 45, 61-96, 100, 112, 120, 124, 317`) plus an inline `<script>` block (`lines 137-374`) | **P2 (Code Separation)** | **OPEN (pre-existing)** | `python3 tools/code_separation_guard.py --path templates/admin/mod_imagecategories/list.twig` |
| **F9** | **`docs/VERSION.md` v1.0.52 Inventory Omissions**: `v1.0.52` entry omits `mod/mod_user/index.php`, `templates/admin/assets/js/mod_user_modules.js`, `templates/admin/mod_user/panel.twig`, `cache/.htaccess`, and `docs/plan/` cleanup | **P2 (Docs Parity)** | **OPEN (docs)** | `git show 2fbaab2f --stat` vs `docs/VERSION.md` |

---

## 2. Detailed Implementation Specifications (Severity-First Execution Order: C3 → C2 → C1 → C4 → C5 → C6 → C7 → C8)

### Component 1 (was C3): Author `Person` Schema & `mod_user_social` Fatal SQL Crash Fix (F3 — P0 Runtime Crash)

1. **[MODIFY] `mod/index/helper/mod_news.helper.php` (`lines 250–268`)**
   - Replace the raw `$db->fetch_array("SELECT link FROM mod_user_social WHERE user_id = {$userId} AND status = 'y'")` block (and its misleading `catch` comment, since `Database::query()` calls `exit()` on SQL error) with `Load::model('mod_user.mod_user_social')` (whose constructor guarantees `ensureTableExists()`), querying `where_user_id_and_is_active($userId, 'y')->order('display_order', 'ASC')->gR()` and reading `$sRow['url']` (matching `mod_publication.helper.php:637–654`).
2. **[MODIFY] `templates/amed/index/mod_news/news.twig` (`lines 169–188`)**
   - Add `"@id": {{ (site.saddress ~ '#organization')|json_ld|raw }},` to both the `author` (`NewsMediaOrganization` branch) and `worksFor` (`Person` branch) JSON-LD objects.
3. **[VERIFY — Production]**
   - Verify `author: {"@type": "Person"}` JSON-LD output with a whitelisted author account (`Gazeteci`, `Yazar`, `Editör`, `Muhabir`, `Köşe Yazarı`), avatar (`static/mod_user/thumb/`), `sameAs`, and `worksFor`.

### Component 2: SEO Metadata (`title`, `description`, `keywords`) Punctuation Preservation, Brand-Suffix Sanitization & Data Sweep (F2, F5 — P0 SEO/SERP)

1. **[MODIFY] `templates/admin/assets/js/script.js` & `templates/admin/assets/js/mod_news.js` (`lines 1927–1983`, `NewsEditManager::initTitleSync`)**
   - Define `sanitizeMetaText(text)` in `templates/admin/assets/js/script.js` that strips emoji and control/format characters while preserving punctuation (`:`, `-`, `'`, `.`, `,`, `%`, `(`, `)`, `?`, `!`):
     ```js
     function sanitizeMetaText(text) {
         if (!text) return "";
         let clean = String(text).replace(/[\p{Extended_Pictographic}\p{Cc}\p{Cf}]+/gu, " ").trim();
         return clean.replace(/\s+/g, " ");
     }
     ```
     *(Keep `sanitizeText()` intact for `makeSeoValue()` at `script.js:83` and `makeKeywordsValue()` at `script.js:117` so URL slugs and keyword tokens continue to strip punctuation).*
   - In `NewsEditManager::initTitleSync()` (`templates/admin/assets/js/mod_news.js:1927–1983`):
     - Add `input` listeners on `#title` and `#keywords` setting `dataset.touched = 'true'`.
     - On `#editTitleInput` input: only auto-fill `#title` when `!titleMeta.value || titleMeta.dataset.touched !== 'true'`, using `sanitizeMetaText(name)` (preserving colons, hyphens, and apostrophes while blocking emoji/control chars); only auto-fill `#keywords` when `!keywordsInput.value || keywordsInput.dataset.touched !== 'true'`.
     - On `#editIntroInput` input: only auto-fill `#description` when `!descriptionInput.value || descriptionInput.dataset.touched !== 'true'`, using `sanitizeMetaText(introText).substring(0, 160)`.
2. **[MODIFY] `mod/mod_news/validator/add.validator.php`, `mod/mod_news/validator/edit.validator.php`, `mod/mod_news/index.php`, `lib/McpNewsTools.class.php`, `lib/AIService.class.php`**
   - Strip trailing brand suffix pattern `/\s*[|–—-]\s*Amed\s*B[üu]lten(?:i)?\s*$/iu` from `title` (falling back to `name` if empty):
     - In `add.validator.php` and `edit.validator.php`: mutate `$_POST['title']` **before** line 108 `required` validation.
     - In `mod_news_mod::cleanNewsTextEntities()` (`mod/mod_news/index.php:108–127`): apply the same brand-suffix strip after entity decoding.
     - In `McpNewsTools::createNews()` (`line 738`) and `updateNews()` (`lines 898–906`): strip trailing brand suffix on `title`.
     - In `AIService::generateSeoMeta()` (`lib/AIService.class.php:328`): strip trailing brand suffix from `$cleanSeoTitle`.
3. **[MODIFY] `templates/amed/index/mod_publication/author.twig` (`lines 249, 290`)**
   - Change `{% set news_title = item.title is defined and item.title is not empty ? item.title : item.name %}` to `{% set news_title = item.name is defined and item.name is not empty ? item.name : item.title %}` so author profile streams always display the punctuated editorial headline `item.name`.
4. **[DATA — Production MCP & Local Docker DB Deterministic Sweep]**
   - Run the deterministic detection query across `mod_news`:
     ```sql
     SELECT id, name, title, summary, description, keywords FROM mod_news
      WHERE title REGEXP '[[:space:]][0-9][[:space:]][0-9][[:space:]]'
         OR title REGEXP 'Amed[[:space:]]*B[üu]lten[[:space:]]*$'
         OR (description <> '' AND description NOT REGEXP '[[:punct:]]')
         OR (name REGEXP '[[:punct:]]' AND title NOT REGEXP '[[:punct:]]');
     ```
   - Update `id=92` via `mcp__amed-bulletin__update_news`: strip `" | Amed Bülten"` from `title`.
   - Update `id=91` (`galatasaray_4_3_amedspor_maç_çekişmeli_geçti`) via `mcp__amed-bulletin__update_news`:
     - `title`: `"Galatasaray 4-3 Amedspor: Maç çekişmeli geçti"`
     - `description`: `"Galatasaray, Süper Lig'in 11. haftasında Amedspor'u 4-3 yendi; beklenmedik bir maç yaşandı."`
     - `keywords`: `"Galatasaray, Amedspor, Süper Lig, RAMS Park, Galatasaray Amedspor maçı"`
     - Diversify the second outbound link in `content` so the article cites 2 distinct live authority URLs per `kevseo`/`kevrast`.
   - Restore clean punctuated `title` and `description` on all remaining rows matched by the predicate (`id=87`, `id=85`, etc.); acceptance = 0 rows remaining.

### Component 3 (was C1): Mobile & Desktop Hero Image (`16:9`) Aspect-Ratio Fix & Detail Accessibility (F1, F5 — P0 UI/CWV & A11y)

1. **[MODIFY] `templates/amed/static/css/detail-foundation.css` (`lines 207–211`)**
   - Update `.detail-hero-image` to:
     ```css
     .detail-hero-image {
       width: 100%;
       height: auto;
       aspect-ratio: 16 / 9;
       object-fit: cover;
       display: block;
     }
     ```
2. **[MODIFY] `templates/amed/index/mod_news/news.twig` (`lines 319, 530`) & `templates/amed/index/mod_announcements/announcement.twig` (`line 333`)**
   - Change `<h6 class="detail-related-title">...</h6>` to `<h3 class="detail-related-title">...</h3>` in both templates.
   - Add `aria-hidden="true"` to `<div class="news-byline-avatar news-byline-avatar-initials" aria-hidden="true">` in `news.twig:319`.

### Component 4: Static & Assets Subdomain HTTP 404 Semantics (F4)

1. **[MODIFY] `static/.htaccess` & `templates/.htaccess`**
   - Prepend at the top of both files:
     ```apache
     <IfModule mod_rewrite.c>
         RewriteEngine Off
     </IfModule>
     ```
2. **[MODIFY] `.htaccess` (`line 51`)**
   - Before `RewriteCond %{REQUEST_FILENAME} !-f`, add:
     ```apache
     # Do not route missing static or template files through the PHP front controller
     RewriteRule ^(static|templates)/ - [L]
     ```

### Component 5: Native 4-Derivative Image Pipeline Verification & Tooling Hardening (F6)

1. **[MODIFY] `mod/image/index.php` (`line 217`)**
   - Update `_generate_url()` collision path from `$file = root_dir . $this->mod . DS . $url;` to `$file = root_dir . 'static' . DS . $this->mod . DS . $url;`.
2. **[MODIFY] `tasks/recompress_news_images.php` (`lines 140–152`)**
   - Guard `/thumb/` generation inside `--ratios` with `if ($forceRatios || !file_exists($thumbPath) || filesize($thumbPath) === 0)` so existing thumbnails are not re-encoded on idempotent runs.
3. **[MODIFY] `.deployignore` (`line 44`)**
   - Remove `tasks/recompress_*` exclusion so `tasks/recompress_news_images.php` deploys to production while `static/mod_*/**` remains protected.

### Component 6: `quality_meter` 11-Locale Dictionary Parity (F7)

1. **[MODIFY] `mod/mod_news/lang/{ar,ckb,de,fa,fr,ru,sv,zza}.php`**
   - Add the 10-key `quality_meter` block (`title`, `chars`, `words`, `subheadings`, `min_chars`, `ideal_chars`, `min_h2`, `status_poor`, `status_good`, `status_excellent`) with native translations in all 8 files.
   - Verify with `python3 tools/i18n_guard.py --path mod/mod_news/lang`.

### Component 7: Admin `mod_imagecategories/list.twig` Full Code Separation (F8)

1. **[MODIFY] `templates/admin/mod_imagecategories/list.twig`, `templates/admin/assets/css/`, `templates/admin/assets/js/`**
   - Extract all 10 `code_separation_guard.py` violations (inline `onclick` handlers on lines `15, 16, 18, 45, 100, 120, 124`, inline `<style>` block on lines `61–96`, inline `style` attributes on lines `112, 317`) and the inline `<script>` block (`lines 137–374`) into external CSS/JS assets.
   - Verify with `python3 tools/code_separation_guard.py --path templates/admin/mod_imagecategories/list.twig`.

### Component 8: Version History Inventory Accuracy & Framework Version Parity (F9)

1. **[MODIFY] `docs/VERSION.md` (v1.0.52 section)**
   - Append the omitted `2fbaab2f` changes to the `v1.0.52` header and component bullets:
     - `mod/mod_user/index.php`: multilingual search `keywords` arrays across all 31 admin modules in `getModuleDefinitions()` plus direct `add` action routes for `mod_faq_mod` and `mod_redirect`.
     - `templates/admin/assets/js/mod_user_modules.js` (`v=1.6` in `templates/admin/mod_user/panel.twig`): multi-word whitespace-tokenized admin module search filter.
     - `cache/.htaccess`: file permission normalization.
     - `docs/plan/`: removal of 6 superseded planning documents.
2. **[MODIFY] `config/site.config.php`, `config/site.config.example.php`, `docs/VERSION.md`**
   - Bump framework version to `1.0.53` and record Components 1–8 in `docs/VERSION.md` in English per `AGENTS.md` rules.

---

## 3. Deterministic Verification Plan

```bash
# 1. PHP Syntax & Quality Guards (workspace)
php -l mod/index/helper/mod_news.helper.php
php -l mod/mod_news/index.php
php -l mod/mod_news/validator/add.validator.php
php -l mod/mod_news/validator/edit.validator.php
php -l lib/McpNewsTools.class.php
php -l lib/AIService.class.php
php -l mod/image/index.php
php -l tasks/recompress_news_images.php
python3 tools/code_separation_guard.py --path templates/admin/mod_imagecategories/list.twig
python3 tools/code_separation_guard.py --path templates/amed/index/mod_news/news.twig
python3 tools/i18n_guard.py --path mod/mod_news/lang
python3 tools/module_boundary_guard.py
python3 tools/min_css_guard.py

# 2. Title & Meta Punctuation + Brand-Suffix Hygiene (production)
curl -s 'https://amedbulten.com/tr/news/galatasaray_4_3_amedspor_ma%C3%A7_%C3%A7eki%C5%9Fmeli_ge%C3%A7ti' \
  | grep -oE '<title>[^<]*</title>'
# Expect: <title>Galatasaray 4-3 Amedspor: Maç çekişmeli geçti - Amed Bülten</title>

curl -s 'https://amedbulten.com/tr/news/abd_ara_se%C3%A7im_yenilgisi_cumhuriyet%C3%A7iler_trump_sonras%C4%B1_aray%C4%B1%C5%9Fta' \
  | grep -oE '<title>[^<]*</title>'
# Expect single "- Amed Bülten" suffix, zero "| Amed Bülten - Amed Bülten"

# 3. Static & Assets Subdomain 404 Semantics (production)
curl -s -o /dev/null -w '%{http_code}\n' https://static.amedbulten.com/mod_news/definitely_missing_xyz.webp   # expect 404
curl -s -o /dev/null -w '%{http_code}\n' https://assets.amedbulten.com/definitely_missing_xyz.css            # expect 404

# 4. Mobile Hero Aspect Ratio (390x844 Chrome DevTools verification)
# .detail-hero-image clientWidth / clientHeight must equal ~1.78 (16:9), e.g. 327x184px (NOT 327x720px)
# Lighthouse Mobile: Accessibility = 100, Best Practices = 100, SEO = 100

# 5. Native 4-derivative gate (production, first post-deploy upload)
ssh -o BatchMode=yes ssh-w02232db@w02232db.kasserver.com \
  'for d in "" 4x3 1x1 thumb; do ls /www/htdocs/w02232db/amedbulten.com/static/mod_news/$d | wc -l; done'
# Expect all four counts to increase by 1 vs pre-upload; the new article's JSON-LD
# "image" array must contain 3 static-origin URLs and zero "/r/" fallback URLs.
```

---

## 4. Derivative Rebuild Runbook (Server-Side, Sanctioned)

```bash
ssh ssh-w02232db@w02232db.kasserver.com
cd /www/htdocs/w02232db/amedbulten.com
php tasks/recompress_news_images.php --ratios          # idempotent; fills missing 4x3/1x1 & missing thumbs
php tasks/recompress_news_images.php --ratios --force  # full regeneration (destructive to derivatives only)
```

---

## 5. Independent Review Record — Concessions, Dissent & Binding Amendments

**Reviewer:** omp — trusted coding assistant (model: `zai/glm-5.3`)
**Reviewed:** Argon's unified revision of this plan (2026-11-08)
**Method:** every claim below was independently re-derived from live `amedbulten.com` probes (2026-11-08), the live stylesheet inventory of the news detail page, `git 2fbaab2f`, and direct source reads cited as `file:line`. Nothing below is trusted from the revision text alone.

### 5.1 Verified and endorsed

| Finding | Independent evidence | Verdict |
|---|---|---|
| **F3 (P0, fatal SQL)** | `mod/index/helper/mod_news.helper.php:255` queries `` `link` … `status` ``; the real schema is `url` + `is_active` (`mod/mod_user/model/mod_user_social.model.php:45-55`). `lib/Database/Database.php:127-131` runs `$e->show(); exit();` **inside the library catch**, so the helper-level `try/catch (\Throwable)` (helper:252-267) can never intercept — the process dies on the first Person-authored article. Component 3.1 mirrors the proven pattern at `mod_publication.helper.php:637-640` (identical `where_user_id_and_is_active()->order('display_order','ASC')->gR()` + `$row['url']`). | **CONFIRMED.** Strongest finding in the revision |
| **F2 (P0, metadata damage)** | Live id=91: `<title>Galatasaray 4 3 Amedspor Maç çekişmeli geçti - Amed Bülten</title>`, `description="Galatasaray Süper Lig in 11 haftasında Amedspor u 4 3 yeni…"`, `keywords="galatasaray,4,3,amedspor,…"`. Exact signature of `templates/admin/assets/js/script.js:4` (`/[^\p{L}\p{N}\s]+/gu → " "`). In `initTitleSync()` (`mod_news.js:1927-1990`), `#title` and `#keywords` are overwritten with **no** `dataset.touched` guard (only `#seo` at :1957 and `#description` at :1977 are guarded). `author.twig:249,290` prefers `item.title` over `item.name`. | **CONFIRMED** (Components 2.1-2.4 endorsed, subject to A1/A4) |
| **F1 (P0, hero distortion)** | Live detail-page stylesheet inventory scanned rule-by-rule: the **only** unscoped element-level `img` rule is Bootstrap's `img{vertical-align:middle}`; `.detail-hero-image` (`detail-foundation.css:207-211`) has `width:100%; aspect-ratio:16/9; object-fit:cover` and **no** `height` declaration, while `news.twig:388` emits `height="720"`. The attribute presentational hint therefore supplies a definite 720px height, `aspect-ratio` is ignored, and `object-fit:cover` crops — box ≈ 327×720 on 390px viewport. | **CONFIRMED.** Fix as specified (subject to A2) |
| **F4 (P1, static 500)** | Root `.htaccess:51-54` front-controller catch-all inherits into `/static/` and `/templates/`: both `static/.htaccess` and `templates/.htaccess` contain only `Options -Indexes`, `FilesMatch` deny, and `mod_expires` directives — **no** `RewriteEngine` directive. `RewriteEngine Off` prepend is non-destructive. | **CONFIRMED** |
| **F5, F6(b,c,d)** | `news.twig:530` & `announcement.twig:333` `<h6>` under `<h2>`; `news.twig:319` initials div without `aria-hidden`; `mod/image/index.php:217` `$file = root_dir . $this->mod . DS . $url;` (no `'static' . DS`); `tasks/recompress_news_images.php:139-152` re-encodes `/thumb/` unconditionally in `--ratios`; `.deployignore:44` `tasks/recompress_*`. | **CONFIRMED** |
| **F7, F8, F9** | i18n arithmetic: **10 leaf keys × 8 locales** is correct. Guard re-run: 10 violations + inline `<script>` (137-374). Commit stat includes `mod_user_modules.js` and `mod_user/panel.twig`. | **CONFIRMED** |

### 5.2 Concessions logged

1. My baseline classified the Person-author surface as "untested" (former F7). Argon's escalation to **deterministically fatal** is correct and better; F3 as written stands.
2. My earlier i18n count ("11 leaf keys, 7 locales") was wrong; the revision's 10 × 8 (including `fa`) is correct.
3. F1's CSS failure mode and F2's JS root cause were outside my original audit's sampling; both verified true.

### 5.3 Dissent — binding amendments

Each amendment is falsifiable. To reject one, present the counter-evidence named in it.

**A1 — Component 2.1 must not drop `sanitizeText()` wholesale; replace its character class.**
`sanitizeText()` (`script.js:3-11`) strips everything outside `\p{L}\p{N}\s` — which incidentally also strips **emoji and control/format characters**, the exact class project editorial standards forbid in metadata (kevseo SKILL Rule 8; v1.0.52 validators reject emoji in `name`/`content`). The revision's "set `titleMeta.value = name.trim()` directly **without** `sanitizeText()`" re-opens the emoji/control channel into SEO `title`/`description`.
*Amendment:* keep the call, change the regex to preserve punctuation while retaining the emoji/control defense:
```js
// preserves : - ' . , % ( ) etc.; still removes emoji + control/format
let clean = text.replace(/[\p{Extended_Pictographic}\p{Cc}\p{Cf}]+/gu, " ").trim();
return clean.replace(/\s+/g, " ");
```
*Falsification:* name one character the old regex removed that this permits **and** that is illegal in SEO metadata. No such character exists — punctuation is precisely the class that must survive.

**A2 — Component 1.1: strike the `detail-foundation.min.css` step; the file does not exist.**
`templates/amed/static/css/` contains no `detail-foundation.min.css`; the live page loads `detail-foundation.css?v=1.0.52` raw, and `tools/min_css_guard.py`'s bundle list does not include `detail-foundation.css` (it covers `custom`, `hero-news-slider`, `homepage-bundle`, `homepage-all`, `latest-news`, `sports-news`, `world-news`, `economy-news` …). The step "Re-minify `detail-foundation.min.css` and verify with `min_css_guard`" references a nonexistent artifact and an uncovered path.
*Amendment:* modify `detail-foundation.css` only. If a minified variant is desired, register it in `min_css_guard.py` and switch the template to load it — as a separate task, not smuggled into this hotfix.

**A3 — §3 omits the verification for the revision's own F6(a).**
F6(a) declares the native 4-derivative upload path unverified, yet §3 contains no command that verifies it.
*Amendment — append to §3:*
```bash
# 5. Native 4-derivative gate (production, first post-deploy upload)
ssh -o BatchMode=yes ssh-w02232db@w02232db.kasserver.com \
  'for d in "" 4x3 1x1 thumb; do ls /www/htdocs/w02232db/amedbulten.com/static/mod_news/$d | wc -l; done'
# expect all four counts to increase by 1 vs pre-upload; the new article's JSON-LD
# "image" array must contain 3 static-origin URLs and zero "/r/" fallback URLs.
```

**A4 — Component 2.4's sweep ("id=87, id=85, etc.") has no detection predicate.**
*Amendment — deterministic detection before manual restore:*
```sql
SELECT id, name, title, description FROM mod_news
 WHERE title REGEXP '[[:space:]][0-9][[:space:]][0-9][[:space:]]'          -- "4 3" score casualty
    OR title REGEXP 'Amed[[:space:]]*B[üu]lten[[:space:]]*$'              -- baked brand suffix
    OR (description <> '' AND description NOT REGEXP '[[:punct:]]');      -- fully de-punctuated
```
Every hit is confirmed by eye against `name` before update; acceptance = 0 rows.

**A5 — Component 3.1 leaves a lying safety comment.**
After the query fix, the surviving `catch (\Throwable) { /* Non-fatal if social table is missing */ }` (helper:265-267) stays misleading: `Database.php:130` `exit()` makes any residual DB error fatal regardless of that catch — the exact trap that let F3 ship.
*Amendment:* delete the try/catch, or replace the comment with: `Database::query() exits on error (Database.php:130); this catch cannot intercept — the query must match mod_user_social's schema.`

**A6 — Execution order must follow severity, not component numbering.**
The revision's Component order executes a UI/CSS fix (C1) before the only finding that **kills page loads** (F3). Rationale: F3 is a deterministic white-screen on every future Person-authored article; F1 is a layout distortion on articles that still render.
*Amendment:* execute **C3 → C2 → C1 → C4 → C5 → C6 → C7 → C8**.

### 5.4 Disposition

With amendments **A1–A6 applied**, all objections are resolved and this plan is approved for execution in the amended order.

**Signed:** omp — trusted coding assistant (model: `zai/glm-5.3`), 2026-11-08, Europe/Istanbul.
**Evidence basis:** live `amedbulten.com` probes and stylesheet inventory (2026-11-08), production disk inspection via read-only SSH, `git 2fbaab2f`, source reads cited per `file:line`.
