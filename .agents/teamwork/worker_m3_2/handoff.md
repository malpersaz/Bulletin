# Handoff Report: Milestone 3 Iteration 2 Remediation (`tools/mcp_cli.py`)

**Worker**: worker_m3_2  
**Roles**: implementer, qa  
**Date**: 2026-11-11T13:04:00Z  
**Target File**: `tools/mcp_cli.py`  
**Recipient**: Parent Orchestrator (`1cb4034b-124b-423c-b681-70f423565312`)  

---

## 1. Observation

### 1.1 Pre-Fix State and Failures
1. Positional syntax `python3 tools/mcp_cli.py amed-bulletin-mcp ping` previously routed to `handle_call(["call", "ping"])`, generating JSON-RPC `method: "tools/call", params: {"name": "ping"}` which failed with `isError: true` ("Unknown tool: ping") and exit code 1.
2. In `tests/test_m3_2_empirical_stress.py`, `test_positional_ping` failed with `AssertionError: 1 != 0 : Positional ping failed with code 1`.
3. In `enforce_editorial_policy`, `image.file_path` verification was only checked when `tool_name == "create_news"`, permitting invalid missing paths during `update_news`.

### 1.2 Modifications Applied to `tools/mcp_cli.py`
The blueprint from `.agents/teamwork/explorer_m3_4/handoff.md` and patch `.agents/teamwork/explorer_m3_4/tools_mcp_cli.patch` were applied to `tools/mcp_cli.py`:
- In `enforce_editorial_policy` (line 221):
  ```python
  # 6. Featured image file path validation on create_news and update_news
  if tool_name in ("create_news", "update_news"):
      img = args.get("image")
      if isinstance(img, dict) and "file_path" in img:
          file_path = str(img["file_path"]).strip()
          if file_path and not Path(file_path).is_file():
              raise EditorialPolicyViolation(
                  f"Featured image file specified in image.file_path does not exist: {file_path}"
              )
  ```
- In `parse_and_dispatch` (lines 633-696):
  Extracted top-level arguments (`--bridge`, `--timeout`) into `global_flags` so they precede subcommands when passed to `parser.parse_args`.
  Routed subcommands:
  - `ping` -> `handle_ping`
  - `list-tools`, `tools-list`, `list_tools` -> `handle_list_tools`
  - `get-record`, `get_record` -> `handle_get_record`
  - `call` -> extracts tool name and dispatches to `handle_call`
  - Generic tool names -> defaults to `call <tool_name>` and dispatches to `handle_call`.

---

## 2. Logic Chain

1. **Step 1**: The MCP bridge architecture provides JSON-RPC protocol methods (`ping`, `tools/list`) alongside tool executions (`tools/call`).
2. **Step 2**: CLI callers invoke both protocol subcommands and tool names using positional syntax (e.g., `amed-bulletin-mcp ping`, `amed-bulletin-mcp list-tools`, `amed-bulletin-mcp get_news`).
3. **Step 3**: By parsing the subcommand token after the server name in `parse_and_dispatch`, dedicated handlers (`handle_ping`, `handle_list_tools`, `handle_get_record`) are invoked directly with the properly placed global flags (`--bridge`, `--timeout`).
4. **Step 4**: By expanding `enforce_editorial_policy` to cover `update_news`, any payload supplying an `image.file_path` pointing to a non-existent file on disk is proactively rejected before reaching downstream execution, adhering to editorial policy invariants.
5. **Step 5**: Direct execution and unit test suites confirm all 24 empirical stress tests and 17 MCP CLI unit tests pass without errors.

---

## 3. Caveats

- **No Caveats**: The fix is minimal, directly targeted at `tools/mcp_cli.py`, introduces no breaking changes or dependencies, and passes all repository test suites and quality guards.

---

## 4. Conclusion

The Milestone 3 Iteration 2 defects in `tools/mcp_cli.py` are resolved:
1. Positional `ping`, `list-tools`, and `get-record` commands correctly route to dedicated handlers with global flags.
2. `update_news` validates `image.file_path` against disk existence identically to `create_news`.
3. All unit tests, empirical stress tests, image pipeline tests, and architecture guards pass cleanly.

---

## 5. Verification Method

To independently verify this remediation, run the following commands in the repository root:

```bash
# 1. Python compilation check
python3 -m py_compile tools/mcp_cli.py

# 2. CLI unit test suite (17 tests)
python3 -m unittest tests/test_mcp_cli.py

# 3. Empirical stress test suite (24 tests, including positional ping)
python3 -m unittest tests/test_m3_2_empirical_stress.py

# 4. Positional ping command test
python3 tools/mcp_cli.py amed-bulletin-mcp ping

# 5. Positional list-tools command test
python3 tools/mcp_cli.py amed-bulletin-mcp list-tools

# 6. update_news missing image rejection test
python3 tools/mcp_cli.py call update_news --args-json '{"id": 1, "image": {"file_path": "/missing.webp"}}' --dry-run

# 7. Code separation and module boundary guards
python3 tools/code_separation_guard.py
python3 tools/module_boundary_guard.py --all
```

All commands exit with code 0 (except #6 which exits with code 1 and an editorial policy rejection error as expected).
