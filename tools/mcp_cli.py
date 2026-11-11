#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
tools/mcp_cli.py
Secret-Opaque JSON-RPC Stdio CLI Wrapper for amed-bulletin MCP Bridge
Amed Framework / Bulletin Editorial Pipeline (v1.0.63)

Provides a sanctioned, secret-opaque command-line interface for workspace sub-agents,
shell verification pipelines (verify_news_pipeline.sh), and automated tools to invoke
MCP tools via ~/.gemini/antigravity/bin/amed_bulletin_mcp.py without credential exposure.

Features:
1. Zero Credential Exposure: Never accepts, logs, or prints API keys or Bearer tokens.
2. Stdio Bridge Transport: Wraps amed_bulletin_mcp.py line-delimited JSON-RPC stdio protocol.
3. Editorial Safety Gate (--editorial-safe, default: True):
   - Unconditionally blocks all delete_* tools.
   - Enforces draft status (status="n") on staging mutations (create_news, update_news, create_faq, update_faq, batch_create_faqs).
   - Enforces author username="habermerkezi" on create_news.
   - Validates existence of image.file_path when specified.
4. CLI Subcommands & Syntax:
   - Subcommand mode:
     python3 tools/mcp_cli.py call <tool_name> [--args-json '<json>'] [--args-file /path] [--out /path]
     python3 tools/mcp_cli.py get-record --module <mod_name> (--id <id> | --slug <slug>) [--lang <lang>] [--out /path]
     python3 tools/mcp_cli.py ping
     python3 tools/mcp_cli.py list-tools
   - Positional mode (PROJECT.md contract parity):
     python3 tools/mcp_cli.py <server> <tool> [args_json] [--editorial-safe]
5. Standard Response Envelope:
   - Formatted JSON envelope on stdout.
   - Environment info (environment.name) printed to stderr.
   - Exit code: 0 on success, 1 on tool/policy/bridge error, 2 on invalid CLI usage.
6. Offline Testability:
   - MCP_CLI_BRIDGE env var allows redirecting to a test/mock bridge.
   - --dry-run flag validates arguments and policy without executing bridge subprocess.
"""

import argparse
import json
import os
from pathlib import Path
import re
import subprocess
import sys
import time
from typing import Any, Dict, List, Optional, Tuple, Union

# ---------------------------------------------------------------------------
# Constants & Bridge Discovery
# ---------------------------------------------------------------------------

DEFAULT_BRIDGE_LOCATIONS = [
    Path.home() / ".gemini" / "antigravity" / "bin" / "amed_bulletin_mcp.py",
    Path.home() / ".gemini" / "antigravity-cli" / "bin" / "amed_bulletin_mcp.py",
]

KNOWN_SERVER_NAMES = {
    "amed-bulletin",
    "amed_bulletin",
    "amed-bulletin-mcp",
    "amed_bulletin_mcp",
    "bulletin",
}

MODULE_TOOL_MAP = {
    "mod_news": "get_news",
    "news": "get_news",
    "mod_publication": "get_publication",
    "publication": "get_publication",
    "mod_announcement": "get_announcement",
    "announcement": "get_announcement",
    "mod_page": "get_page",
    "page": "get_page",
    "mod_headline": "get_headline",
    "headline": "get_headline",
    "mod_faq": "get_faq",
    "faq": "get_faq",
}

FORBIDDEN_TOOL_PREFIXES = ("delete_",)


class EditorialPolicyViolation(Exception):
    """Raised when a tool invocation violates the editorial-safe policy contract."""
    pass


class BridgeError(Exception):
    """Raised when the stdio bridge fails to execute or returns invalid responses."""
    pass


# ---------------------------------------------------------------------------
# Bridge Resolution
# ---------------------------------------------------------------------------

def resolve_bridge_path(override_path: Optional[Union[str, Path]] = None) -> Path:
    """
    Resolves the executable bridge script path.
    Precedence:
    1. Explicit override_path (if provided)
    2. MCP_CLI_BRIDGE environment variable
    3. Standard user home locations (~/.gemini/antigravity/bin/amed_bulletin_mcp.py)
    """
    if override_path:
        p = Path(override_path).resolve()
        if p.is_file():
            return p
        raise FileNotFoundError(f"Specified MCP bridge script not found: {p}")

    env_bridge = os.environ.get("MCP_CLI_BRIDGE")
    if env_bridge:
        p = Path(env_bridge).resolve()
        if p.is_file():
            return p
        raise FileNotFoundError(f"MCP_CLI_BRIDGE environment variable points to non-existent file: {p}")

    for candidate in DEFAULT_BRIDGE_LOCATIONS:
        if candidate.is_file():
            return candidate

    locations_str = "\n  - ".join(str(p) for p in DEFAULT_BRIDGE_LOCATIONS)
    raise FileNotFoundError(
        f"Stdio MCP bridge script not found. Looked in:\n  - {locations_str}\n"
        "Set MCP_CLI_BRIDGE environment variable to point to a valid bridge script."
    )


# ---------------------------------------------------------------------------
# Editorial Safety Policy Engine
# ---------------------------------------------------------------------------

def enforce_editorial_policy(tool_name: str, arguments: Dict[str, Any], enabled: bool = True) -> Dict[str, Any]:
    """
    Enforces editorial safety constraints on tool invocations.
    When enabled (default: True):
    - Blocks delete_* tools unconditionally.
    - Forces draft status ('n') on create/update operations for news, publication, and FAQs.
    - Rejects any attempt to publish ('status' == 'y').
    - Enforces author username='habermerkezi' on create_news.
    - Validates featured image file existence on create_news.
    """
    if not enabled:
        return arguments

    # 1. Unconditionally block destructive tools
    for prefix in FORBIDDEN_TOOL_PREFIXES:
        if tool_name.startswith(prefix):
            raise EditorialPolicyViolation(
                f"Destructive tool '{tool_name}' is prohibited under --editorial-safe policy."
            )

    # Shallow copy arguments to prevent in-place mutation of caller data
    args = dict(arguments)

    # 2. Author enforcement on create_news
    if tool_name == "create_news":
        username = str(args.get("username") or "").strip()
        if not username or username.lower() == "admin":
            args["username"] = "habermerkezi"

    # 3. Status enforcement on news and publications
    if tool_name in ("create_news", "create_publication"):
        status = str(args.get("status") or "").strip().lower()
        if status == "y":
            raise EditorialPolicyViolation(
                f"Publishing status 'y' is prohibited on '{tool_name}'. "
                "New records must be created with status 'n' (draft)."
            )
        args["status"] = "n"

    elif tool_name in ("update_news", "update_publication"):
        if "status" in args:
            status = str(args.get("status") or "").strip().lower()
            if status == "y":
                raise EditorialPolicyViolation(
                    f"Flipping status to published ('y') is prohibited on '{tool_name}'. "
                    "Draft articles cannot be published via automated MCP tools."
                )
            if status != "n":
                args["status"] = "n"

    # 4. Status enforcement on single FAQs
    elif tool_name in ("create_faq", "update_faq"):
        status = str(args.get("status") or "").strip().lower()
        if status == "y":
            raise EditorialPolicyViolation(
                f"Publishing status 'y' is prohibited on '{tool_name}'. "
                "FAQs must be staged with status 'n' (draft)."
            )
        args["status"] = "n"

    # 5. Status enforcement on batch_create_faqs
    elif tool_name == "batch_create_faqs":
        top_status = str(args.get("status") or "").strip().lower()
        if top_status == "y":
            raise EditorialPolicyViolation(
                "Publishing status 'y' is prohibited in 'batch_create_faqs'. "
                "All batch items must be staged with status 'n'."
            )
        args["status"] = "n"

        items = args.get("items")
        if not isinstance(items, list):
            raise EditorialPolicyViolation("Tool 'batch_create_faqs' requires an 'items' array of FAQ objects.")

        sanitized_items = []
        for idx, item in enumerate(items):
            if not isinstance(item, dict):
                raise EditorialPolicyViolation(f"Item #{idx} in batch_create_faqs is not a valid object.")
            item_copy = dict(item)
            item_status = str(item_copy.get("status") or "").strip().lower()
            if item_status == "y":
                raise EditorialPolicyViolation(
                    f"Item #{idx} in batch_create_faqs has status='y' (published), which is prohibited."
                )
            item_copy["status"] = "n"
            sanitized_items.append(item_copy)
        args["items"] = sanitized_items

    # 6. Featured image file path validation on create_news and update_news
    if tool_name in ("create_news", "update_news"):
        img = args.get("image")
        if isinstance(img, dict) and "file_path" in img:
            file_path = str(img["file_path"]).strip()
            if file_path and not Path(file_path).is_file():
                raise EditorialPolicyViolation(
                    f"Featured image file specified in image.file_path does not exist: {file_path}"
                )

    return args


# ---------------------------------------------------------------------------
# JSON-RPC Bridge Communication
# ---------------------------------------------------------------------------

def execute_rpc(
    bridge_path: Path,
    method: str,
    params: Optional[Dict[str, Any]] = None,
    timeout: int = 45,
    request_id: Optional[Union[str, int]] = None,
) -> Tuple[int, Dict[str, Any]]:
    """
    Executes a single line-delimited JSON-RPC 2.0 exchange with the stdio bridge.
    Returns: (exit_code, response_envelope)
    Zero Credential Exposure: Never accepts or injects API keys; child bridge handles transport.
    """
    if request_id is None:
        request_id = f"cli_{int(time.time() * 1000)}"

    rpc_request: Dict[str, Any] = {
        "jsonrpc": "2.0",
        "id": request_id,
        "method": method,
    }
    if params is not None:
        rpc_request["params"] = params

    try:
        proc = subprocess.Popen(
            [sys.executable, str(bridge_path)],
            stdin=subprocess.PIPE,
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE,
            text=True,
            encoding="utf-8",
        )
    except Exception as e:
        return 1, {
            "jsonrpc": "2.0",
            "id": request_id,
            "error": {"code": -32603, "message": f"Failed to spawn MCP bridge process: {e}"},
            "isError": True,
        }

    try:
        input_data = json.dumps(rpc_request, ensure_ascii=False) + "\n"
        stdout_raw, stderr_raw = proc.communicate(input=input_data, timeout=timeout)
    except subprocess.TimeoutExpired:
        proc.kill()
        proc.communicate()
        return 1, {
            "jsonrpc": "2.0",
            "id": request_id,
            "error": {"code": -32603, "message": f"MCP bridge timed out after {timeout} seconds"},
            "isError": True,
        }
    except Exception as e:
        proc.kill()
        return 1, {
            "jsonrpc": "2.0",
            "id": request_id,
            "error": {"code": -32603, "message": f"MCP bridge execution error: {e}"},
            "isError": True,
        }

    # Scrub stderr to ensure no raw token strings are ever printed
    if stderr_raw and stderr_raw.strip():
        sanitized_stderr = re.sub(r"amcp_[a-zA-Z0-9]+", "[REDACTED_API_KEY]", stderr_raw.strip())
        print(f"[bridge] {sanitized_stderr}", file=sys.stderr)

    output_lines = [line.strip() for line in stdout_raw.splitlines() if line.strip()]
    if not output_lines:
        return 1, {
            "jsonrpc": "2.0",
            "id": request_id,
            "error": {"code": -32603, "message": "MCP bridge exited without emitting JSON-RPC output"},
            "isError": True,
        }

    response_obj: Optional[Dict[str, Any]] = None
    for line in reversed(output_lines):
        try:
            parsed = json.loads(line)
            if isinstance(parsed, dict) and ("jsonrpc" in parsed or "result" in parsed or "error" in parsed):
                response_obj = parsed
                break
        except json.JSONDecodeError:
            continue

    if response_obj is None:
        return 1, {
            "jsonrpc": "2.0",
            "id": request_id,
            "error": {"code": -32700, "message": f"Failed to decode bridge JSON output: {stdout_raw[:250]}"},
            "isError": True,
        }

    # Check for protocol error or MCP tool error
    is_error = False
    if "error" in response_obj:
        is_error = True
    elif isinstance(response_obj.get("result"), dict) and response_obj["result"].get("isError") is True:
        is_error = True

    return (1 if is_error else 0), response_obj


# ---------------------------------------------------------------------------
# Diagnostics & Environment Extraction
# ---------------------------------------------------------------------------

def extract_environment_name(response_data: Dict[str, Any]) -> str:
    """Extracts environment.name from MCP response payload for stderr reporting."""
    result = response_data.get("result")
    if isinstance(result, dict):
        meta = result.get("_meta")
        if isinstance(meta, dict) and isinstance(meta.get("environment"), dict):
            name = meta["environment"].get("name")
            if name:
                return str(name)

        struct = result.get("structuredContent")
        if isinstance(struct, dict) and isinstance(struct.get("environment"), dict):
            name = struct["environment"].get("name")
            if name:
                return str(name)

        if isinstance(result.get("environment"), dict):
            name = result["environment"].get("name")
            if name:
                return str(name)

    return "unknown"


# ---------------------------------------------------------------------------
# Subcommand Handlers
# ---------------------------------------------------------------------------

def handle_call(args: argparse.Namespace) -> int:
    """Executes a generic tools/call invocation."""
    tool_name = args.tool.strip()

    # Load arguments from JSON string or file
    arguments: Dict[str, Any] = {}
    if getattr(args, "args_json", None):
        try:
            parsed_args = json.loads(args.args_json)
            if not isinstance(parsed_args, dict):
                print("Error: --args-json must be a JSON object string.", file=sys.stderr)
                return 2
            arguments = parsed_args
        except json.JSONDecodeError as e:
            print(f"Error: Failed to parse --args-json: {e}", file=sys.stderr)
            return 2
    elif getattr(args, "args_file", None):
        file_path = Path(args.args_file)
        if not file_path.is_file():
            print(f"Error: Argument file not found: {file_path}", file=sys.stderr)
            return 2
        try:
            parsed_args = json.loads(file_path.read_text(encoding="utf-8"))
            if not isinstance(parsed_args, dict):
                print(f"Error: File '{file_path}' must contain a JSON object.", file=sys.stderr)
                return 2
            arguments = parsed_args
        except json.JSONDecodeError as e:
            print(f"Error: Failed to parse JSON in '{file_path}': {e}", file=sys.stderr)
            return 2

    # Enforce editorial safety policy
    editorial_safe = not getattr(args, "no_editorial_safe", False)
    try:
        sanitized_args = enforce_editorial_policy(tool_name, arguments, enabled=editorial_safe)
    except EditorialPolicyViolation as e:
        err_envelope = {
            "jsonrpc": "2.0",
            "id": f"cli_policy_err_{int(time.time())}",
            "error": {"code": -32600, "message": str(e)},
            "isError": True,
        }
        print(json.dumps(err_envelope, indent=2, ensure_ascii=False))
        print(f"[mcp_cli] Policy rejection: {e}", file=sys.stderr)
        return 1

    # Dry-run validation mode
    if getattr(args, "dry_run", False):
        dry_envelope = {
            "jsonrpc": "2.0",
            "id": "dry_run",
            "method": "tools/call",
            "params": {"name": tool_name, "arguments": sanitized_args},
            "_meta": {"dry_run": True, "editorial_safe": editorial_safe},
        }
        print(json.dumps(dry_envelope, indent=2, ensure_ascii=False))
        print("[mcp_cli] DRY-RUN: Validation passed. Request not dispatched to bridge.", file=sys.stderr)
        return 0

    # Locate bridge script
    try:
        bridge_path = resolve_bridge_path(getattr(args, "bridge", None))
    except FileNotFoundError as e:
        print(f"Error: {e}", file=sys.stderr)
        return 1

    # Execute RPC call
    exit_code, response = execute_rpc(
        bridge_path=bridge_path,
        method="tools/call",
        params={"name": tool_name, "arguments": sanitized_args},
        timeout=getattr(args, "timeout", 45),
    )

    env_name = extract_environment_name(response)
    print(f"[mcp_cli] Environment: {env_name}", file=sys.stderr)

    formatted_json = json.dumps(response, indent=2, ensure_ascii=False)
    print(formatted_json)

    if getattr(args, "out", None):
        out_path = Path(args.out)
        out_path.parent.mkdir(parents=True, exist_ok=True)
        out_path.write_text(formatted_json, encoding="utf-8")

    return exit_code


def handle_get_record(args: argparse.Namespace) -> int:
    """Fetches a single record and formats output for post-staging audits."""
    module_key = args.module.strip().lower()
    tool_name = MODULE_TOOL_MAP.get(module_key)
    if not tool_name:
        valid_mods = ", ".join(sorted(MODULE_TOOL_MAP.keys()))
        print(f"Error: Unsupported module '{args.module}'. Supported modules: {valid_mods}", file=sys.stderr)
        return 2

    if not args.id and not args.slug:
        print("Error: Either --id or --slug must be provided to get-record.", file=sys.stderr)
        return 2

    arguments: Dict[str, Any] = {}
    if args.id:
        try:
            arguments["id"] = int(args.id)
        except ValueError:
            print(f"Error: --id must be an integer, got: {args.id}", file=sys.stderr)
            return 2
    elif args.slug:
        arguments["seo"] = args.slug.strip()

    if args.lang:
        arguments["lang"] = args.lang.strip()

    # Dry-run validation mode
    if getattr(args, "dry_run", False):
        dry_envelope = {
            "jsonrpc": "2.0",
            "id": "dry_run",
            "method": "tools/call",
            "params": {"name": tool_name, "arguments": arguments},
            "_meta": {"dry_run": True, "target_module": module_key},
        }
        print(json.dumps(dry_envelope, indent=2, ensure_ascii=False))
        print(f"[mcp_cli] DRY-RUN: get-record for {module_key} validated.", file=sys.stderr)
        return 0

    try:
        bridge_path = resolve_bridge_path(getattr(args, "bridge", None))
    except FileNotFoundError as e:
        print(f"Error: {e}", file=sys.stderr)
        return 1

    exit_code, response = execute_rpc(
        bridge_path=bridge_path,
        method="tools/call",
        params={"name": tool_name, "arguments": arguments},
        timeout=getattr(args, "timeout", 45),
    )

    env_name = extract_environment_name(response)
    print(f"[mcp_cli] Environment: {env_name}", file=sys.stderr)

    formatted_json = json.dumps(response, indent=2, ensure_ascii=False)
    print(formatted_json)

    if getattr(args, "out", None):
        out_path = Path(args.out)
        out_path.parent.mkdir(parents=True, exist_ok=True)
        out_path.write_text(formatted_json, encoding="utf-8")

    return exit_code


def handle_ping(args: argparse.Namespace) -> int:
    """Performs a live connectivity ping to verify the bridge and server."""
    try:
        bridge_path = resolve_bridge_path(getattr(args, "bridge", None))
    except FileNotFoundError as e:
        print(f"Error: {e}", file=sys.stderr)
        return 1

    exit_code, response = execute_rpc(
        bridge_path=bridge_path,
        method="ping",
        timeout=getattr(args, "timeout", 45),
    )

    formatted_json = json.dumps(response, indent=2, ensure_ascii=False)
    print(formatted_json)
    return exit_code


def handle_list_tools(args: argparse.Namespace) -> int:
    """Lists available tools exposed by the MCP bridge."""
    try:
        bridge_path = resolve_bridge_path(getattr(args, "bridge", None))
    except FileNotFoundError as e:
        print(f"Error: {e}", file=sys.stderr)
        return 1

    exit_code, response = execute_rpc(
        bridge_path=bridge_path,
        method="tools/list",
        timeout=getattr(args, "timeout", 45),
    )

    formatted_json = json.dumps(response, indent=2, ensure_ascii=False)
    print(formatted_json)
    return exit_code


# ---------------------------------------------------------------------------
# CLI Argument Dispatcher & Parser
# ---------------------------------------------------------------------------

def build_parser() -> argparse.ArgumentParser:
    parser = argparse.ArgumentParser(
        prog="mcp_cli.py",
        description="Secret-Opaque JSON-RPC Stdio CLI Wrapper for amed-bulletin MCP Bridge",
    )
    parser.add_argument("--bridge", type=Path, help="Explicit path to amed_bulletin_mcp.py bridge script")
    parser.add_argument("--timeout", type=int, default=45, help="Subprocess timeout in seconds (default: 45)")

    subparsers = parser.add_subparsers(dest="subcommand", help="Available subcommands")

    # Subcommand: call
    call_parser = subparsers.add_parser("call", help="Execute an MCP tool call")
    call_parser.add_argument("tool", help="Name of the MCP tool to invoke (e.g. get_news, create_news)")
    call_parser.add_argument("--args-json", help="JSON string representing tool arguments object")
    call_parser.add_argument("--args-file", help="Path to JSON file representing tool arguments object")
    call_parser.add_argument("--out", help="Path to save output JSON envelope")
    call_parser.add_argument("--editorial-safe", dest="editorial_safe", action="store_true", default=True,
                             help="Enforce editorial safety gates (default: active)")
    call_parser.add_argument("--no-editorial-safe", dest="no_editorial_safe", action="store_true",
                             help="Bypass editorial safety gates (disabled by default)")
    call_parser.add_argument("--dry-run", action="store_true", help="Validate arguments and policy without executing")

    # Subcommand: get-record
    rec_parser = subparsers.add_parser("get-record", help="Retrieve a CMS record for audit_content_seo")
    rec_parser.add_argument("--module", required=True, help="CMS module name (e.g. mod_news, mod_publication, mod_page)")
    rec_parser.add_argument("--id", help="Record primary integer ID")
    rec_parser.add_argument("--slug", "--seo", dest="slug", help="Record SEO slug")
    rec_parser.add_argument("--lang", help="Target record language code (e.g. tr, ku, en)")
    rec_parser.add_argument("--out", help="Path to save output JSON envelope")
    rec_parser.add_argument("--dry-run", action="store_true", help="Validate arguments without executing")

    # Subcommand: ping
    subparsers.add_parser("ping", help="Send a JSON-RPC ping to verify bridge connectivity")

    # Subcommand: list-tools
    subparsers.add_parser("list-tools", help="Query tools/list from the bridge")

    return parser


def parse_and_dispatch(argv: List[str]) -> int:
    """
    Main entrypoint dispatcher.
    Detects whether argv uses positional syntax (`mcp_cli.py <server> <tool> [args]`)
    or standard subcommand syntax (`mcp_cli.py call <tool> ...`).
    """
    if len(argv) < 1:
        build_parser().print_help()
        return 2

    # Check for Positional Syntax: `mcp_cli.py <server> <tool> [args_json_or_flags...]`
    first_token = argv[0].strip().lower()
    known_subcommands = {"call", "get-record", "ping", "list-tools", "-h", "--help"}

    if first_token not in known_subcommands and not first_token.startswith("-"):
        # Check if first token is a server name (e.g. amed-bulletin)
        if first_token in KNOWN_SERVER_NAMES or (len(argv) >= 2 and not argv[1].startswith("-")):
            server_name = argv[0]
            tool_name = argv[1] if len(argv) > 1 else ""
            remaining = argv[2:]

            if not tool_name:
                print(f"Error: Server '{server_name}' specified but tool name is missing.", file=sys.stderr)
                return 2

            # Extract global flags (--bridge, --timeout) to prepend before subcommands
            global_flags: List[str] = []
            non_global_remaining: List[str] = []
            skip_next = False
            for i, tok in enumerate(remaining):
                if skip_next:
                    skip_next = False
                    continue
                if tok in ("--bridge", "--timeout"):
                    if i + 1 < len(remaining):
                        global_flags.extend([tok, remaining[i + 1]])
                        skip_next = True
                else:
                    non_global_remaining.append(tok)

            parser = build_parser()
            tool_sub = tool_name.strip().lower()

            # Route positional subcommands to their designated handlers
            if tool_sub == "ping":
                parsed_args = parser.parse_args(global_flags + ["ping"])
                return handle_ping(parsed_args)

            elif tool_sub in ("list-tools", "tools-list", "list_tools"):
                parsed_args = parser.parse_args(global_flags + ["list-tools"])
                return handle_list_tools(parsed_args)

            elif tool_sub in ("get-record", "get_record"):
                sub_argv = ["get-record"] + non_global_remaining
                parsed_args = parser.parse_args(global_flags + sub_argv)
                return handle_get_record(parsed_args)

            elif tool_sub == "call":
                if not non_global_remaining:
                    print("Error: Subcommand 'call' requires a tool name.", file=sys.stderr)
                    return 2
                tool_name = non_global_remaining[0]
                non_global_remaining = non_global_remaining[1:]

            # Translate generic positional tool syntax into `call <tool_name>` arguments
            call_argv = ["call", tool_name]
            args_json_candidate = None
            skip_next = False

            for i, tok in enumerate(non_global_remaining):
                if skip_next:
                    skip_next = False
                    continue
                if tok in ("--args-json", "--args-file", "--out"):
                    if i + 1 < len(non_global_remaining):
                        call_argv.extend([tok, non_global_remaining[i + 1]])
                        skip_next = True
                elif tok in ("--editorial-safe", "--no-editorial-safe", "--dry-run"):
                    call_argv.append(tok)
                elif tok.startswith("{") and tok.endswith("}"):
                    args_json_candidate = tok
                else:
                    call_argv.append(tok)

            if args_json_candidate and "--args-json" not in call_argv and "--args-file" not in call_argv:
                call_argv.extend(["--args-json", args_json_candidate])

            parsed_args = parser.parse_args(global_flags + call_argv)
            return handle_call(parsed_args)

    parser = build_parser()
    args = parser.parse_args(argv)

    if args.subcommand == "call":
        return handle_call(args)
    elif args.subcommand == "get-record":
        return handle_get_record(args)
    elif args.subcommand == "ping":
        return handle_ping(args)
    elif args.subcommand == "list-tools":
        return handle_list_tools(args)
    else:
        parser.print_help()
        return 2


def main() -> None:
    exit_code = parse_and_dispatch(sys.argv[1:])
    sys.exit(exit_code)


if __name__ == "__main__":
    main()
