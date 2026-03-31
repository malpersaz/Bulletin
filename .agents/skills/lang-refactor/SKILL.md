---
name: lang-refactor
description: Refactor repository language keys by replacing numeric or non-English keys in en.php files with descriptive English keys and propagating updates safely across the codebase.
---

# Language Key Refactoring Skill

This skill provides a systematic approach to refactoring language keys across the repository, specifically focusing on the English (`en.php`) language layer as the primary reference.

## Objective
Standardize all `en.php` files by replacing numeric and non-English keys with descriptive English keys while ensuring system-wide functional integrity.

## Target Files (31 Modules)
1.  `mod/mod_user/lang/en.php`
2.  `mod/mod_news/lang/en.php`
3.  `mod/mod_page/lang/en.php`
4.  `mod/mod_content/lang/en.php`
5.  `mod/mod_user_pm/lang/en.php`
6.  `mod/lang/lang/en.php`
7.  `mod/mod_popup/lang/en.php`
8.  `mod/mod_survey/lang/en.php`
9.  `mod/mod_publication/lang/en.php`
10. `mod/mod_gallery_mod/lang/en.php`
11. `mod/mod_news_category/lang/en.php`
12. `mod/file/lang/en.php`
13. `mod/management/lang/en.php`
14. `mod/mod_slider/lang/en.php`
15. `mod/mod_attachment_mod/lang/en.php`
16. `mod/mod_document/lang/en.php`
17. `mod/mod_publication_category/lang/en.php`
18. `mod/mod_video/lang/en.php`
19. `mod/mod_gallery/lang/en.php`
20. `mod/mod_user_group/lang/en.php`
21. `mod/mod_menu/lang/en.php`
22. `mod/mod_video_embed_category/lang/en.php`
23. `mod/mod_announcements/lang/en.php`
24. `mod/index/lang/en.php`
25. `mod/mod_category/lang/en.php`
26. `mod/mod_attachment/lang/en.php`
27. `mod/mod_settings/lang/en.php`
28. `mod/mod_headline/lang/en.php`
29. `mod/mod_acl/lang/en.php`
30. `mod/mod_imagecategories/lang/en.php`
31. `mod/mod_video_embed/lang/en.php`

## Procedures
1.  **Mapping Definition:** Identify numeric/Turkish keys in the target `en.php` and define their English counterparts.
2.  **Tool Execution:** Use `refactor_keys.php` to perform the replacement in language files and all templates/controllers.
3.  **Nested Key Support:** Ensure the script handles nested keys (e.g., `admin.1` mapped to `$lang['module']['admin']['1']`).
4.  **Verification:** Use `grep` to ensure no traces of old keys remain in the module's scope.
