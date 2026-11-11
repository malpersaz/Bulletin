# BRIEFING — 2026-11-11T12:45:00Z

## Mission
Implement Milestone 4: Source Catalog Radar (`source_catalog.py`), SEO Auditor Alignment (`audit_seo.py`), Skill Documentation Sync (`kevanger`, `kevtemen`, `kevkolin`, `kevrast`, `kevseo`), and AGENTS.md Rule Reference Fixes.

## 🔒 My Identity
- Archetype: worker
- Roles: implementer, qa, specialist
- Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin
- State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/worker_m4_1/
- Original parent: ce849b95-b4a4-4bc4-8516-ced615e400d4
- Milestone: Milestone 4 (Radar, SEO Alignment, Skill Docs & Rule Fixes)

## 🔒 Key Constraints
- Genuine implementation only, no cheating or hardcoding.
- Modifying only exclusively owned files:
  - .agents/skills/kevkolin/scripts/source_catalog.py
  - .agents/skills/kevseo/scripts/audit_seo.py
  - .agents/skills/kevanger/SKILL.md
  - .agents/skills/kevtemen/SKILL.md
  - .agents/skills/kevkolin/SKILL.md
  - .agents/skills/kevrast/SKILL.md
  - .agents/skills/kevseo/SKILL.md
  - tools/code_separation_guard.py
  - docs/CODE_QUALITY_AGENT_STRATEGY.md
- Admin mobile-first phone only, strict code separation, no non-English in docs/code.

## Current Parent
- Conversation ID: ce849b95-b4a4-4bc4-8516-ced615e400d4
- Updated: 2026-11-11T12:45:00Z

## Task Summary
- **What to build**:
  1. `source_catalog.py`: Added RSS/Atom/sitemap feeds, implemented `parse_entry_datetime()`, `scan_single_feed()`, `tr_fold()`, and `--scan-today` CLI options with Europe/Istanbul (+03:00) today filtering and near-midnight recovery protocol.
  2. `audit_seo.py`: Updated `audit_headline_and_deck()` for `--description` & summary parity, updated `audit_html_architecture()` for heading hierarchy (>=3 h2 in strict), level jumps, paragraph density warnings, and Rule 1 citations.
  3. Skill Docs: Synchronized `kevanger`, `kevtemen`, `kevkolin`, `kevrast`, and `kevseo` SKILL.md docs with two-pass freshness gate, near-midnight recovery, media credits schema, and mcp_cli.py.
  4. Rule Citation Fixes: Fixed Rule 2 -> Rule 1 in `tools/code_separation_guard.py`, `kevseo/SKILL.md`, `audit_seo.py`; fixed Rule 7 -> Rule 6 in `docs/CODE_QUALITY_AGENT_STRATEGY.md`.
- **Success criteria**:
  - Python files compile cleanly. (Verified)
  - All unit/behavioral tests pass. (Verified)
  - Verification guards (code separation, module boundary, i18n) pass. (Verified)
- **Interface contracts**: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/sub_orch_m4/SCOPE.md
- **Code layout**: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/orchestrator_1/PROJECT.md

## Key Decisions Made
- Added verified feed endpoints to `VERIFIED_SOURCES` in `source_catalog.py` and implemented XML namespace-agnostic feed scanner with RFC 2822 & ISO 8601 parsing into `Europe/Istanbul`.
- Integrated `--strict-editorial` flag into `audit_seo.py` ensuring backwards compatibility for standalone snippet testing while enforcing all strict editorial checks when requested.
- Preserved strict code separation across all documentation and templates.

## Artifact Index
- `.agents/skills/kevkolin/scripts/source_catalog.py`
- `.agents/skills/kevseo/scripts/audit_seo.py`
- `.agents/skills/kevanger/SKILL.md`
- `.agents/skills/kevtemen/SKILL.md`
- `.agents/skills/kevkolin/SKILL.md`
- `.agents/skills/kevrast/SKILL.md`
- `.agents/skills/kevseo/SKILL.md`
- `tools/code_separation_guard.py`
- `docs/CODE_QUALITY_AGENT_STRATEGY.md`

## Change Tracker
- **Files modified**:
  - `.agents/skills/kevkolin/scripts/source_catalog.py`: Added RSS/Atom feeds, `scan_single_feed`, `tr_fold`, `parse_entry_datetime`, `--scan-today`, `--seed-url` near-midnight recovery, and `--init-dossier`.
  - `.agents/skills/kevseo/scripts/audit_seo.py`: Added `--description` dual sync, heading hierarchy (>=3 h2 in strict), level jump check, paragraph density warnings, and Rule 1 citation.
  - `.agents/skills/kevanger/SKILL.md`: Documented two-pass gate, near-midnight recovery, `--mode master`, native `image_alt`/`image_credit`, and `tools/mcp_cli.py`.
  - `.agents/skills/kevtemen/SKILL.md`: Documented two-pass freshness gate order (Stage 0 -> Stage 2) and structured near-midnight diagnostics.
  - `.agents/skills/kevkolin/SKILL.md`: Added Local News Radar (`--scan-today`), near-midnight recovery, and `source_lang`/`source_anchors` documentation.
  - `.agents/skills/kevrast/SKILL.md`: Added `--explain` diagnostics documentation, shared `editorial_http` transport, and claim anchoring details.
  - `.agents/skills/kevseo/SKILL.md`: Aligned with editorial rubric (>=3 h2, description sync, paragraph density) and fixed Rule 2 -> Rule 1.
  - `tools/code_separation_guard.py`: Fixed AGENTS.md Rule 2 -> Rule 1.
  - `docs/CODE_QUALITY_AGENT_STRATEGY.md`: Fixed AGENTS.md Rule 7 -> Rule 6.
- **Build status**: All compile and test commands pass 100%.
- **Pending issues**: None.

## Quality Status
- **Build/test result**: All 7 behavioral tests for `audit_seo.py` and 6 verification commands for `source_catalog.py` passed.
- **Lint status**: Zero violations across code separation, i18n guard, and module boundary guard.
- **Tests added/modified**: Full suite of test scenarios verified.

## Loaded Skills
- None explicitly loaded.
