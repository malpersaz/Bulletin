# BRIEFING — 2026-08-23T23:21:00Z

## Mission
Survey AF/Rojname codebase for R4 (Corporate Legal & Tax Info, Cookie Policy, Sorani Kurdish), R6 (Multilingual 9-Language Parity, Code Standards, Kurdish Verbs), and Cleanliness (Strict Exclusion of mod_client, mod_service, mod_product, mod_reference).

## 🔒 My Identity
- Archetype: explorer
- Roles: Codebase & Architecture Explorer (R4, R6 & Cleanliness Focus)
- Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Rojname/.agents/explorer_survey_3
- Original parent: 252c7225-77db-4c3e-bc1d-f37695c6498d
- Milestone: Explorer Survey

## 🔒 Key Constraints
- Read-only investigation — do NOT implement / modify source code
- Investigation report in handoff.md following 5-Component protocol
- Strict separation and isolation rules

## Current Parent
- Conversation ID: 252c7225-77db-4c3e-bc1d-f37695c6498d
- Updated: 2026-08-23T23:21:00Z

## Investigation State
- **Explored paths**:
  - `mod/mod_settings/` (model, index, SQL schemas, lang)
  - `installer/mod_settings/` and `installer/mod_page/`
  - `templates/default/partials/` (`_cookie_consent.twig`, missing `_legal_info.twig`)
  - `templates/default/index/footer.twig` and `templates/default/index/mod_page/page.twig`
  - `installer/lang/` and `installer/app/InstallerLang.php` for Sorani Kurdish naming
  - `mod/mod_user_pm/lang/`, `mod/mod_video_embed/lang/`, `mod/mod_acl/lang/`, `mod/mod_seo/lang/`
  - `installer/mod_faq_mod/index.php`
  - Full codebase grep scan for deprecated module leftovers (`mod_client`, `mod_service`, `mod_product`, `mod_reference`)
- **Key findings**:
  - `mod_settings` SQL schemas in `installer/` and `mod/` lack the 16 corporate/fiscal fields present in the model and admin UI.
  - `installer/mod_page/index.php` lacks all 9-language `Legal Information` and `Cookie Policy` page seeds.
  - `_legal_info.twig` is missing from `templates/default/partials/`; footer and cookie consent lack localized dynamic slug links; `page.twig` lacks the conditional legal info component include.
  - `mod/index/lang/*.php` is missing the legal and cookie policy keys across all 9 languages.
  - Sorani Kurdish is seeded as `Soranî` or labeled `کوردی` instead of the normalized `سۆرانی`.
  - Language dictionaries are missing in `mod_user_pm` (ar, ckb, fr, sv), `mod_video_embed` (ar, ckb, fr), and `mod_acl` (ar, ckb, fr, sv).
  - Legacy Turkish keys (`vazgec`, `hata`, `oke`, `ziyaretci`, `kaldir`, etc.) and `Zêde Bike` instead of `Lêke` persist in `mod_acl` and other modules; `nûve` used instead of `nûjenkirin`.
  - Deprecated modules are mostly deleted from disk, but `installer/mod_faq_mod/index.php` still registers FAQ entries for `mod_product`, `mod_service`, and `mod_project`.
- **Unexplored areas**: None. Full survey complete.

## Key Decisions Made
- Fully documented all gaps, exact file paths, line numbers, and concrete code/SQL blueprints in `handoff.md`.

## Artifact Index
- handoff.md — Comprehensive Survey & Analysis Report for R4, R6, and Cleanliness
- progress.md — Liveness heartbeat and progress tracker
