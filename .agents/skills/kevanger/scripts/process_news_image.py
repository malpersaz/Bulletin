#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Kevanger News Image Processor & WebP Optimizer

Prepares, center-crops (16:9), resizes (>=1200px), and compresses news images into
WebP under 100 KB with standardized journalistic attribution (Wêne: $Source / Fotoğraf: $Source).

Supports direct image input (--input) or modern article URL candidate extraction (--from-article),
SSRF-safe fetching with verified TLS, Pillow EXIF orientation correction, unbranded hero variant ranking,
and outputs JSON directly consumable by MCP create_news / update_news:
  {
    "status": "SUCCESS",
    "mime": "image/webp",
    "width": 1200,
    "height": 675,
    "size_bytes": 84210,
    "size_kb": 82.24,
    "upscaled": false,
    "source_candidate_url": "https://...",
    "source_candidate_width": 1536,
    "source_candidate_height": 864,
    "data_base64": "...",
    "credit_html_ku": "<p><strong>Wêne: $Source</strong></p>",
    "credit_html_tr": "<p><strong>Fotoğraf: $Source</strong></p>",
    "mcp_image_payload": {
      "image": {
        "data_base64": "...",
        "mime": "image/webp"
      }
    }
  }
"""

import argparse
import base64
import html.parser
import io
import ipaddress
import json
import os
import re
import socket
import ssl
import sys
import urllib.error
import urllib.parse
import urllib.request
from typing import Any, Dict, List, Optional, Tuple
from PIL import Image, ImageOps

Image.MAX_IMAGE_PIXELS = 40_000_000
MAX_SIZE_BYTES = 100 * 1024  # 100 KB
MAX_HTML_BYTES = 2 * 1024 * 1024  # 2 MB
MAX_IMAGE_DOWNLOAD_BYTES = 15 * 1024 * 1024  # 15 MB
TARGET_WIDTH = 1200
TARGET_HEIGHT = 675  # 16:9 aspect ratio

DEFAULT_USER_AGENT = (
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 "
    "(KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
)

PLACEHOLDER_FILTER = re.compile(
    r"grey-placeholder|\.svg\b|1x1|avatar|logo|favicon|icon|pixel|blank",
    re.I
)


def validate_public_url(url: str) -> None:
    """Validate that the URL uses http/https and resolves exclusively to public IP addresses (SSRF guard)."""
    parsed = urllib.parse.urlparse(url)
    if parsed.scheme not in ("http", "https"):
        raise ValueError(f"Invalid URL scheme '{parsed.scheme}': only http and https are permitted")
    hostname = parsed.hostname
    if not hostname:
        raise ValueError("URL is missing hostname")

    try:
        # Resolve all addresses
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


def fetch_url_bytes(url: str, max_bytes: int = MAX_IMAGE_DOWNLOAD_BYTES, timeout: int = 15) -> bytes:
    validate_public_url(url)
    opener = build_secure_opener()
    req = urllib.request.Request(
        url,
        headers={
            "User-Agent": DEFAULT_USER_AGENT,
            "Accept": "image/webp,image/avif,image/*,*/*;q=0.8",
        },
    )
    with opener.open(req, timeout=timeout) as resp:
        data = resp.read(max_bytes + 1)
        if len(data) > max_bytes:
            raise ValueError(f"Downloaded content exceeded {max_bytes} bytes limit")
        return data


class ArticleImageCandidateExtractor(html.parser.HTMLParser):
    def __init__(self, base_url: str):
        super().__init__()
        self.base_url = base_url
        self.in_article_or_main = False
        self.in_script_ld_json = False
        self.script_buffer = []
        self.candidates: List[Dict[str, Any]] = []

    def handle_starttag(self, tag: str, attrs: List[Tuple[str, Optional[str]]]):
        tag_lower = tag.lower()
        attr_dict = {k.lower(): (v or "") for k, v in attrs}

        if tag_lower in ("article", "main"):
            self.in_article_or_main = True

        if tag_lower == "script" and attr_dict.get("type") == "application/ld+json":
            self.in_script_ld_json = True
            self.script_buffer = []
            return

        # OpenGraph & Twitter Card
        if tag_lower == "meta":
            prop = attr_dict.get("property") or attr_dict.get("name") or ""
            if prop.lower() in ("og:image", "og:image:url", "twitter:image", "twitter:image:src"):
                content = attr_dict.get("content", "").strip()
                if content and not PLACEHOLDER_FILTER.search(content):
                    full_url = urllib.parse.urljoin(self.base_url, content)
                    self.candidates.append({
                        "url": full_url,
                        "width_hint": 1024,
                        "origin": "meta_og_image",
                        "in_article": self.in_article_or_main,
                    })

        # <source srcset="..." /> inside <picture>
        if tag_lower == "source":
            srcset = attr_dict.get("srcset", "").strip()
            if srcset:
                self._parse_srcset(srcset, "picture_source")

        # <img>
        if tag_lower == "img":
            srcset = attr_dict.get("srcset", "").strip()
            if srcset:
                self._parse_srcset(srcset, "img_srcset")
            src = attr_dict.get("src") or attr_dict.get("data-src") or attr_dict.get("data-original") or ""
            if src.strip() and not PLACEHOLDER_FILTER.search(src):
                full_url = urllib.parse.urljoin(self.base_url, src.strip())
                self.candidates.append({
                    "url": full_url,
                    "width_hint": 800,
                    "origin": "img_src",
                    "in_article": self.in_article_or_main,
                })

    def handle_endtag(self, tag: str):
        tag_lower = tag.lower()
        if tag_lower in ("article", "main"):
            self.in_article_or_main = False
        elif tag_lower == "script" and self.in_script_ld_json:
            self.in_script_ld_json = False
            raw_json = "".join(self.script_buffer).strip()
            if raw_json:
                self._parse_ld_json(raw_json)

    def handle_data(self, data: str):
        if self.in_script_ld_json:
            self.script_buffer.append(data)

    def _parse_srcset(self, srcset: str, origin: str):
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
            full_url = urllib.parse.urljoin(self.base_url, cand_url)
            self.candidates.append({
                "url": full_url,
                "width_hint": width_hint,
                "origin": origin,
                "in_article": self.in_article_or_main,
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
                    self.candidates.append({
                        "url": urllib.parse.urljoin(self.base_url, img),
                        "width_hint": 1200,
                        "origin": "ld_json",
                        "in_article": True,
                    })
                elif isinstance(img, list):
                    for sub in img:
                        if isinstance(sub, str) and not PLACEHOLDER_FILTER.search(sub):
                            self.candidates.append({
                                "url": urllib.parse.urljoin(self.base_url, sub),
                                "width_hint": 1200,
                                "origin": "ld_json",
                                "in_article": True,
                            })
                elif isinstance(img, dict):
                    u = img.get("url")
                    if isinstance(u, str) and not PLACEHOLDER_FILTER.search(u):
                        w = int(img.get("width") or 1200)
                        self.candidates.append({
                            "url": urllib.parse.urljoin(self.base_url, u),
                            "width_hint": w,
                            "origin": "ld_json",
                            "in_article": True,
                        })
                for v in item.values():
                    extract_images(v)
            elif isinstance(item, list):
                for elem in item:
                    extract_images(elem)

        extract_images(data)


def rank_candidates(candidates: List[Dict[str, Any]]) -> List[Dict[str, Any]]:
    """Rank candidates preferring unbranded lead hero images over watermarked social cards."""
    def score_candidate(c: Dict[str, Any]) -> int:
        score = c.get("width_hint", 0)
        url = c["url"].lower()

        # Boost in-article candidates
        if c.get("in_article"):
            score += 500

        # Boost known clean CDN patterns (e.g. BBC cpsprodpb)
        if "cpsprodpb" in url or "original" in url or "1536" in url or "1920" in url:
            score += 400

        # Penalize watermarked / social-branded cards
        if "branded_news" in url or "social" in url or "banner" in url:
            score -= 600

        return score

    # Deduplicate by normalized URL
    seen = set()
    unique = []
    for c in sorted(candidates, key=score_candidate, reverse=True):
        u = c["url"].split("?")[0].rstrip("/")
        if u not in seen:
            seen.add(u)
            unique.append(c)
    return unique


def extract_hero_image_from_article(article_url: str) -> Tuple[Image.Image, str, int, int]:
    html_bytes = fetch_url_bytes(article_url, max_bytes=MAX_HTML_BYTES)
    html_text = html_bytes.decode("utf-8", errors="replace")

    parser = ArticleImageCandidateExtractor(article_url)
    parser.feed(html_text)

    ranked = rank_candidates(parser.candidates)
    if not ranked:
        raise ValueError(f"No suitable news image candidates could be discovered at {article_url}")

    errors = []
    for cand in ranked:
        cand_url = cand["url"]
        try:
            img_bytes = fetch_url_bytes(cand_url, max_bytes=MAX_IMAGE_DOWNLOAD_BYTES)
            img = Image.open(io.BytesIO(img_bytes))
            img.load()  # Verify complete image decode
            w, h = img.size
            if w < 200 or h < 150:
                continue
            return img, cand_url, w, h
        except Exception as e:
            errors.append(f"{cand_url}: {e}")
            continue

    raise ValueError(f"Failed to load any candidate images. Errors: {'; '.join(errors[:3])}")


def load_image_from_input(input_path_or_url: str) -> Tuple[Image.Image, str, int, int]:
    if input_path_or_url.startswith("http://") or input_path_or_url.startswith("https://"):
        img_bytes = fetch_url_bytes(input_path_or_url, max_bytes=MAX_IMAGE_DOWNLOAD_BYTES)
        img = Image.open(io.BytesIO(img_bytes))
        img.load()
        return img, input_path_or_url, img.width, img.height
    else:
        if not os.path.exists(input_path_or_url):
            raise FileNotFoundError(f"Image file not found: {input_path_or_url}")
        img = Image.open(input_path_or_url)
        img.load()
        return img, input_path_or_url, img.width, img.height


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


def process_image(img: Image.Image) -> Tuple[bytes, int, int]:
    # Correct EXIF orientation before center-cropping
    img = ImageOps.exif_transpose(img)

    # Ensure RGB color mode
    if img.mode in ("RGBA", "LA", "P"):
        background = Image.new("RGB", img.size, (255, 255, 255))
        if img.mode == "P":
            img = img.convert("RGBA")
        background.paste(img, mask=img.split()[-1] if "A" in img.mode else None)
        img = background
    elif img.mode != "RGB":
        img = img.convert("RGB")

    # Center crop to 16:9
    img = crop_to_aspect_ratio(img, 16 / 9)

    # Resize to standardized dimensions (1200x675)
    w, h = img.size
    if w != TARGET_WIDTH or h != TARGET_HEIGHT:
        img = img.resize((TARGET_WIDTH, TARGET_HEIGHT), Image.Resampling.LANCZOS)

    # Compress to WebP under 100 KB with stepping down to 25 quality if necessary
    quality = 85
    step = 5
    min_quality = 25
    compressed_bytes = b""

    while quality >= min_quality:
        buf = io.BytesIO()
        img.save(buf, format="WEBP", quality=quality, method=6)
        data = buf.getvalue()
        if len(data) <= MAX_SIZE_BYTES or quality <= min_quality:
            compressed_bytes = data
            break
        quality -= step

    if not compressed_bytes:
        buf = io.BytesIO()
        img.save(buf, format="WEBP", quality=min_quality, method=6)
        compressed_bytes = buf.getvalue()

    return compressed_bytes, img.width, img.height


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


def main():
    parser = argparse.ArgumentParser(
        description="Kevanger News Image Processor & WebP Optimizer (16:9, >=1200px, <100KB)"
    )
    group = parser.add_mutually_exclusive_group(required=True)
    group.add_argument("--input", "-i", type=str, help="Direct path or URL to input image")
    group.add_argument("--from-article", type=str, help="Article URL to extract lead hero image from")

    parser.add_argument("--source", "-s", type=str, default="",
                        help="Source attribution name (mandatory unless --representative is set)")
    parser.add_argument("--representative", "-r", action="store_true",
                        help="Flag if image is representative/symbolic (Wêne: Temsîlî)")
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

        webp_data, final_w, final_h = process_image(raw_img)
    except Exception as e:
        err_res = {"status": "ERROR", "error": str(e)}
        print(json.dumps(err_res, ensure_ascii=False), file=sys.stderr)
        sys.exit(1)

    upscaled = (orig_w < TARGET_WIDTH)
    warnings: List[str] = []
    if upscaled:
        warnings.append(
            f"Original image width {orig_w}px was upscaled to {TARGET_WIDTH}px (16:9)"
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
        "mime": "image/webp",
        "width": final_w,
        "height": final_h,
        "size_bytes": len(webp_data),
        "size_kb": round(len(webp_data) / 1024, 2),
        "upscaled": upscaled,
        "warnings": warnings,
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
