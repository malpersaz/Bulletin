---
name: kevheng
description: Automated Kurmanji linguistic, orthographic, and lexicon verification gate for news and content drafts. Validates tokens against ferheng_kurmanci.db (1.7M+ entries), enforces authentic Kurmanji vocabulary standards, apostrophe rules for numbers/acronyms, Turkish character eradication, and deck character constraints.
---

# Kevheng Linguistic & Orthographic Gate

## Operating Rule

Keep this skill as a short ordered execution checklist. All checks must pass before any draft is approved for final publication or editorial handoff.

## Sequence

### 1. Pre-Flight Script & Lexicon Verification
- Confirm that `/home/malpersaz/Desktop/projects/ocr/ferheng_kurmanci.db` is present and accessible.
- Confirm that the validator script `.agents/skills/kevheng/scripts/validate_kurmanji.py` is present and executable.

### 2. Automated Text & Metadata Audit
Run the automated validator against the drafted article body, deck summary, and optional SEO slug:
```bash
.agents/skills/kevheng/scripts/validate_kurmanji.py --text "$CONTENT" --summary "$SUMMARY" --seo "$SEO"
```
Or when auditing a staged file:
```bash
.agents/skills/kevheng/scripts/validate_kurmanji.py --file "$FILE_PATH" --summary "$SUMMARY" --seo "$SEO"
```

### 3. Verification Criteria Checklist
The audit evaluates seven mandatory quality checkpoints:

1. **Strict Alphabet & Diacritics**:
   - Strictly Latin alphabet with authentic diacritics (`ç`, `ê`, `î`, `ş`, `û`).
   - Zero tolerance for Turkish letters (`ı`, `ğ`, `ö`, `ü`).
2. **Conjunction Invariance**:
   - The conjunction "and" must strictly be `û` (never `u`).
3. **Apostrophe Convention for Numbers & Acronyms**:
   - Ordinals and dates MUST separate suffixes with apostrophes: `14'emîn`, `28'ê`, `2026'an`.
   - Acronyms and initialisms (letters read individually) MUST separate suffixes with apostrophes: `DYE'yê`, `BBC'yê`, `AFP'ê`, `NYT'ê`, `PDK'ê`, `TC'yê`.
   - **Proper Nouns (Navên Xwerû)**: Names of companies, entities, persons, places, and newspapers attach suffixes **directly without apostrophes**: `SpaceXê`, `Amerîkayê`, `Amedê`, `Milliyetê`, `New York Timesê`. Do not copy the Turkish convention of using apostrophes on proper nouns.
4. **Mandatory Authentic Terminology**:
   - Update: `nûjenkirin` (never `update` or `nûve`).
   - Install: `sazkirin` (never `install` or generic `damezrandin`).
   - Click: `tikandin` / `bitikîne` (never `kirtandin` or `keritandin`).
   - Add: `lêke` / `lê zêde bike` (never standalone `Zêde Bike`).
   - Configure / Settings: `mîheng` / `mîhengkirin` (never `veavakirin`).
   - Process / Processing: `verîşandin` / `verîşîn` (never `pêvajokirin`).
   - Artificial Intelligence: `aqilê çêkirî` (never `hişê çêkirî`).
   - Machine Learning: `hînbûna makîneyê` (never `fêrbûna makîneyê`).
   - Autonomous: `xweser` (never `serbixwe`).
   - However / But: `lê` (never `lêbelê` or `lê belê`).
   - Solid / Firm: `qewîm` (never `qaîm`).
   - Rule / Principle: `qayîde` (never `qaîde`).
   - About / Concerning: `li ser` (never `derbarê`, `der barê`, `derheqê`, or `der heqê`).
   - Together with / With: `bi ... re` (never `digel` or `di gel`).
   - Cargo / Freight: `bar` / `keştiya bar` (never `barhilgir`).
5. **Deck Summary Length**:
   - The summary deck must strictly be **90 to 120 characters**.
6. **Interactive Month Annotation Completeness**:
   - Any Kurmanji month name in drafted HTML content must be enclosed in `<span class="month-popover" role="button" tabindex="0" data-month-ku="..." data-month-en="..." data-month-tr="..." data-month-ar="...">`. Bare month names or popovers with missing attributes will fail validation.
7. **Amed Framework Wikipedia-Style SEO Slug (AGENTS.md Rule 10)**:
   - SEO slug must exclusively use underscores (`_`), never hyphens (`-`).
   - Must preserve authentic Kurdish diacritics (`ç, ê, î, ş, û`). Stripping diacritics to English ASCII equivalents will fail validation.

### 4. Neologism & Typo Inspection
- Review any tokens flagged in `unknown_words`.
- Distinguish valid proper nouns, foreign names, and technical terms (e.g. `Starship`, `Raptor`) from actual spelling errors or typos.
- If a genuine typo is detected, correct it surgically before finalizing.

### 5. Gate Clearance & Reporting
- If all checks pass (`Status: PASSED`), report clearance to the user in Kurmanji.
- If any violation is found, output the specific line, matched violation, and recommended authentic Kurmanji replacement.
