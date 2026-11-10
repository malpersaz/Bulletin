#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Kevrast Fact-Checking, Link Provenance & Claim Anchoring Auditor

Rules (see ../SKILL.md):
  R1 Liveness       - full GET over verified TLS; soft-404 / redirect-to-home / 4xx-5xx = FAILED
  R2 Precision      - citation hrefs must be deep content pages; domain roots and
                      section/rubric fronts ALWAYS FAIL (no root-domain fallback)
  R3 Anchoring      - distinctive figures of the citing paragraph must appear in the
                      fetched target page text (71% <-> 71 percent, 1 billion <-> 1 milyar);
                      mismatch -> BLOCK + manual review note; unevaluable -> REVIEW
  R4 Completeness   - every attribution phrase must sit in a paragraph with a live link;
                      draft URL set must be a subset of the dossier URL set;
                      zero-outbound-link drafts FAIL
  R5 Dossier modes  - --dossier alone = Stage 1 (audit dossier URLs vs claims);
                      --dossier + --text/--file = Stage 2 (audit draft against dossier)

Exit codes: 0 = PASSED, 1 = BLOCKED/REVIEW, 2 = usage error.
"""

import argparse
import copy
import json
import os
import re
import ssl
import sys
import urllib.error
import urllib.parse
import urllib.request
from datetime import datetime, timezone
from pathlib import Path
from typing import Any, Dict, List, Optional, Set, Tuple

DEFAULT_USER_AGENT = (
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 "
    "(KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
)
MAX_BYTES = 2 * 1024 * 1024

TAG_RE = re.compile(r"<[^>]+>")
HREF_RE = re.compile(r"<a[^>]+href=[\'\"](https?://[^\'\"]+)[\'\"]", re.I)
RAW_URL_RE = re.compile(r"https?://[^\s<>\"']+")

# Attribution phrases (journalistic citation markers)
CITATION_PHRASE_RE = re.compile(
    r"haberine göre|aktarılan bilgilere göre|bilgilerine göre|verdiği bilgiye göre|"
    r"raporuna göre|anketine göre|araştırmasına göre|açıklamasına göre|ifadesine göre|"
    r"verilerine göre|bulgularına göre|söyledi|açıkladı|"
    r"according to|reported by|stated that|told reporters",
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
    "January", "February", "March", "April", "May", "June", "July", "August",
    "September", "October", "November", "December", "Monday", "Tuesday",
    "Wednesday", "Thursday", "Friday", "Saturday", "Sunday", "However",
}

SOFT_404_TITLE_RE = re.compile(
    r"<title[^>]*>[^<]*(?:404|not found|bulunamad|page not found)[^<]*</title>", re.I)

QUERY_ID_PARAMS = ("id", "p", "post", "art", "article", "story", "haber", "news")

# Flat single-segment article permalinks that end in a numeric ID (+ optional
# outlet letter marker) + extension (e.g. internethaber.com/...-2467480.htm,
# tigrishaber.com/...-173864h.htm) are deep content pages, not rubric fronts.
FLAT_ARTICLE_RE = re.compile(r"\d+[a-z]?\.html?$", re.I)

PLACEHOLDER_URL_RE = re.compile(r"example\.com|replace_with|placeholder", re.I)


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
        # In-memory page cache ensuring <= 1 HTTP GET per unique normalized URL
        self._page_cache: Dict[str, Dict[str, Any]] = {}

    # ------------------------------------------------------------------ R1
    def fetch_page(self, url: str) -> Dict[str, Any]:
        norm = self.norm_url(url)
        if norm in self._page_cache:
            return copy.deepcopy(self._page_cache[norm])

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
            self._page_cache[norm] = copy.deepcopy(result)
            return result
        except Exception as e:
            result["note"] = f"Access/TLS error ({e.__class__.__name__}: {e}) - manual check required"
            self._page_cache[norm] = copy.deepcopy(result)
            return result

        if not (200 <= result["status_code"] < 300):
            self._page_cache[norm] = copy.deepcopy(result)
            return result

        html = result["html"] or ""
        # Soft-404 heuristics
        if SOFT_404_TITLE_RE.search(html):
            result["note"] = "soft-404: title indicates error or not-found page"
            self._page_cache[norm] = copy.deepcopy(result)
            return result
        if len(html.strip()) < 500:
            result["note"] = "soft-404: content too short (<500 bytes)"
            self._page_cache[norm] = copy.deepcopy(result)
            return result
        orig = urllib.parse.urlparse(url)
        final = urllib.parse.urlparse(result["final_url"] or url)
        orig_root = (orig.path or "").strip("/") != ""
        final_root = (final.path or "/").strip("/") == ""
        if orig_root and final_root:
            result["note"] = "soft-404: redirected to domain root/homepage"
            self._page_cache[norm] = copy.deepcopy(result)
            return result

        result["live"] = True
        self._page_cache[norm] = copy.deepcopy(result)
        return result

    # ------------------------------------------------------------------ R2
    def classify_precision(self, url: str) -> Dict[str, Any]:
        p = urllib.parse.urlparse(url)
        segments = [s for s in (p.path or "").split("/") if s]
        query_keys = [k.lower() for k in urllib.parse.parse_qs(p.query or "").keys()]
        has_id_query = any(k in QUERY_ID_PARAMS for k in query_keys)
        if not segments and not has_id_query:
            return {
                "precise": False,
                "kind": "domain_root",
                "note": "Domain root cannot be used as citation target (R2: deep content link required)",
            }
        flat_article = bool(segments) and bool(FLAT_ARTICLE_RE.search(segments[-1]))
        if len(segments) <= 1 and not has_id_query and not flat_article:
            return {
                "precise": False,
                "kind": "section_front",
                "note": "Section/rubric front cannot be used as citation target (R2: article page required)",
            }
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
            # Drop suffixes attached with apostrophe (Nasdaq'daki -> Nasdaq)
            base = tok.split("'")[0].split("’")[0]
            if len(base) >= 3 and base not in entities:
                entities.append(base)
        return entities

    def anchoring(self, citing_text: str, target_html: Optional[str]) -> Dict[str, Any]:
        if target_html is None:
            return {"status": "unknown", "note": "Target page could not be fetched (R1)"}
        target = strip_tags(target_html)
        nums = self.extract_distinctive_numbers(citing_text)
        if nums:
            target_nums = [normalize_number(m.group(1)) for m in TARGET_NUM_RE.finditer(target)]
            missing = [n for n in nums if not any(n == t or n in t for t in target_nums)]
            if missing:
                return {
                    "status": "block",
                    "missing_numbers": missing,
                    "note": f"R3 anchoring error: figures in citing paragraph missing on target page: "
                            f"{', '.join(missing)} - manual review: correct link, update figure, or remove claim",
                }
            return {"status": "pass", "note": f"Figure match {len(nums)}/{len(nums)}"}
        ents = self.extract_entities(citing_text)
        matched = []
        for e in ents:
            if re.search(r"\b" + re.escape(e) + r"\b", target, re.I):
                matched.append(e)
        if matched:
            return {"status": "pass", "note": f"Entity match: {', '.join(matched[:3])}"}
        if len(ents) >= 2:
            return {
                "status": "block",
                "missing_entities": ents[:4],
                "note": f"R3 anchoring error: entities ({', '.join(ents[:4])}) in citing paragraph "
                        f"not found on target page - verify target page link",
            }
        return {
            "status": "review",
            "note": "R3 unevaluable: no distinctive figures and insufficient entities - manual review required",
        }

    # ------------------------------------------------------------------ helpers
    @staticmethod
    def load_dossier(path: str) -> Dict[str, Any]:
        with open(path, encoding="utf-8") as f:
            data = json.load(f)
        sources = data.get("sources")
        if not isinstance(sources, list):
            raise ValueError("Dossier JSON must contain a 'sources' list")
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

    # ------------------------------------------------------------------ factual matrix
    def extract_numbers_and_dates(self, text: str) -> List[str]:
        clean = strip_tags(text)
        matches = []
        for m in re.finditer(r"\d[\d.,:/\-]*\s*(?:yüzde|%|trilyon|milyar|milyon|bin|percent|trillion|billion|million)?", clean):
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
        entry: Dict[str, Any] = {
            "url": url,
            "role": role or "-",
            "liveness": None,
            "precision": None,
            "anchoring": None,
        }
        precision = self.classify_precision(url)
        entry["precision"] = precision
        if skip_network:
            entry["liveness"] = {"live": None, "note": "Network check skipped (--no-network)"}
            entry["anchoring"] = {"status": "unknown", "note": "Network check skipped"}
            return entry

        page = self.fetch_page(url)
        entry["liveness"] = page
        if page["live"]:
            # Fix empty-claims / URL-digit bug: Only anchor if citing_text is non-empty
            if citing_text and citing_text.strip():
                entry["anchoring"] = self.anchoring(citing_text, page["html"])
            else:
                entry["anchoring"] = {"status": "pass", "note": "No claims or citing text provided to anchor (liveness verified)"}
        else:
            entry["anchoring"] = {"status": "unknown", "note": page.get("note") or "Target unreachable"}
        return entry

    def audit_draft(self, text: str, dossier: Optional[Dict[str, Any]] = None,
                    skip_network: bool = False, explain: bool = False) -> Dict[str, Any]:
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
        paragraphs = self._paragraphs(text)
        for idx, para in enumerate(paragraphs, start=1):
            if CITATION_PHRASE_RE.search(strip_tags(para)) and not HREF_RE.search(para):
                linkage.append({
                    "issue": "Attribution phrase lacks hyperlink (R4)",
                    "paragraph_index": idx,
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
                        "issue": "URL not found in research dossier (R4 chain of custody broken); add to dossier with role tag first",
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

        # Component G: Detailed paragraph-by-paragraph diagnostic analysis
        diagnostics: List[Dict[str, Any]] = []
        if explain:
            known_dossier_urls = {self.norm_url(s.get("url", "")) for s in dossier.get("sources", [])} if dossier else None
            for p_idx, para in enumerate(paragraphs, start=1):
                clean_p = strip_tags(para).strip()
                p_hrefs = [m.group(1).rstrip(".,;:)") for m in HREF_RE.finditer(para)]
                snippet = (clean_p[:60] + "...") if len(clean_p) > 60 else clean_p

                # 1. R4 unlinked attribution
                cit_m = CITATION_PHRASE_RE.search(clean_p)
                if cit_m and not p_hrefs:
                    phrase = cit_m.group(0)
                    diagnostics.append({
                        "paragraph_index": p_idx,
                        "snippet": snippet,
                        "code": "R4_UNLINKED_ATTRIBUTION",
                        "severity": "ERROR",
                        "url": None,
                        "detail": f"Attribution phrase '{phrase}' has no outbound source link in this paragraph.",
                        "remediation": "Add an <a href='...'> linking to the source reporting this statement.",
                    })

                # 2. Multi-link variance collisions
                if len(p_hrefs) >= 2:
                    diagnostics.append({
                        "paragraph_index": p_idx,
                        "snippet": snippet,
                        "code": "MULTI_LINK_PARAGRAPH",
                        "severity": "ADVISORY",
                        "url": ", ".join(p_hrefs),
                        "detail": f"Paragraph cites {len(p_hrefs)} distinct source URLs.",
                        "remediation": "Consider splitting paragraph so each distinct source is cited in its own paragraph to avoid attribution collisions.",
                    })

                # 3. Per-link paragraph audits
                for u in p_hrefs:
                    # Dossier containment
                    if known_dossier_urls is not None and self.norm_url(u) not in known_dossier_urls:
                        diagnostics.append({
                            "paragraph_index": p_idx,
                            "snippet": snippet,
                            "code": "R4_DOSSIER_CONTAINMENT",
                            "severity": "ERROR",
                            "url": u,
                            "detail": f"Link '{u}' is absent from research dossier sources.",
                            "remediation": "Add source URL with role to research dossier and verify in Stage 1.",
                        })

                    # Precision & Liveness
                    prec = self.classify_precision(u)
                    if not prec["precise"]:
                        diagnostics.append({
                            "paragraph_index": p_idx,
                            "snippet": snippet,
                            "code": "R2_PRECISION_FAIL",
                            "severity": "ERROR",
                            "url": u,
                            "detail": prec["note"] or f"Imprecise link ({prec['kind']})",
                            "remediation": "Replace with a deep article permalink.",
                        })

                    if not skip_network:
                        page_res = self.fetch_page(u)
                        if not page_res["live"]:
                            diagnostics.append({
                                "paragraph_index": p_idx,
                                "snippet": snippet,
                                "code": "R1_LIVENESS_FAIL",
                                "severity": "ERROR",
                                "url": u,
                                "detail": f"Liveness failed: HTTP {page_res.get('status_code')} ({page_res.get('note')})",
                                "remediation": "Ensure target URL is reachable and does not return 4xx/5xx or soft-404.",
                            })
                        else:
                            # Audit anchoring specifically for this paragraph
                            p_anch = self.anchoring(para, page_res["html"])
                            if p_anch.get("status") == "block":
                                missing_nums = p_anch.get("missing_numbers", [])
                                missing_ents = p_anch.get("missing_entities", [])
                                if missing_nums:
                                    diagnostics.append({
                                        "paragraph_index": p_idx,
                                        "snippet": snippet,
                                        "code": "R3_MISSING_NUMBER",
                                        "severity": "ERROR",
                                        "url": u,
                                        "detail": f"Figure(s) {missing_nums} not found on linked target <{u}>",
                                        "remediation": "Move figure to paragraph citing its source, fix link, or remove claim.",
                                    })
                                elif missing_ents:
                                    diagnostics.append({
                                        "paragraph_index": p_idx,
                                        "snippet": snippet,
                                        "code": "R3_MISSING_ENTITY",
                                        "severity": "ERROR",
                                        "url": u,
                                        "detail": f"Entity/entities {missing_ents} not found on linked target <{u}>",
                                        "remediation": "Verify whether link targets the correct article or remove unrelated entity.",
                                    })

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
            "diagnostics": diagnostics,
            "factual_matrix": {
                "numbers_and_dates": self.extract_numbers_and_dates(text),
                "quotes": self.extract_quotes(text),
                "citations": self.extract_citations(text),
            },
        }
        return report

    def audit_dossier(self, dossier: Dict[str, Any], skip_network: bool = False) -> Dict[str, Any]:
        url_audit: List[Dict[str, Any]] = []
        sources = dossier.get("sources", [])
        if not sources:
            return {
                "passed": False,
                "status": "BLOCKED",
                "mode": "stage1_dossier",
                "total_urls": 0,
                "url_audit": [],
                "error": "Dossier contains empty 'sources' array",
            }

        # Check for placeholder URLs in Stage 1
        for idx, src in enumerate(sources):
            url = src.get("url", "")
            if PLACEHOLDER_URL_RE.search(url):
                return {
                    "passed": False,
                    "status": "BLOCKED",
                    "mode": "stage1_dossier",
                    "total_urls": len(sources),
                    "url_audit": [],
                    "error": f"sources[{idx}].url: placeholder URL must be replaced with real source URL before running Stage 1: {url}",
                }

        for src in sources:
            url = src.get("url", "")
            claims_list = src.get("claims", [])
            claims = " ".join(claims_list).strip() if claims_list else None
            # Fix empty-claims bug: pass None instead of url when claims is empty
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


def validate_dossier_file(path: str) -> Tuple[bool, str]:
    if not os.path.isfile(path):
        return False, f"File not found: {path}"
    try:
        with open(path, "r", encoding="utf-8") as f:
            data = json.load(f)
    except Exception as e:
        return False, f"Invalid JSON format: {e}"

    if not isinstance(data, dict):
        return False, "Dossier root must be a JSON object"

    # Schema validation
    schema_path = Path(__file__).resolve().parent.parent.parent / "kevkolin" / "scripts" / "dossier.schema.json"
    if schema_path.is_file():
        try:
            import jsonschema
            with open(schema_path, "r", encoding="utf-8") as sf:
                schema = json.load(sf)
            jsonschema.validate(instance=data, schema=schema)
        except ImportError:
            pass  # Fallback to manual validation below
        except Exception as e:
            return False, f"Schema validation error: {e}"

    sources = data.get("sources")
    if not isinstance(sources, list) or len(sources) == 0:
        return False, "Dossier 'sources' must be a non-empty array"

    has_primary = False
    for i, s in enumerate(sources):
        if not isinstance(s, dict):
            return False, f"sources[{i}] must be an object"
        u = str(s.get("url") or "")
        if not (u.startswith("http://") or u.startswith("https://")):
            return False, f"sources[{i}].url must be a valid HTTP/HTTPS URL"
        role = s.get("role")
        if role not in ("primary", "supplementary", "historical"):
            return False, f"sources[{i}].role must be one of 'primary', 'supplementary', 'historical'"
        if role == "primary":
            has_primary = True

    if not has_primary:
        return False, "Dossier must contain at least one source with role 'primary'"

    content_type = data.get("type", "news")
    if content_type == "evergreen":
        rationale = str(data.get("evergreen_rationale") or "").strip()
        if len(rationale) < 20:
            return False, "Evergreen dossier requires 'evergreen_rationale' of at least 20 characters"

    return True, "Valid"


def init_dossier_skeleton(path: str, force: bool = False) -> None:
    if os.path.exists(path) and not force:
        print(f"Error: Destination file '{path}' already exists. Use --force to overwrite.", file=sys.stderr)
        sys.exit(2)

    now_date = datetime.now(timezone.utc).strftime("%Y-%m-%d")
    skeleton = {
        "topic": "Investigative Topic Headline",
        "date": now_date,
        "type": "news",
        "summary_of_event": "",
        "key_entities": [],
        "timeline": [],
        "verified_facts": {
            "numbers_and_metrics": [],
            "dates_and_times": [],
            "official_statements_and_quotes": []
        },
        "sources": [
            {
                "url": "https://example.com/REPLACE_WITH_PRIMARY_URL",
                "role": "primary",
                "outlet": "Primary Source Outlet",
                "pub_date": now_date,
                "claims": []
            }
        ],
        "contradictions": [],
        "status": "ready_for_kevrast_stage_1"
    }

    dir_name = os.path.dirname(os.path.abspath(path))
    if dir_name:
        os.makedirs(dir_name, exist_ok=True)
    with open(path, "w", encoding="utf-8") as f:
        json.dump(skeleton, f, indent=2, ensure_ascii=False)
    print(f"Initialized research dossier template at: {path}")


def print_report(report: Dict[str, Any]) -> None:
    print("=" * 60)
    print("KEVRAST: Fact-Checking & Link Provenance Audit Report")
    print("=" * 60)
    verdict = report["passed"] and "PASSED" or ("REVIEW" if report["status"] == "REVIEW" else "BLOCKED")
    print(f"Status: {verdict} | Mode: {report.get('mode', '-')}\n")

    if report.get("error"):
        print(f"ERROR: {report['error']}\n")
        print("=" * 60)
        return

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
                print(f"      note: {note}")
        if prec.get("note"):
            print(f"      note: {prec['note']}")

    for extra in ("citation_linkage", "dossier_containment"):
        for item in report.get(extra, []):
            print(f"  [!] {item['issue']}: {item.get('url') or item.get('paragraph', '')}")

    if report.get("zero_link_draft"):
        print("  [!] R4: Draft contains zero verified external links (zero-link article cannot PASS)")

    diagnostics = report.get("diagnostics", [])
    if diagnostics:
        print(f"\n[Diagnostics & Editorial Explanations ({len(diagnostics)})]")
        for d in diagnostics:
            p_prefix = f"[P{d['paragraph_index']}]"
            print(f"  {p_prefix} [{d['code']}] {d['detail']} -> {d['remediation']}")

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
    parser.add_argument("--explain", action="store_true",
                        help="Enable detailed paragraph-by-paragraph diagnostic analysis and remediation advice")
    parser.add_argument("--init-dossier", help="Initialize a blank structured research dossier template at path")
    parser.add_argument("--validate-dossier", help="Perform offline schema validation on research dossier at path")
    parser.add_argument("--force", action="store_true", help="Force overwrite when initializing dossier")
    parser.add_argument("--json", action="store_true", help="Output results in JSON format")
    args = parser.parse_args()

    if args.init_dossier:
        init_dossier_skeleton(args.init_dossier, force=args.force)
        sys.exit(0)

    if args.validate_dossier:
        valid, msg = validate_dossier_file(args.validate_dossier)
        if valid:
            if args.json:
                print(json.dumps({"valid": True, "path": args.validate_dossier}, indent=2))
            else:
                print(f"PASSED: Dossier '{args.validate_dossier}' conforms to schema.")
            sys.exit(0)
        else:
            if args.json:
                print(json.dumps({"valid": False, "path": args.validate_dossier, "error": msg}, indent=2))
            else:
                print(f"VALIDATION FAILED: {msg}", file=sys.stderr)
            sys.exit(1)

    dossier = None
    if args.dossier:
        try:
            dossier = KevrastAuditor.load_dossier(args.dossier)
        except (OSError, ValueError, json.JSONDecodeError) as e:
            print(f"ERROR: Could not load dossier ({args.dossier}): {e}", file=sys.stderr)
            sys.exit(2)

    content = None
    if args.file:
        with open(args.file, encoding="utf-8") as f:
            content = f.read()
    elif args.text:
        content = args.text
    elif not sys.stdin.isatty():
        content = sys.stdin.read()
    if content is not None and not content.strip() and dossier is not None:
        content = None

    if content is None and dossier is None:
        parser.print_help()
        sys.exit(2)

    auditor = KevrastAuditor()
    if content is None:
        report = auditor.audit_dossier(dossier, skip_network=args.no_network)
    else:
        report = auditor.audit_draft(content, dossier, skip_network=args.no_network, explain=args.explain)

    if args.json:
        print(json.dumps(report, ensure_ascii=False, indent=2, default=str))
    else:
        print_report(report)

    sys.exit(0 if report["passed"] else 1)


if __name__ == "__main__":
    main()
