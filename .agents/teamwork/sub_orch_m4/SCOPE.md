# Scope: Milestone 4 — Radar, SEO Alignment, Skill Docs & Rule Fixes

## Scope Overview
Implement Phase 4 enhancements, documentation, and rule citation corrections:
1. `source_catalog.py --scan-today`:
   - Implement `--scan-today` scanning RSS/Atom/sitemap feeds of verified sources for same-day local stories.
   - Filter entries matching today's date in Europe/Istanbul (+03:00).
   - Integrate with `editorial_http` for verified TLS and request caching.
2. `audit_seo.py` Alignment:
   - Align rubric with `tools/audit_content_seo.py --profile editorial`.
   - Enforce headline length 40–70 characters.
   - Enforce summary length 90–120 characters.
   - Enforce meta description synchronization (`description == summary`).
   - Enforce heading hierarchy (>= 3 `<h2>`, no skipping heading levels `h2 -> h4`).
3. Skill Documentation Synchronization:
   - Synchronize `kevanger/SKILL.md`, `kevtemen/SKILL.md`, `kevkolin/SKILL.md`, `kevrast/SKILL.md`, and `kevseo/SKILL.md`.
   - Clarify two-pass freshness gate order (rough check on discovered link -> full check on compiled dossier).
   - Clarify near-midnight recovery protocol (bounded search for same-day official follow-ups).
4. Correct AGENTS.md Rule Citations:
   - Fix citations in `kevseo/SKILL.md` and `audit_seo.py` (Rule 2 -> Rule 1 for code separation).
   - Fix citations in `tools/code_separation_guard.py` (Rule 2 -> Rule 1).
   - Fix citations in `docs/CODE_QUALITY_AGENT_STRATEGY.md` (Rule 7 -> Rule 6 for English code/comments).

## Files Owned
- .agents/skills/kevkolin/scripts/source_catalog.py
- .agents/skills/kevseo/scripts/audit_seo.py
- .agents/skills/kevanger/SKILL.md
- .agents/skills/kevtemen/SKILL.md
- .agents/skills/kevkolin/SKILL.md
- .agents/skills/kevrast/SKILL.md
- .agents/skills/kevseo/SKILL.md
- tools/code_separation_guard.py
- docs/CODE_QUALITY_AGENT_STRATEGY.md

## Status
**DONE** (Gate Passed: Reviewer 1 APPROVE, Reviewer 2 APPROVE, Challenger 1 APPROVE, Challenger 2 APPROVE, Forensic Auditor CLEAN)

## Acceptance Criteria
- [x] `source_catalog.py --scan-today` correctly parses RSS/Atom feeds and filters today's entries.
- [x] `audit_seo.py` aligns with `audit_content_seo.py` and enforces strict editorial SEO rubrics.
- [x] All SKILL.md docs accurately reflect editorial workflow and correct AGENTS.md rule numbers.
- [x] `code_separation_guard.py` and `docs/CODE_QUALITY_AGENT_STRATEGY.md` cite correct AGENTS.md rule numbers.
- [x] All Python files pass `python3 -m py_compile`.
- [x] All quality guards pass (code separation, module boundary, i18n).
