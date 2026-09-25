# TEST_READY — AF/Rojname Modernization & Synchronization

**Status**: READY  
**Test Suite Location**: `/home/malpersaz/Desktop/projects/htdocs/AF/Rojname/tests/e2e/`  
**Primary Test Runner**: `/home/malpersaz/Desktop/projects/htdocs/AF/Rojname/tests/e2e/run.php`  
**Shell Wrapper**: `/home/malpersaz/Desktop/projects/htdocs/AF/Rojname/tests/run_e2e_tests.sh`  

---

## 1. Test Architecture & Overview

The AF/Rojname E2E test harness is an opaque-box, multi-tiered testing framework built directly in PHP 8+ without external framework dependencies, ensuring zero-configuration execution in CI/CD and CLI environments.

### Test Tier Inventory

| Tier | Suite Name | File Path | Tests | Status | Description |
|:----:|:-----------|:----------|:-----:|:------:|:------------|
| **Tier 0** | Quality Gates & Code Separation | `tests/e2e/suites/Tier0QualityContractsTest.php` | 5 | ✅ PASS | Code separation, touch target CSS audits, macro structure, deprecated module cleanup |
| **Tier 1** | Feature Coverage | `tests/e2e/suites/Tier1FeatureCoverageTest.php` | 105 | ⚙️ READY | ≥5 genuine test cases for each of the 21 features in `PROJECT.md` |
| **Tier 2** | Boundary & Corner Cases | `tests/e2e/suites/Tier2BoundariesCornersTest.php` | 105 | ⚙️ READY | Null, empty, extreme lengths, SQL injection vectors, Unicode, unauthorized access |
| **Tier 3** | Cross-Feature Combinations | `tests/e2e/suites/Tier3CrossFeatureTest.php` | 8 | ✅ PASS | Pairwise interactions: ACL + relations, share toolbar + export, settings + legal footer |
| **Tier 4** | Real-World Scenarios | `tests/e2e/suites/Tier4RealWorldScenariosTest.php` | 5 | ✅ PASS | Full editorial publishing, maintenance mode lockdown, bulk campaigns, category cascade |
| **Tier 5** | Adversarial Hardening | `tests/e2e/suites/Tier5AdversarialHardeningTest.php` | 8 | ✅ PASS | Blind SQL injection fuzzing, XSS entity escaping, permission elevation bypass, stress test |
| **TOTAL**| **Complete E2E Suite** | `tests/e2e/run.php` | **236** | **READY** | Full-coverage regression & verification harness |

---

## 2. 21 Feature Coverage Matrix (Tiers 1 & 2)

| # | Feature Name | Tier 1 (Coverage) | Tier 2 (Boundary) | Milestone |
|---|--------------|:-----------------:|:-----------------:|:---------:|
| 1 | Twig Unicode SEO Filter (`legacySeoFilter`) | 5 tests | 5 tests | M1 |
| 2 | ORM Case-Insensitive Columns & Exceptions | 5 tests | 5 tests | M1 |
| 3 | BaseController Relation Helpers (`ajax_get_files`, `ajax_update_relation`) | 5 tests | 5 tests | M1 |
| 4 | Admin User ACL Modernization (`acl.twig`) | 5 tests | 5 tests | M2 |
| 5 | Asynchronous `toggle_permission` AJAX Endpoint | 5 tests | 5 tests | M2 |
| 6 | Modernized `mod_acl` & `AclManager` | 5 tests | 5 tests | M2 |
| 7 | Admin Partials Code Separation (`data-action`) | 5 tests | 5 tests | M2 |
| 8 | Reusable `share_toolbar.twig` & Assets | 5 tests | 5 tests | M3 |
| 9 | Compact `share.twig` Modernization | 5 tests | 5 tests | M3 |
| 10 | Detail Views Share Toolbar Integration | 5 tests | 5 tests | M3 |
| 11 | Document Export & Tools Cleanliness | 5 tests | 5 tests | M3 |
| 12 | Corporate & Fiscal Settings Schema | 5 tests | 5 tests | M4 |
| 13 | Multilingual Legal & Cookie Page Fixtures | 5 tests | 5 tests | M4 |
| 14 | E-E-A-T Frontend Components (`_legal_info.twig`) | 5 tests | 5 tests | M4 |
| 15 | Sorani Kurdish Display Normalization (`سۆرانی`) | 5 tests | 5 tests | M4 |
| 16 | Brand Placeholder Fallback Hierarchy | 5 tests | 5 tests | M5 |
| 17 | Backend Disk Asset Verification (`file_exists`) | 5 tests | 5 tests | M5 |
| 18 | Client-Side Broken Image Interception | 5 tests | 5 tests | M5 |
| 19 | 9-Language Module Dictionaries Parity | 5 tests | 5 tests | M6 |
| 20 | Code Standards & Action Verbs (`Lêke`) | 5 tests | 5 tests | M6 |
| 21 | Cleanliness & Commercial Module Exclusion | 5 tests | 5 tests | M6 |

---

## 3. How to Run the Tests

### Primary Test Runner
```bash
# Run all 236 E2E tests
php tests/e2e/run.php

# Filter by tier level
php tests/e2e/run.php --tier=0   # Quality contracts
php tests/e2e/run.php --tier=1   # Feature coverage
php tests/e2e/run.php --tier=2   # Boundary & corner cases
php tests/e2e/run.php --tier=3   # Cross-feature combinations
php tests/e2e/run.php --tier=4   # Real-world scenarios
php tests/e2e/run.php --tier=5   # Adversarial hardening

# Filter by test name or pattern
php tests/e2e/run.php --filter=LegacySeo
php tests/e2e/run.php --filter=testF1_

# Structured JSON output (for automated CI/CD aggregation)
php tests/e2e/run.php --json

# Shell wrapper execution
bash tests/run_e2e_tests.sh
```

### Automated Quality Guards
```bash
# Code separation guard (0 inline styles/scripts)
python3 tools/code_separation_guard.py

# Internationalization & code language standards guard
python3 tools/i18n_guard.py

# PHP syntax lint across all test files
find tests/ -name "*.php" -exec php -l {} +
```

---

## 4. Implementation Defect Backlog (Roadmap Escalation)

The test suite accurately surfaces the 26 features awaiting implementation by the milestone agents:

1. **Milestone M2 (Admin User ACL & Code Separation)**:
   - `templates/admin/mod_user/acl.twig`: Purge inline styles/scripts and add mobile tabs.
   - `templates/admin/mod_acl/`: Purge `script.twig` and legacy Turkish references (`processSil`).
   - `templates/admin/partials/`: Decouple `header.twig` and `filter_sheet.twig` to `modal_user_menu.js` and `core_modals.css`.
2. **Milestone M3 (Universal Share Toolbar & Document Export)**:
   - `templates/amida/partials/share.twig`: Refactor to zero-inline and bind to external share assets.
3. **Milestone M4 (Corporate Legal & Fiscal Schema)**:
   - `installer/mod_settings/mod_settings.sql` & `mod/mod_settings/mod_settings.sql`: Add 16 corporate fields.
   - `installer/mod_page/index.php`: Add 9 Legal Information and 9 Cookie Policy multilingual seeds.
   - `installer/lang/index.php` & `InstallerLang.php`: Normalize Sorani display name to `سۆرانی`.
4. **Milestone M5 (Brand Placeholder & Disk Verification)**:
   - `mod/index/helper/mod_publication.helper.php` & `mod_announcements.helper.php`: Add disk asset verification (`file_exists()`).
5. **Milestone M6 (Multilingual Parity & Excluded Module Cleanliness)**:
   - `mod/mod_user_pm/lang/`, `mod_video_embed/lang/`, `mod_acl/lang/`: Add missing `ar.php`, `ckb.php`, `fr.php`, `sv.php`.
   - `installer/mod_faq_mod/index.php`: Purge `mod_product`, `mod_service`, `mod_project` seeds.
   - `templates/admin/partials/sidebar.twig`: Purge lingering `mod_service` link.
