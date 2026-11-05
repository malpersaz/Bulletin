# Wikipedia-Style UTF-8 URL Parity & Statutory Slug Remediation Plan

## 1. Objective & Architectural Mandate

Restore 100% compliance with **Primary Rule 9 (`AGENTS.md` — URL Generation Parity / Wikipedia Style)** across all routing, statutory redirect, sitemap, helper, language dictionary, and template layers in `AF/Bulletin`.

During the `v1.0.45` statutory page redirection and `pageSitemap.xml` filtering pass, an ASCII character-stripping transliteration table (`$transMap` converting `Ç->c`, `Ğ->g`, `İ->i`, `Ö->o`, `Ş->s`, `Ü->u`, `Î->i`, `Ê->e`, `Û->u`) was introduced into [`lib/Amed.class.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/lib/Amed.class.php#L422-L436), [`mod/index/helper/mod_page.helper.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/mod/index/helper/mod_page.helper.php#L74-L80), and [`mod/index/index.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/mod/index/index.php#L1307-L1317). Additionally, several statutory `*_slug` entries in `mod/index/lang/*.php` (`tr.php`, `ku.php`, `zza.php`, `fr.php`, `sv.php`, `de.php`) and live database rows (`mod_page` IDs `22, 29, 34, 37, 39, 40, 41, 42, 43, 44`) still use legacy ASCII-stripped or hyphenated slugs instead of native Wikipedia-style UTF-8 slugs (`Helper::legacy_seo()`).

### Why Wikipedia / MediaWiki & Amed Framework Never Transliterate UTF-8 to ASCII
1. **Native Alphabet Preservation:** Like Wikipedia (`https://tr.wikipedia.org/wiki/Çerez_politikası`, `https://ku.wikipedia.org/wiki/Polîtîkaya_çerezan`), Amed Framework generates slugs in their original UTF-8 alphabet via `Helper::legacy_seo()`, preserving original characters and casing while replacing whitespace with underscores (`_`).
2. **Semantic & Linguistic Integrity:** Stripping diacritics (`ş -> s`, `ç -> c`, `î -> i`, `ê -> e`, `û -> u`) corrupts words across Turkish, Kurmanji Kurdish, Zazaki, French, German, and Swedish.
3. **Pure UTF-8 Case-Insensitive Resolution:** Instead of destructive ASCII transliteration (`strtr(..., $transMap)` + `strtolower()`), slug normalization must use pure UTF-8 multibyte case folding (`mb_strtolower(..., 'UTF-8')` with locale-safe Turkish uppercase `İ -> i` and `I -> ı` folding) against an explicit dictionary of native UTF-8 slugs (plus legacy database aliases for backward-compatible 301 redirects).

---

## 2. Root-Cause Inventory of Non-Compliant UTF-8 / ASCII Code Paths

### 2.1. Forbidden `$transMap` ASCII Transliteration Arrays (3 Core Files)
1. **[`lib/Amed.class.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/lib/Amed.class.php#L403-L443) (lines 403–443):**
   - Defines `$transMap` and strips UTF-8 characters via `$pageTrans = strtr(rawurldecode($pageSlug), $transMap);` and ASCII `strtolower()`.
   - Also only lists ASCII-transliterated keys (`cerez_politikasi`, `kullanim_kosullari`, `agahiyen_qanuni`, etc.) in `$statutoryMap`.
2. **[`mod/index/helper/mod_page.helper.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/mod/index/helper/mod_page.helper.php#L24-L89) (lines 24–89):**
   - Defines `$transMap` and strips UTF-8 characters via `$transliterated = strtr(rawurldecode((string) $seo), $transMap);` and ASCII `strtolower()`.
   - Only lists ASCII keys in `$statutoryRedirects`.
3. **[`mod/index/index.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/mod/index/index.php#L1288-L1322) (lines 1288–1322):**
   - Defines `$transMap` in `pageSitemap()` and uses `strtr(rawurldecode($rawSeo), $transMap)` + ASCII `strtolower()` to filter statutory pages out of `pageSitemap.xml`.

### 2.2. Legacy ASCII `sefurl` Filter Usage in Public Search Template
4. **[`templates/amed/index/search.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/search.twig#L325) (line 325):**
   - Uses `{{ site.saddress }}announcement/{{ announcement.id }}-{{ announcement.name|sefurl }}` which strips Turkish/Kurdish UTF-8 characters to ASCII and replaces spaces with hyphens (`-`), whereas [`announcements.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/mod_announcements/announcements.twig#L127) and [`announcement.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/mod_announcements/announcement.twig#L18) use `|legacy_seo` (`{{ announcement.id }}-{{ announcement.name|legacy_seo }}`).
   - Must be updated to `|legacy_seo` so announcement URLs in search results match canonical Wikipedia-style announcement URLs (`{id}-{name|legacy_seo}`).

### 2.3. Non-Wikipedia ASCII-Stripped `*_slug` Values in `mod/index/lang/*.php` and `legal.helper.php`
5. **Language Files (`mod/index/lang/tr.php`, `ku.php`, `zza.php`, `fr.php`, `sv.php`, `de.php`):**
   - [`mod/index/lang/tr.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/mod/index/lang/tr.php):
     - `'cookie_policy_slug' => 'Cerez_Politikasi'` -> must be `'Çerez_Politikası'`
     - `'privacy_policy_slug' => 'Gizlilik_Politikasi'` -> must be `'Gizlilik_Politikası'`
     - `'terms_of_service_slug' => 'Kullanim_Kosullari'` -> must be `'Kullanım_Koşulları'`
     - `'editorial_policy_slug' => 'Yayin_Ilkeleri'` -> must be `'Yayın_İlkeleri'`
     - `'accessibility_slug' => 'Erisilebilirlik'` -> must be `'Erişilebilirlik'`
     - `'security_policy_slug' => 'Guvenlik_Politikasi'` -> must be `'Güvenlik_Politikası'`
     - `'compliance_slug' => 'Basin_Etigi_ve_Uyum'` -> must be `'Basın_Etiği_ve_Uyum'`
   - [`mod/index/lang/ku.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/mod/index/lang/ku.php):
     - `'cookie_policy_slug' => 'Polîtîkaya_Kulîçeyan'` -> align with `'Polîtîkaya_Çerezan'`
     - `'privacy_policy_slug' => 'Politikaya_Nepaniye'` -> must be `'Polîtîkaya_Nepeniyê'`
     - `'terms_of_service_slug' => 'Mercen_Bikaranine'` -> must be `'Mercên_Bikaranînê'`
     - `'editorial_policy_slug' => 'Prensiben_Wesane'` -> must be `'Prensîbên_Weşanê'`
     - `'accessibility_slug' => 'Gihistinbari'` -> must be `'Gihîştbarî'`
     - `'security_policy_slug' => 'Politikaya_Ewlehiye'` -> must be `'Polîtîkaya_Ewlehiyê'`
     - `'compliance_slug' => 'Etiqa_Capemeniye_u_Lihevhatin'` -> must be `'Etîka_Çapemeniyê_û_Lihevhatin'`
   - [`mod/index/lang/zza.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/mod/index/lang/zza.php):
     - `'legal_info_slug' => 'Melumatê_Huqûqî'`
     - `'cookie_policy_slug' => 'Politîkaya_Çerezan'` -> `'Siyasetê_Çerezan'`
     - `'privacy_policy_slug' => 'Politikaya_Nimiteyiye'` -> must be `'Polîtîkaya_Nimiteyîye'`
     - `'terms_of_service_slug' => 'Serte_Xebitnayisi'` -> must be `'Şertê_Xebitnayîşî'`
     - `'editorial_policy_slug' => 'Prensibe_Wesani'` -> must be `'Prensîbê_Weşanî'`
     - `'accessibility_slug' => 'Resayisbariye'` -> must be `'Resayîşbarîye'`
     - `'security_policy_slug' => 'Politikaya_Asayisi'` -> must be `'Polîtîkaya_Asayîşî'`
     - `'compliance_slug' => 'Liheveyiye'` -> must be `'Liheveyîye'`
   - [`mod/index/lang/fr.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/mod/index/lang/fr.php):
     - `'privacy_policy_slug' => 'Politique_de_Confidentialite'` -> `'Politique_de_Confidentialité'`
     - `'terms_of_service_slug' => 'Conditions_d_Utilisation'` -> `'Conditions_d_Utilisation'`
     - `'editorial_policy_slug' => 'Principes_Editoriaux'` -> `'Principes_Éditoriaux'`
     - `'accessibility_slug' => 'Accessibilite'` -> `'Accessibilité'`
     - `'security_policy_slug' => 'Politique_de_Securite'` -> `'Politique_de_Sécurité'`
     - `'compliance_slug' => 'Conformite'` -> `'Conformité'`
   - [`mod/index/lang/sv.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/mod/index/lang/sv.php):
     - `'terms_of_service_slug' => 'Anvandarvillkor'` -> `'Användarvillkor'`
     - `'accessibility_slug' => 'Tillganglighet'` -> `'Tillgänglighet'`
     - `'security_policy_slug' => 'Sakerhetspolicy'` -> `'Säkerhetspolicy'`
   - [`mod/index/lang/de.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/mod/index/lang/de.php):
     - `'privacy_policy_slug' => 'Datenschutzerklaerung'` -> `'Datenschutzerklärung'`

6. **Database & Live MCP `mod_page` Statutory Records (`d048d282.sql` & Live Production via MCP):**
   - In `mod_page`, rows `37, 39, 40, 41, 42, 43, 44` (`tr`), row `34` (`de`), and row `22` (`ku`) currently store ASCII-stripped or hyphenated `seo` values:
     - `ID=22` (`ku`): `Agahdariya_Zagônî` (name: `Agahdariya Zagônî` -> `Agahiyên_Qanûnî` / `Agahdariya_Zagonî`)
     - `ID=34` (`de`): `Cookie-Richtlinie` -> `Cookie_Richtlinie`
     - `ID=37` (`tr`): `Cerez_Politikasi` -> `Çerez_Politikası`
     - `ID=39` (`tr`): `Yayin_Ilkeleri` -> `Yayın_İlkeleri`
     - `ID=40` (`tr`): `Gizlilik_Politikasi` -> `Gizlilik_Politikası`
     - `ID=41` (`tr`): `Kullanim_Kosullari` -> `Kullanım_Koşulları`
     - `ID=42` (`tr`): `Erisilebilirlik` -> `Erişilebilirlik`
     - `ID=43` (`tr`): `Guvenlik_Politikasi` -> `Güvenlik_Politikası`
     - `ID=44` (`tr`): `Basin_Etigi_ve_Uyum` -> `Basın_Etiği_ve_Uyum`

---

## 3. Step-by-Step Remediation Architecture

### Step 1: Centralize Pure UTF-8 Statutory Slug Normalization & Resolution in `Helper` (`lib/Helper.class.php`)
Instead of duplicating statutory slug arrays and ad-hoc string manipulation across `lib/Amed.class.php`, `mod/index/helper/mod_page.helper.php`, `mod/index/helper/legal.helper.php`, and `mod/index/index.php`, add two deterministic, pure-UTF-8 static helper methods to [`lib/Helper.class.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/lib/Helper.class.php):

1. **`Helper::utf8_slug_key(string $slug): string`**
   - Decodes URL percent-encoding (`rawurldecode($slug)`).
   - Normalizes spaces and hyphens to underscores (`str_replace(['-', ' '], '_', trim($decoded, '/ '))`).
   - Folds uppercase Turkish dotted `İ` (`\xC4\xB0`) to `i` before calling `mb_strtolower(..., 'UTF-8')` so PHP's Unicode case-folding never produces a combining dot above (`i\xCC\x87`), while preserving every UTF-8 character (`ç, ğ, ı, ö, ş, ü, î, ê, û, ä, ö, å, é, è, ë, ع, ک, ی, etc.`) in its native script!
   - **Zero ASCII transliteration (`$transMap` is strictly forbidden).**

2. **`Helper::resolve_statutory_slug(string $slug): ?string`**
   - Normalizes `$slug` via `Helper::utf8_slug_key($slug)` and looks it up in a single canonical UTF-8 map returning the root statutory route (`'legal'`, `'cookie-policy'`, `'privacy-policy'`, `'terms-of-service'`, `'editorial-policy'`, `'accessibility'`, `'security-policy'`, `'compliance'`, or `'contact'`), or `null` if the slug is a regular editorial page.
   - The canonical map includes:
     - **Canonical root route slugs** (`legal`, `cookie_policy`, `privacy_policy`, `terms_of_service`, `editorial_policy`, `accessibility`, `security_policy`, `compliance`, `contact` and short aliases `imprint`, `masthead`, `cookies`, `privacy`, `terms`, `tos`, `editorial`, `editorial_guidelines`, `corrections`, `corrections_policy`, `a11y`, `security`, `ethics_policy`).
     - **Native Wikipedia-Style UTF-8 Slugs across all repository languages:**
       - **Turkish (`tr`):** `yasal_bilgiler`, `künye`, `çerez_politikası`, `gizlilik_politikası`, `kullanım_koşulları`, `yayın_ilkeleri`, `erişilebilirlik`, `güvenlik_politikası`, `basın_etiği_ve_uyum`, `iletişim` (plus Turkish lowercase `I->ı` variant `yayın_ılkeleri`).
       - **Kurmanji Kurdish (`ku`):** `agahiyên_qanûnî`, `agahdariya_zagonî`, `agahdariya_zagônî`, `polîtîkaya_çerezan`, `polîtîkaya_kulîçeyan`, `rêbaza_çerezan`, `polîtîkaya_nepeniyê`, `polîtîkaya_nepaniyê`, `mercên_bikaranînê`, `mercên_bikarhanînê`, `prensîbên_weşanê`, `zasayên_weşanê`, `gihîştbarî`, `gihîştinbarî`, `polîtîkaya_ewlehiyê`, `etîka_çapemeniyê`, `etîka_çapemeniyê_û_lihevhatin`, `kûnye`, `têkilî`.
       - **Zazaki (`zza`):** `melumatê_huqûqî`, `melûmatê_hiqûqî`, `siyasetê_çerezan`, `politîkaya_çerezan`, `polîtîkaya_nimiteyîye`, `şertê_xebitnayîşî`, `prensîbê_weşanî`, `resayîşbarîye`, `polîtîkaya_asayîşî`, `liheveyîye`.
       - **Sorani Kurdish (`ckb`):** `زانیارییە_یاساییەکان`, `سیاسەتی_کووکییەکان`.
       - **Arabic (`ar`):** `المعلومات_القانونية`, `سياسة_ملفات_تعريف_الارتباط`.
       - **German (`de`):** `rechtliche_hinweise`, `cookie_richtlinie`, `datenschutzerklärung`, `nutzungsbedingungen`, `redaktionelle_richtlinien`, `barrierefreiheit`, `sicherheitsrichtlinie`, `presseethik_und_compliance`.
       - **French (`fr`):** `informations_légales`, `politique_de_cookies`, `politique_de_confidentialité`, `conditions_d_utilisation`, `principes_éditoriaux`, `accessibilité`, `politique_de_sécurité`, `conformité`.
       - **Swedish (`sv`):** `juridisk_information`, `cookiepolicy`, `integritetspolicy`, `användarvillkor`, `publicistiska_riktlinjer`, `tillgänglighet`, `säkerhetspolicy`, `regelefterlevnad`.
     - **Legacy Historical Database Slugs (explicitly enumerated for 301 redirect backward-compatibility without runtime transliteration):**
       - `cerez_politikasi`, `gizlilik_politikasi`, `kullanim_kosullari`, `yayin_ilkeleri`, `erisilebilirlik`, `guvenlik_politikasi`, `basin_etigi_ve_uyum`, `kunye`, `kunya`, `iletisim`, `agahiyen_qanuni`, `politikaya_cerezan`, `siyasete_cerezan`, `politikaya_nepaniye`, `politika_nepeniye`, `mercen_bikaranine`, `mercen_bikarhanine`, `prensiben_wesane`, `zasayen_wesane`, `gihistbari`, `gihistinbari`, `politikaya_ewlehiye`, `etika_capemeniye`, `etiqa_capemeniye_u_lihevhatin`, `politikaya_nimiteyiye`, `serte_xebitnayisi`, `prensibe_wesani`, `resayisbariye`, `politikaya_asayisi`, `liheveyiye`, `datenschutzerklaerung`, `politique_de_confidentialite`, `principes_editoriaux`, `accessibilite`, `politique_de_securite`, `conformite`, `anvandarvillkor`, `tillganglighet`, `sakerhetspolicy`.

### Step 2: Remove `$transMap` from `lib/Amed.class.php`, `mod/index/helper/mod_page.helper.php`, and `mod/index/index.php`
1. **[`lib/Amed.class.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/lib/Amed.class.php#L403-L445):**
   - Delete `$statutoryMap` and `$transMap`.
   - Use `Helper::resolve_statutory_slug($remainder)` (and if `$remainder` starts with `page/`, `Helper::resolve_statutory_slug(substr($remainder, 5))`).
   - If a statutory route is matched, 301-redirect in a single hop to `rtrim((string) $this->site['address'], '/') . '/' . $targetSlug`.
2. **[`mod/index/helper/mod_page.helper.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/mod/index/helper/mod_page.helper.php#L23-L90):**
   - Delete `$statutoryRedirects` and `$transMap`.
   - Call `$targetSlug = Helper::resolve_statutory_slug((string) $seo);`.
   - If `$targetSlug !== null` (and `PHP_SAPI !== 'cli'` and `!headers_sent()`), issue `301 Moved Permanently` to `rtrim((string) $this->site['address'], '/') . '/' . $targetSlug`.
3. **[`mod/index/index.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/mod/index/index.php#L1288-L1322):**
   - In `pageSitemap()`, delete `$statutoryPageSlugs` and `$transMap`.
   - For each `$o` in `$pages->toArray()`, check `if (Helper::resolve_statutory_slug((string) $o['seo']) !== null) { continue; }`.

### Step 3: Update `mod/index/lang/*.php` and `mod/index/helper/legal.helper.php` to Native Wikipedia-Style UTF-8 Slugs
1. Update `mod/index/lang/tr.php`, `mod/index/lang/ku.php`, `mod/index/lang/zza.php`, `mod/index/lang/fr.php`, `mod/index/lang/sv.php`, and `mod/index/lang/de.php` so all `*_slug` keys use native UTF-8 characters and underscores (`Çerez_Politikası`, `Gizlilik_Politikası`, `Kullanım_Koşulları`, `Yayın_İlkeleri`, `Erişilebilirlik`, `Güvenlik_Politikası`, `Basın_Etiği_ve_Uyum`, `Polîtîkaya_Çerezan`, `Polîtîkaya_Nepeniyê`, `Mercên_Bikaranînê`, `Prensîbên_Weşanê`, `Gihîştbarî`, `Polîtîkaya_Ewlehiyê`, `Etîka_Çapemeniyê_û_Lihevhatin`, etc.).
2. In [`mod/index/helper/legal.helper.php`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/mod/index/helper/legal.helper.php#L52-L135), place the canonical native UTF-8 slugs **first** in every `fallback_slugs` array (`'Çerez_Politikası'`, `'Gizlilik_Politikası'`, `'Kullanım_Koşulları'`, `'Yayın_İlkeleri'`, `'Erişilebilirlik'`, `'Güvenlik_Politikası'`, `'Basın_Etiği_ve_Uyum'`, `'İletişim'`, `'Agahiyên_Qanûnî'`, `'Polîtîkaya_Çerezan'`, `'Polîtîkaya_Nepeniyê'`, `'Mercên_Bikaranînê'`, `'Prensîbên_Weşanê'`, `'Gihîştbarî'`, `'Polîtîkaya_Ewlehiyê'`, `'Etîka_Çapemeniyê'`, `'Etîka_Çapemeniyê_û_Lihevhatin'`), keeping legacy ASCII slugs only as secondary database lookup fallbacks in `resolvePolicyPageRecord()`.

### Step 4: Replace `|sefurl` with `|legacy_seo` in `templates/amed/index/search.twig`
- In [`templates/amed/index/search.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/amed/index/search.twig#L325), replace:
  `href="{{ site.saddress }}announcement/{{ announcement.id }}-{{ announcement.name|sefurl }}"`
  with:
  `href="{{ site.saddress }}announcement/{{ announcement.id }}-{{ announcement.name|legacy_seo }}"`
  so search result links preserve Wikipedia-style UTF-8 slugs and match `announcements.twig` and `announcement.twig`.

### Step 5: Update Statutory `mod_page` Slugs in Production via MCP (`amed-bulletin:update_page`) & Local SQL Dump
- Update the Turkish statutory `mod_page` records (`ID=37, 39, 40, 41, 42, 43, 44`) on the live server via `amed-bulletin:update_page` (and in `data/sqlite/d048d282.sql`) from legacy ASCII slugs to native Wikipedia-style UTF-8 slugs:
  - `ID=37`: `seo = 'Çerez_Politikası'`
  - `ID=39`: `seo = 'Yayın_İlkeleri'`
  - `ID=40`: `seo = 'Gizlilik_Politikası'`
  - `ID=41`: `seo = 'Kullanım_Koşulları'`
  - `ID=42`: `seo = 'Erişilebilirlik'`
  - `ID=43`: `seo = 'Güvenlik_Politikası'`
  - `ID=44`: `seo = 'Basın_Etiği_ve_Uyum'`

---

## 4. Deterministic Verification Matrix

1. **Zero `$transMap` Check:**
   - `grep -rn "transMap" lib/ mod/ templates/` must return **0 matches**.
2. **Zero Public Template `|sefurl` Check:**
   - `grep -rn "|sefurl" templates/` must return **0 matches**.
3. **Pure UTF-8 Slug Resolution Tests (CLI Verification):**
   - `Helper::resolve_statutory_slug('Çerez_Politikası') === 'cookie-policy'`
   - `Helper::resolve_statutory_slug('Kullanım_Koşulları') === 'terms-of-service'`
   - `Helper::resolve_statutory_slug('Yayın_İlkeleri') === 'editorial-policy'`
   - `Helper::resolve_statutory_slug('Güvenlik_Politikası') === 'security-policy'`
   - `Helper::resolve_statutory_slug('Basın_Etiği_ve_Uyum') === 'compliance'`
   - `Helper::resolve_statutory_slug('Erişilebilirlik') === 'accessibility'`
   - `Helper::resolve_statutory_slug('Polîtîkaya_Çerezan') === 'cookie-policy'`
   - `Helper::resolve_statutory_slug('Agahiyên_Qanûnî') === 'legal'`
   - `Helper::resolve_statutory_slug('Hakkımızda') === null` (regular UTF-8 page is untouched!)
   - `Helper::resolve_statutory_slug('Derbarê_me') === null` (regular UTF-8 Kurdish page is untouched!)
4. **Syntax & Repository Guard Checks:**
   - `php -l` passes on `lib/Helper.class.php`, `lib/Amed.class.php`, `mod/index/helper/mod_page.helper.php`, `mod/index/helper/legal.helper.php`, `mod/index/index.php`, and all modified `mod/index/lang/*.php` files.
   - `python3 tools/code_separation_guard.py`, `python3 tools/i18n_guard.py`, and `python3 tools/module_boundary_guard.py` pass with zero errors.
