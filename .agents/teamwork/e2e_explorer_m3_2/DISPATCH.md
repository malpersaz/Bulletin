# Dispatch: Explorer 2 (Milestone E2E-M3 - Tier 2 Boundary & Corner Cases: Features 13-23)

**Working Directory**: `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin`  
**State Folder**: `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/e2e_explorer_m3_2/`  
**Target Milestone**: `E2E-M3` (Tier 2 Boundary & Corner Cases)  
**Assigned Scope**: Features 13 through 23 in `PROJECT.md § Feature Inventory`  

## Mandatory Inputs to Read
1. `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/ORIGINAL_REQUEST.md` (MUST read first)
2. `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/orchestrator_1/PROJECT.md`
3. `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/e2e_orch_gen2/SCOPE.md`
4. `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/TEST_INFRA.md`
5. Existing Tier 1 tests in `tests/e2e/tier1/test_f13_*.py` through `test_f23_*.py` and test harness under `tests/e2e/harness/`

## Objective
Analyze boundary conditions, edge cases, error-prone inputs, limits, empty inputs, max sizes, invalid types, zero/negative values, and domain-specific extremes for Features 13-23.
Design concrete, detailed test blueprints (≥5 per feature, target ≥55 test cases) for:
- Feature 13: Shared HTTP Client `editorial_http.py` (malformed URLs, RFC1918 loopback / IPv6 private IP SSRF blocking, DNS rebinding, redirect loops >5, Retry-After header with large seconds vs dates, 10m cache expiration boundaries)
- Feature 14: `check_freshness.py` TLS & SSRF integration (untrusted certificate handling, non-http schemes file:// or gopher://, timeout boundaries 1s vs 30s, empty response body)
- Feature 15: `check_freshness.py` ISO fractional seconds (1 to 9 microsecond digits, comma `,` vs period `.`, extreme timezone offsets +14:00 / -12:00, leap seconds, malformed ISO strings)
- Feature 16: `check_freshness.py` non-content tag stripping (nested `<script>` inside `<nav>`, unclosed tags, attributes containing date patterns inside stripped tags, uppercase tags `<SCRIPT>`, CDATA blocks)
- Feature 17: `check_freshness.py` earliest main date & deduplication (conflicting microdata dates vs JSON-LD dates, multiple article entities, duplicate audit URLs with different query params or anchors, empty URL list)
- Feature 18: `check_freshness.py` near-midnight diagnostics (exact 23:59:59 Europe/Istanbul vs 00:00:01 next day, negative age_hours rejection, daylight saving transition dates, leap years)
- Feature 19: `verify_facts_and_links.py` all-paragraph verification (draft with 0 paragraphs, draft with 100+ paragraphs, paragraph without citations, citation missing from dossier, multiple citations in single paragraph)
- Feature 20: `verify_facts_and_links.py` clean number & entity extraction (European number formatting `1.234,56` vs US `1,234.56`, percentage `%` and currency symbols `$`, `€`, `₺`, numeric strings embedded in words, extreme large numbers > 10^12)
- Feature 21: `verify_facts_and_links.py` vendor single-segment allowlist (single segment root domains vs allowed vendor paths, trailing slash handling, subdomain variations, spoofed domain names)
- Feature 22: `verify_facts_and_links.py` cross-language anchoring (unsupported source_lang codes, mismatched source_anchors text, empty anchors list, unicode RTL Arabic/Kurdish Sorani vs Kurmanji latin text)
- Feature 23: `verify_facts_and_links.py` Turkish morphology & attribution (dotless `ı` vs dotted `i`, `I` vs `İ` casing via `tr_fold()`, multiple chained institutional suffixes like `-nin/-nın`, unrecognized suffixes, punctuation directly attached to entity names)

## Output Requirements
Produce a comprehensive blueprint report at `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/e2e_explorer_m3_2/handoff.md`.
Document:
1. Feature-by-feature boundary analysis
2. Exact test case names, target file paths (`tests/e2e/tier2/test_f13_*.py` .. `test_f23_*.py`), inputs, expected outcomes, and assertion mechanisms
3. Opaque-box execution commands
4. Send a completion message when done. DO NOT modify any code.


## 2026-11-11T14:04:58Z
You are Explorer 2 for Milestone E2E-M3 (Tier 2 Boundary & Corner Cases: Features 13-23).
Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin
State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/e2e_explorer_m3_2/

Read your dispatch file at:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/e2e_explorer_m3_2/DISPATCH.md
and ORIGINAL_REQUEST.md at:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/ORIGINAL_REQUEST.md

Your role is read-only exploration and test case blueprint design for Features 13-23 under tests/e2e/tier2/.
Write your comprehensive blueprint report to:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/e2e_explorer_m3_2/handoff.md
DO NOT modify source code or tests.
When complete, notify me with send_message.
