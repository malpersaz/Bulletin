#!/usr/bin/env python3
"""PostToolUse verifier for AF/Bulletin running php -l, boundary checks, and diff-scoped code separation."""

import argparse
import datetime
import json
import os
import sys

SCRIPTS_DIR = os.path.dirname(os.path.abspath(__file__))
if SCRIPTS_DIR not in sys.path:
    sys.path.insert(0, SCRIPTS_DIR)

import hook_utils


def _log_diagnostics(repo_root, violations):
    """Append hook diagnostics only when the repository logs/ directory already exists."""
    logs_dir = os.path.join(repo_root, "logs")
    if not os.path.isdir(logs_dir) or not violations:
        return
    log_file = os.path.join(logs_dir, "agent_hook_diagnostics.log")
    timestamp = datetime.datetime.now(datetime.timezone.utc).isoformat()
    try:
        with open(log_file, "a", encoding="utf-8") as f:
            for item in violations:
                f.write(f"[{timestamp}] PostToolUse: {item}\n")
    except OSError:
        pass


def _resolve_targets(payload, repo_root, cli_files, strict_mode):
    """Resolve targeted repository files from CLI flags, toolCall args, or git status."""
    status_entries = hook_utils.get_git_status_entries(repo_root)
    status_map = {rel: xy for xy, rel in status_entries}

    if cli_files:
        targets = []
        for raw_path in cli_files:
            real_target = hook_utils.resolve_target_path(raw_path, payload, repo_root)
            rel_posix = hook_utils.to_rel_posix(real_target, repo_root)
            if rel_posix:
                fallback = "??" if os.path.isfile(real_target) else ""
                targets.append((status_map.get(rel_posix, fallback), rel_posix))
        return targets

    tool_call = payload.get("toolCall") if isinstance(payload.get("toolCall"), dict) else {}
    args = tool_call.get("args") if isinstance(tool_call.get("args"), dict) else {}
    raw_target = hook_utils.extract_tool_target_path(args)
    if raw_target:
        real_target = hook_utils.resolve_target_path(raw_target, payload, repo_root)
        rel_posix = hook_utils.to_rel_posix(real_target, repo_root)
        if not rel_posix:
            return []
        fallback = "??" if os.path.isfile(real_target) else ""
        return [(status_map.get(rel_posix, fallback), rel_posix)]

    if strict_mode:
        return status_entries

    return []


def main(argv=None):
    parser = argparse.ArgumentParser(add_help=False)
    parser.add_argument("--strict", action="store_true")
    parser.add_argument("--files", nargs="*", default=[])
    parsed, _ = parser.parse_known_args(argv)

    payload = hook_utils.read_stdin_json()
    if payload.get("error") and not parsed.strict:
        print(json.dumps({}))
        return 0

    repo_root = hook_utils.find_repo_root(payload)
    targets = _resolve_targets(payload, repo_root, parsed.files, parsed.strict)
    if not targets:
        print(json.dumps({}))
        return 0

    violations = hook_utils.verify_repo_targets(repo_root, targets)
    if violations:
        if parsed.strict:
            for item in violations:
                sys.stderr.write(item + "\n")
            return 1
        _log_diagnostics(repo_root, violations)

    print(json.dumps({}))
    return 0


if __name__ == "__main__":
    sys.exit(main())
