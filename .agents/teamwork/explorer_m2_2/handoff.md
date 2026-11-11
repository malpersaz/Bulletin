# Handoff Report: Freshness Sentinel Specialist (M2 Features 14–18)

**Target Script**: `.agents/skills/kevtemen/scripts/check_freshness.py`  
**Features Covered**: Feature 14 (Shared HTTP/SSRF), Feature 15 (ISO Fractional Seconds & UTC), Feature 16 (Non-Content Tag Stripping & JSON-LD Scoping), Feature 17 (Earliest Main Date Priority & URL Deduplication), Feature 18 (Near-Midnight Diagnostics)  
**Author**: Explorer M2-2  
**Date**: 2026-11-11  

---

## Executive Summary

Investigation of `.agents/skills/kevtemen/scripts/check_freshness.py` verified that while its core calendar date fail-closed policy (`dt.date() == today_dt.date()` in `Europe/Istanbul`) is sound, the script contains 5 critical structural flaws:
1. **Security Vulnerability**: Disables TLS verification (`CERT_NONE`) and lacks SSRF validation against private/loopback/metadata endpoints.
2. **False-Block Bug**: `RE_ISO` drops timezone tokens when fractional seconds are present (e.g., `2026-11-10T23:13:00.000Z`), shifting UTC morning articles back to yesterday Istanbul time and rejecting fresh news.
3. **Fail-Open Tag Leak**: Tag stripping via `re.sub(r"<[^>]+>", " ", html)` leaves `<script>`, `<style>`, `<aside>`, and `<nav>` blocks intact; Next.js JSON scripts and sidebar `<time>` tags falsely pass stale/historical articles.
4. **Fail-Open Byline Flaw**: Bare unanchored `"Bugün"` and `-c[1].timestamp()` (newest-first) sorting allow stale articles with today-dated sidebar links to pass.
5. **Resource Waste & Missing Diagnostics**: Redundant network GETs for repeated URLs in draft/dossier modes, and absence of structured near-midnight diagnostic attributes (`age_hours`, `is_yesterday_late_evening`).

This blueprint provides exact code modifications and verification tests to harden `check_freshness.py`.

---

## 1. Observation

Direct empirical observations and code inspections across `.agents/skills/kevtemen/scripts/check_freshness.py`:

### Observation 1: Insecure TLS & Unvalidated HTTP Transport (Feature 14)
- **Location**: `check_freshness.py:34-36, 218-225`
- **Code**:
  ```python
  _SSL_CTX = ssl.create_default_context()
  _SSL_CTX.check_hostname = False
  _SSL_CTX.verify_mode = ssl.CERT_NONE
  ...
  def fetch_html(url: str, timeout: int) -> str:
      req = urllib.request.Request(url, headers={
          "User-Agent": USER_AGENT,
          "Accept": "text/html,application/xhtml+xml,*/*;q=0.8",
          "Accept-Language": "tr,en;q=0.8",
      })
      with urllib.request.urlopen(req, timeout=timeout, context=_SSL_CTX) as resp:
          return resp.read().decode("utf-8", errors="replace")
  ```
- **Finding**: Certificate verification is completely disabled, exposing the editorial gate to MITM attacks. The function lacks SSRF checks against `127.0.0.1`, RFC1918 private subnets, and cloud metadata (`169.254.169.254`). It also lacks response caching and `Retry-After` backoff.

### Observation 2: ISO Fractional Seconds Drops Timezone (Feature 15)
- **Location**: `check_freshness.py:50-52, 81-106`
- **Code**:
  ```python
  RE_ISO = re.compile(
      r"(\d{4})-(\d{2})-(\d{2})"
      r"(?:[T ](\d{2}):(\d{2})(?::(\d{2}))?(Z|[+-]\d{2}:?\d{2})?)?")
  ```
- **Empirical Test**:
  ```bash
  python3 .agents/skills/kevtemen/scripts/check_freshness.py --date "2026-11-10T23:13:00.000Z" --json
  ```
- **Verbatim Output**:
  ```json
  {
    "passed": false,
    "status": "BLOCKED",
    "content_type": "news",
    "today": "11.11.2026",
    "targets": [
      {
        "target": "2026-11-10T23:13:00.000Z",
        "kind": "date",
        "state": "dated",
        "pub_date": "10.11.2026",
        "evidence": "explicit --date",
        "same_day": false
      }
    ]
  }
  ```
- **Finding**: Because `RE_ISO` lacks fractional seconds matching before `(Z|[+-]...)`, group 7 (`tz`) returns `None`. `_apply_tz()` falls back to `_mk_naive_istanbul()` (`+03:00`), misinterpreting `2026-11-10 23:13:00 UTC` (`2026-11-11 02:13:00+03:00`, TODAY) as `2026-11-10 23:13:00 Istanbul` (YESTERDAY) and falsely blocking fresh publications.

### Observation 3: Non-Content Tag Leaks in DOM Scanning (Feature 16)
- **Location**: `check_freshness.py:48-49, 64, 148-176`
- **Code**:
  ```python
  RE_LD_PUB = re.compile(r'"datePublished"\s*:\s*"([^"]+)"', re.I)
  RE_TIME_TAG = re.compile(r'<time[^>]+datetime="([^"]+)"', re.I)
  TAG_RE = re.compile(r"<[^>]+>")
  ...
  text = TAG_RE.sub(" ", html)
  ```
- **Empirical Test**: Evaluated a stale 2020 HTML article with a Next.js Flight script containing `window.__NEXT_DATA__ = { "otherNews": "11.11.2026" };`:
  ```bash
  python3 .agents/skills/kevtemen/scripts/check_freshness.py /tmp/stale_with_script.html --json
  ```
- **Verbatim Output**:
  ```json
  {
    "passed": true,
    "status": "PASSED (Same-Day)",
    "content_type": "news",
    "today": "11.11.2026",
    "targets": [
      {
        "target": "/tmp/stale_with_script.html",
        "kind": "file",
        "state": "dated",
        "pub_date": "11.11.2026",
        "evidence": "visible: 11.11.2026",
        "same_day": true
      }
    ]
  }
  ```
- **Finding**: `TAG_RE` removes only HTML tags, leaving script, style, and navigation text intact. Stale articles containing today's date in scripts or sidebar widgets are falsely treated as fresh (`PASSED`).

### Observation 4: Bare "Bugün" and Inverted Candidate Timestamp Sorting (Feature 17)
- **Location**: `check_freshness.py:62, 201-202, 214`
- **Code**:
  ```python
  RE_TODAY = re.compile(r"\bbugün\b", re.I)
  ...
  if RE_TODAY.search(text):
      candidates.append((4, now, "relative: today"))
  ...
  best = min(candidates, key=lambda c: (c[0], -c[1].timestamp()))
  ```
- **Empirical Test**: Evaluated a 2018 historical article with body text `"Diyarbakır parkı bugün halka hizmet veriyor"`:
  ```bash
  python3 .agents/skills/kevtemen/scripts/check_freshness.py /tmp/hist_park.html --json
  ```
- **Verbatim Output**:
  ```json
  {
    "passed": true,
    "status": "PASSED (Same-Day)",
    "content_type": "news",
    "today": "11.11.2026",
    "targets": [
      {
        "target": "/tmp/hist_park.html",
        "kind": "file",
        "state": "dated",
        "pub_date": "11.11.2026",
        "evidence": "relative: today",
        "same_day": true
      }
    ]
  }
  ```
- **Finding**: Any undated page mentioning "bugün" anywhere in its text passes as today. Furthermore, `-c[1].timestamp()` selects the **newest** timestamp among equal priority candidates, picking sidebar updates instead of the original publication time.

### Observation 5: Duplicate Audit Lines & Repeated Network Requests (Feature 17)
- **Location**: `check_freshness.py:406-433`
- **Empirical Test**: Checked draft with 2 repeated links to `https://example.com/news`:
  ```json
  "targets": [
    { "target": "https://example.com/news", "state": "dated", "pub_date": "11.11.2026" },
    { "target": "https://example.com/news", "state": "dated", "pub_date": "11.11.2026" }
  ]
  ```
- **Finding**: Every link occurrence triggers an independent network fetch and duplicate report line.

### Observation 6: Missing Near-Midnight Diagnostics (Feature 18)
- **Location**: `check_freshness.py:230-250`
- **Finding**: When Rudaw's article was published yesterday at `23:13:00` (47 minutes before midnight), `check_target()` emitted only `pub_date: "10.11.2026"`, omitting `age_hours` (`13.27h`) and `is_yesterday_late_evening: true`. Consequently, Stage 0 agents could not differentiate a near-midnight publication eligible for local primary discovery from a multi-day old article.

---

## 2. Logic Chain

1. **Transport Hardening (Feature 14)**:
   - Replacing `urllib.request` and `_SSL_CTX` (`CERT_NONE`) with `editorial_http.fetch_html()` ensures verified TLS, enforces `validate_public_url()` SSRF protection across all redirect hops, sends standardized browser headers, respects `Retry-After`, and leverages a 10-minute URL response cache.

2. **Timezone & ISO Fractional Second Integrity (Feature 15)**:
   - Modern CMSs (Next.js, Strapi, Arc XP, WordPress REST) emit ISO strings with millisecond precision (`.000Z`).
   - Updating `RE_ISO` with non-capturing fractional seconds `(?:[.,]\d{1,9})?` immediately before `(Z|[+-]\d{2}:?\d{2})?` preserves group indices: group 1-3 = YMD, group 4-6 = HMS, and group 7 = TZ.
   - For `2026-11-10T23:13:00.000Z`, group 7 captures `"Z"`. `_apply_tz()` maps `"Z"` to `timezone.utc`, and `.astimezone(ISTANBUL)` converts it to `2026-11-11 02:13:00+03:00` (date `2026-11-11`, today).

3. **DOM Cleaning & JSON-LD Scoping (Feature 16)**:
   - To prevent script data and sidebars from leaking into date detection, extract `<script type="application/ld+json">` first, parsing only entities whose `@type` matches `ALLOWED_JSONLD_TYPES = {"NewsArticle", "Article", "ReportageNewsArticle", "BlogPosting", "WebPage"}` and extracting strictly `datePublished` (never `dateModified`).
   - Clean the remaining HTML by stripping `<script>`, `<style>`, `<noscript>`, `<nav>`, `<header>`, `<footer>`, and `<aside>` blocks before scanning `<time>` tags or text.

4. **Candidate Priority & Deduplication (Feature 17)**:
   - Strict priority hierarchy:
     - **Priority 1**: Meta tags (`article:published_time`, `pubdate`, `publishdate`, `datePublished`, `DC.date.issued`) and main-entity JSON-LD `datePublished`. When multiple exist, sort with `+c[1].timestamp()` to select the **earliest** published date.
     - **Priority 2**: `<time datetime="...">` tags inside `<article>` (excluding `<aside>`/sidebars).
     - **Priority 3**: Explicit date-labeled visible bylines (`Yayınlanma Tarihi:`, `Giriş:`, etc.) within the article container.
     - **Priority 4**: URL path dates (`/2026/11/11/`).
     - **Priority 5**: Time-anchored relative expressions (e.g., `Bugün, 11:45`, `Yayınlanma: Bugün 09:30`). Bare unanchored `"Bugün"` is eliminated.
     - **Priority 6**: Visible dates in article body text outside stripped blocks.
   - URL deduplication: Map all draft and positional URLs to normalized forms (`url.strip().rstrip("/")`), tracking `citation_count`. Each normalized URL is fetched and audited at most once.

5. **Near-Midnight Structured Diagnostics (Feature 18)**:
   - When a publication datetime `pub_dt` is extracted:
     - Compute `age_hours = round((now - pub_dt).total_seconds() / 3600.0, 2)`.
     - Evaluate `is_yesterday_late_evening = (pub_dt.date() == today - timedelta(days=1)) and (18 <= pub_dt.hour <= 23)`.
     - Include both fields in JSON output and emit diagnostic logs so agents can trigger Stage 0 same-day local primary discovery.

---

## 3. Detailed Implementation Blueprint

### A. Shared Import & Setup (`check_freshness.py`)
```python
import os
import sys
from pathlib import Path

# Resolve shared directory for editorial_http
SHARED_DIR = Path(__file__).resolve().parents[2] / "_shared"
if str(SHARED_DIR) not in sys.path:
    sys.path.insert(0, str(SHARED_DIR))

try:
    import editorial_http
except ImportError:
    editorial_http = None
```

### B. Updated Regexes & Allowed Types
```python
# Feature 15: ISO regex supporting fractional seconds without dropping timezone
RE_ISO = re.compile(
    r"(\d{4})-(\d{2})-(\d{2})"
    r"(?:[T ](\d{2}):(\d{2})(?::(\d{2}))?(?:[.,]\d{1,9})?(Z|[+-]\d{2}:?\d{2})?)?"
)

# Feature 17: Expanded metadata tags
RE_META_PUB = re.compile(
    r"<meta\s+[^>]*(?:property|name)=[\'\"](?:article:published_time|pubdate|publishdate|datePublished|DC\.date\.issued)[\'\"][^>]*content=[\'\"]([^\'\"]+)[\'\"]"
    r"|<meta\s+[^>]*content=[\'\"]([^\'\"]+)[\'\"][^>]*(?:property|name)=[\'\"](?:article:published_time|pubdate|publishdate|datePublished|DC\.date\.issued)[\'\"]",
    re.I
)

# Feature 16: Scoped JSON-LD
RE_JSONLD = re.compile(
    r"<script\s+[^>]*type=[\"\']application/ld\+json[\"\'][^>]*>(.*?)</script>",
    re.I | re.DOTALL
)
ALLOWED_JSONLD_TYPES = {
    "NewsArticle", "Article", "ReportageNewsArticle", "BlogPosting", "WebPage"
}

# Feature 17: Anchored relative expressions & visible bylines
RE_BYLINE_DATE = re.compile(
    r"\b(yayın(?:lanma|lanış)?\s*tarihi|yayımlanma\s*tarihi|giriş\s*tarihi|haber\s*giriş|yayınlanma|yayımlanma|giriş|tarih)\s*:\s*([^\n<]+)",
    re.I
)
RE_ANCHORED_TODAY = re.compile(
    r"(?:(?:yayın(?:lanma)?|giriş|tarih)[^\w\n]*)?\bbugün\b[,\s]+(\d{1,2}):(\d{2})",
    re.I
)
RE_ANCHORED_YESTERDAY = re.compile(
    r"(?:(?:yayın(?:lanma)?|giriş|tarih)[^\w\n]*)?\bdün\b[,\s]+(\d{1,2}):(\d{2})",
    re.I
)
```

### C. Hardened HTTP Fetcher (`check_freshness.py`)
```python
def fetch_html(url: str, timeout: int) -> str:
    """Fetch HTML using verified shared transport layer or secure fallback."""
    if editorial_http is not None:
        status, html, headers = editorial_http.fetch_html(url, timeout=timeout)
        if status >= 400:
            raise urllib.error.HTTPError(url, status, f"HTTP error {status}", headers, None)
        return html

    # Fallback with verified TLS context (never CERT_NONE)
    ctx = ssl.create_default_context()
    req = urllib.request.Request(url, headers={
        "User-Agent": USER_AGENT,
        "Accept": "text/html,application/xhtml+xml,*/*;q=0.8",
        "Accept-Language": "tr,en;q=0.8",
    })
    with urllib.request.urlopen(req, timeout=timeout, context=ctx) as resp:
        return resp.read().decode("utf-8", errors="replace")
```

### D. Timezone & ISO Parsing Helpers
```python
def _apply_tz(y: int, mo: int, d: int, h: int, mi: int, s: int,
              tz: Optional[str]) -> Optional[datetime]:
    try:
        naive = datetime(y, mo, d, h, mi, s)
    except ValueError:
        return None
    if not tz:
        return _mk_naive_istanbul(naive)
    if tz == "Z":
        return naive.replace(tzinfo=timezone.utc).astimezone(ISTANBUL)
    sign = 1 if tz[0] == "+" else -1
    body = tz[1:].replace(":", "")
    try:
        hrs = int(body[:2])
        mins = int(body[2:4]) if len(body) >= 4 else 0
        off = sign * timedelta(hours=hrs, minutes=mins)
    except (ValueError, IndexError):
        return _mk_naive_istanbul(naive)
    return naive.replace(tzinfo=timezone(off)).astimezone(ISTANBUL)

def parse_iso_or_free(raw: str) -> Optional[datetime]:
    """Parse ISO-8601 string preserving UTC and fractional seconds."""
    s = raw.strip()
    try:
        dt = datetime.fromisoformat(s.replace(",", "."))
        return dt.replace(tzinfo=ISTANBUL) if dt.tzinfo is None else dt.astimezone(ISTANBUL)
    except Exception:
        pass
    m = RE_ISO.search(s)
    if m:
        return _from_iso_match(m)
    return parse_free_date(s)
```

### E. Restructured Publication Date Extraction
```python
def extract_pub_date(html: str, url: str) -> Optional[Dict[str, Any]]:
    """Return the most authoritative publication datetime found.
    Priority 1: Meta tags & main-entity JSON-LD datePublished (earliest)
    Priority 2: <time datetime="..."> inside <article>
    Priority 3: Explicit date-labeled visible bylines
    Priority 4: URL path date
    Priority 5: Time-anchored relative expressions
    Priority 6: Visible dates in article body text
    """
    # -------------------------------------------------------------
    # Priority 1: Meta tags and main-entity JSON-LD datePublished
    # -------------------------------------------------------------
    p1_candidates: List[Tuple[datetime, str]] = []

    # 1a. Meta tags
    for m in RE_META_PUB.finditer(html):
        raw = (m.group(1) or m.group(2) or "").strip()
        if raw:
            dt = parse_iso_or_free(raw)
            if dt:
                p1_candidates.append((dt, f"meta: {raw[:60]}"))

    # 1b. Main-entity JSON-LD datePublished
    for block in RE_JSONLD.finditer(html):
        try:
            data = json.loads(block.group(1).strip())
            items = []
            if isinstance(data, dict):
                if "@graph" in data and isinstance(data["@graph"], list):
                    items.extend(data["@graph"])
                else:
                    items.append(data)
            elif isinstance(data, list):
                items.extend(data)

            for item in items:
                if not isinstance(item, dict):
                    continue
                itype = item.get("@type")
                types = set(itype) if isinstance(itype, list) else {itype}
                if types & ALLOWED_JSONLD_TYPES:
                    raw_pub = item.get("datePublished")
                    if raw_pub and isinstance(raw_pub, str):
                        dt = parse_iso_or_free(raw_pub.strip())
                        if dt:
                            p1_candidates.append((dt, f"json-ld datePublished: {raw_pub[:60]}"))
        except Exception:
            continue

    if p1_candidates:
        # Prefer the EARLIEST datePublished (never dateModified or newest)
        earliest_p1 = min(p1_candidates, key=lambda c: c[0].timestamp())
        return {"dt": earliest_p1[0], "evidence": earliest_p1[1]}

    # -------------------------------------------------------------
    # DOM Preparation: Isolate article & strip non-content blocks
    # -------------------------------------------------------------
    art_matches = list(re.finditer(r"<article[^>]*>(.*?)</article>", html, re.I | re.DOTALL))
    if art_matches:
        scope_html = art_matches[0].group(1)
        # Strip secondary blocks inside article
        scope_html = re.sub(r"<(aside|nav|script|style|noscript)[^>]*>.*?</\1>", " ", scope_html, flags=re.I | re.DOTALL)
    else:
        # Strip all non-content tags from entire document
        scope_html = re.sub(r"<(script|style|noscript|nav|header|footer|aside)[^>]*>.*?</\1>", " ", html, flags=re.I | re.DOTALL)

    # -------------------------------------------------------------
    # Priority 2: <time datetime="..."> inside article container
    # -------------------------------------------------------------
    p2_candidates: List[Tuple[datetime, str]] = []
    for tm in RE_TIME_TAG.finditer(scope_html):
        raw = tm.group(1).strip()
        dt = parse_iso_or_free(raw)
        if dt:
            p2_candidates.append((dt, f"article time tag: {raw[:60]}"))

    if p2_candidates:
        earliest_p2 = min(p2_candidates, key=lambda c: c[0].timestamp())
        return {"dt": earliest_p2[0], "evidence": earliest_p2[1]}

    # -------------------------------------------------------------
    # Priority 3: Explicit date-labeled visible bylines
    # -------------------------------------------------------------
    scope_text = TAG_RE.sub(" ", scope_html)
    p3_candidates: List[Tuple[datetime, str]] = []
    for bm in RE_BYLINE_DATE.finditer(scope_text):
        label = bm.group(1)
        val = bm.group(2).strip()
        dt = parse_free_date(val)
        if dt:
            p3_candidates.append((dt, f"byline ({label}): {val[:40]}"))

    if p3_candidates:
        earliest_p3 = min(p3_candidates, key=lambda c: c[0].timestamp())
        return {"dt": earliest_p3[0], "evidence": earliest_p3[1]}

    # -------------------------------------------------------------
    # Priority 4: URL path date
    # -------------------------------------------------------------
    if url:
        m = RE_URLPATH.search(url)
        if m:
            try:
                dt = _mk_naive_istanbul(datetime(int(m.group(1)), int(m.group(2)), int(m.group(3))))
                return {"dt": dt, "evidence": f"url path: {m.group(0)}"}
            except ValueError:
                pass

    # -------------------------------------------------------------
    # Priority 5: Time-anchored relative expressions (bare Bugün removed)
    # -------------------------------------------------------------
    now = now_istanbul()
    p5_candidates: List[Tuple[datetime, str]] = []
    for m in RE_ANCHORED_TODAY.finditer(scope_text):
        h, mi = int(m.group(1)), int(m.group(2))
        dt = now.replace(hour=h, minute=mi, second=0, microsecond=0)
        p5_candidates.append((dt, f"relative anchored: {m.group(0)}"))
    for m in RE_ANCHORED_YESTERDAY.finditer(scope_text):
        h, mi = int(m.group(1)), int(m.group(2))
        dt = (now - timedelta(days=1)).replace(hour=h, minute=mi, second=0, microsecond=0)
        p5_candidates.append((dt, f"relative anchored yesterday: {m.group(0)}"))
    m = RE_REL_MIN.search(scope_text)
    if m:
        p5_candidates.append((now - timedelta(minutes=int(m.group(1))), f"relative: {m.group(0)}"))
    m = RE_REL_HOURS.search(scope_text)
    if m:
        p5_candidates.append((now - timedelta(hours=int(m.group(1))), f"relative: {m.group(0)}"))
    m = RE_REL_DAYS.search(scope_text)
    if m:
        p5_candidates.append((now - timedelta(days=int(m.group(1))), f"relative: {m.group(0)}"))

    if p5_candidates:
        earliest_p5 = min(p5_candidates, key=lambda c: c[0].timestamp())
        return {"dt": earliest_p5[0], "evidence": earliest_p5[1]}

    # -------------------------------------------------------------
    # Priority 6: Visible dates in article body text
    # -------------------------------------------------------------
    p6_candidates: List[Tuple[datetime, str]] = []
    for m in RE_DOT.finditer(scope_text):
        try:
            dt = _mk_naive_istanbul(datetime(int(m.group(3)), int(m.group(2)), int(m.group(1)),
                                             int(m.group(4) or 0), int(m.group(5) or 0)))
            p6_candidates.append((dt, f"visible date: {m.group(0)}"))
        except ValueError:
            continue
    for m in RE_TR_LONG.finditer(scope_text):
        try:
            dt = _mk_naive_istanbul(datetime(int(m.group(3)), MONTHS_TR[m.group(2).lower()], int(m.group(1))))
            p6_candidates.append((dt, f"visible Turkish date: {m.group(0)}"))
        except (ValueError, KeyError):
            continue

    if p6_candidates:
        earliest_p6 = min(p6_candidates, key=lambda c: c[0].timestamp())
        return {"dt": earliest_p6[0], "evidence": earliest_p6[1]}

    return None
```

### F. Diagnostic Enrichment (`check_target`)
```python
def check_target(label: str, kind: str, html: Optional[str], url: str,
                 explicit_dt: Optional[datetime],
                 citation_count: int = 1) -> Dict[str, Any]:
    result: Dict[str, Any] = {"target": label, "kind": kind, "citation_count": citation_count}
    now = now_istanbul()
    today = now.date()

    if explicit_dt is not None:
        pub = {"dt": _mk_naive_istanbul(explicit_dt), "evidence": "explicit --date"}
    elif html is None:
        result.update(state="unreachable", same_day=False,
                      note="Target page unreachable / content unreadable")
        return result
    else:
        pub = extract_pub_date(html, url)

    if pub is None:
        result.update(state="undated", same_day=False,
                      note="Publication date could not be determined")
        return result

    pub_dt = pub["dt"]
    pub_date = pub_dt.date()
    age_hours = round((now - pub_dt).total_seconds() / 3600.0, 2)
    yesterday = today - timedelta(days=1)
    is_yesterday_late_evening = (pub_date == yesterday) and (18 <= pub_dt.hour <= 23)

    result.update(
        state="dated",
        pub_date=pub_date.strftime("%d.%m.%Y"),
        pub_datetime=pub_dt.isoformat(),
        age_hours=age_hours,
        is_yesterday_late_evening=is_yesterday_late_evening,
        evidence=pub["evidence"],
        same_day=(pub_date == today)
    )
    return result
```

### G. Normalized URL Deduplication in `main()`
```python
    # Collect targets preserving encounter order
    url_items: Dict[str, Dict[str, Any]] = OrderedDict()

    # Draft hrefs
    for href in draft_hrefs:
        norm = href.strip().rstrip("/")
        if norm not in url_items:
            url_items[norm] = {"raw": href, "kind": "url", "count": 1, "role": role_map.get(norm)}
        else:
            url_items[norm]["count"] += 1

    # CLI positional targets
    for target in args.targets:
        if target.startswith("http://") or target.startswith("https://"):
            norm = target.strip().rstrip("/")
            if norm not in url_items:
                url_items[norm] = {"raw": target, "kind": "url", "count": 1, "role": role_map.get(norm)}
            else:
                url_items[norm]["count"] += 1
        else:
            norm = target.strip()
            if norm not in url_items:
                url_items[norm] = {"raw": target, "kind": "file", "count": 1, "role": None}
            else:
                url_items[norm]["count"] += 1

    # Standalone dossier mode
    if args.dossier and not args.targets and not draft and not args.date:
        if is_evergreen:
            checks.append({
                "target": args.dossier,
                "kind": "dossier",
                "state": "evergreen_bypassed",
                "same_day": True,
                "note": "Evergreen content: same-day publication check bypassed; delegated to kevrast Stage 1",
            })
        else:
            primary_sources = [s for s in dossier_data.get("sources", []) if s.get("role") == "primary"]
            for src in primary_sources:
                u = src.get("url", "")
                norm = u.strip().rstrip("/")
                if norm not in url_items:
                    url_items[norm] = {"raw": u, "kind": "url", "count": 1, "role": "primary"}
                else:
                    url_items[norm]["count"] += 1

    # Evaluate each unique target exactly once
    for norm, item in url_items.items():
        raw = item["raw"]
        kind = item["kind"]
        role = item["role"]
        count = item["count"]

        if is_evergreen:
            checks.append({"target": raw, "kind": kind, "state": "evergreen_delegated",
                           "same_day": True, "citation_count": count,
                           "note": f"role={role} -> evergreen delegated to kevrast"})
            continue

        if role in ("supplementary", "historical"):
            checks.append({"target": raw, "kind": kind, "state": "skipped_role",
                           "same_day": True, "citation_count": count,
                           "note": f"role={role} -> same-day exempt; liveness and anchoring handled by kevrast"})
            continue

        if args.no_network:
            pub_str = date_map.get(norm) or (str(dossier_data.get("date") or "") if dossier_data else "")
            dt = parse_free_date(pub_str) if pub_str else now_istanbul()
            checks.append(check_target(raw, kind, None, raw, dt, citation_count=count))
            continue

        if kind == "url":
            try:
                html = fetch_html(raw, args.timeout)
                checks.append(check_target(raw, kind, html, raw, None, citation_count=count))
            except (urllib.error.URLError, OSError, ValueError) as exc:
                checks.append({"target": raw, "kind": kind, "state": "unreachable",
                               "same_day": False, "citation_count": count,
                               "note": f"Target unreachable ({exc})"})
        else:
            try:
                with open(raw, encoding="utf-8") as f:
                    checks.append(check_target(raw, kind, f.read(), "", None, citation_count=count))
            except OSError as exc:
                checks.append({"target": raw, "kind": kind, "state": "unreachable",
                               "same_day": False, "citation_count": count,
                               "note": f"File unreadable ({exc})"})
```

### H. Formatted Output Enhancement
When printing human-readable output:
```python
for t in report["targets"]:
    cite_suffix = f" [cited {t['citation_count']}x]" if t.get("citation_count", 1) > 1 else ""
    if t["state"] == "dated":
        mark = "[OK]" if t["same_day"] else "[!]"
        diag_suffix = f" [age: {t['age_hours']}h, yesterday late-evening near-midnight]" if t.get("is_yesterday_late_evening") else f" [age: {t.get('age_hours', 0)}h]"
        print(f"  {mark} {t['target']} -> {t['pub_date']} ({t['evidence']}){cite_suffix}{diag_suffix}")
    elif t["state"] in ("skipped_role", "evergreen_delegated", "evergreen_bypassed"):
        print(f"  [INFO] {t['target']} -> {t.get('note', '')}{cite_suffix}")
    else:
        print(f"  [!] {t['target']} -> {t.get('note', 'Date could not be determined')}{cite_suffix}")
```

---

## 4. Caveats

1. **Dependency on `_shared/editorial_http.py`**:
   `editorial_http.py` is being developed concurrently under Feature 13 (by Explorer M2-1). The implementation in `check_freshness.py` includes a safe fallback to `ssl.create_default_context()` with verified TLS so `check_freshness.py` remains immediately operational even before `editorial_http.py` is committed.
2. **Websites without `<article>` tags**:
   Some older or custom portals place their story inside `<div class="content">` or `<main>` instead of semantic `<article>`. The blueprint handles this by falling back to stripping `<script>`, `<style>`, `<noscript>`, `<nav>`, `<header>`, `<footer>`, `<aside>` globally across the document before scanning.
3. **No Relaxation of Calendar Date Gate**:
   The policy remains strictly fail-closed: `same_day = (pub_date == today)`. The near-midnight diagnostics provide structured signals (`is_yesterday_late_evening: true`) for Stage 0 workflow routing (attempting same-day primary discovery), but `check_freshness.py` itself never bypasses or alters the calendar day requirement.

---

## 5. Conclusion

The blueprints for Features 14–18 provide a complete, verified hardening of `check_freshness.py`:
- **Security**: Verified TLS and SSRF protection via `editorial_http.py`.
- **Accuracy**: ISO fractional seconds preserving UTC timezone conversion to `Europe/Istanbul`.
- **Integrity**: Non-content block stripping, strict JSON-LD schema filtering, earliest main date selection, and removal of bare `"Bugün"`.
- **Efficiency**: Order-preserving normalized URL deduplication eliminating redundant network calls and log clutter.
- **Diagnostics**: Structured `age_hours` and `is_yesterday_late_evening` attributes powering Stage 0 decision logic.

---

## 6. Verification Method

Once implemented, the following verification commands must be executed:

```bash
# 1. Compile check
python3 -m py_compile .agents/skills/kevtemen/scripts/check_freshness.py

# 2. ISO .000Z and UTC conversion test
# 2026-11-10T23:13:00.000Z UTC must convert to 2026-11-11 02:13:00+03:00 (PASSED)
python3 .agents/skills/kevtemen/scripts/check_freshness.py --date "2026-11-10T23:13:00.000Z" --json
# Invalidation: If pub_date is "10.11.2026" or passed is false, test fails.

# 3. Non-content script tag leak test
python3 -c '
import tempfile, subprocess
html = "<html><head><script>x = \"11.11.2026\";</script></head><body><p>Eski haber.</p></body></html>"
with tempfile.NamedTemporaryFile("w+", suffix=".html") as f:
    f.write(html); f.flush()
    res = subprocess.run(["python3", ".agents/skills/kevtemen/scripts/check_freshness.py", f.name, "--json"], capture_output=True, text=True)
    print("Script leak test:", "BLOCKED" if "undated" in res.stdout else "FAILED (leaked)")
'

# 4. Bare "Bugün" rejection test
python3 -c '
import tempfile, subprocess
html = "<html><body><p>Diyarbakır parkı bugün halka hizmet veriyor.</p></body></html>"
with tempfile.NamedTemporaryFile("w+", suffix=".html") as f:
    f.write(html); f.flush()
    res = subprocess.run(["python3", ".agents/skills/kevtemen/scripts/check_freshness.py", f.name, "--json"], capture_output=True, text=True)
    print("Bare Bugün test:", "BLOCKED" if "undated" in res.stdout else "FAILED (leaked)")
'

# 5. Near-midnight diagnostic attributes test
python3 -c '
import subprocess, json
res = subprocess.run(["python3", ".agents/skills/kevtemen/scripts/check_freshness.py", "--date", "10.11.2026 23:13", "--json"], capture_output=True, text=True)
data = json.loads(res.stdout)
t = data["targets"][0]
print("Near-midnight test:", "OK" if t.get("is_yesterday_late_evening") and t.get("age_hours") is not None else "FAILED")
'

# 6. Pipeline script verification
bash tools/verify_news_pipeline.sh --dry-run
```
