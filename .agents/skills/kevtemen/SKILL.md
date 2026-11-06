---
name: kevtemen
description: Same-day freshness sentinel for the editorial pipeline. Hard-blocks preparing, editing, or staging any news whose source publication date is not today (Europe/Istanbul) — even when the user supplies the old link. Fail-closed, no override path.
---

# Kevtemen Same-Day Freshness Sentinel

## Operating Rule

- **ABSOLUTE SAME-DAY GATE**: Only content whose source publication date is TODAY (Europe/Istanbul) may be drafted, edited, or staged on the site.
- **FAIL-CLOSED**: Undatable, unreachable, or ambiguous sources are treated as NOT same-day and blocked.
- **NO OVERRIDE**: There is no `--allow-stale`, no backdating, no exceptions. If the user supplies an old or stale link, kevtemen still stops the job and reports it.
- Keep this skill as a short ordered execution checklist.

## Sequence

### 1. Gate First — No Work Starts Before It
Run the sentinel against the user-supplied link(s) and/or the dossier's source dates BEFORE any `kevkolin` research or `kevanger` drafting:
```bash
.agents/skills/kevtemen/scripts/check_freshness.py "$URL1" "$URL2"
# or verify an explicit publication date from a dossier:
.agents/skills/kevtemen/scripts/check_freshness.py --date "2026-11-06 13:22"
```

### 2. Verdict Handling
- Exit 0 (`Status: PASSED (Same-Day)`): the source is from today; the pipeline may proceed.
- Exit 1 (`Status: BLOCKED`): STOP EVERYTHING. Do not research, do not draft, do not call `create_news`/`update_news`. Report the sentinel's warning verbatim to the user, e.g.:
  `DURDURULDU: Bu içerik eski — yayın tarihi 02.11.2026. Siteye eski ve bayat haber girilmesi doğru değildir; işlem kesildi.`
- Never offer to proceed after a block; the gate is not negotiable and there is no approval path around it.

### 3. Pre-Staging Re-Check
Before `create_news`/`update_news`, re-run the sentinel in draft mode against the article HTML; it extracts every cited `href` and verifies each is same-day:
```bash
.agents/skills/kevtemen/scripts/check_freshness.py --file /path/to/draft.html
```

### 4. Applies To Edits Too
`update_news` on an existing record whose source or `created_at` date is not today is equally blocked: report the record's date and halt.

### 5. Reporting
Report the gate outcome (detected publication date, evidence, verdict) in the user's conversation language. A block message must always name the exact publication date that caused it (or state that none could be determined).
