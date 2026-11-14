#!/usr/bin/env python3
"""Shared deterministic utilities for af-cms-synchronizer Antigravity hooks."""

import json
import os
import re
import subprocess
import sys
import tempfile

FILE_MODIFICATION_TOOLS = frozenset({
    "write_file",
    "edit_file",
    "multi_replace_file_content",
    "write_to_file",
    "replace_file_content",
})

FORBIDDEN_MODULES = (
    "mod_content",
    "mod_content_category",
    "mod_product",
    "mod_product_category",
    "mod_service",
    "mod_service_category",
    "mod_client",
    "mod_reference",
    "mod_project",
    "mod_project_category",
    "mod_appointment",
    "mod_reservation",
    "mod_hr",
    "mod_organization_type",
    "mod_test",
    "mod_assessment",
    "mod_category",
    "mod_hero_slider",
)

FORBIDDEN_SHORT_NAMES = frozenset(
    m[4:] for m in FORBIDDEN_MODULES if m.startswith("mod_")
)

ALLOWED_FOREIGN_NAMED_PATHS = frozenset({
    "mod/index/helper/mod_hero_slider.helper.php",
})

BOUNDARY_SKIP_PREFIXES = (
    ".agents/",
    "docs/",
    ".git/",
    "node_modules/",
    "vendor/",
    "tools/",
    "tests/",
)

CODE_SEP_EXTENSIONS = frozenset({".twig", ".html", ".htm", ".php"})
CODE_SEP_EXCLUDE_DIRS = frozenset({
    ".git",
    ".gemini",
    ".agents",
    "vendor",
    "node_modules",
    "docs",
    "cache",
    "data",
    "lib",
    "tools",
    "tests",
})
CODE_SEP_EXCLUDE_PREFIXES = ("static/assets/vendor/",)

RE_STYLE_TAG = re.compile(r"<style\b[^>]*>", re.IGNORECASE)
RE_INLINE_SCRIPT_TAG = re.compile(
    r"<script\b(?![^>]*\bsrc\s*=)(?![^>]*\btype\s*=\s*[\"']application/(?:ld\+)?json[\"'])[^>]*>",
    re.IGNORECASE,
)
RE_INLINE_STYLE_ATTR = re.compile(
    r"(?<![$\w\-:.>])style\s*=\s*(?:\"[^\"]*\"|'[^']*')",
    re.IGNORECASE,
)
RE_INLINE_EVENT_ATTR = re.compile(
    r"(?<![$\w\-:.>])on(?:"
    r"abort|afterprint|animationend|animationiteration|animationstart|"
    r"beforeprint|beforeunload|blur|canplay|canplaythrough|change|click|"
    r"contextmenu|copy|cut|dblclick|drag|dragend|dragenter|dragleave|"
    r"dragover|dragstart|drop|durationchange|emptied|ended|error|focus|"
    r"focusin|focusout|formdata|fullscreenchange|gotpointercapture|"
    r"hashchange|input|invalid|keydown|keypress|keyup|load|loadeddata|"
    r"loadedmetadata|loadstart|lostpointercapture|message|mousedown|"
    r"mouseenter|mouseleave|mousemove|mouseout|mouseover|mouseup|"
    r"mousewheel|offline|online|pagehide|pageshow|paste|pause|play|"
    r"playing|pointercancel|pointerdown|pointerenter|pointerleave|"
    r"pointermove|pointerout|pointerover|pointerup|popstate|progress|"
    r"ratechange|reset|resize|scroll|search|seeked|seeking|select|"
    r"selectionchange|show|stalled|storage|submit|suspend|timeupdate|"
    r"toggle|touchcancel|touchend|touchmove|touchstart|transitionend|"
    r"unload|volumechange|waiting|wheel"
    r")\s*=\s*(?:\"[^\"]*\"|'[^']*')",
    re.IGNORECASE,
)
RE_LINK_TAG = re.compile(r"<link\b[^>]*>", re.IGNORECASE)
RE_INLINE_HTML_COMMENT = re.compile(r"<!--.*?-->")
RE_INLINE_TWIG_COMMENT = re.compile(r"\{#.*?#\}")
RE_INLINE_C_COMMENT = re.compile(r"/\*.*?\*/")
RE_DIFF_HUNK = re.compile(r"^@@ -\d+(?:,\d+)? \+(\d+)(?:,\d+)? @@")

FORBIDDEN_MODULE_PATTERNS = tuple(
    (mod, re.compile(rf"(?<![a-zA-Z0-9_]){re.escape(mod)}(?![a-zA-Z0-9_])"))
    for mod in FORBIDDEN_MODULES
)


def read_stdin_json():
    """Read and parse a JSON object from stdin, defaulting to an empty dict."""
    try:
        raw = sys.stdin.read()
        if not raw or not raw.strip():
            return {}
        data = json.loads(raw)
        return data if isinstance(data, dict) else {}
    except Exception:
        return {}


def is_path_within(candidate_path, root_dir):
    """Return True when candidate_path resolves inside root_dir."""
    if not candidate_path or not root_dir:
        return False
    try:
        real_cand = os.path.realpath(candidate_path)
        real_root = os.path.realpath(root_dir)
        return os.path.commonpath([real_cand, real_root]) == real_root
    except ValueError:
        return False


def find_repo_root(payload=None):
    """Resolve the AF/Bulletin repository root directory safely."""
    candidates = []
    if isinstance(payload, dict):
        cwd = payload.get("cwd")
        if isinstance(cwd, str) and cwd:
            candidates.append(cwd)
        workspace_paths = payload.get("workspacePaths")
        if isinstance(workspace_paths, list):
            for wp in workspace_paths:
                if isinstance(wp, str) and wp:
                    candidates.append(wp)

    candidates.append(os.path.dirname(os.path.abspath(__file__)))

    for start in candidates:
        cur = os.path.realpath(start)
        if os.path.isfile(cur):
            cur = os.path.dirname(cur)
        for _ in range(8):
            if os.path.exists(os.path.join(cur, ".git")):
                return cur
            parent = os.path.dirname(cur)
            if parent == cur:
                break
            cur = parent

    return os.path.realpath(
        os.path.dirname(
            os.path.dirname(
                os.path.dirname(
                    os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
                )
            )
        )
    )


def resolve_target_path(raw_path, payload, repo_root):
    """Resolve a tool path argument against payload cwd or repo_root."""
    if not raw_path or not isinstance(raw_path, str):
        return ""
    expanded = os.path.expanduser(raw_path.strip())
    if os.path.isabs(expanded):
        return os.path.realpath(expanded)
    base_dir = repo_root
    if isinstance(payload, dict):
        cwd = payload.get("cwd")
        if isinstance(cwd, str) and cwd and os.path.isdir(cwd):
            base_dir = cwd
    return os.path.realpath(os.path.join(base_dir, expanded))


def extract_tool_target_path(args):
    """Return raw file path argument across old and new Antigravity tool schemas."""
    if not isinstance(args, dict):
        return ""
    for key in ("Path", "TargetFile", "path", "target_file"):
        val = args.get(key)
        if isinstance(val, str) and val.strip():
            return val.strip()
    return ""


def extract_written_content(tool_name, args):
    """Extract newly written or replaced text content from a file tool call."""
    if not isinstance(args, dict):
        return ""
    if tool_name == "write_file":
        return args.get("Content") if isinstance(args.get("Content"), str) else ""
    if tool_name == "write_to_file":
        return args.get("CodeContent") if isinstance(args.get("CodeContent"), str) else ""
    if tool_name == "edit_file":
        return args.get("Replacement") if isinstance(args.get("Replacement"), str) else ""
    if tool_name == "replace_file_content":
        return (
            args.get("ReplacementContent")
            if isinstance(args.get("ReplacementContent"), str)
            else ""
        )
    if tool_name == "multi_replace_file_content":
        chunks = args.get("ReplacementChunks")
        if not isinstance(chunks, list):
            return ""
        parts = []
        for chunk in chunks:
            if isinstance(chunk, dict):
                text = chunk.get("ReplacementContent") or chunk.get("Replacement") or ""
                if isinstance(text, str) and text:
                    parts.append(text)
        return "\n".join(parts)
    return ""


def get_allowed_write_roots(payload, repo_root):
    """Return resolved roots permitted for file writes."""
    roots = [
        os.path.realpath(repo_root),
        os.path.realpath(os.path.expanduser("~/.gemini")),
        os.path.realpath("/tmp"),
        os.path.realpath(tempfile.gettempdir()),
    ]
    if isinstance(payload, dict):
        artifact_dir = payload.get("artifactDirectoryPath")
        if isinstance(artifact_dir, str) and artifact_dir.strip():
            roots.append(os.path.realpath(os.path.expanduser(artifact_dir.strip())))
    return roots


def to_rel_posix(real_path, repo_root):
    """Return repo-relative POSIX path when real_path is inside repo_root."""
    if not is_path_within(real_path, repo_root):
        return ""
    return os.path.relpath(real_path, repo_root).replace("\\", "/").lstrip("./")


def is_boundary_exempt(rel_posix):
    """Return True if path is exempt from forbidden-module enforcement."""
    norm = rel_posix.replace("\\", "/").lstrip("./")
    return (
        norm in ALLOWED_FOREIGN_NAMED_PATHS
        or norm.startswith(BOUNDARY_SKIP_PREFIXES)
    )


def is_code_sep_exempt(rel_posix):
    """Return True if path is exempt from strict inline CSS/JS code separation."""
    norm = rel_posix.replace("\\", "/").lstrip("./")
    if not norm:
        return True
    ext = os.path.splitext(norm)[1].lower()
    if ext not in CODE_SEP_EXTENSIONS:
        return True
    if norm.startswith(CODE_SEP_EXCLUDE_PREFIXES):
        return True
    parts = norm.split("/")
    for d in parts[:-1]:
        if d in CODE_SEP_EXCLUDE_DIRS:
            return True
    return False


def check_forbidden_modules_in_path(rel_posix):
    """Return a violation string if a non-exempt path belongs to a forbidden module."""
    if is_boundary_exempt(rel_posix):
        return ""
    norm = rel_posix.replace("\\", "/").lstrip("./")
    parts = [p for p in norm.split("/") if p]
    for mod in FORBIDDEN_MODULES:
        if mod in parts:
            return f"Path '{norm}' references forbidden module directory '{mod}'."
    if len(parts) >= 2 and parts[0] == "mod" and parts[1] in FORBIDDEN_SHORT_NAMES:
        return f"Path '{norm}' resides under forbidden module directory 'mod/{parts[1]}'."
    base = os.path.basename(norm)
    if not base.endswith((".md", ".py")):
        for mod, pattern in FORBIDDEN_MODULE_PATTERNS:
            if pattern.search(base):
                return f"Filename '{norm}' matches forbidden module '{mod}'."
    return ""


def check_forbidden_modules_in_content(rel_posix, content):
    """Return a violation string if written content introduces forbidden module tokens."""
    if not content or is_boundary_exempt(rel_posix):
        return ""
    ext = os.path.splitext(rel_posix)[1].lower()
    if ext in (".md", ".txt", ".json", ".svg", ".lock"):
        return ""
    for mod, pattern in FORBIDDEN_MODULE_PATTERNS:
        if pattern.search(content):
            return (
                f"Content targeting '{rel_posix}' introduces forbidden module "
                f"reference '{mod}'."
            )
    return ""


def _sanitize_line_for_code_sep(line):
    """Strip inline comments and allowed async <link onload=...> idioms."""
    cleaned = RE_INLINE_HTML_COMMENT.sub("", line)
    cleaned = RE_INLINE_TWIG_COMMENT.sub("", cleaned)
    cleaned = RE_INLINE_C_COMMENT.sub("", cleaned)
    stripped = cleaned.lstrip()
    if not stripped or stripped.startswith(("{#", "<!--", "//", "/*", "*", "# ")):
        return ""
    if stripped == "#":
        return ""

    if "<link" in cleaned.lower() and (
        "this.media" in cleaned or "this.onload=null" in cleaned or "this.onload = null" in cleaned
    ):
        def _strip_allowed_onload(match):
            tag = match.group(0)
            if "this.media" in tag or "this.onload=null" in tag or "this.onload = null" in tag:
                return RE_INLINE_EVENT_ATTR.sub("", tag)
            return tag

        cleaned = RE_LINK_TAG.sub(_strip_allowed_onload, cleaned)

    return cleaned


def scan_lines_for_code_separation(rel_posix, indexed_lines):
    """Check (line_num, text) pairs for AGENTS.md Rule 1 code separation violations."""
    if is_code_sep_exempt(rel_posix):
        return []
    violations = []
    in_twig_comment = False
    in_html_comment = False
    in_c_comment = False

    for line_no, raw_line in indexed_lines:
        line = raw_line
        stripped = line.lstrip()

        if in_twig_comment:
            if "#}" in line:
                in_twig_comment = False
                line = line.split("#}", 1)[1]
            else:
                continue
        if in_html_comment:
            if "-->" in line:
                in_html_comment = False
                line = line.split("-->", 1)[1]
            else:
                continue
        if in_c_comment:
            if "*/" in line:
                in_c_comment = False
                line = line.split("*/", 1)[1]
            else:
                continue

        if "{#" in line and "#}" not in line:
            in_twig_comment = True
            line = line.split("{#", 1)[0]
        if "<!--" in line and "-->" not in line:
            in_html_comment = True
            line = line.split("<!--", 1)[0]
        if stripped.startswith("/*") and "*/" not in stripped:
            in_c_comment = True
            continue

        cleaned = _sanitize_line_for_code_sep(line)
        if not cleaned:
            continue

        if RE_STYLE_TAG.search(cleaned):
            violations.append(f"{rel_posix}:{line_no}: inline <style> tag detected")
        if RE_INLINE_SCRIPT_TAG.search(cleaned):
            violations.append(
                f"{rel_posix}:{line_no}: inline <script> tag without src/JSON type detected"
            )
        if RE_INLINE_STYLE_ATTR.search(cleaned):
            violations.append(f"{rel_posix}:{line_no}: inline style attribute detected")
        if RE_INLINE_EVENT_ATTR.search(cleaned):
            violations.append(f"{rel_posix}:{line_no}: inline event handler attribute detected")

    return violations


def find_code_separation_violations(rel_posix, text):
    """Scan full text block for code separation violations."""
    if not text or is_code_sep_exempt(rel_posix):
        return []
    indexed = list(enumerate(text.splitlines(), start=1))
    return scan_lines_for_code_separation(rel_posix, indexed)


def get_git_status_entries(repo_root):
    """Return list of (xy_status, rel_posix) from git status --porcelain."""
    try:
        res = subprocess.run(
            ["git", "-c", "core.quotepath=off", "status", "--porcelain", "-uall"],
            cwd=repo_root,
            capture_output=True,
            text=True,
            timeout=10,
            check=False,
        )
    except Exception:
        return []

    if res.returncode != 0 or not res.stdout:
        return []

    def _unquote(path_str):
        s = path_str.rstrip("\r\n")
        if s.startswith('"') and s.endswith('"') and len(s) >= 2:
            s = s[1:-1]
        return s.replace("\\", "/").lstrip("./")

    entries = []
    for line in res.stdout.splitlines():
        if len(line) < 4:
            continue
        xy = line[:2]
        if "D" in xy:
            continue
        raw_path = line[3:]
        if ("R" in xy or "C" in xy) and " -> " in raw_path:
            raw_path = raw_path.split(" -> ", 1)[1]
        rel_posix = _unquote(raw_path)
        if rel_posix:
            entries.append((xy, rel_posix))
    return entries


def get_diff_added_lines(repo_root, rel_posix, xy_status=""):
    """Return [(line_no, line_text)] for newly added/changed lines or untracked files."""
    abs_path = os.path.join(repo_root, rel_posix)
    if not os.path.isfile(abs_path):
        return []

    if xy_status == "??":
        try:
            with open(abs_path, "r", encoding="utf-8", errors="ignore") as f:
                return list(enumerate(f.read().splitlines(), start=1))
        except OSError:
            return []

    try:
        res = subprocess.run(
            ["git", "-c", "core.quotepath=off", "diff", "-U0", "HEAD", "--", rel_posix],
            cwd=repo_root,
            capture_output=True,
            text=True,
            timeout=10,
            check=False,
        )
    except Exception:
        return []

    if res.returncode != 0:
        # Handle unborn HEAD repositories cleanly by checking full file on newly added files.
        if "A" in xy_status:
            try:
                with open(abs_path, "r", encoding="utf-8", errors="ignore") as f:
                    return list(enumerate(f.read().splitlines(), start=1))
            except OSError:
                return []
        return []

    if not res.stdout:
        return []

    added = []
    current_line = 0
    for line in res.stdout.splitlines():
        m = RE_DIFF_HUNK.match(line)
        if m:
            current_line = int(m.group(1))
            continue
        if line.startswith("+++") or line.startswith("---"):
            continue
        if line.startswith("+"):
            added.append((max(current_line, 1), line[1:]))
            current_line += 1
        elif line.startswith(" "):
            current_line += 1
    return added


def run_php_lint(repo_root, rel_posix):
    """Run php -l on a repo-relative PHP file; return error message or empty string."""
    abs_path = os.path.join(repo_root, rel_posix)
    if not os.path.isfile(abs_path):
        return ""
    try:
        res = subprocess.run(
            ["php", "-l", abs_path],
            cwd=repo_root,
            capture_output=True,
            text=True,
            timeout=10,
            check=False,
        )
    except FileNotFoundError:
        return ""
    except Exception as exc:
        return f"PHP lint failed to run for {rel_posix}: {exc}"

    if res.returncode != 0:
        detail = (res.stderr or res.stdout or "").strip()
        return f"PHP syntax error in {rel_posix}: {detail}"
    return ""


def run_module_boundary_guard(repo_root):
    """Run tools/module_boundary_guard.py if present; return error string or empty."""
    guard_path = os.path.join(repo_root, "tools", "module_boundary_guard.py")
    if not os.path.isfile(guard_path):
        return ""
    try:
        res = subprocess.run(
            [sys.executable, guard_path],
            cwd=repo_root,
            capture_output=True,
            text=True,
            timeout=15,
            check=False,
        )
    except Exception as exc:
        return f"Module boundary guard execution failed: {exc}"

    if res.returncode != 0:
        detail = (res.stdout or res.stderr or "").strip()
        return f"Module boundary guard violation:\n{detail}"
    return ""


def verify_repo_targets(repo_root, targets):
    """Run PHP syntax, module boundary, and diff-scoped code separation checks."""
    violations = []
    needs_boundary_check = False

    for xy_status, rel_posix in targets:
        abs_path = os.path.join(repo_root, rel_posix)
        if not os.path.isfile(abs_path):
            continue

        if rel_posix.lower().endswith(".php"):
            lint_err = run_php_lint(repo_root, rel_posix)
            if lint_err:
                violations.append(lint_err)

        if not is_boundary_exempt(rel_posix):
            needs_boundary_check = True
            path_err = check_forbidden_modules_in_path(rel_posix)
            if path_err:
                violations.append(path_err)

        if not is_code_sep_exempt(rel_posix):
            added_lines = get_diff_added_lines(repo_root, rel_posix, xy_status=xy_status)
            sep_errs = scan_lines_for_code_separation(rel_posix, added_lines)
            violations.extend(sep_errs)

    if needs_boundary_check:
        boundary_err = run_module_boundary_guard(repo_root)
        if boundary_err:
            violations.append(boundary_err)

    return violations
