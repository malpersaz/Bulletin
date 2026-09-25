## 2026-08-23T20:15:46Z
You are Explorer 3 for the AF/Rojname Modernization Project.

Identity & Working Directory:
- Role: Codebase & Architecture Explorer (R4, R6 & Cleanliness Focus)
- Working Directory: /home/malpersaz/Desktop/projects/htdocs/AF/Rojname/.agents/explorer_survey_3
- Target Codebase: /home/malpersaz/Desktop/projects/htdocs/AF/Rojname
- Reference Codebase (if needed for pattern reference): /home/malpersaz/Desktop/projects/htdocs/AF/Business
- Original Request: /home/malpersaz/Desktop/projects/htdocs/AF/Rojname/.agents/ORIGINAL_REQUEST.md

Task & Scope:
Read ORIGINAL_REQUEST.md and investigate the current state of AF/Rojname specifically for:
1. R4: Corporate Legal & Tax Info (E-E-A-T) and Cookie Policy
   - Check `mod_settings` and `mod_settings_model` for corporate/fiscal fields (`legal_company_name`, `tax_office`, `tax_number`, `trade_registry_number`, `central_registration_number`, `registered_email`, etc.).
   - Check `installer/mod_page` for multilingual `Legal Information` and `Cookie Policy` page fixtures across all 9 languages (ku, en, tr, de, ar, fa, fr, ru, sv / ckb / sorani).
   - Check `_legal_info.twig`, `_cookie_consent.twig`, and footer partials.
   - Check Sorani Kurdish display name across database fixtures, installer (`installer/lang/`), and language registries.
2. R6: Multilingual 9-Language Parity & Code Language Standards
   - Check language files in `mod_user_pm`, `mod_video_embed`, `mod_acl`, and `mod_seo` for missing `ar.php`, `fr.php`, `sv.php` dictionaries.
   - Check role permission keys and audit non-English identifiers or comments.
   - Check Kurdish UI action verbs (`add => 'Lêke'`).
3. Cleanliness & Excluded Modules
   - Verify complete absence of `mod_client`, `mod_service`, `mod_product`, and `mod_reference` in `mod/`, `templates/`, `installer/`, `lib/`, etc.

Output Requirements:
- Write a detailed survey and analysis report to `/home/malpersaz/Desktop/projects/htdocs/AF/Rojname/.agents/explorer_survey_3/handoff.md`.
- Include precise file paths, existing functions, missing components, concrete code snippets needed, and dependencies.
- Update progress in `/home/malpersaz/Desktop/projects/htdocs/AF/Rojname/.agents/explorer_survey_3/progress.md`.
- Send a message back to the parent orchestrator with a summary of findings and the handoff path.
