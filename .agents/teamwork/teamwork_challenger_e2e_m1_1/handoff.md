# Handoff Report: Adversarial Challenge & Stress-Testing of E2E Test Runner (Milestone E2E-M1)

**Agent**: `teamwork_challenger_e2e_m1_1` (EMPIRICAL CHALLENGER)  
**State Folder**: `/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_challenger_e2e_m1_1/`  
**Verdict**: **`REQUEST_CHANGES`**

---

## 1. Observation

### Test 1: CLI Argument Edge Cases & Filtering
- **Command 1**: `python3 tests/e2e/run_e2e_tests.py --invalid-flag`
  - **Result**: Exit code `2`.
  - **Output**:
    ```
    Configuration/CLI Error: unrecognized arguments: --invalid-flag
    ```
- **Command 2**: `python3 tests/e2e/run_e2e_tests.py --tier 99`
  - **Result**: Exit code `2`.
  - **Output**:
    ```
    Configuration/CLI Error: argument --tier: invalid choice: '99' (choose from 1, 2, 3, 4, all)
    ```
- **Command 3**: `python3 tests/e2e/run_e2e_tests.py --timeout abc`
  - **Result**: Exit code `2`.
  - **Output**:
    ```
    Configuration/CLI Error: argument --timeout: invalid float value: 'abc'
    ```
- **Command 4**: `python3 tests/e2e/run_e2e_tests.py --feature 99`
  - **Result**: Exit code `0`.
  - **Output**:
    ```
    No tests discovered for tier=all, feature=99
    Total discovered in suite: 4
    ```
- **Command 5**: `python3 tests/e2e/run_e2e_tests.py --feature abc`
  - **Result**: Exit code `0`.
  - **Output**:
    ```
    Executing 4 test(s): ....
    === Amed Framework Bulletin E2E Test Suite Run ===
    OVERALL STATUS: PASSED (Total: 4, Passed: 4, Duration: 0.87s)
    ```
  - **Code Inspection** (`tests/e2e/harness/runner.py`, lines 174-206):
    ```python
    def filter_tests(self, tests: List[TestItem], tier: Optional[str] = None, feature_filter: Optional[str] = None) -> List[TestItem]:
        ...
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
        result: Set[int] = set()
        parts = expr.split(",")
        for p in parts:
            ...
            try:
                result.add(int(p))
            except ValueError:
                pass
        return result
    ```
    When `feature_filter="abc"`, `_parse_feature_filter("abc")` encounters a `ValueError`, swallows it with `pass`, and returns `set()`. `if target_features:` evaluates to `False`, leaving `filtered` completely untouched. Thus, passing `--feature abc` runs ALL 4 tests in the suite rather than rejecting invalid input (exit code 2) or matching 0 tests.

### Test 2: Timeout Behavior
- **Setup**: Created a mock test method executing `time.sleep(5)` and executed with `--timeout 1`.
- **Command**: `time python3 tests/e2e/run_e2e_tests.py --feature 999 --timeout 1`
- **Result**: Exit code `1`. Total runtime was `2.347s` (did not hang for 5s).
- **Output**:
  ```
  Executing 3 test(s): FF.
  [2] [Tier 1][F999] test_z_adversarial_mock::TestAdversarialMock.test_sleep_5s (FAILED)
      Message: Execution timed out after 1.0s
  OVERALL STATUS: FAILED (Total: 3, Failed: 2, Errors: 0, Passed: 1, Duration: 2.01s)
  ```
- **Subprocess Timeout**: Created a test executing `self.run_cli(["sleep", "10"], timeout=30.0)` under `--timeout 1`.
  - **Result**: Timed out after `2.07s` with exit code `1`, proving SIGALRM successfully interrupts blocking subprocess executions.

### Test 3: Fail-Fast Execution (`--fail-fast` and `-x`)
- **Setup**: Test suite with 3 tests in order: `test_fail_first` (fails), `test_sleep_5s` (sleeps 5s), `test_subsequent_pass` (passes).
- **Command**: `time python3 tests/e2e/run_e2e_tests.py --feature 999 --fail-fast`
- **Result**: Exit code `1`. Total duration `0.268s`.
- **Output**:
  ```
  Executing 3 test(s): F
  --- Tier Summary ---
    Tier 1: Total=  1 | Passed=  0 | Failed=  1 | Skipped=  0
  [1] [Tier 1][F999] test_z_adversarial_mock::TestAdversarialMock.test_fail_first (FAILED)
      Message: Intentional mock failure for fail-fast check
  OVERALL STATUS: FAILED (Total: 1, Failed: 1, Errors: 0, Passed: 0, Duration: 0.00s)
  ```
- **Short Flag**: `python3 tests/e2e/run_e2e_tests.py --feature 999 -x` produced identical immediate abort.

### Test 4: Output Format Syntax & Parseability
- **Populated Test Runs**:
  - TAP format (`--format tap`): Emitted valid TAP version 13 with YAML diagnostic blocks (`---` / `...`).
  - JUnit format (`--format junit`): Successfully parsed with `xml.etree.ElementTree.fromstring()`. XML special characters (`<`, `>`, `&`, `"`, `'`) and Kurmanji Unicode characters (`Kurdî`, `îûşç`) were cleanly escaped.
  - JSON format (`--format json`): Successfully parsed with `json.loads()`.
  - Output to file (`-o` / `--output`): Successfully written and verified for all formats.
- **Empty Test Run Edge Case**:
  - **Command**: `python3 tests/e2e/run_e2e_tests.py --tier 2 --format json`
  - **Result**: Exit code `0`, but stdout is completely EMPTY (0 bytes). Empty string is invalid JSON (`json.decoder.JSONDecodeError`).
  - **Command**: `python3 tests/e2e/run_e2e_tests.py --tier 2 --format json --output /tmp/test_report.json`
  - **Result**: Exit code `0`, but file `/tmp/test_report.json` was NOT created (`ls: cannot access '/tmp/test_report.json': No such file or directory`).
  - **Code Inspection** (`tests/e2e/run_e2e_tests.py`, lines 153-157):
    ```python
    if not selected_tests:
        if args.format == "console":
            print(f"No tests discovered for tier={args.tier}, feature={args.feature}")
            print(f"Total discovered in suite: {len(all_tests)}")
        return 0
    ```
    The runner early-returns before formatting or opening `args.output`, causing broken pipelines when no tests match the filter.

### Test 5: Broken Test Discovery Error Handling
- **Setup**: Created test file with invalid import `import non_existent_package_that_does_not_exist`.
- **Command**: `python3 tests/e2e/run_e2e_tests.py`
- **Result**: Exit code `1`.
- **Output**:
  ```
  [1] [Tier 1][F?] test_broken_syntax::ImportErrorCase.test_import_error (ERROR)
      Message: NameError: cannot access free variable 'e' where it is not associated with a value in enclosing scope
  ```
- **Code Inspection** (`tests/e2e/harness/runner.py`, lines 110-116):
  ```python
  except Exception as e:
      class ImportErrorCase(unittest.TestCase):
          pass
      setattr(ImportErrorCase, "test_import_error", lambda s: (_ for _ in ()).throw(e))
  ```
  In Python 3, `e` is deleted at the end of the `except` block scope. When `test_import_error` executes later, referencing `e` raises `NameError: cannot access free variable 'e'`, completely masking the actual `ModuleNotFoundError` or `SyntaxError`.

---

## 2. Logic Chain

1. **Premise 1**: Per `TEST_INFRA.md` Section 3 and the Dispatch Objective 1, invalid flags and CLI arguments must be rejected with exit code `2`, and filters must restrict execution to matching tests.
   - Observation: When `--feature abc` is passed, `_parse_feature_filter` catches `ValueError` and returns an empty set. `filter_tests` checks `if target_features:`, which evaluates to `False`, leaving the test list unfiltered. The runner then executes the entire test suite.
   - Deduction: Malformed `--feature` input silently bypasses filtering, violating user intent and argument validation contracts.

2. **Premise 2**: Per `TEST_INFRA.md` Section 3 and Dispatch Objective 4, formatted reports (JSON, JUnit XML, TAP) must be valid parseable output, and `--output` must write the report.
   - Observation: When a filter matches 0 tests (e.g. `--tier 2` or `--feature 99`), `run_e2e_tests.py` early-returns on line 157. For `--format json` or `--format junit`, stdout is empty (invalid syntax). When `--output` is specified, no output file is written.
   - Deduction: Downstream CI/CD stages parsing reports or expecting output artifacts will fail with parse errors or file-not-found errors on 0-test runs.

3. **Premise 3**: Per `TEST_INFRA.md` Section 1, the test runner must be robust and opaque-box. When test modules have import/syntax errors, it must report the underlying failure.
   - Observation: `setattr(ImportErrorCase, "test_import_error", lambda s: (_ for _ in ()).throw(e))` produces a `NameError` on `e` because Python 3 clears `e` on `except` block exit.
   - Deduction: Test authoring debugging is impeded because syntax/import errors are hidden behind a confusing Python internal scope `NameError`.

4. **Premise 4**: Timeouts and fail-fast work reliably and robustly.
   - Observation: Timeouts trigger SIGALRM and fail within ~2s with exit code 1; fail-fast aborts immediately on failure without executing remaining tests.
   - Deduction: Core scheduling, timeout handling, and fail-fast logic are empirically sound.

---

## 3. Caveats

- Only Tier 1 tests currently exist in the repository (`tests/e2e/tier1/test_f00_infra_smoke.py`). Tiers 2 through 4 have not yet been authored (planned for subsequent milestones).
- Stress-testing of timeouts and fail-fast was performed using temporary mock test cases written directly to `tests/e2e/tier1/test_z_adversarial_mock.py` and completely wiped after empirical verification.
- Testing was executed on Linux x86_64 with Python 3.14.

---

## 4. Conclusion

**Verdict: `REQUEST_CHANGES`**

While the core runner engine, timeout protection (SIGALRM), fail-fast abort (`-x`), and active report formatters (Console, TAP, JUnit, JSON) are well-architected and pass stress-testing, three actionable defects were empirically discovered and must be addressed:

1. **Defect 1 (High - Filter Bypass)**: Malformed `--feature` expressions (e.g. `--feature abc`) should either raise a CLI argument error (exit code `2`) or filter to an empty list (matching 0 tests), rather than silently running all tests in the suite.
2. **Defect 2 (Medium - Missing / Empty Report on Zero Matches)**: When `not selected_tests`, if `--format` is `json`, `junit`, or `tap`, the runner should still generate and emit/write a valid empty report structure (or handle `--output` consistently) rather than returning 0 with empty stdout and no output file created.
3. **Defect 3 (Medium - Scope Leak in ImportErrorCase)**: In `runner.py` line 115, bind `e` at definition time (e.g. `def make_raiser(err): return lambda s: (_ for _ in ()).throw(err)` or default argument `lambda s, err=e: (_ for _ in ()).throw(err)`) so the real import/syntax exception is raised rather than `NameError: cannot access free variable 'e'`.

---

## 5. Verification Method

To independently reproduce all findings:

1. **Reproduce Filter Bypass**:
   ```bash
   # Should NOT execute tests, but currently executes all 4 tests:
   python3 tests/e2e/run_e2e_tests.py --feature abc
   ```

2. **Reproduce Empty Report & Missing File**:
   ```bash
   # Stderr/stdout is completely empty:
   python3 tests/e2e/run_e2e_tests.py --tier 2 --format json
   # Output file is NOT created:
   python3 tests/e2e/run_e2e_tests.py --tier 2 --format json --output /tmp/missing_report.json
   ls -la /tmp/missing_report.json
   ```

3. **Reproduce ImportError NameError**:
   ```bash
   echo "import non_existent_pkg_xyz" > tests/e2e/tier1/test_temp_broken.py
   python3 tests/e2e/run_e2e_tests.py
   # Shows "NameError: cannot access free variable 'e'"
   rm -f tests/e2e/tier1/test_temp_broken.py
   ```

4. **Verify Baseline Pass**:
   ```bash
   python3 tests/e2e/run_e2e_tests.py
   python3 tests/e2e/test_harness_self_verification.py
   ```
