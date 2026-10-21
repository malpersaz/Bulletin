# Briefing - SWE Light Reviewer (Round 3)

## Task Objective
Perform deep adversarial review of AF/Bulletin codebase following the complete removal of deprecated commercial modules (`mod_client`, `mod_service`, `mod_product`, `mod_reference` and category variants).

## Key Checkpoints
1. Static code audit across `mod/`, `templates/`, `installer/`, `lib/`, `config/` for any deprecated module traces.
2. Verification of dead routes, dead sitemaps, dead helper methods, dead admin quick actions, dead slider joins/queries.
3. Quality guard validation (StaticAuditor, comment-guard, i18n-guard, Tier0QualityContractsTest).
4. Comprehensive test execution across unit, E2E, adversarial simulators, and linter suites.
5. Identify and eliminate any edge-case oversights, latent regressions, or incomplete test coverage.
