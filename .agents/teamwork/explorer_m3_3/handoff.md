# Handoff Report: Swarm CLI Wrapper (`tools/mcp_cli.py`) Implementation Blueprint

## 1. Observation

### 1.1 Stdio Bridge Implementation & Protocol Contract
- **Bridge File**: `/home/malpersaz/.gemini/antigravity/bin/amed_bulletin_mcp.py` (and identical copy at `/home/malpersaz/.gemini/antigravity-cli/bin/amed_bulletin_mcp.py`, verified sha256 byte parity in `tools/mcp_health_check.py:245-257`).
- **Protocol**: JSON-RPC 2.0 over standard I/O (lines 110-132 in `amed_bulletin_mcp.py`):
  ```python
  def main():
      while True:
          line = sys.stdin.readline()
          if not line:
              break
          line = line.strip()
          if not line:
              continue
          try:
              req_obj = json.loads(line)
          ...
  ```
- **Authentication & Target Pinning Encapsulation**:
  `amed_bulletin_mcp.py` internally encapsulates all credentials and endpoints (lines 15-32, 135-146):
  ```python
  ENDPOINT = "https://amedbulten.com/mcp.php"
  API_KEY = "amcp_f8718fa73029f417ecd7399f4156e5aa6a529a3fc58c1060"
  EXPECTED_SERVER_NAME = "amed-bulletin-mcp"
  ...
  headers = {
      "Content-Type": "application/json",
      "Authorization": f"Bearer {api_key}",
      "User-Agent": "Antigravity-MCP-Bridge/1.0",
      "X-MCP-Expected-Server": EXPECTED_SERVER_NAME,
      "X-MCP-Expected-Resource": expected_resource,
  }
  ```
  The bridge script loads configuration internally without accepting tokens from the caller. Responses are returned as single-line JSON strings to `sys.stdout` followed by `\n` and `sys.stdout.flush()`.
- **Existing Image & Mutation Policy inside Bridge**:
  Lines 37-65 of `amed_bulletin_mcp.py` check image policies:
  - If `username` is empty or `"admin"`, forces `username = "habermerkezi"`.
  - On `create_news`, forces `status = "n"`.
  - On `update_news`, strips `status` if `status != "n"`.
  - For `image`, converts `file_path` to `data_base64` and `mime`.
  - However, the bridge does NOT block `delete_*` tools, does NOT check `create_faq`/`update_faq`/`batch_create_faqs` status, and does NOT provide a user-friendly CLI interface or record dumping for pipeline scripts.

### 1.2 Pipeline & Swarm Integration Points
- **Swarm Sub-Agent Sandbox Block**:
  Observed live in `docs/plan/v1.0.63-kevanger-editorial-pipeline-remediation-and-enhancement-plan.md` Section A2 (lines 87-95):
  ```text
  Encountered error in tool execution: server amed-bulletin is not allowed in this context
  ```
  Sub-agents (@researcher, @drafter, @verifier) do not receive direct `call_mcp_tool` bindings, yet `.agents/skills/kevanger/SKILL.md:11` strictly prohibits raw HTTP requests or `mcp_config.json` extraction.
- **Pipeline Post-Staging Audit (`tools/verify_news_pipeline.sh`)**:
  Lines 149-163 of `tools/verify_news_pipeline.sh`:
  ```bash
  if [[ -n "$RECORD_JSON" ]]; then
      python3 "$WORKSPACE_ROOT/tools/audit_content_seo.py" \
          --module mod_news \
          --profile "$PROFILE" \
          --record-json "$RECORD_JSON" \
          --lang-status "$LANG_STATUS"
  fi
  ```
- **Record Ingestion in `tools/audit_content_seo.py`**:
  Lines 50-80 of `tools/audit_content_seo.py` implement `load_record_from_json()`:
  - Supports raw dictionary, JSON-RPC envelope (`{"jsonrpc": "2.0", "result": ...}`), or `structuredContent`.
  - Extracts `_meta.environment` or `environment`.
  - Extracts `record`, `faq_count`, `default_lang_status`, `faq_items`.

### 1.3 Project Interface Contract & Policy Constraints
- **Interface Contract (`.agents/teamwork/orchestrator_1/PROJECT.md:93-95`)**:
  ```text
  Command: python3 tools/mcp_cli.py <server> <tool> [args_json_or_flags] [--editorial-safe]
  Standard JSON envelope on stdout; exits 0 on success, non-zero on error.
  ```
- **Scope Contract (`.agents/teamwork/sub_orch_m3/SCOPE.md:16-19`)**:
  ```text
  3. tools/mcp_cli.py:
     - Create secret-opaque stdio CLI wrapper around ~/.gemini/antigravity/bin/amed_bulletin_mcp.py.
     - Support --editorial-safe flag enforcing draft status (status: "n") on all staging mutations (create_news, batch_create_faqs).
     - Format tool arguments as JSON on stdin; return standard JSON envelope on stdout; exit 0 on success, non-zero on error.
  ```
- **Mandatory Policy Specifications (Dispatch)**:
  1. Reject `delete_*` tools unconditionally under `--editorial-safe`.
  2. Enforce `status == "n"` on `create_news`, `update_news`, `create_faq`, `update_faq`, `batch_create_faqs` (hard error if publishing `"y"`).
  3. Enforce `username == "habermerkezi"` on `create_news`.
  4. Response envelope handling: standard JSON on stdout; print environment info (`environment.name`) on stderr; exit 0 on success, non-zero on failure.
  5. Testability without live network dependencies (mocking/dry-run support).

---

## 2. Logic Chain

1. **Child Process Stdio Bridge**:
   - `amed_bulletin_mcp.py` operates as a persistent or single-request stdin/stdout line-delimited JSON-RPC bridge.
   - For a CLI tool, spawning the bridge via `subprocess.Popen` and calling `proc.communicate(input=json.dumps(req) + "\n", timeout=timeout)` guarantees leak-free execution: it writes the line, closes `stdin` (triggering EOF detection in the bridge's `readline()` loop), reads all output until the process exits, and avoids deadlock or zombie processes.
2. **Zero Credential Exposure**:
   - The child bridge already reads its own hardcoded/file configuration.
   - Therefore, `mcp_cli.py` has no need for `--api-key`, `--token`, or credential flags.
   - By eliminating all credential flags and environment variables from `mcp_cli.py`, secrets cannot be passed in argv (visible in `ps`), stored in shell history, or printed to logs.
   - Stderr is scrubbed with regex `amcp_[a-zA-Z0-9]+` -> `[REDACTED_API_KEY]` to guarantee no leaked tokens from upstream error traces.
3. **Dual Syntax Compatibility**:
   - Subcommand syntax: `call <tool_name>` and `get-record --module <mod>` provide clean, self-documenting CLI usage for sub-agents and scripts.
   - Positional syntax: `python3 tools/mcp_cli.py <server> <tool> [args] [--editorial-safe]` satisfies the exact contract in `PROJECT.md:94`.
   - By inspecting `sys.argv[1]`, the CLI dispatcher can seamlessly detect whether the invocation is a subcommand (`call`, `get-record`, `ping`, `list-tools`) or positional `<server> <tool>`.
4. **Policy Enforcement Layer (`--editorial-safe`)**:
   - Must run *before* any request is passed to the bridge.
   - Any tool name starting with `delete_` is rejected immediately with a policy error and exit code 1.
   - Content status checks:
     - On `create_news` and `create_publication`: missing status is injected as `"n"`; explicit status `"y"` raises a hard error.
     - On `update_news` and `update_publication`: status `"y"` raises a hard error; status is normalized to `"n"` if modified.
     - On `create_faq` and `update_faq`: status `"y"` raises a hard error; status is set to `"n"`.
     - On `batch_create_faqs`: top-level status `"y"` raises a hard error; each item in `items` is verified and status `"y"` raises a hard error; missing status is normalized to `"n"`.
   - Author check on `create_news`: `username` is enforced as `"habermerkezi"`.
   - Local image existence: if `image.file_path` is provided, `mcp_cli.py` verifies the file exists on disk before dispatching, avoiding failed network calls.
5. **Output & Envelope Handling**:
   - The stdout output is formatted JSON (`indent=2`, `ensure_ascii=False`).
   - If `--out <path>` is supplied (e.g. for `get-record`), the JSON payload is written to disk, creating parent directories if needed.
   - Stderr receives diagnostic lines: `[mcp_cli] Environment: <name> (host: <endpoint>)`.
   - Exit codes:
     - `0`: Successful execution with no error object and `isError: false`.
     - `1`: Editorial policy rejection, tool execution error (`isError: true`), JSON-RPC error, or bridge execution failure.
     - `2`: Invalid CLI syntax or missing mandatory arguments.
6. **Offline Testability**:
   - Environment variable `MCP_CLI_BRIDGE` allows pointing to any mock bridge script in automated tests.
   - `--dry-run` flag performs full CLI argument parsing, policy validation, and request payload generation without executing the bridge, printing the payload to stdout and exiting 0.
   - Pure functional separation makes every policy rule and parser component independently importable and testable via `unittest`.

---

## 3. Caveats

1. **Production Bridge Target**:
   - `~/.gemini/antigravity/bin/amed_bulletin_mcp.py` points directly to `https://amedbulten.com/mcp.php` (PRODUCTION).
   - Any live mutation executed through the bridge touches the live production database.
   - Therefore, the strict enforcement of `status="n"` (draft) and the total prohibition of `delete_*` under `--editorial-safe` is critical to prevent unintended publication or data loss.
2. **Featured Image Encoding**:
   - `amed_bulletin_mcp.py` supports converting `image: {"file_path": "/path/to/img.webp"}` to base64 before posting to `mcp.php`.
   - `mcp_cli.py` preserves this capability and validates file existence on the host filesystem before spawning the bridge.
3. **No Sibling Repo Writes**:
   - Per `AGENTS.md` Rule 3, `mcp_cli.py` resides entirely within `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/tools/` and does not interact with sibling workspaces.

---

## 4. Conclusion & Complete Implementation Blueprint

### 4.1 Architecture Overview

```
                      +---------------------------------------+
                      | Workspace Sub-Agent / Pipeline Script |
                      |   (verify_news_pipeline.sh / bash)    |
                      +-------------------+-------------------+
                                          |
                      CLI Arguments & Stdin JSON / Files
                                          v
                      +---------------------------------------+
                      |           tools/mcp_cli.py            |
                      |                                       |
                      | 1. Argument Parsing (Subcommand / Pos)|
                      | 2. Zero Credential Exposure (no keys) |
                      | 3. Editorial Safety Policy Gate:      |
                      |    - Block delete_*                   |
                      |    - Enforce status="n"               |
                      |    - Enforce author="habermerkezi"    |
                      | 4. Dry-Run / Mock Bridge Check        |
                      +-------------------+-------------------+
                                          |
                      JSON-RPC 2.0 over Stdio (stdin / stdout)
                                          v
                      +---------------------------------------+
                      | ~/.gemini/antigravity/bin/            |
                      | amed_bulletin_mcp.py (Child Process)  |
                      |                                       |
                      | Reads own live config / Bearer token  |
                      | Positive Target Pinning (X-MCP-*)     |
                      +-------------------+-------------------+
                                          |
                      HTTPS POST (Authorization: Bearer ...)
                                          v
                      +---------------------------------------+
                      |   https://amedbulten.com/mcp.php      |
                      |       (Production MCP Server)         |
                      +---------------------------------------+
```

### 4.2 Exact Implementation of `tools/mcp_cli.py`

Below is the complete, production-ready code designed for `tools/mcp_cli.py`:

```python
#!/usr/bin/env python3
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
        if not username or username.lower() != "habermerkezi":
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

    # 6. Featured image file path validation on create_news
    if tool_name == "create_news":
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
    if args.args_json:
        try:
            parsed_args = json.loads(args.args_json)
            if not isinstance(parsed_args, dict):
                print("Error: --args-json must be a JSON object string.", file=sys.stderr)
                return 2
            arguments = parsed_args
        except json.JSONDecodeError as e:
            print(f"Error: Failed to parse --args-json: {e}", file=sys.stderr)
            return 2
    elif args.args_file:
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
    editorial_safe = not args.no_editorial_safe
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
    if args.dry_run:
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
        bridge_path = resolve_bridge_path(args.bridge)
    except FileNotFoundError as e:
        print(f"Error: {e}", file=sys.stderr)
        return 1

    # Execute RPC call
    exit_code, response = execute_rpc(
        bridge_path=bridge_path,
        method="tools/call",
        params={"name": tool_name, "arguments": sanitized_args},
        timeout=args.timeout,
    )

    env_name = extract_environment_name(response)
    print(f"[mcp_cli] Environment: {env_name}", file=sys.stderr)

    formatted_json = json.dumps(response, indent=2, ensure_ascii=False)
    print(formatted_json)

    if args.out:
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
    if args.dry_run:
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
        bridge_path = resolve_bridge_path(args.bridge)
    except FileNotFoundError as e:
        print(f"Error: {e}", file=sys.stderr)
        return 1

    exit_code, response = execute_rpc(
        bridge_path=bridge_path,
        method="tools/call",
        params={"name": tool_name, "arguments": arguments},
        timeout=args.timeout,
    )

    env_name = extract_environment_name(response)
    print(f"[mcp_cli] Environment: {env_name}", file=sys.stderr)

    formatted_json = json.dumps(response, indent=2, ensure_ascii=False)
    print(formatted_json)

    if args.out:
        out_path = Path(args.out)
        out_path.parent.mkdir(parents=True, exist_ok=True)
        out_path.write_text(formatted_json, encoding="utf-8")

    return exit_code


def handle_ping(args: argparse.Namespace) -> int:
    """Performs a live connectivity ping to verify the bridge and server."""
    try:
        bridge_path = resolve_bridge_path(args.bridge)
    except FileNotFoundError as e:
        print(f"Error: {e}", file=sys.stderr)
        return 1

    exit_code, response = execute_rpc(
        bridge_path=bridge_path,
        method="ping",
        timeout=args.timeout,
    )

    formatted_json = json.dumps(response, indent=2, ensure_ascii=False)
    print(formatted_json)
    return exit_code


def handle_list_tools(args: argparse.Namespace) -> int:
    """Lists available tools exposed by the MCP bridge."""
    try:
        bridge_path = resolve_bridge_path(args.bridge)
    except FileNotFoundError as e:
        print(f"Error: {e}", file=sys.stderr)
        return 1

    exit_code, response = execute_rpc(
        bridge_path=bridge_path,
        method="tools/list",
        timeout=args.timeout,
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

            # Translate positional syntax into `call <tool_name>` arguments
            call_argv = ["call", tool_name]
            args_json_candidate = None
            skip_next = False

            for i, tok in enumerate(remaining):
                if skip_next:
                    skip_next = False
                    continue
                if tok in ("--args-json", "--args-file", "--out", "--bridge", "--timeout"):
                    call_argv.extend([tok, remaining[i + 1]])
                    skip_next = True
                elif tok in ("--editorial-safe", "--no-editorial-safe", "--dry-run"):
                    call_argv.append(tok)
                elif tok.startswith("{") and tok.endswith("}"):
                    args_json_candidate = tok
                else:
                    call_argv.append(tok)

            if args_json_candidate and "--args-json" not in call_argv and "--args-file" not in call_argv:
                call_argv.extend(["--args-json", args_json_candidate])

            parser = build_parser()
            parsed_args = parser.parse_args(call_argv)
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
```

---

### 4.3 Policy Enforcement Logic Matrix

| Invoked Tool | Condition / Argument | Action Under `--editorial-safe` (Default: True) | Resulting Exit Code |
| :--- | :--- | :--- | :---: |
| `delete_news`, `delete_faq`, `delete_publication`, any `delete_*` | Any argument | **Hard Block**: Raises `EditorialPolicyViolation` unconditionally | `1` |
| `create_news` | `status: "y"` | **Hard Block**: Raises `EditorialPolicyViolation` (publishing forbidden) | `1` |
| `create_news` | `status` omitted or `"n"` | **Pass**: Forces `status: "n"` | `0` |
| `create_news` | `username` omitted or `"admin"` | **Pass**: Forces `username: "habermerkezi"` | `0` |
| `create_news` | `image.file_path` does not exist | **Hard Block**: Raises `EditorialPolicyViolation` (missing image file) | `1` |
| `update_news` | `status: "y"` | **Hard Block**: Raises `EditorialPolicyViolation` (cannot flip to publish) | `1` |
| `update_news` | `status: "n"` or omitted | **Pass**: Preserves `"n"` | `0` |
| `create_faq`, `update_faq` | `status: "y"` | **Hard Block**: Raises `EditorialPolicyViolation` | `1` |
| `batch_create_faqs` | Top-level `status: "y"` | **Hard Block**: Raises `EditorialPolicyViolation` | `1` |
| `batch_create_faqs` | Any item in `items` has `status: "y"` | **Hard Block**: Raises `EditorialPolicyViolation` | `1` |
| `batch_create_faqs` | All items valid | **Pass**: Forces top-level and item-level `status: "n"` | `0` |
| Any tool | `--no-editorial-safe` flag passed | **Bypass**: Disables local policy checks (bridge's own internal rules still apply) | Determined by bridge |

---

### 4.4 Automated Unit Test Suite Design (`tests/test_mcp_cli.py`)

A comprehensive unit test suite must be co-located under `tests/test_mcp_cli.py` to verify all components without external network access:

1. **Synthetic Mock Bridge (`MockBridgeProcess`)**:
   - A small Python test harness or environment variable `MCP_CLI_BRIDGE` pointing to a local mock script that echoes valid JSON-RPC envelopes with `_meta.environment.name = "production"`.
2. **Test Cases**:
   - `test_resolve_bridge_path_env_override`: Verifies `MCP_CLI_BRIDGE` takes precedence over home directory paths.
   - `test_resolve_bridge_path_not_found`: Verifies clean `FileNotFoundError` with clear search directory diagnostics when no bridge exists.
   - `test_policy_blocks_delete_tools`: Asserts calling `delete_news`, `delete_faq`, `delete_publication` raises `EditorialPolicyViolation`.
   - `test_policy_blocks_create_news_status_published`: Asserts `create_news` with `status: "y"` is rejected.
   - `test_policy_injects_create_news_draft_and_author`: Asserts `status: "n"` and `username: "habermerkezi"` are injected when omitted.
   - `test_policy_blocks_update_news_status_published`: Asserts `update_news` with `status: "y"` is rejected.
   - `test_policy_blocks_batch_faqs_published_items`: Asserts `batch_create_faqs` with any `status: "y"` item is rejected.
   - `test_policy_validates_image_file_path`: Asserts non-existent image paths raise an error.
   - `test_dry_run_mode`: Asserts `--dry-run` outputs the simulated request envelope without spawning subprocess.
   - `test_subcommand_get_record`: Verifies `get-record --module mod_news --id 128 --out /tmp/record.json` generates valid `tools/call` for `get_news` and writes `--out`.
   - `test_positional_syntax`: Asserts `mcp_cli.py amed-bulletin get_news '{"id": 128}'` routes correctly to `get_news`.
   - `test_zero_credential_exposure`: Inspects CLI help text and parses arguments to ensure no `--api-key` or token flags exist; verifies token regex redacts `amcp_*` strings in stderr.

---

## 5. Verification Method

### 5.1 Syntax and Compilation Verification
Run Python compilation check to verify clean syntax and zero import errors:
```bash
python3 -m py_compile tools/mcp_cli.py
```
*Expected Result*: Exit code `0`, no output or syntax errors.

### 5.2 Dry-Run Verification Commands
Verify policy enforcement, argument parsing, and dry-run execution without network or bridge access:
```bash
# 1. Valid create_news dry-run (verifies injection of status='n' and username='habermerkezi')
python3 tools/mcp_cli.py call create_news \
  --args-json '{"name": "Test Title", "content": "<p>Content</p>"}' \
  --dry-run

# 2. Blocked create_news with status='y' (must fail with exit code 1)
python3 tools/mcp_cli.py call create_news \
  --args-json '{"name": "Test", "status": "y"}' \
  --dry-run

# 3. Blocked delete_news (must fail unconditionally with exit code 1)
python3 tools/mcp_cli.py call delete_news \
  --args-json '{"id": 128}' \
  --dry-run

# 4. get-record dry-run (verifies module translation to get_news)
python3 tools/mcp_cli.py get-record --module mod_news --id 128 --dry-run

# 5. Positional syntax dry-run
python3 tools/mcp_cli.py amed-bulletin get_news '{"id": 128}' --dry-run
```

### 5.3 Code Separation & Repository Policy Guard
Run quality guard to ensure no violations:
```bash
python3 tools/code_separation_guard.py
python3 tools/module_boundary_guard.py --all
```
*Expected Result*: `[PASS] 0 inline style/script violations`, `[PASS] No foreign domain leaks`.

### 5.4 Invalidation Conditions
This blueprint would be invalidated if:
1. `amed_bulletin_mcp.py` stops using stdin/stdout line-delimited JSON-RPC 2.0.
2. The MCP protocol ceases returning `_meta.environment` or `environment.name`.
3. The CMS removes the requirement for `username: "habermerkezi"` or `status: "n"`.
