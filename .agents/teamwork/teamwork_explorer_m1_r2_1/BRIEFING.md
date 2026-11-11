# BRIEFING — 2026-11-11T11:41:00Z

## Mission
Investigate Defect 1: Catastrophic WHERE Clause Clobbering in batchCreateFaqs (lib/McpFaqTools.class.php), formulate exact fix strategy and test design.

## 🔒 My Identity
- Archetype: explorer
- Roles: Read-only investigation, analyze problems, synthesize findings, produce structured reports
- Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin
- State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_explorer_m1_r2_1/
- Original parent: 901f7801-31c7-4c08-be51-94ed6f17c21d
- Milestone: Milestone 1, Iteration 2 (Defect 1 Investigation)

## 🔒 Key Constraints
- Read-only investigation — do NOT implement
- Analyze Defect 1: Catastrophic WHERE clause clobbering in McpFaqTools.class.php
- Formulate exact fix strategy
- Design automated smoke test in tasks/mcp_smoke.php
- Handoff report in state folder

## Current Parent
- Conversation ID: 901f7801-31c7-4c08-be51-94ed6f17c21d
- Updated: 2026-11-11T11:33:34Z

## Investigation State
- **Explored paths**:
  - `lib/McpFaqTools.class.php` (lines 565–615, 670–740)
  - `lib/Database/Database.php` (lines 520–545, 603–645)
  - `lib/Orm/Orm.php` (lines 56–125)
  - `lib/Orm/Obuilder.php` (lines 40–90)
  - `lib/McpCrudToolsBase.class.php` (lines 355–425)
  - `tasks/mcp_smoke.php` (lines 760–905)
  - `tools/i18n_guard.py`, `tools/code_separation_guard.py`, `tools/module_boundary_guard.py`
- **Key findings**:
  - Replicated Defect 1: calling `$draftModel->where_status('n')` after `$draftModel->where_mod_name_and_vid_and_lang(...)` overwrites `$this->_where` with `status = 'n'`. Calling `$draftModel->delete()` executes `DELETE FROM mod_faq WHERE status = 'n'`, destroying all draft FAQs across the entire system.
  - Tested Fix Strategy 1 (`addEqFilter` 4x) and Strategy 2 (`addWhere("status = 'n'")`). Verified in runtime that Strategy 1 generates `DELETE FROM mod_faq WHERE mod_name = 'mod_news' AND vid = 2 AND lang = 'tr' AND status = 'n'`, perfectly isolating deletion.
  - Verified test architecture in `tasks/mcp_smoke.php`: inserting a sentinel draft row (`vid = 999999`), invoking `batch_create_faqs` with `replace_existing_drafts: true`, asserting sentinel draft survived while target entity drafts were replaced.
- **Unexplored areas**: Peer defect (makeSeo slashes) assigned to Explorer M1-R2-2.

## Key Decisions Made
- Recommend Strategy 1 (`$this->addEqFilter`) as primary fix because it is the established standard across `McpCrudToolsBase` and `applyFaqFilters()` (lines 676–687), provides SQL escaping and typed integer formatting, and avoids dynamic regex underscore parsing bugs.
- Document Strategy 2 (`addWhere("status = 'n'")`) as a valid 1-line alternative.
- Formulate test design for `tasks/mcp_smoke.php` ensuring full English variable naming to comply with `tools/i18n_guard.py`.

## Artifact Index
- /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_explorer_m1_r2_1/DISPATCH.md — Incoming dispatch
- /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_explorer_m1_r2_1/BRIEFING.md — Working memory
- /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_explorer_m1_r2_1/progress.md — Heartbeat and progress
- /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_explorer_m1_r2_1/handoff.md — Final handoff report
