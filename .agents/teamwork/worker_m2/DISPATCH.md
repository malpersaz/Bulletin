# Dispatch: Worker M2 (Implementation)

Task: Implement Milestone 2 (Features 13-23) per Explorer handoff blueprints and acceptance criteria in SCOPE.md.
State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/worker_m2/
Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin
Original request: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/ORIGINAL_REQUEST.md
Milestone scope: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/sub_orch_m2/SCOPE.md
Project spec: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/orchestrator_1/PROJECT.md

Explorer handoffs:
- /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/explorer_m2_1/handoff.md
- /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/explorer_m2_2/handoff.md
- /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/explorer_m2_3/handoff.md

Owned files:
- .agents/skills/_shared/editorial_http.py
- .agents/skills/kevtemen/scripts/check_freshness.py
- .agents/skills/kevrast/scripts/verify_facts_and_links.py
- .agents/skills/kevkolin/scripts/dossier.schema.json (schema addition for source_lang, source_anchors, role: product)

MANDATORY INTEGRITY WARNING:
DO NOT CHEAT. All implementations must be genuine. DO NOT hardcode test results, create dummy/facade implementations, or circumvent the intended task. A teamwork_auditor will independently verify your work. Integrity violations WILL be detected and your work WILL be rejected.


## 2026-11-11T11:02:20Z
You are Worker M2 (Editorial Infrastructure Engineer) for Milestone 2.
Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin
State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/worker_m2/

MANDATORY INTEGRITY WARNING:
DO NOT CHEAT. All implementations must be genuine. DO NOT hardcode test results, create dummy/facade implementations, or circumvent the intended task. A teamwork_auditor will independently verify your work. Integrity violations WILL be detected and your work WILL be rejected.

MANDATORY Context:
1. /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/ORIGINAL_REQUEST.md
2. /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/sub_orch_m2/SCOPE.md
3. /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/orchestrator_1/PROJECT.md
4. /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/worker_m2/DISPATCH.md
5. Explorer blueprints:
   - /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/explorer_m2_1/handoff.md
   - /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/explorer_m2_2/handoff.md
   - /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/explorer_m2_3/handoff.md

Your Owned Files:
- .agents/skills/_shared/editorial_http.py (CREATE)
- .agents/skills/kevtemen/scripts/check_freshness.py (MODIFY)
- .agents/skills/kevrast/scripts/verify_facts_and_links.py (MODIFY)
- .agents/skills/kevkolin/scripts/dossier.schema.json (MODIFY schema for source_lang, source_anchors, role: product)

Implementation Objectives:
1. Feature 13: Create `.agents/skills/_shared/editorial_http.py` following the complete blueprint in `explorer_m2_1/handoff.md`.
2. Features 14-18: Harden `.agents/skills/kevtemen/scripts/check_freshness.py` following `explorer_m2_2/handoff.md`.
3. Features 19-23: Harden `.agents/skills/kevrast/scripts/verify_facts_and_links.py` & `dossier.schema.json` following `explorer_m2_3/handoff.md`.
