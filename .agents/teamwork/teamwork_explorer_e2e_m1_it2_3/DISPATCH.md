# Task Assignment: Explorer 3 (Iteration 2) — Assertion Utilities Hardening

You are an exploration agent (`teamwork_explorer`).
State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_explorer_e2e_m1_it2_3/
Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin

MANDATORY READING:
You MUST read the following files before starting:
1. /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/ORIGINAL_REQUEST.md
2. /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/orchestrator_1/PROJECT.md
3. /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/e2e_orch/SCOPE.md
4. /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/TEST_INFRA.md
5. /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_challenger_e2e_m1_2/handoff.md

Objective:
Investigate and formulate the fix strategy for domain assertion utilities in `tests/e2e/harness/assertions.py` found by Challenger 2:
1. `assert_no_inline_styles_or_scripts`: Currently blanket-rejects all `<script>` tags, causing false positives on valid `<script type="application/ld+json">` (required for Google News Article SEO schemas in Feature 28) and external `<script src="...">`. Recommend the exact regex and logic to permit JSON-LD and external src while strictly forbidding inline JavaScript logic/handlers.
2. `assert_html_tag_present`: Fix attribute quote matching regex so `>` inside attribute values doesn't truncate tag parsing. Ensure exact attribute matching instead of loose substring matching.
3. `assert_date_is_today`: Support timezone offsets on ISO date strings (`+03:00`, `Z`) and tz-aware `datetime` objects, comparing against `Europe/Istanbul` current date.

Scope Boundaries:
- READ-ONLY investigation. Do NOT modify source code or tests. Recommend exact fix strategy.

Output:
Write your report to:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_explorer_e2e_m1_it2_3/handoff.md
Update progress.md and send message when complete.

## 2026-11-11T11:28:23Z
Read your assignment at /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_explorer_e2e_m1_it2_3/DISPATCH.md.
MANDATORY: Read /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/ORIGINAL_REQUEST.md before starting.
Investigate and formulate the fix strategy for domain assertion utilities in assertions.py found by Challenger 2.
Write your report to /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_explorer_e2e_m1_it2_3/handoff.md.
