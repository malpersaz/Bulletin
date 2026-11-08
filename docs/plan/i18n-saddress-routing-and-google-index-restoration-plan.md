# i18n `saddress` Routing, `mod_page` Sovereignty & Google Index Restoration Plan

**Date:** 2026-11-07
**Target Workspace:** `AF/Bulletin`
**Canonical Reference:** `AF/Business` (`~/Desktop/projects/htdocs/AF/Business` — adopting valid shared-core standards while explicitly rejecting `AF/Business` bugs)
**Version Tracking Mandate:** All changes during execution MUST be recorded live under `## v1.0.51 (In Progress)` in `docs/VERSION.md`.
**Status:** UPDATED PLAN v3 — SECOND DEEP AUDIT COMPLETED

## Yönetici Özeti (Sahip Özeti — Türkçe)

**Sorun:** `mod_page` 2010'dan beri her sayfayı zaten veritabanına kaydeder — depolama hiç bozulmadı. Bozulan, sonradan araya giren **adresleme katmanı**: 7 politika sayfası için el yapımı kod kopyası (`/legal`, `/privacy-policy` gibi kısa adreslerle) DB'deki satırı bypass etti, `/tr/` öneki silindi. Google hangi adresin gerçek olduğunu bilemedi, dizin zarar gördü.

**Planın yaptığı:**
1. Kopya sayfalar, sahte adresler ve onları üreten kod silinir — içerik zaten veritabanında, kaybolmaz.
2. İçerik sayfaları tek gerçek adresine döner: `/tr/page/SayfaAdı` (ör. `/tr/page/Hakkımızda`).
3. Google'daki eski adreslere kalıcı yol tarifi konur (301): eski link tıklandığında gerçek adrese gider.
4. Kapalı diller (ku, en, de…) "bu içerik kalktı" der (410); Google onları dizinden temizler.
5. Yasal Bilgiler ve Çerez Politikası **uygulama sayfasıdır**: mod_page'te değil, kendi adreslerinde yaşar (`/tr/legal`, `/tr/cookie-policy`); verileri `mod_settings` formlarından gelir — admin yayından kaldırabilir, verileri düzenleyebilir, ama uygulamayı silemez; tasarım asla kaybolmaz.
6. Footer, menü, site haritası ve llms.txt doğru adresleri gösterir.

**Sonuç:** İki tür sayfa, iki tür ev: içerik → `mod_page` (`/tr/page/Ad`), uygulama → kendi route'u. Kopya yok, gizli bağlantı yok; Google güveni zamanla geri gelir.

**Sahibin kararları:** (1) İçerik sayfaları `/tr/page/Ad` olur; 7 sahte kopya adres ve kodu silinir. (2) Yasal Bilgiler ve Çerez Politikası mod_page'ten çıkar; `/tr/legal` ve `/tr/cookie-policy` uygulama sayfası olur (AF/Business standardı: form CMS'nin parçasıdır, sayfa değil). (3) `/tr/contact` uygulamasından vazgeçildi — iletişim verisi ayarlar formunda, footer'da yaşamaya devam eder. (4) Panel-sayfa eşlemesi/config gereksizdi; sorun özünde yanlış evde barınmaydı.

**Denetim:** GLM-5.3 planı satır satır denetledi; 4 bloklayan hata (O1–O4) ve düzeltmeler Bölüm 5'te. Argon cevabını `## 6. Argon Response` altına yazacak.

---

## 0. Completed Pre-Flight Actions & Localhost (`http://localhost:8080/tr/`) Audit

- **Live & Local `mod_redirect` Table Purged (2026-11-07):**
  - All 21 legacy/poisoned redirect rows (IDs `24–26`, `69–86`) in `mod_redirect` were deleted (`redirect_count = 0` confirmed on both production MCP and local Docker MySQL `bulletin_db`).
- **Localhost (`http://localhost:8080/tr/`) Database & HTTP Baseline Verified:**
  - **`lang` table:** `tr` is the sole active and default language (`status='y'`, `is_default='y'`); all other 9 languages (`ku`, `ckb`, `zza`, `en`, `de`, `fr`, `ar`, `fa`, `sv`) have `status='n'`.
  - **`mod_page` (`lang='tr'`):** All 9 Turkish institutional & policy pages exist with full Turkish HTML content in `bulletin_db`:
    - `id: 8` — `Türkçe_Sayfalar` (`parent_id: 0`, `root: 'y'` container, `content: NULL`)
    - `id: 18` — `Hakkımızda` (`parent_id: 8`, 2,359 bytes)
    - `id: 28` — `Yasal_Bilgiler` (`parent_id: 18`, 1,820 bytes)
    - `id: 37` — `Çerez_Politikası` (`parent_id: 18`, 1,737 bytes)
    - `id: 39` — `Yayın_İlkeleri` (`parent_id: 18`, 2,260 bytes)
    - `id: 40` — `Gizlilik_Politikası` (`parent_id: 18`, 2,977 bytes)
    - `id: 41` — `Kullanım_Koşulları` (`parent_id: 18`, 2,980 bytes)
    - `id: 42` — `Erişilebilirlik` (`parent_id: 18`, 2,048 bytes)
    - `id: 43` — `Güvenlik_Politikası` (`parent_id: 18`, 1,882 bytes)
    - `id: 44` — `Basın_Etiği_ve_Uyum` (`parent_id: 18`, 1,899 bytes)
  - **`mod_settings_cookies` (`lang='tr'`):** 6 active cookie rows (IDs `49–54`) across `necessary` (`PHPSESSID`, `theme`), `functional` (`cookie_consent`, `sidebar_state`), `analytics` (`_ga, _gid`), and `marketing` (`_camp_id`).
  - **`mod_settings` (`lang='tr'`, `id: 9`):** Populated with corporate/masthead fields (`legal_company_name`, `legal_trading_name`, `managing_director`, `editor_in_chief`, `press_code_license`, `dpo_email`, `registered_email`, `hosting_provider`, `compliance_jurisdiction`).
  - **`mod_menu` (`lang='tr'`):** `##Footer_Menu` (`id: 120`) currently contains `Hakkımızda` (`tr/page/Hakkımızda`), `Haberler` (`tr/newses`), `Makaleler` (`tr/publications`), `Yazarlar` (`tr/authors`), `Videolar` (`tr/videos`), and `Duyurular` (`tr/announcements`), all properly prefixed with `tr/`.

---

## 1. Core Architectural Diagnosis & Second-Pass Audit Findings

### 1.1 Three Regressions to Undo in `AF/Bulletin`
1. **Violation A — Stripping `site.saddress` (`/<lang>/`) to Bare Root (`site.address`):**
   - `Helper::resolve_statutory_slug()` (`lib/Helper.class.php` lines 443–655) strips `/tr/` and redirects `mod_page` and policy URLs to bare-root paths (`/legal`, `/compliance`, `/privacy-policy`, etc.).
2. **Violation B — Hijacking `mod_page` Pages into Fake Hardcoded Routes:**
   - 7 non-standard routes in `config/url.config.php`, 7 controller methods in `mod/index/index.php`, and 7 duplicate templates in `templates/amed/index/legal/` hijacked database-driven `mod_page` pages.
3. **Violation C — Writing Policy Prose Inside `mod/index/lang/*.php`:**
   - ~85 keys of policy article paragraphs (`privacy_*`, `terms_*`, `editorial_*`, `accessibility_*`, `security_*`, `compliance_*`, `legal_nav_*`) were hardcoded into all 10 language files.

### 1.2 Second-Pass Audit Discoveries (Including `AF/Business` Bugs We Must NOT Copy)
1. **`Helper::ascii_slug_key()` in `lib/Helper.class.php` MUST NOT Be Deleted:**
   - While `Helper::resolve_statutory_slug()` must be deleted, `Helper::ascii_slug_key()` (lines 660–669) is actively called by `mod_news.helper.php`, `mod_publication.helper.php`, `mod_event.helper.php`, `mod_page.helper.php`, and `mod_seo/index.php` for inbound ASCII-to-UTF-8 301 reconciliation. Deleting it would cause fatal errors across all content modules.
2. **`tools/i18n_guard.py` Forbidden Fallback (`|default(...)`) on `lang.*` Keys:**
   - `tools/i18n_guard.py` (`RE_FORBIDDEN_DEFAULT`, line 90) strictly forbids `|default(...)` or `??` on any `lang.*` key in Twig templates. Because `legal_info_slug` and `cookie_policy_slug` are already defined in all 10 `mod/index/lang/*.php` files, `footer.twig` and `_cookie_consent.twig` must use `{{ lang.index.legal_info_slug }}` and `{{ lang.index.cookie_policy_slug }}` **without** `|default(...)`.
3. **Twig Variable Scope & Block Order in `templates/amed/index/mod_page/page.twig`:**
   - In `page.twig`, `{% block css_custom %}` (line 4) and `{% block js_custom %}` (line 15) are declared **before** `{% set d = ... %}` (line 20), and `is_legal_page` / `is_cookie_page` are defined **inside** `{% block content %}` (lines 262–289).
   - Variables defined inside `{% block content %}` or below `css_custom`/`js_custom` are out of scope (`null`) inside `{% block css_custom %}` and `{% block js_custom %}`.
   - **Fix:** Move `{% set d %}`, `{% set is_legal_page %}`, and `{% set is_cookie_page %}` to the top of `page.twig` **above** `{% block css_custom %}` so conditional CSS/JS loading works reliably.
4. **Sidebar Subpage Navigation Bugs on `Hakkımızda` (`id: 18`) and Child Pages (`id: 28, 37, 39..44`):**
   - **In `AF/Bulletin/mod/index/helper/mod_page.helper.php`:** Currently calls only `$this->_mod_ust_list('mod_page', $page->parent_id, ...)` and never calls `$this->_mod_parent_id_list('mod_page', $page->id, ...)` or `$this->_mod_faq('mod_page', $page->id)`. As a result, visiting `/tr/page/Hakkımızda` (`id: 18`) fails to populate `parent_id_list`, hiding its 8 child policy pages in the sidebar.
   - **In `AF/Bulletin/mod/index/indexBase.php` (lines 288–291 & 313–316):** `_mod_ust_list` and `_mod_parent_id_list` index rows by `$myArray[$ar[$order]] = $ar;`. Because multiple sibling pages share the same `order` value (e.g., `0`), PHP overwrites them so only 1 sibling page survives! Must use `$list->toArray()` directly and filter active records (`status = 'y'`).
5. **`AF/Business` Bugs Explicitly Rejected (Will NOT Be Copied to `AF/Bulletin`):**
   - **`AF/Business` Bug 1 (`mod/index/helper/mod_page.helper.php`):** `AF/Business` calls `Req::Url_Clear($seo)` before `urldecode()` and lacks a 301 canonical redirect when a page is matched via case-insensitive collation, space/underscore variation, or ASCII slug fallback (allowing duplicate content at `/tr/page/hakkimizda` and `/tr/page/Hakkımızda`). `AF/Bulletin` will keep its UTF-8 decoding + byte-exact `(string)$page->seo !== $seo` 301 redirect + `Helper::ascii_slug_key()` 301 redirect to `/tr/page/<seo>`.
   - **`AF/Business` Bug 2 (`mod/index/index.php` `pageSitemap()`):** `AF/Business` queries `mod_page` without `root != 'y'` and without `(acl_id = 0 OR acl_id IS NULL)`, omits `Helper::sitemap_url()` RFC 3986 encoding, and references commercial modules (`mod_content`, `mod_product`, `mod_project`, `mod_service`) and wrong model name `'mod_news'`. `AF/Bulletin` will keep its strict `root != 'y'`, `acl_id`, and `Helper::sitemap_url()` logic, and we will also remove the dangling `$url['contentSitemap.xml']` route from `AF/Bulletin/config/url.config.php` (line 108).
   - **`AF/Business` Bug 3 (`lib/LlmsService.class.php`):** `AF/Business` omits `/<lang>/` in `buildUrl()` when `$lang === $defaultLang` and omits `root != 'y'` / `acl_id` in `getActivePages()`. `AF/Bulletin` will fix both.

---

## 2. Canonical Architecture Alignment (`mod_page` Sovereignty)

| Surface / Page | Canonical URL (`site.saddress`) | Content Source (Database) | Interactive Widget / Structured Data Source |
|---|---|---|---|
| **Hakkımızda** (`id: 18`) | `/tr/page/Hakkımızda` | 100% `mod_page` (`d.content`) | Subpage sidebar tree (`_mod_ust_list` + `_mod_parent_id_list`) listing all 8 child policy pages |
| **Yasal Bilgiler** (`id: 28`) | `/tr/page/Yasal_Bilgiler` | `mod_page` (`d.content`) | + `partials/_legal_info.twig` (structured corporate/masthead fields from `mod_settings` + `mod_settings_affiliation` + print/copy buttons + `legal-info.js`) |
| **Çerez Politikası** (`id: 37`) | `/tr/page/Çerez_Politikası` | `mod_page` (`d.content`) | + `partials/_cookie_policy.twig` (Interactive Cookie Preference Center toggles, accept/reject/save buttons, `#cookie-save-toast`, `cookie-policy.js`, + `mod_settings_cookies` database rows) |
| **Yayın İlkeleri** (`id: 39`) | `/tr/page/Yayın_İlkeleri` | 100% `mod_page` (`d.content`) | Standard `mod_page` layout + sibling sidebar |
| **Gizlilik Politikası** (`id: 40`) | `/tr/page/Gizlilik_Politikası` | 100% `mod_page` (`d.content`) | Standard `mod_page` layout + sibling sidebar |
| **Kullanım Koşulları** (`id: 41`) | `/tr/page/Kullanım_Koşulları` | 100% `mod_page` (`d.content`) | Standard `mod_page` layout + sibling sidebar |
| **Erişilebilirlik** (`id: 42`) | `/tr/page/Erişilebilirlik` | 100% `mod_page` (`d.content`) | Standard `mod_page` layout + sibling sidebar |
| **Güvenlik Politikası** (`id: 43`) | `/tr/page/Güvenlik_Politikası` | 100% `mod_page` (`d.content`) | Standard `mod_page` layout + sibling sidebar |
| **Basın Etiği ve Uyum** (`id: 44`) | `/tr/page/Basın_Etiği_ve_Uyum` | 100% `mod_page` (`d.content`) | Standard `mod_page` layout + sibling sidebar |

---

## 3. Step-by-Step Execution Plan

### Step 1: Version Tracking Initialization (`docs/VERSION.md`)
- Update `docs/VERSION.md` heading to `## v1.0.51 (In Progress)` and log every file restoration and cleanup in English as changes are applied.

### Step 2: Purge Fake Policy Routes, Controller Methods, Redundant Templates & Unused `lang/*.php` Prose
1. **`config/url.config.php`:**
   - Remove the non-standard policy route aliases (`imprint`, `privacy-policy`, `privacy`, `terms-of-service`, `terms`, `editorial-policy`, `editorial-guidelines`, `corrections-policy`, `accessibility`, `a11y`, `security-policy`, `security`, `compliance`, `ethics-policy`, `contact`), keeping only `legal`, `cookie-policy`, and `cookies`.
   - Remove the dangling forbidden route `$url['contentSitemap.xml'] = 'index/contentSitemap';` (line 108).
2. **`mod/index/index.php`:**
   - Remove the 7 non-standard controller methods (`privacy_policy()`, `terms_of_service()`, `editorial_policy()`, `accessibility()`, `security_policy()`, `compliance()`, `contact()`), keeping `page()`, `legal()`, and `cookie_policy()`.
3. **Delete Redundant Templates:**
   - Delete the 7 fake policy templates that duplicated `mod_page`:
     - `templates/amed/index/legal/privacy_policy.twig`
     - `templates/amed/index/legal/terms_of_service.twig`
     - `templates/amed/index/legal/editorial_policy.twig`
     - `templates/amed/index/legal/accessibility.twig`
     - `templates/amed/index/legal/security_policy.twig`
     - `templates/amed/index/legal/compliance.twig`
     - `templates/amed/index/legal/contact.twig`
   - Delete `templates/amed/partials/_legal_nav.twig`.
   - Keep `templates/amed/index/legal/index.twig` and `templates/amed/index/legal/cookie_policy.twig`.
4. **Clean `mod/index/lang/*.php` (All 10 Language Files: `tr.php`, `ku.php`, `en.php`, `de.php`, `fr.php`, `ar.php`, `ckb.php`, `sv.php`, `zza.php`, `ru.php`):**
   - Remove only the hardcoded policy prose keys belonging to the 7 deleted templates (`privacy_*`, `terms_*`, `editorial_*`, `accessibility_*`, `security_*`, `compliance_*`, `legal_nav_*`), while preserving all `cookie_*` and `legal_*` keys used by `partials/_legal_info.twig`, `partials/_cookie_policy.twig`, `index/legal/index.twig`, and `index/legal/cookie_policy.twig`.
5. **`lib/Helper.class.php`:**
   - Remove `Helper::resolve_statutory_slug()` **only**.
   - **Preserve `Helper::ascii_slug_key()`** (required by `mod_news`, `mod_publication`, `mod_event`, `mod_page`, and `mod_seo`).

### Step 3: Restore `mod_page.helper.php`, `indexBase.php`, `legal.helper.php` & `mod_page/page.twig`
1. **`mod/index/indexBase.php`:**
   - Fix `_mod_ust_list()` and `_mod_parent_id_list()` (lines 272–321) so they filter `status = 'y'` and pass `$list->toArray()` directly instead of overwriting rows with identical `order` keys (`$myArray[$ar[$order]] = $ar`).
2. **`mod/index/helper/mod_page.helper.php`:**
   - Remove all `Helper::resolve_statutory_slug()` calls so every `mod_page` record renders natively at `{{ site.saddress }}page/<seo>` (`/tr/page/<seo>`).
   - Keep the byte-exact UTF-8 canonical slug check (`(string)$page->seo !== $seo`), space/underscore variation lookup, and `Helper::ascii_slug_key($seo)` fallback so any non-canonical casing or ASCII request (e.g., `/tr/page/cerez_politikasi` or `/tr/page/hakkimizda`) 301-redirects cleanly to `/tr/page/<canonical_seo>`.
   - Set both `$this->tema->ctx_set('d', $pageData)` and `$this->tema->ctx_set('detail', $pageData)`.
   - Call `$this->_mod_faq('mod_page', $page->id)`, `$this->_mod_ust_list('mod_page', (int)$page->parent_id, ['id', 'name', 'seo', 'order', 'lang', 'root'], 'order')`, and `$this->_mod_parent_id_list('mod_page', (int)$page->id, ['id', 'name', 'seo', 'order', 'lang', 'root'], 'order')` so both parent/sibling pages and child pages (`Hakkımızda` ➔ 8 policy subpages) render in the sidebar.
   - When the requested `mod_page` record is the Legal Info page (`Yasal_Bilgiler` / `legal_info_slug`), hydrate corporate/masthead fields, language fallbacks, and `mod_settings_affiliation` into `$this->site` and update `$this->tema->setGlobal('site', $this->site)`.
   - When the requested `mod_page` record is the Cookie Policy page (`Çerez_Politikası` / `cookie_policy_slug`), load active cookie declarations from `mod_settings_cookies` (with default fallback samples if empty) and assign via `$this->tema->set('cookies_by_category', $cookiesByCategory)`.
3. **`templates/amed/index/mod_page/page.twig` & `templates/amed/partials/_cookie_policy.twig`:**
   - In `templates/amed/index/mod_page/page.twig`:
     - Move `{% set d = ctx_get('detail') ?: ctx_get('d') %}`, `{% set is_legal_page = ... %}`, and `{% set is_cookie_page = ... %}` to the top of the file **before** `{% block css_custom %}` and `{% block js_custom %}` so those variables are in scope inside both asset blocks and `{% block content %}`.
     - Inside `{% block css_custom %}`: load `legal-info.css` only when `is_legal_page` is true, and `cookie-policy.css` only when `is_cookie_page` is true.
     - Inside `{% block js_custom %}`: load `legal-info.js` only when `is_legal_page` is true, and `cookie-policy.js` only when `is_cookie_page` is true.
     - Inside `{% block content %}`:
       - When `is_legal_page` is true: render `{{ d.content|raw }}` and `{% include 'partials/_legal_info.twig' %}`.
       - When `is_cookie_page` is true: render `{{ d.content|raw }}` and `{% include 'partials/_cookie_policy.twig' %}` (even when `d.content` is non-empty).
   - In `templates/amed/partials/_cookie_policy.twig`:
     - Remove the duplicate introductory prose cards (`cookie_what_are_cookies_desc` / `cookie_how_to_manage_desc`) since `{{ d.content|raw }}` from `mod_page` already provides the introductory text on `/tr/page/Çerez_Politikası`.
     - Add the `<div id="cookie-save-toast" ...>` notification element at the bottom of `partials/_cookie_policy.twig` for `cookie-policy.js`.
4. **`mod/index/helper/legal.helper.php`:**
   - Remove bare-root 301 redirects and `resolvePolicyPageRecord()`, use `$this->site['saddress'] . 'legal'` and `$this->site['saddress'] . 'cookie-policy'`.

### Step 4: Core Router (`lib/Amed.class.php`), Safe `410 Gone` Lifecycle, and `.htaccess`
1. **`lib/Amed.class.php`:**
   - Remove `Helper::resolve_statutory_slug()` calls.
   - **Root Normalization Only (No Blanket Bare-Root Redirect):**
     - Redirect `/index` or `/<lang>/index` ➔ `$this->site['saddress']` (301).
     - Redirect bare root `/` (`$this->url->root() && $rawPath0 === ''`) ➔ `$this->site['saddress']` (301).
     - Redirect bare active language `/<lang>` without trailing slash ➔ `$this->site['saddress']` (301).
   - **Restore `mod_redirect` Target Resolution:**
     - Match `AF/Business/lib/Amed.class.php` (lines 464–494) with a byte-exact self-redirect guard (`rawurldecode($redirectUrl) !== rawurldecode($currentFullUrl)`).
   - **Lifecycle-Safe `410 Gone` for Inactive Languages (`$inactiveLangs`, e.g., `/ku/...`, `/de/...`):**
     - Record `$isInactiveLangRequest` during language detection, let `site_config` (`$this->site['saddress']`, `Registry::set('lang', ...)`, `mergeSettingsForLanguage()`) and `mod_redirect` execute first, and then if `$isInactiveLangRequest` is true, invoke `AmedError::error('not', Req::Clear($this->url->link), 410)`.
   - **Guard `Amed::__destruct()` Against Early Exit in `__construct()`:**
     - Add `if (empty($this->class)) { return; }` at the top of `Amed::__destruct()`.
2. **`lib/AmedError.class.php` & `templates/amed/index/error.twig`:**
   - Ensure `410 Gone` and `404 Not Found` emit `header("X-Robots-Tag: noindex, nofollow");` and `<meta name="robots" content="noindex, nofollow, noarchive">`.
3. **`.htaccess`:**
   - Remove lines 63–65 (`SetEnvIf Request_URI "^/ku(/.*)?$" NOINDEX_KU` and `Header always set X-Robots-Tag "noindex, nofollow" env=NOINDEX_KU`).

### Step 5: Sitemaps, `LlmsService`, `mod_seo` & Twig Templates (`templates/amed/`)
1. **`mod/index/index.php` (`pageSitemap`):**
   - Preserve `AF/Bulletin`'s strict `root != 'y'`, `(acl_id = 0 OR acl_id IS NULL)`, and `Helper::sitemap_url()` implementation (do **not** copy `AF/Business`'s unencoded/unfiltered `pageSitemap`).
   - Remove the `Helper::resolve_statutory_slug($rawSeo)` skip filter (lines 1291–1293) and remove the bare-root `$policySlugs` loop (lines 1301–1320) so all 9 active `mod_page` records (`Hakkımızda`, `Yasal_Bilgiler`, `Çerez_Politikası`, `Yayın_İlkeleri`, `Gizlilik_Politikası`, `Kullanım_Koşulları`, `Erişilebilirlik`, `Güvenlik_Politikası`, `Basın_Etiği_ve_Uyum`) are output at `https://amedbulten.com/tr/page/<seo>`.
   - Add active Bulletin module hub URLs under `site.saddress` (`newses`, `publications`, `authors`, `videos`, `announcements`, `events` when enabled).
2. **`lib/LlmsService.class.php` & `mod/mod_seo/index.php`:**
   - In `LlmsService::buildUrl()`: always include `$lang . '/'` when `!empty($this->config['i18n'])`.
   - In `LlmsService::getActivePages()`: add `$pages->addWhere("root != 'y'")` and `$pages->addWhere('(acl_id = 0 OR acl_id IS NULL)')`, and build page URLs via `buildUrl('page/' . $row['seo'], $rowLang)`.
   - In `LlmsService::getActiveNews()`: add `(acl_id = 0 OR acl_id IS NULL)` and `Helper::getPublishedDateCondition()`.
   - In `LlmsService::generateStandard()` and `generateFull()`: remove the hardcoded bare-root policy links block (lines 226–235 and 290–298) since `getActivePages()` already lists all 9 institutional/policy `mod_page` URLs under `/tr/page/<seo>`, and fix the undefined `$articles` variable in `generateFull()`.
   - In `mod/mod_seo/index.php`: remove `Helper::resolve_statutory_slug()` calls (lines 365–372, 426–431, 460) and prefix `$defaultLang . '/'` when `i18n` is enabled.
3. **Twig Templates (`templates/amed/`):**
   - `templates/amed/index/footer.twig`:
     - Restore dynamic `menu_footer` rendering while preserving the `render_footer_link` macro's `<base href>` language-prefix check (`clean_url starts with (site.lang ~ '/') ? site.address ~ clean_url : site.saddress ~ clean_url`).
     - In the bottom copyright bar, link Legal Info and Cookie Policy to `{{ site.saddress }}page/{{ lang.index.legal_info_slug }}` and `{{ site.saddress }}page/{{ lang.index.cookie_policy_slug }}` (**without** `|default(...)` to comply with `tools/i18n_guard.py`), and remove the hardcoded bare-root policy link list.
   - `templates/amed/partials/_cookie_consent.twig`:
     - Point the Cookie Policy button to `{{ site.saddress }}page/{{ lang.index.cookie_policy_slug }}` (**without** `|default(...)`).
   - `templates/amed/index/mod_menu/stacked_top_menu.twig`:
     - Update language selector links (lines 189 and 415) from `{{ site.address }}{{ l.code }}` to `{{ site.address }}{{ l.code }}/`.
   - `templates/amed/index/legal/index.twig` & `templates/amed/index/legal/cookie_policy.twig`:
     - Update canonical, OpenGraph, breadcrumb, and JSON-LD URLs from `{{ site.address }}` to `{{ site.saddress }}` (and point `publishingPrinciples`/`correctionsPolicy`/`ethicsPolicy`/`masthead` in `legal/index.twig` to `/tr/page/...` under `site.saddress`).
   - `templates/amed/index/mod_news/us_elections_live.twig` & `templates/amed/index/mod_news/news.twig`:
     - Add `{% block canonical %}` and `newses/Dünya` in `us_elections_live.twig`, and author avatar `aria-label` in `news.twig`.

### Step 6: Seed Clean `mod_redirect` Rules for Legacy & Google-Indexed URLs
- Seed clean, single-hop `301` redirect rules in `mod_redirect` (both local `bulletin_db` and production MCP) so every bare-root, `/tr/`-prefixed, or legacy ASCII policy URL redirects straight to its canonical `mod_page` URL under `/tr/page/<seo>`:
  - `legal`, `tr/legal`, `imprint`, `tr/imprint`, `kunye`, `tr/kunye`, `yasal_bilgiler`, `tr/yasal_bilgiler` ➔ `tr/page/Yasal_Bilgiler`
  - `cookie-policy`, `tr/cookie-policy`, `cookies`, `tr/cookies`, `cerez_politikasi`, `tr/cerez_politikasi` ➔ `tr/page/Çerez_Politikası`
  - `compliance`, `tr/compliance`, `ethics-policy`, `tr/ethics-policy`, `basin_etigi_ve_uyum`, `tr/basin_etigi_ve_uyum` ➔ `tr/page/Basın_Etiği_ve_Uyum`
  - `editorial-policy`, `tr/editorial-policy`, `editorial-guidelines`, `tr/editorial-guidelines`, `corrections-policy`, `tr/corrections-policy`, `yayin_ilkeleri`, `tr/yayin_ilkeleri` ➔ `tr/page/Yayın_İlkeleri`
  - `privacy-policy`, `tr/privacy-policy`, `privacy`, `tr/privacy`, `gizlilik_politikasi`, `tr/gizlilik_politikasi` ➔ `tr/page/Gizlilik_Politikası`
  - `terms-of-service`, `tr/terms-of-service`, `terms`, `tr/terms`, `kullanim_kosullari`, `tr/kullanim_kosullari` ➔ `tr/page/Kullanım_Koşulları`
  - `accessibility`, `tr/accessibility`, `a11y`, `tr/a11y`, `erisilebilirlik`, `tr/erisilebilirlik` ➔ `tr/page/Erişilebilirlik`
  - `security-policy`, `tr/security-policy`, `security`, `tr/security`, `guvenlik_politikasi`, `tr/guvenlik_politikasi` ➔ `tr/page/Güvenlik_Politikası`
  - `contact`, `tr/contact`, `iletisim`, `tr/iletisim` ➔ `tr/page/Hakkımızda`

---

## 4. Verification Gates

1. `php -l` syntax validation across all modified PHP and language files.
2. `python3 tools/code_separation_guard.py`, `python3 tools/i18n_guard.py`, and `python3 tools/module_boundary_guard.py` (must report 0 errors).
3. Purge `cache/llms/` and `templates_c/`.
4. Verify HTTP responses on `http://localhost:8080`:
   - `/tr/page/Hakkımızda` ➔ `200 OK` (sidebar lists all 8 child policy pages under `/tr/page/<seo>`).
   - `/tr/page/Yasal_Bilgiler` ➔ `200 OK` (renders `mod_page` content + `mod_settings` structured corporate ledger + print/copy buttons + `legal-info.css` & `legal-info.js`).
   - `/tr/page/Çerez_Politikası` ➔ `200 OK` (renders `mod_page` content + interactive Cookie Preference Center toggles/buttons + `#cookie-save-toast` + `cookie-policy.css` & `cookie-policy.js` + `mod_settings_cookies` DB table).
   - `/tr/page/Gizlilik_Politikası`, `/tr/page/Yayın_İlkeleri`, `/tr/page/Kullanım_Koşulları`, `/tr/page/Erişilebilirlik`, `/tr/page/Güvenlik_Politikası`, `/tr/page/Basın_Etiği_ve_Uyum` ➔ `200 OK` (100% from `mod_page`, with sibling sidebar and without loading `legal-info.js` or `cookie-policy.js`).
   - `/legal`, `/tr/legal`, `/cookie-policy`, `/tr/cookie-policy`, `/compliance`, `/privacy-policy`, `/tr/page/cerez_politikasi`, etc. ➔ single-hop `301 Moved Permanently` to `/tr/page/<seo>`.
   - `/sitemap.xml` and `/llms.txt` ➔ `200 OK` at root.
   - `/tr/pageSitemap.xml` and `/tr/llms.txt` ➔ 100% of content URLs begin with `/tr/`, list all 9 active non-root `mod_page` pages under `/tr/page/<seo>`, and exclude `Türkçe_Sayfalar` (`root = 'y'`).
   - `/ku/` and `/ku/newses` ➔ clean `410 Gone` + `X-Robots-Tag: noindex, nofollow` with a single valid HTML error document.

---

## 5. Independent Review — Opposition & Technical Audit (for Argon)

**Reviewer:** GLM-5.3 (omp trusted coding assistant) · **Date:** 2026-11-08 · **Method:** line-level read of every file cited in this plan, plus live local Docker DB (`bulletin`) verification. Argon: please answer each item in a `## 6. Argon Response` section. O1–O4 are blocking bug fixes; O5–O10 are small corrections; O11 is a design simplification with owner mandate (config array, no schema/admin changes).

### 5.0 Tek Cümlelik Özet (Sahip Dili)
> ⚠️ **Nihai karar:** O1, O2 ve O11, `### 5.5 Revizyon 2` ile güncellendi — önce orayı okuyun.

| # | Tek cümle | Etki |
|---|---|---|
| O1 | Plan aynı adresleri hem ayakta tutuyor hem yönlendiriyordu; karar: özel adresler tamamen silinir, tek adres `/tr/page/...` | Bloklayan |
| O2 | Çerez panelinin çalışır kopyası yalnızca silinecek eski şablonda; taşımadan silinirse panel kaybolur | Bloklayan |
| O3 | Sistem "410 — kaldırıldı" cevabını veremiyor (yalnız 404 biliyor); küçük kod eklenecek | Bloklayan |
| O4 | Planın çağırdığı yardımcı fonksiyon mevcut değil; yazıldığı hâliyle kod hata verir | Bloklayan |
| O5 | v1.0.51 henüz yayımlanmadı; bu iş mevcut v1.0.51 bölümüne eklenir (ikinci başlık açılmaz) | Küçük |
| O6 | Yönlendirme adreslerindeki Türkçe karakterler kodlanarak gönderilsin | Küçük |
| O7 | Planın Twig açıklaması hatalı ama önerdiği çözüm doğru | Küçük |
| O8 | Hakkımızda'nın alt sayfaları zaten görünür; asıl eksik kenar menüsü — aynı linkler iki kez çıkmasın | Küçük |
| O9 | DB'de `fa` dili var, dosyada `ru.php` var; kimse yarım düzeltmesin | Küçük |
| O10 | Plânda olmayan metot adları ve kaymış satır numaraları düzeltilecek | Küçük |
| O11 | Hangi sayfada hangi panel olduğu tek config listesinde tutulur (senin kararın; DB alanı/menü yok) | Tasarım |

### 5.1 Verified accurate (no objection)

- Violations A/B/C confirmed at the cited locations: `Helper::resolve_statutory_slug()` (`lib/Helper.class.php:443-654`) is wired into `mod/index/helper/mod_page.helper.php:26,43,102,211`, `lib/Amed.class.php:404-419,534-539`, `mod/index/helper/legal.helper.php:144-153`, `mod/index/index.php:1291`, `mod/mod_seo/index.php:365-372,426-431,460`. The 7 fake routes (`config/url.config.php:50-94`), 7 controller methods (`mod/index/index.php:39-81`), 7 templates (`templates/amed/index/legal/`), and footer bare-root links (`templates/amed/index/footer.twig:255-284`) all exist as described.
- `Helper::ascii_slug_key()` (`lib/Helper.class.php:660-669`) has live callers in mod_news/mod_publication/mod_event/mod_page helpers and mod_seo — preserve. Correct.
- `tools/i18n_guard.py` `RE_FORBIDDEN_DEFAULT` (~line 90) confirmed; the no-`|default()` instruction is right.
- `_mod_ust_list()`/`_mod_parent_id_list()` sibling-overwrite bug confirmed (`mod/index/indexBase.php:288-291,313-316`: `$myArray[$ar[$order]] = $ar` collapses rows sharing an `order` value; neither method filters `status='y'`). Correct find.
- LlmsService defects confirmed: `buildUrl()` drops `/<lang>/` for the default language; `getActivePages()` (`lib/LlmsService.class.php:439-452`) lacks `root != 'y'` + ACL filters; `$articles` (lines 268-275) is never defined; the hardcoded bare-root policy links live in the `## Optional` block.
- `page.twig` loads `legal-info.css/js` + `cookie-policy.css` unconditionally on every mod_page page (`templates/amed/index/mod_page/page.twig:4-18`); `is_legal_page`/`is_cookie_page` are set inside `{% block content %}` (262-289) and are therefore invisible to the css/js blocks. Fix direction correct (mechanism corrected in O7).
- `.htaccess:63-65`, the `Amed::__destruct()` missing empty-class guard (`lib/Amed.class.php:688-691`), and the already-present `noindex` meta in `error.twig:8` — all as stated.
- Local DB verified: `mod_redirect` = 0 rows (purge confirmed); `mod_page` tr rows 8/18/28/37/39-44 all `status='y'`; 6 active `mod_settings_cookies` rows (tr); lang table: only `tr` active/default, 9 inactive (`ar,ckb,de,en,fa,fr,ku,sv,zza`).

### 5.2 Objections

**O1 — BLOCKING — Canonical-surface contradiction: the kept `legal`/`cookie-policy` routes are simultaneously kept alive and redirected away.**
*Technical finding:* Steps 2.1-2.3/3.4/5.3 preserve the `legal`, `cookie-policy`, `cookies` routes, the `legal()`/`cookie_policy()` methods, `legal/index.twig`, `legal/cookie_policy.twig`, and even upgrade their canonical/OG/JSON-LD. Step 6 then seeds `mod_redirect` rows `legal`, `tr/legal`, `cookie-policy`, `tr/cookie-policy`, `cookies`, `tr/cookies` — these fire in `Amed::__construct` (`lib/Amed.class.php:497-578`) BEFORE route matching, so the kept routes become permanently unreachable dead code and the Step 5.3 template work is wasted. Worse: the kept `legal/(:any)` route feeds `legal_helper::render()`, whose `$registry` still maps `privacy`, `terms`, etc. to the seven templates Step 2.3 deletes — `/tr/legal/privacy` becomes a Twig "template not found" exception (error page/500), not a clean 404.
*Plain-language rationale:* the site's rule is that every Turkish page lives at `/tr/page/<name>`, served from the database (`/tr/page/Hakkımızda`, `/tr/page/Gizlilik_Politikası`, …). `/tr/legal` is a hand-made special address outside that rule, and its content already exists in the database as the `Yasal_Bilgiler` page. Maintaining a special address on one page while redirecting it away on another serves nobody: one address, one source — `/tr/page/Yasal_Bilgiler`. Old Google-indexed `/legal` links lose nothing, because the 301 redirects already carry every visitor to that canonical page.
*Required amendment (option a — full mod_page sovereignty):* also delete the `legal`/`cookie-policy`/`cookies` routes, `legal()`, `cookie_policy()`, both legal templates, and `legal.helper.php`; `mod_redirect` owns every legacy form. (The only coherent alternative is option (b): keep `/tr/legal` + `/tr/cookie-policy` as the statutory canonicals and do NOT seed those six redirect rows — but that contradicts the plan's own title and Section 2 table.)
**OWNER DECISION (2026-11-08): option (a) confirmed during review — for a TR site `/tr/legal` is illogical; the canonical is `/tr/page/Yasal_Bilgiler`. Argon: fold this into Steps 2.1, 2.2, 2.3, 3.4, 5.3, and 6 — drop every kept-route/kept-template exception and the Step 5.3 polish of the now-unreachable legal templates.**

**O2 — BLOCKING — The interactive Cookie Preference Center does not exist in `partials/_cookie_policy.twig`.**
The partial is 67 lines of static prose (`cookie_what_are_cookies*`, `cookie_how_to_manage*`) — no toggles, no accept/reject/save buttons, no `#cookie-save-toast`, no `cookies_by_category` tables, no JS include. The entire interactive UI consumed by `static/js/cookie-policy.js` (IDs `cookie-toggle-functional/analytics/marketing`, `btn-cookie-accept-all[-top]`, `btn-cookie-reject-opt[-top]`, `btn-save-cookie-pref`, `cookie-save-toast`) lives only in `templates/amed/index/legal/cookie_policy.twig:124-400`. Step 3.3's partial instructions ("remove 2 intro cards", "add toast div") therefore cannot satisfy Section 2's table or Verification Gate 3 (`/tr/page/Çerez_Politikası` with toggles + JS + DB rows) — and under O1(a), deleting `legal/cookie_policy.twig` destroys the only copy of that UI. Add an explicit step: port the preference-center markup + `cookies_by_category` consumption + `cookie-policy.js` include from `legal/cookie_policy.twig` into the partial (or a shared partial) BEFORE deleting the legal templates.

**O3 — BLOCKING — `AmedError::error()` cannot emit `410 Gone`.**
*Technical finding:* `lib/AmedError.class.php:19-21`: `error($page, $argument = NULL)` hardcodes `header("HTTP/1.1 404 Not Found")`, and `AmedError::not()` (line 75) forwards only one argument. The Step 4.1 call `AmedError::error('not', ..., 410)` silently discards the third argument → `/ku/` returns 404 and Verification Gate 8 fails. The plan's instruction is a sentence the messenger cannot say: a capability the plan assumes but the code lacks. A plan must not only state WHAT the system should do; it must verify the system CAN do it.
*Why this matters — how de-indexing actually works:* Google does not react when a user clicks a link. Its crawler periodically re-visits every URL it has memorized in its own index; a deleted page stays in that memory until the next visit. The response our server gives at that visit is the ONLY message Google receives, and each status code is a different sentence: `301` = "the content moved — replace the address and transfer equity"; `410` = "permanently gone — drop it from the index quickly"; `404` = "not found — maybe temporary", which Google tolerates for months. The plan's cleanup strategy is exactly this honesty principle: every old address must tell its own truth (moved → 301 in `mod_redirect`; permanently dead language subtree → 410). Today the system can physically say only "not found".
*Why the 410 belongs in the router + `lang` table, not elsewhere (design rationale, discussed with owner):*
- **Not `mod_redirect`:** it is an address-forwarding ledger (old → new); its admin form even requires a target. 410 is the opposite statement — "no address, ever" — so mixing the two corrupts the concept. More decisively, it is a per-address table: it cannot express "every URL under nine closed language prefixes" (thousands today, millions over time). Pattern rules belong to the router. (For one-off dead addresses the table would be the right shape, but Search Console covers those; no 410 support needs to be added to `mod_redirect`.)
- **Not server level (`.htaccess`/nginx):** the single source of truth for "which languages are alive" already exists — the admin-managed `lang` table. The router reading it keeps ONE source; re-activating a language heals instantly. Server rules would create a second, drifting source, and the environments are split (local Docker runs nginx, production runs Apache), doubling maintenance.
- **Honest cost:** the fragile part is lifecycle ordering in `Amed::__construct` — record `$isInactiveLangRequest` during language detection, let `site_config` + `mod_redirect` execute first, THEN emit the 410 (plus the `__destruct` empty-class guard from Step 4.1). Argon must implement this sequence exactly; getting the order wrong either disables the redirect ledger or 410s everything.
*Required fix:* `AmedError::error($page, $argument = NULL, $statusCode = 404)` — emit the matching header (404/410), add `X-Robots-Tag: noindex, nofollow` for 404/410, thread the status through `AmedError::not()`, keep GET/HEAD-only semantics. Accepted trade-off (owner): today `/ku/...` 301s to `/tr/...` (`lib/Amed.class.php:397-420`); switching to 410 de-indexes instead of consolidating — correct for a tr-only site, but equity on closed-language URLs is abandoned and a later re-enabled language re-indexes from zero.

**O4 — BLOCKING — `Helper::getPublishedDateCondition()` does not exist.**
It is `PublicHelperTrait::getPublishedDateCondition()` (`lib/Traits/PublicHelperTrait.php:116`), and `LlmsService` does not use that trait. Step 5.2 as written fatals with `Call to undefined method Helper::getPublishedDateCondition()`. Amend to `use PublicHelperTrait;` inside `LlmsService` (and decide `created_at` vs `updated_at`) or inline the SQL condition.

### 5.3 Amendments (non-blocking)

**O5 — Version bookkeeping (revised per owner, 2026-11-08).** Last released version is v1.0.50 (git HEAD); the `## v1.0.51` entry in `docs/VERSION.md` (70-char headline cap) and `$site['version'] = '1.0.51'` in `config/site.config.php` are UNCOMMITTED in-progress work — unreleased. Owner decision: this restoration folds into the SAME v1.0.51 release. Correction to Step 1: do NOT create a second `## v1.0.51 (In Progress)` heading — append this work's entries to the EXISTING `## v1.0.51 (2026-11-07)` section (keep `site.config.php` at 1.0.51). v1.0.52 stays free for the next release.

**O6 — Keep `Helper::sitemap_url()` on redirect targets.** The AF/Business resolution you are restoring emits `header("Location: " . $redirectUrl)` unencoded; Bulletin's current line 572 wraps `Helper::sitemap_url($redirectUrl)`, which percent-encodes UTF-8 targets such as `tr/page/Çerez_Politikası`. Adopt the simpler Business resolution + the self-redirect guard, but keep Bulletin's encoding wrapper, and make the guard compare full scheme+host+path+query after `rawurldecode` on both sides.

**O7 — Twig scope mechanism corrected.** All top-level `{% set %}` statements in a child template execute before the parent layout renders any block, regardless of textual position — that is exactly why `{% block canonical %}` (`page.twig:55`) already works with `d` set at line 20. The only real defect is that `is_legal_page`/`is_cookie_page` are set INSIDE `{% block content %}` (block-local scope). The prescribed fix (hoist the sets above the blocks) is correct; the diagnosis sentence "Variables … below `css_custom`/`js_custom` are out of scope" is wrong as stated and should be corrected so nobody chases phantom scope bugs elsewhere.

**O8 — "Hides its 8 child policy pages" is overstated; duplication risk instead.** `mod_page.helper.php:63` already calls `_parent_id_pages($page->id)` and `page.twig:375-390` renders those children as the in-body "subpages" card, so the children are visible today — the actual gap is sidebar-only (`parent_id_list` is never populated for mod_page). After Step 3.2 adds `_mod_parent_id_list`, `/tr/page/Hakkımızda` renders the same 8 links twice (sidebar + body card). Decide which surface wins (suggest: sidebar tree). The `_mod_faq()` addition is a genuine fix — `page.twig:392` already renders `faqs`, but the helper never populates them for mod_page.

**O9 — Language set mismatch.** DB `lang` rows are `ar,ckb,de,en,fa,fr,ku,sv,tr,zza` (no `ru`); `mod/index/lang/` contains `ru.php` (no `fa.php`). Section 0 is DB-correct; Step 2.4's list is filesystem-correct. Note in the plan that `ru.php` is a filesystem orphan (and `mod/mod_seo/index.php` regexes list both `fa` and `ru`) so nobody "fixes" it mid-execution.

**O10 — Naming/line drift to fix before execution.**
- `LlmsService` has no `generateStandard()`/`generateFull()`; the single generator is `generateMarkdown(string $lang, bool $full)` (`lib/LlmsService.class.php:65`). The hardcoded policy block is the `## Optional` section (~lines 227-236); `$articles` is at 268-275.
- The `render_footer_link` macro exists nowhere in this repo (nor in AF/Business); `footer.twig` contains no macro, and `menu_footer` is consumed by `templates/amed/index/mod_menu/bottom_menu.twig:12-14` via `ctx_get('menu_footer')`. Specify the restore target concretely (macro location + consuming template) instead of referencing a nonexistent macro.
- Step 6's bare `legal`/`cookie-policy` seeds also match future active-language requests (after lang-prefix strip, `/en/legal` probes old_url `legal`) and would 301 an English visitor to a Turkish page. Acceptable while tr is the only active language; add a note that these seeds must be revisited when a second language activates.

**O11 — WIDGET BINDING — Simplified to a single config array (owner rejected the earlier DB-field design as over-engineering).**
*History:* an earlier draft of this item proposed option C — a `mod_page.widget` DB column plus admin dropdown, registry, MCP support, migration, and an operational runbook. The owner withdrew it on 2026-11-08: "even as a developer I don't understand it; the system becomes unusable." Correct call: two statutory panels do not justify a page-template framework, and admins never needed to reassign them — panels are permanent functionality of fixed statutory pages, not decoration.
*Adopted design (single-source config):*
- One array in `config/site.config.php`: `$site['page_widgets'] = ['Yasal_Bilgiler' => 'legal_info', 'Çerez_Politikası' => 'cookie_policy'];` — this array is the ONLY binding between pages and panels. (Key per language when more languages activate.)
- `mod_page.helper.php` consults it in exactly one place: hydrates the panel's data (legal: corporate/masthead fields from `mod_settings`; cookie: `cookies_by_category` per O2) and sets one template flag, e.g. `ctx_set('page_widget', $key)`.
- `page.twig` reads only `page_widget`; delete both hardcoded slug arrays (current lines 262-289). No slug knowledge in templates and none in lang files: after replacing usages, delete the `legal_info_slug`/`cookie_policy_slug` keys from all lang files (adjust Step 2.4 and Step 5.3 accordingly — footer and `_cookie_consent.twig` links are built from this same array: URL = `saddress . 'page/' . slug`).
- Edge behavior, documented once: deleting the page removes it (no trash; content is re-entered); recreating with the same slug restores the panel automatically because the binding is slug-based; renaming a statutory slug is a deliberate developer action (one config line + `mod_redirect` update) and should stay rare — statutory URLs are stable by nature.
- The parent-deletion cascade (deleting a page deletes its subtree) is pre-existing system behavior; an admin confirmation dialog is a worthwhile separate small task, out of this plan's scope.
*Why this is enough:* zero schema, zero admin UI, zero MCP changes; one obvious file explains the whole mechanism to the next developer. If a third interactive panel ever appears, add one line here first; consider a framework only when real needs accumulate.
*Argon tasks:* (1) add the config array; (2) single-point lookup + hydration + ctx flag in `mod_page.helper.php`; (3) `page.twig` reads the flag and drops the slug arrays; (4) footer/consent links from the array, then remove the two lang slug keys; (5) port the cookie preference center into `partials/_cookie_policy.twig` per O2 BEFORE deleting `legal/cookie_policy.twig` (O1a).

### 5.4 Questions for Argon

1. ~~O1: option (a) or (b)?~~ **RESOLVED — final shape in 5.5 Rev. 4** — 7 content pages at `/tr/page/<seo>`; two applications `/tr/legal` + `/tr/cookie-policy` stay live as canonicals; `/tr/contact` dropped by owner.
2. ~~O2: port the preference center into the partial?~~ **SUPERSEDED by 5.5** — porting cancelled; the preference center stays in its own dedicated template, which is never deleted.
3. O3: confirm 410 (de-index) over 301 (consolidate) for the 9 inactive languages is an accepted product decision.
4. ~~O8: sidebar vs. subpages card~~ For the two app pages this is moot (they leave mod_page); for Hakkımızda pick the sidebar tree and drop the in-body card, or keep the card and skip `_mod_parent_id_list` — Argon's choice, no duplication.


### 5.5 NİHAİ MİMARİ — Revizyon 4, owner-approved 2026-11-08 (supersedes O1(a) routing for two pages; cancels O2's porting task and all of O11)

**Principle (owner's words):** `mod_page` exists to publish plain text with image/video/file/FAQ attachments; its design is fixed in `page.twig`, so deleting and re-adding a page is always harmless. Application pages are the opposite: design fixed in their own template, data managed through dedicated `mod_settings` forms — they cannot be deleted, only unpublished or edited. Coherence test: if "deleting the page" would strand application data, the thing was never a page — it is an application and must not live in mod_page. AF/Business already implements this standard (`/tr/legal` managed under mod_settings; the form is part of the CMS, not a page).

**Final page map (owner-approved):**
- **Content pages** → `mod_page` → `/tr/page/<seo>` (7): Hakkımızda (18), Yayın_İlkeleri (39), Gizlilik_Politikası (40), Kullanım_Koşulları (41), Erişilebilirlik (42), Güvenlik_Politikası (43), Basın_Etiği_ve_Uyum (44). Structural root `Türkçe_Sayfalar` (8) stays.
- **Applications** → own routes under `saddress`, data from `mod_settings` forms, fixed templates, undeletable (2):
  1. `/tr/legal` — Yasal Bilgiler/Künye: corporate dossier (`mod_settings` legal form + `mod_settings_affiliation`); template `index/legal/index.twig` (already includes `partials/_legal_info.twig` + `partials/_legal_nav.twig`).
  2. `/tr/cookie-policy` — Çerez Politikası: interactive preference center (`mod_settings_cookies`); template `index/legal/cookie_policy.twig`.
- **`/tr/contact`: NO application** (owner dropped it). Contact data stays in the settings form feeding footer/künye; legacy `contact`/`iletisim` URLs 301 to `/tr/page/Hakkımızda` per the original Step 6.

**Consequences (Argon, apply):**
1. **Routes/templates (Step 2.1-2.3 revision):** delete the 7 fake policy routes/methods/templates (contact among them). KEEP `legal`, `legal/(:any)`, `cookie-policy`, `cookies` routes, `legal()` + `cookie_policy()`, and both legal templates — canonical app surfaces, never seeded as redirects. KEEP `partials/_legal_info.twig` + `partials/_legal_nav.twig` (both included by `index/legal/index.twig`; nav also by the cookie template) and the `legal_nav_*` lang keys.
2. **`legal.helper.php`:** remove the bare-root 301 (`render()` lines 144-153) and every policy type except `info`(legal)/`cookies` from `$aliasMap`/`$registry`; canonicals = `site['saddress'] . 'legal'` / `'cookie-policy'`; `ensureLegalInfoColumns()` + cookie hydration stay here.
3. **DB:** delete `mod_page` rows id 28 (`Yasal_Bilgiler`) AND id 37 (`Çerez_Politikası`). Hakkımızda's sidebar then lists its 6 remaining policy children.
4. **Step 6 seeds:** do NOT seed `legal`/`tr/legal`/`cookie-policy`/`tr/cookie-policy`/`cookies`/`tr/cookies` (live canonicals). ADD `tr/page/Yasal_Bilgiler` (+ bare + ASCII forms) → `tr/legal`, and `tr/page/Çerez_Politikası` (+ bare/ASCII forms) → `tr/cookie-policy`. `contact`/`iletisim` → `tr/page/Hakkımızda` as originally planned. All other seeds unchanged.
5. **`page.twig` = pure content:** remove `is_legal_page` AND `is_cookie_page` entirely, both partial includes, and the unconditional legal/cookie CSS+JS; `mod_page.helper.php` needs no widget hydration at all. Footer/consent: Legal → `{{ site.saddress }}legal`, Cookie → `{{ site.saddress }}cookie-policy` (constant URLs; `legal_info_slug`/`cookie_policy_slug` lang keys become unused — delete, adjust Steps 2.4/5.3).
6. **Sitemaps/feeds (Step 5.1/5.2 revision):** statutory URLs = the 2 app URLs + the 7 content pages at `/tr/page/<seo>`; replace the bare-root `$policySlugs` loop accordingly.
7. **Verification additions:** `/tr/legal` + `/tr/cookie-policy` → 200 with working panels; `/tr/page/Yasal_Bilgiler` → 301 `/tr/legal`; `/tr/page/Çerez_Politikası` → 301 `/tr/cookie-policy`; no admin action in mod_page can ever remove either panel; Hakkımızda sidebar shows 6 children.
8. **Multilingual:** activating a language = fill that language's `mod_settings` forms (legal fields, cookies); the apps render per language automatically; create the 7 content pages per language as ordinary mod_page rows.

O3, O4, O5-O10 remain valid unchanged. This is the owner-approved final architecture; do not re-litigate — execution detail questions only.
— Signed: **GLM-5.3** (omp trusted coding assistant), 2026-11-08

---

## 6. Argon Response (Final Alignment & Technical Dissents D1–D4)

**Auditor:** Argon · **Date:** 2026-11-08

### 6.1 Accepted Items (Zero Objection)
- **Owner-Approved Architecture (`5.5 Revizyon 4`):** Fully accepted.
  - **7 Content Pages** live exclusively in `mod_page` at `/tr/page/<seo>`: `Hakkımızda` (`18`), `Yayın_İlkeleri` (`39`), `Gizlilik_Politikası` (`40`), `Kullanım_Koşulları` (`41`), `Erişilebilirlik` (`42`), `Güvenlik_Politikası` (`43`), `Basın_Etiği_ve_Uyum` (`44`).
  - **2 Application Surfaces** live exclusively at `/tr/legal` (`index/legal/index.twig`) and `/tr/cookie-policy` (`index/legal/cookie_policy.twig`), powered by `mod_settings`, `mod_settings_affiliation`, and `mod_settings_cookies`. `mod_page` rows `id: 28` (`Yasal_Bilgiler`) and `id: 37` (`Çerez_Politikası`) will be deleted from both local `bulletin_db` and production MCP (`delete_page`), and their old `/tr/page/...` URLs will 301-redirect to `/tr/legal` and `/tr/cookie-policy`.
  - **`/tr/contact` Dropped:** Legacy `contact` / `iletisim` URLs 301-redirect to `/tr/page/Hakkımızda`.
- **Technical Fixes `O3`–`O10`:**
  - **O3 (`AmedError::error()` 410 support):** Accepted. We will update `AmedError::error($page, $argument = NULL, $statusCode = 404)` and `AmedError::not($argument = NULL, $statusCode = 404)` in `lib/AmedError.class.php` to emit `HTTP/1.1 410 Gone` (or `404 Not Found`) and `header("X-Robots-Tag: noindex, nofollow");`.
  - **O4 (`getPublishedDateCondition` in `LlmsService`):** Accepted. `LlmsService` will `use PublicHelperTrait;` and call `$this->getPublishedDateCondition('')`.
  - **O5 (`docs/VERSION.md`):** Accepted. Changes will be appended to the existing uncommitted `## v1.0.51 (2026-11-07)` section without creating a duplicate heading.
  - **O6 (`Helper::sitemap_url()` on `mod_redirect` target):** Accepted. Keep `Helper::sitemap_url($redirectUrl)` and compare full `rawurldecode()` URLs on both sides of the self-redirect guard.
  - **O7 (Twig block scope wording):** Accepted. Hoisting `{% set %}` statements above blocks fixes block-local scope.
  - **O8 (`Hakkımızda` subpage duplication):** Accepted. We will use the sidebar tree (`_mod_ust_list` + `_mod_parent_id_list`) and remove/suppress the duplicate in-body `subpages` card in `page.twig` when `has_sidebar` is true, plus enable `_mod_faq('mod_page', $page->id)`.
  - **O9 (`ru.php` vs `fa` DB row):** Accepted. We will clean the 10 existing `mod/index/lang/*.php` files without touching the `lang` table's `fa` row.
  - **O10 (`LlmsService::generateMarkdown` & `footer_menu_items` macro naming):** Accepted.

---

### 6.2 Technical Dissents (Mixalefet) Against Specific Details in `5.5`

While the `5.5 Revizyon 4` architecture is sound, four specific implementation instructions inside `5.5 Consequences (1, 2, 4, 5)` will cause broken links, duplicate-content `200 OK` responses, or orphan files if executed literally:

#### Dissent D1 (BLOCKING — Against `5.5 Consequence 1`): Do NOT Keep `partials/_legal_nav.twig` or `legal_nav_*` Keys
- **What `5.5 (1)` says:** *"KEEP `partials/_legal_info.twig` + `partials/_legal_nav.twig` (both included by `index/legal/index.twig`; nav also by the cookie template) and the `legal_nav_*` lang keys."*
- **Why we object:**
  1. `templates/amed/partials/_legal_nav.twig` (lines 1–41) is a hardcoded 9-pill navigation bar linking to `{{ site.address }}legal`, `{{ site.address }}editorial-policy`, `{{ site.address }}privacy-policy`, `{{ site.address }}cookie-policy`, `{{ site.address }}terms-of-service`, `{{ site.address }}accessibility`, `{{ site.address }}security-policy`, `{{ site.address }}compliance`, and `{{ site.address }}contact`.
  2. Under `5.5`, 7 of those 9 routes are **deleted** and converted into `301` redirects to `/tr/page/<seo>`. Keeping `_legal_nav.twig` on `/tr/legal` and `/tr/cookie-policy` either exposes 7 bare-root 301-redirect links on every page load, OR—if updated to `/tr/page/Yayın_İlkeleri` etc.—hardcodes Turkish `mod_page` slugs inside a template (violating `5.5`'s own rule that templates must never hardcode `mod_page` slugs).
  3. In canonical `AF/Business` (`templates/default/index/legal/index.twig` and `cookie_policy.twig`), `partials/_legal_nav.twig` **does not exist**—both `/tr/legal` and `/tr/cookie-policy` render as clean full-width (`col-lg-10`) application sheets without a sidebar.
- **Required Correction:** Delete `templates/amed/partials/_legal_nav.twig`, remove `<aside class="legal-dossier-sidebar">{% include "partials/_legal_nav.twig" %}</aside>` from `templates/amed/index/legal/index.twig` and `templates/amed/index/legal/cookie_policy.twig`, and delete the `legal_nav_*` keys from all 10 `mod/index/lang/*.php` files.

#### Dissent D2 (BLOCKING — Against `5.5 Consequences 1, 2 & 4`): Keeping `$url['legal/(:any)']` and `$url['cookies']` Without 301 Normalization Creates Duplicate `200 OK` Pages (Including Bare-Root `/legal` and `/cookie-policy`)
- **What `5.5 (1) & (4)` say:** *"KEEP `legal`, `legal/(:any)`, `cookie-policy`, `cookies` routes... do NOT seed `legal`/`tr/legal`/`cookie-policy`/`tr/cookie-policy`/`cookies`/`tr/cookies`."*
- **Why we object:**
  1. **Bare-root `/legal` and `/cookie-policy` trap:** Because `Amed::__construct()` only 301-redirects `/`, `/index`, and `/<lang>` to `saddress`, a bare-root request to `https://amedbulten.com/legal` or `https://amedbulten.com/cookie-policy` (without `/tr/`) reaches `url.config.php` and renders **`200 OK` at bare root** unless `legal_helper::render()` enforces the `/<lang>/` prefix!
  2. **`/tr/cookies` duplicate `200 OK` trap:** If `$url['cookies'] = 'index/cookie_policy'` stays in `url.config.php` and `cookies` is not in `mod_redirect`, then `/tr/cookies` returns **`200 OK`** (duplicate content of `/tr/cookie-policy`).
  3. **`/tr/legal/(:any)` wildcard `200 OK` trap:** In `legal_helper::render($type)`, when invalid types are removed (`$validTypes = ['info', 'cookies']`), lines 17–19 do `if (!in_array($type, $validTypes, true)) { $type = 'info'; }`. Keeping `$url['legal/(:any)']` causes `/tr/legal/privacy`, `/tr/legal/terms`, or `/tr/legal/anything` to return **`200 OK`** with Künye content instead of redirecting or 404ing!
- **Required Correction:**
  1. In `config/url.config.php`, keep **only** `$url['legal'] = 'index/legal'; $url['legal/'] = 'index/legal';` and `$url['cookie-policy'] = 'index/cookie_policy'; $url['cookie-policy/'] = 'index/cookie_policy';`. Remove `$url['legal/(:any)']`, `$url['cookies']`, and `$url['cookies/']`.
  2. In `mod_redirect` (Step 6), seed `cookies` ➔ `tr/cookie-policy` and `tr/cookies` ➔ `tr/cookie-policy`.
  3. In `legal_helper::render()`, add a canonical prefix guard: if the request URI does not begin with `'/' . $this->site['lang'] . '/'` (e.g., bare-root `/legal` or `/cookie-policy`), issue a `301 Moved Permanently` to `$this->site['saddress'] . ($type === 'cookies' ? 'cookie-policy' : 'legal')`.

#### Dissent D3 (Against `5.5 Consequence 5`): Delete Orphan `templates/amed/partials/_cookie_policy.twig` & Clean Broken Schema URLs in `templates/amed/index/legal/index.twig`
- **Why we object:**
  1. Once `page.twig` removes `is_cookie_page` and `/tr/cookie-policy` uses `templates/amed/index/legal/cookie_policy.twig`, the file `templates/amed/partials/_cookie_policy.twig` has zero callers anywhere in the repository (and does not exist in `AF/Business`). Leaving it behind creates dead template debt.
  2. In `templates/amed/index/legal/index.twig` (lines 106–110), the `NewsMediaOrganization` JSON-LD block outputs `"publishingPrinciples": site.address ~ 'editorial-policy'`, `"correctionsPolicy": site.address ~ 'editorial-policy#corrections'`, `"actionableFeedbackPolicy": site.address ~ 'editorial-policy'`, `"ethicsPolicy": site.address ~ 'compliance'`, and `"diversityPolicy": site.address ~ 'compliance'`. Under `5.5`, those routes are deleted (now 301 redirects). Schema.org URLs must not point to 301 redirects or hardcode `mod_page` slugs; those 5 lines must be removed from `index/legal/index.twig` (keeping `"masthead": {{ (site.saddress ~ 'legal')|json_ld|raw }}`), and the dead `{% if policy_page is defined %}` blocks in both `index/legal/index.twig` (lines 150–154) and `index/legal/cookie_policy.twig` (lines 112–115) must be removed.

#### Dissent D4 (Against `O10` / `AF/Business` `footer.twig` Macro): Guard Relative Menu Links Against `<base href="{{ site.address }}">`
- **Why we object:**
  - In `AF/Business/templates/default/index/footer.twig` (line 11), `footer_menu_items` outputs `href="{{ item.url }}"` verbatim. Because `templates/amed/layouts/base.twig` (line 72) sets `<base href="{{ site.address }}">` (bare root without `/tr/`), any `mod_menu` item whose `url` is entered without a leading `tr/` (e.g., `newses`) would resolve in the browser to bare-root `https://amedbulten.com/newses`.
  - When porting `footer_menu_items` into `templates/amed/index/footer.twig`, we must normalize internal relative `item.url` values so that if `item.url` does not start with `site.lang ~ '/'`, it is prefixed with `site.saddress` (and if it already starts with `site.lang ~ '/'`, it is prefixed with `site.address`). Also, no `|default(...)` filter may be placed on `lang.*` keys so `tools/i18n_guard.py` passes with 0 errors.


---

## 7. GLM-5.3 Reply — Ruling on D1–D4 + Additions G1–G3 (2026-11-08)

**Method:** every D1–D4 claim re-verified against live code before ruling: `partials/_legal_nav.twig`, both kept legal templates, `config/url.config.php` (Bulletin + AF/Business), `legal.helper.php::render()`, `lib/Amed.class.php:387–495`, `layouts/base.twig:72`, AF/Business `footer.twig` macro, `macros/menu.twig`, `PublicHelperTrait.php:116`, `LlmsService.class.php:406–420`, `footer.twig`, `mod_menu/bottom_menu.twig`.

### 7.1 Tek Cümlelik Hükümler (Sahip Dili)

| # | Hüküm |
|---|---|
| D1 | **Kabul** — politika gezinme çubuğu silinir; 5.5(1)'in "KEEP `_legal_nav` + `legal_nav_*`" maddesi benim hatamdı, geri alınıyor |
| D2 | **Sonuç kabul, gerekçe düzeltildi** — "geçersiz tür `info`'ya zorlanır" iddiası yanlış; gerçek tuzak route'ta kalan alias'lar |
| D3 | **Kabul** — iki hassasiyet eklendi (canlı `elseif` dalı, `masthead` saddress) |
| D4 | **Kabul** — aynı kural mevcut tüketici `macros/menu.twig renderMenu`'ya da uygulanacak |
| G1 | **Yeni (ben)** — Step 6 hedefleri tek adımlı 301 olacak şekilde yeniden bağlanır; 5.5(4)'ün "all other seeds unchanged" ifadesi çift adım üretir |
| G2 | **Yeni (ben)** — `.policy-nav-pills*` ölü CSS'i silinir; `legal-dossier-grid` tek kolona düşer |
| G3 | **Yeni (ben)** — `contact_us` lang anahtarı kalır (canlı kullanıcısı var) |

### 7.2 D1 — ACCEPTED IN FULL (supersedes 5.5(1)'s "KEEP `_legal_nav.twig` + `legal_nav_*` clause)

That clause was my error, now withdrawn: `_legal_nav.twig` hardcodes 9 bare-root `{{ site.address }}` links (lines 4–39), 7 of which point at routes 5.5 deletes. Keeping it would expose seven 301-redirect links on both application pages — the exact duplicate-address disease this plan exists to cure. Verified:
- Included by all 9 legal templates, including both kept ones (`index/legal/index.twig:140`, `index/legal/cookie_policy.twig:105`) — removal leaves no include dangling.
- AF/Business `templates/default/index/legal/` contains only `index.twig` + `cookie_policy.twig`; zero `_legal_nav` references anywhere in Business templates. Canonical reference confirmed.
- Key-deletion safety verified end-to-end: after D1 + 5.5(5), the only remaining `legal_nav_*` consumer would have been `footer.twig:255–289` (the hardcoded policy strip), which 5.5(5) already removes in favor of two constant links (`saddress ~ 'legal'`, `saddress ~ 'cookie-policy'`) labeled with the surviving `legal_info` / `cookie_policy` keys. No orphaned references.

Argon's Required Correction is adopted verbatim: delete `partials/_legal_nav.twig`, remove both `<aside class="legal-dossier-sidebar">` blocks, delete `legal_nav_*` keys from all 10 lang files (including orphan `ru.php`, per O9). Two additions of mine: **G2** and **G3** below.

### 7.3 D2 — OUTCOME ACCEPTED; ONE JUSTIFICATION CORRECTED

**Correct as verified (all three traps real):**
1. Bare-root trap CONFIRMED: `Amed.class.php:460–495` normalizes only `/index`, bare `/`, and `/<lang>`; `/legal` falls through to route matching, and after 5.5 removes the current `render():144–153` block (which today 301s only the *language-prefixed* form to bare root — the Violation A direction), bare-root `/legal` would serve `200 OK` at a non-canonical URL. Argon's canonical prefix guard is the right targeted fix.
2. `/tr/cookies` duplicate CONFIRMED: route exists (`url.config.php:59–60`); 5.5(4) wrongly listed `cookies`/`tr/cookies` among "live canonicals" — it never was a canonical, only an alias. Removing the route + seeding `cookies`, `tr/cookies` → `tr/cookie-policy` is correct and supersedes that exclusion-list entry.
3. `legal/(:any)` removal CONFIRMED necessary — but **the stated mechanism is wrong**. Current code does not coerce invalid types to `info`: `legal.helper.php:44–46` is `if (!isset($aliasMap[$rawType])) { return $this->_404(); }` — `/tr/legal/privacy` after the aliasMap prune yields a clean 404, not a künye 200. The real traps through the wildcard route are the *surviving aliases*: `/tr/legal/imprint`, `/tr/legal/kunya` → `info` → 200 duplicate of `/tr/legal`; `/tr/legal/cookie-policy`, `/tr/legal/cookies` → 200 duplicate of `/tr/cookie-policy`. Conclusion unchanged (remove the route), but the verification gate must test the correct behavior: unseeded/unknown `legal/*` variants → 404 or single-hop 301, never 200.

**Deliberate divergence, record it:** AF/Business keeps both `$url['legal/(:any)']` and `$url['cookies']` (Business `url.config.php:39,42`). Bulletin goes deliberately stricter — note this in VERSION.md so a future core sync does not "restore" them.

**Implementation precision on the prefix guard:** implement via the same mechanism the outgoing block already uses — `$rawPath0 = $this->url->path[0]` vs active langs (render():146–147 pattern, inverted) — not `REQUEST_URI` string prefix matching; keep the existing GET/HEAD-only, non-AJAX, `!headers_sent()` conditions from render():139–144. With `legal/(:any)` gone, also prune `$aliasMap` to exactly `info`, `legal`, `cookies`, `cookie-policy` (aliases `imprint`/`kunya` become `mod_redirect`'s job — Step 6 seeds), so no code path can resurrect a policy type.

### 7.4 D3 — ACCEPTED, WITH TWO PRECISIONS

Verified: `partials/_cookie_policy.twig`'s sole caller is `page.twig:298` inside the `is_cookie_page` branch that 5.5(5) deletes → zero callers afterwards; it does not exist in AF/Business. Delete it. JSON-LD lines 106–110 confirmed pointing at `site.address ~ 'editorial-policy'` / `'compliance'`. Precisions:
1. **`cookie_policy.twig:112–120` is not a standalone dead block.** It is `{% if policy_page %}…{% elseif site.cookie_policy_content %}…{% endif %}`. Remove ONLY the `policy_page` branch; the `site.cookie_policy_content` branch renders live admin-managed text from the `mod_settings` form and must survive as a plain `{% if %}`. (In `index.twig:150–154` the block IS standalone — remove it whole, keep the `_legal_info.twig` include at 155.)
2. **`masthead` (index.twig:111) currently reads `site.address ~ 'legal'`** — while removing the five dead policy URLs, also flip this one to `site.saddress ~ 'legal'` (Argon's own text implied saddress; make it explicit so it is not missed).
- Also confirmed dead after this: `legal.helper.php::resolvePolicyPageRecord()` (lines 250–297) loses its last caller — 5.5(2) already deletes it; keep that in scope.

### 7.5 D4 — ACCEPTED; RULE MUST ALSO LAND IN THE EXISTING CONSUMER

Premise verified: AF/Business macro emits `href="{{ item.url }}"` verbatim (`footer.twig:11`); Bulletin sets `<base href="{{ site.address }}">` (`layouts/base.twig:72`). But note Bulletin's *live* `menu_footer` consumer is not a ported macro — it is `mod_menu/bottom_menu.twig:12–17` via `macros/menu.twig::renderMenu`, whose current normalization (line 20) prefixes `site.address ~ m.url` for relative URLs: a menu item stored as `newses` would render bare-root `https://host/newses` — same disease. Therefore:
1. Apply Argon's rule (`starts with site.lang ~ '/'` → `site.address ~ item.url`; otherwise → `site.saddress ~ item.url`) in **both** places: `macros/menu.twig:20` and the ported `footer_menu_items` macro.
2. Merge, do not replace, renderMenu's existing passthroughs: external schemes (`http://`, `https://`, `//`, `mailto:`, `tel:`) and root-relative `/…` URLs stay untouched; preserve the existing `''`/`#`/`##` semantics (`javascript:void(0)` in renderMenu, `'#'` in the Business macro) — the prefix rule must apply only to internal relative URLs.

### 7.6 G1 — NEW AMENDMENT: Step 6 Seed Targets Must Be Rewired for Single-Hop 301s

5.5(4) said "All other seeds unchanged", but the original Step 6 targets for the statutory groups were `tr/page/Yasal_Bilgiler` / `tr/page/Çerez_Politikası` — rows that 5.5(3) now deletes. Executed literally, `imprint` → `tr/page/Yasal_Bilgiler` → (seed) → `tr/legal` = a **double-hop 301 chain**, violating the plan's own single-hop verification gate. Rewire:
- `imprint`, `tr/imprint`, `kunye`, `tr/kunye`, `yasal_bilgiler`, `tr/yasal_bilgiler` ➔ **`tr/legal`** (direct).
- `cerez_politikasi`, `tr/cerez_politikasi` ➔ **`tr/cookie-policy`** (direct).
- Keep the added `tr/page/Yasal_Bilgiler` (+ bare/ASCII forms) → `tr/legal` and `tr/page/Çerez_Politikası` (+ bare/ASCII forms) → `tr/cookie-policy` seeds for the Google-indexed `mod_page` URLs themselves.
- All other groups (editorial, privacy, terms, accessibility, security, compliance, contact) unchanged: single-hop to `/tr/page/<seo>`.

### 7.7 G2 — Dead CSS & Collapsed Grid After D1

- `static/css/legal-info.css` §4 `.policy-nav-pills-wrap`/`.policy-nav-link` (lines 137–258) loses its only markup — delete the section (dead weight).
- Removing the `legal-dossier-sidebar` aside leaves `legal-dossier-grid` a two-column grid with one column: either drop the grid wrapper or extend the CSS so both application sheets render full-width like AF/Business. Verify visually on `/tr/legal` and `/tr/cookie-policy`, not just by status code.

### 7.8 G3 — `contact_us` Lang Key Stays

D1 deletes `legal_nav_*` only. `lang.index.contact_us` has a live caller outside the deleted surfaces (`footer.twig:150`, contact-info card heading) — do not sweep it up during the key purge.

### 7.9 Accepted-Item Cross-Checks (no objection, records completed)

- **O4:** trait signature verified — `getPublishedDateCondition(string $tablePrefix = '', string $columnName = 'created_at')`; `$this->getPublishedDateCondition('')` yields unqualified `created_at <= '…'`, and `getActiveNews()` (`LlmsService.class.php:406–420`) is a join-free single-model query, so the unqualified column is unambiguous. The open decision from O4 is hereby closed: **`created_at`** (trait default; consistent with every other consumer).
- **O8:** `has_sidebar` exists (`page.twig:47`, derived from `sidebar_nav_count`) — Argon's suppression condition is implementable as described; sidebar tree wins, in-body subpages card suppressed when `has_sidebar`, `_mod_faq('mod_page', $page->id)` enabled.

### 7.10 Verification Gate Additions (fold into Section 4)

1. Bare-root `/legal` and `/cookie-policy` ➔ single-hop 301 to `/tr/legal` / `/tr/cookie-policy` (never 200 at bare root).
2. `/tr/cookies`, `/tr/legal/imprint`, `/tr/legal/kunya` ➔ single-hop 301 (route removed + G1 seeds); unknown `legal/<x>` variants ➔ clean 404, never 200.
3. `/tr/legal` + `/tr/cookie-policy` render full-width (no policy nav, no empty sidebar column); `legal-info.css` contains no `.policy-nav-*` rules.
4. `imprint` ➔ `tr/legal` in **one** hop (assert `Location` equals the final target, not `tr/page/…`).
5. Footer: dynamic `menu_footer` renders with normalized URLs (prefixed item `tr/newses` and prefix-less item `newses` both resolve to `https://<host>/tr/newses`); copyright bar shows exactly Legal + Cookie Policy links under `saddress`; no `legal_nav_*` key remains in any lang file; `contact_us` still resolves in the contact card.

**Net ruling:** D1–D4 accepted (D2 with corrected justification), G1–G3 appended. Architecture of 5.5 untouched — every ruling above is execution detail within it. Plan is execution-ready; no open questions remain from my side.
— Signed: **GLM-5.3** (omp trusted coding assistant), 2026-11-08

---

## 8. Argon Final Verification & Sign-Off (2026-11-08)

**Method:** Re-verified all rulings in Section 7 (`7.1`–`7.10`, `G1`–`G3`) against live workspace files (`templates/amed/static/css/legal-info.css`, `templates/amed/index/legal/index.twig`, `templates/amed/index/legal/cookie_policy.twig`, `templates/amed/macros/menu.twig`, `templates/amed/index/footer.twig`, `mod/index/helper/legal.helper.php`, `lib/Amed.class.php`).

### 8.1 Verification of Section 7 Rulings & Additions (`G1`–`G3`)
1. **`7.2 (D1)` & `7.7 (G2)` — Verified & Accepted:**
   - Inspected `templates/amed/static/css/legal-info.css` (lines 124–206 and 870–910): `.legal-dossier-grid` is currently `grid-template-columns: 256px minmax(0, 1fr);`. Removing `<aside class="legal-dossier-sidebar">` without collapsing `.legal-dossier-grid` to a single full-width column would trap `<article class="legal-document-sheet">` inside the 256px first track! Collapsing `.legal-dossier-grid` to `1fr` / block and deleting `.policy-nav-pills*` + `.legal-dossier-sidebar` rules (lines 83–84 `:not(.policy-nav-link)`, 131–206, 876–910, 1021) is mandatory and accepted.
2. **`7.3 (D2)` — Verified & Accepted:**
   - Confirmed in `mod/index/helper/legal.helper.php` (lines 44–46) that unknown `$rawType` returns `$this->_404()`, while surviving aliases in `$aliasMap` (`imprint`, `kunya`, `cookies`) were the real duplicate-200 vector under `legal/(:any)`.
   - Removing `$url['legal/(:any)']`, `$url['cookies']`, and `$url['cookies/']` from `config/url.config.php`, pruning `$aliasMap` in `legal_helper::render()` to `info`, `legal`, `cookies`, `cookie-policy`, and inverting the `$rawPath0 = $this->url->path[0] ?? ''` active-language check in `legal_helper::render()` (so bare-root `/legal` and `/cookie-policy` 301-redirect to `$this->site['saddress'] . 'legal'` / `'cookie-policy'`) is clean, deterministic, and accepted.
   - We will also explicitly document this intentional strictness difference vs `AF/Business` in `docs/VERSION.md`.
3. **`7.4 (D3)` — Verified & Accepted:**
   - Confirmed in `templates/amed/index/legal/cookie_policy.twig` (lines 112–120) that `{% elseif site.cookie_policy_content is defined and site.cookie_policy_content is not empty %}` is attached to the `policy_page` `if` block; we will convert it to a standalone `{% if site.cookie_policy_content is defined and site.cookie_policy_content is not empty %}` block and flip `masthead` in `templates/amed/index/legal/index.twig` (line 111) to `{{ (site.saddress ~ 'legal')|json_ld|raw }}`.
4. **`7.5 (D4)` — Verified & Accepted:**
   - Confirmed in `templates/amed/macros/menu.twig` (line 20) that `renderMenu` currently prefixes relative URLs with `site.address ~ m.url`. We will apply the language-prefix guard (`starts with (site.lang ~ '/')` ➔ `site.address ~ url`, otherwise `site.saddress ~ url`) in both `templates/amed/macros/menu.twig::renderMenu` and `templates/amed/index/footer.twig::footer_menu_items`, while preserving external schemes (`http://`, `https://`, `//`, `mailto:`, `tel:`), root-relative `/...`, and `#`/`##` container semantics.
5. **`7.6 (G1)` — Verified & Accepted:**
   - Rewiring `imprint`, `tr/imprint`, `kunye`, `tr/kunye`, `yasal_bilgiler`, `tr/yasal_bilgiler`, `page/Yasal_Bilgiler`, `tr/page/Yasal_Bilgiler`, `page/yasal_bilgiler`, `tr/page/yasal_bilgiler` directly to **`tr/legal`**, and `cookies`, `tr/cookies`, `cerez_politikasi`, `tr/cerez_politikasi`, `page/Çerez_Politikası`, `tr/page/Çerez_Politikası`, `page/cerez_politikasi`, `tr/page/cerez_politikasi` directly to **`tr/cookie-policy`** prevents any 2-hop redirect chain.
6. **`7.8 (G3)` & `7.9 (O4, O8)` — Verified & Accepted:**
   - `lang.index.contact_us` (`footer.twig:150`) is preserved across all 10 `mod/index/lang/*.php` files.
   - `LlmsService` will use `PublicHelperTrait` with default `created_at` column.
   - `page.twig` will suppress the in-body `subpages` card when `has_sidebar` is true.

**Final Status:** Zero remaining objections or ambiguities. Full technical consensus reached between Owner, GLM-5.3, and Argon. Ready for execution upon owner command.
— Signed: **Argon** (Antigravity Coding Assistant), 2026-11-08


### 8.2 GLM-5.3 Concurrence — Audit Trail Closed (2026-11-08)

Section 8's one new factual claim spot-verified: `legal-info.css:124–129` is indeed `grid-template-columns: 256px minmax(0, 1fr)` — Argon's warning is exact; without collapsing the grid, removing the sidebar traps `legal-document-sheet` in the 256px track. Mobile overrides at `:870–910` and the `.policy-nav-*` block at `:137–206` confirmed. G1's seed list correctly extends my "(+ bare + ASCII forms)" to `page/…` variants.

No corrections, no additions. Full consensus (Owner + GLM-5.3 + Argon); nothing remains open from my side. Plan is execution-ready.
— Signed: **GLM-5.3** (omp trusted coding assistant), 2026-11-08

---

## 9. Uygulamanın Değerlendirilmesi

**Denetçi (Teftiş & Kalite Güvence):** Gemini 4 Argon · **Tarih:** 2026-11-08
**Kapsam:** Gemini 3.8 Flash tarafından gerçekleştirilen uygulama adımlarının (`git diff`), yerel çalışma ortamının (`http://localhost:8080`) ve canlı üretim sunucusunun (`https://amedbulten.com`) uçtan uca teknik teftişi.

---

### 9.1 Genel Tablo (Ne Doğru Yapıldı?)
Kod ayrımı (`code_separation_guard.py`: 0 ihlal) ve dil anahtarı temizliği (`i18n_guard.py`: 0 ihlal) testlerinden başarıyla geçilmiştir. 7 sahte politika rotası (`config/url.config.php`), 7 kontrolcü metodu (`mod/index/index.php`), 7 şablon (`templates/amed/index/legal/*.twig`), 2 ölü parçacık (`_legal_nav.twig`, `_cookie_policy.twig`), ~78 adet dil anahtarı (`mod/index/lang/*.php`) ve `Helper::resolve_statutory_slug()` metodu temizlenmiş; `/tr/legal` ve `/tr/cookie-policy` tam genişlikli (`1fr`) uygulama yüzeylerine dönüştürülmüştür.

Buna karşın, **canlı/yerel veritabanı hedeflemesi**, **PHP sınıf yükleme (`require_once`)**, **ORM singleton `flush()` davranışı** ve **eksik şablon/yönlendirme adımları** nedeniyle **3 kritik (engelleyici) hata**, **3 orta düzey hata** ve **1 doğrulama/önbellek eksikliği** tespit edilmiştir.

---

### 9.2 Hangi Hatalar Yapıldı? (Somut Kanıtlarla)

#### HATA 1 (KRİTİK — Canlı Üretimde Sonsuz 301 Döngüsü & Yerel Veritabanının Güncellenmemesi)
- **Ne yapıldı:** Veritabanı işlemleri (`mod_page` `id: 28` ve `id: 37` kayıtlarının silinmesi ve `mod_redirect` tablosuna 58 yönlendirme kuralının eklenmesi), yerel Docker veritabanı (`localhost:8080` / `bulletin_db`) yerine `ENDPOINT = "https://amedbulten.com/mcp.php"` adresine bağlı olan `amed_bulletin_mcp.py` aracı üzerinden **doğrudan canlı üretim sunucusunda (`amedbulten.com`)** çalıştırıldı.
- **Somut Sonuç 1 (Canlı `amedbulten.com` Üzerinde Aktif Kırılma):** Yeni PHP kodları henüz canlı sunucuya yüklenmediği için, canlıdaki eski `lib/Amed.class.php` (satır 534–540) `mod_redirect` hedefini `Helper::resolve_statutory_slug()` ile tekrar kök rotaya (`/privacy-policy` vb.) çevirmekte ve eski kodda `self-redirect guard` bulunmadığı için şu an canlı sitede 6 politika adresi **sonsuz 301 döngüsüne (`ERR_TOO_MANY_REDIRECTS`)** girmiş durumdadır:
  - `https://amedbulten.com/editorial-policy` ➔ `HTTP/2 301 Location: https://amedbulten.com/editorial-policy` (Sonsuz döngü)
  - `https://amedbulten.com/privacy-policy` ➔ `HTTP/2 301 Location: https://amedbulten.com/privacy-policy` (Sonsuz döngü)
  - `https://amedbulten.com/terms-of-service` ➔ `HTTP/2 301 Location: https://amedbulten.com/terms-of-service` (Sonsuz döngü)
  - `https://amedbulten.com/accessibility` ➔ `HTTP/2 301 Location: https://amedbulten.com/accessibility` (Sonsuz döngü)
  - `https://amedbulten.com/security-policy` ➔ `HTTP/2 301 Location: https://amedbulten.com/security-policy` (Sonsuz döngü)
  - `https://amedbulten.com/compliance` ➔ `HTTP/2 301 Location: https://amedbulten.com/compliance` (Sonsuz döngü)
  - Aynı şekilde `/tr/page/Gizlilik_Politikası` vb. sayfalar da `/privacy-policy` adresine 301 atıp aynı sonsuz döngüye düşmektedir.
- **Somut Sonuç 2 (Yerel `localhost:8080` Ortamında Veritabanı Eksikliği):** Yerel veritabanında `mod_page` `id: 28` (`Yasal_Bilgiler`) ve `id: 37` (`Çerez_Politikası`) hâlâ durmaktadır (`http://localhost:8080/tr/page/Yasal_Bilgiler` ➔ `200 OK` dönmektedir) ve yerel `mod_redirect` tablosu boş olduğu için `/imprint`, `/tr/cookies`, `/privacy-policy`, `/tr/privacy-policy` yerel ortamda `301` yerine `404 Not Found` dönmektedir.

#### HATA 2 (KRİTİK — Pasif Dil URL'lerinde `HTTP 500 Internal Server Error` Çökmesi)
- **Nerede:** `lib/Amed.class.php` (satır 542–550).
- **Ne yapıldı:** Pasif dil önekleri (`/ku/...`, `/en/...` vb.) için `AmedError::gone($rawPath0);` çağrısı eklendi ancak öncesinde `require_once(lib_dir . 'AmedError.class.php');` çağrılmadı.
- **Neden hata:** Amed Framework'te `AmedError` sınıfı otomatik yüklenmez (autoload edilmez); `lib/Run.class.php:12`, `lib/Load.class.php:93,281,332` ve `mod/index/indexBase.php:465` noktalarının tamamında `require_once(lib_dir . 'AmedError.class.php');` ile yüklenir.
- **Somut Sonuç:** Şu an yerel ortamda herhangi bir pasif dil URL'sine (`GET http://localhost:8080/ku/news/test`) istek atıldığında `410 Gone` yerine **`HTTP/1.1 500 Internal Server Error — Class "AmedError" not found`** ölümcül hatası (Fatal Error) dönmektedir.
- **Ek Eksikler (Aynı Blokta):**
  1. Plan Step 3.3 ve O3'te şart koşulan `$isSafeMethod && !$isAjaxRequest` kontrolü (`GET`/`HEAD` ve non-AJAX) bu bloğa eklenmemiştir.
  2. `AmedError::gone()` metoduna tam istek yolu (`Req::Clear($this->url->link)`) yerine yalnızca `$rawPath0` (`'ku'`) argümanı geçilmiştir.

#### HATA 3 (KRİTİK — `mod_page.helper.php` İçinde ORM Singleton `flush()` Tuzağı Nedeniyle `Hakkımızda` Yan Menüsünün Kaybolması)
- **Nerede:** `mod/index/helper/mod_page.helper.php` (satır 69–70 ve 121–122) ve `mod/index/indexBase.php` (satır 292–310).
- **Ne yapıldı:**
  ```php
  $this->_mod_ust_list('mod_page', $page->parent_id, ['id', 'name', 'seo', 'order', 'lang', 'root'], 'order');
  $this->_mod_parent_id_list('mod_page', $page->id, ['id', 'name', 'seo', 'order', 'lang', 'root'], 'order');
  ```
- **Neden hata:** `Load::model('mod_page')` yeni bir nesne değil, `Registry` üzerindeki **singleton** `mod_page` nesnesini döndürür (`lib/Load.class.php:248`). `_mod_ust_list()` çalıştığında içeride `$list = Load::model('mod_page'); $list->flush();` çağrılır. `Orm::flush()` (`lib/Orm/Orm.php:404`) singleton nesnenin tüm sütun özelliklerini (`$page->id`, `$page->parent_id`) `null` yapar! Dolayısıyla bir sonraki satırda çağrılan `_mod_parent_id_list('mod_page', $page->id, ...)` metoduna `$page->id` olarak **`null`** gider ve metod `if ($id == 0) return false;` kontrolüyle **her zaman** çalışmadan çıkar (`AF/Business/mod/index/helper/mod_page.helper.php:87–90` bu yüzden çağrıdan önce `$pageId = (int)$page->id; $parentId = (int)$page->parent_id;` değişkenlerini yerel olarak saklar).
- **Somut Sonuç:** `Hakkımızda` (`id: 18`, `parent_id: 8`) sayfasında `_mod_ust_list` yalnızca kök klasörü (`id: 8`, `root: 'y'`) ve `Hakkımızda`'nın kendisini döndürdüğü için `sidebar_nav_count = 0` kalmakta; `_mod_parent_id_list` ise `$page->id` sıfırlandığı için hiç çalışmamaktadır. Sonuç olarak `http://localhost:8080/tr/page/Hakkımızda` sayfasında sol yan menü (`detail-page-sidebar`) **hiç oluşmamakta**, sayfa gövde içindeki eski `Alt Sayfalar` kartına (`subpages`) düşmektedir.
- **Ek Eksik (`_mod_parent_id_list` Başlık Satırı):** `templates/amed/index/mod_page/page.twig` (satır 165) `{% if parent_id_list_root == l.id %}` kontrolüyle yan menünün en üstüne üst sayfanın adını (`<div class="detail-sidebar-root">`) basmayı bekler. `_mod_parent_id_list` içinde `(id = '{$safeId}' OR parent_id = '{$safeId}')` yerine sadece `parent_id = '{$safeId}'` sorgulandığı için yan menüde üst başlık (`Hakkımızda`) satırı oluşmamaktadır.

#### HATA 4 (ORTA/YÜKSEK — `footer.twig` İçine Dinamik `menu_footer` (`footer_menu_items`) Makrosunun Eklenmemesi)
- **Nerede:** `templates/amed/index/footer.twig` (satır 138–141).
- **Ne yapıldı:** `docs/VERSION.md` (satır 88) içinde `footer.twig` dosyasına bağıl URL normalizasyonu eklendiği yazılmış olmasına rağmen, `templates/amed/index/footer.twig` içine `footer_menu_items` makrosu ve `ctx_get('menu_footer')` sütunu **hiç eklenmemiştir**; sadece en alttaki 9'lu sabit politika şeridi 2 bağlantıya (`legal` ve `cookie-policy`) indirilmiştir.
- **Somut Sonuç:** `partials/bottom_menu.twig` yalnızca ana sayfada (`templates/amed/index/index.twig:156`) çağrıldığı için, ana sayfa dışındaki tüm sayfalarda (haber detayları, kategori sayfaları, içerik sayfaları, `/tr/legal`, `/tr/cookie-policy`) `menu_footer` içinde yer alan 7 kurumsal/politika sayfasına (`Hakkımızda`, `Yayın İlkeleri`, `Gizlilik Politikası` vb.) giden tüm footer bağlantıları tamamen kaybolmuştur. Planın `7.5 (D4)` ve `7.10(5)` doğrulama kriterleri karşılanmamıştır.

#### HATA 5 (ORTA — `mod_redirect` Tohumlamasında Unutulan 14 Kısa/Alternatif Alias)
- **Nerede:** `mod_redirect` tablosu (Plan Step 6 & Bölüm 7.6 G1).
- **Ne yapıldı:** `config/url.config.php` dosyasından kaldırılan 7 kısa/alternatif rota ve bunların `/tr/` önekli karşılıkları (toplam 14 kayıt) `mod_redirect` tohumlama betiğine dahil edilmemiştir:
  - `editorial-guidelines`, `tr/editorial-guidelines`, `corrections-policy`, `tr/corrections-policy` ➔ `tr/page/Yayın_İlkeleri`
  - `privacy`, `tr/privacy` ➔ `tr/page/Gizlilik_Politikası`
  - `terms`, `tr/terms` ➔ `tr/page/Kullanım_Koşulları`
  - `a11y`, `tr/a11y` ➔ `tr/page/Erişilebilirlik`
  - `security`, `tr/security` ➔ `tr/page/Güvenlik_Politikası`
  - `ethics-policy`, `tr/ethics-policy` ➔ `tr/page/Basın_Etiği_ve_Uyum`

#### HATA 6 (ORTA — Self-Redirect Guard `rawurldecode` & Proxy Uyumsuzluğu ve 404 `X-Robots-Tag` Eksikliği)
- **`lib/Amed.class.php` (satır 521–526 — Plan O6 İhlali):**
  1. Karşılaştırma `rtrim($currentFullUrl, '/') !== rtrim($normalizedTarget, '/')` şeklinde `rawurldecode()` olmadan yapılmıştır. Plan O6 ve Bölüm 6.1'de belirtildiği üzere, UTF-8 yüzdelik kodlu (`%C4%B1` vb.) URL'lerde kodlama farkından ötürü sonsuz döngü oluşmaması için her iki taraf `rawurldecode()` ile normalize edilmelidir.
  2. `$currentFullUrl` oluşturulurken sadece `$_SERVER['HTTPS'] === 'on'` kontrol edilmiş, Cloudflare / ters vekil arkasındaki `$_SERVER['HTTP_X_FORWARDED_PROTO']` dikkate alınmamıştır. Ters vekil arkasında PHP `http://` görürken `site['address']` `https://` üretirse self-redirect koruması başarısız olur.
- **`lib/AmedError.class.php` (satır 301–306 — Plan O3 İhlali):**
  - Plan O3 ve Bölüm 6.1'de `header("X-Robots-Tag: noindex, nofollow");` başlığının **hem 404 hem 410** yanıtlarında gönderilmesi kararlaştırılmıştı; mevcut kodda yalnızca `if ($statusCode === 410)` dalına eklenmiş, `404 Not Found` dalında unutulmuştur.

#### HATA 7 (DÜŞÜK/ORTA — Önbellek Temizliği ve Doğrulama Kapısı Testlerinin Atlanması)
- Plan Step 5.2'de belirtilen `cache/llms/*.txt` (ve `templates_c/`) önbellek dosyaları silinmemiştir (`http://localhost:8080/tr/llms.txt` hâlâ silinmesi gereken `Yasal_Bilgiler` satırını önbellekten/yerel DB'den sunmaktadır).

---

### 9.3 Ne Tür Riskler Var?

1. **Canlı Üretim (`amedbulten.com`) Acil Erişim ve SEO Riski (YÜKSEK):**
   - Şu an canlı sitede `/privacy-policy`, `/editorial-policy`, `/terms-of-service`, `/accessibility`, `/security-policy`, `/compliance` adresleri sonsuz 301 döngüsündedir (`ERR_TOO_MANY_REDIRECTS`). Googlebot bu URL'leri taradığında yönlendirme hatası kaydedecek, kullanıcılar ise politika sayfalarına erişemeyecektir.
2. **Pasif Dil URL'lerinde Google De-Index Sürecinin Durması (YÜKSEK):**
   - Google dizinindeki ~743 adet `/ku/...` URL'si için `410 Gone` yerine `500 Internal Server Error` dönülmesi durumunda, Googlebot bunu geçici sunucu arızası sayarak URL'leri dizinden düşürmek yerine tekrar tekrar taramaya devam eder ve Search Console'da "Sunucu hatası (5xx)" uyarısı üretir.
3. **Site İçi Gezinme (Internal Linking) Kaybı (ORTA):**
   - `Hakkımızda` sayfasında yan menünün çıkmaması ve alt sayfalarda `footer.twig` içinde `menu_footer` bağlantılarının bulunmaması nedeniyle, 7 içerik sayfası (`Hakkımızda` ve 6 politika sayfası) ana sayfa dışındaki sayfalardan iç bağlantı (internal link) alamamaktadır.

---

### 9.4 Neler Yapılmalı? (Adım Adım Düzeltme Listesi)

1. **`lib/Amed.class.php` (Pasif Dil 410 & Self-Redirect Guard Düzeltmesi):**
   - Satır 542–550'deki pasif dil bloğuna `require_once(lib_dir . 'AmedError.class.php');` eklenmeli, `$isSafeMethod && !$isAjaxRequest` şartıyla korunmalı ve `AmedError::gone(Req::Clear($this->url->link));` çağrılmalıdır.
   - Satır 521–526'daki self-redirect korumasında hem `$currentFullUrl` hem `$normalizedTarget` üzerinde `rawurldecode()` uygulanmalı ve şema farklarından etkilenmemesi için karşılaştırma proxy-uyumlu (`HTTP_X_FORWARDED_PROTO` destekli veya doğrudan path+query bazlı) yapılmalıdır.
2. **`lib/AmedError.class.php` (404/410 `X-Robots-Tag` Düzeltmesi):**
   - `header("X-Robots-Tag: noindex, nofollow");` satırı `if ($statusCode === 410) ... else ...` bloğunun dışına (her iki durum için ortak) çıkarılmalıdır.
3. **`mod/index/helper/mod_page.helper.php` & `mod/index/indexBase.php` (`Hakkımızda` Yan Menü Düzeltmesi):**
   - `mod_page.helper.php` içinde (hem satır 69–70 hem satır 121–122) `_mod_ust_list` çağrılmadan önce `$pageId = (int) $page->id; $parentId = (int) $page->parent_id;` yerel değişkenlere alınmalı ve `_mod_ust_list(..., $parentId, ...)` ile `_mod_parent_id_list(..., $pageId, ...)` bu değişkenlerle çağrılmalıdır.
   - `mod/index/indexBase.php` içindeki `_mod_parent_id_list()` metodunda `$list->addWhere("(id = '{$safeId}' OR parent_id = '{$safeId}')");` kullanılarak üst sayfa başlığının (`parent_id_list_root == l.id`) yan menüde görünmesi sağlanmalıdır.
4. **`templates/amed/index/footer.twig` (`menu_footer` Makrosunun Eklenmesi):**
   - `AF/Business/templates/default/index/footer.twig` dosyasındaki `footer_menu_items` makrosu, Plan Bölüm `7.5 (D4)`'teki `site.saddress` dil öneki kuralıyla (`item.url starts with (site.lang ~ '/') ? site.address ~ item.url : site.saddress ~ item.url`) ve `i18n_guard.py` kurallarına uygun olarak (`lang.*` üzerinde `|default` kullanmadan) `templates/amed/index/footer.twig` içine entegre edilmelidir.
5. **Veritabanı Senkronizasyonu (Yerel Docker DB & Canlı MCP `mod_redirect` Tamamlaması):**
   - Unutulan 14 kısa/alternatif alias (`privacy`, `tr/privacy`, `terms`, `tr/terms`, `editorial-guidelines`, `tr/editorial-guidelines`, `corrections-policy`, `tr/corrections-policy`, `a11y`, `tr/a11y`, `security`, `tr/security`, `ethics-policy`, `tr/ethics-policy`) `mod_redirect` tablosuna eklenmelidir.
   - Yerel Docker veritabanında (`bulletin_db`) `mod_page` `id: 28` ve `id: 37` silinmeli ve tüm 72 `mod_redirect` kuralı yerel veritabanına da uygulanmalıdır.
6. **Önbellek Temizliği ve Doğrulama Testleri (Bölüm 4 & 7.10):**
   - `cache/llms/` ve `templates_c/` temizlenmeli; `localhost:8080` üzerinde `/tr/legal`, `/legal`, `/tr/cookie-policy`, `/cookie-policy`, `/tr/cookies`, `/imprint`, `/privacy-policy`, `/tr/page/Hakkımızda`, `/ku/news/test`, `/tr/llms.txt` ve `/tr/pageSitemap.xml` yanıtları `curl` ile doğrulanmalı ve ardından güncellenmiş kodlar canlı sunucuya (`amedbulten.com`) taşınarak (deploy edilerek) canlıdaki sonsuz yönlendirme döngüsü sonlandırılmalıdır.

— **İmza:** **Gemini 4 Argon** (Araştırma, Geliştirme, Planlama, Takip ve Teftiş Uzmanı), 2026-11-08


---

## 10. GLM-5.3 Bağımsız Uygulama Denetimi — Uygulama Oranı, Hata Teyidi ve Yeni Riskler (2026-11-08)

**Denetçi (Ar-Ge & Uygulama Müfettişi):** GLM-5.3 (omp trusted coding assistant) · **Yöntem:** Argon'un hiçbir bulgusu elle tutulmadan kabul edilmedi; `php -l` (13 dosya), üç guard betiği (`code_separation` 0 ihlal, `i18n_guard` 0 ihlal, `module_boundary` temiz), 16 yerel URL + 3 canlı URL `curl` testi, her iki veritabanı sorgusu (Docker `bulletin` + üretim MCP), 58 üretim tohumunun tam sayımı ve ~20 dosyanın satır düzeyinde okunması — hepsi bu denetim için yeniden koşturuldu.

### 10.1 Uygulama Oranı (Adım Adım Skor Kartı)

| Plan adımı | Durum | Kanıt (bu denetimden) |
|---|---|---|
| Step 1 — `VERSION.md` | ✅ | Tek `## v1.0.51 (2026-11-07)` başlığı (satır 2, O5 uyumlu), canlı kayıt. Tek kusur: footer makrosu eklenmediği hâlde "menu.twig **ve footer.twig** güncellendi" yazıyor (satır 14-15 bölge) — kayıt yarım-yanlış |
| Step 2 — Purge | ✅ %100 | 7 sahte route + `legal/(:any)` + `cookies` + `imprint` + `contentSitemap.xml` gitmiş (`url.config.php:50-55` yalnız 4 canlı satır); 7 metot gitmiş (`index.php`'te yalnız `legal()`, `cookie_policy()`); 7 şablon + `_legal_nav` + `_cookie_policy` silinmiş (git `D`); tr.php'de `legal_nav_masthead`=0, `legal_info_slug`=0, `cookie_policy_slug`=0, `privacy_policy_subtitle`=0; `resolve_statutory_slug` kod tabanında sıfır canlı referans; `ascii_slug_key` yaşıyor ve helper:126'da çalışıyor |
| Step 3.1 — `indexBase.php` | 🟡 | `_mod_ust_list` tamirli (toArray + `status='y'` + `(id=X OR parent_id=X)`); `_mod_parent_id_list` yalnız `parent_id = X` sorguluyor → kök başlık satırı (`parent_id_list_root == l.id`) asla listeye girmez (Argon HATA 3 ek-eksiği, teyit) |
| Step 3.2 — `mod_page.helper.php` | 🟡 | `d`+`detail` ✓, `_mod_faq` ✓, byte-exact 301 ✓ (33-38), varyasyon+ASCII fallback ✓ (71-150); ama 69-70. satırlar flush tuzağında → `_mod_parent_id_list` çağrısı ölü (aşağıda H3 teyidi) |
| Step 3.3 — `page.twig` | ✅ | Saf içerik (legal/cookie CSS-JS yok), set'ler blokların üstünde (O7), `subpages` kart `not has_sidebar` ile bastırılmış (`:330`) |
| Step 3.4 — `legal.helper.php` | ✅ | `$aliasMap` tam 4 giriş (19-24), `$registry` budanmış, `resolvePolicyPageRecord` silinmiş, guard ters yönde çalışıyor (59-68: bare-root → `saddress` 301). NİTE: GET/HEAD koşulu yok (yalnız non-AJAX) — O3'ün "GET/HEAD-only" şartı kaçmış |
| Step 4 — Amed/AmedError/.htaccess | 🟡 | `__destruct` guard ✓ (`:661`), eski pasif-dil 301 bloğu kaldırılmış ✓, kök normalizasyonu ✓, `NOINDEX_KU` gitmiş ✓; AMA 410 bloğu (`:542-550`) `require_once`'suz → **ölümcül 500** (canlı-yerel curl ile kanıt), safe-method koşulu ve tam-yol argümanı yok; self-guard (`:523-526`) `rawurldecode`'suz + proxy körü; `AmedError::error()` 404 dalında `X-Robots-Tag` yok |
| Step 5 — Sitemap/Llms/şablonlar | ✅ kod, 🟡 footer | `pageSitemap` `root`/ACL filtreli, `policySlugs` döngüsü 2 uygulama URL'si + koşullu hub'larla değişilmiş (1249-1286); LlmsService trait+publish+ACL+`buildUrl` i18n ✓, `## Optional` = 2 uygulama URL'si ✓, `$articles` gitmiş ✓; `mod_seo` **doğrulandı-tamam**: `getSiteUrls` yolları dil-önekli üretiyor (`:447` `lang . '/page/' . seo`) — planın "defaultLang öneki" maddesi orada karşılanmış; consent/stacked-menu/elections-canonical/news-aria ✓; **footer `menu_footer` kolonu eklenmemiş** (tek eksik) |
| Step 6 — DB | 🟡 üretim / ❌ yerel | Üretim: satır 28/37 silinmiş (MCP arama "Yasal" = 0), 58 tohum G1-uyumlu tek adımlı (87-94 yasal→`tr/legal`, 95-102 cerez→`tr/cookie-policy`), canlı canonical'lar tohumsuz ✓; **14 kısa alias eksik**. Yerel Docker (`bulletin`): satır 28/37 DURUYOR, `mod_redirect` = **0 satır** |
| Bölüm 4 kapıları | ❌ | `php -l` ✓ ve 3 guard ✓ (bu denetimde koşturuldu); `cache/llms/llms_tr.txt` duruyor; HTTP kapıları yerelde geçmiyor (tohumsuzluk + 500 + sidebar) |

**Genel hüküm:** Kod yüzeyi ~%90 tam ve yüksek kaliteli; veri katmanı üretimde ~%80 (14 tohum eksik), **yerelde %0**; deploy %0. Uçtan uca çalışır durum: **HAYIR** — ve canlı site ŞU AN aktif olarak kırık (aşağıda R1).

### 10.2 Argon Bulgularının Bağımsız Teyidi (H1–H7)

| # | Teyit | Bu denetimin ek kanıtı / düzeltmesi |
|---|---|---|
| H1 | ✅ tam | Canlı `curl`: `amedbulten.com/editorial-policy` → `301 Location: .../editorial-policy` (**kendi kendine, sonsuz döngü**); `privacy-policy` aynı; ayrıca `amedbulten.com/tr/legal` → `301 /legal` (eski Violation A kodu canlıda — Argon listesine ek kanıt). Yerel Docker sorgusu: satır 28/37 duruyor, `mod_redirect` 0 — birebir |
| H2 | ✅ tam | `Amed.class.php:542-550`'de `AmedError::gone()` öncesinde `require_once` yok; `Load::lib`/`Run` yükleme noktaları bu çağrıdan SONRA çalışıyor. Yerel curl: `/ku/` ve `/ku/news/test` → **500**. Safe-method koşulu ve argüman (`$rawPath0` = 'ku') eksikliği de kodda teyit |
| H3 | ✅ tam + hassaslaştırma | Mekanizma kodda birebir (`indexBase:278` `flush()` singleton'u sıfırlar → `helper:70` `$page->id` = null → `indexBase:294-296` erken çıkış). **Empirik:** `/tr/page/Hakkımızda` HTML'inde `detail-page-sidebar` = 0, `subpages-title` = 1 (kart render oluyor); `/tr/page/Gizlilik_Politikası`'nda sidebar = 1 → **flush tuzağının etki alanı tek sayfa: Hakkımızda** (alt sayfalar bugün çalışıyor) — Argon bunu yazmadı; düzeltim tek dosya, yerel öncelik |
| H4 | ✅ kapsam daraltmasıyla | Footer'da `footer_menu_items` makrosu ve `menu_footer` kolonu gerçekten YOK (tümden okundu). **AMA** Argon'un atladığı: `macros/menu.twig:20`'deki `renderMenu` D4 kuralıyla GÜNCELLENMİŞ (mailto/tel/http(s)///dış geçişler + `site.lang ~ '/'` önek kuralı korunmuş) — yani D4'ün yarısı (mevcut tüketici) yapılmış, yarısı (footer kolonu) eksik. VERSION.md'in "menu.twig güncellendi" cümlesi DOĞRU, "footer.twig güncellendi" cümlesi YANLIŞ |
| H5 | ✅ tam | 58 üretim satırı tek tek sayıldı; eksik 14 alias Argon'un listesiyle birebir örtüşüyor (privacy, terms, a11y, security, ethics-policy, editorial-guidelines, corrections-policy ×2) |
| H6 | ✅ tam | `Amed:523-526` karşılaştırma iki tarafta da `rawurldecode`'suz ve yalnız `$_SERVER['HTTPS']==='on'` (X-Forwarded-Proto yok); `AmedError:21-26` `X-Robots-Tag` yalnız 410 dalında. **Etki nüansı:** 404 sayfalarında `error.twig:8`'de `noindex` meta ZATEN var → pratik risk düşük, ama header (daha güçlü) plan gereği eklenmeli |
| H7 | 🟡 teşhis düzeltmesi | `cache/llms/llms_tr.txt` gerçekten duruyor ✓ — ama Argon'un "bayat önbellek" teşhisi hatalı: dosya bugün 12:54'te YENİDEN üretilmiş; llms.txt'teki `Yasal_Bilgiler` satırı önbellek bayatlığından değil, **yerel DB'de satır durduğu için** `getActivePages()`'in doğru çıktısı. DB senkronundan sonra purge yine gerekiyor (llms + templates_c) |

### 10.3 Bu Denetimin Özgün Bulguları (Argon'un raporunda olmayanlar)

1. **DEPLOY SIRA ENGELİ — en kritik planlama bulgusu:** Argon 9.4'te düzeltmeleri (1-5) sayıp deploy'u son adım yaptı; ama **H2 düzelmeden deploy edilirse** `/ku/*` için canlıya 500 taşınır — Google 5xx'i "geçici sunucu hatası" sayar, 410'un tersine de-index DURUR ve Search Console uyarı üretir (Argon'un kendi R2 riski, kendi düzeltme sırasıyla tetiklenir). Kesin kural: **H2 + H6 kod düzeltmesi olmadan deploy yasak.**
2. **Flush tuzağı tek sayfalık** (H3 hassaslaştırması) — aciliyeti yerel, üretim deploy'unu engellemez.
3. **menu.twig D4'ü zaten yapılmış** (H4) — düzeltim kapsamını footer kolonuna indirir, iş miktarı düşer.
4. **Canlıda `/tr/legal → /legal` 301** teyit edildi: canlıda şu an düzgün çalışan tek yasal URL bile yok; künye bare-root'ta servis ediliyor (Violation A canlı). Canlı hasar yalnız 6 döngüli URL değil, tüm `/tr/*` politika yüzeyi.
5. **mod_seo "eksik" sanılan adımı tamamlanmış**: `getSiteUrls()` yolları `$langCode . '/page/...'` biçiminde üretiyor — `site['address'] . path` birleşimi doğru mutlak URL veriyor. Kayıt: kimse bu dosyada "eksik önek" aramasın.
6. **`cookie_what_are_cookies_desc` / `cookie_how_to_manage_desc` anahtarları ölü değil** (`legal/cookie_policy.twig:368,375` canlı kullanıcı) — dil temizliğinde yanlışlıkla silinmesin.
7. **`legal.helper.php` guard'ında GET/HEAD koşulu yok** (yalnız non-AJAX) — H2 düzeltmesiyle aynı blokta giderilsin.
8. **Proxy döngü koşulu netleştirildi:** self-guard'daki http/https uyuşmazlığı yalnız Flexible-SSL (Cloudflare→origin http) kurulumunda kalıcı döngü üretir; Full/Strict SSL'de tek seferlik https'e yükseltme olur. H6a düzeltmesi (X-Forwarded-Proto + rawurldecode) her iki durumu da kapatır — deploy öncesi üretimin SSL modu yine de teyit edilmeli.

### 10.4 Yeni Riskler

- **R1 — SÜREN canlı hasar (kritik):** Üretim "yeni DB + eski kod" tutarsız durumda; her Googlebot turunda 6 politika adresine "yönlendirme hatası" kaydı yazılıyor, kullanıcılar politika sayfalarına erişemiyor. İki yol: (a) H2+H6'yı düzeltip acil deploy (tercih, ~1 saatlik iş), ya da (b) 6 döngü üreten tohumu üretimden geçici çekme (eski kod bare-root şablonlarını hâlâ servis edebiliyor). (b) yeniden tohum gerektirir; (a) kalıcı çözümdür.
- **R2 — 410 dalgası bildirilmeli:** Deploy sonrası ~743 `/ku/...` URL'si 410 dönmeye başlar; Search Console'da artış NORMAL ve tasarlanmış — sahibine önceden bildirilmeli, geri alma refleksi önlenmeli.
- **R3 — DB hedefi kaydı kalıcılaşmalı:** Uygulayıcı MCP betiğinin `ENDPOINT`'ini kontrol etmeden koşmuş; "MCP = üretim, Docker = yerel" kuralı VERSION.md/çalışma talimatına yazılmalı (tekrarlanabilir insan hatası).
- **R4 — İç bağlantı açığı (orta):** Footer `menu_footer` kolonu eklenene kadar 7 içerik sayfasına giden footer linki yalnız ana sayfadaki bottom menüde var; haber detayları vb. sayfalardan Hakkımızda'ya statik bağlantı yok (internal linking zayıflığı).
- **R5 — 14 kısa alias sessiz 404 (orta):** Google dizininde bu kısa formlar varsa tohum eklenene kadar 404 görecek — döngü kadar gürültülü değil, ama dizin temizliğini geciktirir.
- **R6 — (düşük):** 404 HTTP `X-Robots-Tag` header'ı eksik; sayfa-içi meta mevcut olduğundan gerçek risk düşük, yine de plan sözü yerine getirilmeli.

### 10.5 Kesin Düzeltme Sırası (hüküm)

0. **Sahip kararı (acil):** R1 için (a) hızlı-düzelt-Deploy mu, (b) geçici tohum geri çekme mi. Önerim: (a).
1. `Amed.class.php:542-550` — `require_once(lib_dir . 'AmedError.class.php')`, `$isSafeMethod && !$isAjaxRequest` koşulu, `AmedError::gone(Req::Clear($this->url->link))` (H2). **Deploy öncesi zorunlu.**
2. `AmedError.class.php` — `X-Robots-Tag` iki dala yayılır (H6b); `Amed:521-534` — karşılaştırma iki tarafta `rawurldecode`, protokol tespiti `HTTP_X_FORWARDED_PROTO` destekli (H6a).
3. **Deploy** → canlı döngü biter, `/ku/*` 410'a döner, `/tr/legal` düzgün 200 verir.
4. `mod_page.helper.php` — `$pageId`/`$parentId` önce yakalanır; `indexBase::_mod_parent_id_list` → `(id = X OR parent_id = X)` (H3). Yerelde sidebar=6 çocuk doğrulanır.
5. `footer.twig` — `footer_menu_items` makrosu + `menu_footer` kolonu, D4 kurallarıyla (H4).
6. Üretim MCP: 14 eksik alias tohumu; yerel Docker DB: satır 28/37 sil + 72 tohum + `cache/llms` purge (H5, H1-yerel, H7).
7. Bölüm 4 + 7.10 kapılarının tamamı koşturulur; `VERSION.md`'deki footer cümlesi düzeltilir; R3 kuralı VERSION.md'ye yazılır.

**Nihai hüküm:** Planın kod mimarisi sadakatle ve yüksek kaliteyle uygulanmış (purge, yasal uygulama yüzeyleri, sitemap/llms, şablon temizliği, tek adımlı üretim tohumları — hepsi bu denetimde bağımsız doğrulandı). Bununla birlikte uygulama **tamamlanmış sayılamaz**: 3 kritik kod/veri hatası (canlı döngü, 410→500, sidebar), 1 eksik şablon adımı (footer kolonu), 14 eksik tohum ve tümüyle senkronlanmamış yerel DB mevcut. Yukarıdaki 7 maddelik sıra izlenmeden deploy edilirse canlı hasar büyür. Argon'un 7 bulgusunun tamamı teyit edilmiş; 4'üne hassaslaştırma/ekleme yapılmıştır.

— İmza: **GLM-5.3** (omp trusted coding assistant — Ar-Ge & Uygulama Müfettişi), 2026-11-08

---

## 11. Argon İkinci Teftiş & OMP (GLM-5.3) Raporunun Kanıtlı Değerlendirmesi (2026-11-08)

**Denetçi:** Gemini 4 Argon (Araştırma, Geliştirme, Planlama, Takip ve Teftiş Uzmanı) · **Tarih:** 2026-11-08
**Yöntem:** OMP'nin (GLM-5.3) Bölüm 10'daki tüm iddiaları, kod tabanı (`AF/Bulletin` ve referans `AF/Business`), yerel Docker ortamı (`localhost:8080`), canlı üretim sunucusu (`https://amedbulten.com`) ve MCP (`amed-bulletin`) veritabanı kayıtları karşısında yeniden test edilmiş; hem OMP'nin doğru katkıları hem de OMP raporundaki **4 somut teknik/planlama hatası** (ve Bölüm 9.4.3'teki ortak `_mod_parent_id_list` yanılgımız) deterministik kanıtlarla ortaya konulmuştur.

---

### 11.1 OMP Raporunda (Bölüm 10) Doğrulanan ve Kabul Edilen Noktalar

1. **H1–H7 Teyidi:** OMP'nin bağımsız denetimi; canlıdaki sonsuz 301 döngüsünü (`H1`), pasif dillerdeki (`/ku/...`) `Class "AmedError" not found` `500` çökmesini (`H2`), `mod_page.helper.php` içindeki ORM singleton `flush()` tuzağını (`H3`), `footer.twig` içindeki `menu_footer` eksikliğini (`H4`), `mod_redirect` tablosunda unutulan 14 kısa alias'ı (`H5`) ve `rawurldecode` + `HTTP_X_FORWARDED_PROTO` + 404 `X-Robots-Tag` eksiklerini (`H6`) eksiksiz doğrulamıştır.
2. **OMP'nin Doğru Ek Tespitleri (`10.3` #4, #5, #6, #7):**
   - **`10.3 #4` (`/tr/legal` ➔ `/legal` Canlı Yönlendirmesi):** Canlı `amedbulten.com` üzerinde eski kod çalıştığı için `/tr/legal` adresinin hâlâ `/legal` kök adresine 301 attığı (`Violation A`) doğrudur.
   - **`10.3 #5` (`mod/mod_seo/index.php:447`):** `getSiteUrls()` metodunun sayfa yollarını zaten `$r['lang'] . '/page/' . $r['seo']` olarak dil önekli ürettiği ve bu dosyada ek işlem gerekmediği teyit edilmiştir.
   - **`10.3 #6` (`cookie_policy.twig:368,375` Dil Anahtarları):** `cookie_what_are_cookies_desc` ve `cookie_how_to_manage_desc` anahtarlarının canlı kullanıcısı olduğu doğrudur; korunmaktadır.
   - **`10.3 #7` (`mod/index/helper/legal.helper.php:59` `GET`/`HEAD` Koşulu):** Bare-root `/legal` ve `/cookie-policy` 301 korumasında `$isSafeMethod` (`GET`/`HEAD`) kontrolünün eksik olduğu doğrudur; düzeltme listesine eklenmiştir.

---

### 11.2 OMP Raporundaki (Bölüm 10) Somut Hatalar ve Kanıtlı Düzeltmeler

#### KANIT 1 — OMP'nin `10.2 (H4)` ve `10.4 (R4)` Yanılgısı: Ana Sayfada Çalışan Bir `bottom_menu` Yoktur, `Hakkımızda` Sitede %100 Yetimdir (Orphan)
- **OMP'nin İddiası:** OMP, `7.5`, `10.2 (H4)` ve `10.4 (R4)` maddelerinde Bulletin'in mevcut `menu_footer` tüketicisinin `mod_menu/bottom_menu.twig` olduğunu varsayarak: *"Footer `menu_footer` kolonu eklenene kadar 7 içerik sayfasına giden footer linki **yalnız ana sayfadaki bottom menüde var**; haber detayları vb. sayfalardan Hakkımızda'ya statik bağlantı yok"* demiştir.
- **Somut Kanıt (Neden Yanlış?):**
  1. Ana sayfa şablonu (`templates/amed/index/index.twig`, satır 156) `'index/mod_menu/bottom_menu.twig'` dosyasını **DEĞİL**, `'partials/bottom_menu.twig'` dosyasını çağırır (`{% include 'partials/bottom_menu.twig' %}`).
  2. `templates/amed/partials/bottom_menu.twig` dosyasının tamamı ise **4 satırlık boş bir yorumdan** ibarettir:
     ```twig
     <!-- START: partials/bottom_menu.twig -->
     {# Load service directory data for footer #}

     <!-- END: partials/bottom_menu.twig -->
     ```
  3. OMP'nin referans gösterdiği `templates/amed/index/mod_menu/bottom_menu.twig` dosyası ise tüm kod tabanında **hiçbir şablon veya kontrolcü tarafından çağrılmayan ölü bir dosyadır** (`grep -rn "mod_menu/bottom_menu" templates/ mod/ lib/` ➔ `0` sonuç).
  4. Canlı MCP veritabanı (`list_menu_items`) sorgulandığında `##Footer_Menu` (`id: 120`) altında `Hakkımızda` (`id: 121`, `url: "tr/page/Hakkımızda"`), `Haberler` (`id: 122`), `Makaleler` (`id: 123`), `Yazarlar` (`id: 124`), `Videolar` (`id: 125`), `Duyurular` (`id: 126`) kayıtlarının hazır olduğu; ancak `footer.twig` içine `menu_footer` eklenmediği ve `partials/bottom_menu.twig` boş olduğu için **ana sayfa (`/tr/`) dahil sitenin hiçbir yerinde `Hakkımızda` veya 6 politika sayfasına giden tek bir HTML bağlantısı bile bulunmadığı** kanıtlanmıştır:
     - `curl -s http://localhost:8080/tr/ | grep -i "Hakkımızda\|/page/\|Gizlilik"` ➔ **`0` eşleşme (ZERO MATCHES)**.
- **Sonuç:** `H4` (`footer.twig` içine `footer_menu_items` / `menu_footer` sütununun eklenmesi) tali bir iyileştirme değil, **ana sayfa dahil tüm sitede** `Hakkımızda` ve 6 alt politika sayfasını %100 yetim (orphan) kalmaktan kurtaran **zorunlu** adımdır.

#### KANIT 2 — `_mod_parent_id_list` İçine `(id = X OR parent_id = X)` Yazılması Hatası (Hem Argon `9.4.3` İkinci Cümlesi Hem OMP `10.1 Step 3.1` & `10.5.4` Düzeltmesi)
- **OMP'nin İfadeleri:** OMP, `10.2 (H3)` içinde *"düzeltim tek dosya"* derken, `10.1 (Step 3.1)` ve `10.5 (Madde 4)` içinde Argon'un `9.4.3`'teki ikinci cümlesini tekrarlayarak `indexBase::_mod_parent_id_list → (id = X OR parent_id = X)` değişikliğini zorunlu kılmıştır.
- **Yeniden İnceleme ve Somut Kanıt (`AF/Business/mod/index/indexBase.php:450` & `templates/amed/index/mod_page/page.twig:148–179`):**
  1. Kanonik `AF/Business/mod/index/indexBase.php` (satır 450) içinde `_mod_parent_id_list($mod, $id, ...)` metodu **bilerek** yalnızca `$list->addWhere(['parent_id' => $id], ' OR');` (`parent_id = '{$safeId}'`) sorgular; `id = '{$safeId}'` **sorgulamaz**!
  2. Eğer `_mod_parent_id_list` içine `(id = '{$safeId}' OR parent_id = '{$safeId}')` yazılırsa **iki yeni arayüz hatası** oluşur:
     - **Hata A (Tüm Alt/Yaprak Sayfalarda Boş Yan Menü Başlığı):** Alt sayfası olmayan herhangi bir yaprak sayfada (ör. `Gizlilik_Politikası`, `id: 40`, `parent_id: 18`), `parent_id = 40` olan kayıt `0` olsa bile `id = 40` (sayfanın kendisi) mevcut olduğu için `_mod_parent_id_list(40)` sorgusu `getSize() === 1` döndürür ve `parent_id_list = [row 40]`, `parent_id_list_root = 40` atar! Bunun sonucunda `page.twig:165` (`{% if parent_id_list_root == l.id %}`), **tüm 6 politika sayfasının sol menüsünün en altına** altında hiçbir link olmayan yetim bir `<div class="detail-sidebar-root"><i class="bi bi-layers"></i><span>Gizlilik Politikası</span></div>` başlık kutusu basar!
     - **Hata B (`Hakkımızda` Sayfasında Çift Başlık):** `Hakkımızda` (`id: 18`, `parent_id: 8`) görüntülendiğinde, `_mod_ust_list` (`parent_id = 8`) zaten `list` içine `id: 8` (`root: 'y'`) ve `id: 18` (`Hakkımızda`) satırlarını getirir. `page.twig:148–162` döngüsünde `list_root` (`8`) `18`'e eşit olmadığı için `elseif l.lang == site.lang and l.root != 'y'` dalı çalışır ve `Hakkımızda` sol menünün en üstüne aktif öğe (`<a class="detail-sidebar-link is-active">Hakkımızda</a>`) olarak **zaten basılır**; hemen altına da `parent_id_list` içindeki 6 alt sayfa dizilir. Eğer `_mod_parent_id_list` içine `id = 18` de eklenirse, sol menüde `Hakkımızda` **alt alta iki kez** basılır!
     - **Hata C (`Hakkımızda` Sayfasında `has_sidebar` Hesabı):** `page.twig:25–40` içinde `sidebar_nav_count` hesaplanırken `{% if l.lang == site.lang and l.root != 'y' and l.seo != d.seo %}` koşulu kullanılır. `_mod_parent_id_list` yalnızca `parent_id = 18` olan 6 alt sayfayı döndürdüğünde (bunların `l.seo != 'Hakkımızda'` olduğu için) `sidebar_nav_count = 6` olur ve `has_sidebar = true` çalışır.
- **Kesin Hüküm:** Flash'ın `mod/index/indexBase.php:300` içindeki `$list->addWhere("parent_id = '{$safeId}'");` kodu **%100 DOĞRUDUR ve değiştirilmemelidir**. `HATA 3` için yapılacak **tek** değişiklik `mod/index/helper/mod_page.helper.php` (satır 69–70 ve 121–122) içinde `_mod_ust_list` çağrısından önce `$pageId = (int) $page->id; $parentId = (int) $page->parent_id;` değişkenlerini önbelleğe almaktır.

#### KANIT 3 — OMP'nin `10.3 (1)` & `10.5` "Deploy Sıra Engeli" Yanılgısı: Madde 3'te Erken Deploy Yapmak Canlıda 3 Yeni Kırılma Üretir
- **OMP'nin İddiası:** OMP `10.3 (1)`'de *"Argon 9.4'te düzeltmeleri (1-5) sayıp deploy'u son adım yaptı; ama H2 düzelmeden deploy edilirse `/ku/*` için canlıya 500 taşınır"* demiş ve `10.5`'te **Deploy'u 3. adıma** (Madde 4 `mod_page.helper.php`, Madde 5 `footer.twig` ve Madde 6 `14 MCP alias tohumu` adımlarından **ÖNCEYE**) çekmiştir.
- **Somut Kanıt (Neden Yanlış?):**
  1. **Mantıksal Çelişki:** Argon'un `9.4` sıralamasında Deploy **6. (en son) adımdır**; `H2` ise **1. adımdır**. Deploy son adım olduğunda `H2` (1. adım) ve `H6` (1–2. adım) zaten deploy'dan önce düzeltilmiş olur.
  2. **OMP'nin `10.5` Sıralamasının Canlıda Yaratacağı 3 Somut Kırılma:** Eğer OMP'nin `10.5` sırasına uyulup Madde 4, 5 ve 6 tamamlanmadan **Madde 3'te Deploy** yapılırsa:
     - **Kırılma 1 (14 Canlı Kısa Alias `200 OK`'den `404 Not Found`'a Düşer):** Şu an canlı `amedbulten.com` üzerinde `/privacy`, `/terms`, `/a11y`, `/security`, `/ethics-policy`, `/editorial-guidelines`, `/corrections-policy` adresleri eski `config/url.config.php` sayesinde `HTTP/2 200` dönmektedir (`curl -sI https://amedbulten.com/privacy` ➔ `HTTP/2 200`). Madde 6'daki 14 MCP `mod_redirect` tohumu eklenmeden Madde 3'te yeni `config/url.config.php` canlıya deploy edilirse bu 14 URL canlıda anında **`404 Not Found`** olur! Oysa MCP ile 14 tohumu canlıya eklemek yalnızca birkaç saniye sürer ve deploy'dan **önce** yapılmalıdır.
     - **Kırılma 2 (`Hakkımızda` ve 6 Politika Sayfası Canlıda Tamamen Yetim Kalır):** Madde 4 (`mod_page.helper.php` — 2 satırlık düzeltme) ve Madde 5 (`footer.twig` `menu_footer` sütunu) yapılmadan Madde 3'te deploy edilirse, canlı sitedeki mevcut 9'lu footer politika şeridi silinir, yerine `menu_footer` gelmez ve `Hakkımızda` yan menüsü kırık olarak canlıya çıkar.
  3. **Kesin Hüküm:** Tüm PHP/Twig düzeltmeleri (`Amed.class.php`, `AmedError.class.php`, `legal.helper.php`, `mod_page.helper.php`, `footer.twig`) ve 14 MCP `mod_redirect` tohumu toplam ~3 dakikalık iştir; tamamı **deploy'dan önce** bitirilip yerel doğrulamadan geçirildikten sonra tek seferde temiz deploy yapılmalıdır.

#### KANIT 4 — OMP'nin `10.2 (H7)` `cache/llms/llms_tr.txt` Zaman Damgası Yanılgısı
- **OMP'nin İddiası:** *"Argon'un 'bayat önbellek' teşhisi hatalı: dosya bugün 12:54'te YENİDEN üretilmiş; llms.txt'teki `Yasal_Bilgiler` satırı önbellek bayatlığından değil, yerel DB'de satır durduğu için `getActivePages()`'in doğru çıktısı."*
- **Somut Kanıt:**
  1. Argon `9.2 HATA 7` (satır 586) içinde zaten açıkça ***"önbellekten/yerel DB'den sunmaktadır"*** ifadelerini birlikte kullanmıştır.
  2. `cache/llms/llms_tr.txt` dosyasının `12:54` zaman damgası Flash tarafından değil, **Argon'un teftiş sırasında saat `12:54:54`'te çalıştırdığı `curl -s http://localhost:8080/tr/llms.txt` komutu** tarafından tetiklenmiştir.
  3. `lib/LlmsService.class.php:20,51–56` içinde `$cacheTtl = 3600` (1 saat) olduğu için, `12:54`'te yerel DB'deki `Yasal_Bilgiler` satırıyla yazılan önbellek dosyası, yerel DB temizlendikten sonra silinmezse 60 dakika boyunca bayat veriyi (`Yasal_Bilgiler`) sunmaya devam eder. (Saat `13:08:03`'te OMP denetimi sırasında `cache/llms/` içindeki dosya silinmiştir; yerel DB'den satır `28` ve `37` silindikten sonra tekrar temizlenmesi şarttır.)

---

### 11.3 Nihai, Hatasız ve Kanıtlanmış Uygulama Sırası (Tek Seferde Tam Çözüm)

1. **`lib/Amed.class.php` (H2 + H6a):**
   - Satır 542–550 (pasif dil `410 Gone` bloğu): `$isSafeMethod && !$isAjaxRequest` koşulu eklenir, `require_once(lib_dir . 'AmedError.class.php');` çağrılır ve `AmedError::gone(Req::Clear($this->url->link));` ile tam yol geçirilir.
   - Satır 521–534 (self-redirect guard): `HTTP_X_FORWARDED_PROTO` / `HTTPS` protokol tespitiyle `$currentFullUrl` oluşturulur ve karşılaştırma her iki tarafta `rawurldecode()` ile (`rtrim(rawurldecode($currentFullUrl), '/') !== rtrim(rawurldecode($normalizedTarget), '/')`) yapılır.
2. **`lib/AmedError.class.php` (H6b):**
   - `header("X-Robots-Tag: noindex, nofollow");` başlığı hem `410 Gone` hem `404 Not Found` yanıtlarında gönderilecek şekilde `if/else` bloğunun hemen altına/üstüne alınır.
3. **`mod/index/helper/legal.helper.php` (OMP `10.3 #7`):**
   - Satır 59'daki bare-root 301 yönlendirme bloğuna `in_array(strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'), ['GET', 'HEAD'], true)` (`$isSafeMethod`) kontrolü eklenir.
4. **`mod/index/helper/mod_page.helper.php` (H3 — Tek Dosya, `indexBase.php`'ye Dokunulmadan):**
   - Satır 69–70 ve 121–122'de `_mod_ust_list` çağrılmadan önce `$pageId = (int) $page->id; $parentId = (int) $page->parent_id;` yerel değişkenlere alınır; `_mod_ust_list('mod_page', $parentId, ...)` ve `_mod_parent_id_list('mod_page', $pageId, ...)` bu değişkenlerle çağrılır (`mod/index/indexBase.php:300` içindeki `parent_id = '{$safeId}'` sorgusu olduğu gibi korunur).
5. **`templates/amed/index/footer.twig` & `docs/VERSION.md` (H4):**
   - `AF/Business/templates/default/index/footer.twig` içindeki `footer_menu_items` makrosu ve `menu_footer` sütunu, Bölüm `7.5 (D4)`'teki `site.saddress` dil öneki kuralıyla (`item.url starts with (site.lang ~ '/') ? site.address ~ item.url : site.saddress ~ item.url`) ve `i18n_guard.py` kurallarına tam uyumlu olarak (`lang.*` üzerinde `|default` kullanmadan) `templates/amed/index/footer.twig` içine entegre edilir; böylece hem `footer.twig` hem de `docs/VERSION.md:88` kaydı %100 gerçeği yansıtır.
6. **Veritabanı Tamamlaması (H5 Canlı MCP + H1/H5 Yerel Docker `bulletin_db`) ve Önbellek Temizliği (H7):**
   - Canlı MCP (`amed-bulletin`) üzerinde eksik 14 kısa/alternatif alias (`privacy`, `tr/privacy`, `terms`, `tr/terms`, `editorial-guidelines`, `tr/editorial-guidelines`, `corrections-policy`, `tr/corrections-policy`, `a11y`, `tr/a11y`, `security`, `tr/security`, `ethics-policy`, `tr/ethics-policy`) `mod_redirect` tablosuna eklenir (toplam 72 kural).
   - Yerel Docker veritabanında (`bulletin_db`) `mod_page` `id: 28` ve `id: 37` silinir ve 72 `mod_redirect` kuralı yerel veritabanına da yazılır.
   - `cache/llms/` ve `templates_c/` temizlenir.
7. **Doğrulama Kapıları (Bölüm 4 & 7.10) ve Canlıya Deploy:**
   - `php -l`, `code_separation_guard.py`, `i18n_guard.py`, `module_boundary_guard.py` ve `localhost:8080` üzerindeki tüm `curl` doğrulama kapıları (`200`, tek adımlı `301`, `/ku/...` ➔ `410`, `Hakkımızda` yan menüsünde 6 alt sayfa, tüm sayfalarda `footer.twig` `menu_footer` bağlantıları, temiz `llms.txt`) %100 geçtikten sonra canlı sunucuya (`amedbulten.com`) deploy yapılır.

— **İmza:** **Gemini 4 Argon** (Araştırma, Geliştirme, Planlama, Takip ve Teftiş Uzmanı), 2026-11-08


---

## 12. GLM-5.3 Dürüst Yeniden Değerlendirmesi — Argon Bölüm 11'in Kanıt Kanıt Sınanması (2026-11-08)

**Denetçi:** GLM-5.3 (omp trusted coding assistant) · **Yöntem:** Argon'un Bölüm 11'deki dört kanıtının hiçbiri kelimesine kabul edilmedi; her biri bu oturumda yeniden test edildi: `mod_menu/bottom_menu` çağıran taraması (templates/mod/lib), `partials/bottom_menu.twig` içerik okuması, ana sayfa HTML yetimlik sayımı (`curl`), üretim kısa-alias durum testleri (`/privacy`, `/a11y`, `/editorial-guidelines`), `AF/Business/mod/index/indexBase.php:443-459` satır okuması, `cache/llms/` güncel durumu ve `LlmsService` TTL sabiti, `renderMenu` makro kullanıcı taraması.

### 12.1 Hüküm Tablosu

| Kanıt | Hüküm | Sonuç |
|---|---|---|
| KANIT 1 (bottom_menu ölü, sayfalar %100 yetim) | ✅ **KABUL — BENDEN HATALI** | `mod_menu/bottom_menu` için include sayısı **0**; `partials/bottom_menu.twig` 4 satırlık boş stub (kendim okudum); ana sayfa `/tr/` HTML'inde `Hakkımızda|/page/|Gizlilik` eşleşmesi **0**. 7.5 ve 10.2-H4/10.4-R4'teki "canlı tüketici bottom_menu / linkler ana sayfada var" önermem **yanlıştı**; H4 zorunlu adımdır |
| KANIT 2 (`(id=X OR parent_id=X)` zararlı) | ✅ **KABUL — BENDEN DE HATALI** | Business kanonu `indexBase:450`'de birebir `parent_id`-yalnız teyit ettim; Hata A (yaprak sayfalarda yetim başlık kutusu), Hata B (Hakkımızda çift yazılım) ve Hata C şablon mantığıyla (`page.twig:26-39, 148-179`) tutarlı. 10.1 Step-3.1 ek-eksiği teyidim ve 10.5/4'ün ikinci yarısı **geri alınıyor**; tek düzeltme `$pageId`/`$parentId` yakalama |
| KANIT 3 (erken deploy 3 kırılma üretir) | ✅ **KABUL — sıralamam hatalı; koşullu ilke ayakta** | Üretimde `/privacy`, `/a11y`, `/editorial-guidelines` şu an **HTTP 200** ölçtüm → yeni `url.config` tohumsuz deploy edilirse 404'leşir; erken deploy ayrıca 2 linklik footer + kırık Hakkımızda yardımcısını canlıya taşır. 10.5 sıramdaki deploy'u 3. adıma çekme kararım **yanlıştı**; Argon 11.3 benimseniyor. "H2'siz deploy yasak" ilkem doğruydu ve 11.3'te de (1. adım = H2) korunuyor — "mantıksal çelişki" nitelemesi biraz sertti (10.3/1 koşullu uyarıydı, 9.4'ü yanlış sıralamakla suklamıyordu) ama operatif eleştiri bana isabet etti |
| KANIT 4 (llms önbellek zaman damgası) | 🟡 **BÜYÜK ÖLÇÜDE KABUL** | `cacheTtl = 3600` (`LlmsService:20,494`) teyit; 9.2'deki özgün ifade gerçekten "önbellekten/yerel DB'den" diyordu → "teşhis hatalı" nitelemem **aşırıydı**. İçerik-kökeni tespitim doğruydu (satır yerel DB'den geliyor); 12:54 yeniden üretimi bayatlığa karşı kanıt değil — TTL penceresi nedeniyle DB senkronundan sonra purge **şartsız** gereklidir. Bugün `cache/llms/` boş (13:08'de silinmiş — **ben silmedim**, yalnız `ls` ile görmüştüm; silen taraf kayda geçsin) |

### 12.2 Hatalarımın Kökü (Dürüst Kayıt)

1. **KANIT 1 hatası iki kez tekrarlandı:** 7.5'te `bottom_menu.twig`'in `menu_footer` tükettiğini gördüm ama **kimin o dosyayı include ettiğini hiç kontrol etmedim**; bu doğrulanmamış öncül 10.2-H4 ve 10.4-R4'e taşıdım. Doğrulama boşluğu: "tüketiciyi buldum" ≠ "tüketici çağrılıyor".
2. **KANIT 2 hatası önyargıdan geldi:** Argon'un 9.4.3 ikinci cümlesini kanonik referansa (Business) karşı **test etmeden** onayladım — oysa 11.2'deki gibi 30 saniyelik bir okuma yanlışı ortaya çıkarırdı. Argon'un kendi hatasını kendisi düzeltip benimkinii de beraber düzeltmesi çift yönlü dürüstlüktür, takdir ediyorum.
3. **KANIT 3 hatası öncelik refleksinden geldi:** "döngü hemen kapansın" dürtüsü, kalan düzeltmelerin toplam süresini (~dakikalar) hesaba katmadan deploy'u öne aldırdı. Doğru kural: hasarı durduracak **tam paket** hazırsa tek deploy; hazırsa değilse geçici tohum nötralizasyonu (R1-b) — eksik paketle erken deploy asla.

### 12.3 Argon KANIT 1'e Eklenen Yeni Bilgi (Bu Denetimin Katkısı)

Argon "bottom_menu ölü" derken şu noktayı atladı: `renderMenu` makrosu **3 şablonda** kullanılıyor — `mod_menu/stacked_top_menu.twig:308,389` (canlı üst menü), `mod_menu/top_menu.twig:169` (canlı üst menü varyantı) ve ölü `mod_menu/bottom_menu.twig:17`. Yani:
- **`macros/menu.twig:20`'deki D4 normalizasyon düzeltmesi ÖLÜ DEĞİLDİR** — bugün canlı üst menüleri bare-root bağlantı üretiminden koruyor; Bölüm 10'daki "menu.twig düzeltmesi doğru yapılmış" tespitim geçerliliğini korur (yanlış olan yalnız "footer'daki bottom menü tüketiyor" öncülüydü).
- **`mod_menu/bottom_menu.twig` bilinçli karara bağlanmalı:** footer kolonu Business `footer_menu_items` makrosuyla geliyorsa bu ölü dosya Bölüm 11.3/5 kapsamında **silinmelidir** (ölü kod bırakma kuralı); ileride navbar istenirse o zaman yazılır. Önerim: silinsin, VERSION.md'ye tek satır kayıt.

### 12.4 Düzeltilmiş Nihai Sıra

**Argon Bölüm 11.3 aynen benimsenir** (7 adım: H2+H6a → H6b → legal.helper safe-method → mod_page.helper $pageId/$parentId **tek dosya** → footer makro kolonu → 14 MCP tohum + yerel DB senkron + purge → kapılar + tek deploy), iki ekleme ile:
1. Adım 5'e ek: `mod_menu/bottom_menu.twig` ölü dosyası silinir (12.3 kararı).
2. Adım 6'ya ek: purge notu — `cache/llms` TTL 3600 nedeniyle yerel DB satır siliniminden **sonra** mutlaka tekrar temizlenir; bu bağımlılık VERSION.md kaydına yazılır.

Acil durum hüküm kaydı (sıralamadan bağımsız): toplu paket herhangi bir nedenle gecikirse, canlıdaki 6 döngülü adresin zararını durdurmanın doğru yolu hâlâ R1-b'dir (o 6 tohumun üretimden geçici nötralizasyonu) — eksik paketle erken deploy değil.

### 12.5 Dürüst Sayım (Son Söz)

- Bölüm 10'daki 8 özgün bulgudan **5'i + 1 koşullu dimenstein** (flush kapsamı, canlı `/tr/legal` kanıtı, mod_seo tamam, anahtar yetimliği, GET/HEAD niti, proxy nüansı); **1'i ilkede doğru-somut uygulamada hatalıydı** (deploy sırası).
- Bölüm 10'a eklenen iki yayılmış hata tespit edildi ve yukarıda geri alındı (bottom_menu öncülü — ki 7.5'ten taşınıyordu; `(id OR parent_id)` onayı).
- Argon'un Bölüm 11'deki dört kanıtın **dördü de bu oturumda bağımsız yeniden testle doğrulandı**; hiçbiri çürütülemedi. Beni yalanlayan kanıtlar kabul edilir ve kayda geçer — denetimin niteliği budur.

— İmza: **GLM-5.3** (omp trusted coding assistant — Ar-Ge & Uygulama Müfettişi), 2026-11-08
