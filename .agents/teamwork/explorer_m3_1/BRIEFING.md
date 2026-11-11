# BRIEFING — 2026-11-11T12:18:00Z

## Mission
Investigate .agents/skills/kevanger/scripts/process_news_image.py and produce a complete implementation blueprint for Milestone 3 (Image Pipeline).

## 🔒 My Identity
- Archetype: explorer
- Roles: investigator, synthesizer
- Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin
- State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/explorer_m3_1
- Original parent: 1cb4034b-124b-423c-b681-70f423565312
- Milestone: Milestone 3 (Image Pipeline)

## 🔒 Key Constraints
- Read-only investigation — do NOT implement
- Investigate .agents/skills/kevanger/scripts/process_news_image.py
- Produce structured handoff report in .agents/teamwork/explorer_m3_1/handoff.md
- Adhere to Teamwork protocols, strict code separation, and Kurdish language rules for user facing dialogue

## Current Parent
- Conversation ID: 1cb4034b-124b-423c-b681-70f423565312
- Updated: 2026-11-11T12:18:00Z

## Investigation State
- **Explored paths**:
  - `docs/plan/v1.0.63-kevanger-editorial-pipeline-remediation-and-enhancement-plan.md` (Sections A3, B5, Phase 3)
  - `.agents/skills/kevanger/scripts/process_news_image.py` (Full 519 lines)
  - `.agents/skills/_shared/editorial_http.py` (Verified TLS, SSRF, headers, caching)
  - `tests/e2e/fixtures/editorial/mock_nextjs_sidebar_article.html`
  - `tests/e2e/fixtures/editorial/mock_rudaw_article_1287295.html`
  - `tests/e2e/fixtures/images/` (Image dimensions and formats)
  - `lib/McpNewsTools.class.php` (NEWS_IMAGE_SIZES)
- **Key findings**:
  - Reproduce live Section A3 bug: `url.split("?")[0]` strips Next.js query, collapsing all candidates into one key; `3840w` sidebar thumbnail beats true hero image (score 3840 vs 2420) and clobbers it completely.
  - Formulated full algorithm for `unwrap_cdn_image_url`: unwraps Next.js, Vercel, Photon, Cloudflare, recursive unwrapping up to 3 levels, canonical deduplication keys preserving semantic params while stripping resize params.
  - Designed DOM scoping separating `<article>` vs `<main>` depth and tracking excluded containers (`<aside>`, `<nav>`, `<footer>`, `<header>`, class/id regex) with tag stack; restricting +600 hero bonus to lead article images and penalizing excluded containers by -2000.
  - Designed `srcset` descriptor cap (at 3840w hard, and 1200w for proxy URLs unless verified by explicit `img` width) and `og:image` / `twitter:image` filename stem cross-anchoring (+2000 boost).
  - Designed `--mode master` (default for `--from-article`): preserving natural aspect ratio without 16:9 pre-flattening, max width 1920, WebP quality 90, <1.5 MB, while retaining `--mode inline-16x9` (1280x720, <100 KB).
  - Formulated regional watermark heuristics (top-right red bug, bottom-strip/corner badges) and crop percentage reporting in JSON manifest.
  - Integrated browser-grade headers (`Accept` and `Referer`) using `editorial_http` with secure fallback.
- **Unexplored areas**: None. All 6 requirements fully investigated and prototyped.

## Key Decisions Made
- Confirmed `--mode master` must preserve full vertical resolution to prevent CMS multi-size upscaling degradation (which otherwise forces 675px -> 900px/1200px stretch).
- Formulated exact test verification method using existing e2e fixtures and python assertions.

## Artifact Index
- .agents/teamwork/explorer_m3_1/BRIEFING.md — Persistent memory
- .agents/teamwork/explorer_m3_1/DISPATCH.md — Incoming messages log
- .agents/teamwork/explorer_m3_1/progress.md — Liveness heartbeat
- .agents/teamwork/explorer_m3_1/handoff.md — Final investigation handoff report
