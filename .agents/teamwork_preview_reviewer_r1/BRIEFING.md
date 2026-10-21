# Teamwork Preview Reviewer Briefing (Round 1)

## Context & Objectives
- **Target Repository**: `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin`
- **Working Directory**: `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork_preview_reviewer_r1`
- **Core Mission**: Adversarial review and verification of Round 0 implementer attempt.
  - Review `lib/Run.class.php` and `lib/Validator.class.php` modernization (`Run::execute()`, `Validator::execute()`, `Validator::add()`).
  - Probe edge cases in argument parsing, types, missing methods, string classes, associative parameter unpacking.
  - Audit all module callers across `mod/` for legacy Turkish methods (`ekle`, `calistir`, `duzenle`, etc.).
  - Enforce zero references to forbidden modules (`mod_client`, `mod_service`, `mod_product`, `mod_reference`).
  - Re-run and pass the complete E2E test suite (`php tests/e2e/run.php`) with 100% success rate (236/236 tests).
