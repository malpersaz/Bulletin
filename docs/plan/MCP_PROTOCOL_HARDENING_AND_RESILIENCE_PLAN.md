# MCP Server Protocol Hardening, Multi-Instance Positive Identity Pinning, Gemini OAuth 2.1 Compatibility, Editorial Image Contract, and Docker Resilience Plan

**Document Status:** Audited Enterprise Architectural & Implementation Plan (v2.1 — Multi-Site Portable, Zero-Hardcoding, Gemini OAuth 2.1 & MCP 2026-07-28 Standard)  
**Target Workspace:** `AF/Bulletin` (`/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin`)  
**Default Configured MCP Server Identity:** `amed-bulletin-mcp` / `amed_bulletin_mcp` (via `$site['mcp_server_name']` in `config/site.config.php`)  
**Reference Upstream Release:** `AF/Rojname` v1.1.50 (2026-10-31) + Google Gemini API OAuth / Managed Agent Credentials & MCP `2026-07-28` Specification  
**Date:** 2026-11-01  

---

## 1. Executive Summary & Architectural Philosophy

This plan establishes a **100% configuration-driven, domain-agnostic, multi-instance MCP architecture** for Amed Framework (`AF/Bulletin` v1.0.12 and all future websites built on Amed Framework). It integrates the JSON-RPC 2.0 protocol hardening, mandatory news image contract, and Docker resilience improvements from `AF/Rojname` v1.1.50, while upgrading the authentication and transport layers to align with **Google Gemini API OAuth / Managed Agent Credentials (`ai.google.dev/gemini-api/docs/oauth` & `/docs/agent-credentials`)** and the **Model Context Protocol (MCP) `2026-07-28` Stateless Specification**.

### Why a Portable, Zero-Hardcoding Standard Is Mandatory
Amed Framework powers multiple independent websites and repositories (`AF/Business`, `AF/Rojname`, `AF/Bulletin`, and future deployments). Hardcoding project-specific server names (`amed-bulletin-mcp`, `amed-rojname-mcp`) inside `mcp.php`, `lib/McpServer.class.php`, or `lib/McpOAuth.class.php`, or relying on hardcoded domain blacklists (`FORBIDDEN_HOSTS`) or single-vendor CIMD allowlists (`chatgpt.com`), creates latent cross-site collision risks and blocks modern AI agent platforms whenever the framework is cloned or deployed to a new domain.

To guarantee that **no two Amed Framework instances can ever accidentally write to each other's live websites or local containers—now or on any future website deployment—while remaining 100% compatible with Google Gemini Agents, Antigravity, and MCP 2026 clients**, this plan enforces six engineering pillars:

1. **Dynamic Configuration-Driven Server Identity & Gemini `snake_case` Interoperability (`config/site.config.php`, `lib/McpServer.class.php`, `mcp.php`):**
   - Zero hardcoded site names in `mcp.php`, `lib/McpServer.class.php`, or `lib/McpOAuth.class.php`.
   - `McpServer::resolveServerName()` resolves the server identity dynamically from `AF_MCP_SERVER_NAME` (env) $\rightarrow$ `$site['mcp_server_name']` (`config/site.config.php` / `config/site.config.local.php`) $\rightarrow$ deterministic host-slug derivation (`amed-mcp-<domain-slug>`).
   - Because Google Gemini Interactions API Remote MCP (`type: "mcp_server"`) forbids hyphens (`-`) in MCP server names and mandates `snake_case` (e.g. `amed_bulletin_mcp`), `McpServer::normalizeServerIdentifier()` normalizes `-` and `_` during target-binding comparison so both `amed-bulletin-mcp` (stdio/CLI) and `amed_bulletin_mcp` (Gemini Remote MCP) are recognized seamlessly for the same instance without weakening cross-site isolation.
   - `McpServer::resolveCanonicalResource()` resolves the canonical origin URL from `$site['address']`.
   - Every MCP response emits both `X-MCP-Server-Name` and `X-MCP-Resource`.
2. **RFC 9110 `421 Misdirected Request` Positive Target-Binding Gate with Loopback/Proxy Awareness (`lib/McpServer.class.php` & Bridge Layer):**
   - Instead of maintaining brittle blacklists of sibling domains, Amed Framework enforces **Zero-Trust Positive Target Pinning**.
   - Clients/bridges send `X-MCP-Expected-Server` and `X-MCP-Expected-Resource` headers on every request.
   - Before authentication or tool dispatch, `McpServer::verifyTargetBinding()` validates that `X-MCP-Expected-Server` matches `McpServer::resolveServerName()` (under `-`/`_` canonical normalization) and `X-MCP-Expected-Resource` matches either the origin of `McpServer::resolveCanonicalResource()` or the active request origin (`$_SERVER['HTTP_HOST']`) when operating on loopback (`localhost` / `127.0.0.1` / `::1`). Any mismatch immediately aborts with **HTTP `421 Misdirected Request`** (`JSON_RPC_INVALID_REQUEST`, `-32600`).
   - Conversely, the stdio bridge verifies that every HTTP response carries `X-MCP-Server-Name` matching `EXPECTED_SERVER_NAME` before forwarding results to the agent.
3. **Google Gemini OAuth 2.1 / Managed Agent Credentials & RFC 9728 Hardening (`lib/McpOAuth.class.php`, `lib/McpServer.class.php`):**
   - **JSON & Basic Auth Token Exchange (`/oauth/token`):** Google Gemini's Managed Agent Credentials proxy (`client.credentials.create(type="oauth2")`) and modern OAuth 2.1 clients perform live token validation and refresh against `token_url` using either `application/x-www-form-urlencoded` or `application/json` bodies, and may pass `client_id` / `client_secret` via `Authorization: Basic` (`client_secret_basic`) or POST body (`client_secret_post`). `McpOAuth::handleToken()` is upgraded to parse both form-encoded and JSON payloads, extract `client_id` from `HTTP_AUTHORIZATION` Basic headers when omitted from the body, and advertise `['none', 'client_secret_post', 'client_secret_basic']` in `/.well-known/oauth-authorization-server`.
   - **Configurable Multi-Vendor CIMD Allowlist (SEP-3149):** Replaces the hardcoded `$host !== 'chatgpt.com'` check in `McpOAuth::loadCimdClient()` with a configuration-driven allowlist (`$site['mcp_cimd_allowed_hosts']`) supporting Google Gemini / AI Studio (`*.google.com`, `*.googleapis.com`, `aistudio.google.com`), OpenAI (`chatgpt.com`), and custom enterprise hosts.
   - **Complete RFC 9728 `WWW-Authenticate` Coverage:** Ensures every HTTP `401 Unauthorized` branch in `McpServer` (including invalid API keys and inactive user accounts in `authenticateApiKey()`) emits the `WWW-Authenticate: Bearer realm="mcp", resource_metadata="..."` header.
4. **MCP JSON-RPC 2.0 Protocol Compliance, Notification Framing & `2026-07-28` Stateless Support (`mcp.php`, `lib/McpServer.class.php`):**
   - Pre-parses JSON-RPC payloads before authentication so all `401`, `403`, `421`, `429`, and `500` responses echo the client's `$requestId` instead of `"id": null`.
   - Suppresses response bodies on JSON-RPC notifications (requests without an `"id"` member, such as `notifications/initialized`), returning HTTP `202 Accepted` (or `4xx` without body on failure) to prevent 5-minute client hangs.
   - Adds protocol version `'2026-07-28'` to `SUPPORTED_PROTOCOL_VERSIONS`, implements the stateless `server/discover` method alongside `initialize`, and adds `Mcp-Method, Mcp-Name` to `Access-Control-Allow-Headers`.
5. **Editorial Schema & Mandatory Featured Image Contract (`lib/McpNewsTools.class.php`, `lib/McpFaqTools.class.php`):**
   - Enforces non-nullable featured images (`image.data_base64` and `image.mime`) on news creation (`create_news`), forbids removing featured images on updates (`update_news`), and documents `mod_news`, `mod_publication`, and `mod_page` in `mod_faq` tool schemas.
6. **Docker Resilience & Portable Multi-Instance Health Check (`docker-compose.yml`, `docker/nginx/default.conf`, `docker/php/php.dev.ini`, `tools/mcp_health_check.py`):**
   - Adds container restart policies, parameterized ports, MySQL healthchecks, FastCGI timeout/buffer tuning (`300s`), and `xdebug.start_with_request = trigger`.
   - Introduces a **workspace-auto-discovering** `tools/mcp_health_check.py` that reads `.gemini/settings.json` and `config/site.config.php` dynamically (zero hardcoded URLs or server names in Python logic) and runs a 6-stage verification suite including an adversarial HTTP `421` misdirected-request test and OAuth metadata discovery check.
7. **Phone-First Native App UI Redesign & 100% Kurdish (`ku`, `zza`, `ckb`) Localization Overhaul (`templates/admin/mod_mcp/`, `templates/admin/assets/css/mod_mcp.css`, `templates/admin/assets/js/mod_mcp.js`, `mod/mod_mcp/lang/`):**
   - Re-engineers `/mod_mcp/list` and `/mod_mcp/audit` under **Taste Skill** (`DESIGN_VARIANCE: 6`, `MOTION_INTENSITY: 5`, `VISUAL_DENSITY: 7`) and **Impeccable Super Polish** (`admin-mobile-check` phone-only surface contract) so the MCP admin console feels like a native mobile developer security app.
   - Replaces broken/corrupted machine translations (`"Dêwxan"`, `"Serketdarê"`, `"binfoq"`, Cyrillic `"асno"`, `"کلیلێنتەکان"`, `"زانکۆکان"`) with 100% rewritten, natural, grammatically pure **Kurmanji (`ku.php`)**, **Zazaki / Kirmanckî (`zza.php`)**, and **Sorani (`ckb.php`)** language packs.

---

## 2. Multi-Instance Isolation & Handshake Architecture

```
+-----------------------------------------------------------------------------------+
| MCP Client / Stdio Bridge / Gemini Remote MCP Proxy                               |
|   Pinned Config:                                                                  |
|     ENDPOINT             = "http://localhost:8080/mcp.php" (or live HTTPS domain) |
|     EXPECTED_SERVER_NAME = "amed-bulletin-mcp" (or "amed_bulletin_mcp" in Gemini) |
|     EXPECTED_RESOURCE    = "http://localhost:8080" (derived from ENDPOINT origin) |
+-----------------------------------------------------------------------------------+
          |                                                       ^
          | 1. HTTP POST /mcp.php                                 | 4. Bridge verifies:
          |    Authorization: Bearer amcp_... (or OAuth token)    |    X-MCP-Server-Name == EXPECTED_SERVER_NAME
          |    X-MCP-Expected-Server: amed-bulletin-mcp           |    (Halts on mismatch)
          |    X-MCP-Expected-Resource: http://localhost:8080     |
          v                                                       |
+-----------------------------------------------------------------------------------+
| Amed Framework MCP Endpoint (mcp.php -> lib/McpServer.class.php)                  |
|   Dynamic Identity Resolution (from config/site.config.php & site.config.local):  |
|     $actualServer   = McpServer::resolveServerName()                              |
|     $actualResource = McpServer::resolveCanonicalResource()                       |
|                                                                                   |
|   2. Pre-Auth Positive Target-Binding Gate (McpServer::verifyTargetBinding()):    |
|      - If normalize(X-MCP-Expected-Server) != normalize($actualServer) --> 421!   |
|      - If X-MCP-Expected-Resource != $actualResource (and not valid loopback)     |
|        --> HTTP 421 Misdirected Request (Abort!)                                  |
|                                                                                   |
|   3. Authenticate (API Key or OAuth 2.1 Bearer with RFC 8707 + RFC 9728 headers)  |
|      & Dispatch Tool Call (`initialize`, `server/discover`, `tools/call`, etc.)   |
|      Emit Headers:                                                                |
|        X-MCP-Server-Name: <$actualServer>                                         |
|        X-MCP-Resource: <$actualResource>                                          |
+-----------------------------------------------------------------------------------+
```

---

## 3. Gap Analysis (`AF/Bulletin` Current State vs. Target Portable Standard)

| Component | Current State in `AF/Bulletin` | Target Portable Standard (v2.1) |
| :--- | :--- | :--- |
| `config/site.config.php` & `config/site.config.example.php` | No `$site['mcp_server_name']` or `$site['mcp_cimd_allowed_hosts']` keys defined; server identity is hardcoded inside `lib/McpServer.class.php`. | Defines `$site['mcp_server_name'] = $site['mcp_server_name'] ?? 'amed-bulletin-mcp';` and `$site['mcp_cimd_allowed_hosts']` (supporting Google Gemini, AI Studio, and ChatGPT). |
| `mcp.php` | Emits `"id": null` on fatal/uncaught errors; does not inspect `$isNotification` or `$reqId`; emits no identity headers. | Pre-parses `$rawInput` for `$reqId` and `$isNotification`; returns HTTP `202` with empty body on notifications; dynamically resolves and emits `X-MCP-Server-Name` and `X-MCP-Resource` via `McpServer`; echoes `$reqId`. |
| `lib/McpServer.class.php` | Uses hardcoded `self::SERVER_NAME`; authenticates before parsing JSON-RPC payload; lacks `X-MCP-Expected-Server` / `X-MCP-Expected-Resource` verification; missing `'2026-07-28'` and `server/discover`; missing `WWW-Authenticate` on 401 API key errors. | Provides `resolveServerName()`, `normalizeServerIdentifier()`, and `resolveCanonicalResource()`; parses JSON-RPC payload first; enforces `verifyTargetBinding()` (HTTP `421` on mismatch, with `-`/`_` normalization and loopback awareness); supports MCP `'2026-07-28'`, `server/discover`, and `Mcp-Method`/`Mcp-Name` CORS headers; emits `WWW-Authenticate` on all `401` responses. |
| `lib/McpOAuth.class.php` | Reads `Registry::get('site_config')['address']` directly; `handleToken()` only reads `$_POST` (`application/x-www-form-urlencoded`) and ignores `application/json` bodies and `Authorization: Basic` headers; `loadCimdClient()` hardcodes `$host !== 'chatgpt.com'`. | Uses `McpServer::resolveCanonicalResource()` and `McpServer::resolveServerName()`; `handleToken()` parses both `$_POST` and `application/json` payloads plus `HTTP_AUTHORIZATION` Basic client credentials for Google Gemini OAuth proxy compatibility; `loadCimdClient()` validates against configurable `$site['mcp_cimd_allowed_hosts']`. |
| `lib/McpNewsTools.class.php` | `create_news` marks `image` as `nullable: true` and optional; `update_news` allows `image: null` to clear featured image. | `create_news` requires `image` (`nullable: false`, in `required`) and validates `data_base64` at runtime; `update_news` forbids `image: null` and requires `image` if updating an image-less record. |
| `lib/McpFaqTools.class.php` | `list_faqs` and `create_faq` `mod_name` descriptions only mention `e.g. mod_page`. | Explicitly documents `e.g. mod_news, mod_publication, mod_page` as registered host modules in `mod_faq_mod`. |
| `docker-compose.yml` | Hardcoded ports (`8080:80`, `8081:80`), no `restart: unless-stopped`, no MySQL `healthcheck`. | Parameterized ports (`${BULLETIN_WEB_PORT:-8080}:80`, `${BULLETIN_PMA_PORT:-8081}:80`), `restart: unless-stopped` on all services, and `mysqladmin ping` healthcheck. |
| `docker/nginx/default.conf` & `docker/php/php.dev.ini` | Default 60s FastCGI timeout; `uploads` omitted from script deny rule; `xdebug.start_with_request = yes`. | `fastcgi_read_timeout 300s`, `fastcgi_send_timeout 300s`, `16 16k` / `32k` buffers; `uploads` blocked from PHP execution; `xdebug.start_with_request = trigger`. |
| `tools/mcp_health_check.py` | Missing in `AF/Bulletin` (and hardcoded to `amidakurd.net` in `AF/Rojname`). | Portable, config-driven CLI tool that auto-discovers workspace settings from `.gemini/settings.json` and `config/site.config.php`, verifying config coexistence, bridge parity, live ping, HTTP `421` misdirected-request rejection, `server/discover`, and `tools/list`. |
| `mod/mod_mcp/lang/{ku,zza,ckb}.php` | Severe machine-translation corruption (`"Dêwxan"`, `"Serketdarê"`, `"binfoq"`, Cyrillic `"nê асno"`, `"کلیلێنتەکان"`, `"زانکۆکان"`). | 100% rewritten, idiomatic, grammatically pure Kurmanji (`ku.php`), Zazaki (`zza.php`), and Sorani (`ckb.php`) language packs, synchronized across all 10 language files (`en`, `ku`, `zza`, `ckb`, `tr`, `ar`, `de`, `fr`, `ru`, `sv`). |
| `templates/admin/mod_mcp/{list,audit}.twig`, `mod_mcp.css`, `mod_mcp.js` | Basic flat cards, hardcoded hex colors (`#6b7280`, `#f3f4f6`), no endpoint copy action, `audit.twig` filter button mislabeled with `status_label`, `mod_mcp.js` missing `oauthDeleteConfirm` dataset binding. | Native mobile app experience built with **Taste Skill** & **Super Polish**: interactive endpoint pill with one-tap copy, KPI summary strip, refined M3 elevation/surface tokens, tactile 44px+ touch targets, optimistic DOM state transitions, and zero inline styles/scripts. |

---

## 4. Detailed Implementation Phases

### Phase 1: Portable Server Identity, Positive Target-Binding Gate & Gemini OAuth 2.1 Compatibility (`config/`, `mcp.php`, `lib/McpServer.class.php`, `lib/McpOAuth.class.php`)

#### 1.1. Site Configuration (`config/site.config.php` & `config/site.config.example.php`)
Add canonical MCP server identity and CIMD trusted host configuration before `site.config.local.php` merge:
```php
$site['mcp_server_name'] = $site['mcp_server_name'] ?? 'amed-bulletin-mcp';
$site['mcp_cimd_allowed_hosts'] = $site['mcp_cimd_allowed_hosts'] ?? [
    'chatgpt.com',
    'aistudio.google.com',
    'gemini.google.com',
    'developers.google.com',
];
```
When Amed Framework is deployed to a new website, setting `$site_local['mcp_server_name']` in `config/site.config.local.php` (or setting `$site['mcp_server_name'] = 'auto'`) immediately rebinds all MCP headers, OAuth metadata, and target-binding checks without touching framework code.

#### 1.2. Dynamic Identity Resolution, MCP `2026-07-28` & Target Binding in `lib/McpServer.class.php`
1. **Protocol Versions & Constants:**
   - Update `SUPPORTED_PROTOCOL_VERSIONS` to `['2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25', '2026-07-28']`.
   - Replace `private const SERVER_NAME` with `public const DEFAULT_SERVER_NAME = 'amed-bulletin-mcp';`.
2. **Dynamic Identity & Normalization Methods:**
   ```php
   public static function resolveServerName(): string
   {
       $envName = getenv('AF_MCP_SERVER_NAME');
       if (is_string($envName) && trim($envName) !== '') {
           return trim($envName);
       }
       $site = Registry::has('site_config') ? (array) Registry::get('site_config') : [];
       $configured = trim((string) ($site['mcp_server_name'] ?? ''));
       if ($configured !== '' && $configured !== 'auto') {
           return $configured;
       }
       $host = strtolower((string) parse_url((string) ($site['address'] ?? ''), PHP_URL_HOST));
       if ($host !== '' && $host !== 'localhost' && $host !== '127.0.0.1') {
           $slug = trim((string) preg_replace('/[^a-z0-9]+/', '-', $host), '-');
           if ($slug !== '') {
               return 'amed-mcp-' . $slug;
           }
       }
       return self::DEFAULT_SERVER_NAME;
   }

   public static function normalizeServerIdentifier(string $name): string
   {
       return strtolower(str_replace('_', '-', trim($name)));
   }

   public static function resolveCanonicalResource(): string
   {
       $site = Registry::has('site_config') ? (array) Registry::get('site_config') : [];
       return rtrim((string) ($site['address'] ?? ''), '/');
   }
   ```
3. **Positive Target-Binding Gate (`verifyTargetBinding()`):**
   - Called in `McpServer::handle()` right after parsing `$requestId` and `$isNotification`, **before** `$this->authenticate($requestId, $isNotification)`.
   - Checks `$_SERVER['HTTP_X_MCP_EXPECTED_SERVER']`: if non-empty and `self::normalizeServerIdentifier($expectedServer) !== self::normalizeServerIdentifier(self::resolveServerName())`, emits HTTP `421 Misdirected Request` (empty body if `$isNotification`, or JSON-RPC `-32600` error with `$requestId`). This allows both `amed-bulletin-mcp` (stdio/CLI) and `amed_bulletin_mcp` (Google Gemini Interactions API `snake_case` requirement) while blocking any sibling server (e.g. `amed-rojname-mcp` / `amidakurd`).
   - Checks `$_SERVER['HTTP_X_MCP_EXPECTED_RESOURCE']`: if non-empty, normalizes `scheme://host[:port]` of expected, `self::resolveCanonicalResource()`, and the active HTTP request origin (`$_SERVER['HTTP_HOST']`). If the expected resource matches neither the canonical resource nor a loopback request origin (`localhost`, `127.0.0.1`, `::1`), emits HTTP `421 Misdirected Request`.
4. **Deferred Authentication, Notification Framing & Complete `WWW-Authenticate` Headers:**
   - Parse `$raw = $this->readRequestBody();` and `json_decode()` before calling `$this->authenticate($requestId, $isNotification)`.
   - Update `authenticate()`, `authenticateApiKey()`, and `authenticateAccessToken()` to accept `($requestId = null, bool $isNotification = false)` so all `401`, `403`, `429`, and `500` responses echo `$requestId` and suppress response bodies when `$isNotification` is `true`.
   - Emit `header('WWW-Authenticate: ' . $this->bearerChallenge());` on all HTTP `401` responses in `authenticateApiKey()` (invalid key and inactive user) to comply with RFC 9110 and RFC 9728.
5. **MCP `2026-07-28` `server/discover` & CORS Headers (`sendCorsHeaders()` & `sendJson()`):**
   - In `dispatch()`, route `case 'server/discover':` to `$this->handleInitialize($params)`.
   - Allow `X-MCP-Expected-Server, X-MCP-Expected-Resource, Mcp-Method, Mcp-Name` in `Access-Control-Allow-Headers`.
   - Expose `X-MCP-Server-Name, X-MCP-Resource, MCP-Protocol-Version, Mcp-Session-Id` in `Access-Control-Expose-Headers`.
   - Emit `X-MCP-Server-Name: ' . self::resolveServerName()` and `X-MCP-Resource: ' . self::resolveCanonicalResource()` in `sendJson()` and all notification response branches.

#### 1.3. Google Gemini OAuth 2.1 & Multi-Vendor CIMD Upgrades in `lib/McpOAuth.class.php`
1. **Canonical Resource & Server Name Alignment:**
   - Initialize `$this->resource = class_exists('McpServer') ? McpServer::resolveCanonicalResource() : rtrim((string) (Registry::get('site_config')['address'] ?? ''), '/');`.
2. **Dual-Format Token Endpoint (`handleToken()`) for Gemini Managed Agent Credentials:**
   - Parse input parameters from `$_POST`; if `$_POST` is empty or `Content-Type` contains `application/json`, merge decoded JSON from `php://input` into `$tokenParams`.
   - Support `Authorization: Basic base64(client_id:client_secret)` (`client_secret_basic`) when `client_id` is not provided in the POST/JSON body.
   - Update `sendAuthorizationServerMetadata()` to advertise `'token_endpoint_auth_methods_supported' => ['none', 'client_secret_post', 'client_secret_basic']`.
3. **Configurable CIMD Host Validation (`loadCimdClient()`):**
   - Read allowed hosts from `$site['mcp_cimd_allowed_hosts']` (defaulting to `['chatgpt.com', 'aistudio.google.com', 'gemini.google.com', 'developers.google.com']`) and support exact host or `*.google.com` / `*.googleapis.com` suffix matching while still blocking private/loopback SSRF targets.

#### 1.4. `mcp.php` Endpoint Hardening
1. Pre-parse `php://input` before `ob_start()` to extract `$isNotification` and `$reqId`.
2. In `register_shutdown_function` and `catch (\Throwable $e)`:
   - Suppress response body and return HTTP `202` when `$isNotification` is `true`.
   - Resolve `$serverName = class_exists('McpServer') ? McpServer::resolveServerName() : ($site['mcp_server_name'] ?? 'amed-mcp');` and `$resource = class_exists('McpServer') ? McpServer::resolveCanonicalResource() : '';`.
   - Emit `X-MCP-Server-Name` and `X-MCP-Resource` headers on HTTP 500 responses.
   - Echo `'id' => $reqId` and restrict `error.data.diagnostic` to `$isLocal`.

---

### Phase 2: Editorial Schema & Mandatory Featured Image Enforcement (`lib/McpNewsTools.class.php` & `lib/McpFaqTools.class.php`)

#### 2.1. `lib/McpNewsTools.class.php`
1. **`create_news` Schema & Handler (`createNews()`):**
   - Set `image.nullable` to `false`, restrict `image.properties.mime.enum` to `['image/jpeg', 'image/png', 'image/gif', 'image/webp']`, and add `'image'` to `required`.
   - Validate in `createNews()` that `$args['image']` is a non-empty array with non-empty `data_base64` and that `$imageUrl` resolves cleanly; otherwise throw `McpToolException`.
2. **`update_news` Schema & Handler (`updateNews()`):**
   - Set `image.nullable` to `false` and restrict `image.properties.mime.enum`.
   - In `updateNews()`, reject `image: null` or empty `data_base64` with `McpToolException("Removing the featured image is not allowed; a news article must always have an image.")`, and require `image` when updating a legacy record that currently has an empty `$model->image`.

#### 2.2. `lib/McpFaqTools.class.php`
1. Update `list_faqs` and `create_faq` `mod_name` schema descriptions to explicitly list `e.g. mod_news, mod_publication, mod_page` as host modules registered in `mod_faq_mod`.

---

### Phase 3: Docker & Container Environment Performance & Resilience

#### 3.1. `docker-compose.yml`
1. Add `restart: unless-stopped` to `app`, `web`, `db`, and `pma`.
2. Parameterize ports: `"${BULLETIN_WEB_PORT:-8080}:80"` and `"${BULLETIN_PMA_PORT:-8081}:80"`.
3. Add native MySQL readiness `healthcheck` (`mysqladmin ping -h localhost -ubulletin -pbulletin_password`, interval `10s`, timeout `5s`, retries `5`, start_period `10s`).

#### 3.2. `docker/nginx/default.conf` & `docker/php/php.dev.ini`
1. Extend writable directory script block in `docker/nginx/default.conf` to `^/(static|cache|data|templates_c|uploads)/.*\.(php|phtml|phar|inc)$`.
2. Add `fastcgi_read_timeout 300s;`, `fastcgi_send_timeout 300s;`, `fastcgi_buffers 16 16k;`, and `fastcgi_buffer_size 32k;` to `location ~ \.php$`.
3. Change `xdebug.start_with_request` from `yes` to `trigger` in `docker/php/php.dev.ini`.

---

### Phase 4: Positive-Pinned Bridge, Portable Health Check & Editorial Skill Alignment

#### 4.1. Positive-Pinned Stdio Bridge (`~/.gemini/antigravity/bin/amed_bulletin_mcp.py` & `~/.gemini/antigravity-cli/bin/amed_bulletin_mcp.py`)
1. Define `EXPECTED_SERVER_NAME = "amed-bulletin-mcp"` and derive `expected_resource` (`scheme://host[:port]`) dynamically from `endpoint`.
2. Send `"X-MCP-Expected-Server": EXPECTED_SERVER_NAME` and `"X-MCP-Expected-Resource": expected_resource` on every HTTP POST request.
3. Verify on every HTTP response that `resp.headers.get("X-MCP-Server-Name")` normalized (`_`/`-`) matches `EXPECTED_SERVER_NAME`; abort with a JSON-RPC `-32603` error if missing or mismatched.
4. Enforce `_check_image_policy(req_obj, req_id)` at the bridge layer as a fast pre-flight guard for `create_news` and `update_news`.
5. Synchronize both `~/.gemini/antigravity/bin/amed_bulletin_mcp.py` and `~/.gemini/antigravity-cli/bin/amed_bulletin_mcp.py`.

#### 4.2. Portable, Auto-Discovering `tools/mcp_health_check.py`
Create `tools/mcp_health_check.py` with zero hardcoded project URLs in its core logic:
1. Auto-discovers the active workspace's MCP server key and bridge path from `.gemini/settings.json` and expected server identity from `config/site.config.php` (`$site['mcp_server_name']`), while supporting CLI flags (`--server-key`, `--expected-server`, `--endpoint`, `--bridge`).
2. Verifies global coexistence in `~/.gemini/config/mcp_config.json` and `~/.gemini/antigravity/mcp_config.json` (ensuring no sibling server entries were dropped and no two server keys share the same bridge path or endpoint).
3. Verifies byte-for-byte parity between `~/.gemini/antigravity/bin/<bridge>.py` and `~/.gemini/antigravity-cli/bin/<bridge>.py`.
4. Executes a live JSON-RPC `ping` and `server/discover` (`2026-07-28`) with `X-MCP-Expected-Server` (testing both hyphenated `amed-bulletin-mcp` and Gemini `snake_case` `amed_bulletin_mcp`) and verifies HTTP `200`, echoed `"id"`, and `X-MCP-Server-Name` response header.
5. Executes an **Adversarial Misdirected-Request Probe** (`X-MCP-Expected-Server: __invalid_target_probe__`) and asserts that the server responds with **HTTP `421 Misdirected Request`**.
6. Executes `tools/list` and asserts that `create_news` requires `image` (`nullable: false`).

#### 4.3. Editorial Skill Alignment (`.agents/skills/kevanger/SKILL.md`)
1. Fix `amedbulten` $\rightarrow$ `amed-bulletin` (`amed-bulletin-mcp`) in `.agents/skills/kevanger/SKILL.md` and document the strict workspace MCP server binding rule.

---

### Phase 5: Phone-First Native App Redesign (`mod_mcp/list` & `mod_mcp/audit`) & 100% Kurdish (`ku`, `zza`, `ckb`) Localization Overhaul

#### 5.1. Taste Skill & Super Polish Design Read
- **Design Read:** *"Reading this as: a phone-first developer security & API key console for CMS administrators, with a crisp, tactile, high-trust native mobile utility language, leaning toward Material 3 Expressive / Linear Mobile precision."*
- **Three Dials Configuration:**
  - `DESIGN_VARIANCE`: `6/10` (Structured mobile app cards with asymmetric status badges, monospace cryptographic token chips, and clear visual hierarchy)
  - `MOTION_INTENSITY`: `5/10` (Tactile `scale(0.97)` press feedback, smooth reveal-card entrance, and instant icon state swaps on copy/toggle)
  - `VISUAL_DENSITY`: `7/10` (Compact, scannable mobile cards with 4px/8px spatial rhythm, zero wasted vertical space above the fold, and strict $\ge 44\times44\text{px}$ tap targets)

#### 5.2. 100% Rewritten Kurdish Language Packs (`mod/mod_mcp/lang/ku.php`, `zza.php`, `ckb.php`) & Multi-Language Key Parity
1. **`mod/mod_mcp/lang/ku.php` (Kurmancî):**
   - 100% rewritten from scratch in natural, standard Kurmanji (e.g., *Mifteyên API yên MCP*, *Serlêder / Xwester*, *Komên Rayeyan*, *Hat Betalkirin*, *Bêçalak Bike*, *Nûjenkirin*, *Bitikîne*). Eliminates all nonsensical words (*"Dêwxan"*, *"Serketdarê"*, *"binfoq"*, *"nederbîr"*).
2. **`mod/mod_mcp/lang/zza.php` (Kirmanckî / Zazakî):**
   - 100% rewritten from scratch in pure standard Kirmanckî/Zazakî (e.g., *Kîlîtê API yê MCP*, *Waştekarî*, *Grûba Rayeyan*, *Betal Biyo*, * Aktîf Bike / Bêçalak Bike*, *Bestere*). Eliminates all corrupted tokens and Cyrillic characters (*"Klimenti"*, *"Erdişit"*, *"nê асno"*, *"payiz ra heddna"*).
3. **`mod/mod_mcp/lang/ckb.php` (Soranî):**
   - 100% rewritten from scratch in clean, standard Central Kurdish / Soranî (e.g., *کلیلەکانی APIی MCP*, *ڕاژەخوازەکانی OAuth 2.1*, *گرووپی دەسەڵات*, *هەڵوەشاوەتەوە*, *تۆمارەکانی چاودێری*). Eliminates corrupted machine translations (*"کلیلێنتەکان"*, *"سەلمێنەری دەکەن"*, *"زانکۆکان دەپەیوەندی دەکەن"*).
4. **New UI Keys Across All 10 Language Packs (`en`, `ku`, `zza`, `ckb`, `tr`, `ar`, `de`, `fr`, `ru`, `sv`):**
   - Add `copy_endpoint` (*Copy URL*), `endpoint_copied` (*Endpoint URL copied to clipboard.*), `keys_section_title` (*Issued API Keys*), `stat_total_keys` (*Total Keys*), `stat_active_keys` (*Active*), `stat_oauth_clients` (*OAuth Clients*), and `audit_filter_submit` (*Apply Filter* — fixing the bug in `audit.twig` where `status_label` was used as the filter button label).

#### 5.3. Native Mobile App UI & Interaction Polish (`templates/admin/mod_mcp/list.twig`, `audit.twig`, `mod_mcp.css`, `mod_mcp.js`)
1. **`templates/admin/mod_mcp/list.twig`:**
   - **Segmented App Switcher (`.mcp-tabs`):** Add Bootstrap Icons (`bi-key-fill`, `bi-activity`) with 44px touch height and active pill elevation.
   - **Quick KPI Strip (`.mcp-summary-strip`):** Display compact mobile counters for Total Keys, Active Keys, and OAuth Clients right below the navigation tabs.
   - **Interactive Endpoint Card (`.mcp-endpoint-card`):** Add terminal/plug iconography, monospace URL pill, and a dedicated 44x44px one-tap **Copy Endpoint** button (`data-copy-text="{{ mcp_endpoint }}"`).
   - **One-Time Key Reveal Banner (`.mcp-reveal-card`):** High-contrast warning surface with icon header, selectable monospace token box, copy button, and dismiss button (`reveal_close`).
   - **Compact Key Generator Sheet Card (`.mcp-create-card`):** Clean M3 form layout with leading input icons, 48px touch-friendly inputs/selects, and full-width primary action button.
   - **Issued API Keys & OAuth 2.1 Client Cards (`.mcp-key-card`):** Structured header with status dot + pill badge, monospace prefix chip (`amcp_...`) with copy-prefix support, 2-column metadata grid (Permission Group, Creator, Created At, Last Used), and full-height 44px action buttons in the card footer.
2. **`templates/admin/mod_mcp/audit.twig`:**
   - Replace `{{ lang.mod_mcp.status_label }}` on the filter submit button with `{{ lang.mod_mcp.audit_filter_submit }}` and a filter icon (`bi-funnel-fill`).
   - Polish the 3-column metrics strip and audit log cards with semantic HTTP status badges (`200`, `401`, `421`, `500`), caller-type pills, latency badges, and timestamps.
3. **`templates/admin/assets/css/mod_mcp.css` & `templates/admin/assets/js/mod_mcp.js`:**
   - Replace all hardcoded hex colors (`#6b7280`, `#f3f4f6`, `#374151`) with Material 3 CSS custom properties (`var(--md-sys-color-*)`) and explicit fallbacks.
   - Fix the bug in `mod_mcp.js` where `langData.oauthDeleteConfirm` was never loaded from `langInput.dataset.oauthDeleteConfirm` (causing `window.confirm(undefined)` when deleting an OAuth client!).
   - Add `data-copy-text` support for one-tap copying of the MCP endpoint URL with visual checkmark feedback (`bi-check2`).
   - Add dismiss handler for the one-time key reveal card (`reveal_close`).

---

## 5. Deterministic Verification Suite

1. **PHP Syntax Validation:**
   ```bash
   php -l config/site.config.php
   php -l config/site.config.example.php
   php -l mcp.php
   php -l lib/McpServer.class.php
   php -l lib/McpOAuth.class.php
   php -l lib/McpNewsTools.class.php
   php -l lib/McpFaqTools.class.php
   ```
2. **Bridge & Portable Health Check Verification:**
   ```bash
   python3 -m py_compile tools/mcp_health_check.py ~/.gemini/antigravity/bin/amed_bulletin_mcp.py
   python3 tools/mcp_health_check.py
   ```
3. **MCP Smoke Suite & Repository Quality Guards:**
   ```bash
   php tasks/mcp_smoke.php
   python3 tools/module_boundary_guard.py
   python3 tools/code_separation_guard.py
   python3 tools/i18n_guard.py
   python3 tools/min_css_guard.py
   ```
4. **Changelog Documentation:**
   - Record all changes under `## v1.0.12` in `docs/VERSION.md` in English (`AGENTS.md` Rule 3 & Rule 5).
