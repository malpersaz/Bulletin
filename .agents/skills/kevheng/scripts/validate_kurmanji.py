#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Kevheng Kurmanji Linguistic & Orthographic Validator
Audits Kurmanji text and news articles against ferheng_kurmanci.db (1.7M+ entries),
grammatical rules, mandatory terminology, and deck character constraints.
"""

import sys
import os
import re
import json
import sqlite3
import argparse
from typing import List, Dict, Set, Tuple, Any

OCR_DIR = "/home/malpersaz/Desktop/projects/ocr"
DB_PATH = os.path.join(OCR_DIR, "ferheng_kurmanci.db")

# Add OCR_DIR to path for KurmanjiMorphology
if os.path.exists(OCR_DIR):
    sys.path.append(OCR_DIR)

try:
    from kurmanji_morphology import KurmanjiMorphology, AUTHOR_SPECIAL_WORDS
    morphology = KurmanjiMorphology()
except Exception:
    morphology = None
    AUTHOR_SPECIAL_WORDS = set()

RULES_DIR = os.path.join(os.path.dirname(os.path.dirname(os.path.abspath(__file__))), "rules")
VOCAB_RULES_FILE = os.path.join(RULES_DIR, "disallowed_vocabulary.json")

# Default disallowed terms and preferred alternatives
DISALLOWED_TERMS = {
    "lêbelê": "lê",
    "lê belê": "lê",
    "qaîm": "qewîm",
    "qaim": "qewîm",
    "qaîde": "qayîde",
    "qaide": "qayîde",
    "derbarê": "li ser",
    "der barê": "li ser",
    "derheqê": "li ser",
    "der heqê": "li ser",
    "digel": "bi ... re",
    "di gel": "bi ... re",
    "update": "nûjenkirin",
    "nûve": "nûjenkirin",
    "kirtandin": "tikandin",
    "kirtîne": "bitikîne",
    "keritandin": "tikandin",
    "zêde bike": "lêke / lê zêde bike",
    "hişê çêkirî": "aqilê çêkirî",
    "veavakirin": "mîhengkirin",
    "pêvajokirin": "verîşandin",
    "pêvajoya daneyan": "verîşîna daneyan",
    "fêrbûna makîneyê": "hînbûna makîneyê",
    "fêrbûna kûr": "hînbûna kûr",
    "damezrandin": "sazkirin / avakirin",
    "wesayît": "wesayet",
}

if os.path.exists(VOCAB_RULES_FILE):
    try:
        with open(VOCAB_RULES_FILE, "r", encoding="utf-8") as f:
            custom_terms = json.load(f)
            if isinstance(custom_terms, dict):
                DISALLOWED_TERMS.update(custom_terms)
    except Exception as e:
        print(f"Warning: could not load {VOCAB_RULES_FILE}: {e}", file=sys.stderr)


FORBIDDEN_CHARS = {
    'ı': 'i / î',
    'İ': 'I / Î',
    'ö': 'o',
    'Ö': 'O',
    'ü': 'u / û',
    'Ü': 'U / Û',
    'ğ': 'x',
    'Ğ': 'X',
}

class KevhengValidator:
    def __init__(self, db_path: str = DB_PATH):
        self.db_path = db_path
        self.conn = None
        if os.path.exists(self.db_path):
            try:
                self.conn = sqlite3.connect(self.db_path)
            except Exception as e:
                print(f"Warning: could not open {self.db_path}: {e}", file=sys.stderr)

    def is_word_in_db(self, word: str) -> bool:
        if not self.conn:
            return False
        cur = self.conn.cursor()
        cur.execute("SELECT 1 FROM words WHERE word = ? LIMIT 1", (word,))
        return cur.fetchone() is not None

    def check_forbidden_chars(self, text: str) -> List[Dict[str, Any]]:
        # Strip HTML tags and URLs so HTML attributes do not trigger false positives
        clean_text = re.sub(r'<[^>]+>', ' ', text)
        clean_text = re.sub(r'https?://\S+', ' ', clean_text)
        issues = []
        for char, repl in FORBIDDEN_CHARS.items():
            if char in clean_text:
                matches = [m.start() for m in re.finditer(re.escape(char), clean_text)]
                issues.append({
                    "type": "forbidden_character",
                    "character": char,
                    "recommended_replacement": repl,
                    "count": len(matches),
                    "positions": matches[:5]
                })
        return issues

    def check_conjunction(self, text: str) -> List[Dict[str, Any]]:
        issues = []
        # Find isolated 'u' or 'U' with word boundaries
        matches = list(re.finditer(r'(?<!\S)[uU](?!\S)', text))
        if matches:
            issues.append({
                "type": "invalid_conjunction",
                "message": "Conjunction 'and' in Kurmanji must strictly be 'û' (never 'u').",
                "count": len(matches),
                "positions": [m.start() for m in matches[:5]]
            })
        return issues

    def check_apostrophe_conventions(self, text: str) -> List[Dict[str, Any]]:
        issues = []
        # Numbers without apostrophe before suffixes (e.g. 14emîn, 28ê, 2026an)
        num_patterns = list(re.finditer(r'\b(\d+)(emîn|yemîn|mîn|ê|a|an|yê|ya|yan|da|de|re|ve)\b', text, re.IGNORECASE))
        for m in num_patterns:
            issues.append({
                "type": "missing_apostrophe_number",
                "matched": m.group(0),
                "suggestion": f"{m.group(1)}'{m.group(2)}",
                "position": m.start()
            })

        # Acronyms without apostrophe before suffixes (e.g. NYê, PDKê, YNKê, DYAê, DYEê, AFPê)
        # Note: Proper names (navên xwerû) like SpaceXê, Amerîkayê, Milliyetê take NO apostrophe!
        acronym_patterns = list(re.finditer(r'\b([A-Z]{2,})(ê|a|an|yê|ya|yan|da|de|re|ve)\b', text))
        for m in acronym_patterns:
            issues.append({
                "type": "missing_apostrophe_acronym",
                "matched": m.group(0),
                "suggestion": f"{m.group(1)}'{m.group(2)}",
                "position": m.start()
            })
        return issues

    def check_disallowed_terms(self, text: str) -> List[Dict[str, Any]]:
        issues = []
        text_lower = text.lower()
        for term, preferred in DISALLOWED_TERMS.items():
            if " " in term:
                pattern = r'\b' + re.escape(term) + r'\b'
            else:
                # Match exact term or term with common Kurmanji suffixes (e.g. qaîdeyên, qaîmî)
                pattern = r'\b' + re.escape(term) + r'(?:y?ê|y?a|y?an|y?ên|y?ek|y?a|î|tir|tî)?\b'
            matches = list(re.finditer(pattern, text_lower))
            if matches:
                issues.append({
                    "type": "disallowed_vocabulary",
                    "term": term,
                    "preferred": preferred,
                    "matched_instances": [m.group(0) for m in matches[:5]],
                    "count": len(matches)
                })
        return issues

    def check_deck_summary(self, summary: str) -> Dict[str, Any]:
        char_len = len(summary)
        valid = 90 <= char_len <= 120
        return {
            "valid": valid,
            "length": char_len,
            "min_required": 90,
            "max_required": 120,
            "delta": 0 if valid else (90 - char_len if char_len < 90 else char_len - 120)
        }

    def check_lexicon(self, text: str) -> Dict[str, Any]:
        # Strip HTML tags
        clean_text = re.sub(r'<[^>]+>', ' ', text)
        clean_text = re.sub(r'https?://\S+', ' ', clean_text)
        
        # Tokenize words
        tokens = re.findall(r"[a-zA-ZçêîşûÇÊÎŞÛ']+", clean_text)
        total_tokens = len(tokens)
        checked_words = set()
        recognized_words = set()
        unknown_words = set()

        for raw_token in tokens:
            w = raw_token.strip("'").lower()
            if not w or w.isdigit() or len(w) <= 1:
                continue
            if w in checked_words:
                continue
            checked_words.add(w)

            # Check morphology or DB
            if morphology and w in AUTHOR_SPECIAL_WORDS:
                recognized_words.add(w)
                continue
            if morphology and morphology.is_valid(w):
                recognized_words.add(w)
                continue
            if self.is_word_in_db(w):
                recognized_words.add(w)
                continue

            unknown_words.add(w)

        return {
            "total_tokens": total_tokens,
            "unique_words": len(checked_words),
            "recognized_count": len(recognized_words),
            "unknown_count": len(unknown_words),
            "unknown_words": sorted(list(unknown_words))
        }

    def audit(self, text: str, summary: str = None) -> Dict[str, Any]:
        forbidden = self.check_forbidden_chars(text)
        conjunction = self.check_conjunction(text)
        apostrophes = self.check_apostrophe_conventions(text)
        vocabulary = self.check_disallowed_terms(text)
        lexicon = self.check_lexicon(text)
        
        deck_report = None
        if summary is not None:
            deck_report = self.check_deck_summary(summary)

        is_clean = (
            len(forbidden) == 0 and
            len(conjunction) == 0 and
            len(apostrophes) == 0 and
            len(vocabulary) == 0 and
            (deck_report is None or deck_report["valid"])
        )

        return {
            "passed": is_clean,
            "forbidden_characters": forbidden,
            "conjunction_issues": conjunction,
            "apostrophe_issues": apostrophes,
            "vocabulary_issues": vocabulary,
            "deck_summary": deck_report,
            "lexicon_audit": lexicon
        }

def main():
    parser = argparse.ArgumentParser(description="Kevheng Kurmanji Linguistic & Orthographic Auditor")
    parser.add_argument("--file", "-f", help="Path to text or HTML file to audit")
    parser.add_argument("--summary", "-s", help="Optional deck summary string to audit (90-120 chars)")
    parser.add_argument("--text", "-t", help="Raw text string to audit")
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

    validator = KevhengValidator()
    report = validator.audit(content, summary=args.summary)

    if args.json:
        print(json.dumps(report, ensure_ascii=False, indent=2))
    else:
        print("=" * 60)
        print("KEVHENG: Kurmanji Linguistic & Orthographic Audit Report")
        print("=" * 60)
        print(f"Status: {'PASSED' if report['passed'] else 'ATTENTION NEEDED'}\n")

        if report["deck_summary"]:
            ds = report["deck_summary"]
            print(f"[Deck Summary] Length: {ds['length']} chars (Min: 90, Max: 120) -> {'OK' if ds['valid'] else 'INVALID'}")

        if report["forbidden_characters"]:
            print(f"\n[!] Forbidden Characters Found ({len(report['forbidden_characters'])}):")
            for item in report["forbidden_characters"]:
                print(f"  - '{item['character']}' ({item['count']} times) -> Suggestion: {item['recommended_replacement']}")

        if report["conjunction_issues"]:
            print(f"\n[!] Conjunction 'u' Found:")
            print("  - Use 'û' strictly instead of 'u'.")

        if report["apostrophe_issues"]:
            print(f"\n[!] Missing Apostrophes ({len(report['apostrophe_issues'])}):")
            for item in report["apostrophe_issues"]:
                print(f"  - {item['matched']} -> {item['suggestion']}")

        if report["vocabulary_issues"]:
            print(f"\n[!] Disallowed Vocabulary ({len(report['vocabulary_issues'])}):")
            for item in report["vocabulary_issues"]:
                print(f"  - '{item['term']}' -> Prefer '{item['preferred']}'")

        lex = report["lexicon_audit"]
        print(f"\n[Lexicon Statistics]")
        print(f"  - Total tokens: {lex['total_tokens']}")
        print(f"  - Unique words: {lex['unique_words']}")
        print(f"  - Recognized words: {lex['recognized_count']}")
        print(f"  - Unknown / Proper names / Neologisms: {lex['unknown_count']}")
        if lex["unknown_words"]:
            print(f"  - Sample unknown words (inspect for typos): {lex['unknown_words'][:15]}")
        print("=" * 60)

    if not report["passed"]:
        sys.exit(1)

if __name__ == "__main__":
    main()
