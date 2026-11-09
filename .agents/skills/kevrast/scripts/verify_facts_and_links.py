#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Kevrast Fact-Checking, Link Provenance & Claim Anchoring Auditor

Rules (see ../SKILL.md):
  R1 Liveness       - full GET over verified TLS; soft-404 / redirect-to-home / 4xx-5xx = FAILED
  R2 Precision      - citation hrefs must be deep content pages; domain roots and
                      section/rubric fronts ALWAYS FAIL (no root-domain fallback)
  R3 Anchoring      - distinctive figures of the citing paragraph must appear in the
                      fetched target page text (yüzde 71 <-> 71%, 1 trilyon <-> $1 trillion);
                      mismatch -> BLOCK + manual review note; unevaluable -> REVIEW
  R4 Completeness   - every attribution phrase must sit in a paragraph with a live link;
                      draft URL set must be a subset of the dossier URL set;
                      zero-outbound-link drafts FAIL
  R5 Dossier modes  - --dossier alone = Stage 1 (audit dossier URLs vs claims);
                      --dossier + --text/--file = Stage 2 (audit draft against dossier)

Exit codes: 0 = PASSED, 1 = BLOCKED/REVIEW, 2 = usage error.
"""

import argparse
import json
import re
import ssl
import sys
import urllib.error
import urllib.parse
import urllib.request
from typing import Any, Dict, List, Optional, Set

DEFAULT_USER_AGENT = (
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 "
    "(KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
)
MAX_BYTES = 2 * 1024 * 1024

TAG_RE = re.compile(r"<[^>]+>")
HREF_RE = re.compile(r"<a[^>]+href=[\'\"](https?://[^\'\"]+)[\'\"]", re.I)
RAW_URL_RE = re.compile(r"https?://[^\s<>\"']+")

# Attribution phrases (Turkish journalistic citation markers)
CITATION_PHRASE_RE = re.compile(
    r"haberine göre|aktarılan bilgilere göre|bilgilerine göre|verdiği bilgiye göre|"
    r"raporuna göre|anketine göre|araştırmasına göre|açıklamasına göre|ifadesine göre|"
    r"verilerine göre|bulgularına göre|söyledi|açıkladı",
    re.I,
)

UNIT_WORD_RE = re.compile(
    r"(?:trilyon|milyar|milyon|trillion|billion|million)", re.I)

PERCENT_RE_LIST = [
    re.compile(r"yüzde\s+([\d][\d.,]*)", re.I),
    re.compile(r"([\d][\d.,]*)\s*%"),
    re.compile(r"%\s*([\d][\d.,]*)"),
]
GENERIC_NUM_RE = re.compile(r"\d[\d.,]*")
TARGET_NUM_RE = re.compile(r"(?<![\d])([\d][\d.,]*)(?![\d])")

ENTITY_RE = re.compile(r"\b[A-ZÇĞİÖŞÜ][\wÇĞİÖŞÜçğıöşü\-]{2,}")
ENTITY_STOPWORDS = {
    "Ocak", "Şubat", "Mart", "Nisan", "Mayıs", "Haziran", "Temmuz", "Ağustos",
    "Eylül", "Ekim", "Kasım", "Aralık", "Pazartesi", "Salı", "Çarşamba",
    "Perşembe", "Cuma", "Cumartesi", "Pazar", "Ayrıca", "Ancak", "Bunun",
    "Buna", "Bunu", "Şimdi", "Böylece", "Keza", "Yani", "Öte", "Dolayısıyla",
}

SOFT_404_TITLE_RE = re.compile(
    r"<title[^>]*>[^<]*(?:404|not found|bulunamad)[^<]*</title>", re.I)

QUERY_ID_PARAMS = ("id", "p", "post", "art", "article", "story", "haber", "news")

# Flat single-segment article permalinks that end in a numeric ID (+ optional
# outlet letter marker) + extension (e.g. internethaber.com/...-2467480.htm,
# tigrishaber.com/...-173864h.htm) are deep content pages, not rubric fronts.
FLAT_ARTICLE_RE = re.compile(r"\d+[a-z]?\.html?$", re.I)


def strip_tags(text: str) -> str:
    return TAG_RE.sub(" ", text)


def normalize_number(raw: str) -> str:
    return re.sub(r"[.,\s]", "", raw)


class KevrastAuditor:
    def __init__(self, timeout: int = 20, user_agent: str = DEFAULT_USER_AGENT):
        self.timeout = timeout
        self.user_agent = user_agent
        # Verified TLS: a provenance gate must not accept MITM'd fake pages.
        self.ctx = ssl.create_default_context()

    # ------------------------------------------------------------------ R1
    def fetch_page(self, url: str) -> Dict[str, Any]:
        headers = {
            "User-Agent": self.user_agent,
            "Accept": "text/html,application/xhtml+xml,*/*;q=0.8",
            "Accept-Language": "tr,en;q=0.8",
        }
        req = urllib.request.Request(url, headers=headers, method="GET")
        result: Dict[str, Any] = {
            "url": url, "status_code": None, "final_url": None,
            "html": None, "live": False, "note": None,
        }
        try:
            with urllib.request.urlopen(req, timeout=self.timeout, context=self.ctx) as resp:
                result["status_code"] = resp.status
                result["final_url"] = resp.geturl()
                result["html"] = resp.read(MAX_BYTES).decode("utf-8", errors="replace")
        except urllib.error.HTTPError as e:
            result["status_code"] = e.code
            result["note"] = f"HTTP {e.code}"
            return result
        except Exception as e:  # URLError, ssl.SSLError, timeout, ...
            result["note"] = f"erişim/TLS hatası ({e.__class__.__name__}: {e}) — tarayıcıda manuel kontrol gerekli"
            return result

        if not (200 <= result["status_code"] < 300):
            return result

        html = result["html"] or ""
        # soft-404 heuristics
        if SOFT_404_TITLE_RE.search(html):
            result["note"] = "soft-404: başlık hata sayfası işaret ediyor"
            return result
        if len(html.strip()) < 500:
            result["note"] = "soft-404: içerik yok denecek kadar kısa (<500 bayt)"
            return result
        orig = urllib.parse.urlparse(url)
        final = urllib.parse.urlparse(result["final_url"] or url)
        orig_root = (orig.path or "").strip("/") != ""
        final_root = (final.path or "/").strip("/") == ""
        if orig_root and final_root:
            result["note"] = "soft-404: kök/ana sayfaya yönlendirildi"
            return result

        result["live"] = True
        return result

    # ------------------------------------------------------------------ R2
    def classify_precision(self, url: str) -> Dict[str, Any]:
        p = urllib.parse.urlparse(url)
        segments = [s for s in (p.path or "").split("/") if s]
        query_keys = [k.lower() for k in urllib.parse.parse_qs(p.query or "").keys()]
        has_id_query = any(k in QUERY_ID_PARAMS for k in query_keys)
        if not segments and not has_id_query:
            return {"precise": False, "kind": "domain_root",
                    "note": "alan kökü atıf olarak kullanılamaz (R2: derin içerik linki gerekli)"}
        flat_article = bool(segments) and bool(FLAT_ARTICLE_RE.search(segments[-1]))
        if len(segments) <= 1 and not has_id_query and not flat_article:
            return {"precise": False, "kind": "section_front",
                    "note": "rubrik/bölüm sayfası atıf olarak kullanılamaz (R2: haber sayfası gerekli)"}
        return {"precise": True, "kind": "deep_page", "note": None}

    # ------------------------------------------------------------------ R3
    def extract_distinctive_numbers(self, text: str) -> List[str]:
        clean = strip_tags(text)
        found: Set[str] = set()
        for rx in PERCENT_RE_LIST:
            for m in rx.finditer(clean):
                found.add(normalize_number(m.group(1)))
        for m in UNIT_WORD_RE.finditer(clean):
            start = max(0, m.start() - 12)
            prefix = clean[start:m.start()]
            nm = re.search(r"([\d][\d.,]*)\s*$", prefix)
            if nm:
                found.add(normalize_number(nm.group(1)))
        for m in GENERIC_NUM_RE.finditer(clean):
            digits = normalize_number(m.group(0))
            if len(digits) >= 2:
                found.add(digits)
        return sorted(found)

    def extract_entities(self, text: str) -> List[str]:
        clean = strip_tags(text)
        entities: List[str] = []
        for tok in ENTITY_RE.findall(clean):
            if tok in ENTITY_STOPWORDS or tok.lower() in ("http", "https", "www"):
                continue
            # drop Turkish suffixes attached with apostrophe (Nasdaq'daki -> Nasdaq)
            base = tok.split("'")[0].split("’")[0]
            if len(base) >= 3 and base not in entities:
                entities.append(base)
        return entities

    def anchoring(self, citing_text: str, target_html: Optional[str]) -> Dict[str, Any]:
        if target_html is None:
            return {"status": "unknown", "note": "hedef sayfa indirilemedi (R1)"}
        target = strip_tags(target_html)
        nums = self.extract_distinctive_numbers(citing_text)
        if nums:
            target_nums = [normalize_number(m.group(1)) for m in TARGET_NUM_RE.finditer(target)]
            missing = [n for n in nums if not any(n == t or n in t for t in target_nums)]
            if missing:
                return {
                    "status": "block",
                    "note": f"R3 örtüşme hatası: atıf paragrafındaki sayılar hedef sayfada yok: "
                            f"{', '.join(missing)} — manuel inceleme: derin linki, sayıyı düzeltin veya iddiayı çıkarın",
                }
            return {"status": "pass", "note": f"sayı örtüşmesi {len(nums)}/{len(nums)}"}
        ents = self.extract_entities(citing_text)
        matched = []
        for e in ents:
            if re.search(r"\b" + re.escape(e) + r"\b", target, re.I):
                matched.append(e)
        if matched:
            return {"status": "pass", "note": f"varlık örtüşmesi: {', '.join(matched[:3])}"}
        if len(ents) >= 2:
            return {
                "status": "block",
                "note": f"R3 örtüşme hatası: atıf paragrafındaki varlıklar ({', '.join(ents[:4])}) "
                        f"hedef sayfada hiç yok — manuel inceleme: link yanlış sayfaya mı ait?",
            }
        return {"status": "review",
                "note": "R3 değerlendirilemedi: sayı yok, varlık elverişsiz — manuel inceleme gerekli"}

    # ------------------------------------------------------------------ helpers
    @staticmethod
    def load_dossier(path: str) -> Dict[str, Any]:
        with open(path, encoding="utf-8") as f:
            data = json.load(f)
        sources = data.get("sources")
        if not isinstance(sources, list):
            raise ValueError("dossier JSON 'sources' listesi içermeli")
        return data

    @staticmethod
    def norm_url(url: str) -> str:
        return url.strip().rstrip("/")

    def _paragraphs(self, text: str) -> List[str]:
        paras = re.findall(r"<p[^>]*>(.*?)</p>", text, re.S | re.I)
        return paras if paras else [text]

    def _find_citing_block(self, url: str, text: str) -> Optional[str]:
        for para in self._paragraphs(text):
            if url in para:
                return para
        idx = text.find(url)
        if idx >= 0:
            return text[max(0, idx - 400): idx + 400]
        return None

    # ------------------------------------------------------------------ old matrix
    def extract_numbers_and_dates(self, text: str) -> List[str]:
        clean = strip_tags(text)
        matches = []
        for m in re.finditer(r"\d[\d.,:/\-]*\s*(?:yüzde|%|trilyon|milyar|milyon|bin)?", clean):
            tok = m.group(0).strip()
            if tok and tok not in matches:
                matches.append(tok)
        return matches

    def extract_quotes(self, text: str) -> List[str]:
        clean = strip_tags(text)
        return [m.strip() for m in re.findall(r"\"([^\"]{10,})\"", clean)]

    def extract_citations(self, text: str) -> List[str]:
        clean = strip_tags(text)
        out = []
        for m in CITATION_PHRASE_RE.finditer(clean):
            start = max(0, m.start() - 40)
            out.append(clean[start:m.end()].strip())
        return out

    # ------------------------------------------------------------------ core audits
    def audit_url(self, url: str, citing_text: Optional[str], role: Optional[str],
                  skip_network: bool = False) -> Dict[str, Any]:
        entry: Dict[str, Any] = {"url": url, "role": role or "-",
                                 "liveness": None, "precision": None, "anchoring": None}
        precision = self.classify_precision(url)
        entry["precision"] = precision
        if skip_network:
            entry["liveness"] = {"live": None, "note": "ağ denetimi atlandı (--no-network)"}
            entry["anchoring"] = {"status": "unknown", "note": "ağ denetimi atlandı"}
            return entry
        page = self.fetch_page(url)
        entry["liveness"] = page
        if page["live"]:
            anchor_text = citing_text if citing_text is not None else url
            entry["anchoring"] = self.anchoring(anchor_text, page["html"])
        else:
            entry["anchoring"] = {"status": "unknown", "note": page.get("note") or "hedefe erişilemedi"}
        return entry

    def audit_draft(self, text: str, dossier: Optional[Dict[str, Any]] = None,
                    skip_network: bool = False) -> Dict[str, Any]:
        role_map: Dict[str, str] = {}
        if dossier:
            for src in dossier.get("sources", []):
                role_map[self.norm_url(src.get("url", ""))] = src.get("role", "-")

        hrefs: List[str] = []
        for m in HREF_RE.finditer(text):
            u = m.group(1).rstrip(".,;:)")
            if u not in hrefs:
                hrefs.append(u)
        raw_urls = [u.rstrip(".,;:)'") for u in RAW_URL_RE.findall(strip_tags(text))]
        bare_urls = [u for u in raw_urls if u not in hrefs]

        url_audit: List[Dict[str, Any]] = []
        for u in hrefs:
            role = role_map.get(self.norm_url(u))
            block = self._find_citing_block(u, text)
            url_audit.append(self.audit_url(u, block, role, skip_network))

        for u in bare_urls:
            if u.startswith(("http", "https")):
                entry = self.audit_url(u, None, role_map.get(self.norm_url(u)), skip_network)
                entry["bare_url"] = True
                url_audit.append(entry)

        # R4: citation linkage
        linkage: List[Dict[str, Any]] = []
        for para in self._paragraphs(text):
            if CITATION_PHRASE_RE.search(strip_tags(para)) and not HREF_RE.search(para):
                linkage.append({
                    "issue": "atıf cümlesi link taşımıyor (R4)",
                    "paragraph": strip_tags(para).strip()[:120],
                })

        # R4: dossier containment
        containment: List[Dict[str, Any]] = []
        if dossier is not None:
            known = {self.norm_url(s.get("url", "")) for s in dossier.get("sources", [])}
            for u in hrefs:
                if self.norm_url(u) not in known:
                    containment.append({
                        "url": u,
                        "issue": "URL dösyede yok — zincir-i emniyet kırık (R4); önce dösyeye rol etiketiyle ekleyip Stage 1'i geçin",
                    })

        # R4: minimum evidence
        zero_link = len(hrefs) == 0

        passed = True
        status = "PASSED"
        for e in url_audit:
            if not skip_network:
                if not e["liveness"]["live"]:
                    passed = False
            if not e["precision"]["precise"]:
                passed = False
            if not e.get("bare_url") and e["anchoring"] and e["anchoring"].get("status") in ("block",):
                passed = False
            if not e.get("bare_url") and e["anchoring"] and e["anchoring"].get("status") == "review":
                passed = False
                status = "REVIEW"
        if linkage or containment or zero_link:
            passed = False

        report = {
            "passed": passed,
            "status": status if not passed else "PASSED",
            "mode": "stage2_draft",
            "total_urls": len(url_audit),
            "citation_link_count": len(hrefs),
            "url_audit": url_audit,
            "citation_linkage": linkage,
            "dossier_containment": containment,
            "zero_link_draft": zero_link,
            "factual_matrix": {
                "numbers_and_dates": self.extract_numbers_and_dates(text),
                "quotes": self.extract_quotes(text),
                "citations": self.extract_citations(text),
            },
        }
        return report

    def audit_dossier(self, dossier: Dict[str, Any], skip_network: bool = False) -> Dict[str, Any]:
        url_audit: List[Dict[str, Any]] = []
        for src in dossier.get("sources", []):
            url = src.get("url", "")
            claims = " ".join(src.get("claims", [])) or url
            url_audit.append(self.audit_url(url, claims, src.get("role"), skip_network))

        passed = True
        status = "PASSED"
        for e in url_audit:
            if not skip_network and not e["liveness"]["live"]:
                passed = False
            if not e["precision"]["precise"]:
                passed = False
            if e["anchoring"] and e["anchoring"].get("status") == "block":
                passed = False
            if e["anchoring"] and e["anchoring"].get("status") == "review":
                passed = False
                status = "REVIEW"

        return {
            "passed": passed,
            "status": status if not passed else "PASSED",
            "mode": "stage1_dossier",
            "total_urls": len(url_audit),
            "url_audit": url_audit,
        }


def print_report(report: Dict[str, Any]) -> None:
    print("=" * 60)
    print("KEVRAST: Fact-Checking & Link Provenance Audit Report")
    print("=" * 60)
    verdict = report["passed"] and "PASSED" or ("REVIEW" if report["status"] == "REVIEW" else "BLOCKED")
    print(f"Status: {verdict} | Mode: {report.get('mode', '-')}\n")

    print(f"[Link Provenance Audit] Total links checked: {report['total_urls']}")
    for e in report["url_audit"]:
        parts = []
        if e["liveness"] is None:
            parts.append("liveness:-")
        elif e["liveness"].get("live") is True:
            parts.append(f"liveness:OK (HTTP {e['liveness'].get('status_code')})")
        elif e["liveness"].get("live") is None:
            parts.append(f"liveness:SKIP ({e['liveness'].get('note')})")
        else:
            parts.append(f"liveness:FAIL ({e['liveness'].get('status_code') or e['liveness'].get('note')})")
        prec = e["precision"] or {}
        parts.append("precision:" + ("OK" if prec.get("precise") else f"FAIL/{prec.get('kind')}"))
        if not e.get("bare_url"):
            anch = e["anchoring"] or {}
            parts.append("anchoring:" + str(anch.get("status", "-")))
        mark = "[OK]" if all("FAIL" not in p and "block" not in p for p in parts) else "[!]"
        print(f"  {mark} [{e.get('role', '-')}] {e['url']}")
        print(f"      {' | '.join(parts)}")
        for section in ("liveness", "anchoring"):
            note = (e.get(section) or {}).get("note")
            if note:
                print(f"      not: {note}")
        if prec.get("note"):
            print(f"      not: {prec['note']}")

    for extra in ("citation_linkage", "dossier_containment"):
        for item in report.get(extra, []):
            print(f"  [!] {item['issue']}: {item.get('url') or item.get('paragraph', '')}")

    if report.get("zero_link_draft"):
        print("  [!] R4: taslakta hiç doğrulanmış dış atıf linki yok (sıfır-link haber PASSED olamaz)")

    fm = report.get("factual_matrix")
    if fm:
        print(f"\n[Factual Entity & Claim Matrix]")
        print(f"  - Extracted Numbers & Dates ({len(fm['numbers_and_dates'])}):")
        for num in fm["numbers_and_dates"][:10]:
            print(f"    * {num}")
        print(f"  - In-Text Citations ({len(fm['citations'])}):")
        for cit in fm["citations"]:
            print(f"    * {cit}")
        print(f"  - Quoted Statements ({len(fm['quotes'])}):")
        for q in fm["quotes"]:
            print(f"    * {q}")

    print("=" * 60)


def main():
    parser = argparse.ArgumentParser(
        description="Kevrast Fact-Checking, Link Provenance & Claim Anchoring Auditor")
    parser.add_argument("--file", "-f", help="Path to article HTML or text file")
    parser.add_argument("--text", "-t", help="Raw article HTML or text")
    parser.add_argument("--dossier", "-d",
                        help="Kevkolin dossier JSON (role-tagged source list); "
                             "alone = Stage 1, with --text/--file = Stage 2")
    parser.add_argument("--no-network", action="store_true",
                        help="Skip live HTTP verification (structural checks only)")
    parser.add_argument("--json", action="store_true", help="Output results in JSON format")
    args = parser.parse_args()

    dossier = None
    if args.dossier:
        try:
            dossier = KevrastAuditor.load_dossier(args.dossier)
        except (OSError, ValueError, json.JSONDecodeError) as e:
            print(f"HATA: döşme okunamadı ({args.dossier}): {e}", file=sys.stderr)
            sys.exit(2)

    content = None
    if args.file:
        with open(args.file, encoding="utf-8") as f:
            content = f.read()
    elif args.text:
        content = args.text
    elif not sys.stdin.isatty():
        content = sys.stdin.read()
    # Empty stdin (piped nothing) must not masquerade as an empty draft:
    # prefer dossier-only Stage 1 mode when a dossier was supplied.
    if content is not None and not content.strip() and dossier is not None:
        content = None

    if content is None and dossier is None:
        parser.print_help()
        sys.exit(2)

    auditor = KevrastAuditor()
    if content is None:
        report = auditor.audit_dossier(dossier, skip_network=args.no_network)
    else:
        report = auditor.audit_draft(content, dossier, skip_network=args.no_network)

    if args.json:
        print(json.dumps(report, ensure_ascii=False, indent=2, default=str))
    else:
        print_report(report)

    sys.exit(0 if report["passed"] else 1)


if __name__ == "__main__":
    main()
