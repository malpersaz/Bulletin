"""
Comprehensive self-verification test suite for AF/Bulletin E2E test harness.
Verifies assertions, process execution, sandboxing, mock HTTP server,
PHP bridge, and report formatters.
"""

import json
import os
import stat
import sys
import unittest
from datetime import datetime, timezone

# Ensure tests/e2e is in sys.path
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
if SCRIPT_DIR not in sys.path:
    sys.path.insert(0, SCRIPT_DIR)

from harness.assertions import (
    E2EAssertionError,
    assert_date_is_today,
    assert_exit_code,
    assert_html_tag_absent,
    assert_html_tag_present,
    assert_json_envelope,
    assert_mcp_error,
    assert_mcp_success,
    assert_no_inline_styles_or_scripts,
    assert_normalized_number,
    assert_wikipedia_slug,
)
from harness.base import E2ETestCase, feature, tier, timeout
from harness.mock_http import MockHttpServer
from harness.php_bridge import PhpBridge
from harness.process import ProcessResult, run_cli, run_php, run_python
from harness.reporters import (
    ConsoleReporter,
    JsonReporter,
    JUnitReporter,
    TapReporter,
    TestCaseResult,
    TestRunSummary,
)
from harness.runner import TestItem, TestRunnerEngine
from harness.sandbox import Sandbox


class TestHarnessAssertions(unittest.TestCase):
    """Test domain-specific assertion utilities."""

    def test_assert_exit_code(self):
        res = ProcessResult(cmd=["true"], exit_code=0, stdout="", stderr="", duration_sec=0.1)
        assert_exit_code(res, 0)
        assert_exit_code(res, [0, 1])
        with self.assertRaises(E2EAssertionError):
            assert_exit_code(res, 1)

    def test_assert_json_envelope(self):
        raw = '{"status": "ok", "count": 5}'
        parsed = assert_json_envelope(raw, required_keys=["status", "count"])
        self.assertEqual(parsed["status"], "ok")

        with self.assertRaises(E2EAssertionError):
            assert_json_envelope(raw, required_keys=["missing_key"])

        with self.assertRaises(E2EAssertionError):
            assert_json_envelope("invalid json string")

    def test_assert_mcp_success_and_error(self):
        success_payload = {"jsonrpc": "2.0", "result": {"items": [1, 2, 3]}, "id": 1}
        result = assert_mcp_success(success_payload)
        self.assertEqual(result["items"], [1, 2, 3])

        error_payload = {
            "jsonrpc": "2.0",
            "error": {"code": -32602, "message": "Invalid params: root category rejected"},
            "id": 2
        }
        err = assert_mcp_error(error_payload, expected_code=-32602, message_contains="root category")
        self.assertEqual(err["code"], -32602)

        with self.assertRaises(E2EAssertionError):
            assert_mcp_success(error_payload)

        with self.assertRaises(E2EAssertionError):
            assert_mcp_error(success_payload)

    def test_assert_html_tag(self):
        html = '<figure class="detail-hero main-img"><figcaption class="news-hero-caption">Photo Credit</figcaption></figure>'
        assert_html_tag_present(html, "figure", attrs={"class": "detail-hero"})
        assert_html_tag_present(html, "figcaption", attrs={"class": "news-hero-caption"})
        assert_html_tag_absent(html, "script")

        with self.assertRaises(E2EAssertionError):
            assert_html_tag_present(html, "table")

    def test_assert_code_separation(self):
        clean_html = '<div class="news-item"><p class="text-body">Clean content</p></div>'
        assert_no_inline_styles_or_scripts(clean_html)

        dirty_style = '<div style="color: red;">Violating style</div>'
        with self.assertRaises(E2EAssertionError):
            assert_no_inline_styles_or_scripts(dirty_style)

        dirty_script = '<div><script>alert(1);</script></div>'
        with self.assertRaises(E2EAssertionError):
            assert_no_inline_styles_or_scripts(dirty_script)

        dirty_onclick = '<button onclick="handleClick()">Click</button>'
        with self.assertRaises(E2EAssertionError):
            assert_no_inline_styles_or_scripts(dirty_onclick)

    def test_assert_wikipedia_slug(self):
        assert_wikipedia_slug("Diyarbakır_Tarih_ve_Kültür_Rehberi")
        assert_wikipedia_slug("Zimanê_Kurdî_û_Wêje")

        with self.assertRaises(E2EAssertionError):
            assert_wikipedia_slug("Slug_With_\u0307_Dot_Above")

        with self.assertRaises(E2EAssertionError):
            assert_wikipedia_slug("Slug With Spaces")

    def test_assert_normalized_number(self):
        text = "Projede toplam 45 dönümlük arazi kullanıldı ve 120 ağaç dikildi."
        assert_normalized_number(text, 45)
        assert_normalized_number(text, 120)

        with self.assertRaises(E2EAssertionError):
            # 4 is inside 45, but not standalone
            assert_normalized_number(text, 4)

    def test_assert_date_is_today(self):
        today_iso = datetime.now().strftime("%Y-%m-%d")
        assert_date_is_today(today_iso)

        with self.assertRaises(E2EAssertionError):
            assert_date_is_today("2020-01-01")

    def test_assert_code_separation_json_ld_and_external_scripts(self):
        # Valid JSON-LD should be permitted
        json_ld = '<script type="application/ld+json">{"@context": "https://schema.org", "@type": "NewsArticle"}</script>'
        assert_no_inline_styles_or_scripts(json_ld)

        # Valid external script should be permitted
        ext_script = '<script src="/static/js/bundle.js" defer></script>'
        assert_no_inline_styles_or_scripts(ext_script)

        # External script with inline body must be rejected
        dirty_ext = '<script src="/static/js/bundle.js">console.log("bad");</script>'
        with self.assertRaises(E2EAssertionError):
            assert_no_inline_styles_or_scripts(dirty_ext)

        # Inline script without type="application/ld+json" or src must be rejected
        plain_script = '<script>alert(1);</script>'
        with self.assertRaises(E2EAssertionError):
            assert_no_inline_styles_or_scripts(plain_script)

    def test_assert_html_tag_quote_handling_and_exact_attribute_matching(self):
        # Tag containing '>' inside attribute quotes
        html = '<img alt="Comparison: A > B" src="hero.jpg" class="detail-hero-image primary">'
        assert_html_tag_present(html, "img", attrs={"alt": "Comparison: A > B", "class": "detail-hero-image"})

        # Substring attribute value must NOT match exact identifier
        html_id = '<div id="superhero" class="container">'
        with self.assertRaises(E2EAssertionError):
            assert_html_tag_present(html_id, "div", attrs={"id": "hero"})

        # Exact id match works
        assert_html_tag_present(html_id, "div", attrs={"id": "superhero"})

    def test_assert_date_is_today_with_timezones_and_fractional_seconds(self):
        try:
            from zoneinfo import ZoneInfo
            tz_ist = ZoneInfo("Europe/Istanbul")
        except ImportError:
            tz_ist = None

        if tz_ist:
            now_ist = datetime.now(tz_ist)
            # ISO format with timezone offset (+03:00)
            assert_date_is_today(now_ist.isoformat())

            # Convert to UTC with 'Z' and fractional seconds
            utc_now = now_ist.astimezone(timezone.utc)
            utc_str = utc_now.strftime("%Y-%m-%dT%H:%M:%S.456Z")
            assert_date_is_today(utc_str)

            # tz-aware datetime object
            assert_date_is_today(utc_now)

        # Past date must raise
        with self.assertRaises(E2EAssertionError):
            assert_date_is_today("2021-05-10T12:00:00Z")


class TestHarnessSandbox(unittest.TestCase):
    """Test filesystem isolation and sandboxing."""

    def test_sandbox_lifecycle(self):
        sb = Sandbox()
        root = sb.create()
        self.assertTrue(os.path.exists(root))

        file_path = sb.write_file("test.txt", "hello sandbox")
        self.assertTrue(os.path.exists(file_path))
        self.assertEqual(sb.read_file("test.txt"), "hello sandbox")

        sb.cleanup()
        self.assertFalse(os.path.exists(root))

    def test_sandbox_context_manager(self):
        captured_root = None
        with Sandbox() as sb:
            captured_root = sb.root
            self.assertTrue(os.path.exists(captured_root))
            sb.write_file("nested/data.json", '{"a": 1}')
            self.assertTrue(sb.exists("nested/data.json"))

        self.assertFalse(os.path.exists(captured_root))

    def test_sandbox_path_traversal_prevention(self):
        with Sandbox() as sb:
            # Leading slash traversal attempt
            with self.assertRaises(ValueError):
                sb.path("/tmp/outside_file.txt")

            # Relative climb traversal attempt
            with self.assertRaises(ValueError):
                sb.path("../outside_file.txt")

            with self.assertRaises(ValueError):
                sb.write_file("../forbidden.txt", "exploit")

            # exists returns False on traversal attempt
            self.assertFalse(sb.exists("../outside_file.txt"))

    def test_sandbox_cleanup_readonly_directories(self):
        sb = Sandbox()
        root = sb.create()
        self.assertTrue(os.path.exists(root))

        ro_dir = sb.mkdir("read_only_subdir")
        file_path = sb.write_file("read_only_subdir/test_file.txt", "data")
        self.assertTrue(os.path.exists(file_path))

        # Make file and directory read-only
        os.chmod(file_path, stat.S_IREAD)
        os.chmod(ro_dir, stat.S_IREAD | stat.S_IEXEC)

        sb.cleanup()
        self.assertFalse(os.path.exists(root))


class TestHarnessMockHttp(unittest.TestCase):
    """Test embedded Python HTTP mock server."""

    def test_mock_http_server(self):
        import urllib.request

        with MockHttpServer() as server:
            server.add_route("/test/article", "<html><body>Article 128</body></html>")
            server.add_route("/api/status", {"status": "ok", "items": [1, 2]})
            server.add_route("/rate-limited", "Too Many Requests", status=429, headers={"Retry-After": "2"})

            # GET /test/article
            req = urllib.request.urlopen(server.get_url("/test/article"))
            self.assertEqual(req.status, 200)
            self.assertIn(b"Article 128", req.read())

            # GET /api/status
            req_api = urllib.request.urlopen(server.get_url("/api/status"))
            self.assertEqual(req_api.status, 200)
            data = json.loads(req_api.read().decode())
            self.assertEqual(data["status"], "ok")

            # GET 404
            with self.assertRaises(urllib.error.HTTPError) as ctx:
                urllib.request.urlopen(server.get_url("/nonexistent"))
            self.assertEqual(ctx.exception.code, 404)
            ctx.exception.close()

            # Check request history
            history = server.get_requests("/test/article")
            self.assertEqual(len(history), 1)
            self.assertEqual(history[0]["method"], "GET")

    def test_mock_http_reentrant_concurrency(self):
        import urllib.request

        with MockHttpServer() as server:
            def cb_parent(req):
                nested_res = urllib.request.urlopen(server.get_url("/sub"), timeout=3).read()
                return 200, None, f"Parent got: {nested_res.decode('utf-8')}"

            server.add_callback("/parent", cb_parent)
            server.add_route("/sub", "SUB_OK")

            res = urllib.request.urlopen(server.get_url("/parent"), timeout=5).read()
            self.assertIn(b"Parent got: SUB_OK", res)

    def test_mock_http_header_immutability_and_none_callback_headers(self):
        import urllib.request

        with MockHttpServer() as server:
            caller_headers = {"X-Trace-Id": "trace-123"}
            server.add_route("/immutability", "hello", headers=caller_headers)

            req = urllib.request.urlopen(server.get_url("/immutability"))
            self.assertEqual(req.status, 200)

            # Ensure caller_headers was not mutated in place
            self.assertNotIn("Content-Type", caller_headers)
            self.assertEqual(caller_headers, {"X-Trace-Id": "trace-123"})

            # Callback returning None as headers
            def cb_none_headers(req):
                return 200, None, "body with none headers"

            server.add_callback("/none-headers", cb_none_headers)
            req_none = urllib.request.urlopen(server.get_url("/none-headers"))
            self.assertEqual(req_none.status, 200)
            self.assertIn(b"body with none headers", req_none.read())

    def test_mock_http_rest_methods(self):
        import urllib.request

        with MockHttpServer() as server:
            server.add_route("/api/resource", {"id": 42}, status=200)

            # Test PUT
            req_put = urllib.request.Request(server.get_url("/api/resource"), method="PUT", data=b'{"update": 1}')
            with urllib.request.urlopen(req_put) as resp_put:
                self.assertEqual(resp_put.status, 200)

            # Test DELETE
            req_del = urllib.request.Request(server.get_url("/api/resource"), method="DELETE")
            with urllib.request.urlopen(req_del) as resp_del:
                self.assertEqual(resp_del.status, 200)

            # Test OPTIONS
            req_opt = urllib.request.Request(server.get_url("/api/resource"), method="OPTIONS")
            with urllib.request.urlopen(req_opt) as resp_opt:
                self.assertEqual(resp_opt.status, 200)

            # Verify all recorded in request history
            history = server.get_requests("/api/resource")
            methods = [h["method"] for h in history]
            self.assertIn("PUT", methods)
            self.assertIn("DELETE", methods)
            self.assertIn("OPTIONS", methods)


class TestHarnessProcessAndPhpBridge(unittest.TestCase):
    """Test process execution and PHP bridge."""

    def test_run_cli_and_python(self):
        res = run_python(["-c", "import sys; print('python_ok'); sys.stderr.write('err_ok')"])
        self.assertEqual(res.exit_code, 0)
        self.assertIn("python_ok", res.stdout)
        self.assertIn("err_ok", res.stderr)
        self.assertGreater(res.duration_sec, 0.0)

    def test_run_cli_timeout(self):
        res = run_cli(["sleep", "2"], timeout=0.2)
        self.assertTrue(res.timed_out)
        self.assertEqual(res.exit_code, -1)

    def test_php_bridge(self):
        bridge = PhpBridge()
        # Test basic PHP math and JSON evaluation
        data = bridge.eval_json("echo json_encode(['sum' => 2 + 2, 'text' => 'amed']);")
        self.assertEqual(data["sum"], 4)
        self.assertEqual(data["text"], "amed")


class TestHarnessReporters(unittest.TestCase):
    """Test report formatters (Console, TAP, JUnit, JSON)."""

    def setUp(self):
        self.summary = TestRunSummary(
            results=[
                TestCaseResult(
                    name="test_f01_happy",
                    module="test_f01",
                    tier=1,
                    features=[1],
                    status="passed",
                    duration_sec=0.05
                ),
                TestCaseResult(
                    name="test_f02_fail",
                    module="test_f02",
                    tier=1,
                    features=[2],
                    status="failed",
                    duration_sec=0.08,
                    error_message="Assertion failed: expected 0 got 1"
                ),
            ]
        )
        self.summary.compute_metrics()

    def test_console_reporter(self):
        reporter = ConsoleReporter(use_color=False, verbose=True)
        output = reporter.format(self.summary)
        self.assertIn("Tier 1: Total=  2 | Passed=  1 | Failed=  1", output)
        self.assertIn("OVERALL STATUS: FAILED", output)

    def test_tap_reporter(self):
        reporter = TapReporter()
        output = reporter.format(self.summary)
        self.assertIn("TAP version 13", output)
        self.assertIn("1..2", output)
        self.assertIn("ok 1 - test_f01::test_f01_happy", output)
        self.assertIn("not ok 2 - test_f02::test_f02_fail", output)

    def test_junit_reporter(self):
        reporter = JUnitReporter()
        output = reporter.format(self.summary)
        self.assertIn("<testsuites", output)
        self.assertIn('name="Tier_1"', output)
        self.assertIn("<failure", output)

    def test_json_reporter(self):
        reporter = JsonReporter()
        output = reporter.format(self.summary)
        data = json.loads(output)
        self.assertEqual(data["summary"]["total"], 2)
        self.assertEqual(data["summary"]["passed"], 1)
        self.assertEqual(data["summary"]["failed"], 1)
        self.assertFalse(data["summary"]["is_success"])


class SampleDecoratedTestCase(E2ETestCase):
    default_tier = 1
    default_features = [1]

    @tier(1)
    @feature(1)
    def test_dummy_pass(self):
        self.assertExitCode(0, 0)

    @tier(2)
    @feature(2, 3)
    def test_dummy_boundary(self):
        self.assertWikipediaSlug("Slug_Valid")


class TestHarnessRunnerEngine(unittest.TestCase):
    """Test runner engine discovery and filtering."""

    def test_engine_filter(self):
        engine = TestRunnerEngine()
        item1 = TestItem(
            test_class=SampleDecoratedTestCase,
            method_name="test_dummy_pass",
            module_path="/path/test_f01.py",
            tier=1,
            features=[1],
            timeout=10.0
        )
        item2 = TestItem(
            test_class=SampleDecoratedTestCase,
            method_name="test_dummy_boundary",
            module_path="/path/test_f02.py",
            tier=2,
            features=[2, 3],
            timeout=10.0
        )
        items = [item1, item2]

        # Filter by tier 1
        t1 = engine.filter_tests(items, tier="1")
        self.assertEqual(len(t1), 1)
        self.assertEqual(t1[0].method_name, "test_dummy_pass")

        # Filter by feature 2
        f2 = engine.filter_tests(items, feature_filter="2")
        self.assertEqual(len(f2), 1)
        self.assertEqual(f2[0].method_name, "test_dummy_boundary")

        # Filter by feature range 1-2
        frange = engine.filter_tests(items, feature_filter="1-2")
        self.assertEqual(len(frange), 2)

        # Execute items
        summary = engine.run_tests(items)
        self.assertEqual(summary.total, 2)
        self.assertEqual(summary.passed, 2)
        self.assertTrue(summary.is_success)

    def test_engine_parse_feature_filter_valid_and_malformed(self):
        # Valid expressions
        self.assertEqual(TestRunnerEngine._parse_feature_filter("1"), {1})
        self.assertEqual(TestRunnerEngine._parse_feature_filter("1,2,5"), {1, 2, 5})
        self.assertEqual(TestRunnerEngine._parse_feature_filter("1-4"), {1, 2, 3, 4})
        self.assertEqual(TestRunnerEngine._parse_feature_filter("1,3-5,8"), {1, 3, 4, 5, 8})

        # Malformed expressions must raise ValueError
        with self.assertRaises(ValueError):
            TestRunnerEngine._parse_feature_filter("abc")

        with self.assertRaises(ValueError):
            TestRunnerEngine._parse_feature_filter("1,,2")

        with self.assertRaises(ValueError):
            TestRunnerEngine._parse_feature_filter("5-2")  # inverted range

        with self.assertRaises(ValueError):
            TestRunnerEngine._parse_feature_filter("1-2-3")

        with self.assertRaises(ValueError):
            TestRunnerEngine._parse_feature_filter("")

    def test_engine_import_error_case_closure_preservation(self):
        sb = Sandbox()
        root = sb.create()
        try:
            broken_file = sb.write_file("test_broken_import.py", "import non_existent_library_xyz123\n")
            engine = TestRunnerEngine(base_dir=root)
            items = engine._load_test_file(broken_file, default_tier=1)
            self.assertEqual(len(items), 1)
            self.assertEqual(items[0].method_name, "test_import_error")

            # Execute the test item and ensure ModuleNotFoundError is captured, NOT NameError
            result = engine._execute_single_test(items[0])
            self.assertEqual(result.status, "error")
            self.assertIn("ModuleNotFoundError", result.error_message)
            self.assertNotIn("NameError", result.error_message)
        finally:
            sb.cleanup()

    def test_engine_module_ownership_filter(self):
        sb = Sandbox()
        root = sb.create()
        try:
            # Define module a with TestCase class
            sb.write_file("mod_a.py", (
                "import unittest\n"
                "class BaseCase(unittest.TestCase):\n"
                "    def test_base_method(self):\n"
                "        pass\n"
            ))
            # Define module b importing BaseCase from mod_a
            mod_b_code = (
                "import sys, os\n"
                f"sys.path.insert(0, r'{root}')\n"
                "from mod_a import BaseCase\n"
                "class DerivedCase(BaseCase):\n"
                "    def test_derived_method(self):\n"
                "        pass\n"
            )
            file_b = sb.write_file("test_mod_b.py", mod_b_code)
            engine = TestRunnerEngine(base_dir=root)
            items = engine._load_test_file(file_b, default_tier=1)

            # Items should only contain DerivedCase methods, NOT BaseCase methods
            class_names = [item.test_class.__name__ for item in items]
            self.assertIn("DerivedCase", class_names)
            self.assertNotIn("BaseCase", class_names)
        finally:
            sb.cleanup()

    def test_engine_zero_matches_empty_summary(self):
        engine = TestRunnerEngine()
        filtered = engine.filter_tests([], tier="2", feature_filter="99")
        self.assertEqual(len(filtered), 0)
        summary = engine.run_tests(filtered)
        self.assertEqual(summary.total, 0)
        self.assertTrue(summary.is_success)


class TestHarnessCliRunner(unittest.TestCase):
    """Test CLI runner argument validation, zero-match formatting, and exits."""

    def test_cli_malformed_feature_filter_exits_2(self):
        runner_path = os.path.join(SCRIPT_DIR, "run_e2e_tests.py")
        res = run_python([runner_path, "--feature", "abc"])
        self.assertEqual(res.exit_code, 2)
        self.assertIn("Configuration/CLI Error", res.stderr)

    def test_cli_invalid_tier_filter_exits_2(self):
        runner_path = os.path.join(SCRIPT_DIR, "run_e2e_tests.py")
        res = run_python([runner_path, "--tier", "abc"])
        self.assertEqual(res.exit_code, 2)
        self.assertIn("Configuration/CLI Error", res.stderr)

    def test_cli_zero_matches_json_outputs_valid_empty_json(self):
        runner_path = os.path.join(SCRIPT_DIR, "run_e2e_tests.py")
        res = run_python([runner_path, "--tier", "99", "--format", "json"])
        self.assertEqual(res.exit_code, 0)
        data = json.loads(res.stdout)
        self.assertEqual(data["summary"]["total"], 0)
        self.assertTrue(data["summary"]["is_success"])
        self.assertEqual(data["tests"], [])

    def test_cli_zero_matches_to_output_file(self):
        runner_path = os.path.join(SCRIPT_DIR, "run_e2e_tests.py")
        with Sandbox() as sb:
            out_path = sb.path("report.json")
            res = run_python([runner_path, "--tier", "99", "--format", "json", "--output", out_path])
            self.assertEqual(res.exit_code, 0)
            self.assertTrue(os.path.exists(out_path))
            with open(out_path, "r", encoding="utf-8") as f:
                data = json.load(f)
            self.assertEqual(data["summary"]["total"], 0)

    def test_cli_zero_matches_junit_and_tap(self):
        runner_path = os.path.join(SCRIPT_DIR, "run_e2e_tests.py")
        res_junit = run_python([runner_path, "--tier", "99", "--format", "junit"])
        self.assertEqual(res_junit.exit_code, 0)
        self.assertIn("<testsuites", res_junit.stdout)
        self.assertIn('tests="0"', res_junit.stdout)

        res_tap = run_python([runner_path, "--tier", "99", "--format", "tap"])
        self.assertEqual(res_tap.exit_code, 0)
        self.assertIn("TAP version 13", res_tap.stdout)
        self.assertIn("1..0", res_tap.stdout)


if __name__ == "__main__":
    unittest.main()
