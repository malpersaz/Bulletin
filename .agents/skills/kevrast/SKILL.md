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
.agents/skills/kevrast/scripts/verify_facts_and_links.py --text "$CONTENT" --dossier "$DOSSIER_JSON"
```
Or when auditing an existing file:
```bash
.agents/skills/kevrast/scripts/verify_facts_and_links.py --file "$FILE_PATH" --dossier "$DOSSIER_JSON"
```
Stage 1 (dossier pre-verification, before drafting):
```bash
.agents/skills/kevrast/scripts/verify_facts_and_links.py --dossier "$DOSSIER_JSON"
```

### 3. Verification Criteria Checklist
The audit evaluates five core journalistic safeguards:

1. **Link Provenance Verification (Zero Fabricated, Shortened, or Broken URLs)**:
   - **R1 Liveness**: Every embedded link (`<a href="...">`) MUST be fetched with a full GET over verified TLS and resolve to the real article page. Soft-404 pages (HTTP 200 with error markup), redirect-to-home, and 4xx/5xx all count as FAILED.
   - **R2 Deep-Link Precision**: A citation href MUST be the content page carrying the cited data. Domain roots and section/rubric fronts (e.g. `bbc.com`, `bbc.com/news`) ALWAYS FAIL — there is no root-domain fallback.
   - **R3 Claim Anchoring**: For each linked citation, the distinctive figures in the citing paragraph (normalized: `yüzde 71` ↔ `71%`, `1 trilyon dolar` ↔ `$1 trillion`) MUST appear in the fetched target page text. A number mismatch → BLOCK with a manual-review note. When the paragraph has no distinctive figure, at least one cited named entity must appear; if neither can be evaluated, the link is flagged for manual review.
   - **R4 Citation Completeness & Chain of Custody**: Every attribution phrase (`$Name haberine göre`, `$Name tarafından aktarılan bilgilere göre`, ...) must sit in a paragraph carrying a live link; the draft's URL set must be a subset of the kevkolin dossier URL set; and the draft must contain at least one verified outbound citation link (zero-link drafts FAIL).
   - **R5 Dossier Modes**: `--dossier file.json` alone audits every dossier URL (Stage 1); `--dossier` combined with `--text`/`--file` audits the draft against the dossier (Stage 2). Source roles (`primary` / `supplementary` / `historical`) are carried from the dossier; only `kevtemen` consumes them for same-day enforcement.

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
