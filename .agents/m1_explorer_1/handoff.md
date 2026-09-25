# Handoff Report — Milestone 1 (Explorer 1)

## 1. Observation
- `AF/Rojname/lib/Registry.class.php` does not exist. `init.php:65` calls `require_once(lib_dir . 'Registry.class.php');`.
- Executing `php -d display_errors=1 -r "require_once 'init.php';"` yields verbatim error:
  `Fatal error: Uncaught Error: Failed opening required '/home/malpersaz/Desktop/projects/htdocs/AF/Rojname/lib/Registry.class.php' in /home/malpersaz/Desktop/projects/htdocs/AF/Rojname/init.php:65`
- Canonical `Registry.class.php` exists in `/home/malpersaz/Desktop/projects/htdocs/AF/Business/lib/Registry.class.php` implementing `get`, `add`, `has`, `remove`, `set`, `all`.
- Legacy classes `lib/Kayit.class.php` (42 lines) and `lib/Register.class.php` (51 lines) exist in `/home/malpersaz/Desktop/projects/htdocs/AF/Rojname/lib/` with Turkish/deprecated methods.
- Legacy calls `Registry::ekle(...)` exist across `config/autoload.config.php:42`, `config/db.config.php:12,13`, `config/db.config.local.php:24,25`, `config/db.config.example.php:111,113,115,117`, `config/db.config.sqlite.php:88,90,92,94`, `config/email.config.php:21`, `config/email.config.example.php:21`, `config/i18n.config.php:13,15`, `config/session.config.php:14,15,16,17,18,20,21,22,24`, `config/site.config.example.php:56,58,60,62,64,65,67`, and `config/url.config.php:145`.
- Core loader `lib/Load.class.php` and DB adapters in `AF/Rojname` still invoke Turkish method names (`varmi`, `getir`, `yenile`) which will throw method not found errors against the canonical `Registry` class unless aligned.

## 2. Logic Chain
1. `init.php:65` expects `Registry.class.php` to define the global container for `autoload.config.php` and subsequent service lookups.
2. Porting `AF/Business/lib/Registry.class.php` creates the canonical class with standard PSR-friendly methods (`get`, `add`, `has`, `remove`, `set`, `all`).
3. Updating all `config/*.php` files from `Registry::ekle` to `Registry::add` aligns configuration bootstrapping with the ported class API.
4. Because `lib/Kayit.class.php` and `lib/Register.class.php` have zero active consumers in the framework, deleting them eliminates dead code and enforces the forbidden terms rule.
5. Updating `lib/Load.class.php` and core `lib/` files to standard `Registry` methods ensures smooth end-to-end execution of `init.php`.

## 3. Caveats
- `installer/` scripts also contain legacy `Registry::ekle` calls (`InstallerConfigWriter.php`, `InstallerLang.php`, `installer/index.php`), which are addressed in Milestone 2.
- `mod_settings` contains legacy Turkish identifiers which are in scope for Milestone 4 sync.

## 4. Conclusion
Milestone 1 Core Foundation investigation is complete. The exact file paths, diff locations, deletion targets, and worker implementation sequence have been fully documented in `.agents/m1_explorer_1/report.md`.

## 5. Verification Method
1. Syntax check:
   `php -l /home/malpersaz/Desktop/projects/htdocs/AF/Rojname/lib/Registry.class.php`
   `find /home/malpersaz/Desktop/projects/htdocs/AF/Rojname/config -name "*.php" -exec php -l {} \;`
2. Bootstrap check:
   `cd /home/malpersaz/Desktop/projects/htdocs/AF/Rojname && php -d display_errors=1 -r "require_once 'init.php'; echo 'BOOTSTRAP_OK' . PHP_EOL;"`
3. Registry functional assertions:
   `cd /home/malpersaz/Desktop/projects/htdocs/AF/Rojname && php -d display_errors=1 -r "require_once 'init.php'; Registry::add('k', 'v'); assert(Registry::get('k')==='v'); assert(Registry::has('k')===true); Registry::remove('k'); assert(Registry::has('k')===false); echo 'OK' . PHP_EOL;"`
4. Purge check:
   `test ! -f /home/malpersaz/Desktop/projects/htdocs/AF/Rojname/lib/Kayit.class.php && test ! -f /home/malpersaz/Desktop/projects/htdocs/AF/Rojname/lib/Register.class.php`
