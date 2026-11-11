# Handoff Report: Reviewer M2-1 (Code Quality, Interface Conformance & Adversarial Audit)

**Agent:** Reviewer M2-1 (Code Quality & Interface Conformance)  
**Roles:** reviewer, critic  
**State Folder:** `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/reviewer_m2_1/`  
**Working Directory:** `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin`  
**Target Milestone:** Milestone 2 (Shared HTTP Client & Editorial Gate Hardening, Features 13–23)  
**Parent Agent:** `5c7538a8-5eae-4af0-8169-9ca427353718` (`sub_orch_m2`)  
**Date:** 2026-11-11  

---

## 1. Observation

Direct empirical inspection, AST analysis, compilation, and test execution against all M2 target files yielded the following findings:

### 1.1 Integrity & Facade Verification
- Examined `.agents/skills/_shared/editorial_http.py`, `.agents/skills/kevtemen/scripts/check_freshness.py`, `.agents/skills/kevrast/scripts/verify_facts_and_links.py`, and `.agents/skills/kevkolin/scripts/dossier.schema.json`.
- **Zero hardcoded test outputs**: No conditional shortcuts based on test URLs, hardcoded fixture text, or fabricated responses exist.
- **Genuine logic**: Transport uses standard library `socket`, `ssl`, and `urllib`; gate scripts perform comprehensive regex and DOM parsing; caching writes real atomic disk files via temporary files and `os.replace`.
- **Integrity Status**: **CLEAN** (No integrity violations detected).

### 1.2 Syntax, Types & Interface Conformance
- Executed compilation:
  `python3 -m py_compile .agents/skills/_shared/editorial_http.py .agents/skills/kevtemen/scripts/check_freshness.py .agents/skills/kevrast/scripts/verify_facts_and_links.py` -> Exited 0 with zero warnings or errors.
- Verified interface contracts against `PROJECT.md` (§88–92):
  - `editorial_http.fetch_html(url: str, timeout: int = 15, max_bytes: int = 2_000_000) -> Tuple[int, str, Dict[str, str]]`: Exact match.
  - `editorial_http.fetch_binary(url: str, timeout: int = 20, max_bytes: int = 10_000_000) -> Tuple[int, bytes, Dict[str, str]]`: Exact match.
  - `editorial_http.validate_public_url(url: str) -> Tuple[bool, str]`: Exact match.
- Quality guard execution:
  - `python3 tools/code_separation_guard.py`: **PASSED** (0 violations found).
  - `python3 tools/module_boundary_guard.py --all`: **PASSED** (9,836 files scanned, 0 violations found).
  - `python3 tools/i18n_guard.py`: Reports 22 critical violations, **all strictly confined to Milestone 1 PHP files** (`lib/McpCrudToolsBase.class.php`, `lib/McpFaqTools.class.php`, `lib/McpNewsTools.class.php`, `tasks/mcp_smoke.php`). `.agents/skills/` is excluded by design in `i18n_guard.py` (`EXCLUDE_DIRS = {".agents", ...}`). M2's targets are 100% written in English with standard identifiers and docstrings.

### 1.3 Transport & SSRF Guard (`editorial_http.py`)
- Empirical test across 22 malicious SSRF vectors (`127.0.0.1`, `0.0.0.0`, `localhost`, `169.254.169.254`, `10.0.0.1`, `172.16.0.1`, `192.168.1.1`, `100.64.0.1`, `[::1]`, `[fe80::1]`, `[fc00::1]`, `[::ffff:127.0.0.1]`, `192.0.2.1`, `198.51.100.1`, `203.0.113.1`, `224.0.0.1`, `240.0.0.1`, `255.255.255.255`, `file://`, `ftp://`, `gopher://`): All 22 correctly blocked returning `(False, "<reason>")`.
- Verified `_parse_retry_after`: Correctly handles integer seconds (`120` -> 120.0s), HTTP RFC-date (`Wed, 11 Nov 2026 11:24:06 GMT` -> 59.3s), and negative past dates -> 0.0s.
- Verified response cache: Normalized cache keys correctly equate `https://example.com/test?b=2&a=1#fragment1` and `https://example.com/test?b=2&a=1#fragment2`. Caching stores text as raw strings, binary as Base64, respects TTL expiration, and ignores non-2xx status codes.

### 1.4 Freshness Sentinel Hardening (`check_freshness.py`)
- Empirical test on fractional seconds:
  - `--date "2026-11-10T23:13:00.000Z"` -> Converted to UTC -> `2026-11-11T02:13:00+03:00` (today in Europe/Istanbul), evaluating `"same_day": true`, `"status": "PASSED (Same-Day)"`.
  - `--date "2026-11-10T23:13:00,500Z"` (comma separator) -> Evaluates `"same_day": true`.
  - `--date "2026-11-10T20:13:00.000+00:00"` (20:13 UTC = 23:13 Istanbul, yesterday late evening) -> Evaluates `"same_day": false`, `"is_yesterday_late_evening": true`, `"age_hours": 15.18`.
- Non-content tag stripping: `<script>`, `<style>`, `<aside>`, `<nav>`, `<footer>`, `<noscript>` elements are stripped before DOM text date scanning. A page with only `<script>var now="11.11.2026";</script>` or `<aside>Yayinlanma tarihi: 11.11.2026</aside>` evaluates as `state: "undated"`.
- URL deduplication: Multiple citations in `--text` of the same URL are deduplicated into a single audit entry with `citation_count` tracked (`[cited 3x]`).

### 1.5 Kevrast Gate Hardening & Schema (`verify_facts_and_links.py` & `dossier.schema.json`)
- All-paragraph evaluation: Evaluated draft with clean citing paragraph in P1 and fabricated number `99` in P3 citing the same URL. `audit_draft(explain=True)` successfully blocked with `[P3] [R3_MISSING_NUMBER]` and exit code 1 (`BLOCKED`), resolving previous diagnostic divergence.
- Figure extraction: Replaced `or n in t` substring matching with exact token set membership `n not in target_nums_set`. Testing `12` against a page containing ID `1287295` successfully blocked with `missing_numbers: ['12']`.
- Single-segment vendor allowlist: `https://github.com/releases` with role `product` passed with `precise: true, kind: "vendor_product_page"`. A page with 3 `<article>` elements was blocked with `precise: false, kind: "section_front"`.
- Cross-language anchoring: Verified bilingual pairs (`source_term` <-> `tr_term`). Correctly passed when both present, and blocked with detailed reason when either foreign term or Turkish term was absent.
- Schema validation: Rejected invalid role `"advertisement"`, invalid language `"zh"`, missing `tr_term`, short `source_term` (<3 chars), and empty `sources` array.

---

## 2. Logic Chain

1. **Vulnerability Mitigation**: The prior implementation permitted `CERT_NONE` SSL contexts and unvalidated URLs. Introducing `editorial_http.py` with verified TLS (`ssl.create_default_context()`), SSRF filtering over `ipaddress`, and redirect interception prevents SSRF and impersonation risks across all downstream newsroom tools.
2. **Deterministic Timezone Integrity**: By restructuring `RE_ISO` to use non-capturing groups for fractional seconds `(?:[.,]\d{1,9})?`, group 7 consistently captures the timezone token (`Z` or `±HH:MM`), preventing premature truncation to naive Istanbul time.
3. **Clean Provenance**: Substring matching (`n in t`) allowed false negatives when short numbers appeared as fragments of article IDs or phone numbers. Enforcing exact token matching and stripping non-content element bodies guarantees that claims are truly anchored in editorial body text.
4. **Parity Between Exit Status & Diagnostics**: By auditing all blocks from `_find_all_citing_blocks` rather than only the first block, `audit_draft()` guarantees that any hallucinated claim anywhere in the article is reported with its 1-based paragraph index (`[P3]`) and triggers a non-zero exit code.

---

## 3. Caveats

1. **DNS Rebinding Window (Advisory)**: `validate_public_url()` resolves DNS, checks the resulting IP, and then `opener.open()` resolves DNS a second time. While acceptable for internal pipeline usage against trusted sources, a custom socket adapter pinning the resolved IP could eliminate the theoretical TOCTOU window.
2. **Regex Coverage for Active Participle (Advisory)**: `OUTLET_ATTRIBUTION_RE` catches `aktarılan bilgilere göre` but misses `aktardığı bilgilere göre`. This does not break existing test suites, but extending the regex will prevent subtle unlinked attribution escapes in future drafts.
3. **Microdata `itemprop` (Advisory)**: `check_freshness.py` prioritizes `name` and `property` in `RE_META_PUB`. Pages utilizing Schema.org microdata (`<meta itemprop="datePublished" content="...">`) without OpenGraph or JSON-LD may fall through to body scanning.
4. **Upstream i18n Violations (External)**: `tools/i18n_guard.py` exits 1 due to M1 PHP code (`$cand`, `$resDup`, etc.). M2 has zero PHP changes and does not touch those files.

---

## 4. Conclusion & Verdict

**VERDICT: APPROVE**

Worker M2 has implemented all 11 required features (13 through 23) cleanly, correctly, and without integrity violations. All interface contracts match `PROJECT.md` and `SCOPE.md`. All unit tests, quality guards (separation, boundary), and adversarial challenge tests pass.

---

## 5. Verification Method

To independently reproduce and verify this review, execute the following commands in the workspace root:

```bash
# 1. Compilation Verification
python3 -m py_compile .agents/skills/_shared/editorial_http.py \
                      .agents/skills/kevtemen/scripts/check_freshness.py \
                      .agents/skills/kevrast/scripts/verify_facts_and_links.py

# 2. Quality Guards
python3 tools/code_separation_guard.py
python3 tools/module_boundary_guard.py --all

# 3. Adversarial SSRF, Timezone & Gate Test Suite
python3 -c "
import sys, json, tempfile, subprocess
sys.path.insert(0, '.agents/skills/_shared')
import editorial_http as eh

# SSRF Blocks
for u in ['http://127.0.0.1/', 'http://169.254.169.254/', 'http://100.64.0.1/', 'http://[::1]/', 'file:///etc/hosts']:
    assert not eh.validate_public_url(u)[0]

# Freshness UTC & Late-Evening Check
r1 = subprocess.run(['python3', '.agents/skills/kevtemen/scripts/check_freshness.py', '--date', '2026-11-10T23:13:00.000Z', '--json'], capture_output=True, text=True)
assert json.loads(r1.stdout)['passed'] is True

r2 = subprocess.run(['python3', '.agents/skills/kevtemen/scripts/check_freshness.py', '--date', '2026-11-10T20:13:00.000+00:00', '--json'], capture_output=True, text=True)
res2 = json.loads(r2.stdout)
assert res2['passed'] is False and res2['targets'][0]['is_yesterday_late_evening'] is True

# Kevrast All-Paragraph & Numeric Rigor
sys.path.insert(0, '.agents/skills/kevrast/scripts')
from verify_facts_and_links import KevrastAuditor
aud = KevrastAuditor()
page = '<html><head><script>var id=1287295;</script></head><body><p>Toplam 80.000 kisi katildi.</p></body></html>'
assert aud.anchoring('12 kisi katildi.', page)['status'] == 'block'
assert aud.anchoring('80 bin kisi katildi.', page)['status'] == 'pass'
assert aud.classify_precision('https://vendor.org/releases', role='product')['precise'] is True
assert aud.classify_precision('https://news.org/spor', role='product')['precise'] is False
print('REVIEWER VERIFICATION SUITE: ALL PASSED!')
"

# 4. Dossier Schema Validation CLI
python3 .agents/skills/kevrast/scripts/verify_facts_and_links.py --validate-dossier tests/e2e/fixtures/editorial/dossier_today_rudaw_park.json
```

---

## Quality Review Summary

- **Correctness**: Fully compliant with RFC 7231, RFC 6598, and Kevanger pipeline rules.
- **Logical Completeness**: Complete handling of group indices in regex, proper group unpacking, and clear error cascades.
- **Code Quality**: Clean Python 3 typing, descriptive identifiers, robust fallback handling, and zero inline script/style pollution.
- **Risk Assessment**: Low risk. All changes are backward compatible with previous pipeline CLI conventions.

### Findings

#### [Minor] Finding 1: `tools/i18n_guard.py` exit code 1 on Milestone 1 PHP files
- **What**: Automated `python3 tools/i18n_guard.py` execution exits with code 1 reporting 22 token violations.
- **Where**: `lib/McpCrudToolsBase.class.php`, `lib/McpFaqTools.class.php`, `lib/McpNewsTools.class.php`, `tasks/mcp_smoke.php`.
- **Why**: Worker M1 introduced variables (`$cand*`, `$resDup`, `$dupPayload`), comment words (`intra`, `Jaccard`), and method name `addEqFilter` that fail dictionary checks.
- **Suggestion**: Inform Orchestrator/Sub-Orchestrator M1 to clean up these tokens in M1 files or whitelist them in `DEV_TERMS` in `i18n_guard.py`.

#### [Minor / Suggestion] Finding 2: `OUTLET_ATTRIBUTION_RE` active participle coverage
- **What**: Regex line 88 matches passive participle `aktarılan bilgilere göre` but misses active participle `aktardığı bilgilere göre`.
- **Where**: `.agents/skills/kevrast/scripts/verify_facts_and_links.py:88`
- **Why**: An unlinked sentence like `<p>Rudaw'ın aktardığı bilgilere göre...</p>` will not trigger `R4_UNLINKED_ATTRIBUTION` if another link exists in the article.
- **Suggestion**: Expand regex to: `(?:aktarılan|aktardığı|paylaşılan|paylaştığı|bildirdiği|verilen|verdiği|yayımlanan|yayımladığı)\s+bilgilere\s+göre|`

#### [Minor / Suggestion] Finding 3: `extract_pub_date` Microdata `itemprop`
- **What**: `RE_META_PUB` inspects `name` and `property` attributes but omits `itemprop`.
- **Where**: `.agents/skills/kevtemen/scripts/check_freshness.py:58-60`
- **Why**: HTML5 Schema.org microdata `<meta itemprop="datePublished" content="...">` is not matched by Priority 1 meta-scanning.
- **Suggestion**: Add `itemprop` to the attribute options: `(?:property|name|itemprop)=[\'\"]...`

---

## Adversarial Challenge Summary

**Overall risk assessment**: **LOW**

### Challenge 1: Substring figure collision attack
- **Assumption challenged**: Can an LLM draft hallucinate a small figure (`12`) by hiding behind an unrelated 7-digit ID (`1287295`) on the target page?
- **Attack scenario**: Tested `aud.anchoring('12 kisi katildi.', 'Haber no 1287295. Toplam 80.000 kisi katildi.')`.
- **Result**: PASSED. Exact set matching `n not in target_nums_set` rejected `12` with `missing_numbers: ['12']`.

### Challenge 2: UTC fractional seconds midnight rollover attack
- **Assumption challenged**: Can an article published at 23:13:00.000Z yesterday be misclassified as stale if timezone conversion fails?
- **Attack scenario**: Evaluated `2026-11-10T23:13:00.000Z` against today `2026-11-11`.
- **Result**: PASSED. Correctly converted to `02:13:00+03:00` (today), evaluated as `"same_day": true`.

### Challenge 3: SSRF IPv4-mapped IPv6 & CGNAT bypass attack
- **Assumption challenged**: Can an attacker bypass IP filters using `100.64.0.1` (CGNAT) or `::ffff:127.0.0.1` (IPv4-mapped IPv6)?
- **Attack scenario**: Tested both formats against `validate_public_url()`.
- **Result**: PASSED. Both correctly identified and blocked.
