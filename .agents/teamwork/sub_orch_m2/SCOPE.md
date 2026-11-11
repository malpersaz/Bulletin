# Scope: Milestone 2 — Shared HTTP Client & Editorial Gate Hardening

## Scope Overview
Implement Phase 2 shared transport and editorial gate hardening:
1. `.agents/skills/_shared/editorial_http.py` (verified TLS, SSRF guard, standard headers, Retry-After backoff, URL response cache).
2. `.agents/skills/kevtemen/scripts/check_freshness.py` (TLS verification integration, ISO fractional seconds parsing preserving UTC, non-content tag stripping before date scanning, earliest main-entity date priority, check line deduplication, near-midnight diagnostics).
3. `.agents/skills/kevrast/scripts/verify_facts_and_links.py` (evaluate all citing blocks across draft, non-content tag stripping before R3, exact normalized numeric equality without `n in t`, single-segment vendor download/release allowlist, two-sided cross-language anchoring via `source_lang` and `source_anchors`, Turkish `tr_fold()` and institutional suffix stemmer, expanded R4 attribution detection).
4. `tools/verify_news_pipeline.sh` Stage 1 & Stage 2 verification.

## Files Owned
- .agents/skills/_shared/editorial_http.py
- .agents/skills/kevtemen/scripts/check_freshness.py
- .agents/skills/kevrast/scripts/verify_facts_and_links.py
- .agents/skills/kevkolin/scripts/dossier.schema.json

## Status: DONE
- Gate Result: PASS (Iteration 2)
- All Python files pass `python3 -m py_compile`.
- `editorial_http.py` verified with TLS, SSRF protection across all IP forms, 5-hop redirect limits, Retry-After backoff, and two-tier POSIX atomic caching.
- `check_freshness.py` verified with ISO fractional seconds (.000Z) preserving UTC, non-content tag stripping, earliest datePublished selection, unanchored Bugün elimination, URL deduplication, and near-midnight diagnostics.
- `verify_facts_and_links.py` verified with all-paragraph evaluation (P3/P4 hallucination caught even when P1 is clean), exact numeric token matching (no substring leaks), single-segment vendor download allowlisting, two-sided cross-language anchoring, and Turkish morphology rules.
- Quality guards pass: `code_separation_guard.py` (0 violations), `module_boundary_guard.py --all` (0 violations), `i18n_guard.py` (100% clean).
- Forensic Auditor verdict: CLEAN.
