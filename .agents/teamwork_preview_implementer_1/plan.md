# Implementation Plan

1. Analyze and clean `mod/index/index.php`:
   - Remove methods: `product()`, `products()`, `service()`, `services()`, `client()`, `clients()`, `reference()`, `references()`.
   - Remove helper methods: `_latest_products()`, `_latest_services()`, `_latest_clients()`, `_latest_references()`.
   - Remove sitemap methods: `productSitemap()`, `serviceSitemap()`, `referenceSitemap()`, `clientSitemap()`.
   - Remove deleted category model calls: `Load::model('mod_product_category')` and `Load::model('mod_service_category')`.
2. Clean `mod/mod_user/index.php`:
   - Remove quick actions for `mod_product`, `mod_service`, `mod_reference`, `mod_client`.
3. Clean `mod/mod_seo/index.php`:
   - Remove sitemap generation queries for `mod_product`, `mod_service`, `mod_reference`.
4. Clean `mod/mod_slider/index.php`:
   - Remove source module config, label, and toggle updates for `mod_product`.
5. Clean `mod/index/helper/mod_hero_slider.helper.php`:
   - Remove `mod_product` branch, table check, and query joins.
6. Clean `mod/management/index.php`:
   - Remove `mod_product` and `mod_service` from runtime module readiness checks.
7. Update E2E test harness / suites:
   - Wire `StaticAuditor::checkDeprecatedModuleReferences()` into `tests/e2e/suites/Tier0QualityContractsTest.php` or `Tier1FeatureCoverageTest.php`.
8. Verify:
   - Run grep to confirm 0 references in `mod/`.
   - Run `php tests/e2e/run.php` and other test suites.
   - Update `progress.md`.
