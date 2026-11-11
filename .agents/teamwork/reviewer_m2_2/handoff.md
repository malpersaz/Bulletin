# Handoff Report: Reviewer M2-2 (Security, Regexes & Robustness)

**Milestone:** Milestone 2 (Features 13–23)  
**Agent:** Reviewer M2-2 (Reviewer & Adversarial Critic)  
**State Folder:** `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/reviewer_m2_2/`  
**Working Directory:** `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin`  
**Recipient / Parent Agent:** `5c7538a8-5eae-4af0-8169-9ca427353718` (`sub_orch_m2`)  
**Verdict:** **APPROVE** (with 1 Minor Robustness Advisory)  
**Date:** 2026-11-11  

---

## 1. Observation

Direct code examination, adversarial fuzzing, and execution of test suites yielded the following empirical facts:

### 1.1 Shared Transport & SSRF Guard (`.agents/skills/_shared/editorial_http.py`)
- **Verified TLS & Opener**: Line 247 defines `ctx = ssl.create_default_context()`, configuring certificate verification and host verification. Line 250 wraps `urllib.request.HTTPSHandler(context=ctx)` with `SSRFSafeRedirectHandler()`.
- **SSRF Blocklists**: Lines 76–105 define `BLOCKED_IPV4_NETWORKS` (16 networks) and `BLOCKED_IPV6_NETWORKS` (9 networks). Lines 138–163 in `is_blocked_ip()` check IP ranges, `ip.ipv4_mapped`, and properties `ip.is_private`, `ip.is_loopback`, `ip.is_link_local`, `ip.is_multicast`, `ip.is_reserved`, `ip.is_unspecified`, and `not ip.is_global`.
- **Empirical SSRF Tests**: Executed `validate_public_url()` against adversarial destinations:
  - `http://127.0.0.1/` -> `(False, "... non-public IP '127.0.0.1' (127.0.0.0/8)")`
  - `http://localhost./` -> `(False, "... non-public IP '127.0.0.1' (127.0.0.0/8)")`
  - `http://10.254.12.3/` -> `(False, "... non-public IP '10.254.12.3' (10.0.0.0/8)")`
  - `http://172.31.255.255/` -> `(False, "... non-public IP '172.31.255.255' (172.16.0.0/12)")`
  - `http://192.168.254.1/` -> `(False, "... non-public IP '192.168.254.1' (192.168.0.0/16)")`
  - `http://100.65.1.1/` -> `(False, "... non-public IP '100.65.1.1' (100.64.0.0/10)")`
  - `http://169.254.169.254/` -> `(False, "... non-public IP '169.254.169.254' (169.254.0.0/16)")`
  - `http://[::1]/` -> `(False, "... non-public IP '::1' (::1/128)")`
  - `http://[::ffff:127.0.0.1]/` -> `(False, "... non-public IP '::ffff:127.0.0.1' (127.0.0.0/8)")`
  - `http://[fd00::1]/` -> `(False, "... non-public IP 'fd00::1' (fc00::/7)")`
  - `http://0x7f000001/`, `http://0177.0.0.1/`, `http://2130706433/` -> all resolved to `127.0.0.1` and blocked.
  - Schemes `file://`, `ftp://`, `javascript:`, `gopher://` -> all blocked with `"Invalid URL scheme '...' : only http and https are permitted"`.
- **Adversarial Edge Case Finding (Minor Robustness Advisory)**: In `editorial_http.py` line 191:
  ```python
  if parsed.port is not None:
      if not (1 <= parsed.port <= 65535):
          return False, f"Invalid port: {parsed.port}"
  ```
  Accessing property `parsed.port` on out-of-range ports like `http://1.1.1.1:70000/` causes `urllib.parse` to raise `ValueError("Port out of range 0-65535")`. Because lines 191–194 are outside the initial `try...except Exception as e` block (lines 178–181), this `ValueError` escapes uncaught rather than returning `(False, "Invalid port...")`. While this fails closed, wrapping `parsed.port` in a defensive `try...except ValueError` block will prevent transport-level unhandled exceptions.

### 1.2 Regexes & Freshness Sentinel (`.agents/skills/kevtemen/scripts/check_freshness.py`)
- **ISO Fractional Seconds**: `RE_ISO` on line 75 is `r"(\d{4})-(\d{2})-(\d{2})(?:[T ](\d{2}):(\d{2})(?::(\d{2}))?(?:[.,]\d{1,9})?(Z|[+-]\d{2}:?\d{2})?)?"`. Preserves group 7 as timezone token. Tested `2026-11-10T23:13:00.000Z` in UTC -> parsed and converted to `2026-11-11 02:13:00+03:00` (today in Europe/Istanbul), resulting in `same_day: true, passed: true`.
- **Non-Content Tag Stripping**: Lines 263 and 265 execute `re.sub(r"<(aside|nav|script|style|noscript)[^>]*>.*?</\1>", " ", ...)` and `re.sub(r"<(script|style|noscript|nav|header|footer|aside)[^>]*>.*?</\1>", " ", ...)`. Tested HTML where `<script>var now="11.11.2026";</script>` and `<nav>11.11.2026</nav>` were placed alongside stale body date `10.11.2026`; `extract_pub_date()` returned strictly `10.11.2026 (visible date)` and ignored the script/nav markup.
- **Priority 1 Date Extraction**: Lines 215–256 filter JSON-LD strictly to `ALLOWED_JSONLD_TYPES = {"NewsArticle", "Article", "ReportageNewsArticle", "BlogPosting", "WebPage"}`, extracts only `datePublished` (ignoring `dateModified`), and selects `min(p1_candidates, key=lambda c: c[0].timestamp())` (the earliest datePublished). Tested with `datePublished: 2026-11-10` and `dateModified: 2026-11-11`; correctly chose `2026-11-10`.
- **Removal of Bare Bugün**: Unanchored `RE_TODAY` was completely removed. Replaced by `RE_ANCHORED_TODAY` requiring `\bbugün\b[,\s]+(\d{1,2}):(\d{2})`. Tested bare "Bugün gazetesine göre..." -> returned `None`.
- **Near-Midnight Diagnostics**: Lines 403–405 calculate `age_hours` and `is_yesterday_late_evening = (pub_date == yesterday) and (18 <= pub_dt.hour <= 23)`. Tested on `--date "10.11.2026 22:30"` -> returned `passed: false, is_yesterday_late_evening: true, age_hours: 15.93h`.

### 1.3 Fact-Checking, Provenance & Morphology (`.agents/skills/kevrast/scripts/verify_facts_and_links.py`)
- **All-Paragraph Evaluation**: Lines 587–599 iterate over all citing blocks returned by `_find_all_citing_blocks()`. Tested draft where paragraph 1 cited valid number `80.000` and paragraph 3 cited same URL with fabricated `45`; auditor returned `passed: false, status: "BLOCKED"` with diagnostic `[P3] [R3_MISSING_NUMBER] Figure(s) ['45'] not found...`.
- **Exact Normalized Numeric Equality**: Lines 380–389 eliminate substring checking (`or n in t`). Checks `missing = [n for n in nums if n not in target_nums_set]`. Tested hallucinated `12` against target containing `1287295`; correctly returned `status: "block"` and `missing_numbers: ['12']`. Normalizes scaled numbers (`80 bin` -> `80000`) and percentages (`yüzde 15`, `15%`, `%15`, `15 percent` -> `15%`).
- **Vendor Single-Segment Allowlist**: Lines 301–308 permit `SINGLE_SEGMENT_PRODUCT_SLUGS` (`download`, `releases`, etc.) for `role in ("supplementary", "product")` with `conditional_check_required: True`. Lines 542–556 verify `<article>` count `<= 2` and presence of semantic version or anchor token. Rejects rubric fronts (`https://news.com/spor` -> `precise: false, kind: "section_front"`).
- **Two-Sided Cross-Language Anchoring**: Lines 392–431 verify that `source_term` exists on target page and `tr_term` (or stem) exists in citing draft paragraph. Tested with `Coordinator` <-> `Koordinatör`; verified both positive match and negative block when target omitted source term.
- **Turkish Morphology**: `tr_fold()` properly maps `İ` -> `i` and `I` -> `ı` (tested `tr_fold('İÇİŞLERİ') == tr_fold('içişleri')` and `tr_fold('IĞDIR') == tr_fold('ığdır')`). `stem_institutional()` correctly stems 9 institutional noun patterns (`Valiliğinden` -> `valiliği`, `Belediyesine` -> `belediyesi`).

### 1.4 Research Dossier Schema (`.agents/skills/kevkolin/scripts/dossier.schema.json`)
- Verified with `jsonschema.Draft202012Validator.check_schema(schema)`: 100% valid Draft 2020-12 schema.
- Sources enum includes `"product"`. Contains `source_lang` (`tr`, `en`, `ku`, `ar`, `de`, `fr`) and `source_anchors` (`source_term`, `tr_term`).

### 1.5 Quality Guards & Compilation
- `python3 -m py_compile .agents/skills/_shared/editorial_http.py .agents/skills/kevtemen/scripts/check_freshness.py .agents/skills/kevrast/scripts/verify_facts_and_links.py` -> Clean exit code 0.
- `python3 tools/code_separation_guard.py` -> `[PASS] 0 violations`.
- `python3 tools/module_boundary_guard.py --all` -> `✅ PASSED: 0 violations across 9842 files`.
- Integrity Check: No hardcoded test passes, no dummy facades, no shortcuts detected.

---

## 2. Logic Chain

1. **SSRF Hardening (Observation 1.1)**: Editorial gates ingest URLs from untrusted sources and draft texts. By validating both initial hostnames and each redirect hop against comprehensive IPv4 and IPv6 private/link-local/metadata CIDRs using verified TLS, SSRF exploitation is systematically mitigated.
2. **Timezone Preservation (Observation 1.2)**: `2026-11-10T23:13:00.000Z` represents `2026-11-11 02:13:00` in Istanbul. Preserving the `Z` group despite fractional seconds allows datetime parsing to convert UTC to Istanbul time, preventing morning news from being erroneously rejected as stale.
3. **Fail-Closed Filtering (Observation 1.2, 1.3)**: Stripping `<script>`, `<style>`, `<nav>`, `<header>`, `<footer>`, and `<aside>` prior to DOM text extraction prevents Next.js hydration data and layout widgets from leaking dates and numbers into editorial validation.
4. **All-Paragraph Parity (Observation 1.3)**: Evaluating each citing block in `url_audit` ensures that if an author or generator includes a valid citation in paragraph 1 and a fabricated figure in paragraph 3, paragraph 3 is audited and blocked. This directly resolves the discrepancy where `--explain` reported diagnostics but the overall script previously exited 0.
5. **Exact Numeric Membership (Observation 1.3)**: Removing substring matching (`n in t`) prevents arbitrary two-digit hallucinated figures (e.g. `12`) from passing validation simply because a six-digit article ID or timestamp (e.g. `1287295`) existed elsewhere in the raw HTML.
6. **Cross-Language Anchoring (Observation 1.3, 1.4)**: Requiring two-sided verification (`source_term` on foreign target page AND `tr_term` in Turkish draft) enables valid translations while guaranteeing that foreign primary sources are genuinely anchored.

---

## 3. Caveats

1. **DNS Rebinding Window**: Socket address resolution in `validate_public_url()` occurs prior to `urllib.request.urlopen()`. While redirect hops are strictly validated via `SSRFSafeRedirectHandler`, connection-level IP pinning is not implemented in standard Python `urllib`. For standard editorial pipelines, this level of security satisfies all project requirements.
2. **Out-of-Range Port ValueError**: As detailed in Observation 1.1, accessing `parsed.port` on URLs with ports > 65535 or non-numeric port strings raises a `ValueError` that escapes `validate_public_url()`. While this fails closed, it is noted as an advisory finding for defensive hardening.
3. **PHP i18n Guard Failures in M1**: `tools/i18n_guard.py` failed with 22 errors strictly within Milestone 1 PHP files (`lib/McpCrudToolsBase.class.php`, `lib/McpFaqTools.class.php`, `lib/McpNewsTools.class.php`, `tasks/mcp_smoke.php`). Milestone 2 owns only Python and JSON files under `.agents/skills/`, all of which passed their quality guards.

---

## 4. Conclusion

The Milestone 2 implementation across `.agents/skills/_shared/editorial_http.py`, `check_freshness.py`, `verify_facts_and_links.py`, and `dossier.schema.json` satisfies all functional requirements and security invariants of Features 13–23. The work exhibits high engineering quality and zero integrity violations.

**Verdict: APPROVE** (with 1 Minor Robustness Advisory regarding out-of-range port error handling in `editorial_http.py`).

---

## 5. Verification Method

To independently reproduce this verification:

```bash
# 1. Compilation
python3 -m py_compile .agents/skills/_shared/editorial_http.py \
                      .agents/skills/kevtemen/scripts/check_freshness.py \
                      .agents/skills/kevrast/scripts/verify_facts_and_links.py

# 2. SSRF, Regex, and Gate Hardening Test Suite
python3 -c "
import json, subprocess, sys
sys.path.insert(0, '.agents/skills/_shared')
import editorial_http as eh

# SSRF Checks
for u in ['http://127.0.0.1/', 'http://169.254.169.254/', 'http://100.64.0.1/', 'http://[::1]/', 'http://[fd00::1]/', 'file:///etc/hosts']:
    assert not eh.validate_public_url(u)[0]

# ISO Fractional Seconds
r15 = subprocess.run(['python3', '.agents/skills/kevtemen/scripts/check_freshness.py', '--date', '2026-11-10T23:13:00.000Z', '--json'], capture_output=True, text=True)
assert json.loads(r15.stdout)['passed'] is True

# Near-midnight
r18 = subprocess.run(['python3', '.agents/skills/kevtemen/scripts/check_freshness.py', '--date', '10.11.2026 23:13', '--json'], capture_output=True, text=True)
assert json.loads(r18.stdout)['targets'][0]['is_yesterday_late_evening'] is True

# Kevrast All-Paragraph & Substring Elimination
sys.path.insert(0, '.agents/skills/kevrast/scripts')
from verify_facts_and_links import KevrastAuditor, tr_fold, stem_institutional
aud = KevrastAuditor()
page = '<html><head><script>var id=1287295;</script></head><body><p>Toplam 80.000 kisi katildi.</p></body></html>'
assert aud.anchoring('12 kisi katildi.', page)['status'] == 'block'
assert aud.anchoring('80 bin kisi katildi.', page)['status'] == 'pass'
assert aud.classify_precision('https://vendor.org/releases', role='supplementary')['precise'] is True
assert aud.classify_precision('https://news.org/spor', role='supplementary')['precise'] is False
assert tr_fold('İÇİŞLERİ') == tr_fold('içişleri')
assert stem_institutional('Valiliğinden') == 'valiliği'
print('ALL VERIFICATIONS PASSED!')
"

# 3. Quality Guards
python3 tools/code_separation_guard.py
python3 tools/module_boundary_guard.py --all
```

**Invalidation Conditions:**
- If `validate_public_url("http://127.0.0.1/")` returns `(True, "")`.
- If `anchoring("12 kisi", "id=1287295")` evaluates `pass`.
- If `check_freshness.py --date "2026-11-10T23:13:00.000Z"` evaluates `same_day: false`.
- If `audit_draft()` exits 0 when paragraph 3 contains a hallucinated figure.
