#!/usr/bin/env python3
"""PreToolUse guard for AF/Bulletin enforcing workspace isolation, module boundaries, and code separation."""

import json
import os
import re
import sys

SCRIPTS_DIR = os.path.dirname(os.path.abspath(__file__))
if SCRIPTS_DIR not in sys.path:
    sys.path.insert(0, SCRIPTS_DIR)

import hook_utils

RE_COMMIT_MESSAGE_FLAG = re.compile(
    r"(?:^|\s)(?:-m|--message)(?:\s*=\s*|\s+)(?:\"(?:\\.|[^\"\\])*\"|'(?:\\.|[^'\\])*')"
)
RE_SIBLING_BUSINESS_SEGMENT = r"(?:AF[\\/]+Business|\.\.[\\/]+Business)"
RE_CD_BUSINESS = re.compile(
    rf"(?:^|[;&|()])\s*cd\s+[^\n;|&]*{RE_SIBLING_BUSINESS_SEGMENT}(?:[\\/\s;|&\"']|$)"
)
RE_GIT_C_MUTATE_BUSINESS = re.compile(
    rf"\bgit\s+-C\s+[^\s;|&\"']*{RE_SIBLING_BUSINESS_SEGMENT}[^\n;|&]*\b"
    r"(?:add|commit|push|pull|merge|rebase|cherry-pick|reset|checkout|switch|restore|clean|rm|mv|tag|apply|am|stash|init)\b"
)
RE_REDIRECT_BUSINESS = re.compile(
    rf"(?:&>>?|1>>?|2>>?|>>?)\s*[\"']?[^\s;|&\"']*{RE_SIBLING_BUSINESS_SEGMENT}"
)
RE_FS_MUTATE_BUSINESS = re.compile(
    rf"(?:^|[;&|()])\s*(?:env\s+|sudo\s+)?"
    rf"(?:cp|mv|rm|rmdir|mkdir|touch|install|ln|chmod|chown|rsync|dd|tee|truncate)\b"
    rf"[^\n;|&]*{RE_SIBLING_BUSINESS_SEGMENT}"
)
RE_INPLACE_EDIT_BUSINESS = re.compile(
    rf"\b(?:sed|perl)\b[^\n;|&]*(?:\s-(?:[a-zA-Z]*i[a-zA-Z]*\b|-in-place\b))"
    rf"[^\n;|&]*{RE_SIBLING_BUSINESS_SEGMENT}"
)
RE_PYTHON_WRITE_BUSINESS = re.compile(
    rf"\b(?:python[23]?|php)\b[^\n;|&]*(?:"
    rf"{RE_SIBLING_BUSINESS_SEGMENT}[^\n;|&]*(?:\bopen\(|\bfile_put_contents\b|\bfwrite\b|\bfopen\b|write_text|write_bytes|unlink|rmdir|rename|replace|shutil\.(?:copy|move|rmtree))"
    rf"|"
    rf"(?:\bopen\(|\bfile_put_contents\b|\bfwrite\b|\bfopen\b|write_text|write_bytes|unlink|rmdir|rename|shutil\.(?:copy|move|rmtree))[^\n;|&]*{RE_SIBLING_BUSINESS_SEGMENT}"
    rf")"
)
RE_EXPLICIT_BUSINESS_PATH = re.compile(r"(?:^|[\\/])AF[\\/]Business(?:[\\/]|$)")


def _deny(reason):
    print(json.dumps({"decision": "deny", "reason": reason}))
    return 0


def _allow():
    print(json.dumps({"decision": "allow"}))
    return 0


def _strip_commit_messages(command):
    """Remove commit message literals so prose mentioning AF/Business does not trigger command guards."""
    return RE_COMMIT_MESSAGE_FLAG.sub(" -m <message>", command)


def check_file_tool(tool_name, args, payload, repo_root):
    """Validate file-modifying tool calls against workspace, boundary, and code-separation rules."""
    raw_target = hook_utils.extract_tool_target_path(args)
    if not raw_target:
        return ""

    real_target = hook_utils.resolve_target_path(raw_target, payload, repo_root)
    if RE_EXPLICIT_BUSINESS_PATH.search(real_target):
        return (
            f"WORKSPACE ISOLATION VIOLATION: Autonomous writes to sibling repository "
            f"'AF/Business' are forbidden ('{raw_target}')."
        )

    allowed_roots = hook_utils.get_allowed_write_roots(payload, repo_root)
    if not any(hook_utils.is_path_within(real_target, root) for root in allowed_roots):
        return (
            f"WORKSPACE ISOLATION VIOLATION: Target path '{raw_target}' resolves to "
            f"'{real_target}', outside permitted AF/Bulletin workspace and artifact roots."
        )

    if not hook_utils.is_path_within(real_target, repo_root):
        return ""

    rel_posix = hook_utils.to_rel_posix(real_target, repo_root)
    path_violation = hook_utils.check_forbidden_modules_in_path(rel_posix)
    if path_violation:
        return f"MODULE BOUNDARY VIOLATION: {path_violation}"

    content = hook_utils.extract_written_content(tool_name, args)
    content_violation = hook_utils.check_forbidden_modules_in_content(rel_posix, content)
    if content_violation:
        return f"MODULE BOUNDARY VIOLATION: {content_violation}"

    sep_violations = hook_utils.find_code_separation_violations(rel_posix, content)
    if sep_violations:
        return (
            "CODE SEPARATION VIOLATION (AGENTS.md Rule 1): "
            + "; ".join(sep_violations)
            + ". Move CSS/JS into external files under static/."
        )

    return ""


def check_run_command(args):
    """Block shell commands that enter or mutate the sibling AF/Business repository."""
    if not isinstance(args, dict):
        return ""
    raw_cmd = args.get("CommandLine") or args.get("command") or ""
    if not isinstance(raw_cmd, str) or not raw_cmd.strip():
        return ""

    cmd = _strip_commit_messages(raw_cmd)
    for pattern in (
        RE_CD_BUSINESS,
        RE_GIT_C_MUTATE_BUSINESS,
        RE_REDIRECT_BUSINESS,
        RE_FS_MUTATE_BUSINESS,
        RE_INPLACE_EDIT_BUSINESS,
        RE_PYTHON_WRITE_BUSINESS,
    ):
        if pattern.search(cmd):
            return (
                "WORKSPACE ISOLATION VIOLATION: Shell command attempts to enter or "
                "mutate sibling repository 'AF/Business'."
            )
    return ""


def main():
    payload = hook_utils.read_stdin_json()
    repo_root = hook_utils.find_repo_root(payload)
    tool_call = payload.get("toolCall") if isinstance(payload.get("toolCall"), dict) else {}
    tool_name = tool_call.get("name", "")
    args = tool_call.get("args") if isinstance(tool_call.get("args"), dict) else {}

    if tool_name in hook_utils.FILE_MODIFICATION_TOOLS:
        reason = check_file_tool(tool_name, args, payload, repo_root)
        if reason:
            return _deny(reason)
    elif tool_name == "run_command":
        reason = check_run_command(args)
        if reason:
            return _deny(reason)

    return _allow()


if __name__ == "__main__":
    sys.exit(main())
