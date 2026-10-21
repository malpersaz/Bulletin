# Handoff Report — Explorer Survey 3 (R4, R6 & Code Cleanliness)

## Executive Summary
This survey provides a comprehensive architectural and code audit of **AF/Bulletin** focusing on:
1. **Requirement R4**: Corporate Legal & Tax Info (E-E-A-T), Cookie Policy integration, and Sorani Kurdish normalization.
2. **Requirement R6**: Multilingual 9-Language Parity, dictionary standardization, and Kurdish action verb alignment (`add => 'Lêke'`, `nûve` -> `nûjenkirin`).
3. **Cleanliness & Excluded Modules**: Forensic verification of the complete removal of `mod_client`, `mod_service`, `mod_product`, and `mod_reference`, identifying one lingering fixture registration in `installer/mod_faq_mod/index.php`.

---

## 1. Observation

### 1.1 R4: Corporate Legal & Tax Info (E-E-A-T) and Cookie Policy

#### A. Database Schemas vs ORM Model (`mod_settings`)
- **ORM Model** (`/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/mod/mod_settings/model/mod_settings.model.php`, lines 46–61):
  Defines 16 corporate and fiscal fields:
  ```php
  public $legal_company_name;
  public $company_type;
  public $trade_registry_number;
  public $central_registration_number;
  public $chamber_of_commerce;
  public $tax_office;
  public $tax_number;
  public $vat_number;
  public $authorized_person;
  public $registered_email;
  public $legal_contact_phone;
  public $default_warranty_duration;
  public $warranty_type;
  public $return_policy_days;
  public $warranty_description;
  public $country;
  ```
- **Installer Schema** (`/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/installer/mod_settings/mod_settings.sql`):
  **Deficiency**: Missing all 16 corporate/fiscal fields listed above.
- **Module Schema** (`/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/mod/mod_settings/mod_settings.sql`):
  **Deficiency**: Missing all 16 corporate/fiscal fields and contains obsolete statistics columns (`stat_completed_projects`, `stat_ongoing_projects`, `stat_workforce`, `stat_offices`).

#### B. Missing Multilingual Fixtures in `installer/mod_page/index.php`
- `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/installer/mod_page/index.php`:
  Currently only seeds Root Categories and "About Us" pages across 9 languages (lines 43–409).
  **Deficiency**: Completely missing the 9-language **Legal Information** page fixtures and 9-language **Cookie Policy** page fixtures present in `AF/Business/installer/mod_page/index.php` (lines 410–714):
  - **Legal Information Pages** (Slugs):
    1. English (`en`): `Legal_Information`
    2. Kurdish Kurmancî (`ku`): `Agahiyên_Qanûnî`
    3. Kurdish Soranî (`ckb`): `زانیارییە_یاساییەکان`
    4. Arabic (`ar`): `المعلومات_القانونية`
    5. German (`de`): `Rechtliche_Hinweise`
    6. French (`fr`): `Informations_Légales`
    7. Swedish (`sv`): `Juridisk_Information`
    8. Turkish (`tr`): `Yasal_Bilgiler`
    9. Zazaki (`zza`): `Melumatê_Huqûqî`
  - **Cookie Policy Pages** (Slugs & Rich Content):
    1. English (`en`): `Cookie_Policy`
    2. Kurdish Kurmancî (`ku`): `Polîtîkaya_Çerezan`
    3. Kurdish Soranî (`ckb`): `سیاسەتی_کووکییەکان`
    4. Arabic (`ar`): `سياسة_ملفات_تعريف_الارتباط`
    5. German (`de`): `Cookie_Richtlinie`
    6. French (`fr`): `Politique_de_Cookies`
    7. Swedish (`sv`): `Cookiepolicy`
    8. Turkish (`tr`): `Cerez_Politikasi`
    9. Zazaki (`zza`): `Politîkaya_Çerezan`

#### C. Frontend Templates & Partials
1. **Missing Partial**: `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/default/partials/_legal_info.twig` does not exist. (Required to conditionally render corporate/fiscal information from `site.*`).
2. **Cookie Consent Banner** (`/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/default/partials/_cookie_consent.twig`, line 6):
   - Currently hardcoded: `<a href="{{ site.saddress }}cookie-policy" ...>`
   - Must be updated to dynamic slug:
     ```twig
     <a href="{{ site.saddress }}page/{{ lang.index.cookie_policy_slug is defined and lang.index.cookie_policy_slug is not empty ? lang.index.cookie_policy_slug : 'Cookie_Policy' }}" class="btn btn-outline-light btn-sm cookie-policy-btn">{{ lang.index.cookie_consent.policy_link }}</a>
     ```
3. **Footer Partial** (`/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/default/index/footer.twig`, lines 142–157):
   - Copyright section lacks links to `Legal Information` and `Cookie Policy`.
   - Needs footer links with fallback slugs matching `AF/Business/templates/default/index/footer.twig` (lines 214–221).
4. **Page Detail View** (`/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/default/index/mod_page/page.twig`):
   - Lacks the `is_legal_page` condition to include `partials/_legal_info.twig`.

#### D. Index Language Dictionary Keys (`mod/index/lang/*.php`)
- In `AF/Bulletin/mod/index/lang/*.php`, the following legal and cookie translation keys are missing across all 9 languages:
  - `legal_info`, `legal_info_slug`
  - `cookie_policy`, `cookie_policy_slug`
  - `legal_info_pending`
  - `company_registry_title`, `tax_fiscal_title`, `legal_contact_title`, `warranty_policies_title`
  - `legal_company_name`, `company_type`, `trade_registry_number`, `central_registration_number`, `chamber_of_commerce`, `country`, `tax_office`, `tax_number`, `vat_number`, `authorized_person`, `registered_email`, `legal_contact_phone`, `default_warranty_duration`, `warranty_type`, `return_policy_days`, `months`, `days`.

#### E. Sorani Kurdish Display Name Normalization
- `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/installer/lang/index.php` (line 95):
  - Current: `['name' => 'Soranî', 'seo' => 'ckb']`
  - Standard Target: `['name' => 'سۆرانی', 'seo' => 'ckb']`
- `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/installer/app/InstallerLang.php` (line 64):
  - Current: `'ckb' => array('label' => 'کوردی', 'code' => 'CKB')`
  - Standard Target: `'ckb' => array('label' => 'سۆرانی', 'code' => 'CKB')`
- `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/installer/lang/*.php` (`ar.php`, `ckb.php`, `de.php`, `en.php`, `fa.php`, `fr.php`, `ku.php`, `sv.php`, `tr.php`, `zza.php`):
  - Current label: `'label' => 'کوردی'`
  - Standard Target: `'label' => 'سۆرانی'`

---

### 1.2 R6: Multilingual 9-Language Parity & Code Standards

#### A. Missing Module Dictionaries
| Module | Existing Languages | Missing Languages |
|---|---|---|
| `mod/mod_user_pm/lang/` | `de`, `en`, `ku`, `tr`, `zza` (5) | **`ar.php`**, **`ckb.php`**, **`fr.php`**, **`sv.php`** |
| `mod/mod_video_embed/lang/` | `de`, `en`, `ku`, `sv`, `tr`, `zza` (6) | **`ar.php`**, **`ckb.php`**, **`fr.php`** |
| `mod/mod_acl/lang/` | `de`, `en`, `ku`, `tr`, `zza` (5) | **`ar.php`**, **`ckb.php`**, **`fr.php`**, **`sv.php`** |
| `mod/mod_seo/lang/` | All 9 languages present (`ar`, `ckb`, `de`, `en`, `fr`, `ku`, `sv`, `tr`, `zza`) | Complete |

#### B. Legacy Non-English Dictionary Keys
Several active modules still contain legacy Turkish keys in their language files:
- Keys like `'vazgec'`, `'hata'`, `'oke'`, `'ziyaretci'`, `'kaldir'`, `'evet'`, `'hayir'`, `'tamam'`, `'yenile'` exist in:
  - `mod/mod_acl/lang/*.php`
  - `mod/mod_announcements/lang/*.php`
  - `mod/mod_gallery_mod/lang/*.php`
  - `mod/mod_headline/lang/*.php`
  - `mod/mod_news_category/lang/*.php`
  - `mod/mod_page/lang/*.php`
  - `mod/mod_popup/lang/*.php`
  - `mod/mod_publication_category/lang/*.php`
  - `mod/mod_slider/lang/*.php`
  - `mod/mod_video/lang/*.php`
  - `mod/mod_video_embed/lang/*.php`
- **Resolution**: Refactor to standard English keys (`cancel`, `error`, `success`, `guest`, `remove`, `yes`, `no`, `ok`, `refresh`).

#### C. Kurdish Language Standardization
1. **Action Verbs (`add => 'Lêke'`)**:
   - `mod/mod_acl/lang/ku.php` (line 45): `'add' => 'Zêde Bike'` -> `'add' => 'Lêke'`
   - `mod/mod_announcements/lang/ku.php` (line 38): `'add' => 'Zêde Bike'` -> `'add' => 'Lêke'`
   - `mod/mod_content_category/lang/ku.php` (line 53): `'add' => 'Zêde Bike'` -> `'add' => 'Lêke'`
   - `mod/index/lang/ku.php` (line 359): `'add_title' => 'Şîroveyekê Zêde Bike'` -> `'Şîroveyekê Lêke'`
2. **Update Terminology (`nûve` -> `nûjenkirin`)**:
   - `mod/lang/lang/ku.php` (line 50): `'Rewşa zimanê nehat nûvekirin.'` -> `'Rewşa zimanê nehat nûjenkirin.'`
   - `mod/mod_user/lang/ku.php` (lines 425, 592–595): `'nûvekirin'` -> `'nûjenkirin'`
   - `mod/mod_user_pm/lang/ku.php` (line 58): `'refresh_action' => 'Nûveke'` -> `'Nûjen Bike'`

#### D. Legacy Turkish Identifiers / Handlers in PHP Code
Inline JavaScript event attributes calling Turkish functions exist in several legacy controllers:
- `mod/mod_acl/index.php` (lines 315, 325): `mod_acl_table_ajax.akm.processSil(...)`, `processDosya(...)`
- `mod/mod_gallery_mod/index.php` (lines 345, 355): `processSil`, `processDosya`
- `mod/mod_headline/index.php` (lines 536, 546): `processSil`, `processDosya`
- `mod/mod_popup/index.php` (lines 535, 545): `processSil`, `processDosya`
- `mod/mod_video/index.php` (line 1014): `processDosya`
- `mod/mod_video_embed/index.php` (line 734): `processDosya`

---

### 1.3 Cleanliness & Excluded Modules

#### A. Deprecated Module Deletion Verification
- **Confirmed Complete Removal**:
  - `installer/mod_client/`, `installer/mod_service/`, `installer/mod_service_category/`, `installer/mod_product/`, `installer/mod_product_category/`, `installer/mod_reference/`, `installer/mod_reference_category/` — **ALL DELETED**.
  - `mod/index/helper/mod_client.helper.php`, `mod_service.helper.php`, `mod_product.helper.php`, `mod_reference.helper.php` — **ALL DELETED**.
  - `installer/app/InstallerModuleCatalog.php` and `lib/ModuleRegistry.class.php` — **CLEAN (zero references)**.
  - `templates/admin/assets/js/mod_product.js` — **DELETED**.

#### B. Lingering Fixture Violation Found
- `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/installer/mod_faq_mod/index.php` (lines 45–79):
  Still attempts to seed polymorphic FAQ mappings for excluded modules:
  ```php
  array('name' => 'Product FAQs', 'mod_name' => 'mod_product', ...),
  array('name' => 'Service FAQs', 'mod_name' => 'mod_service', ...),
  array('name' => 'Project FAQs', 'mod_name' => 'mod_project', ...),
  ```
- **Action Required**: Remove these three array elements so that `installer/mod_faq_mod/index.php` only seeds active modules: `mod_content`, `mod_news`, `mod_page`, `mod_publication`.

---

## 2. Logic Chain

1. **R4 Corporate & Fiscal Foundation**:
   - The ORM model `mod_settings.model.php` and the admin template `templates/admin/mod_settings/legal_info.twig` allow administrators to save corporate registry, tax numbers, and contact details.
   - However, if the underlying database schema (`installer/mod_settings/mod_settings.sql` and `mod/mod_settings/mod_settings.sql`) lacks these 16 columns, SQLite and MySQL database queries during installation or updates will fail or discard fields.
   - Furthermore, the public display of E-E-A-T corporate credentials requires `templates/default/partials/_legal_info.twig`, links in `templates/default/index/footer.twig`, and seeded pages in `installer/mod_page/index.php`. Without them, the legal pages result in 404 errors or blank content.

2. **Sorani Kurdish Display Normalization**:
   - Arabic-script languages (Arabic, Persian, Sorani Kurdish) in the Amed Framework must render their localized native name in language dropdowns and installer choices (`العربية`, `فارسی`, `سۆرانی`).
   - Using Latin `Soranî` or non-specific `کوردی` creates inconsistency across the multi-lingual UI and violates project standards.

3. **R6 9-Language Parity & Code Language Standards**:
   - The framework guarantees seamless language switching across 9 core languages (`en`, `ku`, `ckb`, `ar`, `de`, `fr`, `sv`, `tr`, `zza`).
   - Missing dictionaries in `mod_user_pm`, `mod_video_embed`, and `mod_acl` cause fatal missing index warnings or untranslated fallback strings when browsing in Arabic, French, Swedish, or Sorani.
   - Legacy Turkish dictionary keys (`vazgec`, `hata`, etc.) and event handlers (`processSil`, `processDosya`) violate strict English codebase standards and code separation guidelines.
   - Kurdish grammar requires `Lêke` for addition actions and `nûjenkirin` for updating.

4. **Cleanliness & Excluded Modules**:
   - `AF/Bulletin` is an editorial/news platform, strictly excluding commercial business modules (`mod_client`, `mod_service`, `mod_product`, `mod_reference`).
   - While physical controllers and helpers were deleted, `installer/mod_faq_mod/index.php` still registers `mod_product`, `mod_service`, and `mod_project` FAQ records during setup, which would pollute the database with orphaned records.

---

## 3. Caveats
- No code was modified during this survey (strictly read-only investigation).
- All identified dictionary files to be ported (`ar.php`, `ckb.php`, `fr.php`, `sv.php`) have direct reference implementations available in `/home/malpersaz/Desktop/projects/htdocs/AF/Business` that can be cleanly adapted for Bulletin.
- When updating `mod_faq_mod` installer records, verify that existing active databases do not retain orphaned `mod_product` or `mod_service` rows in `mod_faq_mod`.

---

## 4. Conclusion

### Required Implementation Scope

#### Phase 1: R4 Corporate Legal, Tax & Cookie Policy
1. **Update SQL Schemas**:
   - Add the 16 corporate/fiscal fields to `installer/mod_settings/mod_settings.sql` and `mod/mod_settings/mod_settings.sql`.
   - Remove obsolete stats columns (`stat_completed_projects`, etc.) from `mod/mod_settings/mod_settings.sql`.
2. **Seed Legal & Cookie Pages in Installer**:
   - Port the 9 Legal Information page fixtures and 9 Cookie Policy page fixtures from `AF/Business/installer/mod_page/index.php` into `AF/Bulletin/installer/mod_page/index.php`.
3. **Frontend Legal & Cookie Components**:
   - Create `templates/default/partials/_legal_info.twig`.
   - Update `templates/default/partials/_cookie_consent.twig` to use dynamic localized slug link.
   - Update `templates/default/index/footer.twig` to include Legal Information and Cookie Policy links.
   - Update `templates/default/index/mod_page/page.twig` to conditionally render `_legal_info.twig`.
   - Add legal and cookie dictionary keys to `mod/index/lang/*.php` across all 9 languages.
4. **Normalize Sorani Kurdish**:
   - Set `'name' => 'سۆرانی'` in `installer/lang/index.php`.
   - Set `'label' => 'سۆرانی'` in `installer/app/InstallerLang.php` and `installer/lang/*.php`.

#### Phase 2: R6 Multilingual Parity & Code Standards
1. **Add Missing Language Dictionaries**:
   - `mod/mod_user_pm/lang/`: Add `ar.php`, `ckb.php`, `fr.php`, `sv.php`.
   - `mod/mod_video_embed/lang/`: Add `ar.php`, `ckb.php`, `fr.php`.
   - `mod/mod_acl/lang/`: Add `ar.php`, `ckb.php`, `fr.php`, `sv.php`.
2. **Refactor Turkish Keys & Action Verbs**:
   - Refactor legacy Turkish dictionary keys (`vazgec`, `hata`, `oke`, `ziyaretci`, `kaldir`, etc.) to standard English keys.
   - Update Kurdish dictionary files to use `add => 'Lêke'` and `nûve` -> `nûjenkirin`.
3. **Purge Legacy Turkish Identifiers in Code**:
   - Replace inline `processSil` and `processDosya` handlers with modern data attributes or English methods.

#### Phase 3: Excluded Module Cleanliness
1. **Clean `installer/mod_faq_mod/index.php`**:
   - Remove `mod_product`, `mod_service`, and `mod_project` from `$records` in `add_faq_mods()`.

---

## 5. Verification Method

To independently verify after implementation:

```bash
# 1. Verify absence of deprecated module references in active code
grep -rn "mod_client\|mod_service\|mod_product\|mod_reference" \
  /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/mod/ \
  /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/installer/ \
  /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/lib/ \
  /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/

# 2. Verify all 9 languages exist for every active module
python3 -c "
import os
for mod in ['mod_user_pm', 'mod_video_embed', 'mod_acl', 'mod_seo', 'mod_settings']:
    p = f'/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/mod/{mod}/lang'
    langs = sorted([f[:-4] for f in os.listdir(p) if f.endswith('.php')])
    print(f'{mod}: {len(langs)} languages -> {langs}')
"

# 3. Verify PHP syntax on all touched files
find /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/mod \
     /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/installer \
     -name "*.php" -exec php -l {} +

# 4. Run E2E test suites
php /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/tests/e2e/run.php
```
