#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Kevanger News Image Processor & WebP Optimizer
Prepares, crops (16:9), resizes (>=1200px), and compresses news images into
WebP under 100 KB with standardized journalistic attribution (Wêne: $Source / Wêne: Temsîlî).
Outputs JSON ready for MCP create_news / update_news payload:
  {
    "image": {
      "data_base64": "...",
      "mime": "image/webp"
    },
    "credit_html_ku": "<p><strong>Wêne: $Source</strong></p>",
    "credit_html_tr": "<p><strong>Fotoğraf: $Source</strong></p>"
  }
"""

import argparse
import base64
import io
import json
import os
import sys
import urllib.request
from typing import Dict, Any, Tuple
from PIL import Image

MAX_SIZE_BYTES = 100 * 1024  # 100 KB
TARGET_WIDTH = 1200
TARGET_HEIGHT = 675  # 16:9 aspect ratio


def load_image(input_path_or_url: str) -> Image.Image:
    if input_path_or_url.startswith("http://") or input_path_or_url.startswith("https://"):
        req = urllib.request.Request(
            input_path_or_url,
            headers={"User-Agent": "Mozilla/5.0 (compatible; BulletinBot/1.0)"}
        )
        with urllib.request.urlopen(req, timeout=15) as resp:
            data = resp.read()
        return Image.open(io.BytesIO(data))
    else:
        if not os.path.exists(input_path_or_url):
            raise FileNotFoundError(f"Image file not found: {input_path_or_url}")
        return Image.open(input_path_or_url)


def crop_to_aspect_ratio(img: Image.Image, target_ratio: float = 16 / 9) -> Image.Image:
    w, h = img.size
    current_ratio = w / h

    if abs(current_ratio - target_ratio) < 0.02:
        return img

    if current_ratio > target_ratio:
        # Image is wider than 16:9 -> crop horizontal sides
        new_w = int(h * target_ratio)
        offset = (w - new_w) // 2
        return img.crop((offset, 0, offset + new_w, h))
    else:
        # Image is taller than 16:9 -> crop vertical top/bottom
        new_h = int(w / target_ratio)
        offset = (h - new_h) // 2
        return img.crop((0, offset, w, offset + new_h))


def process_image(img: Image.Image) -> Tuple[bytes, int, int]:
    # Ensure RGB
    if img.mode in ("RGBA", "LA", "P"):
        background = Image.new("RGB", img.size, (255, 255, 255))
        if img.mode == "P":
            img = img.convert("RGBA")
        background.paste(img, mask=img.split()[-1] if "A" in img.mode else None)
        img = background
    elif img.mode != "RGB":
        img = img.convert("RGB")

    # Crop to 16:9
    img = crop_to_aspect_ratio(img, 16 / 9)

    # Resize to at least TARGET_WIDTH
    w, h = img.size
    if w != TARGET_WIDTH:
        img = img.resize((TARGET_WIDTH, TARGET_HEIGHT), Image.Resampling.LANCZOS)

    # Compress to WebP under 100 KB
    quality = 85
    step = 5
    min_quality = 40
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
    if is_representative or not source.strip():
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
    parser = argparse.ArgumentParser(description="Process news image to WebP with journalistic attribution")
    parser.add_argument("--input", "-i", type=str, required=True, help="Path or URL to input image")
    parser.add_argument("--source", "-s", type=str, default="", help="Source name (e.g. 'Amida Haber')")
    parser.add_argument("--representative", "-r", action="store_true", help="Flag if image is representative/symbolic")
    parser.add_argument("--out", "-o", type=str, default="", help="Optional output WebP file path")
    args = parser.parse_args()

    try:
        raw_img = load_image(args.input)
        webp_data, final_w, final_h = process_image(raw_img)
    except Exception as e:
        print(json.dumps({"error": str(e)}, ensure_ascii=False), file=sys.stderr)
        sys.exit(1)

    if args.out:
        os.makedirs(os.path.dirname(os.path.abspath(args.out)), exist_ok=True)
        with open(args.out, "wb") as f:
            f.write(webp_data)

    b64_str = base64.b64encode(webp_data).decode("utf-8")
    attr = build_attributions(args.source, args.representative)

    result = {
        "status": "SUCCESS",
        "mime": "image/webp",
        "width": final_w,
        "height": final_h,
        "size_bytes": len(webp_data),
        "size_kb": round(len(webp_data) / 1024, 2),
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


if __name__ == "__main__":
    main()
