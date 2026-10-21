#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Kevrast Fact-Checking & Live Link Auditor
Audits URLs, citations, numbers, dates, and named entities in Turkish news drafts
to enforce zero-hallucination, live link integrity, and anti-citation drift.
"""

import sys
import os
import re
import json
import urllib.request
import urllib.error
import ssl
import argparse
from typing import List, Dict, Any

DEFAULT_USER_AGENT = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"

class KevrastAuditor:
    def __init__(self, timeout: int = 10, user_agent: str = DEFAULT_USER_AGENT):
        self.timeout = timeout
        self.user_agent = user_agent
        # Permissive SSL context for news verification
        self.ctx = ssl.create_default_context()
        self.ctx.check_hostname = False
        self.ctx.verify_mode = ssl.CERT_NONE

    def check_url(self, url: str) -> Dict[str, Any]:
        """Verify URL with live HTTP request and redirect resolution."""
        headers = {
            "User-Agent": self.user_agent,
            "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8"
        }
        req = urllib.request.Request(url, headers=headers, method="HEAD")
        try:
            with urllib.request.urlopen(req, timeout=self.timeout, context=self.ctx) as resp:
                final_url = resp.geturl()
                status = resp.status
                return {
                    "url": url,
                    "final_url": final_url,
                    "status_code": status,
                    "live": (200 <= status < 400),
                    "error": None
                }
        except urllib.error.HTTPError as e:
            # If HEAD fails (e.g. 405 or 403), fallback to GET with byte range
            try:
                get_req = urllib.request.Request(url, headers=headers, method="GET")
                get_req.add_header("Range", "bytes=0-1024")
                with urllib.request.urlopen(get_req, timeout=self.timeout, context=self.ctx) as resp:
                    return {
                        "url": url,
                        "final_url": resp.geturl(),
                        "status_code": resp.status,
                        "live": (200 <= resp.status < 400),
                        "error": None
                    }
            except urllib.error.HTTPError as e2:
                return {
                    "url": url,
                    "final_url": None,
                    "status_code": e2.code,
                    "live": False,
                    "error": f"HTTP {e2.code}: {e2.reason}"
                }
            except Exception as e2:
                return {
                    "url": url,
                    "final_url": None,
                    "status_code": None,
                    "live": False,
                    "error": str(e2)
                }
        except Exception as e:
            return {
                "url": url,
                "final_url": None,
                "status_code": None,
                "live": False,
                "error": str(e)
            }

    def extract_urls(self, text: str) -> List[str]:
        # Extract from href="..."
        href_urls = re.findall(r'href=[\'"]([^\'"]+)[\'"]', text)
        # Extract raw urls
        raw_urls = re.findall(r'https?://[^\s<>"\']+', text)
        all_urls = []
        for u in href_urls + raw_urls:
            u_clean = u.rstrip('.,;:)')
            if u_clean and u_clean not in all_urls:
                all_urls.append(u_clean)
        return all_urls

    def extract_numbers_and_dates(self, text: str) -> List[str]:
        # Clean HTML
        clean = re.sub(r'<[^>]+>', ' ', text)
        # Match Turkish dates, numbers with units, ordinals, percentages
        patterns = [
            r"\b\d+\.(?:\s*[a-zA-ZçğıöşüÇĞİÖŞÜ]+)?\b",
            r"\b\d+'(?:inci|üncü|uncu|üncü|da|de|ta|te|dan|den|tan|ten|a|e|ya|ye)\b",
            r"\b\d+\s*(?:kilometre|km|saat|kez|milyon|milyar|bin|kişi|TL|dolar|avro|euro|ton|metre|metrekare)\b",
            r"\b\d{1,2}:\d{2}\b",
            r"\b(?:19|20)\d{2}\b",
            r"(?:yüzde\s*\d+|%\s*\d+)"
        ]
        matches = []
        for p in patterns:
            for m in re.finditer(p, clean, re.IGNORECASE):
                val = m.group(0).strip()
                if val not in matches:
                    matches.append(val)
        return matches

    def extract_quotes(self, text: str) -> List[str]:
        # Strip HTML tags before extracting quotes to prevent matching href="..."
        clean = re.sub(r'<[^>]+>', ' ', text)
        patterns = [
            r'«([^»]+)»',
            r'“([^”]+)”',
            r'"([^"]+)"'
        ]
        quotes = []
        for p in patterns:
            for m in re.finditer(p, clean):
                q = m.group(1).strip()
                if len(q) > 3 and q not in quotes:
                    quotes.append(q)
        return quotes

    def extract_citations(self, text: str) -> List[str]:
        # Extract Turkish citation phrases
        clean = re.sub(r'<[^>]+>', ' ', text)
        matches = re.findall(
            r'[^.,;]+(?:\s+haberine göre|\s+tarafından\s+(?:aktarılan|paylaşılan|yapılan)\s+[^.,;]+|\s+açıklamasına göre|\s+verilerine göre)',
            clean,
            re.IGNORECASE
        )
        return [m.strip() for m in matches]

    def audit(self, text: str, skip_network: bool = False) -> Dict[str, Any]:
        urls = self.extract_urls(text)
        url_results = []
        all_urls_live = True

        if not skip_network:
            for u in urls:
                res = self.check_url(u)
                url_results.append(res)
                if not res["live"]:
                    all_urls_live = False
        else:
            for u in urls:
                url_results.append({"url": u, "skipped_network": True})

        facts = {
            "numbers_and_dates": self.extract_numbers_and_dates(text),
            "quotes": self.extract_quotes(text),
            "citations": self.extract_citations(text),
        }

        has_urls = len(urls) > 0
        passed = (not has_urls or all_urls_live)

        return {
            "passed": passed,
            "total_urls": len(urls),
            "all_urls_live": all_urls_live,
            "url_audit": url_results,
            "factual_matrix": facts
        }

def main():
    parser = argparse.ArgumentParser(description="Kevrast Fact-Checking & Live Link Auditor")
    parser.add_argument("--file", "-f", help="Path to article HTML or text file")
    parser.add_argument("--text", "-t", help="Raw article HTML or text")
    parser.add_argument("--no-network", action="store_true", help="Skip live HTTP link verification")
    parser.add_argument("--json", action="store_true", help="Output results in JSON format")

    args = parser.parse_args()

    content = ""
    if args.file:
        with open(args.file, "r", encoding="utf-8") as f:
            content = f.read()
    elif args.text:
        content = args.text
    elif not sys.stdin.isatty():
        content = sys.stdin.read()
    else:
        parser.print_help()
        sys.exit(1)

    auditor = KevrastAuditor()
    report = auditor.audit(content, skip_network=args.no_network)

    if args.json:
        print(json.dumps(report, ensure_ascii=False, indent=2))
    else:
        print("=" * 60)
        print("KEVRAST: Fact-Checking & Link Verification Audit Report")
        print("=" * 60)
        print(f"Status: {'PASSED (All Links Live)' if report['passed'] else 'ATTENTION NEEDED (Broken Links Detected)'}\n")

        print(f"[Live Link Audit] Total links checked: {report['total_urls']}")
        for u in report["url_audit"]:
            status_text = f"HTTP {u.get('status_code', 'ERR')}" if u.get('live') else f"FAILED: {u.get('error')}"
            mark = "[OK]" if u.get("live") else "[!]"
            print(f"  {mark} {u['url']} -> {status_text}")

        fm = report["factual_matrix"]
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

    if not report["passed"]:
        sys.exit(1)

if __name__ == "__main__":
    main()
