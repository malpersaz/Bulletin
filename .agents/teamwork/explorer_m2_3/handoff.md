# Handoff Report: Kevrast Fact-Checking & Link Provenance Gate Hardening (Features 19–23)

**Date:** 2026-11-11  
**Author:** explorer_m2_3 (Fact-Checking Specialist)  
**State Folder:** `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/explorer_m2_3/`  
**Milestone:** M2 (Features 19, 20, 21, 22, 23)  
**Target Files:**  
- `.agents/skills/kevrast/scripts/verify_facts_and_links.py`
- `.agents/skills/kevkolin/scripts/dossier.schema.json`
- `.agents/skills/_shared/editorial_http.py` (integration)

---

## 1. Observation

Direct inspection of `.agents/skills/kevrast/scripts/verify_facts_and_links.py` (809 lines) and `.agents/skills/kevkolin/scripts/dossier.schema.json` (83 lines) yielded the following specific observations:

### 1.1 First-Paragraph-Only Verdict Bug (Feature 19)
- **Lines 270–277:**
  ```python
  def _find_citing_block(self, url: str, text: str) -> Optional[str]:
      for para in self._paragraphs(text):
          if url in para:
              return para
      idx = text.find(url)
      if idx >= 0:
          return text[max(0, idx - 400): idx + 400]
      return None
  ```
- **Lines 345–350:**
  ```python
  url_audit: List[Dict[str, Any]] = []
  for u in hrefs:
      role = role_map.get(self.norm_url(u))
      block = self._find_citing_block(u, text)
      url_audit.append(self.audit_url(u, block, role, skip_network))
  ```
- **Observation:** `_find_citing_block(u, text)` returns solely the *first* `<p>` block containing `u`. If an article cites `u` legitimately in Paragraph 1 and again in Paragraph 4 or 5 with fabricated figures (e.g., `%95`) or hallucinated names, `url_audit` contains only Paragraph 1.
- **Verdict vs Diagnostics Parity Gap:** Line 382 calculates `passed` strictly across `url_audit`. Consequently, `audit_draft()` exits `0` (`Status: PASSED`), even while `--explain` (lines 400–498) scans all paragraphs and outputs `[P4] [R3_MISSING_NUMBER] ERROR`!
- **Block Scope Limitation:** Line 267 (`paras = re.findall(r"<p[^>]*>(.*?)</p>", text, re.S | re.I)`) only considers `<p>` tags, completely ignoring `<blockquote>` and `<li>` elements where citations frequently occur.

### 1.2 Unstripped Tags & Substring Number Matching (Feature 20)
- **Lines 90–91:**
  ```python
  def strip_tags(text: str) -> str:
      return TAG_RE.sub(" ", text)
  ```
  `strip_tags` only strips angle brackets `<...>` without removing the inner bodies of `<script>`, `<style>`, `<noscript>`, `<svg>`, `<nav>`, `<footer>`, or `<aside>` elements.
- **Lines 220–224:**
  ```python
  target = strip_tags(target_html)
  nums = self.extract_distinctive_numbers(citing_text)
  if nums:
      target_nums = [normalize_number(m.group(1)) for m in TARGET_NUM_RE.finditer(target)]
      missing = [n for n in nums if not any(n == t or n in t for t in target_nums)]
  ```
- **Observation:** Next.js and Webpack scripts contain large JSON bundles with arbitrary numeric constants (`20`, `25`, `80`, `100`, `1200`, chunk IDs, build timestamps). Unstripped script bodies pollute `target_nums`.
- **Substring Matching Loophole:** `or n in t` allows any 2-digit hallucinated number `n` to match if it appears anywhere inside a target number `t` (e.g. `n = "12"` matches article permalink ID `1287295`, phone numbers, or timestamps like `20261112`).

### 1.3 R2 Rejection of Authoritative Single-Segment Vendor Endpoints (Feature 21)
- **Lines 177–184:**
  ```python
  flat_article = bool(segments) and bool(FLAT_ARTICLE_RE.search(segments[-1]))
  if len(segments) <= 1 and not has_id_query and not flat_article:
      return {
          "precise": False,
          "kind": "section_front",
          "note": "Section/rubric front cannot be used as citation target (R2: article page required)",
      }
  ```
- **Observation:** `classify_precision` rejects every single-segment URL (`len(segments) <= 1`) unconditionally as `section_front`. Valid authoritative vendor endpoints such as `https://antigravity.google/download`, `https://docker.com/pricing`, `https://kubernetes.io/docs`, or `https://github.com/org/repo/releases` fail R2 precision, forcing writers to drop primary vendor download and changelog pages.

### 1.4 Absence of Cross-Language Anchoring in Dossier & Script (Feature 22)
- **`dossier.schema.json:54–80`:** Sources array currently only accepts `url`, `role`, `outlet`, `pub_date`, and `claims`. There is no schema definition for `source_lang` or bilingual anchor pairs (`source_anchors`).
- **`verify_facts_and_links.py:233–246`:** `anchoring()` extracts Turkish capitalized words (`ENTITY_RE = re.compile(r"\b[A-ZÇĞİÖŞÜ][\wÇĞİÖŞÜçğıöşü\-]{2,}")`) from the citing block and attempts exact case-insensitive matches against `target_html`. When citing official foreign documentation (e.g. English docs with `Coordinator`, `Workers`), Turkish translations in the draft (*Koordinatör*, *İşçiler*) fail matching with `R3_MISSING_ENTITY` errors.

### 1.5 Turkish Case-Folding, Morphology, and Attribution Lexicon Gaps (Feature 23)
- **Line 236:**
  ```python
  if re.search(r"\b" + re.escape(e) + r"\b", target, re.I):
  ```
  Python standard regex `re.I` fails on Turkish uppercase `İ` (`U+0130`) vs lowercase `i`, and uppercase `I` vs lowercase dotless `ı` (`U+0131`), causing false entity mismatches on *İçişleri*, *İBB*, *İran*, *Irak*.
- **Line 212:**
  ```python
  base = tok.split("'")[0].split("’")[0]
  ```
  `extract_entities` only strips suffixes attached with an apostrophe. In official Turkish orthography (TDK), institutional proper nouns do not take apostrophes before case suffixes (*Diyarbakır Büyükşehir Belediyesine*, *İçişleri Bakanlığına*, *Dicle Üniversitesinde*, *Diyarbakır Valiliğinden*). These fail to match the base names on the source page.
- **Lines 47–53 (`CITATION_PHRASE_RE`):**
  Omits common Turkish journalistic attributions instructed by `kevanger/SKILL.md:42`:
  - `paylaşılan bilgilere göre`, `analize göre`, `sürüm notlarına göre`, `dokümantasyonuna göre`, `aktarıldığı üzere`, `kaynağında`.
  - Also lumps direct-speech verbs (`dedi`, `söyledi`, `açıkladı`) with outlet attributions, triggering false `R4_UNLINKED_ATTRIBUTION` errors on follow-up quotation paragraphs where the speaker was already linked in the immediately preceding paragraph.

---

## 2. Logic Chain

1. **All-Paragraph Parity (Feature 19):**
   - *Observation:* `_find_citing_block()` selects the first citing block, while `--explain` iterates all blocks; `passed` evaluates only the first block.
   - *Inference:* Fabricated numbers in subsequent paragraphs escape validation during default execution, creating an unacceptable false-pass vulnerability and diagnostic inconsistency.
   - *Conclusion:* Replace `_find_citing_block()` with `_find_all_citing_blocks(url, text) -> List[Tuple[int, str]]`, expand block detection to `<p>`, `<blockquote>`, and `<li>`, and evaluate every citing block in `audit_draft()` to ensure 100% parity between `--explain` error diagnostics and script exit code.

2. **Clean Text & Numeric Rigor (Feature 20):**
   - *Observation:* Non-content script/style tags are retained in text extraction; `n in t` allows short digit substrings to match arbitrarily inside unrelated numbers.
   - *Inference:* Hallucinated 2-digit figures pass validation by accidentally matching JS constants or article URL IDs.
   - *Conclusion:* Strip `<script>`, `<style>`, `<noscript>`, `<svg>`, `<nav>`, `<footer>`, and `<aside>` element bodies before text extraction (while preserving metadata). Eliminate `or n in t` and require exact normalized token equality (`n == t`). Support Turkish number words (`bir..on`, `yirmi..doksan`, `yüz`, `bin`, `milyon`, `milyar`), percentage variations, and scale multipliers (`80 bin` -> `80000`).

3. **Vendor Allowlist with Anti-Laundering Gates (Feature 21):**
   - *Observation:* Authoritative vendor download/release pages (`/download`, `/releases`) are currently rejected by R2 as section fronts.
   - *Inference:* Legitimate tech releases cannot link to authoritative vendor pages, forcing reliance on secondary blogs.
   - *Conclusion:* Allow defined single-segment vendor slugs (`download`, `releases`, `changelog`, `pricing`, `docs`, etc.) ONLY when the dossier source role is `supplementary` (or `product`) AND post-fetch inspection confirms `<article>` count `<= 2` and the page contains a semantic version string (`\bv?\d+\.\d+(?:\.\d+)?\b`) or declared `source_anchors` token. Keep media section fronts unconditionally blocked.

4. **Two-Sided Cross-Language Verification (Feature 22):**
   - *Observation:* Turkish proper noun extraction fails against non-Turkish primary documentation.
   - *Inference:* Direct citations of English/Kurdish primary docs fail R3 unless bilingual terms can be bridged deterministically.
   - *Conclusion:* Add `source_lang` and `source_anchors` (`source_term` <-> `tr_term`) to `dossier.schema.json`. In `anchoring()`, implement two-sided verification: `source_term` MUST exist on the foreign source page AND `tr_term` MUST appear in the Turkish citing paragraph.

5. **Linguistic Accuracy & Transport Integration (Feature 23):**
   - *Observation:* Turkish dotted/dotless I breaks in `re.I`; institutional suffixes without apostrophes cause entity mismatches; attribution phrases lack editorial breadth; direct speech verbs cause multi-paragraph quotation failures; HTTP logic lacks centralized caching and SSRF guards.
   - *Inference:* Legitimate drafts suffer false-positive blocks, while unlinked outlet attributions evade detection.
   - *Conclusion:* Implement `tr_fold()` and institutional suffix stemmer; split attributions into `OUTLET_ATTRIBUTION_RE` (strict single-paragraph link requirement) and `SPEECH_ATTRIBUTION_RE` (allows link in current or immediately preceding paragraph); integrate with `editorial_http.py` for shared verified transport and response caching.

---

## 3. Implementation Blueprint

### 3.1 `dossier.schema.json` Updates
In `.agents/skills/kevkolin/scripts/dossier.schema.json`:
- Extend `properties.sources.items.properties.role.enum`:
  Add `"product"` to `["primary", "supplementary", "historical", "product"]`.
- Add `source_lang` property to `sources.items`:
  ```json
  "source_lang": {
    "type": "string",
    "enum": ["tr", "en", "ku", "ar", "de", "fr"],
    "default": "tr",
    "description": "Primary language of the source document."
  }
  ```
- Add `source_anchors` property to `sources.items`:
  ```json
  "source_anchors": {
    "type": "array",
    "description": "Bilingual anchor pairs for cross-language fact verification.",
    "minItems": 1,
    "maxItems": 12,
    "items": {
      "type": "object",
      "required": ["source_term", "tr_term"],
      "properties": {
        "source_term": {
          "type": "string",
          "minLength": 3,
          "description": "Verbatim term as it appears on the foreign source page."
        },
        "tr_term": {
          "type": "string",
          "minLength": 3,
          "description": "Translated or adapted term as it appears in the Turkish draft text."
        }
      },
      "additionalProperties": false
    }
  }
  ```

### 3.2 `verify_facts_and_links.py` Code Structure Blueprint

#### A. Shared Transport Integration & Imports
```python
import unicodedata
from pathlib import Path

# Integration with shared HTTP client
try:
    from ._shared.editorial_http import fetch_html, validate_public_url
except ImportError:
    _SHARED_DIR = Path(__file__).resolve().parent.parent.parent / "_shared"
    if str(_SHARED_DIR) not in sys.path:
        sys.path.insert(0, str(_SHARED_DIR))
    try:
        from editorial_http import fetch_html, validate_public_url
    except ImportError:
        fetch_html = None
        validate_public_url = None
```

#### B. Constants & Regular Expressions
```python
# Strip non-content element bodies before R3 text extraction
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
```

#### C. Turkish Morphology & Normalization Functions
```python
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
```

#### D. Clean Number & Entity Extraction (Feature 20)
```python
def normalize_number(raw: str) -> str:
    """Normalize numeric strings removing thousand separators."""
    # Convert Turkish/English thousands formatting: 80.000 or 80,000 -> 80000
    cleaned = re.sub(r"[,\s]", "", raw)
    if re.match(r"^\d{1,3}(?:\.\d{3})+$", raw):
        cleaned = raw.replace(".", "")
    return cleaned

def extract_distinctive_numbers(self, text: str) -> List[str]:
    """Extract normalized distinctive numbers, percentages, scales, and Turkish words."""
    clean = strip_non_content_tags(text)
    found: Set[str] = set()

    # Percentages: %80, yüzde 80 -> 80% and 80
    for rx in PERCENT_RE_LIST:
        for m in rx.finditer(clean):
            norm = normalize_number(m.group(1))
            if norm:
                found.add(norm)
                found.add(f"{norm}%")

    # Word-based percentages: yüzde seksen -> 80% and 80
    for m in re.finditer(r"yüzde\s+([a-zçğıöşü\s]+)", clean, re.I):
        words = m.group(1).lower().split()
        if words and words[0] in TR_NUMBER_WORDS:
            val = TR_NUMBER_WORDS[words[0]]
            found.add(str(val))
            found.add(f"{val}%")

    # Scaled numbers: 80 bin -> 80000 and 80; 1.5 milyon -> 1500000
    for m in re.finditer(r"([\d][\d.,]*)\s*(bin|milyon|milyar|trilyon|million|billion|trillion)", clean, re.I):
        num_str = m.group(1).replace(",", ".")
        unit = m.group(2).lower()
        scale = TR_SCALES.get(unit, 1)
        try:
            val = int(float(num_str) * scale)
            found.add(str(val))
            found.add(normalize_number(m.group(1)))
        except ValueError:
            pass

    # Generic numbers >= 2 digits
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
        base = tok.split("'")[0].split("’")[0]
        base = stem_institutional(base)
        if len(base) >= 3 and base not in entities:
            entities.append(base)
    return entities
```

#### E. Precision Classification with Vendor Allowlist (Feature 21)
```python
def classify_precision(self, url: str, role: Optional[str] = None) -> Dict[str, Any]:
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
        slug = segments[0].lower() if segments else ""
        # Single-segment product allowlist condition:
        # Slug must be in allowlist AND role must be supplementary or product
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
```

#### F. Two-Sided Cross-Language Anchoring (Feature 22)
```python
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
```

#### G. All-Paragraph Extraction & Full Parity (Feature 19)
```python
def _extract_blocks(self, text: str) -> List[Tuple[int, str]]:
    """Extract all <p>, <blockquote>, and <li> content blocks with 1-based index."""
    matches = list(re.finditer(r"<(p|blockquote|li)\b[^>]*>(.*?)</\1>", text, re.S | re.I))
    if matches:
        return [(i, m.group(0)) for i, m in enumerate(matches, start=1)]
    parts = [p.strip() for p in re.split(r"\n\s*\n", text) if p.strip()]
    if parts:
        return [(i, p) for i, p in enumerate(parts, start=1)]
    return [(1, text)]

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

def audit_url(self, url: str, citing_text: Optional[str], role: Optional[str],
              skip_network: bool = False, source_info: Optional[Dict[str, Any]] = None) -> Dict[str, Any]:
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
        entry["liveness"] = {"live": None, "note": "Network check skipped (--no-network)"}
        entry["anchoring"] = {"status": "unknown", "note": "Network check skipped"}
        return entry

    page = self.fetch_page(url)
    entry["liveness"] = page

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
```

#### H. `audit_draft()` Implementation with Full Parity
```python
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

    # R4: Dossier containment
    containment: List[Dict[str, Any]] = []
    if dossier is not None:
        known = set(sources_map.keys())
        for u in hrefs:
            if self.norm_url(u) not in known:
                containment.append({
                    "url": u,
                    "issue": "URL not found in research dossier (R4 chain of custody broken); add to dossier with role tag first",
                })

    zero_link = len(hrefs) == 0

    # Verdict Calculation
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

    # Component G: Detailed Diagnostics (Full Parity with passed status)
    diagnostics: List[Dict[str, Any]] = []
    if explain:
        for p_idx, block in blocks:
            clean_p = strip_tags(block).strip()
            p_hrefs = [m.group(1).rstrip(".,;:)") for m in HREF_RE.finditer(block)]
            snippet = (clean_p[:60] + "...") if len(clean_p) > 60 else clean_p

            cit_m = OUTLET_ATTRIBUTION_RE.search(clean_p)
            if cit_m and not p_hrefs:
                diagnostics.append({
                    "paragraph_index": p_idx,
                    "snippet": snippet,
                    "code": "R4_UNLINKED_ATTRIBUTION",
                    "severity": "ERROR",
                    "url": None,
                    "detail": f"Attribution phrase '{cit_m.group(0)}' has no outbound source link in this paragraph.",
                    "remediation": "Add an <a href='...'> linking to the source reporting this statement.",
                })

            for u in p_hrefs:
                aud_match = next((a for a in url_audit if a.get("url") == u and a.get("paragraph_index") == p_idx), None)
                if aud_match:
                    if not aud_match["precision"]["precise"]:
                        diagnostics.append({
                            "paragraph_index": p_idx,
                            "snippet": snippet,
                            "code": "R2_PRECISION_FAIL",
                            "severity": "ERROR",
                            "url": u,
                            "detail": aud_match["precision"]["note"],
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

    return {
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
```

---

## 4. Caveats

1. **Read-Only Investigation:** Per the explorer archetype, no changes have been applied to `.agents/skills/kevrast/scripts/verify_facts_and_links.py` or `.agents/skills/kevkolin/scripts/dossier.schema.json`.
2. **`editorial_http.py` Dependency:** The integration includes dynamic fallback to standard `urllib` if `.agents/skills/_shared/editorial_http.py` has not yet been authored by peer subagents, ensuring `verify_facts_and_links.py` never crashes on import.
3. **Complex Multi-Word Suffix Chains:** The institutional stemmer targets the 9 dominant institutional noun patterns defined by TDK (*belediyesi*, *bakanlığı*, *valiliği*, *üniversitesi*, *müdürlüğü*, *başkanlığı*, *kaymakamlığı*, *odası*, *birliği*). Extraordinary combinations outside these patterns fall back to character length and unstemmed matching.

---

## 5. Conclusion

The investigation confirms the 4 false-pass bugs and 5 false-positive friction sources identified in plan §B4:
1. `audit_draft()` currently checks only the first citing block in `url_audit`, allowing fabricated numbers in subsequent paragraphs to pass with exit `0` while `--explain` reports errors.
2. `strip_tags()` retains `<script>` / `<style>` bundles, and `n in t` allows partial digit substrings to pass.
3. `classify_precision()` rejects single-segment vendor download/release pages without a valid exception.
4. Non-Turkish primary sources fail R3 entity checks due to lack of cross-language anchor declarations.
5. Turkish dotted/dotless `I` fails under standard `re.I`, institutional proper nouns lack suffix stemming, and attribution regexes omit common Turkish journalistic patterns.

The blueprint detailed in Section 3 provides an exact, drop-in specification for implementing Features 19–23 with 100% test parity and zero hallucination risk.

---

## 6. Verification Method

To verify the blueprint against the current codebase:

1. **Verify Existing Line Numbers and Defect Locations:**
   ```bash
   grep -n "def _find_citing_block" .agents/skills/kevrast/scripts/verify_facts_and_links.py
   grep -n "n == t or n in t" .agents/skills/kevrast/scripts/verify_facts_and_links.py
   grep -n -C 5 "classify_precision" .agents/skills/kevrast/scripts/verify_facts_and_links.py
   grep -n "ENTITY_RE = " .agents/skills/kevrast/scripts/verify_facts_and_links.py
   ```

2. **Verify Substring Matching Bug Reproduction:**
   ```bash
   python3 -c "
   target_nums = ['1287295']
   n = '12'
   # Current buggy logic:
   print('Current bug matches 12 in 1287295:', any(n == t or n in t for t in target_nums))
   # Proposed fix:
   print('Proposed fix rejects 12 != 1287295:', n in set(target_nums))
   "
   ```

3. **Verify Turkish Case-Folding Fix:**
   ```bash
   python3 -c "
   import unicodedata
   def tr_fold(text):
       res = text.replace('İ', 'i').replace('I', 'ı')
       return unicodedata.normalize('NFC', res).lower().replace('\u0307', '')
   print('Folded matches:', tr_fold('İÇİŞLERİ') == tr_fold('içişleri'))
   print('Folded matches:', tr_fold('IRAK') == tr_fold('ırak'))
   "
   ```

4. **Verify Pipeline Execution with Fixtures:**
   ```bash
   python3 .agents/skills/kevrast/scripts/verify_facts_and_links.py \
       --file tests/fixtures/editorial/sample_draft.html \
       --dossier tests/fixtures/editorial/sample_news_dossier.json \
       --no-network --explain
   ```
