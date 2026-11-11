---
name: kevrast
description: Automated fact-checking, anti-hallucination, and link provenance gate for Turkish news drafts and kevkolin dossiers. Verifies deep live URLs (root/section shortcuts always fail), anchors cited figures to the fetched target page, enforces the dossier URL chain of custody, and prevents citation drift before publication.
---

# Kevrast Fact-Checking & Verification Gate

## Operating Rule

Keep this skill as a short ordered execution checklist. All factual assertions, quotes, and embedded links must be verified before a news draft is finalized.

## Sequence

### 1. Pre-Flight Script Verification
- Confirm that the validator script `.agents/skills/kevrast/scripts/verify_facts_and_links.py` is present and executable.

### 2. Automated Fact & Link Audit
Stage 2 (draft verification against the dossier):
```bash
python3 .agents/skills/kevrast/scripts/verify_facts_and_links.py --file "$FILE_PATH" --dossier "$DOSSIER_JSON"
```
When diagnosing failures or reviewing paragraph-level provenance, run with `--explain`:
```bash
python3 .agents/skills/kevrast/scripts/verify_facts_and_links.py --file "$FILE_PATH" --dossier "$DOSSIER_JSON" --explain
```
Emits paragraph-by-paragraph diagnostics (`[P1]..[Pn]`), pinpointing unlinked attribution phrases, missing numeric matches, and multi-link collisions with actionable remedies.

Stage 1 (dossier pre-verification, before drafting):
```bash
python3 .agents/skills/kevrast/scripts/verify_facts_and_links.py --dossier "$DOSSIER_JSON"
```

### 3. Verification Criteria Checklist
The audit evaluates five core journalistic safeguards:

1. **Link Provenance Verification (Zero Fabricated, Shortened, or Broken URLs)**:
   - **R1 Liveness & Hardened Transport**: Every embedded link (`<a href="...">`) MUST be fetched with a full GET over verified TLS via `editorial_http.py` (SSRF guard on all redirects, `Retry-After` bounded retry for 429/503, and 10-minute cache). Soft-404 pages, redirect-to-home, and 4xx/5xx all count as FAILED.
   - **R2 Deep-Link Precision & Vendor Pages**: Citation href MUST be the content page carrying cited data. Domain roots and rubric fronts ALWAYS FAIL. Defined single-segment vendor download/release pages (`/download`, `/releases`, `/changelog`) are permitted under strict version/anchor matching.
   - **R3 Claim Anchoring & Morphology**: Distinctive figures in citing paragraphs must match the target page. Features exact normalized numeric matching (removing loose substring matches), Turkish case-folding (`tr_fold()`), institutional suffix stemming (`Belediyesine` -> `Belediyesi`), and cross-language anchoring via `source_lang` and `source_anchors` in the dossier.
   - **R4 Citation Completeness & Speech Separation**: Distinctively separates `OUTLET_ATTRIBUTION_RE` (e.g. `haberine göre`, `analize göre`, `sürüm notlarına göre`, `aktarıldığı üzere`, which strictly require a citation link in the same paragraph) from `SPEECH_ATTRIBUTION_RE` (bare speech verbs `dedi`, `söyledi`, `ifade etti`, which allow continuing a quote from an immediately preceding linked paragraph).
   - **R5 Dossier Modes**: `--dossier` alone audits dossier URLs (Stage 1); `--dossier` with `--file` audits draft against dossier (Stage 2). Source roles (`primary` / `supplementary` / `historical`) are carried from the dossier; only `kevtemen` consumes them for same-day enforcement.

2. **Verbatim Fact Grounding & Zero Extrapolation**:
   - Every number, date, year, percentage, and named entity extracted in the Factual Matrix MUST match verified source evidence.
   - Zero speculative grouping: Never extrapolate or fabricate participant lists (e.g. organizations attending an event) unless explicitly confirmed by sources.

3. **Anti-Citation Drift (Citation-Anchor Integrity)**:
   - When citing `$Name tarafından aktarılan bilgilere göre` or `$Name haberine göre`, the attached claim MUST be the exact claim published by `$Name`.
   - Never shift claims from another outlet onto `$Name`.

4. **Negative-Fact & Absence Invariance**:
   - If an entity, party, or institution is reported as boycotting, withdrawing, or declining participation:
     - Strictly forbid reporting or implying their presence or participation.
     - Report their boycott or absence as a distinct fact citing their rationale.

5. **Multi-Source Contradiction Resolution**:
   - If verified outlets report conflicting data (e.g., turnout numbers, budget figures, dates), document the variance explicitly in the Turkish prose:
     *Format*: `"$A tarafından paylaşılan bilgilere göre ..., ancak $B kaynağında ..."`

### 4. Gate Clearance & Reporting
- If all links are live and claims are entailed by source evidence (`Status: PASSED`), report clearance to the user in Kurmanji (per user communication protocol).
- BLOCK outcomes must print a manual-review note naming the exact failing link, the missing figure/entity, and the corrective action (fix the deep link, correct the figure, or remove the claim).
- If broken links, fabricated URLs, or factual contradictions are found, output the exact issues and required corrections.
