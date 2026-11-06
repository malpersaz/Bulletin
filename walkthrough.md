# v1.0.48 Release Walkthrough

## Problem

The public Turkish homepage (`/tr/`) emitted the administrative no-store header triple even on a successful response. The source path was the homepage hero-slider author-avatar lookup: `mod_hero_slider_helper::loadUserImageMap()` loaded `user_model`, whose constructor eagerly built `AdminTemplate`. `AdminTemplate::__construct()` correctly emits private/no-store headers for admin views, but that policy leaked into the public response.

## Decision

- Made `AdminTemplate` lazy in `mod/mod_user/model/user.model.php`.
  - Successful public model reads do not construct it.
  - `_renderUnauthorized()` and `_renderFlashError()` construct it only when rendering an administrative authorization error.
- Retained deploy-time PHP mtime refresh as cache hygiene and made the deploy header assertion describe the actual administrative-header regression.
- Corrected the restoration plan and `docs/VERSION.md` to replace the disproven stale-OPcache cause with the verified source-level call chain.
- Included the planned v1.0.48 byline timestamp clamp, Indexing API discipline changes, and homepage title/H1 cleanup already documented in `docs/VERSION.md`.

## Verification

| Check | Observed result |
|---|---|
| `php -l mod/mod_user/model/user.model.php` | No syntax errors |
| `bash -n tools/deploy_production.sh` | Passed |
| `i18n_guard`, code-separation, min-CSS, module-boundary, stop-quality gate | Passed; zero violations; stop decision `allow` |
| Production deploy | Completed; root health check returned HTTP 200 |
| `/tr/` deployment assertion | Passed: no `no-store`, epoch-1970 `Expires`, or `Pragma` header |
| Googlebot-style `HEAD /tr/` | HTTP/2 200; `Cache-Control: max-age=0`; current response `Expires`; no administrative cache headers |
| Browser homepage | `<title>` and hidden `<h1>` are `Amed Bülten — Diyarbakır, Ortadoğu ve dünyanın nabzını dinleyen hür yayıncılık.`; no duplicate ASCII prefix or parenthetical fallback |
| Browser legacy news page | Article ID 24 has `updated_at` earlier than `created_at`; the visible byline renders only `Yayınlanma Tarihi: 04.11.2026 12:47`, with no `Güncelleme Tarihi` |

## Deployment

Executed `bash tools/deploy_production.sh`. The production deployment script completed all four steps and its live `/tr/` header assertion passed.
