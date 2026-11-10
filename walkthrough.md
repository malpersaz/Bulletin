# Walkthrough — v1.0.60.2 Release Execution

## Summary of Completed Work

1. **Subdomain `robots.txt` Crawlability (`static/robots.txt`, `templates/robots.txt`):**
   - Configured universal allow-all crawler directives (`User-agent: *` / `Allow: /`) on both static asset subdomains (`static.amedbulten.com` and `assets.amedbulten.com`).
   - Verified both subdomains respond with `HTTP/2 200`.

2. **Asset `.htaccess` CORS/CORP & Direct Execution Hardening (`static/.htaccess`, `templates/.htaccess`):**
   - Expanded `<FilesMatch>` in both `static/.htaccess` and `templates/.htaccess` to apply `Access-Control-Allow-Origin "*"` and `Cross-Origin-Resource-Policy "cross-origin"` to web images (`ico|webp|jpe?g|png|gif|svg`).
   - Hardened `templates/.htaccess` with execution and download denial for scripts and templates (`php[0-9]?|phtml|phar|pl|py|cgi|sh|twig`).

3. **Deploy Script OPcache Invalidation Coverage (`tools/deploy_production.sh`):**
   - Added `${REMOTE_DOCROOT}config` and `${REMOTE_DOCROOT}mcp.php` to the deployment timestamp revalidation touch command, ensuring immediate OPcache invalidation for site configuration and MCP bridge endpoints upon release.

4. **Framework Version Parity (`v1.0.60.2`):**
   - `config/site.config.php`: bumped version fallback to `1.0.60.2`.
   - `config/site.config.example.php`: bumped version fallback to `1.0.60.2`.
   - `docs/SERVER.md`: updated documented version to `v1.0.60.2 (2026-11-10)`.
   - `docs/VERSION.md`: registered release `## v1.0.60.2 (2026-11-10)`.

---

## Quality Gates & Verification

| Gate / Command | Target Files | Result |
|---|---|---|
| `php -l` | `config/site.config.php`, `config/site.config.example.php` | PASS (0 syntax errors) |
| `bash -n` | `tools/deploy_production.sh` | PASS (0 syntax errors) |
| `python3 tools/i18n_guard.py --path` | `config/site.config.php` | PASS (100% CLEAN! Zero violations) |
| `python3 tools/min_css_guard.py` | All bundles | PASS (421/421, 115/115, 697/697, 1949/1949 rule blocks) |
| `python3 tools/module_boundary_guard.py` | Touched files | PASS (0 forbidden bindings) |

---

## Production Verification & Live Probes

### 1. Production Health & Subdomain Verification
- Live version banner check:
  `curl -s https://amedbulten.com/tr/ | grep -o 'v=1\.0\.60\.2'` → **`v=1.0.60.2`**
- Subdomain `robots.txt` checks:
  - `curl -sI https://static.amedbulten.com/robots.txt` → **`HTTP/2 200`**
  - `curl -sI https://assets.amedbulten.com/robots.txt` → **`HTTP/2 200`**

### 2. Read-Only Production Verification & Local Docker Seed Guard Proof
- Live MCP counts (`amed-bulletin` MCP):
  - `list_events({})` → **71 events**
  - `list_events({is_official_holiday: "y"})` → **24 official holidays**
  - `list_event_categories({})` → **11 categories**
- Production read-only SSH artifact checks:
  - `grep -c "update('id')"` in `lib/McpEventTools.class.php` → **2**
  - `grep -c 'protects re-seeded data'` in `tasks/seed_mod_event_taxonomy_v1060.php` → **1**
  - `php -l` syntax validation on both production files → **0 syntax errors**
- Local Docker Phase 4 Idempotency Guard:
  - Executed: `docker exec bulletin_app php /var/www/html/tasks/seed_mod_event_taxonomy_v1060.php`
  - Output confirmed: `-> [SKIP] New-taxonomy categories (8-11) already populated; id-range reclassification skipped (protects re-seeded data).` and `Migration Complete: 0 inserted, 27 skipped.`

### 3. Live MCP Update Persistence Probes (`status: "n"` Isolated)
- Single-row probe round-trip:
  1. `create_event` with `status: "n"`, official holiday `y`, duration `1.5`, recurring `y` → Created record **#77**.
  2. `get_event({id: 77})` → Verified attributes matched probe parameters.
  3. `update_event({id: 77, holiday_duration: 2.0, is_recurring: "n", name: "v1.0.60.2 MCP Update Probe Renamed"})` → Returned `updated: true`.
  4. `list_events({search: "v1.0.60.2 MCP Update Probe"})` → Exactly **1 record** returned (`id: 77`, updated in place, no duplicate row).
  5. `delete_event({id: 77})` → Returned `deleted: true`.
  6. Final state parity: `list_events({})` returned **71**, and `curl -s https://amedbulten.com/tr/eventSitemap.xml | grep -c '<loc>'` returned **71**.
- Category no-op probe:
  - `get_event_category({id: 5})` → Retrieved current description.
  - `update_event_category({id: 5, description: ...})` → Returned `updated: true`.
  - `list_event_categories({})` → Total categories remained **11** (no duplicate category rows created).
