# Bulletin CMS Production Server Guide (SERVER.md)

- **Version:** `v1.0.20` (2026-11-03)
- **Domain:** `amedbulten.com`
- **Host:** `ssh-w02232db@w02232db.kasserver.com` (ALL-INKL KAS)
- **Document Root:** `/www/htdocs/w02232db/amedbulten.com/`

---

## 1. Release Overview (v1.0.20)

In version **v1.0.20**, Bulletin CMS underwent extensive security hardening, web root clutter elimination, and framework route modernization:
1. **Controller-Routed Cache Clearing (`/clear-cache`):** Retired the legacy standalone root script `clear_cache.php` in favor of a secure, authenticated controller route (`/clear-cache`) routed through `mod/index/index.php` with CSRF protection and role-based ACL validation.
2. **Retirement of Legacy Root Scripts:** Permanently removed unauthenticated scripts `clear_cache.php` and `modules.php` from both local tracking and remote production server.
3. **Multi-Layer Apache Security Shields:** Deployed directory-level `.htaccess` protection across `logs/`, `templates_c/`, `cache/`, and `static/`, preventing script execution and unauthorized file access. Reinforced root `.htaccess` with rewrite blocks denying direct HTTP requests to internal directories (`config`, `lib`, `vendor`, `tasks`, `data`, `logs`, `tools`, `tests`, `installer`, `docs`, `templates_c`).
4. **Information Disclosure Prevention:** Gated verbose PHP `display_errors` and stack trace rendering in `index.php` behind local environment detection (`$isLocal`), serving generic 500 error pages on production while logging traces securely.
5. **Strict File Permissions & Safe Deployment:** Upgraded `tools/deploy_production.sh` with automated remote pruning (`--delete`), POSIX permission sanitization (`--chmod=Du=rwx,Dg=rx,Do=rx,Fu=rw,Fg=r,Fo=r`), and post-deploy credential hardening (`chmod 640` on sensitive configs).

---

## 2. Server Root Directory Taxonomy & Architecture

Only essential runtime directories and entrypoints exist on the production server.

### 2.1 Active Server Directories

```text
/www/htdocs/w02232db/amedbulten.com/
├── cache/          # System and Twig caches (script execution blocked by .htaccess)
├── config/         # System configurations & database credentials (blocked by .htaccess)
├── data/           # Application state and JSON data files (blocked by .htaccess)
├── lib/            # Amed Framework core library classes (blocked by .htaccess)
├── logs/           # Application error and access logs (Require all denied)
├── mod/            # Bulletin modules (mod_news, mod_publication, mod_event, etc.)
├── plugins/        # System plugins (e.g. htmleditor)
├── static/         # Public assets (CSS, JS, fonts, media uploads; script execution blocked)
├── tasks/          # Production cron and background tasks (blocked by .htaccess)
├── templates/      # Twig templates (amed, admin)
├── templates_c/    # Compiled template cache (Require all denied)
└── vendor/         # Composer dependencies (--no-dev production bundle)
```

### 2.2 Active Server Root Files (12 Files)

| File | Server Runtime Role |
| :--- | :--- |
| `.htaccess` | Apache rewrite rules, security shields, caching headers, routing |
| `captcha.php` | Captcha image generator endpoint |
| `css.php` | CSS asset bundling and delivery helper |
| `index.php` | Primary application front controller |
| `init.php` | Core bootstrap and environment initialization |
| `js.php` | JavaScript asset minification and delivery helper |
| `llms.php` | AI/LLM structured content endpoint (`llms.txt`) |
| `mcp.php` | Model Context Protocol (MCP) server integration endpoint |
| `resize.php` | On-demand image processing and responsive WebP thumbnailer |
| `robots.php` | Dynamic crawler directives and XML sitemap generator |
| `robots.txt` | Static search engine indexing rules |
| `template.php` | Template asset loading helper |

---

## 3. Security Hardening & Shield Architecture

### 3.1 Root `.htaccess` Shield
- Blocks all direct requests to hidden files and directories:
  ```apache
  <FilesMatch "^\.">
    Require all denied
  </FilesMatch>
  ```
- Denies direct access to sensitive extensions:
  ```apache
  <FilesMatch "\.(md|markdown|json|lock|yml|yaml|dist|sql|sqlite|log|sh|bak|backup|swp)$">
    Require all denied
  </FilesMatch>
  ```
- Direct directory blocking rewrite rules:
  ```apache
  RewriteRule ^(config|lib|vendor|tasks|data|logs|tools|tests|installer|docs|templates_c)(/|$) - [F,L]
  ```

### 3.2 Directory Shields
- **`logs/.htaccess`**: Enforces `Require all denied` to prevent reading sensitive logs via HTTP.
- **`templates_c/.htaccess`**: Enforces `Require all denied` to block access to compiled template PHP files.
- **`cache/.htaccess`**: Disables directory listing (`Options -Indexes`) and blocks execution of all script extensions (`.php`, `.phtml`, `.phar`, `.sh`, `.py`, `.cgi`).
- **`static/.htaccess`**: Disables script execution in user-uploaded media directories (`<FilesMatch "\.(?i:php[0-9]?|phtml|phar|pl|py|cgi|sh)$"> Require all denied`).

### 3.3 Cache Clearing Modernization (`/clear-cache`)
- Standalone `clear_cache.php` is permanently retired.
- Route `/clear-cache` and `/clear-cache/(:any)` routes through `index/clear_cache`.
- Access requires administrative authentication via `AclService::hasPermission('admin_access')`.
- Supports granular target purging:
  - `/clear-cache/templates`: Clears Twig template cache.
  - `/clear-cache/img`: Clears resized image thumbnail cache.
  - `/clear-cache/js` & `/clear-cache/css`: Clears minified asset caches.
  - `/clear-cache/sql`: Clears database query caches.
  - `/clear-cache/all`: Purges all cache subsystems.

---

## 4. Production Deployment Protocol

Direct `git clone` or `git pull` on the production server is strictly forbidden. Deployments are executed via `tools/deploy_production.sh`.

### 4.1 Deployment Commands

1. **Test Deployment (Dry-Run):**
   ```bash
   bash tools/deploy_production.sh --dry-run
   ```
2. **Execute Live Production Deployment:**
   ```bash
   bash tools/deploy_production.sh
   ```

### 4.2 Automated Safety Guardrails
- **Exclusion Filters (`.deployignore`):** Automatically excludes development tooling (`.git/`, `.agents/`, `.gemini/`, `docs/`, `tests/`, `tools/`, `installer/`, `*.md`, `composer.json`, `package.json`).
- **Data Protection:** Preserves live production assets (`config/*.local.php`, `data/*.json`, `static/mod_*`, active logs, and cache).
- **Remote Pruning (`--delete`):** Automatically purges files removed from the repository from the live server.
- **Permission Normalization:** Enforces `0755` for directories and `0644` for files (`--chmod=Du=rwx,Dg=rx,Do=rx,Fu=rw,Fg=r,Fo=r`), followed by `chmod 640` on credentials.
- **Automated Cache Purge & Health Check:** Flushes Twig caches post-transfer and queries `https://amedbulten.com/` to verify HTTP 200 status.

---

## 5. Maintenance & Remote Troubleshooting Commands

Via SSH (`ssh ssh-w02232db@w02232db.kasserver.com`):

- **Verify Clean Root:**
  ```bash
  ls -la /www/htdocs/w02232db/amedbulten.com/
  ```
- **Manual Cache Purge:**
  ```bash
  rm -rf /www/htdocs/w02232db/amedbulten.com/cache/twig/*
  rm -rf /www/htdocs/w02232db/amedbulten.com/templates_c/*
  ```
- **Inspect Application Logs:**
  ```bash
  tail -n 50 /www/htdocs/w02232db/amedbulten.com/logs/error.log
  tail -n 50 /www/htdocs/w02232db/amedbulten.com/logs/ajax_errors.log
  ```
- **Verify HTTP 403 on Protected Paths:**
  ```bash
  curl -I https://amedbulten.com/config/
  curl -I https://amedbulten.com/logs/
  curl -I https://amedbulten.com/tasks/
  ```
- **Verify Retired Scripts Return 404:**
  ```bash
  curl -I https://amedbulten.com/clear_cache.php
  curl -I https://amedbulten.com/modules.php
  ```

---

## 6. Resolved Defects & Audit History

1. **Database `view_count` Column Deficiency (`mod_page`, `mod_publication`):**
   - Added column `view_count INT(11) NOT NULL DEFAULT 0` and wrapped `_update_view_count()` in `try ... catch` blocks to prevent uncaught PDO exceptions.
2. **Kurmanji Localization Routing Issue (`https://amedbulten.com/ku/`):**
   - Set `status = 'y'` for language code `ku` in the `lang` database table, enabling flawless HTTP 200 responses on Kurdish pages.
3. **Module Alias 404 Remediation (`/news`, `/publication`, `/event`):**
   - Configured route aliases in `config/url.config.php` resolving shortened URLs cleanly.
4. **Information Disclosure & Unauthenticated Cache Stampede (v1.0.20):**
   - Removed unauthenticated `clear_cache.php` and `modules.php`.
   - Routed cache management to authenticated `/clear-cache` controller.
   - Suppressed public PHP error dumps in production.
5. **Dotfile Security Shield & AI Index Discovery (`/.well-known/llms.txt`):**
   - The root `.htaccess` multi-layer security shield (`<FilesMatch "^\."> Require all denied`) and host-level Apache policies deliberately deny direct access to dot-prefixed paths like `/.well-known/` (HTTP 403 Forbidden).
   - Canonical AI/LLM ingestion standard on Bulletin CMS operates via `/llms.txt` and `/llms-full.txt` (HTTP 200 with `X-Robots-Tag: noindex, follow`).
