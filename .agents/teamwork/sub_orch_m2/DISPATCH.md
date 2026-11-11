# Dispatch: Milestone 2 Sub-Orchestrator

Implement Milestone 2: Shared HTTP Client & Editorial Gate Hardening per SCOPE.md and docs/plan/v1.0.63-kevanger-editorial-pipeline-remediation-and-enhancement-plan.md.
Follow the standard Iteration Loop (Explorers -> Worker -> Reviewers -> Challengers -> Auditor -> Gate).
State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/sub_orch_m2/
Original request: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/ORIGINAL_REQUEST.md
Project spec: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/orchestrator_1/PROJECT.md


## 2026-11-11T10:47:52Z
You are the Sub-Orchestrator for Milestone 2 (M2: Shared HTTP Client & Editorial Gate Hardening).
Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin
State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/sub_orch_m2/
MANDATORY: You MUST read the original request at:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/ORIGINAL_REQUEST.md
and your milestone scope document at:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/sub_orch_m2/SCOPE.md
and project specification at:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/orchestrator_1/PROJECT.md

Scope:
Features 13 through 23 (Shared HTTP Client & Editorial Gate Hardening).
Detailed requirements are in docs/plan/v1.0.63-kevanger-editorial-pipeline-remediation-and-enhancement-plan.md and the survey handoff at /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/explorer_survey_pipeline_2/handoff.md.

Execute the Iteration Loop:
1. Spawn Explorer(s) to formulate implementation blueprint.
2. Spawn Worker to implement code changes (with mandatory integrity warning) in owned files.
3. Spawn 2 Reviewers independently to verify code, tests, and syntax.
4. Spawn 2 Challengers to empirically test edge cases, regexes, and security controls.
5. Spawn Forensic Auditor (teamwork_auditor) to verify integrity.
6. Evaluate Gate in GATE_STATUS.md. All must pass (strict AND).
7. When gate passes, write handoff.md in your state folder and notify parent via send_message.

Owned files:
- .agents/skills/_shared/editorial_http.py
- .agents/skills/kevtemen/scripts/check_freshness.py
- .agents/skills/kevrast/scripts/verify_facts_and_links.py
