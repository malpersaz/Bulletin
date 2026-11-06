#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Kevtemen Same-Day Freshness Sentinel
Absolute gate: only content whose source publication date is TODAY (Europe/Istanbul)
may be prepared, edited, or staged on the site. Older, undatable, unreachable, or
future-dated sources are BLOCKED. Fail-closed by design. No override exists.
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
HREF_RE = re.compile(r"href=[\'\"](https?://[^\'\"]+)[\'\"]")


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
        candidates.append((4, now, "relative: bugün"))
    if RE_YESTERDAY.search(text):
        candidates.append((4, now - timedelta(days=1), "relative: dün"))

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
                      note="sayfaya erişilemedi / içerik okunamadı")
        return result
    else:
        pub = extract_pub_date(html, url)

    if pub is None:
        result.update(state="undated", same_day=False,
                      note="yayın tarihi belirlenemedi")
        return result

    pub_date = pub["dt"].date()
    result.update(state="dated", pub_date=pub_date.strftime("%d.%m.%Y"),
                  evidence=pub["evidence"], same_day=(pub_date == today))
    return result


def build_report(targets: List[Dict[str, Any]]) -> Dict[str, Any]:
    same_day = all(t["same_day"] for t in targets) and any(t["same_day"] for t in targets)
    return {"passed": same_day, "today": now_istanbul().strftime("%d.%m.%Y"),
            "targets": targets}


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
    parser.add_argument("--dossier",
                        help="Kevkolin döşme JSON'u (rol etiketli kaynak listesi): "
                             "yalnız 'primary' rolleri same-day denetlenir; "
                             "supplementary/historical linkler tarih kapısından muaftır")
    args = parser.parse_args()

    checks: List[Dict[str, Any]] = []

    if args.date:
        dt = parse_free_date(args.date)
        if dt is None:
            print(f"HATA: --date ayrıştırılamadı: {args.date!r}", file=sys.stderr)
            sys.exit(2)
        checks.append(check_target(args.date, "date", None, "", dt))

    draft = ""
    if args.file:
        with open(args.file, encoding="utf-8") as f:
            draft = f.read()
    elif args.text:
        draft = args.text
    role_map = {}
    if args.dossier:
        try:
            with open(args.dossier, encoding="utf-8") as df:
                dossier_data = json.load(df)
            for src in dossier_data.get("sources", []):
                role_map[str(src.get("url", "")).rstrip("/")] = src.get("role", "-")
        except (OSError, ValueError) as exc:
            print(f"HATA: döşme okunamadı ({args.dossier}): {exc}", file=sys.stderr)
            sys.exit(2)
    draft_hrefs = HREF_RE.findall(draft)
    if draft_hrefs and not args.dossier:
        print("Uyarı: --dossier verilmedi; tüm href'ler same-day denetlenir "
              "(rol-farkındal mod için kevkolin döşmesini --dossier ile geçirin).")
    for href in draft_hrefs:
        role = role_map.get(href.rstrip("/"))
        if role in ("supplementary", "historical"):
            checks.append({"target": href, "kind": "url", "state": "skipped_role",
                           "same_day": True,
                           "note": f"rol={role} → same-day muaf; canlılık ve örtüşme kevrast'ın kapsamı"})
            continue
        try:
            checks.append(check_target(href, "url", fetch_html(href, args.timeout), href, None))
        except (urllib.error.URLError, OSError, ValueError) as exc:
            checks.append({"target": href, "kind": "url", "state": "unreachable",
                           "same_day": False, "note": f"sayfaya erişilemedi ({exc})"})

    for target in args.targets:
        if target.startswith("http://") or target.startswith("https://"):
            try:
                checks.append(check_target(target, "url", fetch_html(target, args.timeout), target, None))
            except (urllib.error.URLError, OSError, ValueError) as exc:
                checks.append({"target": target, "kind": "url", "state": "unreachable",
                               "same_day": False, "note": f"sayfaya erişilemedi ({exc})"})
        else:
            try:
                with open(target, encoding="utf-8") as f:
                    checks.append(check_target(target, "file", f.read(), "", None))
            except OSError as exc:
                checks.append({"target": target, "kind": "file", "state": "unreachable",
                               "same_day": False, "note": f"dosya okunamadı ({exc})"})

    if not checks:
        parser.print_help()
        sys.exit(2)

    report = build_report(checks)

    if args.json:
        print(json.dumps(report, ensure_ascii=False, indent=2))
    else:
        print("=" * 60)
        print("KEVTEMEN: Same-Day Freshness Sentinel")
        print("=" * 60)
        status = ("PASSED (Same-Day)" if report["passed"] else "BLOCKED")
        print(f"Status: {status} | Bugün: {report['today']}\n")
        print("[Source Date Audit]")
        for t in report["targets"]:
            if t["state"] == "dated":
                mark = "[OK]" if t["same_day"] else "[!]"
                print(f"  {mark} {t['target']} -> {t['pub_date']} ({t['evidence']})")
            elif t["state"] == "skipped_role":
                print(f"  [BİLGİ] {t['target']} -> {t.get('note', '')}")
            else:
                print(f"  [!] {t['target']} -> {t.get('note', 'tarih belirlenemedi')}")
        if not report["passed"]:
            print()
            for t in report["targets"]:
                if t["state"] == "dated" and not t["same_day"]:
                    print(f"DURDURULDU: Bu içerik eski — yayın tarihi {t['pub_date']}. "
                          "Siteye eski ve bayat haber girilmesi doğru değildir; işlem kesildi.")
                elif t["state"] == "undated":
                    print("DURDURULDU: Yayın tarihi belirlenemedi; aynı güne ait olduğu "
                          "kanıtlanmayan içerik siteye alınmaz — işlem kesildi.")
                elif t["state"] == "unreachable":
                    print("DURDURULDU: Kaynak doğrulanamadı (erişilemedi); aynı güne ait "
                          "olduğu kanıtlanmayan içerik siteye alınmaz — işlem kesildi.")
        print("=" * 60)

    sys.exit(0 if report["passed"] else 1)


if __name__ == "__main__":
    main()
