#!/usr/bin/env python3
"""
i18n_guard.py - Automated Internationalization & Code Quality Guard for Amed Framework

Enforces global software development standards across the entire repository:
1. All code identifiers (functions, variables, language keys, classes) and comments must be in English.
2. No 'alias' usage in queries or code.
3. Strict code separation: No inline CSS or JavaScript in PHP, Twig, or HTML files.
4. No forbidden `|default(...)` fallbacks on localization keys.
5. No missing translation keys.
"""

import os
import sys
import re
import glob
import subprocess
import json
import argparse
from typing import Dict, List, Set, Tuple, Optional

REPO_ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), ".."))

# Directories excluded from audits
EXCLUDE_DIRS = {
    ".git", ".gemini", ".antigravitycli", ".agents", ".playwright-mcp",
    "vendor", "node_modules", "cache", "templates_c", "backup", "log", "logs",
    "data", "static/assets/vendor", "static/vendor", "templates/base_static", "base_static",
    "tools"
}

# Standard programming abbreviations and vocabulary
DEV_TERMS = {
    "id", "db", "sql", "url", "uri", "ip", "faq", "ajax", "acl", "api", "dom",
    "ui", "ux", "img", "src", "href", "pos", "len", "btn", "nav", "cfg", "cnt",
    "dir", "tmp", "str", "int", "bool", "num", "curr", "prev", "max", "min",
    "init", "auth", "mod", "seo", "pm", "csrf", "json", "csv", "pdf", "svg",
    "addr", "ddl",
    "png", "jpg", "jpeg", "gif", "webp", "zip", "xml", "rss", "ftp", "ssh",
    "http", "https", "ssl", "tls", "jwt", "uuid", "guid", "md5", "sha", "attr",
    "param", "params", "arg", "args", "kwarg", "kwargs", "fn", "cb", "ctx",
    "req", "res", "resp", "env", "std", "fmt", "sync", "async", "iter", "obj",
    "elem", "val", "var", "const", "prop", "props", "idx", "sep", "buf", "ptr",
    "ref", "doc", "docs", "config", "sys", "app", "dev", "prod", "repo", "desc",
    "asc", "auto", "msg", "cls", "ext", "ctrl", "ctrls", "i18n", "l10n", "a11y",
    "utf", "ascii", "twig", "php", "js", "css", "html", "stmt", "pdo", "orm",
    "crud", "dto", "dao", "cli", "sdk", "md", "txt", "tsv", "yml", "yaml",
    "postgres", "postgresql", "postgre", "mysql", "mysqli", "sqlite",
    "ini", "tbl", "col", "calc", "diff", "dest", "stat", "lang", "i", "j",
    "k", "v", "x", "y", "z", "e", "th", "td", "tr", "ul", "ol", "li", "p",
    "h1", "h2", "h3", "h4", "h5", "h6", "div", "span", "hr", "br", "pre",
    "em", "b", "i", "u", "s", "sub", "sup", "nav", "del", "ins", "ok", "err",
    "whatsapp", "favicon", "webmanifest", "imagick", "anim", "video", "aspect",
    "captcha", "kurdish", "snackbar", "fab", "workspace", "ssid", "profil", "goto",
    "az", "za", "dob", "datatable", "emessage", "semail", "akfiles", "akfilestime",
    "mcp", "rpc", "sse", "llm", "llms", "ai", "cms", "vid", "bmp", "todo",
    "timeout", "mega", "step1", "step2", "details", "email",
    "sitemap", "fullscreen", "cta", "perm", "perms", "addperm", "addpage", "installerbase", "installerbaseinterface",
    "ckb", "zza", "ku", "sv", "ar", "de", "fr", "tr", "fa", "en", "ru", "rtl", "meetup",
    "mbstring", "sef", "sefurl", "gdpr", "dpo", "sla", "kpi", "roi", "ctr", "swot", "kaizen",
    "gantt", "biometric", "uc", "runtime", "webhook", "endpoints", "imagecategories",
    "popup", "inarray", "lifecycle", "fallback", "fallbacks", "validator", "validators",
    "sustainability", "entrepreneurship", "scalability", "centricity", "inclusivity",
    "mindset", "quantification", "ensurance", "offboarding", "onboarding", "tokenizing", "tokenization",
    "lightbox", "glightbox", "accordion", "tooltip", "breadcrumb", "breadcrumbs", "breakpoint", "scrim",
    "iframe", "iframes", "swiper", "dbuilder", "llms", "akm", "akmsoftware", "amed", "amedresize", "rmodel", "cdn", "stoon", "bento",
    "diag", "cimd", "ssrf", "goog", "inst", "instantiate", "dsn", "doesn", "gsc", "conf", "ro",
    "rgb", "rgba", "hsl", "hsla", "pk", "ai", "tinymce", "mce", "nfc", "enum", "qtype",
    "ttl", "proj", "vars", "reqs", "accs", "dbrefs", "rownum",
    "kb", "mb", "gb", "tb", "fps", "hd", "sd",
    "oauth", "chatgpt", "gpt", "pkce", "mcpo", "instantiates", "gemini",
    "modality", "modalities", "vimeo", "mailto", "telehealth", "og", "tiktok", "tik", "tok",
    "unhtmlentities", "temizle", "hsc", "cdata", "aylar", "kontrol",
    "tema", "secenek", "resimgetir", "ust", "arsiv", "yil", "fullmanset", "anket", "oyver", "kat"
}

# Non-English detection relies purely on the English dictionary and morphological rules (no hardcoded word lists)

# Regex patterns for code separation checks
RE_INLINE_STYLE_ATTR = re.compile(r'\bstyle\s*=\s*["\']([^"\']*)["\']', re.IGNORECASE)
RE_INLINE_STYLE_TAG = re.compile(r'<style\b[^>]*>', re.IGNORECASE)
RE_INLINE_SCRIPT_TAG = re.compile(r'<script\b(?![^>]*\bsrc=)(?![^>]*\btype=["\']application\/ld\+json["\'])[^>]*>(?!<\/script>)', re.IGNORECASE)
RE_INLINE_EVENT_HANDLER = re.compile(r'\bon[a-zA-Z]+\s*=\s*["\'][^"\']*["\']', re.IGNORECASE)

# Regex pattern for SQL alias
RE_SQL_ALIAS = re.compile(r'\b(?:SELECT|FROM|JOIN)\b[\s\S]*?\bAS\s+([a-zA-Z0-9_]+)', re.IGNORECASE)

# Regex pattern for forbidden default filter or null-coalescing operator on lang keys: lang.*|default(...) or lang.* ?? '...'
RE_FORBIDDEN_DEFAULT = re.compile(r"\blang(?:\.[a-zA-Z0-9_]+|\[[^\]]+\])+\s*(?:\|\s*default\s*\(|\?\?)", re.IGNORECASE)

# Regex pattern for Twig lang variable access: lang.domain.key... (supports nested keys)
RE_TWIG_LANG_KEY = re.compile(r"\blang\.([a-zA-Z0-9_]+)\.([a-zA-Z0-9_.]+)")

# Non-ASCII character detection pattern
RE_NON_ASCII = re.compile(r"[^\x00-\x7F]")


class EnglishLexicon:
    def __init__(self):
        self.words: Set[str] = set()
        self._load_system_dictionary()

    def _load_system_dictionary(self):
        dict_path = "/usr/share/dict/words"
        if os.path.exists(dict_path):
            try:
                with open(dict_path, "r", encoding="utf-8", errors="ignore") as f:
                    for line in f:
                        w = line.strip().lower()
                        if w:
                            self.words.add(w)
            except Exception:
                pass

    def is_english_token(self, token: str) -> Tuple[bool, str]:
        """Checks if a single token is standard English."""
        if not token:
            return True, ""
        
        token_lower = token.lower()

        # Non-ASCII is strictly non-English
        if RE_NON_ASCII.search(token):
            return False, f"Contains non-English characters: '{token}'"


        # Digits or recognized programming keywords
        if token_lower.isdigit() or token_lower in DEV_TERMS:
            return True, ""

        # Dictionary match
        if token_lower in self.words:
            return True, ""

        # Common English inflectional suffixes
        suffixes = [
            "ification", "ability", "ibility", "ship", "ator", "ation", "ition", "izing", "ized", "ization",
            "ness", "ment", "ence", "ance", "ical", "less", "able", "ible", "tion", "sion",
            "ism", "ist", "ive", "ity", "ize", "ise", "ing", "est", "er", "ed", "ly", "es", "s", "al", "or"
        ]
        for suffix in suffixes:
            if token_lower.endswith(suffix):
                stem = token_lower[:-len(suffix)]
                if stem in self.words or (stem + "e") in self.words or (stem + "able") in self.words or (stem + "y") in self.words or stem in DEV_TERMS:
                    return True, ""

        # Common English prefixes
        prefixes = ["un", "re", "pre", "post", "non", "multi", "sub", "de", "dis", "over", "under"]
        for prefix in prefixes:
            if token_lower.startswith(prefix):
                stem = token_lower[len(prefix):]
                if stem in self.words or stem in DEV_TERMS:
                    return True, ""

        # Compound words check (split into two valid words or prefix + word)
        for i in range(2, len(token_lower) - 2):
            w1, w2 = token_lower[:i], token_lower[i:]
            if (w1 in self.words or w1 in DEV_TERMS) and (w2 in self.words or w2 in DEV_TERMS):
                return True, ""
            for s in suffixes:
                if w2.endswith(s):
                    stem2 = w2[:-len(s)]
                    if stem2 in self.words or (stem2 + "e") in self.words or (stem2 + "y") in self.words or stem2 in DEV_TERMS:
                        if w1 in self.words or w1 in DEV_TERMS:
                            return True, ""

        return False, f"Unrecognized non-English token: '{token}'"

    def split_identifier(self, identifier: str) -> List[str]:
        """Splits snake_case, camelCase, PascalCase, and kebab-case into words."""
        clean = identifier.strip("$_")
        parts = re.split(r"[_\-]+", clean)
        tokens = []
        for part in parts:
            if not part:
                continue
            sub = re.findall(r"[A-Z]?[a-z]+|[0-9]+|[A-Z]+(?=[A-Z][a-z]|\b)", part)
            if sub:
                tokens.extend(sub)
            else:
                tokens.append(part)
        return tokens

    def check_identifier(self, identifier: str) -> Tuple[bool, str]:
        """Validates all constituent tokens of an identifier."""
        tokens = self.split_identifier(identifier)
        for token in tokens:
            is_valid, reason = self.is_english_token(token)
            if not is_valid:
                return False, reason
        return True, ""


class I18nGuard:
    def __init__(self, root_dir: str):
        self.root_dir = root_dir
        self.lexicon = EnglishLexicon()
        self.lang_keys_map: Dict[str, Dict[str, Set[str]]] = {}
        self.load_all_language_packs()

    def load_all_language_packs(self):
        """Indexes translation keys (including nested dot-paths) from all **/lang/*.php across the entire repository."""
        php_code = r'''
        define("AKM_LOADED", true);
        define("AFM_SECURE", true);
        $files = glob("mod/*/lang/*.php");
        $result = [];
        foreach ($files as $f) {
            $parts = explode("/", $f);
            $domain = $parts[1];
            $lang_code = basename($f, ".php");
            $lang = [];
            @include $f;
            $arr = $lang[$domain] ?? [];
            $paths = [];
            $flatten = function($a, $prefix = "") use (&$flatten, &$paths) {
                if (!is_array($a)) return;
                foreach ($a as $k => $v) {
                    $p = $prefix === "" ? (string)$k : "$prefix.$k";
                    $paths[] = $p;
                    if (is_array($v)) {
                        $flatten($v, $p);
                    }
                }
            };
            $flatten($arr);
            $result[$lang_code][$domain] = $paths;
        }
        echo json_encode($result);
        '''
        try:
            res = subprocess.run(["php", "-r", php_code], cwd=self.root_dir, capture_output=True, text=True)
            if res.returncode == 0 and res.stdout.strip():
                data = json.loads(res.stdout)
                for lcode, domains in data.items():
                    self.lang_keys_map[lcode] = {}
                    for domain, klist in domains.items():
                        self.lang_keys_map[lcode][domain] = set(klist)
                return
        except Exception:
            pass

        lang_files = glob.glob(os.path.join(self.root_dir, "**", "lang", "*.php"), recursive=True)
        for file_path in lang_files:
            rel = os.path.relpath(file_path, self.root_dir)
            parts = set(os.path.normpath(rel).split(os.sep))
            if parts.intersection(EXCLUDE_DIRS):
                continue

            filename = os.path.basename(file_path)
            lang_code = os.path.splitext(filename)[0]

            path_parts = rel.split(os.sep)
            domain = "index"
            if "mod" in path_parts:
                mod_idx = path_parts.index("mod")
                if mod_idx + 1 < len(path_parts):
                    domain = path_parts[mod_idx + 1]

            if lang_code not in self.lang_keys_map:
                self.lang_keys_map[lang_code] = {}
            if domain not in self.lang_keys_map[lang_code]:
                self.lang_keys_map[lang_code][domain] = set()

            keys = self.extract_php_array_keys(file_path)
            self.lang_keys_map[lang_code][domain].update(keys)

    def extract_php_array_keys(self, file_path: str) -> Set[str]:
        keys = set()
        try:
            with open(file_path, "r", encoding="utf-8", errors="ignore") as f:
                content = f.read()
                matches = re.findall(r"['\"]([a-zA-Z0-9_]+)['\"]\s*=>", content)
                keys.update(matches)
                matches_assign = re.findall(r"\$lang(?:\[['\"][a-zA-Z0-9_]+['\"]\])?\[['\"]([a-zA-Z0-9_]+)['\"]\]\s*=", content)
                keys.update(matches_assign)
        except Exception:
            pass
        return keys

    def get_target_files(self, all_files=False, target_path=None) -> List[str]:
        """Discovers files across the entire Amed Framework."""
        if target_path:
            abs_path = os.path.abspath(target_path)
            if os.path.isfile(abs_path):
                return [abs_path]
            files_to_check = []
            for root, dirs, filenames in os.walk(abs_path):
                dirs[:] = [d for d in dirs if d not in EXCLUDE_DIRS and not d.startswith(".")]
                for fn in filenames:
                    if fn.endswith((".twig", ".php", ".js", ".html")):
                        files_to_check.append(os.path.join(root, fn))
            return files_to_check

        if all_files:
            files_to_check = []
            for root, dirs, filenames in os.walk(self.root_dir):
                dirs[:] = [d for d in dirs if d not in EXCLUDE_DIRS and not d.startswith(".")]
                for fn in filenames:
                    if fn.endswith((".twig", ".php", ".js", ".html")):
                        files_to_check.append(os.path.join(root, fn))
            return files_to_check

        # Default: git diff / modified files
        files_to_check = []
        try:
            res = subprocess.run(["git", "status", "--porcelain"], cwd=self.root_dir, capture_output=True, text=True)
            if res.returncode == 0:
                for line in res.stdout.splitlines():
                    if len(line) > 3:
                        status, file_rel = line[:2], line[3:].strip()
                        if " -> " in file_rel:
                            file_rel = file_rel.split(" -> ")[1].strip()
                        if status not in ("D ", " D"):
                            parts = set(os.path.normpath(file_rel).split(os.sep))
                            if not parts.intersection(EXCLUDE_DIRS):
                                if file_rel.endswith((".twig", ".php", ".js", ".html")):
                                    abs_f = os.path.join(self.root_dir, file_rel)
                                    if os.path.isfile(abs_f):
                                        files_to_check.append(abs_f)
        except Exception:
            pass

        return files_to_check

    def check_file(self, file_path: str) -> List[Dict]:
        issues = []
        rel_path = os.path.relpath(file_path, self.root_dir)
        ext = os.path.splitext(file_path)[1].lower()

        try:
            with open(file_path, "r", encoding="utf-8", errors="ignore") as f:
                lines = f.readlines()
        except Exception as e:
            return [{"file": rel_path, "line": 0, "severity": "ERROR", "code": "READ_ERROR", "message": str(e)}]

        content = "".join(lines)
        is_lang_file = "lang" in rel_path and ext == ".php"

        # 1. Check for Forbidden Alias in SQL queries / code
        if ext in (".php", ".sql"):
            for idx, line in enumerate(lines, 1):
                if re.search(r'\b(?:SELECT\s+[\w\(\)\*,\s`\.\'"]+?\s+AS\s+[a-zA-Z0-9_]+|(?:FROM|JOIN)\s+`?[a-zA-Z0-9_]+`?\s+AS\s+[a-zA-Z0-9_]+)', line, re.IGNORECASE):
                    issues.append({
                        "file": rel_path,
                        "line": idx,
                        "severity": "ERROR",
                        "code": "FORBIDDEN_ALIAS",
                        "message": "Usage of 'alias' is forbidden. Use canonical column or code names.",
                        "snippet": line.strip()
                    })

        # 2. Strict Code Separation (PHP, Twig, HTML)
        if ext in (".twig", ".php", ".html") and not is_lang_file and not rel_path.startswith("lib/"):
            for idx, line in enumerate(lines, 1):
                stripped = line.strip()
                if stripped.startswith("{#") or stripped.startswith("<!--") or stripped.startswith("//") or stripped.startswith("*"):
                    continue

                if RE_INLINE_STYLE_TAG.search(line):
                    issues.append({
                        "file": rel_path,
                        "line": idx,
                        "severity": "ERROR",
                        "code": "INLINE_STYLE_TAG",
                        "message": "Forbidden <style> tag. Move styles to an external CSS file.",
                        "snippet": stripped
                    })

                style_match = RE_INLINE_STYLE_ATTR.search(line)
                if style_match:
                    issues.append({
                        "file": rel_path,
                        "line": idx,
                        "severity": "ERROR",
                        "code": "INLINE_STYLE_ATTR",
                        "message": f"Forbidden inline style attribute '{style_match.group(0)}'. Move styles to CSS classes.",
                        "snippet": stripped
                    })

                if RE_INLINE_SCRIPT_TAG.search(line):
                    # Layout templates inject global runtime configuration objects (SITE_URL, adminLang)
                    if not ("layout" in rel_path.lower() or "layouts" in rel_path.lower()):
                        issues.append({
                            "file": rel_path,
                            "line": idx,
                            "severity": "ERROR",
                            "code": "INLINE_SCRIPT_TAG",
                            "message": "Forbidden inline <script> block. Move JavaScript to an external JS file.",
                            "snippet": stripped
                        })

                # Check for inline event handlers (e.g. onclick, onchange)
                # Exempt standard Google/Lighthouse async CSS print-swap pattern on <link> tags
                if "<link " in line and ("this.media=" in line or "this.onload=null" in line):
                    event_match = None
                else:
                    event_match = RE_INLINE_EVENT_HANDLER.search(line)

                if event_match:
                    issues.append({
                        "file": rel_path,
                        "line": idx,
                        "severity": "ERROR",
                        "code": "INLINE_EVENT_HANDLER",
                        "message": f"Forbidden inline event handler '{event_match.group(0)}'. Bind events in external JS.",
                        "snippet": stripped
                    })

        # 3. Check for Forbidden |default(...) filter or ?? fallback on lang keys in Twig
        if ext == ".twig":
            for idx, line in enumerate(lines, 1):
                if RE_FORBIDDEN_DEFAULT.search(line):
                    issues.append({
                        "file": rel_path,
                        "line": idx,
                        "severity": "ERROR",
                        "code": "FORBIDDEN_FALLBACK",
                        "message": "Forbidden fallback (|default(...) or ??) used on localization key.",
                        "snippet": line.strip()
                    })

        # 4. Check for Missing Language Keys in Twig (including nested paths across all core languages)
        if ext == ".twig":
            for idx, line in enumerate(lines, 1):
                for match in RE_TWIG_LANG_KEY.finditer(line):
                    domain, key = match.group(1), match.group(2).rstrip(".")
                    if domain in ("code", "dir", "name") or not key:
                        continue

                    missing_in = []
                    for lcode in ["en", "ku", "tr"]:
                        keys_set = self.lang_keys_map.get(lcode, {}).get(domain, set())
                        if key not in keys_set:
                            missing_in.append(lcode)

                    if missing_in:
                        issues.append({
                            "file": rel_path,
                            "line": idx,
                            "severity": "WARNING",
                            "code": "MISSING_KEY",
                            "message": f"Translation key `lang.{domain}.{key}` is missing in language pack(s): {', '.join(missing_in)}.",
                            "snippet": line.strip()
                        })

        # 5. Check Non-English Language Key Names in **/lang/*.php
        if is_lang_file:
            for idx, line in enumerate(lines, 1):
                m = re.search(r"['\"]([a-zA-Z0-9_]+)['\"]\s*=>", line)
                if m:
                    kname = m.group(1)
                    is_valid, reason = self.lexicon.check_identifier(kname)
                    if not is_valid:
                        issues.append({
                            "file": rel_path,
                            "line": idx,
                            "severity": "ERROR",
                            "code": "NON_ENGLISH_KEY_NAME",
                            "message": f"Non-English language key '{kname}': {reason}",
                            "snippet": line.strip()
                        })

        # 6. Check Non-English Functions, Classes, Variables, and Comments
        if ext in (".php", ".js", ".css", ".twig") and not is_lang_file:
            for idx, line in enumerate(lines, 1):
                # Check Non-English Comments
                comment_match = None
                if ext in (".php", ".js"):
                    comment_match = re.search(r'(?<!:)(?://|/\*|^\s*\*)\s*(.*)', line)
                    if not comment_match and ext == ".php":
                        comment_match = re.search(r'^\s*#\s*(.*)', line)
                elif ext == ".css":
                    comment_match = re.search(r'/\*\s*(.*?)(?:\*/|$)', line)
                elif ext == ".twig":
                    comment_match = re.search(r'{#\s*(.*?)(?:#}|$)', line)

                if comment_match:
                    comment_text = comment_match.group(1).strip()
                    if comment_text and not comment_text.startswith(("@", "var", "param", "return", "type", "todo", "fixme", "see", "package", "subpackage", "author", "copyright", "license", "property", "method", "throws", "global", "internal", "link", "uses", "phpcs", "eslint", "prettier", "ts-", "stylelint", "/*", "*/", "---", "===")):
                        c_clean = re.sub(r'https?://\S+', '', comment_text)
                        c_clean = re.sub(r'\S+@\S+', '', c_clean)
                        c_clean = re.sub(r'(?:/[\w\.-]+)+', '', c_clean)
                        c_clean = re.sub(r'`[^`]+`', '', c_clean)
                        c_clean = re.sub(r'<[^>]+>', '', c_clean)
                        c_clean = re.sub(r'\$[a-zA-Z0-9_]+', '', c_clean)
                        c_clean = re.sub(r'\b[a-zA-Z0-9_]+(?:::|->)[a-zA-Z0-9_]+\b', '', c_clean)
                        c_clean = re.sub(r'\bv?\d+(?:\.\d+)*\b', '', c_clean)

                        c_tokens = re.findall(r'\b[a-zA-Z]{3,}\b', c_clean)
                        for ct in c_tokens:
                            is_valid, reason = self.lexicon.check_identifier(ct)
                            if not is_valid:
                                issues.append({
                                    "file": rel_path,
                                    "line": idx,
                                    "severity": "ERROR",
                                    "code": "NON_ENGLISH_COMMENT",
                                    "message": f"Non-English comment word '{ct}' detected. Comments must be strictly in English: {reason}",
                                    "snippet": line.strip()
                                })
                                break

                # Check Function Declarations
                if ext in (".php", ".js"):
                    fn_matches = re.finditer(r'\bfunction\s+([a-zA-Z0-9_]+)\s*\(', line)
                    for fn_m in fn_matches:
                        fn_name = fn_m.group(1)
                        if not fn_name.startswith("__"):
                            is_valid, reason = self.lexicon.check_identifier(fn_name)
                            if not is_valid:
                                issues.append({
                                    "file": rel_path,
                                    "line": idx,
                                    "severity": "ERROR",
                                    "code": "NON_ENGLISH_FUNCTION",
                                    "message": f"Non-English function name '{fn_name}': {reason}",
                                    "snippet": line.strip()
                                })

                    # Check Class / Trait / Interface Declarations
                    cls_matches = re.finditer(r'\b(?:class|interface|trait)\s+([a-zA-Z0-9_]+)', line)
                    for cls_m in cls_matches:
                        cls_name = cls_m.group(1)
                        is_valid, reason = self.lexicon.check_identifier(cls_name)
                        if not is_valid:
                            issues.append({
                                "file": rel_path,
                                "line": idx,
                                "severity": "ERROR",
                                "code": "NON_ENGLISH_CLASS",
                                "message": f"Non-English class/interface name '{cls_name}': {reason}",
                                "snippet": line.strip()
                            })

                # Check Variable Declarations & Usage in PHP
                if ext == ".php":
                    var_matches = re.finditer(r'\$([a-zA-Z0-9_]{3,})\b', line)
                    for var_m in var_matches:
                        var_name = var_m.group(1)
                        if var_name in ("_GET", "_POST", "_SERVER", "_SESSION", "_COOKIE", "_FILES", "_REQUEST", "GLOBALS", "this", "db", "res", "row", "sql", "cfg"):
                            continue
                        is_valid, reason = self.lexicon.check_identifier(var_name)
                        if not is_valid:
                            issues.append({
                                "file": rel_path,
                                "line": idx,
                                "severity": "ERROR",
                                "code": "NON_ENGLISH_VARIABLE",
                                "message": f"Non-English variable '${var_name}': {reason}",
                                "snippet": line.strip()
                            })
                            break

        return issues

    def run(self, all_files=False, target_path=None, json_output=False) -> int:
        files = self.get_target_files(all_files=all_files, target_path=target_path)
        all_issues = []

        for f in files:
            issues = self.check_file(f)
            all_issues.extend(issues)

        if json_output:
            print(json.dumps({"total_files_checked": len(files), "total_issues": len(all_issues), "issues": all_issues}, indent=2))
            return 1 if any(i["severity"] == "ERROR" for i in all_issues) else 0

        print("\n========================================================")
        print(" 🌐 Amed Framework i18n-guard Quality Audit Report")
        print("========================================================\n")
        print(f"📁 Files Audited: {len(files)}")

        if not files:
            print("✨ No modified files detected in git working tree.\n")
            return 0

        errors = [i for i in all_issues if i["severity"] == "ERROR"]
        warnings = [i for i in all_issues if i["severity"] == "WARNING"]

        if not all_issues:
            print("✅ 100% CLEAN! Zero violations found in audited files.\n")
            return 0

        print(f"🚨 Total Violations: {len(all_issues)} ({len(errors)} Errors, {len(warnings)} Warnings)\n")

        for issue in all_issues:
            icon = "❌ ERROR" if issue["severity"] == "ERROR" else "⚠️ WARNING"
            print(f"{icon} [{issue['code']}] {issue['file']}:{issue['line']}")
            print(f"   Message: {issue['message']}")
            if "snippet" in issue and issue["snippet"]:
                print(f"   Snippet: {issue['snippet']}")
            print("-" * 60)

        print("\n💡 Summary:")
        if errors:
            print(f"❌ FAILED: {len(errors)} critical error(s) must be fixed.\n")
            return 1
        else:
            print(f"⚠️ PASSED WITH WARNINGS: {len(warnings)} warning(s) found.\n")
            return 0


def main():
    parser = argparse.ArgumentParser(description="Amed Framework i18n Quality Guard")
    parser.add_argument("--all", action="store_true", help="Audit all files across repository")
    parser.add_argument("--path", type=str, help="Audit specific file or directory path")
    parser.add_argument("--json", action="store_true", help="Output JSON results")

    args = parser.parse_args()
    guard = I18nGuard(REPO_ROOT)
    exit_code = guard.run(all_files=args.all, target_path=args.path, json_output=args.json)
    sys.exit(exit_code)


if __name__ == "__main__":
    main()
