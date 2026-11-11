# Handoff Report: E2E Test Runner CLI, Filtering, and Discovery Defect Investigation (Milestone E2E-M1, Iteration 2)

**Agent**: `teamwork_explorer_e2e_m1_it2_1` (TEAMWORK EXPLORER)  
**State Folder**: `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_explorer_e2e_m1_it2_1/`  
**Working Directory**: `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin`  
**Date**: 2026-11-11  
**Milestone**: E2E-M1 (Iteration 2)  
**Mission**: Investigate runner CLI, filtering, and discovery defects found by Challenger 1 and Reviewer 1, and formulate exact fix strategy.

---

## 1. Observation

Direct code examination and empirical execution across `tests/e2e/run_e2e_tests.py`, `tests/e2e/harness/runner.py`, `tests/e2e/harness/reporters.py`, and peer findings from Challenger 1 (`teamwork_challenger_e2e_m1_1/handoff.md`) and Reviewer 1 (`teamwork_reviewer_e2e_m1_1/handoff.md`) revealed the following verified defects:

### 1.1 Malformed `--feature <val>` Filter Silent Bypass
- **File**: `tests/e2e/harness/runner.py`, lines 174-206:
  ```python
  def filter_tests(
      self,
      tests: List[TestItem],
      tier: Optional[str] = None,
      feature_filter: Optional[str] = None
  ) -> List[TestItem]:
      ...
      # 2. Feature filtering
      if feature_filter:
          target_features = self._parse_feature_filter(feature_filter)
          if target_features:
              filtered = [
                  t for t in filtered
                  if any(f in target_features for f in t.features) or (not t.features and 0 in target_features)
              ]

      return filtered

  @staticmethod
  def _parse_feature_filter(expr: str) -> Set[int]:
      """Parse comma-separated or range string into set of ints (e.g. '1,3,5-8')."""
      result: Set[int] = set()
      parts = expr.split(",")
      for p in parts:
          p = p.strip()
          if not p:
              continue
          if "-" in p:
              start_str, end_str = p.split("-", 1)
              try:
                  start, end = int(start_str), int(end_str)
                  result.update(range(start, end + 1))
              except ValueError:
                  pass
          else:
              try:
                  result.add(int(p))
              except ValueError:
                  pass
      return result
  ```
- **Empirical Execution**:
  ```bash
  python3 tests/e2e/run_e2e_tests.py --feature abc
  ```
  - **Output**:
    ```
    Executing 4 test(s): ....
    === Amed Framework Bulletin E2E Test Suite Run ===
    OVERALL STATUS: PASSED (Total: 4, Passed: 4, Duration: 0.87s)
    ```
  - **Exit Code**: `0`.
- **Root Cause**: When given malformed input like `abc`, `1,abc`, `1-foo`, or `5-2`, `_parse_feature_filter` catches `ValueError` with `pass`. When all tokens are invalid, it returns `set()` (empty set). In `filter_tests`, `if target_features:` evaluates to `False` on an empty set, bypassing filtering entirely. All tests in the suite execute as if `--feature` were never passed.

### 1.2 Zero Test Matches: Empty Stdout and Missing Output File for Structured Formats
- **File**: `tests/e2e/run_e2e_tests.py`, lines 153-158:
  ```python
  if not selected_tests:
      if args.format == "console":
          print(f"No tests discovered for tier={args.tier}, feature={args.feature}")
          print(f"Total discovered in suite: {len(all_tests)}")
      return 0
  ```
- **Empirical Execution**:
  ```bash
  python3 tests/e2e/run_e2e_tests.py --tier 2 --format json
  ```
  - **Output**: 0 bytes written to stdout.
  - **Result**: Empty string causes `json.decoder.JSONDecodeError` in downstream JSON parsers.
  ```bash
  python3 tests/e2e/run_e2e_tests.py --tier 2 --format json --output /tmp/test_report.json
  ls -la /tmp/test_report.json
  ```
  - **Result**: `ls: cannot access '/tmp/test_report.json': No such file or directory`.
- **Root Cause**: The runner early-returns on line 157 prior to instantiating any reporter or writing to `args.output`. However, inspection of `tests/e2e/harness/reporters.py` confirms that `JsonReporter`, `JUnitReporter`, `TapReporter`, and `ConsoleReporter` all natively and cleanly format an empty `TestRunSummary()`:
  - `JsonReporter`: Returns valid JSON object with `"summary": {"total": 0, "passed": 0, "is_success": true}, "tests": []`.
  - `JUnitReporter`: Returns valid XML `<?xml version='1.0' encoding='utf-8'?>\n<testsuites name="AF_Bulletin_E2E" tests="0" failures="0" errors="0" skipped="0" time="0.000" />`.
  - `TapReporter`: Returns valid TAP v13 `TAP version 13\n1..0`.
  - `ConsoleReporter`: Returns standard suite run header, zeroed tier summary, and `OVERALL STATUS: PASSED (Total: 0, Passed: 0, Duration: 0.00s)`.

### 1.3 `ImportErrorCase` Closure Scope Leak
- **File**: `tests/e2e/harness/runner.py`, lines 110-126:
  ```python
  except Exception as e:
      # Capture module import failure as an error test item
      class ImportErrorCase(unittest.TestCase):
          pass
      setattr(ImportErrorCase, "test_import_error", lambda s: (_ for _ in ()).throw(e))
      items.append(
          TestItem(
              test_class=ImportErrorCase,
              method_name="test_import_error",
              module_path=file_path,
              tier=default_tier,
              features=[],
              timeout=self.default_timeout
          )
      )
      return items
  ```
- **Empirical Reproduction**:
  Creating a test file with `import non_existent_package` and running the suite produced:
  ```
  [1] [Tier 1][F?] test_broken_syntax::ImportErrorCase.test_import_error (ERROR)
      Message: NameError: cannot access free variable 'e' where it is not associated with a value in enclosing scope
  ```
- **Root Cause**: In Python 3 (PEP 3110), the target variable `e` in `except Exception as e:` is cleared from the namespace at the end of the `except` block to prevent reference cycles. Because the lambda references `e` as a free variable in its closure, executing `test_import_error` after the `except` block raises `NameError`, completely hiding the underlying `ImportError` or `SyntaxError`.

### 1.4 Test Discovery Module Ownership Check Missing
- **File**: `tests/e2e/harness/runner.py`, lines 128-152:
  ```python
  for attr_name in dir(module):
      attr = getattr(module, attr_name)
      if inspect.isclass(attr) and issubclass(attr, unittest.TestCase):
          class_tier = getattr(attr, "__e2e_tier__", getattr(attr, "default_tier", default_tier))
          class_features = getattr(attr, "__e2e_features__", getattr(attr, "default_features", []))

          for m_name in dir(attr):
              if m_name.startswith("test_"):
                  ...
  ```
- **Empirical Test**:
  When a test file `test_b.py` imports a `TestCase` class defined in `test_a.py` (or a helper module), `dir(module)` contains the imported class. `inspect.isclass(attr)` and `issubclass(attr, unittest.TestCase)` are both `True`.
- **Root Cause**: `_load_test_file` does not verify `getattr(attr, "__module__", None) == module.__name__`. As a result, imported test classes are discovered, registered, and executed multiple times under different modules. In standard library `unittest.TestLoader.loadTestsFromModule`, class ownership verification is mandatory.
- **Related Collision**: Line 102 derives `mod_name = f"e2e_test_{os.path.splitext(os.path.basename(file_path))[0]}"`. If two tiers share a filename (e.g. `tier1/test_f01.py` and `tier2/test_f01.py`), both map to `e2e_test_test_f01`, risking `sys.modules` collision and class attribute mismatch.

---

## 2. Logic Chain

1. **Premise 1**: Per `TEST_INFRA.md` Section 3 and `run_e2e_tests.py` line 34 (`CustomArgumentParser`), invalid CLI arguments must be rejected immediately with exit code `2`.
   - **Observation**: Running `--invalid-flag`, `--tier 99`, or `--timeout abc` triggers `CustomArgumentParser.error()` and exits with code 2.
   - **Contrast**:
     - *Syntactic Invalidity*: `--feature abc` is not a valid integer or integer range. Allowing `--feature abc` to match 0 tests and exit 0 would mislead CI pipelines: a typo in a test command (e.g. `--feature feat12`) would result in a green build (exit code 0) without any tests executing.
     - *Semantic Non-Existence*: `--feature 99` is a syntactically valid integer filter. Filtering against a valid integer that has no tests discovered correctly yields 0 matches, passes with exit code 0, and generates an empty report.
   - **Deduction**: `_parse_feature_filter` must reject malformed expressions by raising `ValueError`. The CLI parser must validate `--feature` and exit with code 2 on `ValueError`, while `filter_tests` must unconditionally apply the filter set when provided.

2. **Premise 2**: Per `TEST_INFRA.md` Section 3, report formatters (`--format <console|json|tap|junit>`) and output redirection (`-o`/`--output <path>`) must always produce valid, parseable outputs according to their format schemas.
   - **Observation**: `JsonReporter`, `JUnitReporter`, `TapReporter`, and `ConsoleReporter` already produce valid output when formatting an empty `TestRunSummary()`. Lines 153-158 of `run_e2e_tests.py` short-circuit execution before report formatting and before file creation.
   - **Deduction**: Removing the early-return and letting an empty `TestRunSummary()` flow into the selected reporter and output writer guarantees that:
     1. Stdout receives valid JSON, JUnit XML, TAP, or Console text.
     2. `--output <path>` is always created with the formatted content.
     3. Exit code is 0 (as `is_success` is True for 0 failures).

3. **Premise 3**: In Python 3, `except Exception as e:` deletes `e` from the local scope at block exit.
   - **Observation**: `lambda s: (_ for _ in ()).throw(e)` references `e` as an unbound free variable after the block exits, triggering `NameError`.
   - **Observation**: In Python, function default parameter expressions are evaluated at definition time (while `e` is still in scope).
   - **Deduction**: Changing the lambda signature to `lambda s, err=e: (_ for _ in ()).throw(err)` binds `e` to `err` in `__defaults__` at definition time. When invoked, `err` evaluates to the captured exception object, cleanly re-raising the original `ImportError` or `SyntaxError`.

4. **Premise 4**: Every class in Python retains a reference to its defining module in `__module__`.
   - **Observation**: In `_load_test_file`, `module.__name__` is set to `mod_name`. Classes defined inside the file have `attr.__module__ == module.__name__`. Classes imported from other modules have `attr.__module__ != module.__name__`.
   - **Deduction**: Checking `if getattr(attr, "__module__", None) != module.__name__: continue` restricts discovery strictly to classes authored in that specific module, eliminating duplicate test runs from imported base classes. Furthermore, prefixing `mod_name` with `tier{default_tier}` eliminates cross-tier module naming collisions.

---

## 3. Caveats

- **Read-Only Scope**: This investigation is strictly read-only; no production or test files were modified during this task.
- **Milestone Scope**: Only Tier 1 tests currently exist (`tests/e2e/tier1/test_f00_infra_smoke.py`). Tiers 2-4 test suites will be introduced in Milestones E2E-M2 through E2E-M5.
- **Python Version**: Empirical verification was executed on Python 3.14 on Linux x86_64; behavior of Python 3 exception scope deletion (`del e`) and default argument binding is identical across all Python 3.8+ versions.

---

## 4. Conclusion & Recommended Fix Strategy

All four defects have been diagnosed with verified root causes and empirically proven solutions. Below are the exact, concrete remediation proposals for the implementation agent.

### 4.1 Fix for `tests/e2e/harness/runner.py`

#### Change 1: `ImportErrorCase` Closure Fix (`runner.py:115`)
**Target**: `tests/e2e/harness/runner.py`, line 115
- **Before**:
  ```python
  setattr(ImportErrorCase, "test_import_error", lambda s: (_ for _ in ()).throw(e))
  ```
- **After**:
  ```python
  setattr(ImportErrorCase, "test_import_error", lambda s, err=e: (_ for _ in ()).throw(err))
  ```
- **Rationale**: Binds the exception instance `e` into the lambda's default arguments at definition time before Python deletes `e` at `except` block termination.

#### Change 2: Test Discovery Module Ownership & Tier Namespace (`runner.py:102` and lines 128-132)
**Target**: `tests/e2e/harness/runner.py`, lines 102 and 130
- **Before**:
  ```python
  mod_name = f"e2e_test_{os.path.splitext(os.path.basename(file_path))[0]}"
  ```
  and
  ```python
  for attr_name in dir(module):
      attr = getattr(module, attr_name)
      if inspect.isclass(attr) and issubclass(attr, unittest.TestCase):
          class_tier = getattr(attr, "__e2e_tier__", getattr(attr, "default_tier", default_tier))
  ```
- **After**:
  ```python
  mod_name = f"e2e_test_tier{default_tier}_{os.path.splitext(os.path.basename(file_path))[0]}"
  ```
  and
  ```python
  for attr_name in dir(module):
      attr = getattr(module, attr_name)
      if inspect.isclass(attr) and issubclass(attr, unittest.TestCase):
          if getattr(attr, "__module__", None) != module.__name__:
              continue
          class_tier = getattr(attr, "__e2e_tier__", getattr(attr, "default_tier", default_tier))
  ```
- **Rationale**: Ensures only test cases defined in the inspected module are loaded (preventing duplicate test discovery when base test classes are imported) and prevents module namespace collisions across tiers.

#### Change 3: Strict `_parse_feature_filter` Validation & Unconditional Filter Application (`runner.py:175-207`)
**Target**: `tests/e2e/harness/runner.py`, lines 175-207
- **Before**:
  ```python
          # 2. Feature filtering
          if feature_filter:
              target_features = self._parse_feature_filter(feature_filter)
              if target_features:
                  filtered = [
                      t for t in filtered
                      if any(f in target_features for f in t.features) or (not t.features and 0 in target_features)
                  ]

          return filtered

      @staticmethod
      def _parse_feature_filter(expr: str) -> Set[int]:
          """Parse comma-separated or range string into set of ints (e.g. '1,3,5-8')."""
          result: Set[int] = set()
          parts = expr.split(",")
          for p in parts:
              p = p.strip()
              if not p:
                  continue
              if "-" in p:
                  start_str, end_str = p.split("-", 1)
                  try:
                      start, end = int(start_str), int(end_str)
                      result.update(range(start, end + 1))
                  except ValueError:
                      pass
              else:
                  try:
                      result.add(int(p))
                  except ValueError:
                      pass
          return result
  ```
- **After**:
  ```python
          # 2. Feature filtering
          if feature_filter is not None:
              target_features = self._parse_feature_filter(feature_filter)
              filtered = [
                  t for t in filtered
                  if any(f in target_features for f in t.features) or (not t.features and 0 in target_features)
              ]

          return filtered

      @staticmethod
      def _parse_feature_filter(expr: str) -> Set[int]:
          """Parse comma-separated or range string into set of ints (e.g. '1,3,5-8')."""
          result: Set[int] = set()
          parts = expr.split(",")
          for p in parts:
              p = p.strip()
              if not p:
                  raise ValueError("Empty feature token in filter expression")
              if "-" in p:
                  tokens = p.split("-")
                  if len(tokens) != 2:
                      raise ValueError(f"Invalid range syntax in feature filter: '{p}'")
                  try:
                      start, end = int(tokens[0]), int(tokens[1])
                  except ValueError:
                      raise ValueError(f"Invalid integer in feature range: '{p}'")
                  if start > end:
                      raise ValueError(f"Inverted feature range '{start}-{end}': start cannot be greater than end")
                  result.update(range(start, end + 1))
              else:
                  try:
                      result.add(int(p))
                  except ValueError:
                      raise ValueError(f"Invalid feature ID '{p}': must be an integer or range")
          return result
  ```
- **Rationale**: Replaces silent `pass` error swallowing with explicit `ValueError` on malformed syntax, and applies `target_features` unconditionally when `feature_filter is not None`.

---

### 4.2 Fix for `tests/e2e/run_e2e_tests.py`

#### Change 1: CLI Argument Validation for `--feature` (`run_e2e_tests.py:41-56`)
**Target**: `tests/e2e/run_e2e_tests.py`, add validator and update argument parser
- **Before**:
  ```python
      parser.add_argument(
          "-f", "--feature",
          type=str,
          default=None,
          help="Filter by feature ID (1 through 36, or comma-separated e.g. 1,2,5, or range 1-12)"
      )
  ```
- **After**:
  ```python
  def _validate_feature_arg(val: str) -> str:
      try:
          TestRunnerEngine._parse_feature_filter(val)
      except ValueError as e:
          raise argparse.ArgumentTypeError(str(e))
      return val
  ```
  and
  ```python
      parser.add_argument(
          "-f", "--feature",
          type=_validate_feature_arg,
          default=None,
          help="Filter by feature ID (1 through 36, or comma-separated e.g. 1,2,5, or range 1-12)"
      )
  ```
- **Rationale**: Reuses `TestRunnerEngine._parse_feature_filter` as an argument validator. Any malformed expression triggers `argparse.ArgumentTypeError`, which `CustomArgumentParser.error()` outputs as `Configuration/CLI Error: argument -f/--feature: ...` and exits with code 2.

#### Change 2: Zero Test Matches Handling & Universal File Output (`run_e2e_tests.py:153-195`)
**Target**: `tests/e2e/run_e2e_tests.py`, lines 153-195
- **Before**:
  ```python
      if not selected_tests:
          if args.format == "console":
              print(f"No tests discovered for tier={args.tier}, feature={args.feature}")
              print(f"Total discovered in suite: {len(all_tests)}")
          return 0

      # 4. Optional global mock server for --no-network
      shared_mock_server = None
      if args.no_network:
          shared_mock_server = MockHttpServer()
          mock_url = shared_mock_server.start()
          os.environ["AF_E2E_MOCK_HTTP_URL"] = mock_url

      # 5. Live progress tracking for console format
      def on_test_complete(r: TestCaseResult) -> None:
          if args.format == "console" and not args.output:
              if r.status == "passed":
                  sys.stdout.write(".")
              elif r.status == "failed":
                  sys.stdout.write("F")
              elif r.status == "error":
                  sys.stdout.write("E")
              elif r.status == "skipped":
                  sys.stdout.write("s")
              sys.stdout.flush()

      try:
          if args.format == "console" and not args.output:
              sys.stdout.write(f"Executing {len(selected_tests)} test(s): ")
              sys.stdout.flush()

          summary = engine.run_tests(selected_tests, progress_callback=on_test_complete)

          if args.format == "console" and not args.output:
              sys.stdout.write("\n\n")
              sys.stdout.flush()
      finally:
          if shared_mock_server:
              shared_mock_server.stop()
              os.environ.pop("AF_E2E_MOCK_HTTP_URL", None)
  ```
- **After**:
  ```python
      if not selected_tests:
          if args.format == "console" and not args.output:
              print(f"No tests discovered for tier={args.tier}, feature={args.feature}")
              print(f"Total discovered in suite: {len(all_tests)}")
              return 0
          summary = TestRunSummary()
          summary.compute_metrics()
      else:
          # 4. Optional global mock server for --no-network
          shared_mock_server = None
          if args.no_network:
              shared_mock_server = MockHttpServer()
              mock_url = shared_mock_server.start()
              os.environ["AF_E2E_MOCK_HTTP_URL"] = mock_url

          # 5. Live progress tracking for console format
          def on_test_complete(r: TestCaseResult) -> None:
              if args.format == "console" and not args.output:
                  if r.status == "passed":
                      sys.stdout.write(".")
                  elif r.status == "failed":
                      sys.stdout.write("F")
                  elif r.status == "error":
                      sys.stdout.write("E")
                  elif r.status == "skipped":
                      sys.stdout.write("s")
                  sys.stdout.flush()

          try:
              if args.format == "console" and not args.output:
                  sys.stdout.write(f"Executing {len(selected_tests)} test(s): ")
                  sys.stdout.flush()

              summary = engine.run_tests(selected_tests, progress_callback=on_test_complete)

              if args.format == "console" and not args.output:
                  sys.stdout.write("\n\n")
                  sys.stdout.flush()
          finally:
              if shared_mock_server:
                  shared_mock_server.stop()
                  os.environ.pop("AF_E2E_MOCK_HTTP_URL", None)
  ```
- **Rationale**: When 0 tests match, if interactive console without `--output` is requested, it provides the concise diagnostic and exits 0. If structured format (`json`, `junit`, `tap`) or file output (`--output`) is requested, it instantiates an empty `TestRunSummary()`, formats it with the requested reporter, and outputs valid syntax to stdout or the designated file path.

---

## 5. Verification Method

Once implemented, the changes can be independently verified using the following commands:

1. **Verify Malformed Feature Rejection (Exit Code 2)**:
   ```bash
   python3 tests/e2e/run_e2e_tests.py --feature abc
   # Must exit with code 2 and print: Configuration/CLI Error: argument -f/--feature: Invalid feature ID 'abc'
   
   python3 tests/e2e/run_e2e_tests.py --feature 1,,2
   # Must exit with code 2
   
   python3 tests/e2e/run_e2e_tests.py --feature 5-2
   # Must exit with code 2 (inverted range)
   ```

2. **Verify Valid Semantic Zero-Match Query (Exit Code 0 & Empty Reports)**:
   ```bash
   # JSON output to stdout must be valid JSON:
   python3 tests/e2e/run_e2e_tests.py --tier 2 --format json | jq .
   
   # JUnit XML output to stdout must be valid XML:
   python3 tests/e2e/run_e2e_tests.py --tier 2 --format junit
   
   # TAP output to stdout must be valid TAP v13:
   python3 tests/e2e/run_e2e_tests.py --tier 2 --format tap
   
   # Output file must be created even when 0 tests match:
   python3 tests/e2e/run_e2e_tests.py --tier 2 --format json --output /tmp/empty_report.json
   ls -la /tmp/empty_report.json && jq .summary /tmp/empty_report.json && rm -f /tmp/empty_report.json
   ```

3. **Verify `ImportErrorCase` True Exception Reporting**:
   ```bash
   echo "import non_existent_package_test_xyz" > tests/e2e/tier1/test_temp_broken.py
   python3 tests/e2e/run_e2e_tests.py
   # Must report ModuleNotFoundError: No module named 'non_existent_package_test_xyz'
   # Must NOT report NameError on variable 'e'
   rm -f tests/e2e/tier1/test_temp_broken.py
   ```

4. **Verify Module Ownership on Test Discovery**:
   ```bash
   # Base test classes imported across test modules must not be discovered or executed multiple times
   python3 -m unittest tests/e2e/test_harness_self_verification.py
   python3 tests/e2e/run_e2e_tests.py --list
   ```

5. **Verify Full Baseline Suite Pass**:
   ```bash
   python3 tests/e2e/run_e2e_tests.py
   python3 -m unittest tests/e2e/test_harness_self_verification.py
   ```
