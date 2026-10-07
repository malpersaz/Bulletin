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
    # Standard Country and Place Names (Nave_Dewletan_u_Cihan.md)
    "turkiye": "Turkî",
    "tirkiye": "Turkî",
    "yunanistan": "Yewnan",
    "yunan": "Yewnan",
    "yûnan": "Yewnan",
    "rusya": "Ûrisya",
    "rûsya": "Ûrisya",
    "suriye": "Sûrî",
    "sûriye": "Sûrî",
    "irak": "Îraq",
    "iraq": "Îraq",
    "londra": "London",
    "fransa": "Frensa",
    "erbîl": "Hewlêr",
    "erbil": "Hewlêr",
    "diyarbekir": "Amed",
    "diyarbakir": "Amed",
    "diyarbakır": "Amed",
    "i̇stanbul": "Stenbol",
    "istanbul": "Stenbol",
    "hesekê": "Hisiça",
    "heseke": "Hisiça",
    "qsd": "HSD",
    "moskow": "Mosqow",
    "moskova": "Mosqow",
    "mosco": "Mosqow",
    "kiev": "Kyîv",
    "kyiv": "Kyîv",
    "kîev": "Kyîv",
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

KURMANJI_MONTHS = {
    "Rêbendan": {"en": "January", "tr": "Ocak", "ar": "كانون الثاني"},
    "Reşemî": {"en": "February", "tr": "Şubat", "ar": "شباط"},
    "Adar": {"en": "March", "tr": "Mart", "ar": "آذار"},
    "Avrêl": {"en": "April", "tr": "Nisan", "ar": "نيسان"},
    "Gulan": {"en": "May", "tr": "Mayıs", "ar": "أيار"},
    "Pûşper": {"en": "June", "tr": "Haziran", "ar": "حزيران"},
    "Tîrmeh": {"en": "July", "tr": "Temmuz", "ar": "تموز"},
    "Gelawêj": {"en": "August", "tr": "Ağustos", "ar": "آب"},
    "Rezber": {"en": "September", "tr": "Eylül", "ar": "أيلول"},
    "Kewçêr": {"en": "October", "tr": "Ekim", "ar": "تشرين الأول"},
    "Sermawez": {"en": "November", "tr": "Kasım", "ar": "تشرين الثاني"},
    "Berfanbar": {"en": "December", "tr": "Aralık", "ar": "كانون الأول"},
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

    def check_vowels_before_y(self, text: str) -> List[Dict[str, Any]]:
        # Strip HTML tags and URLs
        clean_text = re.sub(r'<[^>]+>', ' ', text)
        clean_text = re.sub(r'https?://\S+', ' ', clean_text)
        issues = []
        # Find 'îy' or 'êy' within words (not across apostrophes like DYE'yê or 3'yê)
        matches = list(re.finditer(r"\b[a-zA-ZçêîşûÇÊÎŞÛ]*([îê]y)[a-zA-ZçêîşûÇÊÎŞÛ]*\b", clean_text, re.IGNORECASE))
        for m in matches:
            word = m.group(0)
            corrected = re.sub(r'îy', 'iy', word, flags=re.IGNORECASE)
            corrected = re.sub(r'êy', 'iy', corrected, flags=re.IGNORECASE)
            issues.append({
                "type": "invalid_vowel_before_y",
                "matched": word,
                "suggestion": corrected,
                "message": "Vowels 'î' and 'ê' must become 'i' before 'y' (e.g. 'iy', never 'îy' or 'êy')."
            })
        return issues

    def check_day_names(self, text: str) -> List[Dict[str, Any]]:
        clean_text = re.sub(r'<[^>]+>', ' ', text)
        clean_text = re.sub(r'https?://\S+', ' ', clean_text)
        issues = []
        # Match lowercase day names (yekşem, duşem, sêşem, çarşem, pêncşem, în, şemî/şemi)
        days_regex = r'\b(yekşem|duşem|sêşem|çarşem|pêncşem|în|şemî|şemi)(?:y?ê|y?a|y?an|y?ên|ê|a|an)?\b'
        for m in re.finditer(days_regex, clean_text):
            issues.append({
                "type": "lowercase_day_name",
                "matched": m.group(0),
                "suggestion": m.group(0).capitalize(),
                "message": "Names of days must be capitalized (Yekşem, Duşem, Sêşem, Çarşem, Pêncşem, În, Şemî)."
            })
        return issues

    def check_disallowed_terms(self, text: str) -> List[Dict[str, Any]]:
        issues = []
        clean_text = re.sub(r'<[^>]+>', ' ', text)
        clean_text = re.sub(r'https?://\S+', ' ', clean_text)
        text_lower = clean_text.lower()
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

    def check_quotes(self, text: str) -> List[Dict[str, Any]]:
        issues = []
        guillemets = list(re.finditer(r'[«»]', text))
        if guillemets:
            issues.append({
                "type": "disallowed_french_guillemets",
                "message": "French guillemets (« ... ») are disallowed. Strictly use standard double quotes (\" ... \").",
                "count": len(guillemets),
                "positions": [m.start() for m in guillemets[:5]]
            })
        return issues

    def check_preposition_bo(self, text: str) -> List[Dict[str, Any]]:
        issues = []
        clean_text = re.sub(r'<[^>]+>', ' ', text)
        clean_text = re.sub(r'https?://\S+', ' ', clean_text)
        matches = list(re.finditer(r'(?<!\bji\s)\bbo\b', clean_text, re.IGNORECASE))
        if matches:
            issues.append({
                "type": "disallowed_bare_bo",
                "message": "Never use bare 'bo' alone as preposition. Strictly use 'ji bo'.",
                "count": len(matches),
                "positions": [m.start() for m in matches[:5]]
            })
        return issues

    def check_month_annotations(self, html_text: str) -> List[Dict[str, Any]]:
        """
        Audit that any Kurdish month names in HTML content are properly enclosed
        within <span class="month-popover">...</span>.
        Verbose data attributes (data-month-*) are NOT required because the frontend engine
        (month-popover.js) automatically resolves multilingual translations via its built-in catalog.
        """
        issues = []
        popover_pattern = re.compile(r'<span\s+[^>]*class=["\'][^"\']*month-popover[^"\']*["\'][^>]*>(.*?)</span>', re.IGNORECASE | re.DOTALL)

        # Replace all month-popover spans with neutral placeholder to inspect unannotated months
        stripped_for_months = popover_pattern.sub(' __MONTH_POPOVER_PLACEHOLDER__ ', html_text)
        # Strip all remaining HTML tags
        clean_text = re.sub(r'<[^>]+>', ' ', stripped_for_months)

        # Look for raw/unannotated month names
        month_names_regex = r'\b(Rêbendan|Reşemî|Adar|Avrêl|Gulan|Pûşper|Tîrmeh|Gelawêj|Rezber|Kewçêr|Sermawez|Berfanbar)(?:y?ê|y?a|y?an|y?ên)?\b'
        for m in re.finditer(month_names_regex, clean_text, re.IGNORECASE):
            raw_match = m.group(0)
            canonical = m.group(1).capitalize()
            for k in KURMANJI_MONTHS.keys():
                if k.lower() == canonical.lower():
                    canonical = k
                    break
            issues.append({
                "type": "unannotated_month",
                "month": raw_match,
                "canonical": canonical,
                "suggestion": f'<span class="month-popover">{canonical}</span>'
            })

        return issues

    def check_seo_slug(self, seo: str) -> List[Dict[str, Any]]:
        """
        Audit SEO slug for Amed Framework Wikipedia-style parity (AGENTS.md Rule 10).
        Requires underscores ('_'), zero hyphens ('-'), and preservation of Kurdish diacritics.
        """
        issues = []
        if not seo:
            return issues
        
        # Rule 1: No hyphens/dashes allowed (must use underscores)
        if '-' in seo:
            issues.append({
                "type": "disallowed_hyphen",
                "slug": seo,
                "reason": "Amed Framework Wikipedia-style URLs strictly require underscores ('_') instead of hyphens ('-').",
                "suggestion": seo.replace('-', '_')
            })
        
        # Rule 2: No consecutive underscores
        if '__' in seo:
            issues.append({
                "type": "consecutive_underscores",
                "slug": seo,
                "reason": "Slug contains consecutive underscores ('__').",
                "suggestion": re.sub(r'_+', '_', seo)
            })

        # Rule 3: Check for English/ASCII-mangled common Kurmanji tokens where diacritics were stripped
        mangled_patterns = [
            (r'\bsingale\b', 'şingalê / şingal'),
            (r'\bderiye\b', 'deriyê'),
            (r'\bte_vekirin\b', 'tê_vekirin'),
            (r'\bcapa\b', 'çapa'),
            (r'\bpirtuken\b', 'pirtûkên'),
            (r'\bkurmanci\b', 'kurmancî'),
            (r'\bvekirine\b', 'vekirinê'),
            (r'\bxweberbijerkirine\b', 'xweberbijêrkirinê'),
        ]
        for pattern, replacement in mangled_patterns:
            if re.search(pattern, seo, re.IGNORECASE):
                issues.append({
                    "type": "mangled_kurdish_character",
                    "slug": seo,
                    "matched": pattern,
                    "reason": f"Kurdish diacritics appear to have been ASCII-stripped. Prefer authentic '{replacement}'.",
                    "suggestion": "Preserve authentic Kurdish alphabet diacritics in Wikipedia-style slugs."
                })

        return issues

    def audit(self, text: str, summary: str = None, seo: str = None) -> Dict[str, Any]:
        forbidden = self.check_forbidden_chars(text)
        conjunction = self.check_conjunction(text)
        apostrophes = self.check_apostrophe_conventions(text)
        vocabulary = self.check_disallowed_terms(text)
        quotes = self.check_quotes(text)
        bare_bo = self.check_preposition_bo(text)
        vowel_y_issues = self.check_vowels_before_y(text)
        day_issues = self.check_day_names(text)
        lexicon = self.check_lexicon(text)
        
        # Check month annotations if HTML tags are detected
        month_issues = []
        if "<" in text and ">" in text:
            month_issues = self.check_month_annotations(text)
            
        seo_issues = self.check_seo_slug(seo) if seo else []
        
        deck_report = None
        if summary is not None:
            deck_report = self.check_deck_summary(summary)

        is_clean = (
            len(forbidden) == 0 and
            len(conjunction) == 0 and
            len(apostrophes) == 0 and
            len(vocabulary) == 0 and
            len(quotes) == 0 and
            len(bare_bo) == 0 and
            len(vowel_y_issues) == 0 and
            len(day_issues) == 0 and
            len(month_issues) == 0 and
            len(seo_issues) == 0 and
            (deck_report is None or deck_report["valid"])
        )

        return {
            "passed": is_clean,
            "forbidden_characters": forbidden,
            "conjunction_issues": conjunction,
            "apostrophe_issues": apostrophes,
            "vocabulary_issues": vocabulary,
            "quote_issues": quotes,
            "bare_bo_issues": bare_bo,
            "vowel_before_y_issues": vowel_y_issues,
            "day_name_issues": day_issues,
            "month_issues": month_issues,
            "seo_issues": seo_issues,
            "deck_summary": deck_report,
            "lexicon_audit": lexicon
        }

def main():
    parser = argparse.ArgumentParser(description="Kevheng Kurmanji Linguistic & Orthographic Auditor")
    parser.add_argument("--file", "-f", help="Path to text or HTML file to audit")
    parser.add_argument("--summary", "-s", help="Optional deck summary string to audit (90-120 chars)")
    parser.add_argument("--seo", help="Optional SEO slug to audit for Amed Framework Wikipedia-style parity")
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
    report = validator.audit(content, summary=args.summary, seo=args.seo)

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

        if report["quote_issues"]:
            print(f"\n[!] Quotation Mark Issues ({len(report['quote_issues'])}):")
            for item in report["quote_issues"]:
                print(f"  - {item['message']}")

        if report["bare_bo_issues"]:
            print(f"\n[!] Preposition Issue ({len(report['bare_bo_issues'])}):")
            for item in report["bare_bo_issues"]:
                print(f"  - {item['message']}")

        if report["month_issues"]:
            print(f"\n[!] Month Annotation Issues ({len(report['month_issues'])}):")
            for item in report["month_issues"]:
                print(f"  - Month '{item['month']}' is unannotated. Required: {item['suggestion']}")

        if report["seo_issues"]:
            print(f"\n[!] SEO Slug Issues ({len(report['seo_issues'])}):")
            for item in report["seo_issues"]:
                print(f"  - {item['reason']} (Suggestion: {item['suggestion']})")

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
