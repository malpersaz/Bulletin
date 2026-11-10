---
name: kevtemen
description: Same-day freshness sentinel for the editorial pipeline. Hard-blocks preparing, editing, or staging any news whose source publication date is not today (Europe/Istanbul) — even when the user supplies the old link. Supports content-type aware evergreen bypass with strict anti-laundering controls. Fail-closed, no override path.
---

# Kevtemen Same-Day Freshness Sentinel

## Operating Rule

- **ABSOLUTE SAME-DAY GATE FOR NEWS**: For content classified as `news` (default), only content whose source publication date is TODAY (Europe/Istanbul) may be drafted, edited, or staged on the site.
- **CONTENT-TYPE AWARE EVERGREEN BYPASS**: For content classified as `evergreen` in the research dossier, the same-day date gate is bypassed (`PASSED (EVERGREEN)`) and HTTP liveness is delegated to `kevrast` Stage 1, protected by strict anti-laundering checks:
  1. Mandatory `evergreen_rationale` (`>= 20` characters).
  2. All draft hyperlinks must be declared in the research dossier `sources`.
  3. Strict prohibition of breaking-news temporal markers (`bugün`, `dün`, `bu sabah`, `az önce`, `son dakika`).
- **FAIL-CLOSED**: Undatable, unreachable, or ambiguous sources are treated as NOT same-day and blocked.
- **NO OVERRIDE**: There is no `--allow-stale`, no backdating, no exceptions. If the user supplies an old or stale link, kevtemen stops the job and reports it.
- Keep this skill as a short ordered execution checklist.

## Sequence

### 1. Gate First — No Work Starts Before It
Run the sentinel against the user-supplied link(s) or directly against the research dossier JSON:
```bash
# Standalone dossier mode (evaluates dossier type and primary sources):
python3 .agents/skills/kevtemen/scripts/check_freshness.py --dossier /path/to/dossier.json

# Positional targets or explicit publication date:
python3 .agents/skills/kevtemen/scripts/check_freshness.py "$URL1" "$URL2"
python3 .agents/skills/kevtemen/scripts/check_freshness.py --date "2026-11-10 13:22"
```

### 2. Verdict Handling
- Exit 0 (`Status: PASSED (Same-Day)` or `Status: PASSED (EVERGREEN)`): the source satisfies freshness or evergreen policy; the pipeline may proceed.
- Exit 1 (`Status: BLOCKED`): STOP EVERYTHING. Do not research, do not draft, do not call `create_news`/`update_news`. Report the sentinel's warning verbatim to the user.
- Never offer to proceed after a block; the gate is not negotiable and there is no approval path around it.

### 3. Pre-Staging Re-Check (Role-Aware & Anti-Laundering)
Before `create_news`/`update_news`, re-run the sentinel in draft mode together with the research dossier JSON:
```bash
python3 .agents/skills/kevtemen/scripts/check_freshness.py --file /path/to/draft.html --dossier /path/to/dossier.json
```
- In `news` mode, only `primary`-role sources are same-day checked; `supplementary` and `historical` links are exempt from the date gate.
- In `evergreen` mode, verifies that no undeclared links or breaking-news temporal markers leaked into the draft.

### 4. Applies To Edits Too
`update_news` on an existing record whose source or `created_at` date is not today is equally blocked unless formally classified as evergreen: report the record's date and halt.

### 5. Reporting
Report the gate outcome in the user's conversation language. A block message must always name the exact reason or publication date that caused it.
