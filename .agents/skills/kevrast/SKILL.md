---
name: kevrast
description: Automated fact-checking, anti-hallucination gate, and live link verification auditor for Turkish news drafts. Verifies live HTTP 200 URLs, cross-references numbers, dates, quotes, and prevents citation drift before publication.
---

# Kevrast Fact-Checking & Verification Gate

## Operating Rule

Keep this skill as a short ordered execution checklist. All factual assertions, quotes, and embedded links must be verified before a news draft is finalized.

## Sequence

### 1. Pre-Flight Script Verification
- Confirm that the validator script `.agents/skills/kevrast/scripts/verify_facts_and_links.py` is present and executable.

### 2. Automated Fact & Link Audit
Run the automated auditor against the drafted article body or file:
```bash
.agents/skills/kevrast/scripts/verify_facts_and_links.py --text "$CONTENT"
```
Or when auditing an existing file:
```bash
.agents/skills/kevrast/scripts/verify_facts_and_links.py --file "$FILE_PATH"
```

### 3. Verification Criteria Checklist
The audit evaluates five core journalistic safeguards:

1. **Mandatory Live Link Verification (Zero Broken URLs)**:
   - Every embedded link (`<a href="...">`) MUST return an HTTP 200 status code on the publisher's destination server.
   - Strictly prohibit guessing, approximating, or fabricating article URLs.
   - Disallow URLs that return 404, 4xx, 5xx, or redirect to error/missing pages.
   - If a specific deep link cannot be verified, fallback strictly to the verified publisher rubric/root domain.

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
- If broken links, fabricated URLs, or factual contradictions are found, output the exact issues and required corrections.
