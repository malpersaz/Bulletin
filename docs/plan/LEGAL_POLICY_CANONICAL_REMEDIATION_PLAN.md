# Legal & Policy URLs Canonicalization and Multi-Language Remediation Plan

## 1. Executive Summary & Root Cause Analysis

### Context
Amed Bulletin (`amedbulten.com`) operates statutory and compliance legal policy pages (`security-policy`, `privacy-policy`, `terms-of-service`, `cookie-policy`, `editorial-policy`, `accessibility`, `compliance`, `legal`, `contact`). These policies are published as single-source, standardized legal declarations.

### Root Causes of Google Indexing `/{lang}/security-policy`
1. **Dynamic XML Sitemap Generation (`mod/index/index.php` -> `pageSitemap()`):**
   - The master `sitemap.xml` generates language-specific child sitemaps (e.g. `/ku/pageSitemap.xml`, `/tr/pageSitemap.xml`).
   - In `pageSitemap()`, line 1136 outputs statutory policy links using `$this->site['saddress'] . $pslug`. When executing in a language context (e.g. `/ku/`), `$this->site['saddress']` evaluates to `https://amedbulten.com/ku/`, actively advertising `https://amedbulten.com/ku/security-policy` to Googlebot and other web crawlers in XML sitemaps.
2. **Dynamic Canonical Tags in Twig Templates:**
   - In `templates/amed/index/legal/` (`security_policy.twig`, `privacy_policy.twig`, etc.), the `<link rel="canonical">` and JSON-LD schema definitions construct URLs using `{{ site.saddress }}<slug>`.
   - When Googlebot crawls `/ku/security-policy`, the page confirms itself as its own canonical target (`https://amedbulten.com/ku/security-policy`), causing Google to index the language-prefixed duplicate instead of a single root canonical URL.
3. **Internal Navigation Links (`_legal_nav.twig`, `footer.twig`):**
   - Internal tabs and footer links use `{{ site.saddress }}security-policy`, leading internal crawlers directly into language-prefixed endpoints.
4. **Missing 301 Canonical Normalization in Router / Legal Helper:**
   - Accessing `https://amedbulten.com/ku/security-policy` returns an HTTP 200 OK status instead of issuing a 301 Permanent Redirect to the canonical root URL (`https://amedbulten.com/security-policy`).
5. **AI / LLM Directory (`lib/LlmsService.class.php`):**
   - References in `llms.txt` and `llms-full.txt` are built via `$this->buildUrl($lang, 'security-policy')`, which appends language prefixes when `$lang` is non-default.

---

## 2. Proposed Remediation Strategy

### Phase 1: Canonical 301 Redirect Normalization
- Enforce strict single-URL canonicalization across all policy endpoints:
  - Slugs: `legal`, `editorial-policy`, `privacy-policy`, `cookie-policy`, `terms-of-service`, `accessibility`, `security-policy`, `compliance`, `contact` (and their aliases).
- In `mod/index/helper/legal.helper.php` (or in router/base controller):
  - Check whether a language prefix exists in the requested URI (e.g., via `$this->url->path[0]` matching an active language code).
  - If a language prefix is present on any policy route, immediately issue an HTTP 301 Moved Permanently response redirecting to the root un-prefixed URL:
    `https://amedbulten.com/<canonical_slug>`
  - Preserve any query parameters if present.

### Phase 2: XML Sitemap Clean-up
- In `mod/index/index.php` -> `pageSitemap()`:
  - Do NOT output policy URLs in language-prefixed sitemaps (`/{lang}/pageSitemap.xml`).
  - Only output policy URLs when the current sitemap corresponds to the default root context (or only inside the root `/sitemap.xml` / `pageSitemap.xml`), and strictly format `<loc>` using the root base address:
    `$this->site['address'] . $pslug` (ensuring no language subpath like `/ku/` or `/tr/` is included).

### Phase 3: Twig Template Canonical & Schema Modernization
- In `templates/amed/index/legal/` (`security_policy.twig`, `privacy_policy.twig`, `terms_of_service.twig`, `cookie_policy.twig`, `editorial_policy.twig`, `accessibility.twig`, `compliance.twig`, `index.twig`, `contact.twig`):
  - Replace `{{ site.saddress }}<slug>` with `{{ site.address }}<slug>` for:
    - `<link rel="canonical" href="...">`
    - `<meta property="og:url" content="...">`
    - `<meta name="twitter:url" content="...">`
    - JSON-LD `@id`, `url`, and breadcrumb item definitions.
- In `templates/amed/partials/_legal_nav.twig` and `templates/amed/index/footer.twig`:
  - Standardize navigation links to point to `{{ site.address }}<slug>` so that on-page links do not propagate language prefixes into crawlers.

### Phase 4: `lib/LlmsService.class.php` Standardization
- For statutory policy links in `LlmsService::generateMarkdown()`:
  - Change `$this->buildUrl($lang, 'security-policy')` to use `$this->baseUrl . 'security-policy'` (root un-prefixed URL), ensuring external LLM indexing engines always refer to the canonical root address.

### Phase 5: Verification & Quality Audit
- Syntax checks (`php -l`) on all modified PHP files.
- Separation guard checks (`python3 tools/code_separation_guard.py`).
- i18n guard checks (`python3 tools/i18n_guard.py`).
- Integration / cURL simulation testing to verify that:
  - `GET /ku/security-policy` returns `HTTP/1.1 301 Moved Permanently` -> `Location: https://amedbulten.com/security-policy`.
  - `GET /security-policy` returns `HTTP/1.1 200 OK` with `<link rel="canonical" href="https://amedbulten.com/security-policy"/>`.
  - `/ku/pageSitemap.xml` does not contain `security-policy` or language-prefixed policy URLs.
  - Default `pageSitemap.xml` lists only `https://amedbulten.com/security-policy`.

---

## 3. Step-by-Step Implementation Tasks

| Task ID | Component | File Path | Action |
|---------|-----------|-----------|--------|
| **T1** | Controller / Helper | `mod/index/helper/legal.helper.php` | Add 301 redirect guard for language-prefixed requests on policy routes |
| **T2** | XML Sitemap | `mod/index/index.php` | Ensure policy URLs are only printed in default root sitemap using `site.address` |
| **T3** | Twig Templates | `templates/amed/index/legal/*.twig` | Switch canonical, OpenGraph, Twitter, and JSON-LD URLs from `saddress` to `address` |
| **T4** | Navigation & Footer | `templates/amed/partials/_legal_nav.twig`, `templates/amed/index/footer.twig` | Switch policy links from `saddress` to `address` |
| **T5** | LLMS Discovery | `lib/LlmsService.class.php` | Use `$this->baseUrl` for all policy links |
| **T6** | Audit & Verification | Command line / curl | Run guards, check status codes and sitemap outputs |
