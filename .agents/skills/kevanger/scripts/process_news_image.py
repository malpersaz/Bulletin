#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Kevanger News Image Processor & WebP Optimizer

Prepares, verifies, and optimizes news images for the Amed Framework CMS.
Supports:
1. --mode master (Default): Clean, unflattened master image preserving natural
   aspect ratio (>= 1200px width, max 1920px width, WebP quality 90, < 1.5 MB)
   designed for CMS multi-size derivative generation (16:9, 4:3, 1:1) without
   double lossy compression artifacts.
2. --mode inline-16x9: Legacy center-cropped 16:9 (1280x720) WebP under 100 KB.
3. Next.js / CDN proxy URL unwrapping (/_next/image?url=...) and canonical deduplication.
4. Depth-tracked DOM container scoping (<article> vs <main>) with automatic exclusion
   of sidebars, tickers, recommendations, and nav containers.
5. Srcset descriptor capping (max 3840w; max 1200w for proxy URLs unless explicit)
   and og:image / twitter:image stem anchoring (+2000 score bonus).
6. Regional watermark heuristics for Kurdish and Turkish news agencies (Rudaw/K24 top-right
   red bug, AA/DHA/İHA bottom ticker bar) with crop percentage logging.
7. Standardized journalistic attribution (Wêne: $Source / Fotoğraf: $Source).
"""

import argparse
import base64
import html.parser
import io
import ipaddress
import json
import os
from pathlib import Path
import re
import socket
import ssl
import sys
import urllib.error
import urllib.parse
import urllib.request
from typing import Any, Dict, List, Optional, Set, Tuple
from PIL import Image, ImageOps

Image.MAX_IMAGE_PIXELS = 40_000_000
MAX_SIZE_BYTES_INLINE = 100 * 1024       # 100 KB
MAX_SIZE_BYTES_MASTER = 1536 * 1024      # 1.5 MB
MAX_HTML_BYTES = 2 * 1024 * 1024         # 2 MB
MAX_IMAGE_DOWNLOAD_BYTES = 15 * 1024 * 1024  # 15 MB

TARGET_WIDTH_INLINE = 1280
TARGET_HEIGHT_INLINE = 720  # 16:9 aspect ratio
MIN_MASTER_WIDTH = 1200
MAX_MASTER_WIDTH = 1920

DEFAULT_USER_AGENT = (
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 "
    "(KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
)

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

PLACEHOLDER_FILTER = re.compile(
    r"grey-placeholder|\.svg\b|1x1|avatar|logo|favicon|icon|pixel|blank",
    re.I
)

EXCLUDE_CONTAINER_RE = re.compile(
    r"\b(sidebar|related|recommend|most-read|popular|son-haber|benzer|widget|ticker|footer|comment|banner|ad-|ads-|social|share|breadcrumb|pagination|nav|navbar|menu)\b",
    re.I
)

VOID_TAGS = {
    "img", "br", "hr", "input", "meta", "link", "source", "area", "base", "col", "embed", "param", "track", "wbr"
}

# Resolve shared transport layer from .agents/skills/_shared/
_SHARED_DIR = Path(__file__).resolve().parents[2] / "_shared"
if str(_SHARED_DIR) not in sys.path:
    sys.path.insert(0, str(_SHARED_DIR))

try:
    import editorial_http
except ImportError:
    editorial_http = None


# ---------------------------------------------------------------------------
# SSRF & URL Validation
# ---------------------------------------------------------------------------

def validate_public_url(url: str) -> None:
    """Validate that the URL uses http/https and resolves exclusively to public IP addresses."""
    if editorial_http is not None:
        valid, reason = editorial_http.validate_public_url(url)
        if not valid:
            raise ValueError(reason)
        return

    parsed = urllib.parse.urlparse(url)
    if parsed.scheme not in ("http", "https"):
        raise ValueError(f"Invalid URL scheme '{parsed.scheme}': only http and https are permitted")
    hostname = parsed.hostname
    if not hostname:
        raise ValueError("URL is missing hostname")

    try:
        addr_info = socket.getaddrinfo(hostname, None)
    except socket.gaierror as e:
        raise ValueError(f"Could not resolve host '{hostname}': {e}")

    for item in addr_info:
        ip_str = item[4][0]
        try:
            ip = ipaddress.ip_address(ip_str)
            if (
                ip.is_private
                or ip.is_loopback
                or ip.is_link_local
                or ip.is_reserved
                or ip.is_multicast
                or ip.is_unspecified
            ):
                raise ValueError(
                    f"SSRF protection: host '{hostname}' resolves to non-public IP '{ip_str}'"
                )
        except ValueError as err:
            raise err


class SSRFSafeRedirectHandler(urllib.request.HTTPRedirectHandler):
    """Ensure HTTP redirects do not jump to private/internal endpoints."""
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        validate_public_url(newurl)
        return super().redirect_request(req, fp, code, msg, headers, newurl)


def build_secure_opener() -> urllib.request.OpenerDirector:
    ctx = ssl.create_default_context()
    https_handler = urllib.request.HTTPSHandler(context=ctx)
    redirect_handler = SSRFSafeRedirectHandler()
    return urllib.request.build_opener(https_handler, redirect_handler)


def fetch_url_bytes(
    url: str,
    max_bytes: int = MAX_IMAGE_DOWNLOAD_BYTES,
    timeout: int = 15,
    is_html: bool = False,
    referer: str = ""
) -> bytes:
    """Fetch URL bytes using editorial_http if available, otherwise secure urllib fallback."""
    if editorial_http is not None:
        if is_html:
            status_code, html_str, resp_headers = editorial_http.fetch_html(
                url, timeout=timeout, max_bytes=max_bytes
            )
            if not (200 <= status_code < 300):
                err = resp_headers.get("error") or resp_headers.get("x-error") or f"HTTP {status_code}"
                raise ValueError(f"Failed to fetch HTML from {url}: {err}")
            return html_str.encode("utf-8")
        else:
            custom_headers = {}
            if referer:
                custom_headers["Referer"] = referer
            status_code, raw_bytes, resp_headers = editorial_http.fetch_binary(
                url, timeout=timeout, max_bytes=max_bytes, headers=custom_headers
            )
            if not (200 <= status_code < 300):
                err = resp_headers.get("error") or resp_headers.get("x-error") or f"HTTP {status_code}"
                raise ValueError(f"Failed to fetch binary from {url}: {err}")
            return raw_bytes

    # Fallback to internal urllib
    validate_public_url(url)
    opener = build_secure_opener()
    headers = dict(DEFAULT_HTML_HEADERS if is_html else DEFAULT_IMAGE_HEADERS)
    if referer and not is_html:
        headers["Referer"] = referer

    req = urllib.request.Request(url, headers=headers)
    with opener.open(req, timeout=timeout) as resp:
        data = resp.read(max_bytes + 1)
        if len(data) > max_bytes:
            raise ValueError(f"Downloaded content exceeded {max_bytes} bytes limit")
        return data


# ---------------------------------------------------------------------------
# CDN Unwrapping & Canonical Deduplication
# ---------------------------------------------------------------------------

def unwrap_cdn_image_url(url: str, base_url: str = "") -> str:
    """
    Unwraps Next.js, Vercel, WordPress Photon, and Cloudflare proxied image URLs.
    Recursively resolves inner targets, resolves relative URLs, and strips wrappers.
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


# ---------------------------------------------------------------------------
# DOM Scoping & Candidate Extraction
# ---------------------------------------------------------------------------

class ArticleImageCandidateExtractor(html.parser.HTMLParser):
    def __init__(self, base_url: str):
        super().__init__()
        self.base_url = base_url
        self.article_depth = 0
        self.main_depth = 0
        self.excluded_depth = 0
        self.tag_stack: List[Tuple[str, bool]] = []
        self.in_script_ld_json = False
        self.script_buffer: List[str] = []
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

        # Handle LD+JSON scripts
        if tag_lower == "script" and attr_dict.get("type") == "application/ld+json":
            self.in_script_ld_json = True
            self.script_buffer = []
            return

        # Handle <meta>
        if tag_lower == "meta":
            prop = (attr_dict.get("property") or attr_dict.get("name") or "").lower()
            if prop in ("og:image", "og:image:url", "twitter:image", "twitter:image:src"):
                content = attr_dict.get("content", "").strip()
                if content and not PLACEHOLDER_FILTER.search(content):
                    full_url = urllib.parse.urljoin(self.base_url, content)
                    unwrapped = unwrap_cdn_image_url(full_url, self.base_url)
                    stem = extract_filename_stem(unwrapped)
                    if stem and len(stem) >= 4 and stem not in {"image", "photo", "thumb", "default"}:
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

        # Handle <source srcset="..." /> inside <picture>
        if tag_lower == "source":
            srcset = attr_dict.get("srcset", "").strip()
            if srcset:
                self._parse_srcset(srcset, "picture_source", in_article, in_main, is_excluded, None)

        # Handle <img>
        if tag_lower == "img":
            explicit_width: Optional[int] = None
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
        elif tag_lower == "script" and self.in_script_ld_json:
            self.in_script_ld_json = False
            raw_json = "".join(self.script_buffer).strip()
            if raw_json:
                self._parse_ld_json(raw_json)

        for i in range(len(self.tag_stack) - 1, -1, -1):
            t, was_excl = self.tag_stack[i]
            if t == tag_lower:
                if was_excl and self.excluded_depth > 0:
                    self.excluded_depth -= 1
                del self.tag_stack[i:]
                break

    def handle_data(self, data: str):
        if self.in_script_ld_json:
            self.script_buffer.append(data)

    def _parse_srcset(
        self,
        srcset: str,
        origin: str,
        in_article: bool,
        in_main: bool,
        is_excluded: bool,
        explicit_width: Optional[int]
    ):
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

            # Capping logic: cap at 3840w max; for proxy URLs cap at 1200w unless explicit
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

    def _parse_ld_json(self, raw_json: str):
        try:
            data = json.loads(raw_json)
        except Exception:
            return

        def extract_images(item: Any):
            if isinstance(item, dict):
                img = item.get("image")
                if isinstance(img, str) and not PLACEHOLDER_FILTER.search(img):
                    full_url = urllib.parse.urljoin(self.base_url, img)
                    unwrapped = unwrap_cdn_image_url(full_url, self.base_url)
                    stem = extract_filename_stem(unwrapped)
                    if stem and len(stem) >= 4 and stem not in {"image", "photo", "thumb", "default"}:
                        self.meta_stems.add(stem)
                    self.candidates.append({
                        "url": full_url,
                        "unwrapped_url": unwrapped,
                        "width_hint": 1200,
                        "origin": "ld_json",
                        "in_article": True,
                        "in_main": True,
                        "is_lead_hero": True,
                        "is_excluded": False,
                        "matches_meta_stem": True,
                    })
                elif isinstance(img, list):
                    for sub in img:
                        if isinstance(sub, str) and not PLACEHOLDER_FILTER.search(sub):
                            full_url = urllib.parse.urljoin(self.base_url, sub)
                            unwrapped = unwrap_cdn_image_url(full_url, self.base_url)
                            stem = extract_filename_stem(unwrapped)
                            if stem and len(stem) >= 4 and stem not in {"image", "photo", "thumb", "default"}:
                                self.meta_stems.add(stem)
                            self.candidates.append({
                                "url": full_url,
                                "unwrapped_url": unwrapped,
                                "width_hint": 1200,
                                "origin": "ld_json",
                                "in_article": True,
                                "in_main": True,
                                "is_lead_hero": True,
                                "is_excluded": False,
                                "matches_meta_stem": True,
                            })
                elif isinstance(img, dict):
                    u = img.get("url")
                    if isinstance(u, str) and not PLACEHOLDER_FILTER.search(u):
                        w = int(img.get("width") or 1200)
                        full_url = urllib.parse.urljoin(self.base_url, u)
                        unwrapped = unwrap_cdn_image_url(full_url, self.base_url)
                        stem = extract_filename_stem(unwrapped)
                        if stem and len(stem) >= 4 and stem not in {"image", "photo", "thumb", "default"}:
                            self.meta_stems.add(stem)
                        self.candidates.append({
                            "url": full_url,
                            "unwrapped_url": unwrapped,
                            "width_hint": w,
                            "origin": "ld_json",
                            "in_article": True,
                            "in_main": True,
                            "is_lead_hero": True,
                            "is_excluded": False,
                            "matches_meta_stem": True,
                        })
                for v in item.values():
                    extract_images(v)
            elif isinstance(item, list):
                for elem in item:
                    extract_images(elem)

        extract_images(data)


# ---------------------------------------------------------------------------
# Candidate Ranking & Deduplication
# ---------------------------------------------------------------------------

def rank_candidates(candidates: List[Dict[str, Any]], base_url: str = "") -> List[Dict[str, Any]]:
    """Rank candidates preferring authentic in-article lead hero images over secondary thumbnails."""
    def score_candidate(c: Dict[str, Any]) -> int:
        score = c.get("width_hint", 0)
        url = c.get("unwrapped_url", c.get("url", "")).lower()

        # Severe penalty for excluded containers (-2000)
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
        cand_url = c.get("unwrapped_url", c["url"])
        key = canonical_image_key(cand_url, base_url)
        if key not in seen:
            seen.add(key)
            unique.append(c)

    return unique


# ---------------------------------------------------------------------------
# Regional Watermark Heuristics
# ---------------------------------------------------------------------------

def inspect_watermarks(img: Image.Image) -> Dict[str, Any]:
    """
    Detects standard Kurdish & Turkish regional agency watermarks/overlays:
    - Rûdaw / K24 red bug in top-right corner
    - AA / DHA / İHA / MA ticker strip across bottom
    """
    w, h = img.size
    detected_regions = []
    crop_margins = {"top": 0.0, "bottom": 0.0, "left": 0.0, "right": 0.0}

    # Inspect top-right corner (Rûdaw / K24 red bug signature)
    tr_box = (int(w * 0.82), 0, w, int(h * 0.12))
    tr_crop = img.crop(tr_box)
    red_count = 0
    total_tr_px = tr_crop.width * tr_crop.height
    pixels = tr_crop.get_flattened_data() if hasattr(tr_crop, "get_flattened_data") else tr_crop.getdata()
    for px in pixels:
        if isinstance(px, (tuple, list)):
            r, g, b = px[0], px[1], px[2]
        else:
            r = g = b = px
        if r > 160 and g < 70 and b < 70:
            red_count += 1
    if total_tr_px > 0 and (red_count / total_tr_px) > 0.015:
        detected_regions.append("top_right_bug")
        crop_margins["top"] = 5.0

    # Inspect bottom strip (AA, DHA, İHA ticker bar)
    bs_box = (0, int(h * 0.94), w, h)
    bs_crop = img.crop(bs_box)
    extrema = bs_crop.convert("L").getextrema()
    if extrema[1] - extrema[0] > 180:
        detected_regions.append("bottom_strip")
        crop_margins["bottom"] = 6.0

    return {
        "watermark_detected": len(detected_regions) > 0,
        "watermark_regions": detected_regions,
        "crop_percentages": crop_margins,
    }


# ---------------------------------------------------------------------------
# Image Processing & WebP Encoding
# ---------------------------------------------------------------------------

def crop_to_aspect_ratio(img: Image.Image, target_ratio: float = 16 / 9) -> Image.Image:
    w, h = img.size
    current_ratio = w / h

    if abs(current_ratio - target_ratio) < 0.02:
        return img

    if current_ratio > target_ratio:
        new_w = int(h * target_ratio)
        offset = (w - new_w) // 2
        return img.crop((offset, 0, offset + new_w, h))
    else:
        new_h = int(w / target_ratio)
        offset = (h - new_h) // 2
        return img.crop((0, offset, w, offset + new_h))


def process_image(
    img: Image.Image,
    mode: str = "master",
    crop_watermark: bool = False
) -> Tuple[bytes, int, int, Dict[str, Any]]:
    """
    Optimizes and encodes image into WebP format according to mode:
    - 'master': unflattened natural aspect ratio, max width 1920px, quality 90 (<1.5 MB).
    - 'inline-16x9': 16:9 center crop to 1280x720, quality stepping down (<100 KB).
    """
    # EXIF orientation correction
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
        if h - bottom_px > top_px + 100:
            img = img.crop((0, top_px, w, h - bottom_px))
            w, h = img.size

    if mode == "master":
        # Preserve natural vertical resolution (no 16:9 crop)
        if w > MAX_MASTER_WIDTH:
            target_h = int(h * (float(MAX_MASTER_WIDTH) / w))
            img = img.resize((MAX_MASTER_WIDTH, target_h), Image.Resampling.LANCZOS)
            w, h = img.size

        # WebP encoding at quality 90 (<1.5 MB)
        quality = 90
        buf = io.BytesIO()
        img.save(buf, format="WEBP", quality=quality, method=6)
        data = buf.getvalue()

        while len(data) > MAX_SIZE_BYTES_MASTER and quality > 75:
            quality -= 5
            buf = io.BytesIO()
            img.save(buf, format="WEBP", quality=quality, method=6)
            data = buf.getvalue()

        return data, w, h, wm_report

    else:  # inline-16x9
        img = crop_to_aspect_ratio(img, 16 / 9)
        if img.width != TARGET_WIDTH_INLINE or img.height != TARGET_HEIGHT_INLINE:
            img = img.resize((TARGET_WIDTH_INLINE, TARGET_HEIGHT_INLINE), Image.Resampling.LANCZOS)
        w, h = img.size

        # Compress to WebP under 100 KB
        quality = 85
        compressed_bytes = b""
        while quality >= 25:
            buf = io.BytesIO()
            img.save(buf, format="WEBP", quality=quality, method=6)
            data = buf.getvalue()
            if len(data) <= MAX_SIZE_BYTES_INLINE or quality <= 25:
                compressed_bytes = data
                break
            quality -= 5

        if not compressed_bytes:
            buf = io.BytesIO()
            img.save(buf, format="WEBP", quality=25, method=6)
            compressed_bytes = buf.getvalue()

        return compressed_bytes, w, h, wm_report


# ---------------------------------------------------------------------------
# Intake & Extraction Entrypoints
# ---------------------------------------------------------------------------

def extract_hero_image_from_article(article_url: str) -> Tuple[Image.Image, str, int, int]:
    html_bytes = fetch_url_bytes(article_url, max_bytes=MAX_HTML_BYTES, is_html=True)
    html_text = html_bytes.decode("utf-8", errors="replace")

    parser = ArticleImageCandidateExtractor(article_url)
    parser.feed(html_text)

    ranked = rank_candidates(parser.candidates, base_url=article_url)
    if not ranked:
        raise ValueError(f"No suitable news image candidates could be discovered at {article_url}")

    parsed_article = urllib.parse.urlparse(article_url)
    origin_referer = f"{parsed_article.scheme}://{parsed_article.netloc}/"

    errors = []
    for cand in ranked:
        cand_url = cand["url"]
        try:
            img_bytes = fetch_url_bytes(
                cand_url,
                max_bytes=MAX_IMAGE_DOWNLOAD_BYTES,
                is_html=False,
                referer=origin_referer
            )
            img = Image.open(io.BytesIO(img_bytes))
            img.load()
            w, h = img.size
            if w < 200 or h < 150:
                continue
            return img, cand_url, w, h
        except Exception as e:
            errors.append(f"{cand_url}: {e}")
            continue

    raise ValueError(f"Failed to load any candidate images. Errors: {'; '.join(errors[:3])}")


def load_image_from_input(input_path_or_url: str) -> Tuple[Image.Image, str, int, int]:
    if input_path_or_url.startswith(("http://", "https://")):
        img_bytes = fetch_url_bytes(input_path_or_url, max_bytes=MAX_IMAGE_DOWNLOAD_BYTES, is_html=False)
        img = Image.open(io.BytesIO(img_bytes))
        img.load()
        return img, input_path_or_url, img.width, img.height
    else:
        if not os.path.exists(input_path_or_url):
            raise FileNotFoundError(f"Image file not found: {input_path_or_url}")
        img = Image.open(input_path_or_url)
        img.load()
        return img, input_path_or_url, img.width, img.height


def build_attributions(source: str, is_representative: bool) -> Dict[str, str]:
    if is_representative:
        text_ku = "Wêne: Temsîlî"
        text_tr = "Fotoğraf: Temsili"
    else:
        src = source.strip()
        text_ku = f"Wêne: {src}"
        text_tr = f"Fotoğraf: {src}"

    return {
        "attribution_ku": text_ku,
        "attribution_tr": text_tr,
        "credit_html_ku": f"<p><strong>{text_ku}</strong></p>",
        "credit_html_tr": f"<p><strong>{text_tr}</strong></p>",
    }


# ---------------------------------------------------------------------------
# CLI Main
# ---------------------------------------------------------------------------

def main():
    parser = argparse.ArgumentParser(
        description="Kevanger News Image Processor & WebP Optimizer (Master / Inline-16x9)"
    )
    group = parser.add_mutually_exclusive_group(required=True)
    group.add_argument("--input", "-i", type=str, help="Direct path or URL to input image")
    group.add_argument("--from-article", type=str, help="Article URL to extract lead hero image from")

    parser.add_argument(
        "--mode", type=str, choices=["master", "inline-16x9"], default="master",
        help="Output mode: 'master' (>=1200px natural aspect ratio WebP, <1.5MB) or 'inline-16x9' (1280x720, <100KB)"
    )
    parser.add_argument("--source", "-s", type=str, default="",
                        help="Source attribution name (mandatory unless --representative is set)")
    parser.add_argument("--representative", "-r", action="store_true",
                        help="Flag if image is representative/symbolic (Wêne: Temsîlî)")
    parser.add_argument("--crop-watermark", action="store_true",
                        help="Automatically crop detected agency watermark or ticker bar")
    parser.add_argument("--out", "-o", type=str, default="", help="Optional output WebP file path")
    parser.add_argument("--strict-min-width", action="store_true",
                        help="Exit 1 if original image width is below 1200px (disallow upscaling)")
    args = parser.parse_args()

    # Enforce mandatory attribution
    source_clean = args.source.strip()
    if not source_clean and not args.representative:
        print(
            "Error: Mandatory attribution missing. Either --source <name> or --representative (-r) must be specified.",
            file=sys.stderr
        )
        sys.exit(2)

    try:
        if args.from_article:
            raw_img, cand_url, orig_w, orig_h = extract_hero_image_from_article(args.from_article)
        else:
            raw_img, cand_url, orig_w, orig_h = load_image_from_input(args.input)

        webp_data, final_w, final_h, wm_report = process_image(
            raw_img,
            mode=args.mode,
            crop_watermark=args.crop_watermark
        )
    except Exception as e:
        err_res = {"status": "ERROR", "error": str(e)}
        print(json.dumps(err_res, ensure_ascii=False), file=sys.stderr)
        sys.exit(1)

    upscaled = (orig_w < MIN_MASTER_WIDTH)
    warnings: List[str] = []
    if upscaled:
        warnings.append(
            f"Original image width {orig_w}px is below minimum standard width {MIN_MASTER_WIDTH}px"
        )
        if args.strict_min_width:
            print(f"Error: {warnings[0]} (--strict-min-width enforced)", file=sys.stderr)
            sys.exit(1)
        else:
            print(f"Warning: {warnings[0]}", file=sys.stderr)

    if args.out:
        out_dir = os.path.dirname(os.path.abspath(args.out))
        if out_dir:
            os.makedirs(out_dir, exist_ok=True)
        with open(args.out, "wb") as f:
            f.write(webp_data)

    b64_str = base64.b64encode(webp_data).decode("ascii")
    attr = build_attributions(source_clean, args.representative)

    result = {
        "status": "WARNING" if upscaled else "SUCCESS",
        "mode": args.mode,
        "mime": "image/webp",
        "width": final_w,
        "height": final_h,
        "size_bytes": len(webp_data),
        "size_kb": round(len(webp_data) / 1024, 2),
        "upscaled": upscaled,
        "warnings": warnings,
        "watermark_analysis": wm_report,
        "source_candidate_url": cand_url,
        "source_candidate_width": orig_w,
        "source_candidate_height": orig_h,
        "data_base64": b64_str,
        **attr,
        "mcp_image_payload": {
            "image": {
                "data_base64": b64_str,
                "mime": "image/webp"
            }
        }
    }

    print(json.dumps(result, indent=2, ensure_ascii=False))
    sys.exit(0)


if __name__ == "__main__":
    main()
