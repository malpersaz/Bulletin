# Dispatch: Challenger M2-2 (Empirical Gate & Morphology Stress Testing)

Task: Adversarially challenge and stress-test `check_freshness.py` and `verify_facts_and_links.py`.
State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/challenger_m2_2/
Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin
Original request: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/ORIGINAL_REQUEST.md
Milestone scope: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/sub_orch_m2/SCOPE.md
Project spec: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/orchestrator_1/PROJECT.md
Worker handoff: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/worker_m2/handoff.md

Challenge Focus:
- Test `check_freshness.py` with adversarial payloads:
  - Complex ISO fractional timestamps (`.000000Z`, `.123+03:00`, comma-separated fractions `2026-11-10T23:13:00,500Z`).
  - Stale HTML with nested scripts, SVG text, comments, data attributes containing today's date.
  - Earliest date priority vs sidebar dates, modified dates, navigation words ("bugün").
- Test `verify_facts_and_links.py` with adversarial drafts:
  - Multi-paragraph drafts where P1 is clean and P5 has fabricated numbers or entities.
  - Number substring matching tests (`12` vs `1287295`, `95` vs `1950`, `80` vs `180`).
  - Turkish morphology edge cases: *Diyarbakır Büyükşehir Belediyesine*, *İçişleri Bakanlığı*, dotted/dotless I (`İSTANBUL` vs `istanbul`, `IĞDIR` vs `ığdır`).
  - Vendor single-segment allowlist boundaries (e.g. `https://example.com/download` vs news sections `https://example.com/ekonomi`).
  - Cross-language anchoring two-sided validation: mismatched terms, missing terms, correct terms.
- Verdict: APPROVE or REJECT (with full reproduction code).
Output: handoff.md in /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/challenger_m2_2/


## 2026-11-11T11:19:49Z
You are Challenger M2-2 (Empirical Gate & Morphology Stress Testing) for Milestone 2.
Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin
State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/challenger_m2_2/

MANDATORY Context:
1. /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/ORIGINAL_REQUEST.md
2. /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/sub_orch_m2/SCOPE.md
3. /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/orchestrator_1/PROJECT.md
4. /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/worker_m2/handoff.md
5. /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/challenger_m2_2/DISPATCH.md

Targets to stress-test:
- `.agents/skills/kevtemen/scripts/check_freshness.py`
- `.agents/skills/kevrast/scripts/verify_facts_and_links.py`

Challenge Instructions:
1. Stress-test `check_freshness.py`:
   - Empirical tests with various ISO timestamps: `2026-11-10T23:13:00.000Z` (assert parses to today in Europe/Istanbul `+03:00`), `2026-11-10T20:59:59.999Z` (yesterday evening -> is_yesterday_late_evening True), comma decimals `2026-11-10T23:13:00,123Z`.
   - Stale HTML with embedded `<script>` or `<aside>` containing today's date (verify it does NOT pass as today).
   - Historical text containing unanchored "bugün" (verify it does NOT pass as today).
   - Duplicate URLs in draft (verify they are evaluated once with citation count).
2. Stress-test `verify_facts_and_links.py`:
   - Multi-paragraph draft where paragraph 1 is valid and paragraph 4 contains fabricated number (assert `audit_draft()` fails with exit non-zero and reports missing number).
   - Number substring matching attack: target text with article ID `1287295`, draft cites `12` or `95` (assert `anchoring()` rejects substring match).
   - Scale multiplier test: draft writes `80 bin`, target text has `80000` (assert matches).
   - Turkish morphology test: proper noun with case suffixes without apostrophes (*Diyarbakır Büyükşehir Belediyesine* -> matches *Diyarbakır Büyükşehir Belediyesi*, *İçişleri Bakanlığına* -> matches *İçişleri Bakanlığı*).
   - Case-folding test: dotted `İ` / `i` and dotless `I` / `ı` (assert `tr_fold()` handles both correctly).
   - Vendor single-segment allowlist test: `https://example.com/download` with version `2.31.0` and role `product`/`supplementary` (assert passes), rubric front `https://news.com/ekonomi` (assert fails).
   - Cross-language anchoring test: foreign doc with `Coordinator` and draft with `Koordinatör` using declared `source_anchors` (assert passes if both exist, fails if foreign term missing).
3. Render verdict: APPROVE or REJECT (with full reproduction code and error traces if failed).
Write your report to: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/challenger_m2_2/handoff.md
When done, message me with your verdict and findings.
