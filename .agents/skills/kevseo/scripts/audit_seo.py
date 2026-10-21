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

    def audit_html_architecture(self, html_text: str) -> List[Dict[str, Any]]:
        issues = []

        # 1. No <h1> inside content
        if re.search(r'<h1[^>]*>', html_text, re.IGNORECASE):
            issues.append({
                "type": "disallowed_h1",
                "severity": "CRITICAL",
                "message": "Content must not contain <h1> tags. Article title is already rendered as <h1> by the template."
            })

        # 2. Minimum <h2> subheadings
        h2_matches = re.findall(r'<h2[^>]*>(.*?)</h2>', html_text, re.IGNORECASE | re.DOTALL)
        if len(h2_matches) == 0:
            issues.append({
                "type": "missing_h2",
                "severity": "WARNING",
                "message": "Content lacks <h2> thematic subheadings. Google News heavily rewards structured sections with descriptive subheadings."
            })

        # 3. Check for inline styles and scripts (AGENTS.md Rule 2)
        if re.search(r'\sstyle=[\'"][^\'"]*[\'"]', html_text, re.IGNORECASE):
            issues.append({
                "type": "inline_style_violation",
                "severity": "CRITICAL",
                "message": "Strict Code Separation: Inline style='...' attributes are forbidden in news HTML."
            })

        if re.search(r'<script[^>]*>', html_text, re.IGNORECASE):
            issues.append({
                "type": "inline_script_violation",
                "severity": "CRITICAL",
                "message": "Strict Code Separation: Inline <script> tags are forbidden in news HTML."
            })

        if re.search(r'\son[a-z]+=[\'"][^\'"]*[\'"]', html_text, re.IGNORECASE):
            issues.append({
                "type": "inline_event_handler",
                "severity": "CRITICAL",
                "message": "Strict Code Separation: Inline event handlers (e.g. onclick) are forbidden."
            })

        # 4. Outbound links / citations
        links = re.findall(r'<a\s+[^>]*href=[\'"]([^\'"]+)[\'"][^>]*>(.*?)</a>', html_text, re.IGNORECASE)
        if len(links) < 1:
            issues.append({
                "type": "missing_citations",
                "severity": "WARNING",
                "message": "Content has no outbound citations. At least 1-2 primary source authority links are required for E-E-A-T."
            })

        # 5. Image attribution (Turkish: Fotoğraf: ... or Görsel: ...)
        if not re.search(r'<strong>\s*(?:Fotoğraf|Görsel)\s*:\s*[^<]+</strong>', html_text, re.IGNORECASE):
            issues.append({
                "type": "missing_image_attribution",
                "severity": "NOTICE",
                "message": "Image attribution line ('Fotoğraf: ...') not found at the bottom of the article."
            })

        # 6. Disallowed French guillemets
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

    def audit_headline_and_deck(self, headline: str, summary: str) -> Dict[str, Any]:
        h_len = len(headline) if headline else 0
        s_len = len(summary) if summary else 0

        deck_valid = (90 <= s_len <= 120)
        h_status = "PASSED" if (40 <= h_len <= 90) else ("WARNING" if h_len > 90 else "NOTICE")

        return {
            "headline_length": h_len,
            "headline_status": h_status,
            "summary_length": s_len,
            "deck_valid": deck_valid,
            "deck_status": "PASSED" if deck_valid else "FAILED"
        }

    def run_full_audit(self, html_text: str, summary: str = "", headline: str = "", seo: str = "") -> Dict[str, Any]:
        length_res = self.audit_content_length(html_text)
        arch_issues = self.audit_html_architecture(html_text) + self.audit_seo_slug(seo)
        meta_res = self.audit_headline_and_deck(headline, summary)

        critical_count = sum(1 for i in arch_issues if i.get("severity") == "CRITICAL")
        has_failed_length = (length_res.get("status") == "FAILED")
        has_failed_deck = (not meta_res.get("deck_valid") if summary else False)

        passed = (critical_count == 0 and not has_failed_length and not has_failed_deck)

        return {
            "status": "PASSED" if passed else "ATTENTION NEEDED",
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
    parser.add_argument("--seo", type=str, default="", help="Optional Wikipedia-style SEO slug")
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
    results = auditor.run_full_audit(content, args.summary, args.headline, args.seo)

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
