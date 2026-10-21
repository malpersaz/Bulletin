# Milestone 1: Core Foundation & Registry Fix — Investigation Report

**Explorer**: Explorer 1  
**Working Directory**: `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/m1_explorer_1`  
**Reference Source**: `/home/malpersaz/Desktop/projects/htdocs/AF/Business`  
**Target Workspace**: `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin`  
**Date**: 2026-08-23  

---

## 1. Executive Summary

In `AF/Bulletin`, bootstrap initialization (`init.php`) fails with a fatal error because `lib/Registry.class.php` is missing while `init.php:65` explicitly requires it (`require_once(lib_dir . 'Registry.class.php');`). Furthermore, the codebase still contains legacy, deprecated classes with Turkish identifiers (`lib/Kayit.class.php` and `lib/Register.class.php`), and `config/*.php` files call deprecated `Registry::ekle(...)` instead of the canonical `Registry::add(...)`.

This report provides the complete file-by-file investigation, exact code replacements, dependency impact analysis (including `lib/Load.class.php` and core library callers), and an actionable, step-by-step implementation and verification plan for the implementation worker.

---

## 2. Itemized Investigation Findings

### 2.1 Missing `lib/Registry.class.php` & Canonical Porting

- **Target Location**: `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/lib/Registry.class.php`
- **Source Reference**: `/home/malpersaz/Desktop/projects/htdocs/AF/Business/lib/Registry.class.php`
- **Current State in AF/Bulletin**: File does NOT exist.
- **Current Failure Output**:
  ```
  Warning: require_once(/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/lib/Registry.class.php): Failed to open stream: No such file or directory in /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/init.php on line 65
  Fatal error: Uncaught Error: Failed opening required '/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/lib/Registry.class.php' in /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/init.php:65
  ```
- **Canonical Implementation**:
  ```php
  <?php
  /**
   * Amed Framework
   * Web: www.akmsoftware.co.uk
   * E-Mail: info@akmsoftware.co.uk
   * User: AKM
   * Date: 09.04.2011 - 14:27
   * File: Registry.class.php
   * Description: Core Registry Container Class (Registry Pattern)
   */
  class Registry {
  	static private $data = [];

  	// Fetch a registered key
  	static public function get($key) {
  		return (isset(self::$data[$key]) ? self::$data[$key]  : NULL);
  	}

  	// Add/Register a new key
  	static public function add($key, $value) {
  		self::$data[$key] = $value;
  	}

  	// Check if key exists
  	static public function has($key) {
      	return isset(self::$data[$key]);
    	}

  	// Delete/Remove a registered key
  	static public function remove($key){
  		unset(self::$data[$key]);
  	}

  	// Update/Reset a registered key
  	static public function set($key, $value){
  		if(self::has($key)){
  			self::remove($key);
  		}
  		self::add($key, $value);
  	}

  	// Dump all registered data for debugging
    	static public function all(){
    		require_once __DIR__ . '/dBug.php';
    		new dBug(self::$data);
    	}
  }
  ```

---

### 2.2 Audit of `config/*.php` Files (`Registry::ekle` -> `Registry::add`)

A complete search across all 12 configuration files in `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/config/` reveals the following exact occurrences to update:

| # | File Path | Line Number(s) | Current Code (`Registry::ekle`) | Required Code (`Registry::add`) |
|---|---|---|---|---|
| 1 | `config/autoload.config.php` | 42 | `Registry::ekle('autoload_config', $autoload);` | `Registry::add('autoload_config', $autoload);` |
| 2 | `config/db.config.php` | 12, 13 | `Registry::ekle('database', 'installed');`<br>`Registry::ekle('db_config', $db);` | `Registry::add('database', 'installed');`<br>`Registry::add('db_config', $db);` |
| 3 | `config/db.config.local.php` | 24, 25 | `Registry::ekle('database', 'installed');`<br>`Registry::ekle('db_config', $db);` | `Registry::add('database', 'installed');`<br>`Registry::add('db_config', $db);` |
| 4 | `config/db.config.example.php` | 111, 113, 115, 117 | `Registry::ekle('database', 'localhost');`<br>`Registry::ekle('database', 'remote');`<br>`//Registry::ekle('database', 'remote');`<br>`Registry::ekle('db_config', $db);` | `Registry::add('database', 'localhost');`<br>`Registry::add('database', 'remote');`<br>`//Registry::add('database', 'remote');`<br>`Registry::add('db_config', $db);` |
| 5 | `config/db.config.sqlite.php` | 88, 90, 92, 94 | `Registry::ekle('database', 'localhost');`<br>`Registry::ekle('database', 'remote');`<br>`//Registry::ekle('database', 'remote');`<br>`Registry::ekle('db_config', $db);` | `Registry::add('database', 'localhost');`<br>`Registry::add('database', 'remote');`<br>`//Registry::add('database', 'remote');`<br>`Registry::add('db_config', $db);` |
| 6 | `config/email.config.php` | 19, 21 | `'ad'=>'Your Site Name'`<br>`Registry::ekle('email_config',$email);` | `'name'=>'Your Site Name'`<br>`Registry::add('email_config',$email);` |
| 7 | `config/email.config.example.php` | 19, 21 | `'ad'=>'Your Site Name'`<br>`Registry::ekle('email_config',$email);` | `'name'=>'Your Site Name'`<br>`Registry::add('email_config',$email);` |
| 8 | `config/i18n.config.php` | 13, 15 | `Registry::ekle('i18n', TRUE);`<br>`Registry::ekle('i18n_bootstrap', array(...));` | `Registry::add('i18n', TRUE);`<br>`Registry::add('i18n_bootstrap', array(...));` |
| 9 | `config/session.config.php` | 14, 15, 16, 17, 18, 20, 21, 22, 24 | `Registry::ekle('session_name', 'AKMCMS');`<br>`#Registry::ekle('session_path', ...);`<br>`Registry::ekle('session_db', FALSE);`<br>`Registry::ekle('session_check_ip', FALSE);`<br>`Registry::ekle('session_check_agent', FALSE);`<br>`Registry::ekle('session_life_time', 60);`<br>`// Registry::ekle('session_time_out',10);`<br>`Registry::ekle('session_active_check', TRUE);`<br>`// Registry::ekle('session_active_time',60);` | `Registry::add('session_name', 'AKMCMS');`<br>`#Registry::add('session_path', ...);`<br>`Registry::add('session_db', FALSE);`<br>`Registry::add('session_check_ip', FALSE);`<br>`Registry::add('session_check_agent', FALSE);`<br>`Registry::add('session_life_time', 60);`<br>`// Registry::add('session_time_out',10);`<br>`Registry::add('session_active_check', TRUE);`<br>`// Registry::add('session_active_time',60);` |
| 10 | `config/site.config.example.php` | 56, 58, 60, 62, 64, 65, 67 | `Registry::ekle('app_template', ...);`<br>`Registry::ekle('admin_template', 'admin');`<br>`Registry::ekle('app_default_mod', 'index');`<br>`Registry::ekle('app_default_function', 'index');`<br>`Registry::ekle('lang', $site['lang']);`<br>`Registry::ekle('default_lang', ...);`<br>`Registry::ekle('site_config', $site);` | `Registry::add('app_template', ...);`<br>`Registry::add('admin_template', 'admin');`<br>`Registry::add('app_default_mod', 'index');`<br>`Registry::add('app_default_function', 'index');`<br>`Registry::add('lang', $site['lang']);`<br>`Registry::add('default_lang', ...);`<br>`Registry::add('site_config', $site);` |
| 11 | `config/site.config.php` | 57, 59, 61, 63, 65, 66, 68 | Already uses `Registry::add(...)`. No change needed. | No change needed. |
| 12 | `config/url.config.php` | 145 | `Registry::ekle('url_config',$url);` | `Registry::add('url_config',$url);` |

---

### 2.3 Legacy Class Deletion (`lib/Kayit.class.php` & `lib/Register.class.php`)

1. **`lib/Kayit.class.php`**:
   - Location: `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/lib/Kayit.class.php` (42 lines)
   - Contains Turkish methods: `getir`, `ekle`, `add`, `varmi`, `sil`, `yenile`, `hepsi`.
   - Usages in active code: 0. (Not autoloaded by `autoload.config.php`, not required by `init.php`).
   - Action: **Delete file**.

2. **`lib/Register.class.php`**:
   - Location: `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/lib/Register.class.php` (51 lines)
   - Contains methods: `get`, `set`, `exists`, `delete`, `renew`, `all`.
   - Usages in active code: 0. (Not autoloaded by `autoload.config.php`, not required by `init.php`).
   - Action: **Delete file**.

3. **Orphan `lib/AdminTemplate.class copy.php`**:
   - Location: `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/lib/AdminTemplate.class copy.php`
   - An old Smarty backup file with invalid spaces in filename and legacy Turkish calls (`Registry::getir`).
   - Action: **Delete file**.

---

### 2.4 Critical Dependency Impact: Autoloader & Core Library Parity

During investigation of `init.php`, we traced execution beyond line 65:
When `init.php` loads `lib/Load.class.php` and executes `Load::libfile($libfile)`, `Load.class.php` in `AF/Bulletin` contains legacy calls to Turkish methods (`Registry::varmi`, `Registry::getir`, `Registry::yenile`, `Registry::ekle`).

Because the canonical `Registry` class only implements `get`, `add`, `has`, `set`, `remove`, `all`, calling `init.php` will trigger fatal errors in `lib/Load.class.php` unless `Load.class.php` is synced with `AF/Business/lib/Load.class.php`.

The same applies to other core libraries loaded during bootstrap:
- `lib/Load.class.php`: Replace `varmi` -> `has`, `getir` -> `get`, `yenile` -> `set`, `ekle` -> `add` (or port canonical file directly from `AF/Business/lib/Load.class.php`).
- `lib/Database/Database.php`: Lines 53, 54, 59 replace `getir` -> `get`, `yenile` -> `set`.
- `lib/Database/Adapter/PDO_MYSQL.php`: Line 29 replace `getir` -> `get`.
- `lib/Database/Adapter/PDO_SQLITE.php`: Line 42 replace `getir` -> `get`.
- `lib/Database/Adapter/PDO_POSTGRESQL.php`: Line 29 replace `getir` -> `get`.
- `lib/Database/Adapter/Mysqli.php`: Line 27 replace `getir` -> `get`.
- `lib/BaseModuleValidator.class.php`: Line 16 replace `getir` -> `get`.
- `lib/InstallationState.php`: Lines 27, 28 replace `getir` -> `get`.
- `lib/ImageService.class.php`: Line 250 replace `getir` -> `get`.
- `lib/Email.class.php`: Line 18 replace `getir` -> `get`.
- `lib/AIService.class.php`: Line 18 replace `varmi`/`getir` -> `has`/`get`.

---

## 3. Worker Implementation Plan

The implementation worker should execute the following sequence:

### Phase 1: Core Registry Porting
1. Copy canonical `Registry.class.php` from `/home/malpersaz/Desktop/projects/htdocs/AF/Business/lib/Registry.class.php` into `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/lib/Registry.class.php`.

### Phase 2: Configuration Standardization (`Registry::ekle` -> `Registry::add`)
2. In `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/config/autoload.config.php`:
   - Replace line 42 `Registry::ekle` with `Registry::add`.
   - Update docblock and section comments to English.
3. In `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/config/db.config.php`:
   - Replace lines 12-13 `Registry::ekle` with `Registry::add`.
4. In `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/config/db.config.local.php`:
   - Replace lines 24-25 `Registry::ekle` with `Registry::add`.
5. In `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/config/db.config.example.php`:
   - Replace lines 111, 113, 115, 117 `Registry::ekle` with `Registry::add`.
6. In `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/config/db.config.sqlite.php`:
   - Replace lines 88, 90, 92, 94 `Registry::ekle` with `Registry::add`.
7. In `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/config/email.config.php` and `config/email.config.example.php`:
   - Change `'ad'` to `'name'` on line 19.
   - Replace line 21 `Registry::ekle` with `Registry::add`.
   - Update docblock comments to English.
8. In `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/config/i18n.config.php`:
   - Replace lines 13, 15 `Registry::ekle` with `Registry::add`.
9. In `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/config/session.config.php`:
   - Replace lines 14, 15, 16, 17, 18, 20, 21, 22, 24 `Registry::ekle` with `Registry::add`.
   - Update comments to English.
10. In `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/config/site.config.example.php`:
    - Replace lines 56, 58, 60, 62, 64, 65, 67 `Registry::ekle` with `Registry::add`.
11. In `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/config/url.config.php`:
    - Replace line 145 `Registry::ekle` with `Registry::add`.

### Phase 3: Core Library Parity Alignment
12. Sync `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/lib/Load.class.php` with `/home/malpersaz/Desktop/projects/htdocs/AF/Business/lib/Load.class.php`.
13. Update `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/lib/Database/Database.php` lines 53-59 to use `Registry::get()` and `Registry::set()`.
14. Update `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/lib/Database/Adapter/*.php` (`PDO_MYSQL.php`, `PDO_SQLITE.php`, `PDO_POSTGRESQL.php`, `Mysqli.php`) to use `Registry::get('db_config')`.
15. Update `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/lib/BaseModuleValidator.class.php`, `lib/InstallationState.php`, `lib/ImageService.class.php`, `lib/Email.class.php`, `lib/AIService.class.php` to use standard `Registry::get` / `Registry::has`.

### Phase 4: Legacy File Deletions
16. Delete `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/lib/Kayit.class.php`.
17. Delete `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/lib/Register.class.php`.
18. Delete `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/lib/AdminTemplate.class copy.php`.

---

## 4. Verification Methods & Commands

The worker and verifiers must execute the following commands in sequence:

### Command 1: Syntax Validation
```bash
# Verify syntax of Registry class and all config files
php -l /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/lib/Registry.class.php
find /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/config -name "*.php" -exec php -l {} \;
```
**Expected Result**: Every file returns `No syntax errors detected in <file>`.

### Command 2: Bootstrap Initialization Test
```bash
cd /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin && php -d display_errors=1 -r "require_once 'init.php'; echo 'BOOTSTRAP_OK' . PHP_EOL;"
```
**Expected Result**:
Output is `BOOTSTRAP_OK` with exit code `0` (zero warnings, zero fatal errors).

### Command 3: Full Registry Functional Unit Test
```bash
cd /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin && php -d display_errors=1 -r "
require_once 'init.php';
// Test Add and Get
Registry::add('m1_test_key', 'm1_value');
assert(Registry::get('m1_test_key') === 'm1_value', 'Failed to get registered key');
// Test Has
assert(Registry::has('m1_test_key') === true, 'Failed has check for existing key');
assert(Registry::has('non_existent_key') === false, 'Failed has check for non-existing key');
// Test Set (overwrite)
Registry::set('m1_test_key', 'm1_updated');
assert(Registry::get('m1_test_key') === 'm1_updated', 'Failed set overwrite');
// Test Remove
Registry::remove('m1_test_key');
assert(Registry::has('m1_test_key') === false, 'Failed remove key');
assert(Registry::get('m1_test_key') === null, 'Failed get on removed key');
echo 'REGISTRY_UNIT_TESTS_PASSED' . PHP_EOL;
"
```
**Expected Result**:
Output is `REGISTRY_UNIT_TESTS_PASSED` with exit code `0`.

### Command 4: Purge Verification
```bash
# Ensure no lingering Kayit.class.php or Register.class.php exist in lib/
ls /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/lib/Kayit.class.php 2>&1
ls /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/lib/Register.class.php 2>&1
# Ensure no active Registry::ekle exists in config/
grep -rn "Registry::ekle" /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/config/
```
**Expected Result**:
`ls` commands report `No such file or directory`. `grep` returns 0 matches.
