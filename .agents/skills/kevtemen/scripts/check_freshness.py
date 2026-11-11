#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Kevtemen Same-Day Freshness Sentinel

Absolute gate:
- For content classified as 'news' (or default): only content whose source publication date is
  TODAY (Europe/Istanbul) may be prepared, edited, or staged on the site. Older, undatable,
  unreachable, or future-dated sources are BLOCKED. Fail-closed by design.
- For content classified as 'evergreen': same-day publication check is bypassed with status
  'PASSED (EVERGREEN)' while delegating HTTP liveness verification to kevrast, subject to
  strict anti-laundering checks (rationale >= 20 chars, all draft links in dossier, zero
  breaking-news temporal markers).
"""

import argparse
from collections import OrderedDict
import json
from pathlib import Path
import re
import ssl
import sys
import urllib.error
import urllib.request
from datetime import datetime, timedelta, timezone
from typing import Any, Dict, List, Optional, Tuple
from zoneinfo import ZoneInfo

# Resolve shared directory for editorial_http
_SHARED_DIR = Path(__file__).resolve().parents[2] / "_shared"
if str(_SHARED_DIR) not in sys.path:
    sys.path.insert(0, str(_SHARED_DIR))

try:
    import editorial_http
except ImportError:
    editorial_http = None

try:
    ISTANBUL = ZoneInfo("Europe/Istanbul")
except Exception:
    ISTANBUL = timezone(timedelta(hours=3))

USER_AGENT = (
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) "
    "AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
)

MONTHS_TR = {
    "ocak": 1, "şubat": 2, "subat": 2, "mart": 3, "nisan": 4,
    "mayıs": 5, "mayis": 5, "haziran": 6, "temmuz": 7,
    "ağustos": 8, "agustos": 8, "eylül": 9, "eylul": 9,
    "ekim": 10, "kasım": 11, "kasim": 11, "aralık": 12, "aralik": 12,
}

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

RE_TIME_TAG = re.compile(r'<time[^>]+datetime="([^"]+)"', re.I)

# Feature 15: ISO regex supporting fractional seconds without dropping timezone
RE_ISO = re.compile(
    r"(\d{4})-(\d{2})-(\d{2})"
    r"(?:[T ](\d{2}):(\d{2})(?::(\d{2}))?(?:[.,]\d{1,9})?(Z|[+-]\d{2}:?\d{2})?)?"
)

RE_DOT = re.compile(r"\b(\d{1,2})\.(\d{1,2})\.(\d{4})(?:[ ,]+(\d{1,2}):(\d{2}))?")
RE_SLASH = re.compile(r"\b(\d{1,2})/(\d{1,2})/(\d{4})\b")
RE_URLPATH = re.compile(r"/(\d{4})/(\d{2})/(\d{2})/")
RE_TR_LONG = re.compile(
    r"\b(\d{1,2})\s+(" + "|".join(MONTHS_TR) + r")\s+(\d{4})", re.I)

# Feature 17: Anchored relative expressions & visible bylines (bare Bugün removed)
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

RE_REL_HOURS = re.compile(r"(\d{1,2})\s*saat\s*önce", re.I)
RE_REL_MIN = re.compile(r"(\d{1,3})\s*(?:dakika|dk)\s*önce", re.I)
RE_REL_DAYS = re.compile(r"(\d{1,3})\s*gün\s*önce", re.I)

TAG_RE = re.compile(r"<[^>]+>")
HREF_RE = re.compile(r"href=[\'\"](https?://[^\'\"]+)[\'\"]", re.I)

BREAKING_TEMPORAL_MARKERS = re.compile(
    r"\b(bugün|dün|bu sabah|az önce|son dakika|today|yesterday|breaking news)\b",
    re.I
)


def now_istanbul() -> datetime:
    return datetime.now(ISTANBUL)


def _mk_naive_istanbul(dt: datetime) -> datetime:
    return dt.replace(tzinfo=ISTANBUL) if dt.tzinfo is None else dt.astimezone(ISTANBUL)


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


def _from_iso_match(m: re.Match) -> Optional[datetime]:
    y, mo, d = int(m.group(1)), int(m.group(2)), int(m.group(3))
    h = int(m.group(4) or 0)
    mi = int(m.group(5) or 0)
    s = int(m.group(6) or 0)
    return _apply_tz(y, mo, d, h, mi, s, m.group(7))


def parse_free_date(text: str) -> Optional[datetime]:
    """Parse an explicit date string: ISO, DD.MM.YYYY[ HH:MM], DD/MM/YYYY, DD Ay YYYY."""
    s = text.strip()
    try:
        dt = datetime.fromisoformat(s.replace(",", "."))
        return dt.replace(tzinfo=ISTANBUL) if dt.tzinfo is None else dt.astimezone(ISTANBUL)
    except Exception:
        pass
    m = RE_ISO.search(s)
    if m:
        dt = _from_iso_match(m)
        if dt:
            return dt
    m = RE_DOT.search(s)
    if m:
        try:
            return _mk_naive_istanbul(datetime(int(m.group(3)), int(m.group(2)),
                                               int(m.group(1)), int(m.group(4) or 0),
                                               int(m.group(5) or 0)))
        except ValueError:
            pass
    m = RE_SLASH.search(s)
    if m:
        try:
            return _mk_naive_istanbul(datetime(int(m.group(3)), int(m.group(2)),
                                               int(m.group(1))))
        except ValueError:
            pass
    m = RE_TR_LONG.search(s)
    if m:
        try:
            return _mk_naive_istanbul(datetime(int(m.group(3)),
                                               MONTHS_TR[m.group(2).lower()],
                                               int(m.group(1))))
        except (ValueError, KeyError):
            pass
    return None


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
        scope_html = re.sub(r"<(aside|nav|script|style|noscript)[^>]*>.*?</\1>", " ", scope_html, flags=re.I | re.DOTALL)
    else:
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


def fetch_html(url: str, timeout: int) -> str:
    """Fetch HTML using verified shared transport layer or secure fallback."""
    if editorial_http is not None:
        try:
            status, html, headers = editorial_http.fetch_html(url, timeout=timeout)
        except Exception as exc:
            raise urllib.error.URLError(f"Transport exception: {exc}")

        if status >= 400:
            err_msg = headers.get("error") or headers.get("x-error") or f"HTTP error {status}"
            raise urllib.error.HTTPError(url, status, err_msg, headers, None)
        if status < 200:
            err_msg = headers.get("error") or headers.get("x-error") or f"Transport error (HTTP {status})"
            raise urllib.error.URLError(err_msg)
        return html

    ctx = ssl.create_default_context()
    req = urllib.request.Request(url, headers={
        "User-Agent": USER_AGENT,
        "Accept": "text/html,application/xhtml+xml,*/*;q=0.8",
        "Accept-Language": "tr,en;q=0.8",
    })
    with urllib.request.urlopen(req, timeout=timeout, context=ctx) as resp:
        return resp.read().decode("utf-8", errors="replace")


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


def build_report(targets: List[Dict[str, Any]], is_evergreen: bool = False,
                 delegated_urls: Optional[List[str]] = None,
                 anti_laundering_violations: Optional[List[str]] = None) -> Dict[str, Any]:
    if is_evergreen:
        passed = (len(anti_laundering_violations or []) == 0)
        status = "PASSED (EVERGREEN)" if passed else "BLOCKED (evergreen_anti_laundering_violation)"
        return {
            "passed": passed,
            "status": status,
            "content_type": "evergreen",
            "today": now_istanbul().strftime("%d.%m.%Y"),
            "delegated_liveness_urls": delegated_urls or [],
            "anti_laundering_violations": anti_laundering_violations or [],
            "targets": targets,
        }

    same_day = all(t["same_day"] for t in targets) and any(t["same_day"] for t in targets)
    return {
        "passed": same_day,
        "status": "PASSED (Same-Day)" if same_day else "BLOCKED",
        "content_type": "news",
        "today": now_istanbul().strftime("%d.%m.%Y"),
        "targets": targets,
    }


def main() -> None:
    parser = argparse.ArgumentParser(
        description="Kevtemen Same-Day Freshness Sentinel (fail-closed, no override)")
    parser.add_argument("targets", nargs="*",
                        help="Source URL(s) or local HTML file path(s)")
    parser.add_argument("--date", help="Explicit publication date to verify "
                        "(YYYY-MM-DD[ HH:MM] | DD.MM.YYYY | DD Ay YYYY)")
    parser.add_argument("--text", help="Draft HTML/text: extracts and checks every cited href")
    parser.add_argument("--file", help="Read draft HTML/text from file path")
    parser.add_argument("--timeout", type=int, default=15)
    parser.add_argument("--json", action="store_true", help="Output JSON")
    parser.add_argument("--no-network", action="store_true",
                        help="Skip live network requests; verify publication dates via dossier pub_date or explicit --date")
    parser.add_argument("--dossier",
                        help="Kevkolin research dossier JSON: applies role-tagging and content-type rules")
    args = parser.parse_args()

    checks: List[Dict[str, Any]] = []
    dossier_data: Optional[Dict[str, Any]] = None
    role_map: Dict[str, str] = {}
    date_map: Dict[str, str] = {}
    is_evergreen = False
    anti_laundering_violations: List[str] = []
    delegated_liveness_urls: List[str] = []

    if args.dossier:
        try:
            with open(args.dossier, encoding="utf-8") as df:
                dossier_data = json.load(df)
            for src in dossier_data.get("sources", []):
                u = str(src.get("url", "")).rstrip("/")
                r = src.get("role", "-")
                role_map[u] = r
                if src.get("pub_date"):
                    date_map[u] = str(src.get("pub_date"))
                if r == "primary":
                    delegated_liveness_urls.append(src.get("url", ""))
            if dossier_data.get("type") == "evergreen":
                is_evergreen = True
        except (OSError, ValueError) as exc:
            print(f"ERROR: Could not read dossier ({args.dossier}): {exc}", file=sys.stderr)
            sys.exit(2)

    # Anti-laundering check 1: evergreen rationale
    if is_evergreen and dossier_data:
        rationale = str(dossier_data.get("evergreen_rationale") or "").strip()
        if len(rationale) < 20:
            anti_laundering_violations.append(
                "evergreen_rationale missing or too short (<20 characters) in research dossier"
            )

    draft = ""
    if args.file:
        try:
            with open(args.file, encoding="utf-8") as f:
                draft = f.read()
        except OSError as exc:
            print(f"ERROR: Could not read file ({args.file}): {exc}", file=sys.stderr)
            sys.exit(2)
    elif args.text:
        draft = args.text

    # Anti-laundering check 2 & 3: Draft link containment & temporal markers
    if is_evergreen:
        if draft:
            clean_draft = TAG_RE.sub(" ", draft)
            m = BREAKING_TEMPORAL_MARKERS.search(clean_draft)
            if m:
                anti_laundering_violations.append(
                    f"evergreen_temporal_peg_violation: breaking-news temporal marker '{m.group(0)}' found in evergreen draft"
                )

            draft_hrefs = [h.rstrip("/") for h in HREF_RE.findall(draft)]
            known_dossier_urls = set(role_map.keys())
            for h in draft_hrefs:
                if h not in known_dossier_urls:
                    anti_laundering_violations.append(
                        f"unlisted_link_in_evergreen_draft: draft contains link '{h}' not declared in dossier sources"
                    )

        if dossier_data:
            for s in dossier_data.get("sources", []):
                for c in s.get("claims", []):
                    m = BREAKING_TEMPORAL_MARKERS.search(c)
                    if m:
                        anti_laundering_violations.append(
                            f"evergreen_temporal_peg_violation: breaking-news marker '{m.group(0)}' found in source claim"
                        )

    # Handle explicit --date check immediately if given
    if args.date:
        dt = parse_free_date(args.date)
        if dt is None:
            print(f"ERROR: Could not parse --date: {args.date!r}", file=sys.stderr)
            sys.exit(2)
        checks.append(check_target(args.date, "date", None, "", dt))

    # Collect targets preserving encounter order (Normalized URL deduplication)
    url_items: Dict[str, Dict[str, Any]] = OrderedDict()

    # Draft hrefs
    draft_hrefs = HREF_RE.findall(draft)
    if draft_hrefs and not args.dossier:
        print("Warning: --dossier not provided; all draft links audited under same-day freshness "
              "(pass research dossier via --dossier for role-aware mode).", file=sys.stderr)

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

    # Standalone dossier mode: when no targets, file, text, or date provided
    if args.dossier and not args.targets and not draft and not args.date:
        if is_evergreen:
            checks.append({
                "target": args.dossier,
                "kind": "dossier",
                "state": "evergreen_bypassed",
                "same_day": True,
                "citation_count": 1,
                "note": "Evergreen content: same-day publication check bypassed; delegated to kevrast Stage 1",
            })
        else:
            primary_sources = [s for s in dossier_data.get("sources", []) if s.get("role") == "primary"]
            if not primary_sources:
                print("ERROR: Research dossier contains zero primary sources to verify.", file=sys.stderr)
                sys.exit(2)
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
            except (urllib.error.URLError, OSError, ValueError, Exception) as exc:
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

    if not checks and not is_evergreen:
        parser.print_help()
        sys.exit(2)

    report = build_report(
        checks,
        is_evergreen=is_evergreen,
        delegated_urls=delegated_liveness_urls,
        anti_laundering_violations=anti_laundering_violations
    )

    if args.json:
        print(json.dumps(report, ensure_ascii=False, indent=2))
    else:
        print("=" * 60)
        print("KEVTEMEN: Same-Day Freshness Sentinel")
        print("=" * 60)
        print(f"Status: {report['status']} | Today: {report['today']}\n")
        print("[Source Date Audit]")
        for t in report["targets"]:
            cite_suffix = f" [cited {t.get('citation_count', 1)}x]" if t.get("citation_count", 1) > 1 else ""
            if t["state"] == "dated":
                mark = "[OK]" if t["same_day"] else "[!]"
                diag_suffix = (
                    f" [age: {t['age_hours']}h, yesterday late-evening near-midnight]"
                    if t.get("is_yesterday_late_evening")
                    else f" [age: {t.get('age_hours', 0)}h]"
                )
                print(f"  {mark} {t['target']} -> {t['pub_date']} ({t['evidence']}){cite_suffix}{diag_suffix}")
            elif t["state"] in ("skipped_role", "evergreen_delegated", "evergreen_bypassed"):
                print(f"  [INFO] {t['target']} -> {t.get('note', '')}{cite_suffix}")
            else:
                print(f"  [!] {t['target']} -> {t.get('note', 'Date could not be determined')}{cite_suffix}")

        if anti_laundering_violations:
            print("\n[Anti-Laundering Violations]")
            for v in anti_laundering_violations:
                print(f"  [!] {v}")

        if not report["passed"]:
            print()
            if is_evergreen:
                print("BLOCKED: Evergreen anti-laundering checks failed. Review violations above.")
            else:
                for t in report["targets"]:
                    if t["state"] == "dated" and not t["same_day"]:
                        print(f"BLOCKED: Content is stale - publication date {t['pub_date']}. "
                              "Stale news cannot be published on the site; operation halted.")
                    elif t["state"] == "undated":
                        print("BLOCKED: Publication date could not be determined; content unproven "
                              "to be published today cannot be accepted - operation halted.")
                    elif t["state"] == "unreachable":
                        print("BLOCKED: Source could not be verified (unreachable); content unproven "
                              "to be published today cannot be accepted - operation halted.")
        print("=" * 60)

    sys.exit(0 if report["passed"] else 1)


if __name__ == "__main__":
    main()
