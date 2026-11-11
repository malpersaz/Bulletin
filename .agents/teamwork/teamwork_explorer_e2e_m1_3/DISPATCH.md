# Task Assignment: Explorer for Test Architecture & Runner Design

You are an exploration agent (`teamwork_explorer`).
State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_explorer_e2e_m1_3/
Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin

MANDATORY READING:
You MUST read the following files before starting:
1. /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/ORIGINAL_REQUEST.md
2. /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/orchestrator_1/PROJECT.md
3. /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/docs/plan/v1.0.63-kevanger-editorial-pipeline-remediation-and-enhancement-plan.md
4. /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/e2e_orch/SCOPE.md

Objective:
Architect the E2E Test Suite and Runner:
1. Design the unified test runner architecture (`python3 tests/e2e/run_e2e_tests.py` or similar) supporting `--tier 1|2|3|4|all`, `--feature <id>`, and clear TAP/JUnit/JSON summary output.
2. Design test directory layout (`tests/e2e/tier1/`, `tests/e2e/tier2/`, `tests/e2e/tier3/`, `tests/e2e/tier4/`, `tests/e2e/harness/`, `tests/e2e/fixtures/`).
3. Define how opaque-box tests interact with components: CLI invocation of scripts (`check_freshness.py`, `verify_facts_and_links.py`, `process_news_image.py`, `source_catalog.py`, `audit_seo.py`, `mcp_cli.py`), PHP CLI test scripts for MCP tools and ORM, and template/guard validators.
4. Prepare the complete blueprint and draft content for `TEST_INFRA.md` per Project Pattern Dual Track specifications.

Scope Boundaries:
- READ-ONLY investigation and architectural design. Do NOT write or modify source code or tests.

Output:
Write your architecture proposal and draft TEST_INFRA.md to:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_explorer_e2e_m1_3/handoff.md
Update your progress.md periodically.
Send a message when complete with summary and report path.


## 2026-11-11T10:50:19Z
Read your assignment at /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_explorer_e2e_m1_3/DISPATCH.md.
MANDATORY: Read /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/ORIGINAL_REQUEST.md before starting.
Architect the unified E2E test runner and test directory layout, and draft the comprehensive TEST_INFRA.md document per Project Pattern requirements.
Write your handoff report to /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_explorer_e2e_m1_3/handoff.md.
