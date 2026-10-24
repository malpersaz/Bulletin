#!/usr/bin/env python3
"""
Bulletin Curator Content & SEO Quality Guard (100-Point Rubric)
Audits CMS records (mod_news, mod_publication, mod_page, mod_announcements)
against the Four-Zone Semantic HTML, Metadata Sync, Slug Governance,
Active-Language Parity, and Code Separation standards.
"""

import argparse
import html
import json
import re
import subprocess
import sys
from pathlib import Path
from typing import Any, Dict, List, Optional, Tuple

MODULE_ALIASES = {
    "news": "mod_news",
    "mod_news": "mod_news",
    "publication": "mod_publication",
    "mod_publication": "mod_publication",
    "page": "mod_page",
    "mod_page": "mod_page",
    "announcement": "mod_announcements",
    "announcements": "mod_announcements",
    "mod_announcements": "mod_announcements",
}

MODULES_WITH_SEO_META = {
    "mod_news",
    "mod_publication",
    "mod_page",
}


def fetch_record_via_php(module: str, record_id: Optional[int], slug: Optional[str], lang: Optional[str]) -> Dict[str, Any]:
    php_code = r"""
    error_reporting(0);
    ini_set('display_errors', '0');
    require_once 'init.php';
    $module = $argv[1] ?? '';
    $id = (int) ($argv[2] ?? 0);
    $slug = (string) ($argv[3] ?? '');
    $lang = (string) ($argv[4] ?? '');
    $db = Load::lib('Database');
    $safeMod = preg_replace('/[^a-z0-9_]/', '', $module);
    $where = [];
    if ($id > 0) {
        $where[] = "id = " . $id;
    } elseif ($slug !== '') {
        $where[] = "seo = '" . Obuilder::cc($slug) . "'";
    }
    if ($lang !== '') {
        $where[] = "lang = '" . Obuilder::cc($lang) . "'";
    }
    $whereSql = !empty($where) ? ('WHERE ' . implode(' AND ', $where)) : '';
    $db->fetch_array("SELECT * FROM `{$safeMod}` {$whereSql} ORDER BY id DESC LIMIT 1");
    $rows = $db->toArray();
    $item = $rows[0] ?? null;
    $faqCount = 0;
    $faqItems = [];
    $langStatus = 'n';
    if (is_array($item)) {
        $itemLang = Obuilder::cc((string) ($item['lang'] ?? 'en'));
        $itemId = (int) ($item['id'] ?? 0);
        $db->fetch_array("SELECT status FROM `lang` WHERE seo = '{$itemLang}' LIMIT 1");
        $langRows = $db->toArray();
        $langStatus = (string) ($langRows[0]['status'] ?? 'n');
        if ($safeMod === 'mod_announcements') {
            $db->fetch_array("SELECT id, question, answer FROM `mod_faq` WHERE mod_name = 'mod_announcements' AND vid IN (0, {$itemId}) AND lang = '{$itemLang}' AND status = 'y'");
        } else {
            $db->fetch_array("SELECT id, question, answer FROM `mod_faq` WHERE mod_name = '{$safeMod}' AND vid = {$itemId} AND lang = '{$itemLang}' AND status = 'y'");
        }
        $faqRows = $db->toArray();
        $faqItems = is_array($faqRows) ? $faqRows : [];
        $faqCount = count($faqItems);
    }
    echo json_encode(['item' => $item, 'faq_count' => $faqCount, 'faq_items' => $faqItems, 'lang_status' => $langStatus], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    """

    args_list = [
        module,
        str(record_id or 0),
        slug or "",
        lang or "",
    ]

    # Try local php if running inside container, otherwise docker exec bulletin_app
    commands = []
    if Path("/.dockerenv").exists():
        commands.append(["php", "-r", php_code, "--"] + args_list)
    else:
        commands.append(["docker", "exec", "bulletin_app", "php", "-r", php_code, "--"] + args_list)
        commands.append(["php", "-r", php_code, "--"] + args_list)

    last_err = ""
    for cmd in commands:
        try:
            proc = subprocess.run(cmd, capture_output=True, text=True, timeout=15)
            if proc.returncode == 0 and proc.stdout.strip():
                data = json.loads(proc.stdout.strip())
                if isinstance(data, dict) and "item" in data:
                    if data.get("item"):
                        return data
                    raise RuntimeError(f"Record not found in {module} (query args: id={record_id}, slug={slug}, lang={lang})")
            else:
                last_err = proc.stderr.strip() or proc.stdout.strip()
        except RuntimeError:
            raise
        except Exception as exc:
            last_err = str(exc)

    raise RuntimeError(last_err or f"Failed to fetch record from {module}")


def strip_html_text(raw_html: str) -> str:
    cleaned = re.sub(r"<(script|style)\b[^>]*>.*?</\1>", " ", raw_html, flags=re.IGNORECASE | re.DOTALL)
    cleaned = re.sub(r"<[^>]+>", " ", cleaned)
    cleaned = html.unescape(cleaned)
    cleaned = re.sub(r"\s+", " ", cleaned).strip()
    return cleaned


KURMANJI_STOPWORDS = {
    "ji", "bo", "yên", "derbarê", "hatiye", "wekî", "ligel",
    "navbera", "piştî", "berî", "xwe", "herî", "dê", "bû",
    "li", "ku", "ev", "wê", "wan",
}

TURKISH_STOPWORDS = {
    "için", "olarak", "tarafından", "olan", "birlikte", "göre",
    "ancak", "sonrası", "öncesi", "hakkında", "ilgili", "arasında",
    "üzerine", "kapsamında", "nedeniyle", "konusunda", "yapılan",
    "edildi", "olduğu",
}


def detect_language_mismatch(target_lang: str, text: str) -> Optional[str]:
    lang = target_lang.strip().lower()
    if not lang or not text:
        return None
    lower = text.lower()
    words = set(re.findall(r"[^\W\d_]+", lower, flags=re.UNICODE))

    if lang not in ("ku", "ckb", "zza"):
        ku_chars = len(re.findall(r"[êîû]", lower))
        ku_words = len(words.intersection(KURMANJI_STOPWORDS))
        if ku_chars >= 2 and ku_words >= 2:
            return f"Linguistic mismatch: record.lang is '{lang}', but text contains Kurmanji Kurdish ('ku') prose."

    if lang != "tr":
        tr_chars = len(re.findall(r"[ğı]", lower)) + len(re.findall(r"İ", text))
        tr_words = len(words.intersection(TURKISH_STOPWORDS))
        if tr_chars >= 2 and tr_words >= 2:
            return f"Linguistic mismatch: record.lang is '{lang}', but text contains Turkish ('tr') prose."

    return None


def evaluate_record(
    module: str,
    record: Dict[str, Any],
    faq_count: int,
    lang_status: str = "y",
    faq_items: Optional[List[Dict[str, Any]]] = None,
) -> Tuple[int, List[Dict[str, Any]], List[str]]:
    checks: List[Dict[str, Any]] = []
    critical_violations: List[str] = []

    record_lang = str(record.get("lang") or "").strip()
    if lang_status != "y":
        critical_violations.append(
            f"Record language '{record_lang}' is not active in the lang table (lang.status = '{lang_status}')."
        )

    content_html = str(record.get("content") or "")
    combined_html = content_html
    plain_text = strip_html_text(content_html)
    plain_len = len(plain_text)

    record_prose = f"{record.get('name', '')} {record.get('summary', '')} {plain_text}"
    prose_mismatch = detect_language_mismatch(record_lang, record_prose)
    if prose_mismatch:
        critical_violations.append(prose_mismatch)

    for faq_row in (faq_items or []):
        faq_text = strip_html_text(f"{faq_row.get('question', '')} {faq_row.get('answer', '')}")
        faq_mismatch = detect_language_mismatch(record_lang, faq_text)
        if faq_mismatch:
            critical_violations.append(f"mod_faq #{faq_row.get('id')}: {faq_mismatch}")

    # -------------------------------------------------------------------------
    # Category 1: Editorial Depth & Grounding (25 pts)
    # -------------------------------------------------------------------------
    min_chars = 600 if module == "mod_announcements" else 2000
    if plain_len >= min_chars:
        depth_pts = 18
        depth_msg = f"Plain-text body length is {plain_len} chars (target >= {min_chars})."
    elif plain_len >= int(min_chars * 0.6):
        depth_pts = 10
        depth_msg = f"Plain-text body length is {plain_len} chars (partial credit; target >= {min_chars})."
    else:
        depth_pts = 0
        depth_msg = f"Plain-text body length is {plain_len} chars (below minimum {min_chars})."
    checks.append({"category": "Depth & Grounding", "points": depth_pts, "max": 18, "detail": depth_msg})

    paragraphs = re.findall(r"<p\b[^>]*>(.*?)</p>", content_html, flags=re.IGNORECASE | re.DOTALL)
    substantive_p = [strip_html_text(p) for p in paragraphs if len(strip_html_text(p)) >= 80]
    p_target = 2 if module == "mod_announcements" else 4
    p_pts = 7 if len(substantive_p) >= p_target else (4 if len(substantive_p) >= 2 else 0)
    checks.append({
        "category": "Substantive Paragraphs",
        "points": p_pts,
        "max": 7,
        "detail": f"Found {len(substantive_p)} substantive <p> blocks (>= 80 chars each; target >= {p_target}).",
    })

    # -------------------------------------------------------------------------
    # Category 2: Four-Zone Semantic HTML Structure (25 pts)
    # -------------------------------------------------------------------------
    has_lead_strong = bool(
         paragraphs and re.search(r"<strong\b[^>]*>.{20,}?</strong>", paragraphs[0], flags=re.IGNORECASE | re.DOTALL)
    )
    lead_pts = 6 if has_lead_strong else 0
    checks.append({
        "category": "Zone 1: Answer-First Lead",
        "points": lead_pts,
        "max": 6,
        "detail": "Opening paragraph contains a <strong> lead definition." if has_lead_strong else "Opening <p> is missing a <strong> answer-first lead.",
    })

    h2_matches = re.findall(r"<h2\b[^>]*>(.*?)</h2>", content_html, flags=re.IGNORECASE | re.DOTALL)
    h3_matches = re.findall(r"<h3\b[^>]*>(.*?)</h3>", content_html, flags=re.IGNORECASE | re.DOTALL)
    h1_matches = re.findall(r"<h1\b[^>]*>", combined_html, flags=re.IGNORECASE)
    if module == "mod_announcements":
        heading_pts = 7 if len(h2_matches) >= 1 and len(h1_matches) == 0 else 0
    else:
        if len(h2_matches) >= 3 and len(h3_matches) >= 1 and len(h1_matches) == 0:
            heading_pts = 7
        elif len(h2_matches) >= 2 and len(h1_matches) == 0:
            heading_pts = 4
        else:
            heading_pts = 0
    checks.append({
        "category": "Zone 2: Heading Hierarchy",
        "points": heading_pts,
        "max": 7,
        "detail": f"Found {len(h2_matches)} <h2>, {len(h3_matches)} <h3>, and {len(h1_matches)} <h1> tags.",
    })

    li_matches = re.findall(r"<li\b[^>]*>", combined_html, flags=re.IGNORECASE)
    list_pts = 4 if len(li_matches) >= 3 else (2 if len(li_matches) >= 1 else 0)
    checks.append({
        "category": "Zone 2: Structured Lists",
        "points": list_pts,
        "max": 4,
        "detail": f"Found {len(li_matches)} <li> items across body.",
    })

    has_table = bool(
        re.search(r"<table\b", combined_html, flags=re.IGNORECASE)
        and re.search(r"<thead\b", combined_html, flags=re.IGNORECASE)
        and re.search(r"<tbody\b", combined_html, flags=re.IGNORECASE)
    )
    has_blockquote = bool(re.search(r"<blockquote\b", combined_html, flags=re.IGNORECASE))
    if module == "mod_announcements":
        z3_pts = 8 if (has_table or has_blockquote or len(li_matches) >= 3) else 0
    else:
        z3_pts = (5 if has_table else 0) + (3 if has_blockquote else 0)
    checks.append({
        "category": "Zone 3: Table & Callout",
        "points": z3_pts,
        "max": 8,
        "detail": f"Semantic <table>: {'yes' if has_table else 'no'}, <blockquote>: {'yes' if has_blockquote else 'no'}.",
    })

    # -------------------------------------------------------------------------
    # Category 3: Metadata & Dual-Field Synchronization (20 pts)
    # -------------------------------------------------------------------------
    summary = str(record.get("summary") or "").strip()
    summary_len = len(summary)
    if 90 <= summary_len <= 160:
        sum_pts = 8
        sum_detail = f"summary length is {summary_len} chars (within 90..160)."
    else:
        sum_pts = 0
        sum_detail = f"summary length is {summary_len} chars (expected 90..160)."
        critical_violations.append(sum_detail)
    checks.append({"category": "Summary Length (90-160)", "points": sum_pts, "max": 8, "detail": sum_detail})

    if module in MODULES_WITH_SEO_META:
        description = str(record.get("description") or "").strip()
        desc_len = len(description)
        desc_synced = (90 <= desc_len <= 160) and (description == summary)
        desc_pts = 5 if desc_synced else (3 if 90 <= desc_len <= 160 else 0)
        checks.append({
            "category": "Meta Description Sync",
            "points": desc_pts,
            "max": 5,
            "detail": f"description length is {desc_len} chars, synced with summary: {'yes' if description == summary else 'no'}.",
        })

        title = str(record.get("title") or "").strip()
        title_len = len(title)
        title_pts = 4 if 20 <= title_len <= 70 else (2 if title_len > 0 else 0)
        checks.append({
            "category": "SEO Title",
            "points": title_pts,
            "max": 4,
            "detail": f"title length is {title_len} chars ('{title}').",
        })

        keywords = [k.strip() for k in str(record.get("keywords") or "").split(",") if k.strip()]
        kw_pts = 3 if len(keywords) >= 3 else (1 if len(keywords) >= 1 else 0)
        checks.append({
            "category": "Meta Keywords",
            "points": kw_pts,
            "max": 3,
            "detail": f"Found {len(keywords)} comma-separated keywords.",
        })
    else:
        name_val = str(record.get("name") or "").strip()
        ann_meta_pts = 12 if (10 <= len(name_val) <= 70 and 90 <= summary_len <= 160) else 6
        checks.append({
            "category": "Announcement Name & Deck Meta",
            "points": ann_meta_pts,
            "max": 12,
            "detail": f"Announcement name length is {len(name_val)} chars (1-70 allowed).",
        })

    # -------------------------------------------------------------------------
    # Category 4: Wikipedia-Style Slug, Internal Links & Native FAQ (15 pts)
    # -------------------------------------------------------------------------
    if module in MODULES_WITH_SEO_META:
        seo = str(record.get("seo") or "").strip()
        valid_slug = bool(seo and " " not in seo and "/" not in seo)
        slug_pts = 6 if valid_slug else 0
        checks.append({
            "category": "Wikipedia-Style Slug",
            "points": slug_pts,
            "max": 6,
            "detail": f"seo slug is '{seo}'." if valid_slug else "Invalid or empty seo slug.",
        })
    else:
        checks.append({
            "category": "Announcement Route Compliance",
            "points": 6,
            "max": 6,
            "detail": "mod_announcements uses ID/deck routing (no seo column).",
        })

    links = re.findall(r"<a\b[^>]*href=[\"']([^\"']+)[\"'][^>]*>", combined_html, flags=re.IGNORECASE)
    link_pts = 5 if len(links) >= 1 else 0
    checks.append({
        "category": "Zone 4: Contextual Links",
        "points": link_pts,
        "max": 5,
        "detail": f"Found {len(links)} contextual link(s) in HTML.",
    })

    faq_pts = 4 if faq_count > 0 else 0
    checks.append({
        "category": "Zone 4: Native mod_faq Coverage",
        "points": faq_pts,
        "max": 4,
        "detail": f"Found {faq_count} active FAQ record(s) in mod_faq for lang='{record.get('lang', '')}'.",
    })

    # -------------------------------------------------------------------------
    # Category 5: Strict Code Separation & Security Compliance (15 pts)
    # -------------------------------------------------------------------------
    inline_styles = re.findall(r"\bstyle\s*=\s*([\"']).*?\1", combined_html, flags=re.IGNORECASE | re.DOTALL)
    if len(inline_styles) == 0:
        style_pts = 5
    else:
        style_pts = 0
        critical_violations.append(f"Found {len(inline_styles)} forbidden inline style=\"...\" attribute(s) (AGENTS.md Rule 2).")
    checks.append({
        "category": "Zero Inline Styles",
        "points": style_pts,
        "max": 5,
        "detail": f"Inline style attributes found: {len(inline_styles)}.",
    })

    script_or_style_tags = re.findall(r"<\s*(script|style)\b", combined_html, flags=re.IGNORECASE)
    inline_events = re.findall(r"\bon[a-z]+\s*=\s*([\"']).*?\1", combined_html, flags=re.IGNORECASE | re.DOTALL)
    if len(script_or_style_tags) == 0 and len(inline_events) == 0:
        sec_pts = 5
    else:
        sec_pts = 0
        critical_violations.append("Found forbidden <script>/<style> tags or inline event handlers in HTML.")
    checks.append({
        "category": "Zero Script/Style/Event Tags",
        "points": sec_pts,
        "max": 5,
        "detail": f"Script/style tags: {len(script_or_style_tags)}, inline events: {len(inline_events)}.",
    })

    if len(h1_matches) == 0:
        h1_pts = 5
    else:
        h1_pts = 0
        critical_violations.append(f"Found {len(h1_matches)} forbidden <h1> tag(s) inside body HTML.")
    checks.append({
        "category": "Zero Stray <h1> Tags",
        "points": h1_pts,
        "max": 5,
        "detail": f"Stray <h1> tags in body: {len(h1_matches)}.",
    })

    total_score = sum(c["points"] for c in checks)
    return total_score, checks, critical_violations


def main() -> int:
    parser = argparse.ArgumentParser(description="Audit CMS content/SEO quality against the 100-Point Bulletin Curator Rubric.")
    parser.add_argument("--module", required=True, help="Target module (e.g. mod_news, news, publication, page, announcement)")
    parser.add_argument("--id", type=int, default=None, help="Record ID")
    parser.add_argument("--slug", type=str, default=None, help="Record SEO slug")
    parser.add_argument("--lang", type=str, default=None, help="Language code (e.g. tr, ku, en)")
    parser.add_argument("--min-score", type=int, default=85, help="Minimum passing score (default: 85)")
    parser.add_argument("--json", action="store_true", help="Output result as JSON")
    args = parser.parse_args()

    module = MODULE_ALIASES.get(args.module.strip().lower(), "")
    if not module:
        print(f"[ERROR] Unsupported module '{args.module}'.", file=sys.stderr)
        return 2

    if not args.id and not args.slug:
        print("[ERROR] Provide either --id or --slug.", file=sys.stderr)
        return 2

    try:
        payload = fetch_record_via_php(module, args.id, args.slug, args.lang)
    except Exception as exc:
        print(f"[ERROR] {exc}", file=sys.stderr)
        return 2

    record = payload["item"]
    faq_count = int(payload.get("faq_count", 0))
    lang_status = str(payload.get("lang_status", "y"))
    faq_items = payload.get("faq_items") or []
    score, checks, violations = evaluate_record(module, record, faq_count, lang_status, faq_items)
    passed = (score >= args.min_score) and (len(violations) == 0)

    if args.json:
        print(json.dumps({
            "passed": passed,
            "score": score,
            "min_score": args.min_score,
            "module": module,
            "id": int(record.get("id", 0)),
            "lang": record.get("lang", ""),
            "name": record.get("name", ""),
            "seo": record.get("seo", ""),
            "checks": checks,
            "critical_violations": violations,
        }, indent=2, ensure_ascii=False))
        return 0 if passed else 1

    print("=" * 72)
    print(f"Bulletin Curator SEO & Content Audit: {module} #{record.get('id')} ({record.get('lang')})")
    print(f"Name : {record.get('name')}")
    if "seo" in record:
        print(f"Slug : {record.get('seo')}")
    print("-" * 72)
    for chk in checks:
        status = "PASS" if chk["points"] == chk["max"] else ("PARTIAL" if chk["points"] > 0 else "FAIL")
        print(f"[{status:7s}] ({chk['points']:2d}/{chk['max']:2d}) {chk['category']}: {chk['detail']}")
    print("-" * 72)
    if violations:
        print("CRITICAL VIOLATIONS:")
        for v in violations:
            print(f"  - {v}")
        print("-" * 72)
    verdict = "PASSED" if passed else "FAILED"
    print(f"FINAL SCORE: {score}/100 (Minimum Required: {args.min_score}/100) -> {verdict}")
    print("=" * 72)
    return 0 if passed else 1


if __name__ == "__main__":
    sys.exit(main())
