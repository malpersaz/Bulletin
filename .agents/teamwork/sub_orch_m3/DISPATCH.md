# Dispatch: Milestone 3 Sub-Orchestrator

Implement Milestone 3: Image Pipeline, mod_news Media Credits & Swarm CLI Wrapper per SCOPE.md and docs/plan/v1.0.63-kevanger-editorial-pipeline-remediation-and-enhancement-plan.md.
Follow the standard Iteration Loop (Explorers -> Worker -> Reviewers -> Challengers -> Auditor -> Gate).
State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/sub_orch_m3/
Original request: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/ORIGINAL_REQUEST.md
Project spec: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/orchestrator_1/PROJECT.md


## 2026-11-11T12:07:40Z
You are the Sub-Orchestrator for Milestone 3 (M3: Image Pipeline, mod_news Media Credits & Swarm CLI Wrapper).
Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin
State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/sub_orch_m3/
MANDATORY: You MUST read the original request at:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/ORIGINAL_REQUEST.md
and your milestone scope document at:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/sub_orch_m3/SCOPE.md
and project specification at:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/orchestrator_1/PROJECT.md

Scope:
Features 24 through 29 (Image Pipeline, mod_news Media Credits & Swarm CLI Wrapper).
Detailed requirements are in docs/plan/v1.0.63-kevanger-editorial-pipeline-remediation-and-enhancement-plan.md and the survey handoff at /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/explorer_survey_pipeline_2/handoff.md.

Execute the Iteration Loop:
1. Spawn Explorer(s) to formulate implementation blueprint.
2. Spawn Worker to implement code changes (with mandatory integrity warning) in owned files.
3. Spawn 2 Reviewers independently to verify code, templates, and syntax.
4. Spawn 2 Challengers to empirically test unwrapping, srcset cap, hero scoping, master mode, schema/view queries, and CLI bridge.
5. Spawn Forensic Auditor (teamwork_auditor) to verify integrity.
6. Evaluate Gate in GATE_STATUS.md. All must pass (strict AND).
7. When gate passes, write handoff.md in your state folder and notify parent via send_message.

Owned files:
- .agents/skills/kevanger/scripts/process_news_image.py
- mod/mod_news/mod_news.sql
- mod/mod_news/model/mod_news_model.php
- mod/mod_news/model/newses_model.php
- mod/mod_news/controller/index.php
- mod/mod_news/validator/add.validator.php
- mod/mod_news/validator/edit.validator.php
- templates/amed/index/mod_news/news.twig
- templates/admin/mod_news/edit.twig
- templates/admin/mod_news/add.twig
- tools/mcp_cli.py

## 2026-11-11T12:22:47Z
**Context**: Milestone 3 Acceleration Directive
**Content**: The user is facing tight weekly and 5-hour quota limits. Please accelerate completion safely: move immediately from explorer handoffs to Worker implementation, and execute a focused, rapid gate check.
**Action**: Synthesize explorer reports immediately upon explorer_m3_2 completion, dispatch the Worker with the unified blueprint, run verification, and deliver handoff promptly.

## 2026-11-11T13:00:39Z
**Context**: Milestone 3 Iteration 2
**Content**: Milestone 4 has completed with Gate PASS. Milestone 3 is the only remaining prerequisite for the final release milestone (M5). Please proceed swiftly with the mcp_cli.py fix and re-verification so we can proceed to release.
**Action**: Finalize Iteration 2, evaluate Gate, and hand off.
