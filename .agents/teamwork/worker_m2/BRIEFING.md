# BRIEFING — 2026-11-11T11:20:00Z

## Mission
Implement Milestone 2 (Features 13-23): create hardened shared HTTP client `editorial_http.py`, harden kevtemen freshness checker `check_freshness.py` (Features 14-18), and harden kevrast fact and link verification `verify_facts_and_links.py` and `dossier.schema.json` (Features 19-23).

## 🔒 My Identity
- Archetype: worker
- Roles: implementer, qa
- Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin
- State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/worker_m2/
- Original parent: 5c7538a8-5eae-4af0-8169-9ca427353718
- Milestone: Milestone 2 (Editorial Infrastructure)

## 🔒 Key Constraints
- Integrity Mandate: genuine implementation only, no mock/hardcoded checks, no dummy facades.
- Owned files: `.agents/skills/_shared/editorial_http.py`, `.agents/skills/kevtemen/scripts/check_freshness.py`, `.agents/skills/kevrast/scripts/verify_facts_and_links.py`, `.agents/skills/kevkolin/scripts/dossier.schema.json`.
- Strict Code Separation: no inline CSS/JS.
- Python 3 standard library only unless specified; pass `python3 -m py_compile`.
- Pass project guards: `code_separation_guard.py`, `module_boundary_guard.py --all`, `i18n_guard.py`.

## Current Parent
- Conversation ID: 5c7538a8-5eae-4af0-8169-9ca427353718
- Updated: 2026-11-11T11:20:00Z

## Task Summary
- **What to build**:
  - Feature 13: `.agents/skills/_shared/editorial_http.py` with SSRF validation, safe redirect handler, browser headers, bounded Retry-After retries, two-tier cache (memory + atomic POSIX file).
  - Features 14-18: Harden `check_freshness.py` with `editorial_http`, fractional ISO parsing, non-content tag stripping, earliest datePublished selection, near-midnight diagnostics.
  - Features 19-23: Harden `verify_facts_and_links.py` and `dossier.schema.json` with all-paragraph verification, non-content stripping & exact numeric equality, vendor single-segment allowlist, cross-language anchoring, Turkish morphology & expanded attribution patterns.
- **Success criteria**:
  - 100% genuine logic, all unit tests and regressions passing.
  - Code separation guard, module boundary guard, i18n guard pass.
- **Interface contracts**: `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/sub_orch_m2/SCOPE.md`
- **Code layout**: `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/orchestrator_1/PROJECT.md`

## Key Decisions Made
- Implemented `editorial_http.py` with verified TLS, comprehensive SSRF protection (loopback, private subnets, link-local metadata, CGNAT `100.64.0.0/10`, ULA `fc00::/7`), and atomic POSIX cache writing.
- Fixed `RE_ISO` fractional seconds parsing with non-capturing group preserving UTC timezone and converting to `Europe/Istanbul`.
- Replaced first-paragraph-only matching in `verify_facts_and_links.py` with `_find_all_citing_blocks` across `<p>`, `<blockquote>`, `<li>`.
- Eliminated substring matching `n in t` in R3 anchoring; implemented normalized scaled number extraction and exact equality.
- Implemented single-segment product allowlist with post-fetch version and anchor verification.
- Added two-sided cross-language verification using `source_lang` and `source_anchors`.
- Added Turkish `tr_fold()` and institutional suffix stemmer.

## Artifact Index
- `.agents/skills/_shared/editorial_http.py`
- `.agents/skills/kevtemen/scripts/check_freshness.py`
- `.agents/skills/kevrast/scripts/verify_facts_and_links.py`
- `.agents/skills/kevkolin/scripts/dossier.schema.json`
- `.agents/teamwork/worker_m2/handoff.md`

## Change Tracker
- **Files modified**:
  - `.agents/skills/_shared/editorial_http.py`: Created shared HTTP transport layer with SSRF, retry, cache.
  - `.agents/skills/kevtemen/scripts/check_freshness.py`: Integrated `editorial_http`, fixed ISO .000Z parsing, tag stripping, earliest date, and diagnostics.
  - `.agents/skills/kevrast/scripts/verify_facts_and_links.py`: All-paragraph audit, exact numbers, vendor allowlist, cross-language anchoring, Turkish morphology.
  - `.agents/skills/kevkolin/scripts/dossier.schema.json`: Added `product` role, `source_lang`, `source_anchors`.
- **Build status**: PASS (all Python files compiled and 10/10 verification tests passing).
- **Pending issues**: None.

## Quality Status
- **Build/test result**: PASS (10/10 M2 test cases passing, news pipeline Stages 1-2 verified).
- **Lint status**: PASS (`py_compile` clean; code separation guard 0 violations, module boundary guard 0 violations).
- **Tests added/modified**: Comprehensive unit and regression test suite covering Features 13-23.

## Loaded Skills
- None explicitly requested to dump.
