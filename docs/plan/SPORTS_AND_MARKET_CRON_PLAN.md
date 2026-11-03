# Production Plan: High-Security & High-Efficiency Sports and Market Data Synchronization Architecture

- **Document Path:** `docs/plan/SPORTS_AND_MARKET_CRON_PLAN.md`
- **Target Version:** `v1.0.21`
- **Target Production Host:** `amedbulten.com` on ALL-INKL KAS (`kasserver.com`)
- **Status:** Implemented / Ready for Production

---

## 1. Executive Summary & Root Cause Analysis

On the Bulletin CMS homepage, the **Super League Standings & Fixtures** widget (`SportsDataService`) and the **Live Market Rates** widget (`MarketDataService`) were displaying static fallback seed data instead of live upstream data.

Empirical investigation identified three root causes:

1. **Dual-Shutdown FastCGI Broken-Pipe Collision (Fatal Process Termination):**
   - Both `SportsDataService::scheduleDeferredRefresh()` and `MarketDataService::scheduleDeferredRefresh()` independently register `register_shutdown_function()` closures when cache files are missing or expired.
   - During homepage rendering (`templates/amed/index/index.twig`), `SportsDataService` registers its shutdown callback first (Block L4.1), followed by `MarketDataService` (Block R3).
   - At request shutdown, the first closure flushes output buffers (`ob_end_flush()`, `flush()`) and invokes `fastcgi_finish_request()`, which closes the FastCGI socket between PHP-FPM and the web server.
   - When the second shutdown closure executes, it unconditionally calls `flush()` on the already-closed FastCGI stream. In PHP-FPM, flushing to a closed FastCGI pipe triggers an immediate process abort before the second service can fetch or write its cache file.
2. **Absence of a Web-Accessible Controller Route for ALL-INKL KAS Cronjobs:**
   - ALL-INKL KAS (`kasserver.com`) executes scheduled tasks via HTTP/HTTPS URL requests (`Use CronJobs to automatically call up a URL at specified times`), supporting optional HTTP Basic Authentication (`HTTP user` / `HTTP password`).
   - Existing CLI runners (`tasks/update_market_rates.php` and `tasks/update_sports_data.php`) live inside `tasks/`, which is blocked by `.htaccess` (`RewriteRule ^(config|lib|vendor|tasks|...) - [F,L]`) with `403 Forbidden` and guarded by `if (PHP_SAPI !== 'cli') exit(1);`.
3. **PHP 8.5 `curl_close()` Deprecation Warnings:**
   - Both `lib/MarketDataService.class.php` (lines 303, 329) and `lib/SportsDataService.class.php` (line 447) call `curl_close()`, which emits deprecation notices under PHP 8.5 (`Function curl_close() is deprecated since 8.5, as it has no effect since PHP 8.0`).

---

## 2. Architectural Goals

### 2.1 High-Level Security Objectives
1. **Zero Web-Root Clutter:** Preserve the strict 12-file production root taxonomy defined in `docs/SERVER.md`. Do not introduce standalone unrouted `.php` files in the document root; keep `tasks/` blocked (`403 Forbidden`) in `.htaccess`.
2. **Controller-Routed Endpoint (`/cron`):** Route all HTTP cron requests through the front controller (`config/url.config.php` -> `mod/index/index.php::cron()` -> `mod/index/helper/cron_manager.helper.php`), matching the architectural pattern established by `/clear-cache`.
3. **Multi-Layer Cryptographic Authentication (Constant-Time):**
   - Support **HTTP Basic Authentication** (`Authorization: Basic ...` / `PHP_AUTH_USER` & `PHP_AUTH_PW`), allowing credentials to be stored inside ALL-INKL KAS *Advanced Settings* (`HTTP user` / `HTTP password`) rather than exposing secrets in plain URLs.
   - Support **Header Token** (`X-Cron-Token`) and **Query Token** (`?token=...`) as secondary/fallback transport mechanisms.
   - Support authenticated **Super Admin sessions** (`group_id === 1` or `mod_settings` edit permission) and local CLI/loopback execution for diagnostics.
   - Validate all secrets strictly via timing-attack-safe `hash_equals()`.
4. **Anti-Abuse Cooldown, Mutex Locking & DoS Protection:**
   - Enforce atomic non-blocking file locks (`refresh.lock` via `flock(LOCK_EX | LOCK_NB)`) so concurrent requests never trigger parallel upstream scrapes.
   - Enforce a minimum cooldown gate (60 seconds for HTTP cron calls) to prevent upstream API rate-limiting even if the cron endpoint is called repeatedly.
   - Emit strict security headers on all cron responses:
     - `Content-Type: application/json; charset=utf-8`
     - `Cache-Control: no-store, no-cache, must-revalidate, max-age=0`
     - `X-Robots-Tag: noindex, nofollow, noarchive`
     - `X-Content-Type-Options: nosniff`
   - Log unauthorized attempts (rate-limited per IP) to `logs/` without leaking paths, stack traces, or environment details.

### 2.2 High-Level Efficiency & Reliability Objectives
1. **Unified Single-Flush Deferred Refresh Coordinator:**
   - Eliminate the dual-shutdown FastCGI crash by introducing a unified background refresh queue across `MarketDataService` and `SportsDataService`.
   - Ensure output buffer flushing (`ob_end_flush()`, `flush()`), `session_write_close()`, and `fastcgi_finish_request()` execute **at most once** per HTTP request lifecycle before sequentially running all queued refresh callbacks inside isolated `try ... catch (\Throwable)` blocks.
2. **Dedicated Granular Cron Endpoints with Adaptive Live-Match Polling (Using 1 or 2 of 3 Available Slots):**
   - **Slot 1 — Market Data (`/cron/market`):** Runs every **5 minutes**, keeping gold, forex, BIST 100, Bitcoin, silver, and Brent oil rates continuously fresh (`< 1ms` TTFB from local JSON cache for all visitors).
   - **Slot 2 — Sports Data (`/cron/sports`):** Runs every **5 minutes** in KAS with **Smart Adaptive Match Detection**:
     - **During Live Matches (`state === 'in'` or kickoff within 15 minutes):** Refreshes on every invocation (every 3–5 minutes) so live scores and match clocks (`CANLI 39'`) stay accurate in real time.
     - **Outside Match Windows (No active live match):** Automatically throttles upstream fetches to once every **15 minutes**, protecting the server from ESPN API rate-limiting while using zero extra cron slots.
   - **Combined Endpoint (`/cron/all` or `/cron`):** Executes both Market refresh and Adaptive Sports refresh in a single call (allowing the entire system to run on just **1 Cronjob slot** if desired).
   - **Remaining Slots:** At least 1–2 slots remain completely free in ALL-INKL KAS.
3. **Clean Telemetry & KAS Email Filter Compatibility:**
   - Return structured, deterministic JSON responses (`{"status":"ok", ...}` on HTTP 200; `{"status":"error", ...}` on HTTP 403/500) so ALL-INKL KAS *Email filter* rules can suppress routine success emails and alert only on failures.
4. **PHP 8.0–8.5+ Cleanliness:**
   - Remove obsolete `curl_close()` calls from `MarketDataService` and `SportsDataService`.

---

## 3. Detailed Implementation Specifications

### 3.1 Configuration Layer (`config/site.config.php` & `config/site.config.example.php`)
Add default cron authentication settings to `config/site.config.php` (overridable via `config/site.config.local.php` or environment variables):
- `$site['cron_user']`: Default `'amed_cron'` (or `getenv('AMED_CRON_USER')`).
- `$site['cron_token']`: High-entropy cryptographic token (or `getenv('AMED_CRON_TOKEN')`).
- Document placeholder keys in `config/site.config.example.php`.

### 3.2 Routing Layer (`config/url.config.php` & `mod/index/index.php`)
1. In `config/url.config.php`, register English-only canonical routes:
   ```php
   // Automated Cron & Background Feed Refresh Routes
   $url['cron'] = 'index/cron';
   $url['cron/'] = 'index/cron';
   $url['cron/(:any)'] = 'index/cron/$1';
   ```
2. In `mod/index/index.php`, add the controller action:
   ```php
   public function cron($target = null)
   {
       $cron = Load::helper("index.cron_manager");
       $cron->render($target);
       unset($cron);
   }
   ```
3. Ensure `.htaccess` passes `Authorization` headers (`HTTP_AUTHORIZATION` / `REDIRECT_HTTP_AUTHORIZATION`) cleanly under Apache/FastCGI on ALL-INKL KAS:
   ```apache
   RewriteCond %{HTTP:Authorization} .+
   RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
   ```
   This guarantees PHP-FPM under Apache receives HTTP Basic Auth (`HTTP user` / `HTTP password` from KAS *Advanced Settings*) and `X-Cron-Token` headers reliably.

### 3.3 Cron Manager Helper (`mod/index/helper/cron_manager.helper.php`)
Create `mod/index/helper/cron_manager.helper.php` extending `indexBase`:
1. **Authentication Resolution (`isAuthorized()`):**
   - Extract expected `$cronUser` and `$cronToken` from `$this->site['cron_user']` and `$this->site['cron_token']`.
   - Check HTTP Basic Auth (`$_SERVER['PHP_AUTH_USER']` / `$_SERVER['PHP_AUTH_PW']`, plus base64 decoding of `$_SERVER['HTTP_AUTHORIZATION']` / `$_SERVER['REDIRECT_HTTP_AUTHORIZATION']` for FastCGI compatibility) using `hash_equals()`.
   - Check `$_SERVER['HTTP_X_CRON_TOKEN']` and `$_GET['token']` using `hash_equals()`.
   - Check active admin session (`group_id === 1` or `Role::hasPermission('mod_settings', 'edit')`) as an administrative fallback.
2. **Target Dispatch (`market`, `sports`, `all`):**
   - Normalize `$target` (`market`, `sports`, or default `all`).
   - Respect a minimum 60-second anti-abuse window for HTTP token calls while passing `$force = true` once the window has elapsed (so a 5-minute or 15-minute KAS cron always forces a fresh upstream pull).
   - Measure execution latency in milliseconds (`duration_ms`).
   - Clear any buffered whitespace (`if (ob_get_length()) { @ob_clean(); }`), set `header('Content-Type: application/json; charset=utf-8', true)`, output structured JSON, and terminate cleanly (`exit`) before Twig template rendering.

### 3.4 Unified Deferred Refresh & PHP 8.5 Fixes (`lib/MarketDataService.class.php` & `lib/SportsDataService.class.php`)
1. **Shared FastCGI Finish Guard:**
   - Track FastCGI request finalization via `$GLOBALS['__amed_fastcgi_finished']` (or a unified static queue `$GLOBALS['__amed_deferred_tasks']`).
   - When either `MarketDataService::scheduleDeferredRefresh()` or `SportsDataService::scheduleDeferredRefresh()` is invoked:
     - Append the service callback to `$GLOBALS['__amed_deferred_tasks']`.
     - Register a **single** master shutdown handler (only once per request) that:
       1. Flushes active output buffers (`while (ob_get_level() > 0) { @ob_end_flush(); } flush();`).
       2. Closes active PHP session (`if (session_status() === PHP_SESSION_ACTIVE) { @session_write_close(); }`).
       3. Calls `fastcgi_finish_request()` **once** and sets `$GLOBALS['__amed_fastcgi_finished'] = true`.
       4. Iterates through all queued refresh callbacks inside individual `try { ... } catch (\Throwable $e)` blocks.
2. **Remove Deprecated `curl_close()`:**
   - Remove `curl_close($singleHandle)` in `lib/MarketDataService.class.php` (lines 303 and 329) and `lib/SportsDataService.class.php` (line 447), as `CurlHandle` objects are automatically closed upon scope exit or `curl_multi_remove_handle()` in PHP 8.0+.
3. **Cache Manager Integration (`mod/index/helper/cache_manager.helper.php`):**
   - Preserve `refresh.lock` in `$ignoredNames` so lockfile permissions are never disrupted during cache purges.
   - Clear `RedisCache` keys (`bulletin_market_rates_v1` and `bulletin_super_league_v1`) when `market`, `sports`, or `all` caches are purged.

---

## 4. ALL-INKL KAS (`kasserver.com`) Setup Specification

Once deployed, the two Cronjobs are configured in the ALL-INKL KAS English panel (`Tools` -> `Cronjobs` -> `Add a cronjob`) as follows:

### 4.1 Cronjob 1 — Market Rates (Every 5 Minutes)
| Field | Value |
| :--- | :--- |
| **Log / Path** | `https://` -> `amedbulten.com/cron/market` |
| **Comment** | `Bulletin Market Rates Refresh (5m)` |
| **Execution interval** | Select **Every minute** -> `5` |
| **HTTP user** *(Advanced Settings)* | `amed_cron` |
| **HTTP password** *(Advanced Settings)* | `<configured_cron_token>` |
| **Email address** | *(Leave empty, or set admin email with Email filter: `"status":"error"`)* |
| **Cronjob is active** | `Yes` |

### 4.2 Cronjob 2 — Super League Sports Data (Adaptive 5m Live / 15m Idle)
| Field | Value |
| :--- | :--- |
| **Log / Path** | `https://` -> `amedbulten.com/cron/sports` |
| **Comment** | `Bulletin Super League Refresh (Adaptive)` |
| **Execution interval** | Select **Every minute** -> `5` *(automatically throttles to 15m when no match is live)* |
| **HTTP user** *(Advanced Settings)* | `amed_cron` |
| **HTTP password** *(Advanced Settings)* | `<configured_cron_token>` |
| **Email address** | *(Leave empty, or set admin email with Email filter: `"status":"error"`)* |
| **Cronjob is active** | `Yes` |

*(Note: If preferred, `?token=<configured_cron_token>` can also be appended directly to `Log / Path` instead of using `HTTP user` / `HTTP password` — both methods are supported simultaneously).*

---

## 5. Verification & Quality Gate Checklist

1. **Syntax Validation (`php -l`):**
   - Validate `lib/MarketDataService.class.php`, `lib/SportsDataService.class.php`, `mod/index/index.php`, `mod/index/helper/cron_manager.helper.php`, `mod/index/helper/cache_manager.helper.php`, `config/url.config.php`, `config/site.config.php`.
2. **Security Verification:**
   - Unauthenticated request `curl -i http://localhost:8080/cron/market` -> Must return `HTTP 403 Forbidden` with JSON error and `X-Robots-Tag: noindex, nofollow`.
   - Invalid token `curl -i "http://localhost:8080/cron/market?token=wrong"` -> Must return `HTTP 403 Forbidden`.
   - Valid Basic Auth `curl -i -u "amed_cron:<token>" http://localhost:8080/cron/market` -> Must return `HTTP 200 OK` and refresh `cache/market/market_rates.json`.
   - Valid Query Token `curl -i "http://localhost:8080/cron/sports?token=<token>"` -> Must return `HTTP 200 OK` and refresh `cache/sports/super_league.json`.
3. **Self-Healing Dual-Shutdown Verification:**
   - Remove both `cache/market/market_rates.json` and `cache/sports/super_league.json`.
   - Request homepage `curl -s http://localhost:8080/tr/ > /dev/null`.
   - Verify that **both** `market_rates.json` and `super_league.json` are generated in the background within 4 seconds without any FastCGI broken-pipe crash or PHP 8.5 deprecation notice.
4. **Repository Guards:**
   - Run `python3 tools/code_separation_guard.py` and `python3 tools/module_boundary_guard.py`.
