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
import json
import re
import ssl
import sys
import urllib.error
import urllib.request
from datetime import datetime, timedelta, timezone
from typing import Any, Dict, List, Optional
from zoneinfo import ZoneInfo

try:
    ISTANBUL = ZoneInfo("Europe/Istanbul")
except Exception:
    ISTANBUL = timezone(timedelta(hours=3))

USER_AGENT = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"

_SSL_CTX = ssl.create_default_context()
_SSL_CTX.check_hostname = False
_SSL_CTX.verify_mode = ssl.CERT_NONE

MONTHS_TR = {
    "ocak": 1, "şubat": 2, "subat": 2, "mart": 3, "nisan": 4,
    "mayıs": 5, "mayis": 5, "haziran": 6, "temmuz": 7,
    "ağustos": 8, "agustos": 8, "eylül": 9, "eylul": 9,
    "ekim": 10, "kasım": 11, "kasim": 11, "aralık": 12, "aralik": 12,
}

RE_META_PUB = re.compile(
    r'article:published_time[^>]*content="([^"]+)"'
    r'|content="([^"]+)"[^>]*property="article:published_time"', re.I)
RE_LD_PUB = re.compile(r'"datePublished"\s*:\s*"([^"]+)"', re.I)
RE_TIME_TAG = re.compile(r'<time[^>]+datetime="([^"]+)"', re.I)
RE_ISO = re.compile(
    r"(\d{4})-(\d{2})-(\d{2})"
    r"(?:[T ](\d{2}):(\d{2})(?::(\d{2}))?(Z|[+-]\d{2}:?\d{2})?)?")
RE_DOT = re.compile(r"\b(\d{1,2})\.(\d{1,2})\.(\d{4})(?:[ ,]+(\d{1,2}):(\d{2}))?")
RE_SLASH = re.compile(r"\b(\d{1,2})/(\d{1,2})/(\d{4})\b")
RE_URLPATH = re.compile(r"/(\d{4})/(\d{2})/(\d{2})/")
RE_TR_LONG = re.compile(
    r"\b(\d{1,2})\s+(" + "|".join(MONTHS_TR) + r")\s+(\d{4})", re.I)
RE_REL_HOURS = re.compile(r"(\d{1,2})\s*saat\s*önce", re.I)
RE_REL_MIN = re.compile(r"(\d{1,3})\s*(?:dakika|dk)\s*önce", re.I)
RE_REL_DAYS = re.compile(r"(\d{1,3})\s*gün\s*önce", re.I)
RE_YESTERDAY = re.compile(r"\bdün\b", re.I)
RE_TODAY = re.compile(r"\bbugün\b", re.I)

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
        off = sign * timedelta(hours=int(body[:2]), minutes=int(body[2:4]))
    except ValueError:
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
    m = RE_ISO.search(text)
    if m:
        dt = _from_iso_match(m)
        if dt:
            return dt
    m = RE_DOT.search(text)
    if m:
        try:
            return _mk_naive_istanbul(datetime(int(m.group(3)), int(m.group(2)),
                                               int(m.group(1)), int(m.group(4) or 0),
                                               int(m.group(5) or 0)))
        except ValueError:
            pass
    m = RE_SLASH.search(text)
    if m:
        try:
            return _mk_naive_istanbul(datetime(int(m.group(3)), int(m.group(2)),
                                               int(m.group(1))))
        except ValueError:
            pass
    m = RE_TR_LONG.search(text)
    if m:
        try:
            return _mk_naive_istanbul(datetime(int(m.group(3)),
                                               MONTHS_TR[m.group(2).lower()],
                                               int(m.group(1))))
        except (ValueError, KeyError):
            pass
    return None


def extract_pub_date(html: str, url: str) -> Optional[Dict[str, Any]]:
    """Return the most authoritative publication datetime found: {'dt', 'evidence'}.
    Priority: structured (meta/JSON-LD/time tag) > visible explicit dates >
    URL path date > relative expressions > bare ISO anywhere."""
    candidates: List[tuple] = []  # (priority, dt, evidence)

    structured: List[str] = []
    for rx in (RE_META_PUB, RE_LD_PUB, RE_TIME_TAG):
        for m in rx.finditer(html):
            raw = m.group(1) or m.group(2)
            if raw:
                structured.append(raw.strip())
    for raw in structured:
        m = RE_ISO.search(raw)
        if m:
            dt = _from_iso_match(m)
            if dt:
                candidates.append((1, dt, f"structured: {raw[:60]}"))

    text = TAG_RE.sub(" ", html)
    for m in RE_DOT.finditer(text):
        try:
            dt = _mk_naive_istanbul(datetime(int(m.group(3)), int(m.group(2)),
                                             int(m.group(1)), int(m.group(4) or 0),
                                             int(m.group(5) or 0)))
            candidates.append((2, dt, f"visible: {m.group(0)}"))
        except ValueError:
            continue
    for m in RE_TR_LONG.finditer(text):
        try:
            dt = _mk_naive_istanbul(datetime(int(m.group(3)),
                                             MONTHS_TR[m.group(2).lower()],
                                             int(m.group(1))))
            candidates.append((2, dt, f"visible: {m.group(0)}"))
        except (ValueError, KeyError):
            continue

    if url:
        m = RE_URLPATH.search(url)
        if m:
            try:
                dt = _mk_naive_istanbul(datetime(int(m.group(1)), int(m.group(2)),
                                                 int(m.group(3))))
                candidates.append((3, dt, "url path"))
            except ValueError:
                pass

    now = now_istanbul()
    m = RE_REL_MIN.search(text)
    if m:
        candidates.append((4, now - timedelta(minutes=int(m.group(1))),
                           f"relative: {m.group(0)}"))
    m = RE_REL_HOURS.search(text)
    if m:
        candidates.append((4, now - timedelta(hours=int(m.group(1))),
                           f"relative: {m.group(0)}"))
    m = RE_REL_DAYS.search(text)
    if m:
        candidates.append((4, now - timedelta(days=int(m.group(1))),
                           f"relative: {m.group(0)}"))
    if RE_TODAY.search(text):
        candidates.append((4, now, "relative: today"))
    if RE_YESTERDAY.search(text):
        candidates.append((4, now - timedelta(days=1), "relative: yesterday"))

    if not candidates:
        for m in RE_ISO.finditer(html):
            dt = _from_iso_match(m)
            if dt:
                candidates.append((5, dt, f"bare ISO: {m.group(0)}"))

    if not candidates:
        return None
    best = min(candidates, key=lambda c: (c[0], -c[1].timestamp()))
    return {"dt": best[1], "evidence": best[2]}


def fetch_html(url: str, timeout: int) -> str:
    req = urllib.request.Request(url, headers={"User-Agent": USER_AGENT})
    with urllib.request.urlopen(req, timeout=timeout, context=_SSL_CTX) as resp:
        return resp.read().decode("utf-8", errors="replace")


def check_target(label: str, kind: str, html: Optional[str], url: str,
                 explicit_dt: Optional[datetime]) -> Dict[str, Any]:
    result: Dict[str, Any] = {"target": label, "kind": kind}
    today = now_istanbul().date()

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

    pub_date = pub["dt"].date()
    result.update(state="dated", pub_date=pub_date.strftime("%d.%m.%Y"),
                  evidence=pub["evidence"], same_day=(pub_date == today))
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
            # Check for breaking-news temporal markers in draft
            clean_draft = TAG_RE.sub(" ", draft)
            m = BREAKING_TEMPORAL_MARKERS.search(clean_draft)
            if m:
                anti_laundering_violations.append(
                    f"evergreen_temporal_peg_violation: breaking-news temporal marker '{m.group(0)}' found in evergreen draft"
                )

            # Check draft links containment against dossier
            draft_hrefs = [h.rstrip("/") for h in HREF_RE.findall(draft)]
            known_dossier_urls = set(role_map.keys())
            for h in draft_hrefs:
                if h not in known_dossier_urls:
                    anti_laundering_violations.append(
                        f"unlisted_link_in_evergreen_draft: draft contains link '{h}' not declared in dossier sources"
                    )

        # Also check dossier claims for temporal markers
        if dossier_data:
            for s in dossier_data.get("sources", []):
                for c in s.get("claims", []):
                    m = BREAKING_TEMPORAL_MARKERS.search(c)
                    if m:
                        anti_laundering_violations.append(
                            f"evergreen_temporal_peg_violation: breaking-news marker '{m.group(0)}' found in source claim"
                        )

    # Standalone dossier mode: when no targets, file, text, or date provided
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
            if not primary_sources:
                print("ERROR: Research dossier contains zero primary sources to verify.", file=sys.stderr)
                sys.exit(2)
            for src in primary_sources:
                u = src.get("url", "")
                if args.no_network:
                    pub_str = date_map.get(u.rstrip("/")) or str(dossier_data.get("date") or "")
                    dt = parse_free_date(pub_str) if pub_str else now_istanbul()
                    checks.append(check_target(u, "url", None, u, dt))
                else:
                    try:
                        checks.append(check_target(u, "url", fetch_html(u, args.timeout), u, None))
                    except (urllib.error.URLError, OSError, ValueError) as exc:
                        checks.append({"target": u, "kind": "url", "state": "unreachable",
                                       "same_day": False, "note": f"Target unreachable ({exc})"})

    if args.date:
        dt = parse_free_date(args.date)
        if dt is None:
            print(f"ERROR: Could not parse --date: {args.date!r}", file=sys.stderr)
            sys.exit(2)
        checks.append(check_target(args.date, "date", None, "", dt))

    draft_hrefs = HREF_RE.findall(draft)
    if draft_hrefs and not args.dossier:
        print("Warning: --dossier not provided; all draft links audited under same-day freshness "
              "(pass research dossier via --dossier for role-aware mode).", file=sys.stderr)

    for href in draft_hrefs:
        role = role_map.get(href.rstrip("/"))
        if is_evergreen:
            checks.append({"target": href, "kind": "url", "state": "evergreen_delegated",
                           "same_day": True,
                           "note": f"role={role} -> evergreen delegated to kevrast"})
            continue
        if role in ("supplementary", "historical"):
            checks.append({"target": href, "kind": "url", "state": "skipped_role",
                           "same_day": True,
                           "note": f"role={role} -> same-day exempt; liveness and anchoring handled by kevrast"})
            continue
        if args.no_network:
            pub_str = date_map.get(href.rstrip("/")) or (str(dossier_data.get("date") or "") if dossier_data else "")
            dt = parse_free_date(pub_str) if pub_str else now_istanbul()
            checks.append(check_target(href, "url", None, href, dt))
            continue
        try:
            checks.append(check_target(href, "url", fetch_html(href, args.timeout), href, None))
        except (urllib.error.URLError, OSError, ValueError) as exc:
            checks.append({"target": href, "kind": "url", "state": "unreachable",
                           "same_day": False, "note": f"Target unreachable ({exc})"})

    for target in args.targets:
        role = role_map.get(target.rstrip("/"))
        if is_evergreen:
            checks.append({"target": target, "kind": "url", "state": "evergreen_delegated",
                           "same_day": True,
                           "note": f"role={role} -> evergreen delegated to kevrast"})
            continue
        if role in ("supplementary", "historical"):
            checks.append({"target": target, "kind": "url", "state": "skipped_role",
                           "same_day": True,
                           "note": f"role={role} -> same-day exempt"})
            continue
        if args.no_network:
            pub_str = date_map.get(target.rstrip("/")) or ""
            dt = parse_free_date(pub_str) if pub_str else now_istanbul()
            checks.append(check_target(target, "url", None, target, dt))
            continue
        if target.startswith("http://") or target.startswith("https://"):
            try:
                checks.append(check_target(target, "url", fetch_html(target, args.timeout), target, None))
            except (urllib.error.URLError, OSError, ValueError) as exc:
                checks.append({"target": target, "kind": "url", "state": "unreachable",
                               "same_day": False, "note": f"Target unreachable ({exc})"})
        else:
            try:
                with open(target, encoding="utf-8") as f:
                    checks.append(check_target(target, "file", f.read(), "", None))
            except OSError as exc:
                checks.append({"target": target, "kind": "file", "state": "unreachable",
                               "same_day": False, "note": f"File unreadable ({exc})"})

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
            if t["state"] == "dated":
                mark = "[OK]" if t["same_day"] else "[!]"
                print(f"  {mark} {t['target']} -> {t['pub_date']} ({t['evidence']})")
            elif t["state"] in ("skipped_role", "evergreen_delegated", "evergreen_bypassed"):
                print(f"  [INFO] {t['target']} -> {t.get('note', '')}")
            else:
                print(f"  [!] {t['target']} -> {t.get('note', 'Date could not be determined')}")

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
