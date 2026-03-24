# German Language Expansion Plan

Last updated: 2026-03-13

## Objective

- Ensure every Amed Framework module under `mod/` ships a `lang/de.php` file.
- Use the existing English translations as the source of truth and produce clean German equivalents.
- Track progress in this plan file so the work can be resumed or audited if needed.

## Module Translation Status

- Status legend:
- `completed`: German file added during this expansion pass.
- `reviewed`: German file already existed and was reviewed in this pass.
- `aligned`: German file already existed and required fixes to match the English source structure in this pass.
- `pending`: no German file added yet.
- `skipped`: module does not use a language array.

- `file`: completed
- `image`: skipped
- `index`: aligned
- `lang`: reviewed
- `management`: aligned
- `mod_acl`: completed
- `mod_announcements`: completed
- `mod_attachment`: completed
- `mod_attachment_mod`: completed
- `mod_category`: pending
- `mod_content`: pending
- `mod_document`: pending
- `mod_gallery`: pending
- `mod_gallery_mod`: pending
- `mod_headline`: pending
- `mod_imagecategories`: pending
- `mod_menu`: pending
- `mod_page`: pending
- `mod_popup`: pending
- `mod_settings`: aligned
- `mod_slider`: pending
- `mod_survey`: pending
- `mod_user`: aligned
- `mod_user_group`: aligned
- `mod_user_pm`: pending
- `mod_video`: pending
- `mod_video_embed`: pending
- `mod_video_embed_category`: pending
- `mods`: pending
- `tools`: pending

## Notes

- Start from the English file for each module, keep the array structure untouched, and replace the string values with German text. Observe existing style (English keys, no untranslated Turkish comments unless they already exist).
- Because this is a large effort, we will update the plan after each module to reflect completion.

## Smoke Check

- Verify login, registration, activation, and forgot-password flows under `mod_user` render German labels, validation messages, and status text.
- Verify admin user management screens render German grid labels, role messages, password helper text, and success/error notifications.
- Verify `mod_settings` renders German content for general settings, language selection, asset upload helpers, and delete confirmations.
- Verify frontend shared UI such as search, login modal, avatar modal, cookie consent, cache management, and pagination render German strings from `index`.
- Verify video, embedded video, announcement, survey, attachment, and gallery screens do not show missing labels, raw keys, or English fallback text.
- Verify management panel menus, including profile actions, message management, administration links, and quick menu entries, render German text.
- Verify `mod_user_group` screens render German general labels such as info, action, success, error, and OK.
- Verify permission-denied, invalid form submission, failed login, and delete-confirmation scenarios do not expose English strings or broken placeholders.

## Pending Priority

- Priority 1: `mod_content`, `mod_category`, `mod_menu`, `mod_page`
- Reason: core content creation and frontend navigation depend on these modules.

- Priority 2: `mod_gallery`, `mod_gallery_mod`, `mod_document`, `mod_headline`, `mod_popup`, `mod_slider`
- Reason: these are prominent content presentation modules and affect visible site sections.

- Priority 3: `mod_video`, `mod_video_embed`, `mod_video_embed_category`, `mod_imagecategories`, `mod_attachment` ecosystem follow-ups
- Reason: media workflows are important, but some surrounding German coverage is already in place.

- Priority 4: `mod_survey`, `mod_user_pm`, `mods`, `tools`
- Reason: secondary or admin-oriented workflows; lower impact than core publishing paths.
