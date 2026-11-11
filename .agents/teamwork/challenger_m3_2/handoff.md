# Challenger 2 Handoff Report: Milestone 3 (CLI Wrapper & Database View Empirical Stress Tester)

**Verdict**: `REQUEST_CHANGES` (1 Bug Found in `tools/mcp_cli.py` Positional Syntax Dispatch)

---

## 1. Observation

### 1.1 Positional Syntax Bug in `tools/mcp_cli.py`
When executing the positional ping command explicitly specified in the dispatch instructions:
```bash
python3 tools/mcp_cli.py amed-bulletin-mcp ping
```
The command failed with exit code 1 and output verbatim:
```
[mcp_cli] Environment: production
{
  "jsonrpc": "2.0",
  "id": "cli_1794401169577",
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
```
**File and Line**: `tools/mcp_cli.py:624-657`:
```python
        if first_token in KNOWN_SERVER_NAMES or (len(argv) >= 2 and not argv[1].startswith("-")):
            server_name = argv[0]
            tool_name = argv[1] if len(argv) > 1 else ""
            remaining = argv[2:]

            if not tool_name:
                print(f"Error: Server '{server_name}' specified but tool name is missing.", file=sys.stderr)
                return 2

            # Translate positional syntax into `call <tool_name>` arguments
            call_argv = ["call", tool_name]
```
In `parse_and_dispatch`, when positional syntax `<server> <tool>` is used, any second argument is blindly mapped to `call_argv = ["call", tool_name]` and dispatched to `handle_call`.
Because `ping` is a top-level JSON-RPC protocol method rather than an MCP tool under `tools/call`, the MCP server rejects it with `"Unknown tool: ping"`.
The same failure occurs if a user invokes `python3 tools/mcp_cli.py amed-bulletin-mcp list-tools`:
```bash
$ python3 tools/mcp_cli.py amed-bulletin-mcp list-tools
# Exits with 1: "Unknown tool: list-tools"
```
Conversely, non-positional subcommands work cleanly:
```bash
$ python3 tools/mcp_cli.py ping
{"jsonrpc": "2.0", "id": "cli_...", "result": []}
# Exit code: 0
```

### 1.2 Editorial-Safe Policy Enforcement
Empirically tested via `tests/test_m3_2_empirical_stress.py`:
1. **Destructive Tool Blocking**:
   `delete_news`, `delete_faq`, `delete_publication`, `delete_attachment`, `delete_category`, `delete_headline`, and `delete_page` were all blocked with exit code 1 and error:
   `"Destructive tool '...' is prohibited under --editorial-safe policy."`.
2. **Draft Status Enforcement**:
   - `create_news` with `status="y"`, `status="Y"`, and `status="  y  "` were rejected with exit code 1:
     `"Publishing status 'y' is prohibited on 'create_news'. New records must be created with status 'n' (draft)."`.
   - `update_news`, `create_publication`, `create_faq`, and `batch_create_faqs` with `status="y"` were similarly rejected with exit code 1.
   - When status was omitted, `status="n"` was automatically injected.
3. **Author Enforcement**:
   - `create_news` with `username="admin"`, `username="Admin"`, `username="ADMIN"`, or empty was automatically rewritten to `username="habermerkezi"`.
   - Custom reporter usernames (e.g. `username="diyar_reporter"`) were preserved.
4. **Featured Image Local File Validation**:
   - `create_news` with non-existent `image.file_path` (`/path/to/missing.webp`) was rejected with exit code 1:
     `"Featured image file specified in image.file_path does not exist: /path/to/missing.webp"`.
   - Existing file paths passed validation with exit code 0.
   - *Observation/Gap*: In `tools/mcp_cli.py:221`, the file existence check is guarded by `if tool_name == "create_news":`. It does not check `update_news`, which also accepts `image.file_path`.

### 1.3 Secret-Opaque Token Redaction
Tested via mock bridge in `test_token_scrubbing_in_stderr`:
Stderr containing `amcp_SECRET999abc`, `amcp_SESS123`, and `amcp_ANOTHERKEY` was scrubbed to `[REDACTED_API_KEY]`.
Zero raw `amcp_*` tokens appeared in stderr or stdout.

### 1.4 Database View Self-Healing in Docker
Tested in `bulletin_app` container:
1. Replaced `newses` view with an outdated version lacking `image_alt` and `image_credit`.
2. Instantiated `newses_model` via `Load::model("mod_news.newses"); new newses_model();`.
3. `ensureViewExists()` detected missing columns, dropped the stale view, and recreated it. Both `image_alt` and `image_credit` column checks returned count 1 (`EXISTS`).
4. Dropped column `image_credit` from table `mod_news` (`ALTER TABLE mod_news DROP COLUMN image_credit`).
5. Instantiated `newses_model`: `ensureTableColumns()` executed `ALTER TABLE mod_news ADD COLUMN image_credit ... AFTER image_alt` and rebuilt the view. All 4 checks in DB returned `EXISTS`.

### 1.5 Quality Guards & Syntax
- `python3 tools/code_separation_guard.py`: 0 violations across 25 files.
- `python3 tools/i18n_guard.py`: 0 violations across 32 files.
- `python3 tools/module_boundary_guard.py --all`: 0 violations across 10,126 files.
- `php -l`: All touched PHP files passed syntax validation.
- `python3 -m py_compile`: All Python files compiled without errors.

---

## 2. Logic Chain

1. **Step 1**: The dispatch instructions explicitly requested:
   `Test positional syntax: tools/mcp_cli.py amed-bulletin-mcp ping.`
2. **Step 2**: Running `python3 tools/mcp_cli.py amed-bulletin-mcp ping` executes lines 624-657 of `tools/mcp_cli.py`, which unconditionally builds `call_argv = ["call", tool_name]` and dispatches to `handle_call`.
3. **Step 3**: `handle_call` formats a JSON-RPC request with `method: "tools/call", params: {"name": "ping", "arguments": {}}`.
4. **Step 4**: The server receives `tools/call` for tool `"ping"`. Because "ping" is a protocol-level JSON-RPC method, not an MCP tool, the server returns `"Unknown tool: ping"` with `isError: true` and exit code 1.
5. **Step 5**: Therefore, positional syntax fails when invoking subcommands like `ping` or `list-tools`.
6. **Step 6**: The fix is simple and deterministic: in `parse_and_dispatch`, if `tool_name` matches known subcommands (`ping`, `list-tools`), dispatch directly to `handle_ping` or `handle_list_tools` instead of converting to `call <tool>`.

---

## 3. Caveats

- Live tool calls against production bridge (`https://amedbulten.com/mcp.php`) were executed in read-only mode (`get_news` id=100, `ping`, `list-tools`) and dry-run mode (`--dry-run`). Live mutation calls were not executed against production to prevent corrupting remote editorial state.
- Standalone test harness `tests/test_m3_2_empirical_stress.py` was written directly in `tests/` conforming to repository layout rules.

---

## 4. Conclusion

**Verdict**: `REQUEST_CHANGES`.

The database view migration (`newses.model.php`), editorial-safe policy engine (`enforce_editorial_policy`), and stderr token scrubbing are implemented robustly and verified empirically.
However, `tools/mcp_cli.py` has a defect in positional syntax dispatch:
Invoking `python3 tools/mcp_cli.py amed-bulletin-mcp ping` fails with exit code 1 (`Unknown tool: ping`).

### Required Action for Worker:
In `tools/mcp_cli.py` (`parse_and_dispatch`):
Handle subcommands in positional mode:
```python
            if tool_name == "ping":
                # Translate to ping subcommand
                call_argv = ["ping"]
                for i, tok in enumerate(remaining):
                    if tok in ("--bridge", "--timeout"):
                        call_argv.extend([tok, remaining[i + 1]])
                parsed_args = parser.parse_args(call_argv)
                return handle_ping(parsed_args)
            elif tool_name in ("list-tools", "tools-list"):
                call_argv = ["list-tools"]
                for i, tok in enumerate(remaining):
                    if tok in ("--bridge", "--timeout"):
                        call_argv.extend([tok, remaining[i + 1]])
                parsed_args = parser.parse_args(call_argv)
                return handle_list_tools(parsed_args)
```
Additionally, consider extending the image file path existence check in `enforce_editorial_policy` to cover `update_news` as well as `create_news`.

---

## 5. Verification Method

To independently verify this finding:
```bash
# 1. Run the empirical stress test reproducing the positional ping failure:
python3 -m unittest tests/test_m3_2_empirical_stress.py -k test_positional_ping

# Expected output before fix:
# AssertionError: 1 != 0 : Positional ping failed with code 1: Unknown tool: ping

# 2. Run the direct CLI command:
python3 tools/mcp_cli.py amed-bulletin-mcp ping
# Expected output before fix: Exits with 1, {"content": [{"type": "text", "text": "Unknown tool: ping"}], "isError": true}

# 3. Verify that subcommand syntax works:
python3 tools/mcp_cli.py ping
# Output: {"jsonrpc": "2.0", "result": []}, Exit code: 0
```
