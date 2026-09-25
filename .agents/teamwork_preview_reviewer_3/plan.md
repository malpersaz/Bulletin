# Review Plan - SWE Light Adversarial Reviewer (Round 3)

## Phase 1: Independent Analysis & Static Code Boundary Verification
- Check all modified files (`mod/index/index.php`, `mod/mod_user/index.php`, `mod/mod_seo/index.php`, `mod/mod_slider/index.php`, `mod/index/helper/mod_hero_slider.helper.php`, `mod/management/index.php`, tests, etc.)
- Run comprehensive grep scans across `mod/`, `templates/`, `installer/`, `lib/`, `config/`, `tests/` for commercial module residues (`mod_client`, `mod_service`, `mod_product`, `mod_reference`, `product_category`, `service_category`, `reference_category`, `productSitemap`, etc.)

## Phase 2: Behavioral & Adversarial Probing
- Check routing in `mod/index/index.php` for unexpected fallbacks or broken route tables.
- Check `mod_seo` sitemap generator to ensure only valid content types and tables are queried.
- Check `mod_slider` and `mod_hero_slider.helper.php` for item binding, image fetching, and SQL queries.
- Check `mod/management/index.php` for module health checks, module lists, database table sync.
- Check test harness & assertions: verify `StaticAuditor.php`, `Tier0QualityContractsTest.php`, and language/fixture consistency.

## Phase 3: Defect Discovery & Remediation
- Identify any gaps or latent defects in edge cases, route fallbacks, or test contracts.
- Implement necessary fixes cleanly.

## Phase 4: Full Suite Re-verification
- Run all test suites: `php tests/e2e/run.php`, adversarial suites, touch target auditor, PHP syntax linter.
- Update progress and publish final adversarial report.
