#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Kevseo Google News, Discover & Semantic HTML SEO Auditor
Audits Turkish news content against semantic HTML mandates, heading hierarchies,
content length thresholds, E-E-A-T citation depth, Wikipedia-style SEO slugs, and snippet standards.
"""

import sys
import os
import re
import argparse
from typing import List, Dict, Any

class KevseoAuditor:
    def __init__(self):
        pass

    def audit_content_length(self, html_text: str) -> Dict[str, Any]:
        # Strip HTML to count visible characters and words
        plain = re.sub(r'<[^>]+>', ' ', html_text)
        plain = re.sub(r'\s+', ' ', plain).strip()
        char_count = len(plain)
        words = plain.split()
        word_count = len(words)

        status = "PASSED"
        msg = "Content length meets professional SEO standards."
        if char_count < 1500:
            status = "FAILED"
            msg = f"Thin content detected ({char_count} chars, {word_count} words). Minimum required is 1,500 chars."
        elif char_count < 2500:
            status = "WARNING"
            msg = f"Acceptable for short breaking news ({char_count} chars, {word_count} words), but recommended 2,500+ chars for Google Top Stories."

        return {
            "status": status,
            "char_count": char_count,
            "word_count": word_count,
            "message": msg
        }

    def audit_html_architecture(self, html_text: str, strict: bool = False) -> List[Dict[str, Any]]:
        issues = []

        # 1. No <h1> inside content
        if re.search(r'<h1[^>]*>', html_text, re.IGNORECASE):
            issues.append({
                "type": "disallowed_h1",
                "severity": "CRITICAL",
                "message": "Content must not contain <h1> tags. Article title is already rendered as <h1> by the template."
            })

        # 2. Heading Hierarchy & Minimum <h2> subheadings
        h2_matches = re.findall(r'<h2[^>]*>(.*?)</h2>', html_text, re.IGNORECASE | re.DOTALL)
        h2_count = len(h2_matches)

        if strict:
            if h2_count < 3:
                issues.append({
                    "type": "insufficient_h2",
                    "severity": "CRITICAL",
                    "message": f"Editorial standards require at least 3 <h2> thematic subheadings (found {h2_count})."
                })
        else:
            if h2_count == 0:
                issues.append({
                    "type": "missing_h2",
                    "severity": "WARNING",
                    "message": "Content lacks <h2> thematic subheadings. Google News heavily rewards structured sections with descriptive subheadings."
                })
            elif h2_count < 3:
                issues.append({
                    "type": "insufficient_h2",
                    "severity": "WARNING",
                    "message": f"Content has only {h2_count} <h2> subheading(s). At least 3 <h2> thematic subheadings are recommended for Google News / Discover."
                })

        # Heading Level Jumps (No skipping heading levels: e.g. <h2> -> <h4>)
        headings = [int(m.group(1)) for m in re.finditer(r'<h([1-6])\b[^>]*>', html_text, re.IGNORECASE)]
        if headings:
            if headings[0] > 2:
                issues.append({
                    "type": "heading_level_jump",
                    "severity": "CRITICAL",
                    "message": f"First heading in content is <h{headings[0]}>. News content must begin with <h2> before nested subsections."
                })
            for i in range(1, len(headings)):
                prev = headings[i - 1]
                curr = headings[i]
                if curr > prev + 1:
                    issues.append({
                        "type": "heading_level_jump",
                        "severity": "CRITICAL",
                        "message": f"Invalid heading hierarchy: jumped from <h{prev}> to <h{curr}> without intermediate <h{prev + 1}>."
                    })

        # 3. Paragraph Limits & Chunking (warning if > 5 sentences or > 90 words)
        p_matches = re.finditer(r'<p\b[^>]*>(.*?)</p>', html_text, re.IGNORECASE | re.DOTALL)
        for idx, match in enumerate(p_matches, start=1):
            p_html = match.group(1)
            plain_p = re.sub(r'<[^>]+>', ' ', p_html)
            plain_p = re.sub(r'\s+', ' ', plain_p).strip()
            if not plain_p:
                continue
            words = plain_p.split()
            word_count = len(words)

            cleaned_p = re.sub(r'\b\d+[\.,]\d+\b', '', plain_p)
            cleaned_p = re.sub(r'\b(?:Dr|Prof|Doç|Av|vb|vs|bkz|ör|m|km|sn|dk|sa|No|st|cad|mah)\.', '', cleaned_p, flags=re.IGNORECASE)
            sentences = [s.strip() for s in re.split(r'[.!?…]+(?:\s+|$)', cleaned_p) if s.strip()]
            sentence_count = len(sentences)

            if sentence_count > 5 or word_count > 90:
                issues.append({
                    "type": "dense_paragraph",
                    "severity": "WARNING",
                    "message": f"Paragraph #{idx} is too dense ({word_count} words, {sentence_count} sentences). Keep paragraphs under 5 sentences and 90 words for mobile readability."
                })

        # 4. Check for inline styles and scripts (AGENTS.md Rule 1)
        if re.search(r'\sstyle=[\'"][^\'"]*[\'"]', html_text, re.IGNORECASE):
            issues.append({
                "type": "inline_style_violation",
                "severity": "CRITICAL",
                "message": "Strict Code Separation (AGENTS.md Rule 1): Inline style='...' attributes are forbidden in news HTML."
            })

        if re.search(r'<script[^>]*>', html_text, re.IGNORECASE):
            issues.append({
                "type": "inline_script_violation",
                "severity": "CRITICAL",
                "message": "Strict Code Separation (AGENTS.md Rule 1): Inline <script> tags are forbidden in news HTML."
            })

        if re.search(r'\son[a-z]+=[\'"][^\'"]*[\'"]', html_text, re.IGNORECASE):
            issues.append({
                "type": "inline_event_handler",
                "severity": "CRITICAL",
                "message": "Strict Code Separation (AGENTS.md Rule 1): Inline event handlers (e.g. onclick) are forbidden."
            })

        # 5. Outbound links / citations
        links = re.findall(r'<a\s+[^>]*href=[\'"]([^\'"]+)[\'"][^>]*>(.*?)</a>', html_text, re.IGNORECASE)
        if len(links) < 1:
            issues.append({
                "type": "missing_citations",
                "severity": "WARNING",
                "message": "Content has no outbound citations. At least 1-2 primary source authority links are required for E-E-A-T."
            })

        # 6. Image attribution (Turkish: Fotoğraf: ... / Görsel: ..., Kurdish: Wêne: ...)
        if not re.search(r'<strong>\s*(?:Fotoğraf|Görsel|Wêne)\s*:\s*[^<]+</strong>', html_text, re.IGNORECASE):
            issues.append({
                "type": "missing_image_attribution",
                "severity": "NOTICE",
                "message": "Image attribution line ('Wêne: ...' or 'Fotoğraf: ...') not found at the bottom of the article."
            })

        # 7. Disallowed French guillemets
        if re.search(r'[«»]', html_text):
            issues.append({
                "type": "disallowed_guillemets",
                "severity": "CRITICAL",
                "message": "French guillemets («...») are forbidden. Use standard double quotes (\"...\")."
            })

        return issues

    def audit_seo_slug(self, seo: str) -> List[Dict[str, Any]]:
        issues = []
        if not seo:
            return issues
        if '-' in seo:
            issues.append({
                "type": "disallowed_hyphen",
                "severity": "CRITICAL",
                "message": f"Wikipedia-style SEO slug must use underscores ('_'), never hyphens ('-'): {seo.replace('-', '_')}"
            })
        if '__' in seo:
            issues.append({
                "type": "consecutive_underscores",
                "severity": "CRITICAL",
                "message": "SEO slug contains consecutive underscores ('__')."
            })
        return issues

    def audit_headline_and_deck(
        self,
        headline: str,
        summary: str,
        description: str = "",
        strict: bool = False
    ) -> Dict[str, Any]:
        h_len = len(headline.strip()) if headline else 0
        s_len = len(summary.strip()) if summary else 0
        d_len = len(description.strip()) if description else 0

        h_valid = (40 <= h_len <= 70)
        s_valid = (90 <= s_len <= 120)

        desc_provided = bool(description and description.strip())
        desc_synced = (description.strip() == summary.strip()) if (desc_provided and summary) else False
        d_valid = (90 <= d_len <= 120) and desc_synced

        if headline:
            h_status = "PASSED" if h_valid else "FAILED"
        else:
            h_status = "FAILED" if strict else "EMPTY"

        if summary:
            s_status = "PASSED" if s_valid else "FAILED"
        else:
            s_status = "FAILED" if strict else "EMPTY"

        if desc_provided:
            d_status = "PASSED" if d_valid else "FAILED"
        else:
            d_status = "FAILED" if strict else "EMPTY"

        if strict:
            meta_valid = h_valid and s_valid and d_valid
        else:
            fail_h = bool(headline and not h_valid)
            fail_s = bool(summary and not s_valid)
            fail_d = bool(desc_provided and not d_valid)
            meta_valid = not (fail_h or fail_s or fail_d)

        return {
            "headline_length": h_len,
            "headline_status": h_status,
            "summary_length": s_len,
            "deck_valid": s_valid,
            "deck_status": s_status,
            "description_length": d_len,
            "description_status": d_status,
            "description_synced": desc_synced,
            "meta_valid": meta_valid
        }

    def run_full_audit(
        self,
        html_text: str,
        summary: str = "",
        headline: str = "",
        seo: str = "",
        description: str = "",
        strict: bool = False
    ) -> Dict[str, Any]:
        length_res = self.audit_content_length(html_text)
        arch_issues = self.audit_html_architecture(html_text, strict=strict) + self.audit_seo_slug(seo)
        meta_res = self.audit_headline_and_deck(headline, summary, description=description, strict=strict)

        critical_count = sum(1 for i in arch_issues if i.get("severity") == "CRITICAL")
        has_failed_length = (length_res.get("status") == "FAILED")
        has_failed_meta = not meta_res.get("meta_valid")

        passed = (critical_count == 0 and not has_failed_length and not has_failed_meta)

        return {
            "status": "PASSED" if passed else "FAILED",
            "length_analysis": length_res,
            "architecture_issues": arch_issues,
            "meta_analysis": meta_res
        }

def main():
    parser = argparse.ArgumentParser(description="Kevseo Google SEO & Semantic HTML Auditor")
    parser.add_argument("--content", type=str, help="HTML content string")
    parser.add_argument("--file", type=str, help="Path to file containing HTML content")
    parser.add_argument("--summary", type=str, default="", help="Deck summary text")
    parser.add_argument("--headline", type=str, default="", help="News headline")
    parser.add_argument("--description", type=str, default="", help="Meta description text (synchronized with summary)")
    parser.add_argument("--seo", type=str, default="", help="Optional Wikipedia-style SEO slug")
    parser.add_argument("--strict-editorial", action="store_true", help="Enforce strict editorial rubric parity (required headline, summary, description sync, >=3 h2)")
    parser.add_argument("--json", action="store_true", help="Output raw JSON")
    args = parser.parse_args()

    content = ""
    if args.file:
        if os.path.exists(args.file):
            with open(args.file, "r", encoding="utf-8") as f:
                content = f.read()
        else:
            print(f"Error: file {args.file} not found.", file=sys.stderr)
            sys.exit(1)
    elif args.content:
        content = args.content
    else:
        content = sys.stdin.read()

    auditor = KevseoAuditor()
    results = auditor.run_full_audit(
        content,
        summary=args.summary,
        headline=args.headline,
        seo=args.seo,
        description=args.description,
        strict=args.strict_editorial
    )

    if args.json:
        import json
        print(json.dumps(results, indent=2, ensure_ascii=False))
        sys.exit(0 if results["status"] == "PASSED" else 1)

    print("=" * 60)
    print("KEVSEO: Google News, Discover & Semantic HTML SEO Report")
    print("=" * 60)
    print(f"Status: {results['status']}")
    print()
    len_info = results["length_analysis"]
    print(f"[Content Depth] Visible Characters: {len_info['char_count']} | Words: {len_info['word_count']}")
    print(f"  Note: {len_info['message']}")
    print()

    meta_info = results["meta_analysis"]
    if meta_info["summary_length"] > 0:
        print(f"[Deck Summary] Length: {meta_info['summary_length']} chars (Min: 90, Max: 120) -> {meta_info['deck_status']}")
    if meta_info.get("description_length", 0) > 0:
        print(f"[Meta Description] Length: {meta_info['description_length']} chars (Synced: {meta_info['description_synced']}) -> {meta_info['description_status']}")
    if meta_info["headline_length"] > 0:
        print(f"[Headline] Length: {meta_info['headline_length']} chars -> {meta_info['headline_status']}")
    print()

    arch_issues = results["architecture_issues"]
    if arch_issues:
        print(f"[Semantic Architecture Feedback] ({len(arch_issues)} items):")
        for issue in arch_issues:
            print(f"  [{issue['severity']}] {issue['type']}: {issue['message']}")
    else:
        print("[Semantic Architecture Feedback] Clean! Valid heading structure and code separation.")
    print("=" * 60)

    sys.exit(0 if results["status"] == "PASSED" else 1)

if __name__ == "__main__":
    main()
