## 2026-11-11T12:24:51Z
You are Explorer 3 for Milestone 4 (Radar, SEO Alignment, Skill Docs & Rule Fixes).
State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/explorer_m4_3/
Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin

MANDATORY FIRST STEP:
Read ORIGINAL_REQUEST.md at:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/ORIGINAL_REQUEST.md
Also read:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/sub_orch_m4/SCOPE.md
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/orchestrator_1/PROJECT.md
and docs/plan/v1.0.63-kevanger-editorial-pipeline-remediation-and-enhancement-plan.md (§A1, §A2, §A5, §B5, §B6).

Your specific investigation area:
Focus on Skill Documentation synchronization and AGENTS.md rule citation fixes:
1. Review all 5 skill documents:
   - `.agents/skills/kevanger/SKILL.md`
   - `.agents/skills/kevtemen/SKILL.md`
   - `.agents/skills/kevkolin/SKILL.md`
   - `.agents/skills/kevrast/SKILL.md`
   - `.agents/skills/kevseo/SKILL.md`
2. Document required updates:
   - Explicit two-pass freshness gate order: Stage 0 (seed URL pre-gate) before research, Stage 2 (dossier gate) after dossier creation.
   - Near-midnight recovery protocol (bounded search for same-day official follow-ups using `source_catalog.py --scan-today`).
   - Image pipeline `--mode master` (clean >=1200px master) and native `image_alt`/`image_credit` columns.
   - Swarm CLI wrapper usage (`tools/mcp_cli.py`).
   - Rule citation fix in `kevseo/SKILL.md`: update "AGENTS.md Rule 2" -> "AGENTS.md Rule 1".
3. Review rule citation fixes in:
   - `tools/code_separation_guard.py`: update "Rule 2" -> "Rule 1".
   - `docs/CODE_QUALITY_AGENT_STRATEGY.md`: update "Rule 7" -> "Rule 6" for English code/comments.
4. Provide a concrete, line-by-line / section-by-section specification for Worker. Write your report to `handoff.md` in your state folder.
