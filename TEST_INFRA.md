# E2E Test Infra: AF/Bulletin Modernization

## Test Philosophy
- Opaque-box, requirement-driven. No dependency on implementation internals.
- Verification Methodology: Category-Partition + Boundary Value Analysis + Pairwise Combinatorial + Real-World Workload Testing.

## Feature Inventory
| # | Feature | Source | Tier 1 | Tier 2 | Tier 3 | Tier 4 |
|---|---------|--------|:------:|:------:|:------:|:------:|
| 1 | Twig Unicode SEO Filter (`legacySeoFilter`) | R2 | 5 | 5 | ✓ | ✓ |
| 2 | ORM Case-Insensitive Columns & Exceptions | R2 | 5 | 5 | ✓ | ✓ |
| 3 | BaseModuleController AJAX Relation Endpoints | R2 | 5 | 5 | ✓ | ✓ |
| 4 | Admin User ACL Modernization (`acl.twig`) | R1 | 5 | 5 | ✓ | ✓ |
| 5 | Asynchronous `toggle_permission` AJAX Endpoint | R1 | 5 | 5 | ✓ | ✓ |
| 6 | Modernized `mod_acl` & `AclManager` | R1 | 5 | 5 | ✓ | ✓ |
| 7 | Admin Partials Code Separation (`data-action`) | R1 | 5 | 5 | ✓ | ✓ |
| 8 | Reusable `share_toolbar.twig` & Assets | R3 | 5 | 5 | ✓ | ✓ |
| 9 | Compact `share.twig` Modernization | R3 | 5 | 5 | ✓ | ✓ |
| 10 | Detail Views Share Toolbar Integration | R3 | 5 | 5 | ✓ | ✓ |
| 11 | Document Export & Tools Cleanliness | R3 | 5 | 5 | ✓ | ✓ |
| 12 | Corporate & Fiscal Settings Schema | R4 | 5 | 5 | ✓ | ✓ |
| 13 | Multilingual Legal & Cookie Page Fixtures | R4 | 5 | 5 | ✓ | ✓ |
| 14 | E-E-A-T Frontend Components (`_legal_info.twig`) | R4 | 5 | 5 | ✓ | ✓ |
| 15 | Sorani Kurdish Display Normalization (`سۆرانی`) | R4 | 5 | 5 | ✓ | ✓ |
| 16 | Brand Placeholder Fallback Hierarchy | R5 | 5 | 5 | ✓ | ✓ |
| 17 | Backend Disk Asset Verification (`file_exists`) | R5 | 5 | 5 | ✓ | ✓ |
| 18 | Client-Side Broken Image Interception | R5 | 5 | 5 | ✓ | ✓ |
| 19 | 9-Language Module Dictionaries Parity | R6 | 5 | 5 | ✓ | ✓ |
| 20 | Code Standards & Action Verbs (`Lêke`) | R6 | 5 | 5 | ✓ | ✓ |
| 21 | Cleanliness & Commercial Module Exclusion | Exclusions | 5 | 5 | ✓ | ✓ |

## Test Architecture
- Test Runner: `tests/e2e/run.php`
- Quality Guards:
  - `python3 tools/code_separation_guard.py` (0 violations)
  - `python3 tools/i18n_guard.py` (0 violations)
  - `find . -name "*.php" ! -path "./vendor/*" -exec php -l {} +` (0 errors)
  - `node --check <js_files>` (0 errors)
  - Excluded module scanner (zero occurrences of `mod_client`, `mod_service`, `mod_product`, `mod_reference`)
  - Kurdish Unicode SEO assertion suite

## Coverage Thresholds
- Tier 1: Feature Coverage (≥5 test cases per feature = 105 tests)
- Tier 2: Boundary & Corner Cases (≥5 test cases per feature = 105 tests)
- Tier 3: Cross-Feature Combinations (pairwise interactions across modules)
- Tier 4: Real-World Scenarios (Full E2E workflows: admin ACL toggle, legal page rendering, social sharing, image fallbacks)
