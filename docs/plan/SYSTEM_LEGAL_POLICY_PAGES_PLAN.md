# System Legal, Statutory & Editorial Compliance Policy Pages Architecture Plan

## 1. Executive Summary & Architectural Objective

This document defines the concrete, repository-grounded architecture and phased implementation plan for all statutory, legal, data protection, and editorial compliance pages in **`AF/Bulletin` (Amed Framework — Bulletin CMS)**.

Every specification in this plan is grounded in the active `AF/Bulletin` `v1.0.4` codebase (`config/url.config.php`, `mod/index/index.php`, `mod/index/helper/legal.helper.php`, `mod/mod_settings/index.php`, `mod/mod_settings/model/mod_settings.model.php`, `installer/mod_settings/mod_settings.sql`, `lib/McpSettingsTools.class.php`, `lib/LlmsService.class.php`, `templates/admin/mod_settings/legal_info.twig`, `templates/amida/index/legal/`, and `templates/amida/partials/_legal_info.twig`) and strictly adheres to `AGENTS.md` Rules 1–10.

### Core Architectural Invariants
1. **Domain Alignment (Journalism & Digital Publishing):** Unlike commercial retail repositories, `AF/Bulletin` is a newsroom, press, and digital publication platform (`mod_news`, `mod_headline`, `mod_publication`, `mod_video_embed`, `mod_announcements`). All statutory disclosures prioritize **Press Law / Imprint (`Künye`)**, **Editorial Principles & Right of Reply**, **Copyright & IP Protection**, **GDPR / KVKK Privacy**, and **Google News / Discover Publisher Transparency** (`NewsMediaOrganization` Schema.org metadata). No foreign modules (`mod_organization_type`, `mod_product`, etc.) may be referenced.
2. **Module Isolation (`mod_page` Separation):** `mod_page` remains strictly a generic CMS page module free of slug conditionals or hardcoded legal template branches. All statutory and compliance endpoints are served centrally via `mod/index/index.php` and `mod/index/helper/legal.helper.php`.
3. **Pure-English Canonical Routing:** All URL routes and controller endpoints use clean, standard English slugs (`/{lang}/legal`, `/{lang}/cookie-policy`, `/{lang}/privacy-policy`, `/{lang}/terms-of-service`, `/{lang}/editorial-policy`, `/{lang}/accessibility`, `/{lang}/security-policy`, `/{lang}/compliance`).
4. **Hybrid Data Architecture:**
   - **Dynamic Institutional & Masthead Identifiers:** Publisher/company name, Managing Director (`managing_director`), Editor-in-Chief (`editor_in_chief`), Press License (`press_code_license`), Hosting Provider (`hosting_provider`), DPO email (`dpo_email`), Tax/MERSIS numbers, and contact coordinates are stored in `mod_settings`, managed via the phone-only admin modal (`mod_settings/legal_info`) and MCP tools (`McpSettingsTools`), and injected via `$this->site`.
   - **Dynamic Cookie Inventory:** Stored per language in `mod_settings_cookies` and managed via `mod_settings/cookie_policy` and MCP cookie declaration tools.
   - **Localized Statutory Clauses:** Served from `mod/index/lang/*.php` with descriptive English keys and zero hardcoded fallback prose in Twig templates.
5. **Strict Code Separation & Accessibility (Rules 2, 6 & 7):** Zero inline CSS (`style="..."` or `<style>`) and zero inline JavaScript (`onclick="..."` or inline `<script>` logic) across all public (`templates/amida/`) and phone-only admin (`templates/admin/`) templates. Full WCAG 2.1 Level AA and dark-mode parity.

---

## 2. Root-Cause Audit of Current Implementation Defects (Baseline `v1.0.4`)

A deterministic audit of the current codebase identified seven concrete defects and architectural gaps in the existing legal/policy implementation and the previous draft of this plan:

### 2.1. Commercial E-Commerce Drift (`Warranty & Return Policy`)
- **Defect:** The previous plan defined **Module 5** as `Warranty & Return Policy (/{lang}/return-policy)` covering *"14-day statutory withdrawal, RMA procedures, original packaging, and hardware/product defects"*.
- **Impact:** `AF/Bulletin` is a news and publication CMS, not an e-commerce store selling physical merchandise. Meanwhile, mandatory press and journalism compliance disclosures required by Press Law No. 5187, Internet Law No. 5651, EU EMFA, and Google News (`publishingPrinciples`, `correctionsPolicy`, `actionableFeedbackPolicy` — *Right of Reply & Corrections / Cevap ve Düzeltme Hakkı*) were missing from the catalog.
- **Resolution:** Replace Module 5 with **Editorial Principles, Corrections & Right of Reply Policy (`/{lang}/editorial-policy`)**. Retain existing `warranty_*` columns in `mod_settings` only as optional legacy columns (rendered in `_legal_info.twig` solely if explicitly non-empty) and remove e-commerce retail assumptions from the primary Bulletin workflow.

### 2.2. Missing Publishing Credentials (`Künye`) Columns in `mod_settings` Backend & Admin Form
- **Defect:** Both `templates/amida/partials/_legal_info.twig` (lines 19–25, 71–144) and `lib/LlmsService.class.php` (lines 171–203) read journalism masthead and legal fields from `$site` / `$settings`:
  - `managing_director`, `editor_in_chief`, `press_code_license`, `dpo_email`, `hosting_provider`, `compliance_jurisdiction`
  - `legal_trading_name`, `company_registration_number`, `corporate_address`, `contact_address`
  However, **none of these 10 columns exist** in:
  1. `mod/mod_settings/index.php` (`ensureLegalInfoColumns()`, `_extract_legal_fields_from_post()`, `_normalize_legal_fields()`)
  2. `mod/mod_settings/model/mod_settings.model.php`
  3. `installer/mod_settings/mod_settings.sql`
  4. `templates/admin/mod_settings/legal_info.twig`
- **Impact:** Administrators cannot enter or save the newspaper's **Publishing Credentials (`Künye`)** from the admin panel, causing the entire `has_publishing_info` card in `_legal_info.twig` and the masthead fields in `llms.txt` to remain permanently blank unless manually inserted via raw SQL.

### 2.3. Broken `#company_type` Select & Missing `vat_number` Input in `templates/admin/mod_settings/legal_info.twig`
- **Defect:**
  - `templates/admin/mod_settings/legal_info.twig` (lines 104–123) renders a `<select id="company_type">` populated from `organization_types`. Because `mod_organization_type` is a forbidden foreign module in `AF/Bulletin`, `mod/mod_settings/index.php` (line 1058) sets `$this->theme->set('organization_types', []);`, resulting in an unusable empty dropdown in the admin UI.
  - `vat_number` is defined in `ensureLegalInfoColumns()` and rendered in `_legal_info.twig` (line 280), but has no `<input>` field in `templates/admin/mod_settings/legal_info.twig`.
- **Resolution:** Convert `company_type` in `templates/admin/mod_settings/legal_info.twig` into a clean text input (or static localized media/company type datalist without referencing `mod_organization_type`), add the missing `vat_number` input, and add a dedicated **Publishing Credentials & Masthead (`Künye`)** card in `templates/admin/mod_settings/legal_info.twig`.

### 2.4. Duplicate-Content Fallthrough in `mod/index/helper/legal.helper.php`
- **Defect:** In `mod/index/helper/legal.helper.php` (lines 16–90):
  - `$validTypes` is hardcoded to `['info', 'cookies', 'privacy', 'terms', 'warranty']`.
  - Any unknown `$type` silently falls back to `'info'`.
  - More critically, the `if ($type === 'cookies') ... else ...` branch renders `index/legal/index` for `'privacy'`, `'terms'`, and `'warranty'` while setting `canonical_url` to `/legal/privacy`, `/legal/terms`, etc.
- **Impact:** Visiting `/tr/legal/privacy` or `/tr/legal/terms` serves the exact same HTML as `/tr/legal` under a different canonical URL, causing SEO duplicate-content indexing.
- **Resolution:** Refactor `legal_helper::render($type)` into a deterministic policy registry map where each supported policy slug resolves to its dedicated template, localized title/subtitle, canonical URL (`/{lang}/{canonical-slug}`), and Schema.org page type, returning a proper HTTP 404 via `$this->notFound()` for unknown slugs.

### 2.5. Meta Description Bug & Hardcoded English Strings in Twig Templates
- **Defect 1 (`templates/amida/index/legal/index.twig`, line 10):**
  ```twig
  {% set page_meta_description = lang.index.legal_info_pending is defined and lang.index.legal_info_pending is not empty ? lang.index.legal_info_pending : site.description %}
  ```
  Even when full legal information is configured, `<meta name="description">`, `og:description`, `twitter:description`, and JSON-LD `description` output `lang.index.legal_info_pending` (*"The official and statutory information of the company is currently being prepared."*) instead of `lang.index.legal_notice_desc`!
- **Defect 2 (`templates/amida/index/legal/cookie_policy.twig`, lines 171–190, 231–244, 285–292, 333–340):**
  Contains hardcoded English fallback table rows (`"Preserves user session state across page requests."`, `"First-party"`, `"Analytics Provider"`) directly inside Twig markup, violating Rule 7.
- **Defect 3 (`og_locale` Mapping in `index.twig` & `cookie_policy.twig`):**
  Only maps `en`, `tr`, `de`, `ku`, `ckb`, `zza`, omitting `ar` (`ar_AR`), `fr` (`fr_FR`), `sv` (`sv_SE`), and `ru` (`ru_RU`).

### 2.6. Localization Dictionary File Asymmetry (`mod/index/lang/` vs `mod/mod_settings/lang/`)
- **Defect:** `mod/mod_settings/lang/` and `legal.helper.php` include 10 locales (`ar`, `ckb`, `de`, `en`, `fr`, `ku`, `ru`, `sv`, `tr`, `zza`), whereas `mod/index/lang/` has 9 files (`ru.php` is absent). Furthermore, previous documentation cited inconsistent version numbers (`v1.0.3.12`) not matching `docs/VERSION.md` (`v1.0.0`–`v1.0.4`).

### 2.7. Missing MCP Tool Fields, Sitemap Entries & `llms.txt` Policy Links
- **Defect:**
  - `lib/McpSettingsTools.class.php` (`update_site_settings`) only exposes `cookie_policy_content` among legal columns; none of the corporate registry or journalism masthead (`Künye`) columns can be updated via MCP.
  - `mod/index/index.php::pageSitemap()` does not include the canonical legal/policy URLs (`/{lang}/legal`, `/{lang}/cookie-policy`, `/{lang}/privacy-policy`, etc.) in XML sitemaps.
  - `lib/LlmsService.class.php` (lines 210–211) links only `/legal` and `/cookie-policy`.

---

## 3. Policy Pages Catalog & Status Matrix

All statutory and editorial compliance pages use pure-English canonical routes under `/{lang}/...` mapped in `config/url.config.php` to `mod/index/index.php` and `mod/index/helper/legal.helper.php`:

| # | Policy Page | Canonical URL | Legacy / Sub-Route Alias | Schema.org `@type` | Current Status (`v1.0.4`) |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **1** | **Legal Information & Imprint (`Künye`)** | `/{lang}/legal` | `/{lang}/legal/info`, `/{lang}/imprint` | `AboutPage` + `NewsMediaOrganization` | **Implemented** *(Needs DB/Admin `Künye` sync & meta desc fix)* |
| **2** | **Cookie Policy & Preferences** | `/{lang}/cookie-policy` | `/{lang}/cookies`, `/{lang}/legal/cookies` | `WebPage` | **Implemented** *(Needs Twig hardcoded fallback cleanup)* |
| **3** | **Privacy Policy & Data Protection** | `/{lang}/privacy-policy` | `/{lang}/legal/privacy` | `WebPage` | **Planned** |
| **4** | **Terms of Service & Copyright** | `/{lang}/terms-of-service` | `/{lang}/legal/terms` | `WebPage` | **Planned** |
| **5** | **Editorial Principles & Right of Reply** | `/{lang}/editorial-policy` | `/{lang}/corrections-policy`, `/{lang}/legal/editorial` | `WebPage` (`publishingPrinciples` / `correctionsPolicy`) | **Planned** *(Replaces E-Commerce Return Policy)* |
| **6** | **Accessibility Statement** | `/{lang}/accessibility` | `/{lang}/legal/accessibility` | `WebPage` | **Planned** |
| **7** | **Security & Vulnerability Disclosure** | `/{lang}/security-policy` | `/{lang}/legal/security` | `WebPage` | **Planned** |
| **8** | **Press Ethics & Corporate Compliance** | `/{lang}/compliance` | `/{lang}/legal/compliance` | `WebPage` (`ethicsPolicy` / `diversityPolicy`) | **Planned** |

---

## 4. Database Schema, Admin UI & MCP Synchronization Plan

Before adding the remaining public policy templates, the underlying `mod_settings` schema, phone-only admin modal, and MCP settings tools must be synchronized with `templates/amida/partials/_legal_info.twig` and `lib/LlmsService.class.php`.

### 4.1. `mod_settings` Schema Expansion (`installer/mod_settings/mod_settings.sql`, `mod/mod_settings/index.php`, `mod/mod_settings/model/mod_settings.model.php`)
Add the missing journalism masthead (`Künye`) and corporate address columns to `ensureLegalInfoColumns()` in `mod/mod_settings/index.php`, `installer/mod_settings/mod_settings.sql`, and property declarations in `mod/mod_settings/model/mod_settings.model.php`:

| Column Name | SQL Type | Purpose & Consumer |
| :--- | :--- | :--- |
| `legal_trading_name` | `VARCHAR(255) DEFAULT NULL` | Brand / trading name (`_legal_info.twig` line 171) |
| `company_registration_number` | `VARCHAR(100) DEFAULT NULL` | Registration ID (`_legal_info.twig` line 180, `LlmsService` line 192) |
| `corporate_address` | `TEXT DEFAULT NULL` | Registered headquarters address (`_legal_info.twig` line 225, `LlmsService` line 196) |
| `contact_address` | `TEXT DEFAULT NULL` | Legal correspondence address (`_legal_info.twig` line 341, `LlmsService` line 196) |
| `managing_director` | `VARCHAR(255) DEFAULT NULL` | Publisher / Managing Director (`_legal_info.twig` line 83, `LlmsService` line 172) |
| `editor_in_chief` | `VARCHAR(255) DEFAULT NULL` | Responsible Editor-in-Chief (`_legal_info.twig` line 92, `LlmsService` line 175) |
| `press_code_license` | `VARCHAR(150) DEFAULT NULL` | Press License / BİK / ISSN code (`_legal_info.twig` line 101, `LlmsService` line 178) |
| `dpo_email` | `VARCHAR(255) DEFAULT NULL` | Data Protection Officer (KVKK/GDPR) email (`_legal_info.twig` line 113, `LlmsService` line 181) |
| `hosting_provider` | `VARCHAR(255) DEFAULT NULL` | Statutory Hosting Provider under Law No. 5651 (`_legal_info.twig` line 125) |
| `compliance_jurisdiction` | `VARCHAR(255) DEFAULT NULL` | Competent courts & regulatory jurisdiction (`_legal_info.twig` line 134, `LlmsService` line 200) |

Update `_extract_legal_fields_from_post()` and `_normalize_legal_fields()` in `mod/mod_settings/index.php` (lines 2003–2055) so all 10 fields are extracted, trimmed, and persisted across `mod_settings`.

### 4.2. Phone-Only Admin Surface (`templates/admin/mod_settings/legal_info.twig`)
In accordance with **Rule 6 (Admin Mobile-First Surface Rule)** and **Rule 2 (Strict Code Separation)**:
1. Add **Section 0: Publishing Credentials & Masthead (`Künye`)** at the top of `templates/admin/mod_settings/legal_info.twig` with mobile-first inputs for:
   - `managing_director`
   - `editor_in_chief`
   - `press_code_license`
   - `dpo_email`
   - `hosting_provider`
   - `compliance_jurisdiction`
2. In **Section 1 (Company & Registry)**:
   - Replace the empty `<select id="company_type">` (which depended on the forbidden `mod_organization_type` module) with a standard `<input type="text" id="company_type" name="company_type">`.
   - Add inputs for `legal_trading_name`, `company_registration_number`, and `corporate_address`.
3. In **Section 2 (Tax & Fiscal)**:
   - Add the missing `<input type="text" id="vat_number" name="vat_number">` field.
4. In **Section 3 (Legal Contact)**:
   - Add `<textarea id="contact_address" name="contact_address">`.
5. Synchronize all new field labels and placeholders across all 10 `mod/mod_settings/lang/{ar,ckb,de,en,fr,ku,ru,sv,tr,zza}.php` dictionaries.

### 4.3. MCP Settings Tool Integration (`lib/McpSettingsTools.class.php`)
Update `McpSettingsTools::definitions()` (`update_site_settings` schema) and `$editableColumns` / global synchronization logic in `lib/McpSettingsTools.class.php` so MCP agents can read and update both the corporate registry fields and the journalism masthead (`Künye`) fields.

---

## 5. Detailed Specifications per Policy Page

### Module 1: Legal Information & Imprint (`Künye`) — `/{lang}/legal`
- **Status:** Implemented; scheduled for schema sync and metadata fix.
- **Purpose:** Official statutory corporate disclosures and newspaper/digital publication masthead (`Künye` under Turkish Press Law No. 5187, Internet Law No. 5651, and EU transparency directives).
- **Routing:**
  - `config/url.config.php`: `$url['legal'] = 'index/legal';`, `$url['imprint'] = 'index/legal';`
  - `mod/index/index.php`: `public function legal($type = null)` -> `legal_helper::render($type)`
- **Template:** `templates/amida/index/legal/index.twig` (includes `templates/amida/partials/_legal_info.twig`)
- **Required Fixes in `index.twig`:**
  1. Fix line 10 so `page_meta_description` uses `lang.index.legal_notice_desc` (falling back to `site.description`) instead of `lang.index.legal_info_pending`.
  2. Expand `og_locale` mapping to cover all supported locales (`en_US`, `tr_TR`, `ku`, `ckb`, `zza`, `de_DE`, `fr_FR`, `ar_AR`, `sv_SE`, `ru_RU`).
  3. Enrich the `NewsMediaOrganization` JSON-LD node with `publishingPrinciples` (`{{ site.saddress }}editorial-policy`), `correctionsPolicy` (`{{ site.saddress }}editorial-policy#corrections`), `ethicsPolicy` (`{{ site.saddress }}compliance`), and `masthead` (`{{ site.saddress }}legal`).

---

### Module 2: Cookie Policy & Interactive Preferences Center — `/{lang}/cookie-policy`
- **Status:** Implemented; scheduled for i18n template purification.
- **Purpose:** Transparent disclosure of first-party and third-party cookies (`mod_settings_cookies`) and interactive consent management (`cookie_consent` storage).
- **Routing:**
  - `config/url.config.php`: `$url['cookie-policy'] = 'index/cookie_policy';`, `$url['cookies'] = 'index/cookie_policy';`
  - `mod/index/index.php`: `public function cookie_policy()` -> `legal_helper::render('cookies')`
- **Template:** `templates/amida/index/legal/cookie_policy.twig`
- **External Assets:** `templates/amida/static/css/cookie-policy.css`, `templates/amida/static/js/cookie-policy.js`
- **Required Fixes in `cookie_policy.twig`:**
  - Replace hardcoded English fallback table rows (`"Preserves user session state..."`, `"First-party"`, etc.) with localized keys from `lang.index.cookie_fallback.*` (note: `legal.helper.php` already populates `cookies_by_category` via `getDefaultCookieSamples($langCode, $siteName)` when the DB table is empty, so Twig fallbacks must use `lang.index.*` keys rather than raw English literals).

---

### Module 3: Privacy Policy & Data Protection (GDPR / KVKK) — `/{lang}/privacy-policy`
- **Status:** Planned.
- **Purpose:** Statutory personal data protection notice under GDPR (EU 2016/679) and KVKK (Law No. 6698).
- **Routing:**
  - `config/url.config.php`: `$url['privacy-policy'] = 'index/privacy_policy';`, `$url['privacy-policy/'] = 'index/privacy_policy';`
  - `mod/index/index.php`: `public function privacy_policy()` -> `$legal->render('privacy');`
- **Key Sections:**
  1. **Data Controller Identity (`Veri Sorumlusu`):** Dynamically binds `site.legal_company_name` (or `site.name`), `site.corporate_address`, `site.registered_email`, and `site.dpo_email`.
  2. **Categories of Processed Data:** Reader access logs (IP address, user-agent, timestamps as required by Law No. 5651), user account registration data (`mod_user`), comment submissions (`mod_news` / `mod_publication`), and cookie identifiers.
  3. **Legal Bases & Processing Purposes:** Journalism and freedom of expression exemptions, performance of service, statutory retention obligations, and explicit consent.
  4. **Data Retention & Third-Party Transfers:** Log retention schedules, CDN/hosting infrastructure (`site.hosting_provider`), and analytics processors.
  5. **Data Subject Rights (GDPR Art. 15–22 / KVKK Art. 11):** Right of access, rectification, erasure, objection, and direct DPO application channel (`site.dpo_email` / `site.registered_email`).
- **Template:** `templates/amida/index/legal/privacy_policy.twig`
- **Structured Data:** Schema.org `WebPage` with `about: "Privacy Policy"`.

---

### Module 4: Terms of Service & Copyright Policy — `/{lang}/terms-of-service`
- **Status:** Planned.
- **Purpose:** Governing terms of use for readers, subscribers, and syndication partners, including intellectual property protection for news articles, columns, photographs, and video broadcasts.
- **Routing:**
  - `config/url.config.php`: `$url['terms-of-service'] = 'index/terms_of_service';`, `$url['terms-of-service/'] = 'index/terms_of_service';`
  - `mod/index/index.php`: `public function terms_of_service()` -> `$legal->render('terms');`
- **Key Sections:**
  1. **Scope & Acceptance of Terms:** Conditions for accessing `site.name` digital journalism services.
  2. **Copyright, Press Attribution & Syndication Rules:** Ownership of news reports (`mod_news`), opinion columns (`mod_publication`), photo galleries (`mod_gallery`), and videos (`mod_video`). Quotation limits under press law (mandatory source attribution and active hyperlink back to the canonical URL; prohibition of unauthorized full-text scraping or commercial republication).
  3. **Reader Comments & Community Guidelines:** Rules governing user comments and interactive polls (`mod_survey`), hate-speech prohibition, and moderation rights.
  4. **AI Training & Automated Scraping Notice:** Alignment with `llms.txt` attribution requirements and prohibition of abusive bot traffic.
  5. **Limitation of Liability & External Links:** Editorial disclaimers and governing court jurisdiction (`site.compliance_jurisdiction`).
- **Template:** `templates/amida/index/legal/terms_of_service.twig`

---

### Module 5: Editorial Principles, Corrections & Right of Reply — `/{lang}/editorial-policy`
- **Status:** Planned *(Replaces E-Commerce Return & Warranty Policy)*.
- **Purpose:** Press transparency, fact-checking methodology, corrections/retractions workflow, and statutory **Right of Reply and Correction (`Cevap ve Düzeltme Hakkı`)** procedure required for news publishers and Google News / Discover (`kevseo`) trust signals.
- **Routing:**
  - `config/url.config.php`:
    - `$url['editorial-policy'] = 'index/editorial_policy';`
    - `$url['editorial-policy/'] = 'index/editorial_policy';`
    - `$url['corrections-policy'] = 'index/editorial_policy';`
  - `mod/index/index.php`: `public function editorial_policy()` -> `$legal->render('editorial');`
- **Key Sections:**
  1. **Editorial Independence & Verification Standards:** Multi-source verification, separation of news reporting (`mod_news`) from opinion columns (`mod_publication`), and protection of confidential sources.
  2. **Corrections, Clarifications & Retractions (`#corrections`):** How factual errors are updated transparently with timestamped editorial notes (`updated_at`) rather than silent deletion.
  3. **Statutory Right of Reply (`Cevap ve Düzeltme Hakkı`):** Step-by-step legal procedure for individuals or institutions to submit formal correction/reply requests via `site.registered_email` or `site.mail` to the Editor-in-Chief (`site.editor_in_chief`).
  4. **AI-Assisted Editorial Workflow Disclosure:** Transparent disclosure of human-in-the-loop editorial oversight, automated fact-checking (`kevrast`), and active-language verification.
- **Template:** `templates/amida/index/legal/editorial_policy.twig`
- **Structured Data:** Schema.org `WebPage` linked from `NewsMediaOrganization.publishingPrinciples` and `NewsMediaOrganization.correctionsPolicy`.

---

### Module 6: Accessibility Statement (WCAG 2.1 Level AA) — `/{lang}/accessibility`
- **Status:** Planned.
- **Purpose:** Formal commitment to digital accessibility for all readers in accordance with WCAG 2.1 Level AA and EN 301 549 standards.
- **Routing:**
  - `config/url.config.php`: `$url['accessibility'] = 'index/accessibility';`, `$url['accessibility/'] = 'index/accessibility';`
  - `mod/index/index.php`: `public function accessibility()` -> `$legal->render('accessibility');`
- **Key Sections:**
  1. **Conformance Status:** WCAG 2.1 Level AA target across desktop broadsheet and mobile viewports.
  2. **Implemented Accessibility Features:** Semantic HTML5 landmarks (`<header>`, `<nav>`, `<main>`, `<article>`, `<footer>`), keyboard navigation (`/` search shortcut, `:focus-visible` rings), `prefers-reduced-motion` support, dark/light contrast compliance, and screen-reader friendly ARIA labels.
  3. **Multilingual & Script Support:** Full typography and directional support for LTR (`tr`, `ku`, `en`, `de`, `fr`, `sv`, `zza`, `ru`) and RTL (`ar`, `ckb`) scripts.
  4. **Accessibility Feedback Channel:** Direct contact link (`site.mail` / `site.legal_contact_phone`) for reporting barriers.
- **Template:** `templates/amida/index/legal/accessibility.twig`

---

### Module 7: Security Policy & Vulnerability Disclosure — `/{lang}/security-policy`
- **Status:** Planned.
- **Purpose:** Platform security architecture transparency and responsible vulnerability disclosure framework (`security.txt` / ISO 29147 alignment).
- **Routing:**
  - `config/url.config.php`: `$url['security-policy'] = 'index/security_policy';`, `$url['security-policy/'] = 'index/security_policy';`
  - `mod/index/index.php`: `public function security_policy()` -> `$legal->render('security');`
- **Key Sections:**
  1. **Infrastructure & Application Safeguards:** TLS/HTTPS enforcement, hardened database session handling (`Session_Db` SHA-256 fingerprinting), CSRF token verification, Role-Based Access Control (`mod_acl`), and outbound SSRF protocol pinning (`HttpClient`).
  2. **Responsible Vulnerability Disclosure Guidelines:** Scope, safe-harbor assurance for good-faith security researchers, and encrypted/direct reporting channel (`site.registered_email` / `site.dpo_email`).
  3. **Incident Response & Breach Notification:** Statutory notification commitments.
- **Template:** `templates/amida/index/legal/security_policy.twig`

---

### Module 8: Press Ethics & Corporate Compliance — `/{lang}/compliance`
- **Status:** Planned.
- **Purpose:** Journalistic code of ethics, conflict-of-interest rules, anti-bribery commitments, diversity policy, and whistleblower protection.
- **Routing:**
  - `config/url.config.php`: `$url['compliance'] = 'index/compliance';`, `$url['compliance/'] = 'index/compliance';`
  - `mod/index/index.php`: `public function compliance()` -> `$legal->render('compliance');`
- **Key Sections:**
  1. **Journalistic Code of Ethics:** Commitment to truthful reporting, human rights, and prohibition of hate speech or defamation.
  2. **Advertising & Sponsored Content Separation:** Strict visual and editorial separation between independent journalism and advertisements/announcements (`mod_announcements`).
  3. **Conflict of Interest & Anti-Corruption:** Prohibition of undisclosed gifts, political/commercial pressure, or paid-for coverage.
  4. **Diversity, Inclusion & Whistleblower Channel:** Equal opportunity newsroom standards and confidential ethics reporting channel.
- **Template:** `templates/amida/index/legal/compliance.twig`
- **Structured Data:** Schema.org `WebPage` linked from `NewsMediaOrganization.ethicsPolicy` and `NewsMediaOrganization.diversityPolicy`.

---

## 6. Shared Styling & Template Architecture (Rule 2 Compliance)

To avoid duplicating CSS across 8 policy templates and to strictly satisfy **Rule 2 (Strict Code Separation Mandate)**:
1. **Shared Policy Stylesheet (`templates/amida/static/css/legal-info.css`):**
   - Extend `templates/amida/static/css/legal-info.css` with reusable policy document classes (`.policy-doc-shell`, `.policy-section-card`, `.policy-clause-list`, `.policy-meta-banner`, `.policy-nav-pills`) supporting both Light and Dark modes (`[data-bs-theme="dark"]`) and RTL layouts (`[dir="rtl"]`).
2. **Cross-Policy Navigation Partial (`templates/amida/partials/_legal_nav.twig`):**
   - Create a shared horizontal/sidebar navigation bar included at the top of all 8 legal/policy templates so visitors and auditors can seamlessly switch between:
     - `Legal Information (Künye)` (`/{lang}/legal`)
     - `Editorial Policy` (`/{lang}/editorial-policy`)
     - `Privacy Policy` (`/{lang}/privacy-policy`)
     - `Cookie Policy` (`/{lang}/cookie-policy`)
     - `Terms of Service` (`/{lang}/terms-of-service`)
     - `Accessibility` (`/{lang}/accessibility`)
     - `Security Policy` (`/{lang}/security-policy`)
     - `Compliance & Ethics` (`/{lang}/compliance`)
3. **Footer, XML Sitemap & `llms.txt` Wiring:**
   - **`templates/amida/index/footer.twig`:** Add clean policy links in `.feeds-area` / footer compliance bar.
   - **`mod/index/index.php::pageSitemap()`:** Output `<url>` entries for all active canonical policy endpoints (`legal`, `cookie-policy`, `privacy-policy`, `terms-of-service`, `editorial-policy`, `accessibility`, `security-policy`, `compliance`) with `<priority>0.60</priority>`.
   - **`lib/LlmsService.class.php`:** Add the full set of canonical policy URLs under `## Optional` (lines 205–212) so AI crawlers and RAG indexers discover all governance policies.

---

## 7. Phased Implementation Roadmap

```
[Phase 1: Database Schema, Admin Künye UI & MCP Synchronization]
  ├── 1.1 Update installer/mod_settings/mod_settings.sql & mod/mod_settings/model/mod_settings.model.php
  │       with the 10 missing publishing/corporate columns (managing_director, editor_in_chief,
  │       press_code_license, dpo_email, hosting_provider, compliance_jurisdiction,
  │       legal_trading_name, company_registration_number, corporate_address, contact_address).
  ├── 1.2 Update ensureLegalInfoColumns(), _extract_legal_fields_from_post(), and
  │       _normalize_legal_fields() in mod/mod_settings/index.php.
  ├── 1.3 Refactor templates/admin/mod_settings/legal_info.twig (Phone-Only Admin Surface):
  │       add Publishing Credentials (Künye) card, replace empty organization_types select,
  │       add vat_number, corporate_address, and contact_address inputs.
  ├── 1.4 Synchronize mod/mod_settings/lang/{ar,ckb,de,en,fr,ku,ru,sv,tr,zza}.php.
  └── 1.5 Expose legal & masthead fields in lib/McpSettingsTools.class.php.

[Phase 2: Routing & Controller/Helper Refactoring]
  ├── 2.1 Register canonical routes in config/url.config.php:
  │       privacy-policy, terms-of-service, editorial-policy, corrections-policy,
  │       accessibility, security-policy, compliance, imprint.
  ├── 2.2 Add controller action methods in mod/index/index.php.
  └── 2.3 Refactor mod/index/helper/legal.helper.php (legal_helper::render) with a strict
          policy type registry (info, cookies, privacy, terms, editorial, accessibility,
          security, compliance) and 404 handling for invalid slugs.

[Phase 3: Localization Dictionary Parity]
  ├── 3.1 Add structured policy keys ($lang['index']['policy'][...]) to mod/index/lang/en.php.
  └── 3.2 Synchronize translations across all active mod/index/lang/*.php dictionaries
          (tr, ku, en, de, fr, ar, ckb, sv, zza, and create/sync ru.php if enabled).

[Phase 4: Public Theme Templates, Sitemap & llms.txt Integration]
  ├── 4.1 Fix page_meta_description bug and og_locale mapping in templates/amida/index/legal/index.twig.
  ├── 4.2 Replace hardcoded English fallback strings in templates/amida/index/legal/cookie_policy.twig.
  ├── 4.3 Create templates/amida/partials/_legal_nav.twig and extend templates/amida/static/css/legal-info.css.
  ├── 4.4 Create templates/amida/index/legal/{privacy_policy,terms_of_service,editorial_policy,
  │       accessibility,security_policy,compliance}.twig.
  └── 4.5 Wire policy links into templates/amida/index/footer.twig, mod/index/index.php::pageSitemap(),
          and lib/LlmsService.class.php.

[Phase 5: Verification & Release Documentation]
  ├── 5.1 Run deterministic syntax, code separation, i18n, and module boundary guards.
  ├── 5.2 Run MCP smoke test suite (tasks/mcp_smoke.php).
  └── 5.3 Record changes in docs/VERSION.md.
```

---

## 8. Deterministic Verification & Quality Gates

Every phase implemented from this plan must pass the following automated repository checks with zero errors before completion:

1. **PHP Syntax Validation:**
   ```bash
   php -l config/url.config.php
   php -l mod/index/index.php
   php -l mod/index/helper/legal.helper.php
   php -l mod/mod_settings/index.php
   php -l mod/mod_settings/model/mod_settings.model.php
   php -l lib/McpSettingsTools.class.php
   php -l lib/LlmsService.class.php
   ```
2. **Strict Code Separation Guard (Rule 2):**
   ```bash
   python3 tools/code_separation_guard.py --path templates/amida/index/legal/
   python3 tools/code_separation_guard.py --path templates/admin/mod_settings/
   ```
   Must report **`0 violations`** (no inline `style="..."`, `<style>`, `onclick="..."`, or inline `<script>` blocks in any `.twig` or `.php` file; `<script type="application/ld+json">` is allowed for Schema.org metadata).
3. **Localization & Language Purity Guard (Rule 7):**
   ```bash
   python3 tools/i18n_guard.py --path mod/index/lang/
   python3 tools/i18n_guard.py --path mod/mod_settings/lang/
   ```
   Must verify descriptive English keys in `en.php` and parity across localized dictionaries.
4. **Domain Boundary Enforcement Guard (Rule 4 & Synchronizer Rule 2):**
   ```bash
   python3 tools/module_boundary_guard.py
   ```
   Must confirm zero references to forbidden commercial or assessment modules (`mod_organization_type`, `mod_product`, `mod_service`, `mod_content`, etc.).
5. **MCP & Runtime Smoke Verification:**
   ```bash
   php tasks/mcp_smoke.php
   ```
