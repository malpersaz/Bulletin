# Dispatch: Reviewer M2-2 (Security, Regexes & Robustness)

Task: Independently review security, regexes, Turkish morphology, and gate robustness for Milestone 2 (Features 13-23).
State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/reviewer_m2_2/
Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin
Original request: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/ORIGINAL_REQUEST.md
Milestone scope: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/sub_orch_m2/SCOPE.md
Project spec: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/orchestrator_1/PROJECT.md
Worker handoff: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/worker_m2/handoff.md

Files to inspect:
- .agents/skills/_shared/editorial_http.py
- .agents/skills/kevtemen/scripts/check_freshness.py
- .agents/skills/kevrast/scripts/verify_facts_and_links.py
- .agents/skills/kevkolin/scripts/dossier.schema.json

Review Focus:
- SSRF prevention completeness (RFC 1918, CGNAT 100.64.0.0/10, ULA fc00::/7, metadata, loopback).
- Regex edge cases in `check_freshness.py` and `verify_facts_and_links.py`.
- Turkish dotted/dotless I case-folding (`tr_fold`) and institutional stemming.
- Verification tests: run `python3 -m py_compile`, run tests, run quality guards (`code_separation_guard.py`, `module_boundary_guard.py --all`, `i18n_guard.py`).
- Verdict: APPROVE or REQUEST_CHANGES.
Output: handoff.md in /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/reviewer_m2_2/


## 2026-11-11T11:19:29Z
You are Reviewer M2-2 (Security, Regexes & Robustness) for Milestone 2.
Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin
State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/reviewer_m2_2/

MANDATORY Context:
1. /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/ORIGINAL_REQUEST.md
2. /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/sub_orch_m2/SCOPE.md
3. /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/orchestrator_1/PROJECT.md
4. /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/worker_m2/handoff.md
5. /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/reviewer_m2_2/DISPATCH.md

Files to inspect:
- .agents/skills/_shared/editorial_http.py
- .agents/skills/kevtemen/scripts/check_freshness.py
- .agents/skills/kevrast/scripts/verify_facts_and_links.py
- .agents/skills/kevkolin/scripts/dossier.schema.json

Tasks:
1. Review SSRF protection completeness (IPv4/IPv6 loopback, RFC 1918, CGNAT 100.64.0.0/10, cloud metadata 169.254.169.254, ULA fc00::/7, non-HTTP schemes).
2. Review regex correctness in `check_freshness.py` (fractional ISO parsing, non-content tag stripping, datePublished priority, removal of unanchored "Bugün").
3. Review `verify_facts_and_links.py` (all-paragraph evaluation, elimination of substring matching, vendor single-segment allowlist, two-sided cross-language anchoring, Turkish dotted/dotless I folding, institutional stemmer).
4. Run verification tests and quality guards (`python3 tools/code_separation_guard.py`, `python3 tools/module_boundary_guard.py --all`, `python3 tools/i18n_guard.py`).
5. Render verdict: APPROVE or REQUEST_CHANGES.
Write your report to: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/reviewer_m2_2/handoff.md
When done, message me with your verdict and findings.
