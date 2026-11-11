---
name: kevtemen
description: Same-day freshness sentinel for the editorial pipeline. Hard-blocks preparing, editing, or staging any news whose source publication date is not today (Europe/Istanbul) — even when the user supplies the old link. Supports content-type aware evergreen bypass with strict anti-laundering controls. Fail-closed, no override path.
---

# Kevtemen Same-Day Freshness Sentinel

## Operating Rule

- **ABSOLUTE SAME-DAY GATE FOR NEWS**: For content classified as `news` (default), only content whose source publication date is TODAY (Europe/Istanbul) may be drafted, edited, or staged on the site.
- **TWO-PASS FRESHNESS GATE ORDER**:
  1. **Stage 0 (Seed URL Pre-Gate)**: Evaluates candidate URLs before any research or drafting begins to eliminate token waste.
  2. **Stage 2 (Dossier Gate)**: Evaluates compiled research dossiers, enforcing role tags (`primary` vs `supplementary` / `historical`) and anti-laundering rules.
- **NEAR-MIDNIGHT RECOVERY PROTOCOL**: When a user-supplied seed URL fails Stage 0 solely because it was published in the late evening of yesterday (`>= 20:00 Europe/Istanbul` or structured diagnostic `is_yesterday_late_evening: true`), permit a single bounded scan for a same-day primary follow-up (`today` in `Europe/Istanbul`) via `kevkolin` (`source_catalog.py --scan-today`). If an official or primary same-day report is verified, promote to `role: "primary"` and demote seed to `role: "supplementary"`. If no same-day follow-up exists, immediately HALT (`BLOCKED`). Never launder via unrelated links.
- **CONTENT-TYPE AWARE EVERGREEN BYPASS**: For content classified as `evergreen` in the research dossier, the same-day date gate is bypassed (`PASSED (EVERGREEN)`) and HTTP liveness is delegated to `kevrast` Stage 1, protected by strict anti-laundering checks:
  1. Mandatory `evergreen_rationale` (`>= 20` characters).
  2. All draft hyperlinks must be declared in the research dossier `sources`.
  3. Strict prohibition of breaking-news temporal markers (`bugün`, `dün`, `bu sabah`, `az önce`, `son dakika`).
- **FAIL-CLOSED**: Undatable, unreachable, or ambiguous sources are treated as NOT same-day and blocked.
- **NO OVERRIDE**: There is no `--allow-stale`, no backdating, no exceptions. If the user supplies an old or stale link, kevtemen stops the job and reports it.
- Keep this skill as a short ordered execution checklist.

## Sequence

### 1. Gate First — Two-Pass Gate Order

#### Stage 0: Seed URL Pre-Gate (Zero Token Waste)
Run on candidate URL(s) before any research or drafting starts:
```bash
python3 .agents/skills/kevtemen/scripts/check_freshness.py "$SEED_URL"
```
- Exit 0 (`Status: PASSED (Same-Day)`): source is today-dated; proceed to `kevkolin` research.
- Exit 1 (`Status: BLOCKED`): check near-midnight structured diagnostics (`age_hours`, `is_yesterday_late_evening`).
  - If `is_yesterday_late_evening` is true: trigger Near-Midnight Recovery Protocol.
  - If older: STOP. Report exact publication date/age and halt.

#### Stage 2: Dossier Gate (Role-Aware & Anti-Laundering)
Run immediately after `kevkolin` compiles the research dossier JSON, before `kevrast` Stage 1:
```bash
python3 .agents/skills/kevtemen/scripts/check_freshness.py --dossier /path/to/dossier.json
```
- In `news` mode, verifies that at least one `role: "primary"` source is dated today in `Europe/Istanbul`. `supplementary` and `historical` sources are exempt from the date gate.
- In `evergreen` mode, verifies that temporal markers are absent and that all declared sources comply.

### 2. Verdict Handling & Diagnostics
- Exit 0 (`Status: PASSED (Same-Day)` or `Status: PASSED (EVERGREEN)`): the source satisfies freshness or evergreen policy; pipeline proceeds.
- Exit 1 (`Status: BLOCKED`): STOP EVERYTHING. Do not research, do not draft, do not call `create_news`/`update_news`.
  - When reporting a block to the user, inspect structured near-midnight diagnostic attributes emitted by `check_freshness.py`:
    - `age_hours` and `is_yesterday_late_evening`.
    - Report exact age (e.g. *"Çavkanî 13.3 saet berê, di 23:13+03:00 de (47 xulek berî nîvê şevê) hatiye weşandin"*) rather than a generic date mismatch string.
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
