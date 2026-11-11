# Task Assignment: Reviewer 2 (Schema, Mobile Admin & Public Template Parity)

You are Reviewer 2 for Milestone 3.
Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin
State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/reviewer_m3_2/

Read the following documents:
- /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/ORIGINAL_REQUEST.md
- /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/orchestrator_1/PROJECT.md
- /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/sub_orch_m3/SCOPE.md
- Worker handoff: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/worker_m3_1/handoff.md

Your verification objectives:
1. Verify database schema and view parity:
   - Check `mod/mod_news/mod_news.sql` and `newses.model.php` view logic.
   - Run verification in container if available (`SHOW COLUMNS FROM mod_news`, `SHOW COLUMNS FROM newses`).
2. Verify admin templates (`templates/admin/mod_news/edit.twig`, `add.twig`, `wizard_content.twig`):
   - Phone-only mobile-first layout (AGENTS.md Rule 5).
   - Zero inline styles (`style="..."`) or inline scripts (AGENTS.md Rule 1).
   - Valid localized labels in `lang.mod_news.admin.columns`.
3. Verify public template (`templates/amed/index/mod_news/news.twig`):
   - Hero rendered as `<figure class="detail-hero">` with `alt="{{ d.image_alt|default(d.name) }}"`.
   - `<figcaption class="news-hero-caption">{{ d.image_credit }}</figcaption>` conditionally rendered.
   - Schema.org JSON-LD `ImageObject` includes `caption` and `creditText`.
   - Zero inline styles or scripts (strict code separation).
4. Verify AGENTS.md Rule citations:
   - Confirm Rule 9 is cited in validators (`add.validator.php`, `edit.validator.php`).
5. Render a clear verdict: APPROVE or REQUEST_CHANGES.

Write your report to .agents/teamwork/reviewer_m3_2/handoff.md.
Notify me via send_message when finished.


## 2026-11-11T12:42:23Z
You are Reviewer 2 for Milestone 3 (Schema, Mobile Admin & Public Template Parity).
Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin
State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/reviewer_m3_2/

MANDATORY: You MUST read the original user request at:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/ORIGINAL_REQUEST.md
and your dispatch instructions at:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/reviewer_m3_2/DISPATCH.md
and the milestone scope at:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/sub_orch_m3/SCOPE.md
and the project spec at:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/orchestrator_1/PROJECT.md
and the worker handoff at:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/worker_m3_1/handoff.md

Verify database schema and view parity, admin templates (phone-only mobile-first layout, zero inline CSS/JS), public template (news.twig hero figure, figcaption, JSON-LD ImageObject), and AGENTS.md rule citations.
Render a clear verdict: APPROVE or REQUEST_CHANGES.
Write report to .agents/teamwork/reviewer_m3_2/handoff.md and notify me via send_message when complete.
