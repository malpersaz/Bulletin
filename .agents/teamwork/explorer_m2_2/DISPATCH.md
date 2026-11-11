# Dispatch: Explorer M2-2 (Freshness Sentinel Hardening)
Task: Investigate `.agents/skills/kevtemen/scripts/check_freshness.py` and formulate complete implementation specification for Features 14-18.
Milestone: M2 (Features 14, 15, 16, 17, 18)
State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/explorer_m2_2/
Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin
Original request: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/ORIGINAL_REQUEST.md
Milestone scope: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/sub_orch_m2/SCOPE.md
Project spec: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/orchestrator_1/PROJECT.md
Reference plan: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/docs/plan/v1.0.63-kevanger-editorial-pipeline-remediation-and-enhancement-plan.md
Reference survey: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/explorer_survey_pipeline_2/handoff.md
Output: handoff.md in /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/explorer_m2_2/


## 2026-11-11T10:50:35Z
You are Explorer M2-2 (Freshness Sentinel Specialist) for Milestone 2.
Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin
State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/explorer_m2_2/

MANDATORY: First read:
1. /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/ORIGINAL_REQUEST.md
2. /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/sub_orch_m2/SCOPE.md
3. /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/orchestrator_1/PROJECT.md
4. /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/explorer_m2_2/DISPATCH.md
5. Relevant sections of docs/plan/v1.0.63-kevanger-editorial-pipeline-remediation-and-enhancement-plan.md (§A1, §A4, §A5)

Task:
Investigate `.agents/skills/kevtemen/scripts/check_freshness.py` and formulate the implementation blueprint for Features 14-18.

Requirements to specify in detail:
1. Feature 14: Integration of `editorial_http.py`:
   - Replace insecure `_SSL_CTX` (`CERT_NONE`) and unvalidated `urllib.request` with `fetch_html()` from `.agents/skills/_shared/editorial_http.py`.
2. Feature 15: ISO fractional seconds and UTC handling:
   - Inspect `RE_ISO` (lines 50-52) and `_from_iso_match()` / `_apply_tz()`.
   - Update regex to capture fractional seconds `(?:[.,]\d{1,9})?` without dropping timezone (`Z` or `[+-]\d{2}:?\d{2}`).
   - Ensure `2026-11-10T23:13:00.000Z` parses correctly to UTC and converts to Europe/Istanbul (`+03:00`) as `2026-11-11 02:13:00+03:00` (today).
3. Feature 16: Non-content tag stripping before date scanning:
   - Strip `<script>`, `<style>`, `<noscript>`, `<nav>`, `<header>`, `<footer>`, `<aside>` blocks before extracting dates from body text or `<time>` tags.
   - Separate JSON-LD extraction (`application/ld+json`), restricting to `@type` in `NewsArticle`, `Article`, `ReportageNewsArticle`, `BlogPosting`, `WebPage`.
4. Feature 17: Earliest main date priority & deduplication:
   - Priority 1: `<meta property="article:published_time">`, `<meta name="pubdate|publishdate|datePublished|DC.date.issued">`, and main-entity JSON-LD `datePublished`. Prefer EARLIEST datePublished (never dateModified or newest).
   - Priority 2: `<time datetime="...">` inside `<article>` (excluding `<aside>`/sidebars).
   - Priority 3: Explicit date-labeled visible bylines in article header.
   - Remove bare unanchored "Bugün" (`RE_TODAY`) unless accompanied by a time in byline zone.
   - URL deduplication in `--file` and `--dossier` modes: evaluate each normalized URL at most once, record `citation_count`.
5. Feature 18: Near-midnight diagnostics:
   - Emit structured attributes: `age_hours` (float), `is_yesterday_late_evening: bool` (`18 <= pub_dt.hour <= 23` on `today - 1 day`).

Write your comprehensive findings and implementation blueprint to:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/explorer_m2_2/handoff.md
Follow the Handoff Protocol (Observation, Logic Chain, Caveats, Conclusion, Verification Method).
When done, message me with your status.
