# Task Assignment: Explorer 1 (M3 Image Pipeline)

You are Explorer 1 for Milestone 3 (Image Pipeline).
Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin
State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/explorer_m3_1/

Read the following documents:
- /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/ORIGINAL_REQUEST.md
- /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/orchestrator_1/PROJECT.md
- /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/sub_orch_m3/SCOPE.md
- docs/plan/v1.0.63-kevanger-editorial-pipeline-remediation-and-enhancement-plan.md (Sections A3, B5, Phase 3)
- Target file: .agents/skills/kevanger/scripts/process_news_image.py

Your objective:
Investigate .agents/skills/kevanger/scripts/process_news_image.py and detail the exact implementation blueprint for:
1. `unwrap_cdn_image_url(url: str) -> str`: Unwrapping Next.js (`/_next/image?url=...`), `/_vercel/image`, WordPress Photon, Cloudflare before filtering/ranking. Deduplication by canonical unwrapped URL.
2. Separating `<article>` vs `<main>` DOM scoping and excluding secondary containers (`<aside>`, `<nav>`, `<footer>`, sidebar/ticker classes). Restricting `+600` hero bonus to primary article elements.
3. Capping `srcset` descriptor score at 3840w (e.g. capping at 1200w unless confirmed by img attributes), and boosting candidate score when matching `og:image` / `twitter:image` stem.
4. Implementing `--mode master` (default for `--from-article`): outputting unflattened, clean `>= 1200px` master image (WebP quality 90 or high-res PNG) for CMS multi-size generation without double lossy down/upscale. Retaining `--mode inline-16x9` (1280x720, <100KB) for secondary images.
5. Regional watermark heuristics (bottom corners, top-right).
6. Browser-grade HTTP headers for HTML and image fetching.

Write a complete, structured handoff report to `.agents/teamwork/explorer_m3_1/handoff.md`.
Report back when finished via send_message.


## 2026-11-11T12:10:23Z
You are Explorer 1 for Milestone 3 (Image Pipeline).
Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin
State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/explorer_m3_1/

MANDATORY: You MUST read the original user request at:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/ORIGINAL_REQUEST.md
and your dispatch instructions at:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/explorer_m3_1/DISPATCH.md
and the milestone scope at:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/sub_orch_m3/SCOPE.md
and the project spec at:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/orchestrator_1/PROJECT.md
and docs/plan/v1.0.63-kevanger-editorial-pipeline-remediation-and-enhancement-plan.md (Sections A3, B5, Phase 3).

Investigate .agents/skills/kevanger/scripts/process_news_image.py thoroughly:
1. `unwrap_cdn_image_url(url: str) -> str`: Unwrapping Next.js (/_next/image?url=...), /_vercel/image, WordPress Photon, Cloudflare before filtering/ranking. Deduplication by canonical unwrapped URL.
2. Separating <article> vs <main> DOM scoping and excluding secondary containers (<aside>, <nav>, <footer>, sidebar/ticker classes). Restricting +600 hero bonus to primary article elements.
3. Capping srcset descriptor score at 3840w (e.g. capping at 1200w unless confirmed by img attributes), and boosting candidate score when matching og:image / twitter:image stem.
4. Implementing --mode master (default for --from-article): outputting unflattened, clean >= 1200px master image (WebP quality 90 or high-res PNG) for CMS multi-size generation without double lossy down/upscale. Retaining --mode inline-16x9 (1280x720, <100KB) for secondary images.
5. Regional watermark heuristics (bottom corners, top-right).
6. Browser-grade HTTP headers for HTML and image fetching.

Write a complete, structured handoff report to .agents/teamwork/explorer_m3_1/handoff.md.
When finished, notify me via send_message.
