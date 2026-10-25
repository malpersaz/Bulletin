#!/usr/bin/env python3
"""
Bundle-Aware Minified CSS Guard & String-Safe Deterministic Minifier
Amed Framework — Bulletin CMS

Enforces rule-block parity (`missing-in-min = 0`, `stale-in-min = 0`) between
source stylesheets and tracked `.min.css` bundles, detects orphan `.min.css`
artifacts, and provides a string-safe stdlib `--build` mode.
"""

from __future__ import annotations

import argparse
import json
import re
import sys
from pathlib import Path
from typing import Dict, List, Set, Tuple

ROOT_DIR = Path(__file__).resolve().parent.parent

BUNDLE_MANIFEST: Dict[str, List[str]] = {
    "templates/amida/static/css/custom.min.css": [
        "templates/amida/static/css/custom.css",
        "templates/amida/static/css/stacked-top-menu.css",
    ],
    "templates/amida/static/css/hero-news-slider.min.css": [
        "templates/amida/static/css/hero-news-slider.css",
    ],
}

VENDOR_EXEMPT: Set[str] = {
    "bootstrap.min.css",
}

TOKEN_RE = re.compile(
    r"""('(?:\\.|[^'\\])*'|"(?:\\.|[^"\\])*")|(/\*[\s\S]*?\*/)""",
    re.DOTALL,
)


def strip_comments_preserve_strings(css: str) -> Tuple[str, List[str]]:
    """Strip CSS comments while replacing quoted strings with placeholders."""
    strings: List[str] = []

    def _repl(match: re.Match) -> str:
        quoted = match.group(1)
        if quoted is not None:
            idx = len(strings)
            strings.append(quoted)
            return f"__CSS_STR_{idx}__"
        return ""

    cleaned = TOKEN_RE.sub(_repl, css)
    return cleaned, strings


def restore_strings(css: str, strings: List[str]) -> str:
    """Restore quoted string placeholders."""
    for idx, val in enumerate(strings):
        css = css.replace(f"__CSS_STR_{idx}__", val)
    return css


def minify_css(css: str) -> str:
    """String-safe deterministic CSS minifier matching repository .min.css format."""
    cleaned, strings = strip_comments_preserve_strings(css)
    cleaned = re.sub(r"\s+", " ", cleaned)
    cleaned = re.sub(r"\s*([{};:,])\s*", r"\1", cleaned)
    cleaned = cleaned.replace(";}", "}")
    cleaned = cleaned.strip()
    return restore_strings(cleaned, strings)


def normalize_ws(text: str, strings: List[str]) -> str:
    """Normalize whitespace and punctuation outside quoted strings."""
    norm = re.sub(r"\s+", " ", text).strip()
    norm = re.sub(r"\s*([,:;>+~()])\s*", r"\1", norm)
    return restore_strings(norm, strings)


def parse_rule_blocks(css: str) -> Set[Tuple[str, str, Tuple[str, ...]]]:
    """
    Parse CSS into a set of (at_rule_context, normalized_selector, sorted_declarations).
    Supports nested @media / @supports blocks without colliding with top-level selectors.
    """
    cleaned, strings = strip_comments_preserve_strings(css)
    blocks: Set[Tuple[str, str, Tuple[str, ...]]] = set()

    n = len(cleaned)
    i = 0
    context_stack: List[str] = []

    while i < n:
        brace_open = cleaned.find("{", i)
        if brace_open == -1:
            break

        header_raw = cleaned[i:brace_open].strip()
        # Handle closing braces that occurred before brace_open
        while "}" in header_raw:
            close_pos = header_raw.find("}")
            if context_stack:
                context_stack.pop()
            header_raw = header_raw[close_pos + 1 :].strip()

        header = normalize_ws(header_raw, strings)

        # Determine if this block is an enclosing at-rule (@media, @supports, @keyframes)
        # by peeking ahead to see if another '{' appears before the matching '}'.
        next_open = cleaned.find("{", brace_open + 1)
        next_close = cleaned.find("}", brace_open + 1)

        if header.startswith("@") and next_open != -1 and (next_close == -1 or next_open < next_close):
            context_stack.append(header)
            i = brace_open + 1
            continue

        if next_close == -1:
            break

        body_raw = cleaned[brace_open + 1 : next_close]
        decls: List[str] = []
        for part in body_raw.split(";"):
            part_clean = normalize_ws(part, strings)
            if part_clean:
                decls.append(part_clean)

        at_ctx = " :: ".join(context_stack)
        if header and decls:
            blocks.add((at_ctx, header, tuple(sorted(decls))))

        i = next_close + 1

    return blocks


def find_orphan_min_files() -> List[str]:
    """Find any *.min.css under templates/*/static/css/ not in BUNDLE_MANIFEST or VENDOR_EXEMPT."""
    orphans: List[str] = []
    for path in sorted(ROOT_DIR.glob("templates/*/static/css/*.min.css")):
        if path.name in VENDOR_EXEMPT:
            continue
        rel = path.relative_to(ROOT_DIR).as_posix()
        if rel not in BUNDLE_MANIFEST:
            orphans.append(rel)
    return orphans


def build_bundles() -> int:
    """Regenerate all configured .min.css bundles from their source files."""
    for target_rel, source_rels in BUNDLE_MANIFEST.items():
        chunks: List[str] = []
        for src_rel in source_rels:
            src_path = ROOT_DIR / src_rel
            if not src_path.is_file():
                print(f"[ERROR] Missing CSS source file: {src_rel}", file=sys.stderr)
                return 1
            chunks.append(minify_css(src_path.read_text(encoding="utf-8")))

        target_path = ROOT_DIR / target_rel
        combined = "".join(chunks)
        target_path.write_text(combined, encoding="utf-8")
        print(f"[BUILT] {target_rel} ({len(combined)} bytes) <- {', '.join(source_rels)}")
    return 0


def check_bundles(as_json: bool = False) -> int:
    """Verify all configured .min.css bundles against their source files."""
    report = {
        "status": "ok",
        "orphans": find_orphan_min_files(),
        "bundles": {},
    }
    has_error = bool(report["orphans"])

    for target_rel, source_rels in BUNDLE_MANIFEST.items():
        target_path = ROOT_DIR / target_rel
        if not target_path.is_file():
            report["bundles"][target_rel] = {
                "error": f"Missing target file: {target_rel}",
            }
            has_error = True
            continue

        source_blocks: Set[Tuple[str, str, Tuple[str, ...]]] = set()
        missing_sources: List[str] = []
        for src_rel in source_rels:
            src_path = ROOT_DIR / src_rel
            if not src_path.is_file():
                missing_sources.append(src_rel)
                continue
            source_blocks |= parse_rule_blocks(src_path.read_text(encoding="utf-8"))

        if missing_sources:
            report["bundles"][target_rel] = {
                "error": f"Missing source files: {', '.join(missing_sources)}",
            }
            has_error = True
            continue

        min_blocks = parse_rule_blocks(target_path.read_text(encoding="utf-8"))
        missing_in_min = sorted(
            f"{ctx + ' -> ' if ctx else ''}{sel}"
            for (ctx, sel, _) in (source_blocks - min_blocks)
        )
        stale_in_min = sorted(
            f"{ctx + ' -> ' if ctx else ''}{sel}"
            for (ctx, sel, _) in (min_blocks - source_blocks)
        )

        if missing_in_min or stale_in_min:
            has_error = True

        report["bundles"][target_rel] = {
            "sources": source_rels,
            "source_rules": len(source_blocks),
            "min_rules": len(min_blocks),
            "missing_in_min_count": len(missing_in_min),
            "stale_in_min_count": len(stale_in_min),
            "missing_in_min": missing_in_min[:20],
            "stale_in_min": stale_in_min[:20],
        }

    if has_error:
        report["status"] = "failed"

    if as_json:
        print(json.dumps(report, indent=2))
    else:
        if report["orphans"]:
            print(f"[FAIL] Orphan .min.css files found: {', '.join(report['orphans'])}")
        for target_rel, info in report["bundles"].items():
            if "error" in info:
                print(f"[FAIL] {target_rel}: {info['error']}")
                continue
            status_tag = (
                "OK"
                if info["missing_in_min_count"] == 0 and info["stale_in_min_count"] == 0
                else "FAIL"
            )
            print(
                f"[{status_tag}] {target_rel}: {info['min_rules']}/{info['source_rules']} rule blocks "
                f"(missing_in_min={info['missing_in_min_count']}, stale_in_min={info['stale_in_min_count']})"
            )

    return 1 if has_error else 0


def main() -> int:
    parser = argparse.ArgumentParser(description="Bundle-aware .min.css guard and builder.")
    parser.add_argument("--build", action="store_true", help="Regenerate .min.css bundles from source CSS.")
    parser.add_argument("--check", action="store_true", help="Verify .min.css bundles against source CSS (default).")
    parser.add_argument("--json", action="store_true", help="Emit JSON output in check mode.")
    args = parser.parse_args()

    if args.build:
        rc = build_bundles()
        if rc != 0:
            return rc
    return check_bundles(as_json=args.json)


if __name__ == "__main__":
    sys.exit(main())
