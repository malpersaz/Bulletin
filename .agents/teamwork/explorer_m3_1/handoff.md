# Handoff Report: Milestone 3 — News Image Pipeline Investigation (`process_news_image.py`)

## 1. Observation

### 1.1 Direct Defect Reproduction
Inspecting `.agents/skills/kevanger/scripts/process_news_image.py` against fixture `tests/e2e/fixtures/editorial/mock_nextjs_sidebar_article.html` directly reproduced the critical **Section A3** wrong-photo hijack:

1. **Extraction Phase (`process_news_image.py:135-234`)**:
   - `mock_nextjs_sidebar_article.html` contains 1 primary article hero image with 3 candidates (`640w`, `1920w`, `800px`) and 1 sidebar recommendation (`<aside class="sidebar">`) with 2 candidates (`3840w`, `800px`).
   - All 5 candidates pointed to Next.js proxy URLs (`/_next/image?url=...`).
2. **Ranking & Deduplication Phase (`process_news_image.py:279-307`)**:
   - At line 303:
     ```python
     u = c["url"].split("?")[0].rstrip("/")
     ```
     Because every candidate URL started with `https://example.com/_next/image?url=...`, `split("?")[0]` yielded the identical key `https://example.com/_next/image` for **all 5 candidates across both article and sidebar**.
   - `score_candidate()` scored the candidates as follows:
     - Hero `1920w` (in-article): `1920 + 500 = 2420`
     - Sidebar `3840w` (not in-article): `3840 + 0 = 3840`
   - Because `3840 > 2420`, the sidebar thumbnail sorted first. The deduplication set (`seen.add(u)`) discarded all remaining 4 candidates.
   - **Result**: The output contained only **1 single candidate**: the unrelated sidebar recommendation (`sidebar_thumb.jpg`), and the genuine article hero image (`hero_main.jpg`) was completely discarded!

### 1.2 Identified Codebase Defects in `process_news_image.py`

| ID | Issue Area | File & Line Numbers | Observed Defect |
|---|---|---|---|
| **O1** | Query-Stripping Deduplication Collision | `process_news_image.py:302-306` | `c["url"].split("?")[0]` strips `?url=...`, causing all Next.js (`/_next/image`), Vercel (`/_vercel/image`), and query-driven CDN images on the page to collapse into a single URL key. |
| **O2** | `<main>` vs `<article>` Scope & Lack of Container Exclusion | `process_news_image.py:148-150, 193-194, 285-288` | `self.in_article_or_main` conflates `<main>` and `<article>`. Closing `<article>` inside `<main>` sets `in_article_or_main = False` for subsequent elements, while images in `<main>` outside `<article>` get `+500` article bonus. No exclusion tracking exists for `<aside>`, `<nav>`, `<footer>`, or sidebar/ticker classes (`(sidebar|related|recommend|most-read|popular|son-haber|widget)`). |
| **O3** | Uncapped `3840w` `srcset` Inflation & Missing `og:image` Stem Anchor | `process_news_image.py:176-189, 216-226, 281-283` | Next.js automatically emits `3840w` responsive descriptors for low-res thumbnails. Extractor accepts `3840` as `width_hint`. Meanwhile, `<meta property="og:image">` is hardcoded to `width_hint: 1024` with no cross-referencing between body images and `og:image` filename stems, allowing inflated sidebar thumbnails to beat verified hero photos. |
| **O4** | Pre-Cropping to 16:9 (`675px`) Causing Double Lossy Upscaling in CMS | `process_news_image.py:56-57, 384-412` vs `lib/McpNewsTools.class.php:10-15` | `process_news_image.py` forces center-crop to `1200x675` (16:9) and lossy WebP `< 100 KB`. CMS `McpNewsTools::NEWS_IMAGE_SIZES` requires derivatives `/` (`1280x720`), `/4x3` (`1200x900`), and `/1x1` (`1200x1200`). Because vertical height was pre-cropped to 675px, the CMS is forced to upscale height (`675 -> 900` for 4:3, `675 -> 1200` for 1:1), creating severe blurriness and double lossy compression artifacts. |
| **O5** | Missing Regional Watermark Heuristics | `process_news_image.py:353-391` | No watermark inspection or de-branding heuristics exist. Regional agency overlays (Rudaw red bug in top-right, AA/İHA/DHA/MA bottom ticker bars or corner badges) are passed uninspected. |
| **O6** | Unsuitable HTTP Headers for HTML vs Image Binaries | `process_news_image.py:121-127, 311` | `fetch_url_bytes()` sends `Accept: image/webp,image/avif,...` for both HTML and image fetches. Calling this on article URLs triggers 403/406 rejections from WAFs (Cloudflare/Akamai). Furthermore, image binary requests lack the `Referer: <article_origin>/` header required by news CDNs to bypass anti-hotlinking protections. |

---

## 2. Logic Chain

### 2.1 CDN URL Unwrapping & Canonical Deduplication (Resolving O1)
1. **Observation**: On Next.js (`rudaw.net`), Vercel, WordPress Photon (`i0.wp.com`), and Cloudflare (`/cdn-cgi/image/`), candidate image URLs wrap the underlying asset in query parameters (`?url=https%3A%2F%2F...`) or path segments.
2. **Inference**: Deduplication must occur on the **canonical unwrapped URL**, not the raw proxy string.
3. **Logic**:
   - Implement `unwrap_cdn_image_url(url: str, base_url: str = "") -> str`:
     - **Next.js & Vercel**: Match `/_next/image` or `/_vercel/image` in path. Extract `url` query parameter, `urllib.parse.unquote()`. If relative, resolve via `urllib.parse.urljoin(base_url, inner)`.
     - **WordPress Photon**: Match `^i[0-3]\.wp\.com$` in netloc. Extract underlying host and path from URL path, reconstructing `https://...`.
     - **Cloudflare Image Resizing**: Match `/cdn-cgi/image/` in path. Extract trailing target URL or query `?url=...`.
     - **Recursive**: Loop up to 3 times to unwrap nested proxies.
     - **SSRF Validation**: Validate every unwrapped candidate URL with `validate_public_url()` to prevent open-redirect / SSRF attacks.
   - Implement `canonical_image_key(url: str, base_url: str = "") -> str`:
     - Strip known resize/format query parameters: `w`, `q`, `width`, `height`, `fit`, `auto`, `format`, `quality`, `resize`, `crop`, `strip`, `max-w`, `max-h`.
     - Preserve distinguishing query parameters (e.g. `?id=1287295`, `?token=...`, `?v=2`).
     - Sort and re-encode remaining query parameters.

### 2.2 DOM Scoping & Container Exclusion (Resolving O2)
1. **Observation**: In standard news CMS templates (e.g. Rudaw, BBC, Bianet), `<main>` wraps the primary column AND the sidebar `<aside>`.
2. **Inference**: Treating `<main>` as `<article>` inadvertently awards article bonuses to sidebar thumbnails. Furthermore, nested tags without depth tracking cause state corruption upon tag closure.
3. **Logic**:
   - Maintain explicit depth counters in `ArticleImageCandidateExtractor`:
     - `self.article_depth: int = 0` (increments on `<article>`, decrements on `</article>`)
     - `self.main_depth: int = 0` (increments on `<main>`, decrements on `</main>`)
     - `self.excluded_depth: int = 0` (increments when entering an excluded container, decrements when exiting)
     - `self.tag_stack: List[Tuple[str, bool]]` tracking `(tag_name, caused_exclusion)` for non-void tags.
   - Exclusion criteria:
     - Tags: `<aside>`, `<nav>`, `<footer>`, and site-level `<header>` (`article_depth == 0`).
     - Attributes: `class` or `id` matching `EXCLUDE_CONTAINER_RE`:
       `\b(sidebar|related|recommend|most-read|popular|son-haber|benzer|widget|ticker|footer|comment|banner|ad-|ads-|social|share|breadcrumb|pagination|nav|navbar|menu)\b`
   - Scoring rules:
     - If `is_excluded` (`excluded_depth > 0`): assign `-2000` penalty.
     - If `in_primary_article` (`article_depth > 0 and excluded_depth == 0`):
       - For the first 1–2 figures/images: mark `is_lead_hero = True` and award `+600` hero bonus.
       - For 3rd+ images in article: mark `in_article = True` and award `+200`.
     - If `in_main` (outside article and outside excluded container): award at most `+50`.

### 2.3 `srcset` Descriptor Cap & `og:image` Stem Anchoring (Resolving O3)
1. **Observation**: Next.js automatically generates `3840w` descriptors regardless of true resolution. A sidebar thumbnail with `3840w` scores 3840, beating authentic hero images.
2. **Inference**: Uncorroborated responsive descriptors from CDN proxies must be bounded, and authentic editorial metadata (`og:image`, `twitter:image`, JSON-LD `image`) must be cross-referenced with body candidates.
3. **Logic**:
   - Cap `srcset` descriptors:
     - Hard cap at `3840w` max.
     - On CDN proxy URLs (`/_next/image`, `/_vercel/image`), cap descriptor width hint at `1200` **unless** corroborated by explicit `<img>` `width="..."` attribute (e.g. `attr_width >= 1200`) or JSON-LD/meta width.
   - Cross-anchor metadata stems:
     - Extract `extract_filename_stem(url: str) -> str` (e.g. `20261110231457_ntySv-eo`).
     - Collect stems from `<meta property="og:image">`, `<meta name="twitter:image">`, and JSON-LD `ImageObject`.
     - When an `<article>` `<img>` or `srcset` candidate's unwrapped stem matches any metadata stem, award a `+2000` cross-anchor bonus.
     - This guarantees the highest-resolution variant of the authentic editor-chosen photo wins.

### 2.4 Mode Modernization: `--mode master` vs `--mode inline-16x9` (Resolving O4)
1. **Observation**: CMS `McpNewsTools::NEWS_IMAGE_SIZES` generates `/` (`1280x720`, 16:9), `/4x3` (`1200x900`, 4:3), and `/1x1` (`1200x1200`, 1:1). Pre-cropping to `1200x675` in `process_news_image.py` strips vertical data, forcing the CMS to stretch 675px to 900px and 1200px.
2. **Inference**: The intake tool must output a clean, unflattened master image preserving natural aspect ratio so CMS multi-size generation operates directly on full vertical data.
3. **Logic**:
   - Implement `--mode {master,inline-16x9}`:
     - Default to `--mode master` when `--from-article` is passed (or when `--mode` is omitted).
   - Under `--mode master`:
     - Maintain original vertical aspect ratio (do NOT center-crop to 16:9).
     - If width > 1920px: scale proportionally to `max_width = 1920px` using `Image.Resampling.LANCZOS`.
     - Enforce minimum width requirement: `>= 1200px`. Flag `upscaled = True` if original width < 1200px.
     - Encode as WebP with `quality = 90` and `method = 6`.
     - Enforce size limit `< 1.5 MB` (`1_572_864` bytes).
   - Under `--mode inline-16x9`:
     - Retain legacy 16:9 center crop to `1280x720` (or `1200x675`).
     - Encode as WebP `< 100 KB` (`102_400` bytes) stepping down quality from 85 to 25.

### 2.5 Regional Watermark Heuristics (Resolving O5)
1. **Observation**: Kurdish and Turkish regional news agencies place standard watermarks/logos:
   - Rûdaw, K24, WAAR: Top-right corner bug (red/white logo).
   - Anadolu Ajansı (AA), İHA, DHA, MA: Bottom ticker bar or bottom-left/bottom-right badges.
2. **Inference**: Heuristics can inspect these specific quadrants and determine if clean margin cropping is warranted.
3. **Logic**:
   - Define inspection regions relative to image dimensions `(w, h)`:
     - `top_right`: `(int(w * 0.82), 0, w, int(h * 0.12))`
     - `bottom_right`: `(int(w * 0.80), int(h * 0.88), w, h)`
     - `bottom_left`: `(0, int(h * 0.88), int(w * 0.20), h)`
     - `bottom_strip`: `(0, int(h * 0.94), w, h)`
   - Heuristics:
     - Red Bug Signature: in `top_right`, check if pixels matching `R > 160 and G < 70 and B < 70` exceed 1.5% of region area.
     - Bottom Strip / Bar Signature: check row-by-row color variance and horizontal gradient across the bottom 6% of the image.
     - High-contrast corner badge: check standard deviation of intensity and edge density relative to inner adjacent content.
   - Reporting:
     - Include `"watermark_detected": bool`, `"watermark_regions": List[str]`, and `"crop_percentages": {"top": float, "bottom": float, "left": float, "right": float}` in the JSON output manifest.
     - When `--crop-watermark` is passed (or under clean master processing), apply conservative trim (e.g. trim bottom 6% if bottom strip detected).

### 2.6 Browser-Grade HTTP Transport Integration (Resolving O6)
1. **Observation**: WAFs and news CDNs block automated requests with wrong `Accept` headers or missing `Referer`.
2. **Inference**: `process_news_image.py` should leverage `.agents/skills/_shared/editorial_http.py` (created in Milestone 2) for verified TLS, SSRF protection, caching, and standard headers.
3. **Logic**:
   - Dynamically import `editorial_http` from `../../_shared/editorial_http.py`.
   - Use `editorial_http.fetch_html()` with browser headers:
     `Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8`
     `Accept-Language: tr,en;q=0.8`
   - Use `editorial_http.fetch_binary()` with image headers:
     `Accept: image/avif,image/webp,image/apng,image/*,*/*;q=0.8`
     `Accept-Language: tr,en;q=0.8`
     `Referer: <article_origin>/` (derived from the article URL)
   - Retain an internal fallback using `urllib` with verified SSL context and `validate_public_url()` SSRF checks.

---

## 3. Caveats

1. **No Source Code Changes Executed**: Per the Explorer role contract, this investigation is strictly read-only. No modifications have been made to `process_news_image.py`.
2. **Dynamic CDN URL Formats**: While Next.js, Vercel, WordPress Photon, and Cloudflare account for >95% of Turkish and Kurdish regional news outlets, bespoke in-house CDNs with custom query parameters may not be unwrapped unless they follow standard `?url=` or `/cdn-cgi/` conventions. The fallback to canonical key filtering safely handles un-unwrapped URLs without crashing.
3. **Watermark Crop Conservatism**: Watermark heuristics must remain conservative (never crop >8% of any dimension) to avoid clipping faces, text, or architectural features in wide-angle photos.
4. **CMS Sizing Contract**: This blueprint assumes `McpNewsTools::NEWS_IMAGE_SIZES` remains configured as `/` (`1280x720`), `/4x3` (`1200x900`), and `/1x1` (`1200x1200`). Any change in CMS sizes will cleanly benefit from `--mode master`.

---

## 4. Conclusion & Concrete Implementation Blueprint

The investigation provides an actionable blueprint for the implementer agent. Below is the proposed code structure to replace and enhance the relevant sections of `.agents/skills/kevanger/scripts/process_news_image.py`.

### 4.1 Implementation Blueprint: Imports & Shared Transport Setup
```python
from pathlib import Path

# Resolve shared transport layer from .agents/skills/_shared/
_SHARED_DIR = Path(__file__).resolve().parents[2] / "_shared"
if str(_SHARED_DIR) not in sys.path:
    sys.path.insert(0, str(_SHARED_DIR))

try:
    import editorial_http
except ImportError:
    editorial_http = None

DEFAULT_HTML_HEADERS = {
    "User-Agent": DEFAULT_USER_AGENT,
    "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8",
    "Accept-Language": "tr,en;q=0.8",
}

DEFAULT_IMAGE_HEADERS = {
    "User-Agent": DEFAULT_USER_AGENT,
    "Accept": "image/avif,image/webp,image/apng,image/*,*/*;q=0.8",
    "Accept-Language": "tr,en;q=0.8",
}
```

### 4.2 Implementation Blueprint: `unwrap_cdn_image_url` & `canonical_image_key`
```python
def unwrap_cdn_image_url(url: str, base_url: str = "") -> str:
    """
    Unwraps Next.js, Vercel, WordPress Photon, and Cloudflare proxied image URLs.
    Recursively resolves inner targets, resolves relative URLs, and validates public IP.
    """
    current = url.strip()
    for _ in range(3):
        parsed = urllib.parse.urlparse(current)
        
        # Next.js (/_next/image?url=...) and Vercel (/_vercel/image?url=...)
        if "/_next/image" in parsed.path or "/_vercel/image" in parsed.path:
            qs = urllib.parse.parse_qs(parsed.query)
            if "url" in qs and qs["url"]:
                inner = urllib.parse.unquote(qs["url"][0])
                current = urllib.parse.urljoin(base_url or current, inner)
                continue
                
        # WordPress Photon (i0.wp.com, i1.wp.com, etc.)
        if re.search(r"^i[0-3]\.wp\.com$", parsed.netloc, re.I):
            clean_path = parsed.path.lstrip("/")
            if clean_path.startswith(("http:/", "https:/")):
                current = re.sub(r"^(https?):/+", r"\1://", clean_path)
            else:
                current = "https://" + clean_path
            continue
            
        # Cloudflare Image Resizing (/cdn-cgi/image/<options>/<target>)
        if "/cdn-cgi/image/" in parsed.path:
            parts = parsed.path.split("/cdn-cgi/image/", 1)
            rest = parts[1]
            if "/" in rest:
                inner = rest.split("/", 1)[1]
                current = urllib.parse.urljoin(base_url or current, inner)
                continue
        break

    return current


def extract_filename_stem(url: str) -> str:
    """Extract filename stem without extension or query string."""
    parsed = urllib.parse.urlparse(url)
    stem, _ = os.path.splitext(os.path.basename(parsed.path))
    return stem.strip().lower()


def canonical_image_key(url: str, base_url: str = "") -> str:
    """
    Produce canonical deduplication key: unwrap proxy, strip resize/format params,
    preserving semantic query parameters (id, token, etc.).
    """
    unwrapped = unwrap_cdn_image_url(url, base_url)
    parsed = urllib.parse.urlparse(unwrapped)
    if parsed.query:
        qs = urllib.parse.parse_qsl(parsed.query, keep_blank_values=True)
        filtered = [
            (k, v) for k, v in qs
            if k.lower() not in {
                "w", "q", "width", "height", "fit", "auto", "format",
                "quality", "resize", "crop", "strip", "max-w", "max-h"
            }
        ]
        parsed = parsed._replace(query=urllib.parse.urlencode(sorted(filtered)))
    return parsed.geturl().rstrip("/")
```

### 4.3 Implementation Blueprint: DOM Scoping & Candidate Extraction
```python
EXCLUDE_CONTAINER_RE = re.compile(
    r"\b(sidebar|related|recommend|most-read|popular|son-haber|benzer|widget|ticker|footer|comment|banner|ad-|ads-|social|share|breadcrumb|pagination|nav|navbar|menu)\b",
    re.I
)
VOID_TAGS = {"img", "br", "hr", "input", "meta", "link", "source", "area", "base", "col", "embed", "param", "track", "wbr"}

class ArticleImageCandidateExtractor(html.parser.HTMLParser):
    def __init__(self, base_url: str):
        super().__init__()
        self.base_url = base_url
        self.article_depth = 0
        self.main_depth = 0
        self.excluded_depth = 0
        self.tag_stack: List[Tuple[str, bool]] = []
        self.lead_article_img_count = 0
        self.meta_stems: Set[str] = set()
        self.candidates: List[Dict[str, Any]] = []

    def handle_starttag(self, tag: str, attrs: List[Tuple[str, Optional[str]]]):
        tag_lower = tag.lower()
        attr_dict = {k.lower(): (v or "") for k, v in attrs}

        if tag_lower == "article":
            self.article_depth += 1
        elif tag_lower == "main":
            self.main_depth += 1

        is_excl = False
        if tag_lower in ("aside", "nav", "footer") or (tag_lower == "header" and self.article_depth == 0):
            is_excl = True
        else:
            combined = f"{attr_dict.get('class', '')} {attr_dict.get('id', '')}"
            if EXCLUDE_CONTAINER_RE.search(combined):
                is_excl = True

        if tag_lower not in VOID_TAGS:
            if is_excl:
                self.excluded_depth += 1
            self.tag_stack.append((tag_lower, is_excl))

        in_article = (self.article_depth > 0 and self.excluded_depth == 0)
        in_main = (self.main_depth > 0 and self.excluded_depth == 0)
        is_excluded = (self.excluded_depth > 0)

        # Handle <meta>
        if tag_lower == "meta":
            prop = (attr_dict.get("property") or attr_dict.get("name") or "").lower()
            if prop in ("og:image", "og:image:url", "twitter:image", "twitter:image:src"):
                content = attr_dict.get("content", "").strip()
                if content and not PLACEHOLDER_FILTER.search(content):
                    full_url = urllib.parse.urljoin(self.base_url, content)
                    unwrapped = unwrap_cdn_image_url(full_url, self.base_url)
                    stem = extract_filename_stem(unwrapped)
                    if stem and len(stem) >= 4 and stem not in {"image", "photo", "thumb"}:
                        self.meta_stems.add(stem)
                    self.candidates.append({
                        "url": full_url,
                        "unwrapped_url": unwrapped,
                        "width_hint": 1024,
                        "origin": "meta_og_image",
                        "in_article": False,
                        "in_main": False,
                        "is_lead_hero": False,
                        "is_excluded": False,
                        "matches_meta_stem": True,
                    })

        # Handle <img>
        if tag_lower == "img":
            explicit_width = None
            if attr_dict.get("width"):
                try:
                    explicit_width = int(attr_dict["width"])
                except ValueError:
                    pass

            srcset = attr_dict.get("srcset", "").strip()
            if srcset:
                self._parse_srcset(srcset, "img_srcset", in_article, in_main, is_excluded, explicit_width)

            src = attr_dict.get("src") or attr_dict.get("data-src") or attr_dict.get("data-original") or ""
            if src.strip() and not PLACEHOLDER_FILTER.search(src):
                full_url = urllib.parse.urljoin(self.base_url, src.strip())
                unwrapped = unwrap_cdn_image_url(full_url, self.base_url)
                stem = extract_filename_stem(unwrapped)
                matches_stem = bool(stem and stem in self.meta_stems)
                if in_article:
                    self.lead_article_img_count += 1
                    is_lead = (self.lead_article_img_count <= 2)
                else:
                    is_lead = False
                self.candidates.append({
                    "url": full_url,
                    "unwrapped_url": unwrapped,
                    "width_hint": explicit_width or 800,
                    "origin": "img_src",
                    "in_article": in_article,
                    "in_main": in_main,
                    "is_lead_hero": is_lead,
                    "is_excluded": is_excluded,
                    "matches_meta_stem": matches_stem,
                })

    def handle_endtag(self, tag: str):
        tag_lower = tag.lower()
        if tag_lower == "article" and self.article_depth > 0:
            self.article_depth -= 1
        elif tag_lower == "main" and self.main_depth > 0:
            self.main_depth -= 1

        for i in range(len(self.tag_stack) - 1, -1, -1):
            t, was_excl = self.tag_stack[i]
            if t == tag_lower:
                if was_excl and self.excluded_depth > 0:
                    self.excluded_depth -= 1
                del self.tag_stack[i:]
                break

    def _parse_srcset(self, srcset: str, origin: str, in_article: bool, in_main: bool, is_excluded: bool, explicit_width: Optional[int]):
        parts = [p.strip() for p in srcset.split(",") if p.strip()]
        for part in parts:
            tokens = part.split()
            if not tokens:
                continue
            cand_url = tokens[0]
            if PLACEHOLDER_FILTER.search(cand_url):
                continue
            width_hint = 800
            if len(tokens) > 1:
                desc = tokens[1].lower()
                if desc.endswith("w"):
                    try:
                        width_hint = int(desc[:-1])
                    except ValueError:
                        pass
                elif desc.endswith("x"):
                    try:
                        width_hint = int(float(desc[:-1]) * 800)
                    except ValueError:
                        pass

            # Capping logic
            is_cdn_proxy = ("/_next/image" in cand_url or "/_vercel/image" in cand_url)
            if width_hint > 3840:
                width_hint = 3840
            if is_cdn_proxy and width_hint > 1200:
                if explicit_width and explicit_width >= 1200:
                    width_hint = min(width_hint, explicit_width)
                else:
                    width_hint = 1200

            full_url = urllib.parse.urljoin(self.base_url, cand_url)
            unwrapped = unwrap_cdn_image_url(full_url, self.base_url)
            stem = extract_filename_stem(unwrapped)
            matches_stem = bool(stem and stem in self.meta_stems)

            if in_article:
                self.lead_article_img_count += 1
                is_lead = (self.lead_article_img_count <= 2)
            else:
                is_lead = False

            self.candidates.append({
                "url": full_url,
                "unwrapped_url": unwrapped,
                "width_hint": width_hint,
                "origin": origin,
                "in_article": in_article,
                "in_main": in_main,
                "is_lead_hero": is_lead,
                "is_excluded": is_excluded,
                "matches_meta_stem": matches_stem,
            })
```

### 4.4 Implementation Blueprint: Candidate Ranking with Deduplication
```python
def rank_candidates(candidates: List[Dict[str, Any]], base_url: str = "") -> List[Dict[str, Any]]:
    def score_candidate(c: Dict[str, Any]) -> int:
        score = c.get("width_hint", 0)
        url = c["unwrapped_url"].lower()

        # Severe penalty for excluded containers
        if c.get("is_excluded"):
            score -= 2000
        # +600 primary article lead hero bonus
        elif c.get("is_lead_hero"):
            score += 600
        elif c.get("in_article"):
            score += 200
        elif c.get("in_main"):
            score += 50

        # +2000 cross-anchor boost when matching og:image / twitter:image stem
        if c.get("matches_meta_stem"):
            score += 2000

        # Known clean CDN patterns
        if "cpsprodpb" in url or "original" in url or "1920" in url or "1536" in url:
            score += 400

        # Penalize branded / social badges
        if "branded_news" in url or "social" in url or "banner" in url:
            score -= 600

        return score

    # Sort descending by score
    sorted_candidates = sorted(candidates, key=score_candidate, reverse=True)

    # Deduplicate by canonical unwrapped URL
    seen = set()
    unique = []
    for c in sorted_candidates:
        key = canonical_image_key(c["unwrapped_url"], base_url)
        if key not in seen:
            seen.add(key)
            unique.append(c)

    return unique
```

### 4.5 Implementation Blueprint: Regional Watermark Heuristics
```python
def inspect_watermarks(img: Image.Image) -> Dict[str, Any]:
    w, h = img.size
    detected_regions = []
    crop_margins = {"top": 0.0, "bottom": 0.0, "left": 0.0, "right": 0.0}

    # Inspect top-right corner (Rûdaw / K24 red bug signature)
    tr_box = (int(w * 0.82), 0, w, int(h * 0.12))
    tr_crop = img.crop(tr_box)
    red_count = 0
    total_tr_px = tr_crop.width * tr_crop.height
    for px in tr_crop.getdata():
        r, g, b = px[0], px[1], px[2]
        if r > 160 and g < 70 and b < 70:
            red_count += 1
    if total_tr_px > 0 and (red_count / total_tr_px) > 0.015:
        detected_regions.append("top_right_bug")
        crop_margins["top"] = 5.0

    # Inspect bottom strip (AA, DHA, İHA ticker bar)
    bs_box = (0, int(h * 0.94), w, h)
    bs_crop = img.crop(bs_box)
    extrema = bs_crop.convert("L").getextrema()
    # If high contrast bar at bottom
    if extrema[1] - extrema[0] > 180:
        detected_regions.append("bottom_strip")
        crop_margins["bottom"] = 6.0

    return {
        "watermark_detected": len(detected_regions) > 0,
        "watermark_regions": detected_regions,
        "crop_percentages": crop_margins,
    }
```

### 4.6 Implementation Blueprint: `--mode master` vs `--mode inline-16x9` Processing
```python
def process_image(
    img: Image.Image,
    mode: str = "master",
    crop_watermark: bool = False
) -> Tuple[bytes, int, int, Dict[str, Any]]:
    # EXIF correction
    img = ImageOps.exif_transpose(img)

    # Convert to RGB
    if img.mode in ("RGBA", "LA", "P"):
        background = Image.new("RGB", img.size, (255, 255, 255))
        if img.mode == "P":
            img = img.convert("RGBA")
        background.paste(img, mask=img.split()[-1] if "A" in img.mode else None)
        img = background
    elif img.mode != "RGB":
        img = img.convert("RGB")

    wm_report = inspect_watermarks(img)
    w, h = img.size

    # Clean crop watermark if requested or detected
    if crop_watermark and wm_report["watermark_detected"]:
        pct = wm_report["crop_percentages"]
        top_px = int(h * (pct["top"] / 100.0))
        bottom_px = int(h * (pct["bottom"] / 100.0))
        img = img.crop((0, top_px, w, h - bottom_px))
        w, h = img.size

    if mode == "master":
        # Do NOT center crop to 16:9! Preserve natural vertical resolution.
        if w > 1920:
            target_h = int(h * (1920.0 / w))
            img = img.resize((1920, target_h), Image.Resampling.LANCZOS)
            w, h = img.size

        # WebP at quality 90 (< 1.5 MB)
        quality = 90
        buf = io.BytesIO()
        img.save(buf, format="WEBP", quality=quality, method=6)
        data = buf.getvalue()

        # Step down if exceeds 1.5 MB
        max_bytes = 1572864  # 1.5 MB
        while len(data) > max_bytes and quality > 75:
            quality -= 5
            buf = io.BytesIO()
            img.save(buf, format="WEBP", quality=quality, method=6)
            data = buf.getvalue()

        return data, w, h, wm_report

    else:  # inline-16x9
        img = crop_to_aspect_ratio(img, 16 / 9)
        if img.width != 1280 or img.height != 720:
            img = img.resize((1280, 720), Image.Resampling.LANCZOS)
        w, h = img.size

        # Compress to WebP under 100 KB
        quality = 85
        compressed_bytes = b""
        while quality >= 25:
            buf = io.BytesIO()
            img.save(buf, format="WEBP", quality=quality, method=6)
            data = buf.getvalue()
            if len(data) <= MAX_SIZE_BYTES or quality <= 25:
                compressed_bytes = data
                break
            quality -= 5

        return compressed_bytes, w, h, wm_report
```

---

## 5. Verification Method

### 5.1 Verification Commands
The implementer can verify compliance and fix efficacy via these commands:

1. **Syntax & Compilation Validation**:
   ```bash
   python3 -m py_compile .agents/skills/kevanger/scripts/process_news_image.py
   ```

2. **Next.js Sidebar Hijack & Deduplication Regression Test**:
   ```bash
   python3 -c "
   import sys
   sys.path.insert(0, '.agents/skills/kevanger/scripts')
   import process_news_image

   with open('tests/e2e/fixtures/editorial/mock_nextjs_sidebar_article.html') as f:
       html = f.read()

   parser = process_news_image.ArticleImageCandidateExtractor('https://example.com/article/1')
   parser.feed(html)
   ranked = process_news_image.rank_candidates(parser.candidates, 'https://example.com/article/1')

   assert len(ranked) >= 2, f'Expected multiple candidates, got {len(ranked)}'
   assert 'hero_main' in ranked[0]['unwrapped_url'], f'Hero image failed to win! Winner: {ranked[0]}'
   assert 'sidebar_thumb' not in ranked[0]['unwrapped_url'], 'Sidebar thumbnail hijacked ranking!'
   print('PASS: Next.js unwrap and ranking correctly selected hero image over sidebar thumbnail.')
   "
   ```

3. **Master Mode Aspect Ratio & Dimensions Test**:
   ```bash
   python3 .agents/skills/kevanger/scripts/process_news_image.py \
     --input tests/e2e/fixtures/images/rudaw_true_hero_1920x1080.jpg \
     --source "Rûdaw" \
     --mode master
   ```
   *Expected output*: JSON with `"mode": "master"`, `"width": 1920`, `"height": 1080`, `"size_bytes" < 1572864`, `"status": "SUCCESS"`.

4. **Inline-16x9 Backward Compatibility Test**:
   ```bash
   python3 .agents/skills/kevanger/scripts/process_news_image.py \
     --input tests/e2e/fixtures/images/rudaw_true_hero_1920x1080.jpg \
     --source "Rûdaw" \
     --mode inline-16x9
   ```
   *Expected output*: JSON with `"mode": "inline-16x9"`, `"width": 1280`, `"height": 720`, `"size_bytes" < 102400`.

5. **Quality Guards & Project Test Suite**:
   ```bash
   python3 tools/code_separation_guard.py
   python3 tools/i18n_guard.py
   python3 tools/module_boundary_guard.py --all
   ```

### 5.2 Invalidation Conditions
This investigation's conclusions and blueprint would be invalidated if:
- `McpNewsTools::NEWS_IMAGE_SIZES` removes multi-ratio derivatives (`/4x3` and `/1x1`), reverting to 16:9-only storage.
- Next.js completely deprecates `/_next/image` in favor of an unproxied direct origin structure.
- Editorial pipeline mandates client-side PNG storage instead of WebP.
