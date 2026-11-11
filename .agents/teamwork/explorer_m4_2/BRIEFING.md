# BRIEFING — 2026-11-11T12:32:00Z

## Mission
Investigate `.agents/skills/kevseo/scripts/audit_seo.py` and its alignment with `tools/audit_content_seo.py --profile editorial`, audit all rubric checks, citation fixes, CLI flags, and produce a concrete worker-ready implementation spec.

## 🔒 My Identity
- Archetype: explorer
- Roles: read-only investigation, analysis, synthesis, handoff report
- Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin
- State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/explorer_m4_2/
- Original parent: ce849b95-b4a4-4bc4-8516-ced615e400d4
- Milestone: Milestone 4 (Radar, SEO Alignment, Skill Docs & Rule Fixes)

## 🔒 Key Constraints
- Read-only investigation — do NOT implement in project source code
- Files in `.agents/teamwork/` must only be agent metadata
- Strictly follow Handoff Protocol (5 components)
- Output must be actionable, detailed, and directly usable by the Worker

## Current Parent
- Conversation ID: ce849b95-b4a4-4bc4-8516-ced615e400d4
- Updated: 2026-11-11T12:32:00Z

## Investigation State
- **Explored paths**:
  - `ORIGINAL_REQUEST.md`, `SCOPE.md`, `PROJECT.md`, `TEST_INFRA.md`
  - `docs/plan/v1.0.63-kevanger-editorial-pipeline-remediation-and-enhancement-plan.md` (§B6)
  - `.agents/skills/kevseo/scripts/audit_seo.py`
  - `tools/audit_content_seo.py`
  - `.agents/skills/kevseo/SKILL.md`
  - `tools/verify_news_pipeline.sh`
  - `tools/code_separation_guard.py`
  - `docs/CODE_QUALITY_AGENT_STRATEGY.md`
  - `mod/mod_news/validator/add.validator.php`
- **Key findings**:
  1. `audit_seo.py` currently allows completely empty `--headline` and `--summary` to exit 0 because `has_failed_headline` and `has_failed_deck` are only checked if the string is non-empty.
  2. `audit_seo.py` completely lacks `--description` and dual-field synchronization (`description == summary`, 90–120 chars).
  3. `audit_seo.py` treats `< 3 <h2>` headings as non-blocking `WARNING` or passes 1–2 `<h2>` silently without issue; `audit_content_seo.py --profile editorial` requires `>= 3 <h2>` for full credit.
  4. `audit_seo.py` does not check for heading level jumps (e.g. `<h2>` -> `<h4>` or first heading `> <h2>`).
  5. `audit_seo.py` lacks paragraph length checks (warning on > 5 sentences or > 90 words).
  6. `audit_seo.py` line 63 and `kevseo/SKILL.md` line 39 cite "AGENTS.md Rule 2" instead of "AGENTS.md Rule 1".
  7. `code_separation_guard.py` line 6 cites "AGENTS.md Rule 2" instead of "AGENTS.md Rule 1".
  8. `CODE_QUALITY_AGENT_STRATEGY.md` lines 16 & 20 cite "AGENTS.md Rule 7" instead of "AGENTS.md Rule 6".
  9. `mod_news` validators cite "Rule 10" instead of "Rule 9".
- **Unexplored areas**: None within M4 scope. Investigation complete.

## Key Decisions Made
- Formulated exact regexes and algorithms for sentence and word counting in Turkish editorial prose.
- Formulated exact heading hierarchy jump detector (`curr > prev + 1` and `headings[0] > 2`).
- Formulated exact `--strict-editorial` enforcement model that preserves backwards compatibility for snippet auditing in non-strict mode while strictly enforcing editorial invariants in pipeline mode.
- Designed comprehensive test suite specification for Worker.

## Artifact Index
- `.agents/teamwork/explorer_m4_2/DISPATCH.md` — Incoming task log
- `.agents/teamwork/explorer_m4_2/progress.md` — Liveness heartbeat
- `.agents/teamwork/explorer_m4_2/BRIEFING.md` — Situational awareness
- `.agents/teamwork/explorer_m4_2/handoff.md` — 5-component handoff report for Worker
