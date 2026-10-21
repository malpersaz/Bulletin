# PHP 8.5 Modernization & Security Hardening Report

**Date:** October 21, 2026  
**Target Runtime:** PHP `^8.4 || ^8.5` (Verified on PHP 8.5.4 CLI/NTS with `error_reporting=E_ALL` while preserving Docker PHP 8.4 FPM compatibility)

---

## 1. Overview & Motivation

To protect the repository against emerging PHP security vulnerabilities, eliminate legacy attack surfaces, and guarantee zero compile-time or runtime deprecation notices on **PHP 8.5**, a full-repository static and runtime compliance audit was executed across all PHP source files.

Key objectives of this modernization pass:
1. Enforce PHP `^8.4 || ^8.5` at the package manager level (`composer.json` and `composer.lock`) so both PHP 8.5 CLI/production and PHP 8.4 Docker FPM environments pass Composer platform checks.
2. Eliminate legacy third-party code containing exposed `phpinfo()`, `eval()`, and removed PHP 4/5/7 constructs.
3. Resolve all PHP 8.2–8.5 compile-time and runtime deprecations (`curl_close()`, `imagedestroy()`, `ReflectionProperty::setAccessible()`, implicitly nullable parameter types, `E_USER_ERROR`, callback-style `session_set_save_handler()`, `fputcsv()` missing `$escape`, `ctype_digit()` on integers, and dynamic properties on `BaseModuleController`).

---

## 2. Summary of Changes by Category

### 2.1. Composer Platform Requirement (`^8.4 || ^8.5`)
- **Why:** Ensures the environment and dependencies explicitly target modern PHP 8.4/8.5+ standards without breaking Docker (`php:8.4-fpm`) via `vendor/composer/platform_check.php`.
- **Locations:**
  - `composer.json` (Added `"php": "^8.4 || ^8.5"` to `require`)
  - `composer.lock` & `vendor/composer/platform_check.php` (Synchronized lock file hash and platform check)

---

### 2.2. Security Hardening & YAML Modernization
- **Why:** Deep security scanning identified an unused legacy directory (`plugins/htmleditor/tiny_mce/`) containing Moxiecode FileManager and ImageManager plugins from the PHP 4/5 era. These files were unreferenced across `mod/`, `lib/`, and `templates/`, yet contained:
  - Direct `phpinfo()` calls (`CorePlugin.php`), exposing server configuration if hit via HTTP.
  - `eval()` execution in `JSCompressor.php`.
  - Removed functions (`split()`, `ereg()`, `utf8_encode()`, `utf8_decode()`) and PHP 4 reference assignment syntax (`=& new`), causing fatal parse errors on PHP 8+.
- **Locations Removed / Updated:**
  - `plugins/htmleditor/tiny_mce/` (Removed the entire unused legacy directory and all sub-plugins).
  - `lib/Yaml/` & `lib/Yaml.class.php` (Removed the entire unused Symfony 1 YAML directory from 2008).

---

### 2.3. Explicit Nullable Parameter Types (PHP 8.4 / 8.5 Compliance)
- **Why:** Implicitly marking typed parameters as nullable via a `= null` default value without an explicit `?` type prefix triggers a `Deprecated` notice in PHP 8.4 and PHP 8.5.
- **Locations (13 Files in `lib/swift/`):**
  - `lib/swift/classes/Swift/Transport/SpoolTransport.php` (Line 27: `?Swift_Spool $spool = null`)
  - `lib/swift/classes/Swift/Mime/Headers/AbstractHeader.php` (Line 231: `?Swift_Mime_HeaderEncoder $encoder = null`)
  - `lib/swift/classes/Swift/Mime/Headers/ParameterizedHeader.php` (Line 52: `?Swift_Encoder $paramEncoder = null`)
  - `lib/swift/classes/Swift/Mime/ContentEncoder/QpContentEncoder.php` (Line 29: `?Swift_StreamFilter $filter = null`)
  - `lib/swift/classes/Swift/Encoder/QpEncoder.php` (Line 109: `?Swift_StreamFilter $filter = null`)
  - `lib/swift/classes/Swift/KeyCache/DummyKeyCache.php` (Line 60: `?Swift_InputByteStream $writeThrough = null`)
  - `lib/swift/classes/Swift/KeyCache/SimpleKeyCacheInputStream.php` (Line 57: `?Swift_InputByteStream $is = null`)
  - `lib/swift/classes/Swift/KeyCache/ArrayKeyCache.php` (Line 118: `?Swift_InputByteStream $writeThrough = null`)
  - `lib/swift/classes/Swift/KeyCache/DiskKeyCache.php` (Line 149: `?Swift_InputByteStream $writeThrough = null`)
  - `lib/swift/classes/Swift/KeyCache/NullKeyCache.php` (Line 54: `?Swift_InputByteStream $writeThrough = null`)
  - `lib/swift/classes/Swift/Plugins/AntiFloodPlugin.php` (Line 57: `?Swift_Plugins_Sleeper $sleeper = null`)
  - `lib/swift/classes/Swift/Plugins/ThrottlerPlugin.php` (Line 80: `?Swift_Plugins_Sleeper $sleeper = null, ?Swift_Plugins_Timer $timer = null`)
  - `lib/swift/classes/Swift/KeyCache.php` (Line 58: `?Swift_InputByteStream $is = null`)

---

### 2.4. Removal of Deprecated `curl_close()` Calls (PHP 8.5 RFC)
- **Why:** `curl_close()` has been a no-op since PHP 8.0 (as `CurlHandle` objects automatically free resources upon destruction) and is officially **deprecated in PHP 8.5**.
- **Locations (8 Occurrences across 4 Files):**
  - `lib/AIService.class.php` (Lines 111, 453, 457)
  - `mod/mod_user/index.php` (Line 1183)
  - `mod/mod_seo/index.php` (Lines 265, 304)
  - `mod/mod_video_embed/index.php` (Lines 1109, 1117)

---

### 2.5. Removal of Deprecated `imagedestroy()` Calls (PHP 8.5 RFC)
- **Why:** Similar to `curl_close()`, `imagedestroy()` has been a no-op since PHP 8.0 (`GdImage` objects are automatically garbage-collected) and is officially **deprecated in PHP 8.5**. Replaced with `unset()` or `null` assignment.
- **Locations (7 Occurrences across 3 Files):**
  - `mod/mod_settings/index.php` (Lines 2402–2403)
  - `lib/BrandAssetGenerator.class.php` (Lines 399–400, 436–437)
  - `lib/AmedResize.class.php` (Line 217)

---

### 2.6. Removal of `ReflectionProperty::setAccessible()` (PHP 8.5 RFC)
- **Why:** `ReflectionProperty::setAccessible()` has had no effect since PHP 8.1 and is officially **deprecated in PHP 8.5**.
- **Location:**
  - `tests/module_management_runtime_test.php` (Line 138)

---

### 2.7. Elimination of `E_USER_ERROR` & `Session_Db` Modernization
- **Why:**
  1. Triggering or referencing `E_USER_ERROR` is deprecated in PHP 8.4/8.5 in favor of throwing standard exceptions/errors.
  2. Calling `session_set_save_handler()` with 6 individual procedural callbacks is deprecated in favor of implementing `SessionHandlerInterface`.
- **Locations:**
  - `index.php` (Line 75): Removed deprecated `E_USER_ERROR` check from the custom error handler array.
  - `lib/swift/classes/Swift/Transport/EsmtpTransport.php` (Line 233): Replaced `trigger_error('Call to undefined method ' . $method, E_USER_ERROR)` with `throw new BadMethodCallException('Call to undefined method ' . $method)`.
  - `lib/session/Session.db.php`: Updated `Session_Db` to formally implement `SessionHandlerInterface` (`open`, `close`, `read`, `write`, `destroy`, `gc`) and register via `session_set_save_handler($this, true)`, while preserving backward-compatible wrapper methods.

---

### 2.8. Runtime Deprecation Hardening (`fputcsv()` & Dynamic Properties)
- **Why:**
  1. Calling `fputcsv()` without an explicit `$escape` argument emits a runtime `Deprecated` notice in PHP 8.4 and PHP 8.5 (`the $escape parameter must be provided as its default value will change`).
  2. Creating dynamic properties via `BaseModuleController::__set()` without `#[\AllowDynamicProperties]` emits a runtime `Deprecated` notice in PHP 8.2–8.5.
- **Locations:**
  - `mod/mod_newsletter/index.php` (Lines 404, 407): Passed explicit `',', '"', ''` parameters to `fputcsv()`.
  - `lib/BaseModuleController.class.php` (Line 21): Added `#[\AllowDynamicProperties]` attribute to `BaseModuleController`.

---

## 3. Verification Results

- **Static Syntax & Deprecation Lint (`php -d error_reporting=E_ALL -l`):** All PHP files passed with **0 errors and 0 deprecation warnings**.
- **Runtime Test Suite (`php tests/module_management_runtime_test.php`):** All assertions passed without warnings.
- **Adversarial Template & Core Suite (`php tests/twig_template_adversarial_test.php`):** 62 / 62 assertions passed with 0 failures and 0 deprecation notices.
- **Repository Guards:** `code_separation_guard.py`, `module_boundary_guard.py`, and `i18n_guard.py` passed with 0 violations.
