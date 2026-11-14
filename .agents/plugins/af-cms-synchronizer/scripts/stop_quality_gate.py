#!/usr/bin/env python3
"""Stop quality gate for AF/Bulletin enforcing single-retry safety, PHP lint, boundary, and diff-scoped code separation."""

import json
import sys
import os

SCRIPTS_DIR = os.path.dirname(os.path.abspath(__file__))
if SCRIPTS_DIR not in sys.path:
    sys.path.insert(0, SCRIPTS_DIR)

import hook_utils


def _allow():
    print(json.dumps({"decision": "allow"}))
    return 0


def _continue(reason):
    print(json.dumps({"decision": "continue", "reason": reason}))
    return 0


def evaluate_stop_gate(payload, repo_root):
    """Return a list of blocking violation descriptions, or [] if stop is allowed."""
    if payload.get("continuationActive") is True:
        return []

    termination_reason = payload.get("terminationReason", "NO_TOOL_CALL")
    if isinstance(termination_reason, str) and termination_reason != "NO_TOOL_CALL":
        return []

    entries = hook_utils.get_git_status_entries(repo_root)
    if not entries:
        return []

    return hook_utils.verify_repo_targets(repo_root, entries)


def main():
    payload = hook_utils.read_stdin_json()
    repo_root = hook_utils.find_repo_root(payload)
    violations = evaluate_stop_gate(payload, repo_root)

    if violations:
        return _continue(
            "STOP REJECTED: Resolve the following quality violations before finishing:\n"
            + "\n---\n".join(violations)
        )

    return _allow()


if __name__ == "__main__":
    sys.exit(main())
