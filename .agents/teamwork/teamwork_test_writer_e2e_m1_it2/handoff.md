# Handoff Report: E2E Test Harness & Runner Hardening (Milestone E2E-M1, Iteration 2)

**Agent**: `teamwork_test_writer_e2e_m1_it2`  
**State Folder**: `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_test_writer_e2e_m1_it2/`  
**Working Directory**: `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin`  
**Date**: 2026-11-11T11:52:00Z  
**Type**: Hard (Task Complete)  

---

## 1. Observation

Direct code examination, empirical reproduction, and subsequent verification across `tests/e2e/run_e2e_tests.py`, `tests/e2e/harness/runner.py`, `tests/e2e/harness/mock_http.py`, `tests/e2e/harness/sandbox.py`, `tests/e2e/harness/assertions.py`, and `tests/e2e/test_harness_self_verification.py` established the following verified observations:

### 1.1 Runner CLI & Engine (`run_e2e_tests.py`, `runner.py`)
- **Malformed `--feature` Argument**:
  - Before fix, `python3 tests/e2e/run_e2e_tests.py --feature abc` exited with code `0` and executed all tests because `_parse_feature_filter` swallowed `ValueError` with `pass`, returning `set()`. In `filter_tests()`, `if target_features:` evaluated to `False` on an empty set, silently bypassing filtering.
  - After fix, `TestRunnerEngine._parse_feature_filter` raises `ValueError` on malformed expressions (`abc`, `1,,2`, `5-2`, `1-2-3`, `""`). CLI validator `_validate_feature_arg` intercepts this, triggering `CustomArgumentParser.error()`.
  - Verbatim command output:
    ```
    $ python3 tests/e2e/run_e2e_tests.py --feature abc
    Configuration/CLI Error: argument -f/--feature: Invalid feature ID 'abc': must be an integer or range
    Exit Code: 2
    ```
- **Zero Test Matches Formatting**:
  - Before fix, `python3 tests/e2e/run_e2e_tests.py --tier 2 --format json` returned 0 bytes to stdout because lines 153-158 early-returned before report instantiation.
  - After fix, when 0 tests match a filter, structured formats (`json`, `tap`, `junit`) and file output (`--output <path>`) instantiate and format an empty `TestRunSummary()`.
  - Verbatim output for `python3 tests/e2e/run_e2e_tests.py --tier 99 --format json`:
    ```json
    {
      "version": "1.0.63",
      "framework": "Amed Framework Bulletin E2E",
      "timestamp": "2026-11-11T11:47:54.503952+00:00",
      "summary": {
        "total": 0,
        "passed": 0,
        "failed": 0,
        "errors": 0,
        "skipped": 0,
        "duration_sec": 0,
        "is_success": true
      },
      "tests": []
    }
    ```
  - Exit code: `0`.
- **`ImportErrorCase` Closure Scope Leak**:
  - In `runner.py`, `except Exception as e:` cleans up `e` from enclosing scope upon block exit under PEP 3110. The original `lambda s: (_ for _ in ()).throw(e)` produced `NameError: cannot access free variable 'e'`.
  - Remediated by binding default argument at definition time: `lambda s, err=e: (_ for _ in ()).throw(err)`. Now re-raises the underlying `ModuleNotFoundError` or `SyntaxError`.
- **Discovery Module Ownership Check**:
  - `runner.py` previously loaded any `unittest.TestCase` in `dir(module)`. If `test_b.py` imported a base class from `test_a.py`, base class methods were duplicated.
  - Added module ownership verification: `if getattr(attr, "__module__", None) != module.__name__: continue`.
  - Prefixed module name with `f"e2e_test_tier{default_tier}_{os.path.splitext(os.path.basename(file_path))[0]}"` to prevent cross-tier collisions in `sys.modules`.

### 1.2 Mock HTTP Server Concurrency & Integrity (`mock_http.py`)
- **Re-entrant Deadlock**:
  - Single-threaded `HTTPServer` deadlocked whenever a callback initiated a secondary request to the same mock server.
  - Upgraded to `ThreadingHTTPServer` with `daemon_threads = True` (with `socketserver.ThreadingMixIn` fallback for older Python runtimes).
- **Header Immutability & None Handling**:
  - In `_dispatch()`, `headers` was previously retrieved by reference and mutated in place with `headers["Content-Type"] = ...`. Callers passing a persistent dictionary had their dictionary mutated.
  - Remediated by copying headers via `dict(raw_headers) if raw_headers else {}` both in `add_route()` and `_dispatch()`.
  - Callbacks returning `None` as the headers tuple item (`(200, None, "body")`) are converted safely to `{}` without raising `TypeError`.
- **REST Methods**:
  - Added `do_PUT`, `do_DELETE`, `do_PATCH`, and `do_OPTIONS` handler methods to `MockRouteHandler`.

### 1.3 Filesystem Sandbox Containment & Cleanup (`sandbox.py`)
- **Path Traversal Defense**:
  - `Sandbox.path()` previously used naive `os.path.join(self.root, *parts)`, allowing directory escapes via leading slashes (`/tmp/...`) or parent climbing (`../`).
  - Remediated by canonicalizing `root = os.path.realpath(self.root)`, resolving `target = os.path.realpath(os.path.join(root, *parts))`, and enforcing `os.path.commonpath([target, root]) == root`. Any escape raises `ValueError`.
  - `Sandbox.exists()` catches `ValueError` and returns `False`.
- **Read-Only Directory Cleanup (Zero Leaks)**:
  - Deleting files in POSIX requires write and execute permissions on the parent directory. Previously, `shutil.rmtree(self.root, ignore_errors=True)` silently failed on read-only subdirectories (`0o500`), leaking directories in `/tmp`.
  - Added `_ensure_writable_tree(top: str)` recursive permission restoration before deletion, accompanied by a parent-aware `onerror` handler `_handle_remove_readonly` ensuring `0o700` (`stat.S_IRWXU`) on both `path` and `os.path.dirname(path)`.

### 1.4 Domain Assertion Hardening (`assertions.py`)
- **Code Separation Rule 1**:
  - `assert_no_inline_styles_or_scripts` previously flagged any `<script>` tag. This caused false rejections of Feature 28 Google NewsArticle structured data (`<script type="application/ld+json">`) and external asset scripts (`<script src="...">`).
  - Remediated with quote-aware regex permitting `type="application/ld+json"` and `src="..."` (provided the external script has an empty body). Inline scripts and scripts with inline JavaScript bodies remain strictly rejected.
- **Quote-Aware HTML Tag & Strict Attribute Matching**:
  - `assert_html_tag_present` used `rf"<{tag}\b([^>]*)>"`. If an attribute contained `>` inside quotes (e.g. `alt="Comparison: A > B"`), regex match truncated prematurely.
  - Remediated with quote-aware pattern `rf"<{re.escape(tag)}\b((?:[^>\"']|\"[^\"]*\"|'[^']*')*?)\s*/?>"`.
  - Attribute key matching updated to boundary-safe `(?:^|\s){k}\s*=`.
  - Attribute value matching now enforces strict equality `actual_val == v` for all standard attributes, while preserving subset containment `expected_classes.issubset(actual_classes)` exclusively for `class`.
- **Timezone-Aware ISO Date Matching**:
  - `assert_date_is_today` regex ignored trailing timezone offsets (`Z`, `+03:00`, `+00:00`) and fractional seconds. Near midnight, UTC timestamps representing today in `Europe/Istanbul` falsely failed.
  - Remediated by parsing ISO timestamps with timezone offsets and converting tz-aware `datetime` objects to `Europe/Istanbul` prior to date equality evaluation.

---

## 2. Logic Chain

1. **CLI Validation & Defect Containment**:
   - Observation 1.1 proved that malformed filters silently ran all tests, masking user typos and pipeline misconfigurations.
   - Raising `ValueError` in `_parse_feature_filter` and raising `argparse.ArgumentTypeError` in `_validate_feature_arg` guarantees immediate fail-closed behavior with exit code `2`.
   - Modifying `run_e2e_tests.py` to format an empty `TestRunSummary()` ensures that CI parsers expecting JSON, JUnit XML, or TAP receive well-formed payloads instead of crashing on empty files or premature termination.
2. **Re-entrant Concurrency**:
   - Observation 1.2 proved that single-threaded HTTP execution caused deadlock on nested calls.
   - Python's `ThreadingHTTPServer` spawns a thread per connection, eliminating circular wait states while maintaining thread safety via existing `threading.Lock` primitives.
3. **Path Containment & Teardown**:
   - Observation 1.3 demonstrated that `Sandbox` allowed escaping to arbitrary paths, causing test artifacts to leak outside temp directories.
   - By asserting `os.path.commonpath([target, root]) == root`, operations outside `self.root` are aborted before filesystem access.
   - Restoring `stat.S_IRWXU` recursively guarantees that `shutil.rmtree` succeeds even on trees containing mode `0500` directories and mode `0400` files.
4. **Architectural Specification Alignment**:
   - Observation 1.4 showed that strict code separation tests were incompatible with mandatory Google NewsArticle JSON-LD requirements from Feature 28.
   - Distinguishing structured data metadata and external script tags from executable inline JavaScript preserves the strict code separation invariant of `AGENTS.md` Rule 1 without preventing JSON-LD rendering.

---

## 3. Caveats

- **Scope Adherence**: Only files within the authorized write ownership (`tests/e2e/run_e2e_tests.py`, `tests/e2e/harness/*`, `tests/e2e/test_harness_self_verification.py`) were modified. No production application files (`lib/`, `mod/`, `templates/`, `docker/`, `config/`) were touched.
- **Milestone Scope**: Tests in `tests/e2e/tier1/` currently consist of smoke infrastructure tests (`test_f00_infra_smoke.py`). The full suite of 36 Tier 1 tests will be introduced under Milestone E2E-M2.
- **Python Compatibility**: All fixes were developed and verified on Python 3.14 on Linux x86_64 and maintain backwards compatibility across Python 3.8+ through standard library primitives.

---

## 4. Conclusion

All defects reported across the runner CLI, test discovery engine, mock HTTP server, filesystem sandbox, and domain assertion utilities have been completely remediated.

1. **`tests/e2e/run_e2e_tests.py`**:
   - Rejects malformed `--feature` with exit code `2`.
   - Validates `--tier` and formats empty `TestRunSummary` for non-console formats (JSON, TAP, JUnit) and file outputs when 0 tests match.
2. **`tests/e2e/harness/runner.py`**:
   - Resolves `ImportErrorCase` closure bug via default parameter binding.
   - Enforces test discovery module ownership.
   - Prevents `sys.modules` collision across tiers.
   - Strictly validates feature filter strings and applies them unconditionally.
3. **`tests/e2e/harness/mock_http.py`**:
   - Eliminates re-entrant deadlocks with `ThreadingHTTPServer`.
   - Protects caller headers from mutation and handles `None` headers gracefully.
   - Supports `PUT`, `DELETE`, `PATCH`, and `OPTIONS`.
4. **`tests/e2e/harness/sandbox.py`**:
   - Prevents directory traversal via leading slashes and relative climbing.
   - Guarantees zero leak teardown of read-only directories and files.
5. **`tests/e2e/harness/assertions.py`**:
   - Permits JSON-LD and external script tags without inline bodies.
   - Parses tags with `>` inside attribute quotes.
   - Enforces exact attribute matching on non-class attributes.
   - Handles timezone offsets and converts to `Europe/Istanbul`.
6. **`tests/e2e/test_harness_self_verification.py`**:
   - Expanded from 21 tests to 38 tests, covering 100% of remediations. All 38 tests pass.

---

## 5. Verification Method

To independently reproduce and verify all fixes, run the following verification commands:

```bash
# 1. Run the expanded self-verification test suite (38 unit tests)
python3 -m unittest tests/e2e/test_harness_self_verification.py

# 2. Run existing Tier 1 tests offline using the unified test runner
python3 tests/e2e/run_e2e_tests.py --tier 1 --no-network

# 3. Verify malformed feature argument rejection (Exit code 2)
python3 tests/e2e/run_e2e_tests.py --feature abc
# Expected exit code: 2, Error message: Configuration/CLI Error: argument -f/--feature: Invalid feature ID 'abc'

# 4. Verify zero test match query formatting (Exit code 0 & valid JSON)
python3 tests/e2e/run_e2e_tests.py --tier 99 --format json
# Expected: valid empty JSON envelope with total=0, is_success=true

# 5. Verify code separation across codebase
python3 tools/code_separation_guard.py
# Expected: 0 violations found
```

### Invalidation Conditions:
- If `python3 tests/e2e/run_e2e_tests.py --feature abc` exits with code `0`.
- If `python3 tests/e2e/run_e2e_tests.py --tier 99 --format json` produces invalid JSON or non-zero exit code.
- If `Sandbox.path("/tmp/outside.txt")` or `Sandbox.write_file("../leak.txt", "data")` fails to raise `ValueError`.
- If a read-only subdirectory remains in `/tmp` following `Sandbox.cleanup()`.
- If `assert_no_inline_styles_or_scripts` rejects `<script type="application/ld+json">`.
- If `assert_html_tag_present` matches `id="superhero"` when searching for `attrs={"id": "hero"}`.
- If `test_harness_self_verification.py` has any test failures or errors.
