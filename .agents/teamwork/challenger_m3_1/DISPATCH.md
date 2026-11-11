# Task Assignment: Challenger 1 (Image Pipeline Empirical Stress Tester)

You are Challenger 1 for Milestone 3.
Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin
State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/challenger_m3_1/

Read the following documents:
- /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/ORIGINAL_REQUEST.md
- /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/orchestrator_1/PROJECT.md
- /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/sub_orch_m3/SCOPE.md
- Worker handoff: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/worker_m3_1/handoff.md
- Target: .agents/skills/kevanger/scripts/process_news_image.py

Your adversarial challenge objectives:
1. Empirically test `process_news_image.py`:
   - Test against Next.js fixture (`tests/e2e/fixtures/editorial/mock_nextjs_sidebar_article.html`) verifying:
     a) Unwrapping Next.js proxy URLs (`/_next/image?url=...`).
     b) Distinct images are NOT collapsed into one URL.
     c) Hero scoring restricts bonus to `<article>` and excludes `<aside>` / sidebar thumbnails.
     d) Responsiveness `srcset` score cap at 3840w (and 1200w for proxies without explicit width).
     e) `og:image` stem cross-anchoring (+2000 bonus) ensures the authentic hero photo wins over sidebar thumbnails.
2. Empirically test `--mode master` vs `--mode inline-16x9`:
   - Verify `--mode master` outputs clean `>= 1200px` width master image (WebP quality 90, <1.5 MB) preserving natural aspect ratio without center-cropping to 16:9.
   - Verify `--mode inline-16x9` center-crops to 16:9 and encodes <100 KB.
3. Test edge cases:
   - Malformed URLs, SSRF attempts (127.0.0.1, 169.254.169.254), nested CDN proxies.
4. Render a clear empirical verdict: APPROVE or REQUEST_CHANGES.

Write your report to .agents/teamwork/challenger_m3_1/handoff.md.
Notify me via send_message when finished.


## 2026-11-11T12:42:32Z
You are Challenger 1 for Milestone 3 (Image Pipeline Empirical Stress Tester).
Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin
State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/challenger_m3_1/

MANDATORY: You MUST read the original user request at:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/ORIGINAL_REQUEST.md
and your dispatch instructions at:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/challenger_m3_1/DISPATCH.md
and the milestone scope at:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/sub_orch_m3/SCOPE.md
and the project spec at:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/orchestrator_1/PROJECT.md
and the worker handoff at:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/worker_m3_1/handoff.md

Empirically test process_news_image.py:
1. Test against Next.js fixture (tests/e2e/fixtures/editorial/mock_nextjs_sidebar_article.html) verifying CDN unwrapping, non-collapsing candidate URLs, article vs sidebar scoping, srcset cap, and og:image stem cross-anchoring.
2. Empirically test --mode master vs --mode inline-16x9 (dimensions, quality, size limits).
3. Test edge cases (malformed URLs, SSRF attempts, nested CDN proxies).
Render a clear empirical verdict: APPROVE or REQUEST_CHANGES.
Write report to .agents/teamwork/challenger_m3_1/handoff.md and notify me via send_message when complete.
