"""
Base test case and decorators for the E2E test framework.
Provides E2ETestCase, @tier, @feature decorators, automatic sandboxing,
and domain assertion wrappers.
"""

from __future__ import annotations

import functools
import os
import unittest
from typing import Any, Callable, Dict, List, Optional, Union

from . import assertions
from .mock_http import MockHttpServer
from .php_bridge import PhpBridge, PROJECT_ROOT
from .process import ProcessResult, run_cli, run_php, run_python
from .sandbox import Sandbox


def tier(level: int) -> Callable[[Any], Any]:
    """Decorator to assign a test method or class to a tier (1, 2, 3, 4)."""
    def decorator(target: Any) -> Any:
        target.__e2e_tier__ = int(level)
        return target
    return decorator


def feature(*feature_ids: int) -> Callable[[Any], Any]:
    """Decorator to associate a test method or class with feature ID(s) (1 to 36)."""
    def decorator(target: Any) -> Any:
        # Flatten and validate feature ids
        ids = []
        for fid in feature_ids:
            if isinstance(fid, (list, tuple)):
                ids.extend(int(x) for x in fid)
            else:
                ids.extend(int(x) for x in str(fid).split(",") if x.strip())
        target.__e2e_features__ = ids
        return target
    return decorator


def timeout(seconds: float) -> Callable[[Any], Any]:
    """Decorator to specify a custom timeout (in seconds) for a test method."""
    def decorator(target: Any) -> Any:
        target.__e2e_timeout__ = float(seconds)
        return target
    return decorator


class E2ETestCase(unittest.TestCase):
    """
    Base test case for all E2E test tiers.
    Provides automatic sandboxing, embedded HTTP mock server access,
    process execution wrappers, and domain assertion helpers.
    """

    default_tier: int = 1
    default_features: List[int] = []

    def setUp(self) -> None:
        super().setUp()
        self.sandbox = Sandbox()
        self.sandbox.create()
        self.php = PhpBridge(project_root=PROJECT_ROOT)
        self._mock_http: Optional[MockHttpServer] = None

    def tearDown(self) -> None:
        if self._mock_http is not None:
            self._mock_http.stop()
            self._mock_http = None
        if hasattr(self, "sandbox") and self.sandbox:
            self.sandbox.cleanup()
        super().tearDown()

    @property
    def project_root(self) -> str:
        """Root directory of the AF/Bulletin repository."""
        return PROJECT_ROOT

    @property
    def mock_http(self) -> MockHttpServer:
        """Lazily initialize and start an embedded HTTP mock server."""
        if self._mock_http is None:
            self._mock_http = MockHttpServer()
            self._mock_http.start()
        return self._mock_http

    def fixture_path(self, *parts: str) -> str:
        """Get absolute path to a fixture file under tests/e2e/fixtures/."""
        return os.path.join(self.project_root, "tests", "e2e", "fixtures", *parts)

    # Process execution shortcuts
    def run_cli(
        self,
        cmd: Union[str, List[str]],
        cwd: Optional[str] = None,
        env: Optional[Dict[str, str]] = None,
        timeout: float = 30.0,
        input_data: Optional[str] = None
    ) -> ProcessResult:
        """Execute CLI command with sandbox cwd by default if inside sandbox."""
        work_dir = cwd or (self.sandbox.root if hasattr(self, "sandbox") else self.project_root)
        return run_cli(cmd, cwd=work_dir, env=env, timeout=timeout, input_data=input_data)

    def run_python(
        self,
        args: Union[str, List[str]],
        cwd: Optional[str] = None,
        env: Optional[Dict[str, str]] = None,
        timeout: float = 30.0,
        input_data: Optional[str] = None
    ) -> ProcessResult:
        """Execute Python command with project root or sandbox cwd."""
        work_dir = cwd or self.project_root
        return run_python(args, cwd=work_dir, env=env, timeout=timeout, input_data=input_data)

    def run_php(
        self,
        args: Union[str, List[str]],
        cwd: Optional[str] = None,
        env: Optional[Dict[str, str]] = None,
        timeout: float = 30.0,
        input_data: Optional[str] = None
    ) -> ProcessResult:
        """Execute PHP command with project root cwd."""
        work_dir = cwd or self.project_root
        return run_php(args, cwd=work_dir, env=env, timeout=timeout, input_data=input_data)

    # Domain Assertion Wrappers
    def assertExitCode(
        self,
        result: Any,
        expected_code: Union[int, List[int]] = 0,
        msg: Optional[str] = None
    ) -> None:
        assertions.assert_exit_code(result, expected_code=expected_code, msg=msg)

    def assertJsonEnvelope(
        self,
        data: Union[str, bytes, Dict[str, Any]],
        required_keys: Optional[List[str]] = None,
        msg: Optional[str] = None
    ) -> Dict[str, Any]:
        return assertions.assert_json_envelope(data, required_keys=required_keys, msg=msg)

    def assertMcpSuccess(
        self,
        response: Union[str, bytes, Dict[str, Any]],
        msg: Optional[str] = None
    ) -> Dict[str, Any]:
        return assertions.assert_mcp_success(response, msg=msg)

    def assertMcpError(
        self,
        response: Union[str, bytes, Dict[str, Any]],
        expected_code: Optional[int] = None,
        message_contains: Optional[str] = None,
        msg: Optional[str] = None
    ) -> Dict[str, Any]:
        return assertions.assert_mcp_error(
            response, expected_code=expected_code, message_contains=message_contains, msg=msg
        )

    def assertHtmlTagPresent(
        self,
        html: str,
        tag: str,
        attrs: Optional[Dict[str, str]] = None,
        msg: Optional[str] = None
    ) -> None:
        assertions.assert_html_tag_present(html, tag, attrs=attrs, msg=msg)

    def assertHtmlTagAbsent(
        self,
        html: str,
        tag: str,
        attrs: Optional[Dict[str, str]] = None,
        msg: Optional[str] = None
    ) -> None:
        assertions.assert_html_tag_absent(html, tag, attrs=attrs, msg=msg)

    def assertNoInlineStylesOrScripts(self, content: str, msg: Optional[str] = None) -> None:
        assertions.assert_no_inline_styles_or_scripts(content, msg=msg)

    def assertWikipediaSlug(self, slug: str, msg: Optional[str] = None) -> None:
        assertions.assert_wikipedia_slug(slug, msg=msg)

    def assertNormalizedNumber(self, text: str, number: Union[int, float, str], msg: Optional[str] = None) -> None:
        assertions.assert_normalized_number(text, number, msg=msg)

    def assertDateIsToday(
        self,
        date_val: Union[str, Any],
        tz_name: str = "Europe/Istanbul",
        msg: Optional[str] = None
    ) -> None:
        assertions.assert_date_is_today(date_val, tz_name=tz_name, msg=msg)
