# Explorer 4 Handoff Report: Milestone 3 Iteration 2 Fix Blueprint for `tools/mcp_cli.py`

**Date**: 2026-11-11T13:00:00Z  
**Author**: Explorer 4 (Read-Only Investigation & Blueprint Formulation)  
**Target File**: `tools/mcp_cli.py`  
**Test Suite**: `tests/test_m3_2_empirical_stress.py`  
**Artifact Patch**: `.agents/teamwork/explorer_m3_4/tools_mcp_cli.patch`

---

## 1. Observation

### 1.1 Direct Reproduction of Positional `ping` Failure
Executing the empirical test command:
```bash
python3 -m unittest tests/test_m3_2_empirical_stress.py -k test_positional_ping
```
Failed verbatim with exit code 1:
```
[test_positional_ping] returncode: 1
[test_positional_ping] stdout: {
  "jsonrpc": "2.0",
  "id": "cli_1794401635719",
  "result": {
    "_meta": {
      "environment": {
        "name": "production",
        "endpoint_host": "amedbulten.com",
        "site_status": "open"
      }
    },
    "content": [
      {
        "type": "text",
        "text": "Unknown tool: ping"
      }
    ],
    "isError": true
  }
}
FAIL: test_positional_ping (tests.test_m3_2_empirical_stress.TestMilestone3EmpiricalStress.test_positional_ping)
AssertionError: 1 != 0 : Positional ping failed with code 1: ...
```

Direct CLI invocation:
```bash
$ python3 tools/mcp_cli.py amed-bulletin-mcp ping
# Output: {"isError": true, "content": [{"text": "Unknown tool: ping"}]}
# Exit code: 1
```

### 1.2 Inspection of `parse_and_dispatch` in `tools/mcp_cli.py:618-658`
Lines 618-658 currently state:
```python
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
...
            parser = build_parser()
            parsed_args = parser.parse_args(call_argv)
            return handle_call(parsed_args)
```
When `argv` starts with `<server>`, `tool_name` is set to `argv[1]`. Regardless of whether `tool_name` is a top-level subcommand (`ping`, `list-tools`, `get-record`) or an actual MCP tool name, it is unconditionally prefixed with `"call"`:
`call_argv = ["call", tool_name]` and passed to `handle_call(parsed_args)`.
`handle_call` sends a JSON-RPC request with `method: "tools/call", params: {"name": tool_name}`. Because `ping` and `list-tools` are JSON-RPC protocol methods (`ping` and `tools/list`), the bridge reports `"Unknown tool: ping"` or `"Unknown tool: list-tools"`.
Similarly, `python3 tools/mcp_cli.py amed-bulletin-mcp get-record --module mod_news --id 100` fails with `argparse` exit code 2 because `call` does not recognize `--module` or `--id`.

### 1.3 `argparse` Positional Ordering Caveat
In `tools/mcp_cli.py`, `--bridge` and `--timeout` are registered as top-level arguments on `parser` before `subparsers`:
```python
parser.add_argument("--bridge", type=Path, help="Explicit path to amed_bulletin_mcp.py bridge script")
parser.add_argument("--timeout", type=int, default=45, help="Subprocess timeout in seconds (default: 45)")
```
If `--bridge` or `--timeout` appears *after* a subcommand token (e.g. `["ping", "--timeout", "10"]`), Python's `argparse` raises `unrecognized arguments: --timeout 10`.
Empirically tested:
```bash
python3 -c 'from tools.mcp_cli import build_parser; p = build_parser(); p.parse_args(["ping", "--timeout", "10"])'
# Exits code 2: error: unrecognized arguments: --timeout 10
python3 -c 'from tools.mcp_cli import build_parser; p = build_parser(); p.parse_args(["--timeout", "10", "ping"])'
# Exits code 0: Namespace(bridge=None, timeout=10, subcommand='ping')
```
Therefore, in positional mode, global options (`--bridge`, `--timeout`) in `remaining` must be extracted into `global_flags` and passed *before* the subcommand token.

### 1.4 Gap in `enforce_editorial_policy` for `update_news` Image Validation
In `tools/mcp_cli.py:221-228`:
```python
    # 6. Featured image file path validation on create_news
    if tool_name == "create_news":
        img = args.get("image")
        if isinstance(img, dict) and "file_path" in img:
            file_path = str(img["file_path"]).strip()
            if file_path and not Path(file_path).is_file():
                raise EditorialPolicyViolation(
                    f"Featured image file specified in image.file_path does not exist: {file_path}"
                )
```
`update_news` also accepts `image` dictionaries with `file_path`. Currently:
`python3 tools/mcp_cli.py call update_news --args-json '{"id": 1, "image": {"file_path": "/missing.webp"}}' --dry-run`
succeeds with exit code 0 because the file existence check was only applied to `create_news`.

---

## 2. Logic Chain

1. **Premise 1**: The MCP bridge distinguishes between JSON-RPC protocol methods (`ping`, `tools/list`) and MCP tool invocations (`tools/call` with `params.name = <tool>`).
2. **Premise 2**: In `tools/mcp_cli.py`, `handle_ping` handles `ping`, `handle_list_tools` handles `tools/list`, `handle_get_record` handles formatted record retrieval, and `handle_call` handles `tools/call`.
3. **Premise 3**: In positional syntax (`tools/mcp_cli.py <server> <command_or_tool> [args]`), `argv[1]` can be a subcommand (`ping`, `list-tools`, `get-record`) or a tool name (`get_news`, `create_news`, etc.).
4. **Premise 4**: If `argv[1]` is normalized to lowercase and matches `ping`, `list-tools` (or aliases `tools-list`, `list_tools`), or `get-record`, it must dispatch to its respective handler function.
5. **Premise 5**: Because `argparse` requires top-level arguments (`--bridge`, `--timeout`) to appear before the subcommand, any `--bridge` or `--timeout` flags in `remaining` must be extracted into a `global_flags` list and placed prior to the subcommand token.
6. **Premise 6**: For `enforce_editorial_policy`, expanding line 221 to `if tool_name in ("create_news", "update_news"):` ensures that any `image.file_path` provided to `update_news` is verified to exist on disk before staging.

---

## 3. Caveats

1. **Subcommand Aliases**: Positional syntax may encounter slight variations like `tools-list` or `list_tools`. Both are aliased to `list-tools` for resilience.
2. **Positional `call` Keyword**: If a caller explicitly specifies `tools/mcp_cli.py <server> call <tool_name> [args]`, the logic extracts `tool_name` from the remaining arguments so that it doesn't try to invoke an MCP tool named `"call"`.
3. **Read-Only Investigation Compliance**: Per the Explorer archetype mandate, no modifications have been made to repository source code (`tools/mcp_cli.py`). The blueprint has been verified empirically in-process and saved as a standalone patch in `.agents/teamwork/explorer_m3_4/tools_mcp_cli.patch`.

---

## 4. Conclusion & Exact Blueprint

The defect is fully understood, diagnosed, and resolved. Worker 4 can apply the following changes to `tools/mcp_cli.py`.

### 4.1 Exact Code Modifications

#### Modification 1: `enforce_editorial_policy` (Line 220-221)
**File**: `tools/mcp_cli.py`  
**Before**:
```python
    # 6. Featured image file path validation on create_news
    if tool_name == "create_news":
```
**After**:
```python
    # 6. Featured image file path validation on create_news and update_news
    if tool_name in ("create_news", "update_news"):
```

#### Modification 2: `parse_and_dispatch` (Lines 630-658)
**File**: `tools/mcp_cli.py`  
**Before**:
```python
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
```

**After**:
```python
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
```

### 4.2 Machine-Applicable Patch
The verified patch file is available at:
`.agents/teamwork/explorer_m3_4/tools_mcp_cli.patch`

Worker 4 can apply it directly via:
```bash
git apply .agents/teamwork/explorer_m3_4/tools_mcp_cli.patch
```
Verified via `git apply --check`: 0 errors.

---

## 5. Verification Method

Once Worker 4 applies the changes, verify using the following steps:

1. **Verify positional ping test passes**:
   ```bash
   python3 -m unittest tests/test_m3_2_empirical_stress.py -k test_positional_ping
   # Expected: Ran 1 test ... OK, returncode 0
   ```

2. **Verify direct positional CLI commands**:
   ```bash
   # Positional ping:
   python3 tools/mcp_cli.py amed-bulletin-mcp ping
   # Expected: {"jsonrpc": "2.0", "result": []}, Exit code: 0

   # Positional list-tools:
   python3 tools/mcp_cli.py amed-bulletin-mcp list-tools
   # Expected: JSON object with list of tools, Exit code: 0

   # Positional get-record:
   python3 tools/mcp_cli.py amed-bulletin-mcp get-record --module mod_news --id 100
   # Expected: Formatted record JSON, Exit code: 0
   ```

3. **Verify update_news missing image rejection**:
   ```bash
   python3 tools/mcp_cli.py call update_news --args-json '{"id": 1, "image": {"file_path": "/path/to/missing_update_image.webp"}}' --dry-run
   # Expected: Exit code 1, "Featured image file specified in image.file_path does not exist"
   ```

4. **Run the entire empirical stress suite**:
   ```bash
   python3 -m unittest tests/test_m3_2_empirical_stress.py
   # Expected: Ran 24 tests ... OK (0 failures, 0 errors)
   ```

5. **Run repository quality guards**:
   ```bash
   python3 tools/code_separation_guard.py
   python3 tools/i18n_guard.py
   python3 tools/module_boundary_guard.py --all
   python3 -m py_compile tools/mcp_cli.py
   # Expected: All guards report PASS / 0 violations.
   ```
