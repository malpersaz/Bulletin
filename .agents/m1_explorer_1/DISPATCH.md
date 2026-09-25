## 2026-08-23T19:26:56Z
You are Explorer 1 for Milestone 1 (Core Foundation & Registry Fix).
Your working directory is /home/malpersaz/Desktop/projects/htdocs/AF/Rojname/.agents/m1_explorer_1.
Task specification: /home/malpersaz/Desktop/projects/htdocs/AF/Rojname/.agents/ORIGINAL_REQUEST.md.
Project blueprint: /home/malpersaz/Desktop/projects/htdocs/AF/Rojname/PROJECT.md.
Reference source: /home/malpersaz/Desktop/projects/htdocs/AF/Business
Target workspace: /home/malpersaz/Desktop/projects/htdocs/AF/Rojname

Scope:
Investigate the exact steps for:
1. Porting canonical `lib/Registry.class.php` from AF/Business to AF/Rojname.
2. Replacing all `Registry::ekle(...)` calls with `Registry::add(...)` in `config/*.php`.
3. Deleting legacy `lib/Kayit.class.php` and `lib/Register.class.php`.
4. Defining exact worker implementation plan and validation command (`php -r "require_once 'init.php';"`).

DO NOT write source code. Write your findings to /home/malpersaz/Desktop/projects/htdocs/AF/Rojname/.agents/m1_explorer_1/report.md and send completion message.
