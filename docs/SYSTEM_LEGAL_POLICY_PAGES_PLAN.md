# System Legal, Statutory & Compliance Policy Pages Architecture Plan

## 1. Executive Summary & Objective

This architectural document defines the standardized roadmap and modular implementation blueprint for all institutional, statutory, legal, and compliance policy pages in **Amed Framework / Bulletin CMS**.

### Core Architecture Principles
1. **Module Isolation:** Generic content modules (such as `mod_page`) must remain strictly generic and free of hardcoded routing hacks, slug conditionals, or page-specific template branches.
2. **Centralized Frontend Routing:** All institutional/statutory pages are routed through canonical, pure English endpoints under `mod/index` (e.g., `/{lang}/legal`, `/{lang}/privacy-policy`, `/{lang}/cookie-policy`).
3. **Data Source Hybrid Architecture:**
   - Dynamic institutional identifiers (Company/Publisher Name, Tax ID, Address, Phone, Email, Trade Registry) are retrieved directly from `mod_settings` and `site.config.php`.
   - Structured policy clauses and descriptions are served from localized language dictionaries in `mod/index/lang/` with full multilingual i18n support across all platform locales (`en`, `ku`, `ckb`, `ar`, `de`, `fr`, `sv`, `tr`, `zza`, `ru`).
4. **Theme Parity & Accessibility:** Full template compliance across the active public frontend theme (`amida`), adhering to WCAG 2.1 AA accessibility standards, dark mode theming, and Schema.org structured data.

---

## 2. Policy Pages Catalog & Status Matrix

| # | Page Name | Canonical URL | Primary Objective & Scope | Status |
|---|---|---|---|---|
| 1 | **Legal Information** | `/{lang}/legal` | Corporate registry, Tax ID, MERSIS, authorized rep, statutory warranty | **Completed (v1.0.3.12)** |
| 2 | **Privacy Policy** | `/{lang}/privacy-policy` | GDPR / KVKK data protection, processing legal bases, subject rights | *Ready for Execution* |
| 3 | **Cookie Policy** | `/{lang}/cookie-policy` | Cookie categorization, tracker disclosures, consent management | **Completed (v1.0.3.12)** |
| 4 | **Terms of Service** | `/{lang}/terms-of-service` | Site usage terms, IP rights, liability disclaimers, governing law | *Ready for Execution* |
| 5 | **Warranty & Return Policy** | `/{lang}/return-policy` | Statutory 14-day withdrawal, warranty claims, RMA process | *Ready for Execution* |
| 6 | **Accessibility Statement** | `/{lang}/accessibility` | WCAG 2.1 AA compliance, accessibility features, contact channels | *Ready for Execution* |
| 7 | **Security Policy** | `/{lang}/security-policy` | Infrastructure safeguards, SSL/TLS, responsible disclosure policy | *Ready for Execution* |
| 8 | **Corporate Compliance** | `/{lang}/compliance` | Ethical principles, anti-bribery, whistleblower protection | *Ready for Execution* |

---

## 3. Detailed Specifications per Policy Page

---

### Module 1: Legal Information (`/{lang}/legal`) — [COMPLETED]
* **Purpose:** Official commercial and statutory company disclosures.
* **Routing:** `mod/index/index.php` -> `legal()` -> `legal.helper.php`.
* **Data Sources:** `site.legal_company_name`, `site.tax_number`, `site.tax_office`, `site.trade_registry_number`, `site.central_registration_number`, `site.authorized_person`, `site.registered_email`, `site.legal_contact_phone`.
* **Structured Data:** Schema.org `AboutPage` & `Organization`.
* **Templates:** `templates/*/index/legal/index.twig` (Includes `partials/_legal_info.twig`).

---

### Module 2: Privacy Policy (`/{lang}/privacy-policy`)
* **Purpose:** Comprehensive privacy disclosure under GDPR (EU Regulation 2016/679) and regional data protection acts (KVKK No. 6698).
* **Key Sections:**
  1. **Data Controller Identification:** Legal entity name, registered address, Data Protection Officer / Representative contact.
  2. **Categories of Personal Data Collected:** Technical log data (IP, browser), contact form submissions (name, email, phone), user account records.
  3. **Legal Bases & Purposes for Processing:** Contractual fulfillment, statutory obligation, legitimate interests, explicit consent.
  4. **Data Retention Periods:** Server log retention, transaction record archiving rules.
  5. **Data Subject Rights:** Right of access, rectification, erasure (right to be forgotten), restriction of processing, data portability, and right to lodge complaints with supervisory authorities.
  6. **International Data Transfers & Third-Party Processors:** Hosting providers, analytics services, mail transport layers.
* **Controller / Helper Action:** `public function privacy_policy()` or `legal('privacy')` via `legal_helper.php`.
* **Structured Data:** Schema.org `WebPage` with `about: "Privacy Policy"`.

---

### Module 3: Cookie Policy (`/{lang}/cookie-policy`)
* **Purpose:** Transparent disclosure of all first-party and third-party cookies, tracking technologies, and consent mechanisms.
* **Key Sections:**
  1. **What Are Cookies:** Brief definition of session and persistent cookies.
  2. **Classification & Inventory:**
     - *Essential / Strictly Necessary:* Session handling (`PHPSESSID`, CSRF token, authentication state).
     - *Functional / Preference:* Theme selection (`theme` cookie), language preference (`lang`).
     - *Analytics / Performance:* Aggregate visitor metrics and page response monitoring.
  3. **Cookie Lifecycle & Storage:** Duration of cookies stored on user devices.
  4. **User Control & Browser Management:** Step-by-step guidance on modifying cookie settings in Chrome, Firefox, Safari, and Edge.
* **Controller / Helper Action:** `public function cookie_policy()` or `legal('cookies')` via `legal_helper.php`.

---

### Module 4: Terms of Service (`/{lang}/terms-of-service`)
* **Purpose:** Binding contractual terms governing access and use of the platform and digital services.
* **Key Sections:**
  1. **Acceptance of Terms:** User agreement upon browsing and utilizing site capabilities.
  2. **Intellectual Property Rights:** Trademarks, logos, authored articles, photography, software codebase, and copyright ownership.
  3. **Permitted & Prohibited Use:** Anti-scraping provisions, prohibition of automated abuse, security vulnerability probing constraints.
  4. **Limitation of Liability & Disclaimer of Warranties:** Service availability standards ("as is" basis), third-party link disclaimers.
  5. **Termination & Suspension:** Circumstances leading to suspension of user privileges.
  6. **Governing Law & Jurisdiction:** Applicable jurisdiction and venue for legal dispute resolution.
* **Controller / Helper Action:** `public function terms_of_service()` or `legal('terms')` via `legal_helper.php`.

---

### Module 5: Warranty & Return Policy (`/{lang}/return-policy`)
* **Purpose:** Consumer protection disclosures, statutory withdrawal guidelines, and warranty claim procedures.
* **Key Sections:**
  1. **Statutory Right of Withdrawal:** 14-day statutory return period without penalty.
  2. **Return Eligibility Criteria:** Original packaging, unused condition, documentation requirements.
  3. **RMA & Return Request Procedure:** Step-by-step procedure for initiating return requests.
  4. **Refund Processing Timelines:** Payment method reconciliation timelines (bank transfer, credit card).
  5. **Warranty Coverage Terms:** Scope of hardware/product defects covered under manufacturer or merchant warranty.
* **Controller / Helper Action:** `public function return_policy()` or `legal('return')` via `legal_helper.php`.

---

### Module 6: Accessibility Statement (`/{lang}/accessibility`)
* **Purpose:** Formal commitment to digital accessibility in accordance with WCAG 2.1 Level AA and EN 301 549 standards.
* **Key Sections:**
  1. **Conformance Target:** WCAG 2.1 Level AA compliance commitment.
  2. **Accessibility Features Implemented:** Keyboard-only navigation, ARIA landmarks, visible focus rings, color contrast ratios, screen-reader optimized headings.
  3. **Known Limitations & Continuous Improvement:** Ongoing audits and mobile phone-first testing protocols.
  4. **Feedback & Contact Channel:** Direct communication channel for accessibility barrier reporting.
* **Controller / Helper Action:** `public function accessibility()` or `legal('accessibility')` via `legal_helper.php`.

---

### Module 7: Security Policy & Vulnerability Disclosure (`/{lang}/security-policy`)
* **Purpose:** Security architecture disclosure and responsible vulnerability disclosure framework.
* **Key Sections:**
  1. **Security Measures & Protocols:** SSL/TLS encryption, CSRF protection, salted password hashing, Role-Based Access Control (ACL).
  2. **Responsible Disclosure Program (Bug Bounty / Security Inquiries):** Reporting guidelines for security researchers.
  3. **Data Incident Response Commitment:** Breach notification procedures under applicable legislation.
* **Controller / Helper Action:** `public function security_policy()` or `legal('security')` via `legal_helper.php`.

---

### Module 8: Corporate Compliance & Code of Ethics (`/{lang}/compliance`)
* **Purpose:** Institutional governance, ethical standards, anti-corruption policies, and corporate responsibility.
* **Key Sections:**
  1. **Ethical Business Conduct:** Zero tolerance for bribery, anti-corruption compliance.
  2. **Fair Competition & Anti-Trust:** Compliance with market competition regulations.
  3. **Diversity & Non-Discrimination:** Equal opportunity and respectful workplace standards.
  4. **Whistleblower & Reporting Mechanism:** Confidential reporting channels for ethical violations.
* **Controller / Helper Action:** `public function compliance()` or `legal('compliance')` via `legal_helper.php`.

---

## 4. Technical Implementation Blueprint

Whenever a specific policy page from this roadmap is selected for implementation, follow this standardized execution sequence:

```
[Phase 1: Language Dictionaries]
  └── Add structured localization keys to mod/index/lang/{en,ku,ckb,ar,de,fr,sv,tr,zza}.php

[Phase 2: Controller & Helper Integration]
  ├── Update mod/index/helper/legal.helper.php (or dedicated sub-helper)
  └── Map clean canonical endpoint in mod/index/index.php

[Phase 3: Theme Template Implementation]
  ├── Create templates/amida/index/legal/{policy_type}.twig
  └── Maintain 100% parity across active public themes

[Phase 4: Navigation & Footer Wiring]
  └── Add accessible pill/footer link in templates/*/index/footer.twig

[Phase 5: Verification & Documentation]
  ├── Verify mobile-first layout (phone-only viewport)
  ├── Verify WCAG 2.1 AA accessibility & Schema.org JSON-LD
  └── Bump version and record progress in docs/VERSION.md
```
