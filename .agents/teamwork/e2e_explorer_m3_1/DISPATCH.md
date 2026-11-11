# Dispatch: Explorer 1 (Milestone E2E-M3 - Tier 2 Boundary & Corner Cases: Features 1-12)

**Working Directory**: `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin`  
**State Folder**: `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/e2e_explorer_m3_1/`  
**Target Milestone**: `E2E-M3` (Tier 2 Boundary & Corner Cases)  
**Assigned Scope**: Features 1 through 12 in `PROJECT.md § Feature Inventory`  

## Mandatory Inputs to Read
1. `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/ORIGINAL_REQUEST.md` (MUST read first)
2. `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/orchestrator_1/PROJECT.md`
3. `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/e2e_orch_gen2/SCOPE.md`
4. `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/TEST_INFRA.md`
5. Existing Tier 1 tests in `tests/e2e/tier1/test_f01_*.py` through `test_f12_*.py` and test harness under `tests/e2e/harness/`

## Objective
Analyze boundary conditions, edge cases, error-prone inputs, limits, empty inputs, max sizes, invalid types, zero/negative values, and domain-specific extremes for Features 1-12.
Design concrete, detailed test blueprints (≥5 per feature, target ≥60 test cases) for:
- Feature 1: `McpCrudToolsBase::addEqFilter` & `addLikeSearchFilter` (empty strings, sql injection attempts in values, non-existent columns, null handling, multi-condition conjunction combinations, unicode search terms)
- Feature 2: `McpServer::resolveEnvironment` DB fetch (empty/null db results, missing config, failed DB connect array return, malformed records)
- Feature 3: `McpCrudToolsBase::getRecordAuditMeta` DB fetch & draft FAQs (zero drafts, non-existent table, recordStatus boundary cases, special chars in record IDs)
- Feature 4: `McpHeadlineTools` string argument fix (empty string name, whitespace name, boundary length strings, non-string coercion rejection, missing language code)
- Feature 5: `McpNewsTools` leaf category check (root='y', root='n', root='invalid', category_id=0, non-existent category_id, negative category_id)
- Feature 6: `McpNewsTools` mandatory `makeSeo()` (extreme unicode, trailing/leading special characters, consecutive dots/slashes, U+0307 combining dot above, empty title slugification)
- Feature 7: `McpNewsTools::getNews` enrichment (missing user/author record, deleted category, null author_fullname, non-existent news id=0 or -1, large int IDs)
- Feature 8: `McpNewsTools::checkDuplicateNews` (exact 48h boundary: 47h59m vs 48h01m, identical title vs slight whitespace differences, same category vs different category, null timestamps)
- Feature 9: `McpFaqTools::batchCreateFaqs` (empty batch 0 items, exact limit 25 items, overflow limit 26 items rejection, malformed items in batch, partial failure atomicity rollback)
- Feature 10: `McpFaqTools::listFaqModules` (empty mod_faq_mod table, invalid module names, pagination/limit boundaries)
- Feature 11: `McpFaqTools::listFaqs` `include_answer` (include_answer=true vs false vs missing, massive HTML answers, answers with special unicode)
- Feature 12: Nginx `.well-known` passthrough (missing trailing slash, sub-paths `/.well-known/acme-challenge/test`, dot-dot traversal attempts `/.well-known/../secret`, nonexistent well-known files returning 404 not 403/redirect)

## Output Requirements
Produce a comprehensive blueprint report at `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/e2e_explorer_m3_1/handoff.md`.
Document:
1. Feature-by-feature boundary analysis
2. Exact test case names, target file paths (`tests/e2e/tier2/test_f01_*.py` .. `test_f12_*.py`), inputs, expected outcomes, and assertion mechanisms
3. Opaque-box execution commands
4. Send a completion message when done. DO NOT modify any code.


## 2026-11-11T14:04:53Z
You are Explorer 1 for Milestone E2E-M3 (Tier 2 Boundary & Corner Cases: Features 1-12).
Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin
State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/e2e_explorer_m3_1/

Read your dispatch file at:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/e2e_explorer_m3_1/DISPATCH.md
and ORIGINAL_REQUEST.md at:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/ORIGINAL_REQUEST.md

Your role is read-only exploration and test case blueprint design for Features 1-12 under tests/e2e/tier2/.
Write your comprehensive blueprint report to:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/e2e_explorer_m3_1/handoff.md
DO NOT modify source code or tests.
When complete, notify me with send_message.
