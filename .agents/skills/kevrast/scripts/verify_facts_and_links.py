#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Kevrast Fact-Checking, Link Provenance & Claim Anchoring Auditor

Rules (see ../SKILL.md):
  R1 Liveness       - full GET over verified TLS; soft-404 / redirect-to-home / 4xx-5xx = FAILED
  R2 Precision      - citation hrefs must be deep content pages; domain roots and
                      section/rubric fronts ALWAYS FAIL (no root-domain fallback)
                      (exception: verified single-segment vendor product pages)
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
from pathlib import Path
import re
import ssl
import sys
import unicodedata
import urllib.error
import urllib.parse
import urllib.request
from datetime import datetime, timezone
from typing import Any, Dict, List, Optional, Set, Tuple

# Integration with shared editorial HTTP client
_SHARED_DIR = Path(__file__).resolve().parent.parent.parent / "_shared"
if str(_SHARED_DIR) not in sys.path:
    sys.path.insert(0, str(_SHARED_DIR))

try:
    from editorial_http import fetch_html as shared_fetch_html, validate_public_url
except ImportError:
    shared_fetch_html = None
    validate_public_url = None

DEFAULT_USER_AGENT = (
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 "
    "(KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
)
MAX_BYTES = 2 * 1024 * 1024

TAG_RE = re.compile(r"<[^>]+>")
HREF_RE = re.compile(r"<a[^>]+href=[\'\"](https?://[^\'\"]+)[\'\"]", re.I)
RAW_URL_RE = re.compile(r"https?://[^\s<>\"']+")

# Non-content tags to strip before text and numeric extraction
NON_CONTENT_TAGS_RE = re.compile(
    r"<(script|style|noscript|svg|nav|footer|aside)\b[^>]*>.*?</\1>",
    re.S | re.I,
)

# Preserve article metadata before stripping non-content tags
META_ARTICLE_RE = re.compile(
    r'<meta\s+[^>]*(?:property|name)=[\'"](?:og:title|og:description|twitter:description|description)[\'"][^>]*content=[\'"]([^\'"]+)[\'"]',
    re.I,
)
META_ARTICLE_REV_RE = re.compile(
    r'<meta\s+[^>]*content=[\'"]([^\'"]+)[\'"][^>]*\s+(?:property|name)=[\'"](?:og:title|og:description|twitter:description|description)[\'"]',
    re.I,
)

# Single-segment product page allowlist & version regex
SINGLE_SEGMENT_PRODUCT_SLUGS = {
    "download", "downloads", "releases", "changelog",
    "security", "pricing", "status", "docs", "manifesto", "about",
}
SEMANTIC_VERSION_RE = re.compile(r"\bv?\d+\.\d+(?:\.\d+)?\b", re.I)

# Expanded Outlet Attribution (strictly requires link in same paragraph)
OUTLET_ATTRIBUTION_RE = re.compile(
    r"(?:"
    r"(?:haberine|aktardığına|bildirdiğine|paylaştığına|duyurduğuna|analize|analizine|"
    r"bloguna|raporuna|anketine|araştırmasına|açıklamasına|ifadesine|verilerine|"
    r"bulgularına|sürüm\s+notlarına|dokümantasyonuna|belgesine|kayıtlarına)\s+göre|"
    r"(?:aktarılan|paylaşılan|verilen|yayımlanan|yayınlanan)\s+bilgilere\s+göre|"
    r"verdiği\s+bilgiye\s+göre|bilgilerine\s+göre|"
    r"(?:aktarıldığı|bildirildiği|belirtildiği|kaydedildiği)\s+üzere|"
    r"\bkaynağında\b|\bkaynaklarına\s+göre\b|"
    r"according\s+to|reported\s+by"
    r")",
    re.I,
)

# Speech Attribution (allows link in current or preceding paragraph)
SPEECH_ATTRIBUTION_RE = re.compile(
    r"\b(?:dedi|söyledi|açıkladı|ifade\s+etti|belirtti|kaydetti|vurguladı|"
    r"told\s+reporters|stated\s+that|added\s+that)\b",
    re.I,
)

# Turkish Morphology & Normalization
def tr_fold(text: str) -> str:
    """Case-folding handling Turkish dotted/dotless I correctly."""
    res = text.replace("İ", "i").replace("I", "ı")
    res = unicodedata.normalize("NFC", res).lower()
    return res.replace("\u0307", "")


INSTITUTIONAL_RULES = [
    (re.compile(r"belediyesi(?:ne|nde|nden|nin|ni)$", re.I), "belediyesi"),
    (re.compile(r"bakanlığı(?:na|nda|ndan|nın|nı)$", re.I), "bakanlığı"),
    (re.compile(r"valiliği(?:ne|nde|nden|nin|ni)$", re.I), "valiliği"),
    (re.compile(r"üniversitesi(?:ne|nde|nden|nin|ni)$", re.I), "üniversitesi"),
    (re.compile(r"müdürlüğü(?:ne|nde|nden|nün|nü)$", re.I), "müdürlüğü"),
    (re.compile(r"başkanlığı(?:na|nda|ndan|nın|nı)$", re.I), "başkanlığı"),
    (re.compile(r"kaymakamlığı(?:na|nda|ndan|nın|nı)$", re.I), "kaymakamlığı"),
    (re.compile(r"odası(?:na|nda|ndan|nın|nı)$", re.I), "odası"),
    (re.compile(r"birliği(?:ne|nde|nden|nin|ni)$", re.I), "birliği"),
]


def stem_institutional(word: str) -> str:
    """Stems Turkish institutional proper noun case suffixes (e.g. Belediyesine -> belediyesi)."""
    for pattern, replacement in INSTITUTIONAL_RULES:
        if pattern.search(word):
            return pattern.sub(replacement, word)
    return word


TR_NUMBER_WORDS = {
    "sıfır": 0, "bir": 1, "iki": 2, "üç": 3, "uc": 3, "dört": 4, "dort": 4,
    "beş": 5, "bes": 5, "altı": 6, "alti": 6, "yedi": 7, "sekiz": 8, "dokuz": 9,
    "on": 10, "yirmi": 20, "otuz": 30, "kırk": 40, "kirk": 40,
    "elli": 50, "altmış": 60, "altmis": 60, "yetmiş": 70, "yetmis": 70,
    "seksen": 80, "doksan": 90,
}

TR_SCALES = {
    "yüz": 100, "yuz": 100,
    "bin": 1000,
    "milyon": 1_000_000,
    "milyar": 1_000_000_000,
    "trilyon": 1_000_000_000_000,
    "trillion": 1_000_000_000_000,
    "billion": 1_000_000_000,
    "million": 1_000_000,
}

PERCENT_RE_LIST = [
    re.compile(r"yüzde\s+([\d][\d.,]*)", re.I),
    re.compile(r"([\d][\d.,]*)\s*%"),
    re.compile(r"%\s*([\d][\d.,]*)"),
    re.compile(r"([\d][\d.,]*)\s*percent\b", re.I),
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

FLAT_ARTICLE_RE = re.compile(r"\d+[a-z]?\.html?$", re.I)
PLACEHOLDER_URL_RE = re.compile(r"example\.com|replace_with|placeholder", re.I)


def strip_tags(text: str) -> str:
    return TAG_RE.sub(" ", text)


def strip_non_content_tags(html: str) -> str:
    """Strip script, style, nav, footer, aside bodies while preserving metadata."""
    if not html:
        return ""
    meta_parts = META_ARTICLE_RE.findall(html) + META_ARTICLE_REV_RE.findall(html)
    clean_html = NON_CONTENT_TAGS_RE.sub(" ", html)
    plain = TAG_RE.sub(" ", clean_html)
    if meta_parts:
        plain += " " + " ".join(meta_parts)
    return plain


def normalize_number(raw: str) -> str:
    """Normalize numeric strings removing thousand separators and standardizing decimals."""
    raw = raw.strip()
    if re.match(r"^\d{1,3}(?:,\d{3})+$", raw):
        return raw.replace(",", "")
    if re.match(r"^\d{1,3}(?:\.\d{3})+$", raw):
        return raw.replace(".", "")
    if re.match(r"^\d+,\d+$", raw):
        return raw.replace(",", ".")
    return raw


class KevrastAuditor:
    def __init__(self, timeout: int = 20, user_agent: str = DEFAULT_USER_AGENT):
        self.timeout = timeout
        self.user_agent = user_agent
        self.ctx = ssl.create_default_context()
        self._page_cache: Dict[str, Dict[str, Any]] = {}

    # ------------------------------------------------------------------ R1
    def fetch_page(self, url: str) -> Dict[str, Any]:
        norm = self.norm_url(url)
        if norm in self._page_cache:
            return copy.deepcopy(self._page_cache[norm])

        result: Dict[str, Any] = {
            "url": url,
            "status_code": None,
            "final_url": None,
            "html": None,
            "live": False,
            "reachable": False,
            "unreachable_reason": None,
            "note": None,
        }

        if shared_fetch_html is not None:
            try:
                status, body, headers = shared_fetch_html(url, timeout=self.timeout)
            except Exception as e:
                status = 0
                body = ""
                headers = {"error": f"Transport exception: {e}", "x-error": f"Transport exception: {e}"}

            result["status_code"] = status
            result["final_url"] = headers.get("x-final-url") or url
            if 200 <= status < 300:
                result["html"] = body
            else:
                err_msg = headers.get("error") or headers.get("x-error") or f"HTTP {status}"
                result["note"] = err_msg
                result["reachable"] = False
                result["unreachable_reason"] = err_msg
                self._page_cache[norm] = copy.deepcopy(result)
                return result
        else:
            headers = {
                "User-Agent": self.user_agent,
                "Accept": "text/html,application/xhtml+xml,*/*;q=0.8",
                "Accept-Language": "tr,en;q=0.8",
            }
            try:
                req = urllib.request.Request(url, headers=headers, method="GET")
                with urllib.request.urlopen(req, timeout=self.timeout, context=self.ctx) as resp:
                    result["status_code"] = resp.status
                    result["final_url"] = resp.geturl()
                    result["html"] = resp.read(MAX_BYTES).decode("utf-8", errors="replace")
            except urllib.error.HTTPError as e:
                result["status_code"] = e.code
                result["note"] = f"HTTP {e.code}"
                result["reachable"] = False
                result["unreachable_reason"] = result["note"]
                self._page_cache[norm] = copy.deepcopy(result)
                return result
            except Exception as e:
                result["note"] = f"Access/TLS error ({e.__class__.__name__}: {e}) - manual check required"
                result["reachable"] = False
                result["unreachable_reason"] = result["note"]
                self._page_cache[norm] = copy.deepcopy(result)
                return result

            if not (200 <= (result["status_code"] or 0) < 300):
                result["reachable"] = False
                result["unreachable_reason"] = result["note"]
                self._page_cache[norm] = copy.deepcopy(result)
                return result

        html = result["html"] or ""
        # Soft-404 heuristics
        if SOFT_404_TITLE_RE.search(html):
            result["note"] = "soft-404: title indicates error or not-found page"
            result["reachable"] = False
            result["unreachable_reason"] = result["note"]
            self._page_cache[norm] = copy.deepcopy(result)
            return result
        if len(html.strip()) < 500:
            result["note"] = "soft-404: content too short (<500 bytes)"
            result["reachable"] = False
            result["unreachable_reason"] = result["note"]
            self._page_cache[norm] = copy.deepcopy(result)
            return result

        try:
            orig = urllib.parse.urlparse(url)
            final = urllib.parse.urlparse(result["final_url"] or url)
            orig_root = (orig.path or "").strip("/") != ""
            final_root = (final.path or "/").strip("/") == ""
            if orig_root and final_root:
                result["note"] = "soft-404: redirected to domain root/homepage"
                result["reachable"] = False
                result["unreachable_reason"] = result["note"]
                self._page_cache[norm] = copy.deepcopy(result)
                return result
        except (ValueError, Exception) as e:
            result["note"] = f"URL parse error: {e}"
            result["reachable"] = False
            result["unreachable_reason"] = result["note"]
            self._page_cache[norm] = copy.deepcopy(result)
            return result

        result["live"] = True
        result["reachable"] = True
        result["unreachable_reason"] = None
        self._page_cache[norm] = copy.deepcopy(result)
        return result

    # ------------------------------------------------------------------ R2
    def classify_precision(self, url: str, role: Optional[str] = None) -> Dict[str, Any]:
        try:
            p = urllib.parse.urlparse(url)
            segments = [s for s in (p.path or "").split("/") if s]
            query_keys = [k.lower() for k in urllib.parse.parse_qs(p.query or "").keys()]
        except (ValueError, Exception) as e:
            return {
                "precise": False,
                "kind": "malformed_url",
                "note": f"Malformed URL cannot be parsed ({e})",
            }
        has_id_query = any(k in QUERY_ID_PARAMS for k in query_keys)
        if not segments and not has_id_query:
            return {
                "precise": False,
                "kind": "domain_root",
                "note": "Domain root cannot be used as citation target (R2: deep content link required)",
            }
        flat_article = bool(segments) and bool(FLAT_ARTICLE_RE.search(segments[-1]))
        if len(segments) <= 1 and not has_id_query and not flat_article:
            slug = segments[0].lower() if segments else ""
            # Single-segment product allowlist condition
            if slug in SINGLE_SEGMENT_PRODUCT_SLUGS and (role in ("supplementary", "product")):
                return {
                    "precise": True,
                    "kind": "vendor_product_page",
                    "conditional_check_required": True,
                    "note": "Single-segment vendor product page (requires post-fetch version/anchor check)",
                }
            return {
                "precise": False,
                "kind": "section_front",
                "note": "Section/rubric front cannot be used as citation target (R2: article page required)",
            }
        return {"precise": True, "kind": "deep_page", "note": None}

    # ------------------------------------------------------------------ R3
    def extract_distinctive_numbers(self, text: str) -> List[str]:
        clean = strip_non_content_tags(text)
        found: Set[str] = set()
        masked = clean

        # 1. Percentages: %80, yüzde 80, 80 percent, 80%
        for rx in PERCENT_RE_LIST:
            for m in rx.finditer(masked):
                norm = normalize_number(m.group(1))
                if norm:
                    found.add(f"{norm}%")
            masked = rx.sub(" ", masked)

        # Word-based percentages: yüzde seksen -> 80%
        for m in re.finditer(r"yüzde\s+([a-zçğıöşü\s]+)", masked, re.I):
            words = m.group(1).lower().split()
            if words and words[0] in TR_NUMBER_WORDS:
                val = TR_NUMBER_WORDS[words[0]]
                found.add(f"{val}%")
        masked = re.sub(r"yüzde\s+([a-zçğıöşü\s]+)", " ", masked, flags=re.I)

        # 2. Scaled numbers: 80 bin -> 80000; 1.5 milyon -> 1500000
        for m in re.finditer(r"([\d][\d.,]*)\s*(bin|milyon|milyar|trilyon|million|billion|trillion)\b", masked, re.I):
            num_str = normalize_number(m.group(1))
            unit = m.group(2).lower()
            scale = TR_SCALES.get(unit, 1)
            try:
                val = int(float(num_str) * scale)
                found.add(str(val))
            except ValueError:
                pass
        masked = re.sub(r"([\d][\d.,]*)\s*(bin|milyon|milyar|trilyon|million|billion|trillion)\b", " ", masked, flags=re.I)

        # 3. Generic numbers >= 2 digits
        for m in GENERIC_NUM_RE.finditer(masked):
            raw = m.group(0)
            digits = normalize_number(raw).rstrip(".")
            if len(digits.replace(".", "")) >= 2:
                found.add(digits)

        return sorted(found)

    def extract_entities(self, text: str) -> List[str]:
        clean = strip_tags(text)
        entities: List[str] = []
        for tok in ENTITY_RE.findall(clean):
            if tok in ENTITY_STOPWORDS or tok.lower() in ("http", "https", "www"):
                continue
            base = tok.split("'")[0].split("’")[0]
            base = stem_institutional(base)
            if len(base) >= 3 and base not in entities:
                entities.append(base)
        return entities

    def anchoring(self, citing_text: str, target_html: Optional[str],
                  source_info: Optional[Dict[str, Any]] = None) -> Dict[str, Any]:
        if target_html is None:
            return {"status": "unknown", "note": "Target page could not be fetched (R1)"}

        target_clean = strip_non_content_tags(target_html)
        nums = self.extract_distinctive_numbers(citing_text)

        # 1. Exact Normalized Numeric Matching (Removing substring matching n in t)
        if nums:
            target_nums_set = set(self.extract_distinctive_numbers(target_clean))
            missing = [n for n in nums if n not in target_nums_set]
            if missing:
                return {
                    "status": "block",
                    "missing_numbers": missing,
                    "note": f"R3 anchoring error: figures in citing paragraph missing on target page: "
                            f"{', '.join(missing)} - manual review: correct link, update figure, or remove claim",
                }
            return {"status": "pass", "note": f"Figure match {len(nums)}/{len(nums)}"}

        # 2. Two-Sided Cross-Language Anchoring
        source_anchors = (source_info or {}).get("source_anchors") or []
        source_lang = (source_info or {}).get("source_lang", "tr")

        if source_anchors:
            target_folded = tr_fold(target_clean)
            citing_folded = tr_fold(citing_text)
            verified_pairs = []
            missing_on_source = []

            for pair in source_anchors:
                s_term = pair.get("source_term", "").strip()
                tr_term = pair.get("tr_term", "").strip()
                s_term_folded = tr_fold(s_term)
                tr_term_folded = tr_fold(stem_institutional(tr_term))

                in_source = bool(re.search(r"\b" + re.escape(s_term_folded) + r"\b", target_folded, re.I))
                in_draft = bool(re.search(r"\b" + re.escape(tr_term_folded) + r"\b", citing_folded, re.I))

                if in_source and in_draft:
                    verified_pairs.append((s_term, tr_term))
                elif in_draft and not in_source:
                    missing_on_source.append(s_term)

            if missing_on_source:
                return {
                    "status": "block",
                    "note": f"R3 cross-language anchor error: source term(s) {missing_on_source} not found on target page",
                }
            if verified_pairs:
                return {
                    "status": "pass",
                    "note": f"Cross-language anchor match: {len(verified_pairs)} pair(s) verified ({', '.join(p[1] for p in verified_pairs)})",
                }
            if source_lang != "tr":
                return {
                    "status": "block",
                    "note": f"R3 cross-language anchoring error: none of the declared source_anchors ({[p['tr_term'] for p in source_anchors]}) found in citing paragraph",
                }

        # 3. Standard Entity Matching with Turkish Morphology
        ents = self.extract_entities(citing_text)
        target_folded = tr_fold(target_clean)
        matched = []
        for e in ents:
            e_folded = tr_fold(e)
            if re.search(r"\b" + re.escape(e_folded) + r"\b", target_folded):
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

    def _extract_blocks(self, text: str) -> List[Tuple[int, str]]:
        """Extract all <p>, <blockquote>, and <li> content blocks with 1-based index."""
        matches = list(re.finditer(r"<(p|blockquote|li)\b[^>]*>(.*?)</\1>", text, re.S | re.I))
        if matches:
            return [(i, m.group(0)) for i, m in enumerate(matches, start=1)]
        parts = [p.strip() for p in re.split(r"\n\s*\n", text) if p.strip()]
        if parts:
            return [(i, p) for i, p in enumerate(parts, start=1)]
        return [(1, text)]

    def _paragraphs(self, text: str) -> List[str]:
        blocks = self._extract_blocks(text)
        return [b[1] for b in blocks]

    def _find_all_citing_blocks(self, url: str, text: str) -> List[Tuple[int, str]]:
        """Find all blocks citing the given url, returning (block_index, block_html)."""
        norm = self.norm_url(url)
        blocks = self._extract_blocks(text)
        citing = []
        for idx, block in blocks:
            block_hrefs = [self.norm_url(m.group(1).rstrip(".,;:)")) for m in HREF_RE.finditer(block)]
            if norm in block_hrefs or url in block:
                citing.append((idx, block))
        if not citing:
            idx = text.find(url)
            if idx >= 0:
                citing.append((1, text[max(0, idx - 400): idx + 400]))
        return citing

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
        for m in OUTLET_ATTRIBUTION_RE.finditer(clean):
            start = max(0, m.start() - 40)
            out.append(clean[start:m.end()].strip())
        return out

    # ------------------------------------------------------------------ core audits
    def audit_url(self, url: str, citing_text: Optional[str], role: Optional[str],
                  skip_network: bool = False,
                  source_info: Optional[Dict[str, Any]] = None) -> Dict[str, Any]:
        entry: Dict[str, Any] = {
            "url": url,
            "role": role or "-",
            "liveness": None,
            "precision": None,
            "anchoring": None,
        }
        precision = self.classify_precision(url, role)
        entry["precision"] = precision
        if skip_network:
            entry["liveness"] = {"live": None, "reachable": None, "note": "Network check skipped (--no-network)"}
            entry["reachable"] = None
            entry["anchoring"] = {"status": "unknown", "note": "Network check skipped"}
            return entry

        try:
            page = self.fetch_page(url)
        except Exception as e:
            page = {
                "url": url,
                "status_code": 0,
                "final_url": url,
                "html": None,
                "live": False,
                "reachable": False,
                "unreachable_reason": f"Fetch error: {e}",
                "note": f"Fetch error: {e}",
            }

        entry["liveness"] = page
        entry["reachable"] = page.get("reachable", page.get("live", False))
        if not entry["reachable"]:
            entry["unreachable_reason"] = page.get("unreachable_reason") or page.get("note") or "Target unreachable"

        # Post-fetch validation for single-segment vendor pages (Feature 21)
        if page["live"] and precision.get("conditional_check_required"):
            html = page["html"] or ""
            article_count = len(re.findall(r"<article\b", html, re.I))
            has_version = bool(SEMANTIC_VERSION_RE.search(strip_non_content_tags(html)))
            has_anchor = False
            for pair in (source_info or {}).get("source_anchors", []):
                if tr_fold(pair.get("source_term", "")) in tr_fold(html):
                    has_anchor = True
                    break
            if article_count > 2 or (not has_version and not has_anchor):
                entry["precision"] = {
                    "precise": False,
                    "kind": "section_front",
                    "note": f"R2 vendor page check failed: <article> count={article_count} (max 2) and lacks version/source anchor",
                }

        if page["live"]:
            if citing_text and citing_text.strip():
                entry["anchoring"] = self.anchoring(citing_text, page["html"], source_info)
            else:
                entry["anchoring"] = {"status": "pass", "note": "No claims or citing text provided to anchor (liveness verified)"}
        else:
            entry["anchoring"] = {"status": "unknown", "note": page.get("note") or "Target unreachable"}
        return entry

    def audit_draft(self, text: str, dossier: Optional[Dict[str, Any]] = None,
                    skip_network: bool = False, explain: bool = False) -> Dict[str, Any]:
        sources_map: Dict[str, Dict[str, Any]] = {}
        if dossier:
            for src in dossier.get("sources", []):
                norm_u = self.norm_url(src.get("url", ""))
                sources_map[norm_u] = {
                    "role": src.get("role", "-"),
                    "source_lang": src.get("source_lang", "tr"),
                    "source_anchors": src.get("source_anchors", []),
                }

        hrefs: List[str] = []
        for m in HREF_RE.finditer(text):
            u = m.group(1).rstrip(".,;:)")
            if u not in hrefs:
                hrefs.append(u)
        raw_urls = [u.rstrip(".,;:)'") for u in RAW_URL_RE.findall(strip_tags(text))]
        bare_urls = [u for u in raw_urls if u not in hrefs]

        # Evaluate EVERY citing block across the entire draft (Feature 19)
        url_audit: List[Dict[str, Any]] = []
        for u in hrefs:
            src_info = sources_map.get(self.norm_url(u))
            blocks = self._find_all_citing_blocks(u, text)
            if not blocks:
                url_audit.append(self.audit_url(u, None, (src_info or {}).get("role"), skip_network, src_info))
            else:
                for b_idx, block in blocks:
                    entry = self.audit_url(u, block, (src_info or {}).get("role"), skip_network, src_info)
                    entry["paragraph_index"] = b_idx
                    url_audit.append(entry)

        for u in bare_urls:
            if u.startswith(("http", "https")):
                src_info = sources_map.get(self.norm_url(u))
                entry = self.audit_url(u, None, (src_info or {}).get("role"), skip_network, src_info)
                entry["bare_url"] = True
                url_audit.append(entry)

        # R4: Expanded Attribution Linkage (Feature 23)
        linkage: List[Dict[str, Any]] = []
        blocks = self._extract_blocks(text)
        prev_had_link = False
        for b_idx, block in blocks:
            clean_b = strip_tags(block).strip()
            has_link = bool(HREF_RE.search(block))

            outlet_m = OUTLET_ATTRIBUTION_RE.search(clean_b)
            if outlet_m and not has_link:
                linkage.append({
                    "issue": "Attribution phrase lacks hyperlink (R4)",
                    "paragraph_index": b_idx,
                    "phrase": outlet_m.group(0),
                    "paragraph": clean_b[:120],
                })

            speech_m = SPEECH_ATTRIBUTION_RE.search(clean_b)
            if speech_m and not has_link and not prev_had_link:
                linkage.append({
                    "issue": "Direct-speech attribution lacks hyperlink (R4)",
                    "paragraph_index": b_idx,
                    "phrase": speech_m.group(0),
                    "paragraph": clean_b[:120],
                })

            prev_had_link = has_link

        # R4: dossier containment
        containment: List[Dict[str, Any]] = []
        if dossier is not None:
            known = set(sources_map.keys())
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

        # Component G: Detailed paragraph-by-paragraph diagnostic analysis (Full Parity)
        diagnostics: List[Dict[str, Any]] = []
        if explain:
            known_dossier_urls = set(sources_map.keys()) if dossier else None
            for p_idx, block in blocks:
                clean_p = strip_tags(block).strip()
                p_hrefs = [m.group(1).rstrip(".,;:)") for m in HREF_RE.finditer(block)]
                snippet = (clean_p[:60] + "...") if len(clean_p) > 60 else clean_p

                # 1. R4 unlinked attribution
                cit_m = OUTLET_ATTRIBUTION_RE.search(clean_p)
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

                    aud_match = next((a for a in url_audit if a.get("url") == u and a.get("paragraph_index") == p_idx), None)
                    if aud_match:
                        if not aud_match["precision"]["precise"]:
                            diagnostics.append({
                                "paragraph_index": p_idx,
                                "snippet": snippet,
                                "code": "R2_PRECISION_FAIL",
                                "severity": "ERROR",
                                "url": u,
                                "detail": aud_match["precision"]["note"] or f"Imprecise link ({aud_match['precision']['kind']})",
                                "remediation": "Replace with a deep article permalink.",
                            })
                        if not skip_network and not aud_match["liveness"]["live"]:
                            diagnostics.append({
                                "paragraph_index": p_idx,
                                "snippet": snippet,
                                "code": "R1_LIVENESS_FAIL",
                                "severity": "ERROR",
                                "url": u,
                                "detail": f"Liveness failed: HTTP {aud_match['liveness'].get('status_code')} ({aud_match['liveness'].get('note')})",
                                "remediation": "Ensure target URL is reachable and does not return 4xx/5xx or soft-404.",
                            })
                        anch = aud_match.get("anchoring") or {}
                        if anch.get("status") == "block":
                            missing_nums = anch.get("missing_numbers", [])
                            missing_ents = anch.get("missing_entities", [])
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
                            else:
                                diagnostics.append({
                                    "paragraph_index": p_idx,
                                    "snippet": snippet,
                                    "code": "R3_ANCHOR_FAIL",
                                    "severity": "ERROR",
                                    "url": u,
                                    "detail": anch.get("note", "Anchoring failed"),
                                    "remediation": "Verify anchor terms on target page or update citing paragraph.",
                                })

        report = {
            "passed": passed,
            "status": "REVIEW" if status == "REVIEW" else ("PASSED" if passed else "BLOCKED"),
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
            url_audit.append(self.audit_url(url, claims, src.get("role"), skip_network, src))

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
            "status": "REVIEW" if status == "REVIEW" else ("PASSED" if passed else "BLOCKED"),
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
            pass
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
        if role not in ("primary", "supplementary", "historical", "product"):
            return False, f"sources[{i}].role must be one of 'primary', 'supplementary', 'historical', 'product'"
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
        p_info = f" [P{e['paragraph_index']}]" if e.get("paragraph_index") else ""
        print(f"  {mark} [{e.get('role', '-')}] {e['url']}{p_info}")
        print(f"      {' | '.join(parts)}")
        for section in ("liveness", "anchoring"):
            note = (e.get(section) or {}).get("note")
            if note:
                print(f"      note: {note}")
        if prec.get("note"):
            print(f"      note: {prec['note']}")

    for extra in ("citation_linkage", "dossier_containment"):
        for item in report.get(extra, []):
            p_prefix = f"[P{item['paragraph_index']}] " if item.get("paragraph_index") else ""
            print(f"  [!] {p_prefix}{item['issue']}: {item.get('url') or item.get('paragraph', '')}")

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
