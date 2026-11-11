"""
AF/Bulletin E2E Test Harness Package.
Provides unified base classes, runners, reporters, subprocess management,
HTTP mocking, and domain assertion utilities.
"""

from .assertions import (
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
from .base import E2ETestCase, feature, tier, timeout
from .mock_http import MockHttpServer
from .php_bridge import PROJECT_ROOT, PhpBridge
from .process import ProcessResult, run_cli, run_php, run_python
from .reporters import (
    BaseReporter,
    ConsoleReporter,
    JsonReporter,
    JUnitReporter,
    TapReporter,
    TestCaseResult,
    TestRunSummary,
)
from .runner import TestItem, TestRunnerEngine
from .sandbox import Sandbox

__all__ = [
    "E2ETestCase",
    "tier",
    "feature",
    "timeout",
    "TestRunnerEngine",
    "TestItem",
    "BaseReporter",
    "ConsoleReporter",
    "TapReporter",
    "JUnitReporter",
    "JsonReporter",
    "TestCaseResult",
    "TestRunSummary",
    "ProcessResult",
    "run_cli",
    "run_python",
    "run_php",
    "PhpBridge",
    "MockHttpServer",
    "Sandbox",
    "PROJECT_ROOT",
    "E2EAssertionError",
    "assert_exit_code",
    "assert_json_envelope",
    "assert_mcp_success",
    "assert_mcp_error",
    "assert_html_tag_present",
    "assert_html_tag_absent",
    "assert_no_inline_styles_or_scripts",
    "assert_wikipedia_slug",
    "assert_normalized_number",
    "assert_date_is_today",
]
