# Task Assignment: Explorer 2 (M3 mod_news Schema, Models & Templates)

You are Explorer 2 for Milestone 3 (mod_news Media Credits & Templates).
Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin
State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/explorer_m3_2/

Read the following documents:
- /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/ORIGINAL_REQUEST.md
- /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/orchestrator_1/PROJECT.md
- /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/sub_orch_m3/SCOPE.md
- docs/plan/v1.0.63-kevanger-editorial-pipeline-remediation-and-enhancement-plan.md (Section B5, Phase 3)
- Target files:
  - mod/mod_news/mod_news.sql
  - mod/mod_news/model/* (identify exact model files: mod_news.model.php, newses.model.php, etc.)
  - mod/mod_news/controller/index.php
  - mod/mod_news/validator/add.validator.php
  - mod/mod_news/validator/edit.validator.php
  - templates/amed/index/mod_news/news.twig
  - templates/admin/mod_news/edit.twig
  - templates/admin/mod_news/add.twig

Your objective:
Investigate all `mod_news` schema, model, controller, validator, and template files and detail the exact implementation blueprint for:
1. Adding `image_alt VARCHAR(255) DEFAULT NULL` and `image_credit VARCHAR(255) DEFAULT NULL` to `mod/mod_news/mod_news.sql`.
2. Inspecting and updating models (`mod_news.model.php` and `newses.model.php` view definition) and controllers (`mod/mod_news/controller/index.php`) so `image_alt` and `image_credit` are properly selected, saved, and sanitized.
3. Updating validators (`add.validator.php`, `edit.validator.php`) with rules for `image_alt` and `image_credit` (optional, max length 255).
4. Updating admin templates (`templates/admin/mod_news/add.twig`, `edit.twig`) with phone-only mobile-first inputs for `image_alt` and `image_credit` with appropriate i18n keys and zero inline styles/scripts.
5. Updating public template `templates/amed/index/mod_news/news.twig`:
   - Upgrade `.detail-hero` to `<figure class="detail-hero">`
   - Render `alt="{{ d.image_alt|default(d.name) }}"` on the hero `<img>`
   - Render `<figcaption class="news-hero-caption">{{ d.image_credit }}</figcaption>` when `d.image_credit` is present
   - Update Schema.org JSON-LD `ImageObject` with `caption` and `creditText`
   - Strict code separation: ZERO inline styles (`style="..."`) or inline scripts.
6. Check compatibility with existing quality guards: `code_separation_guard.py`, `i18n_guard.py`, `module_boundary_guard.py`.

Write a complete, structured handoff report to `.agents/teamwork/explorer_m3_2/handoff.md`.
Report back when finished via send_message.


## 2026-11-11T12:10:33Z
[Message] timestamp=2026-11-11T12:10:33Z sender=1cb4034b-124b-423c-b681-70f423565312 priority=MESSAGE_PRIORITY_HIGH content=You are Explorer 2 for Milestone 3 (mod_news Media Credits & Templates).
Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin
State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/explorer_m3_2/
Investigate all mod_news schema, model, controller, validator, and template files...
