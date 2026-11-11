"""
Test discovery engine, filter, executor, and timeout manager.
Discovers E2E tests across tier1-tier4, applies tier and feature filters,
executes tests isolated with timeout protection, and aggregates results.
"""

from __future__ import annotations

import importlib.util
import inspect
import os
import signal
import sys
import time
import traceback
import unittest
from typing import Any, Callable, Dict, List, Optional, Set, Tuple

from .base import E2ETestCase
from .php_bridge import PROJECT_ROOT
from .reporters import TestCaseResult, TestRunSummary


class TimeoutException(Exception):
    """Raised when a test exceeds its execution timeout."""
    pass


def _timeout_handler(signum, frame):
    raise TimeoutException("Test case execution timed out.")


class TestItem:
    """Discovered test case method with associated metadata."""

    def __init__(
        self,
        test_class: type[unittest.TestCase],
        method_name: str,
        module_path: str,
        tier: int,
        features: List[int],
        timeout: float
    ):
        self.test_class = test_class
        self.method_name = method_name
        self.module_path = module_path
        self.tier = tier
        self.features = features
        self.timeout = timeout

    @property
    def id(self) -> str:
        mod_name = os.path.splitext(os.path.basename(self.module_path))[0]
        return f"{mod_name}::{self.test_class.__name__}::{self.method_name}"

    def __repr__(self) -> str:
        return f"<TestItem {self.id} tier={self.tier} features={self.features}>"


class TestRunnerEngine:
    """
    Engine to discover, filter, and execute E2E test suites.
    """

    def __init__(
        self,
        base_dir: Optional[str] = None,
        default_timeout: float = 30.0,
        fail_fast: bool = False,
        verbose: bool = False
    ):
        self.base_dir = base_dir or os.path.join(PROJECT_ROOT, "tests", "e2e")
        self.default_timeout = default_timeout
        self.fail_fast = fail_fast
        self.verbose = verbose

    def discover_tests(self) -> List[TestItem]:
        """
        Scan tier1 through tier4 directories for test_*.py files
        and discover test classes and methods.
        """
        items: List[TestItem] = []
        tier_dirs = ["tier1", "tier2", "tier3", "tier4"]

        for tier_idx, t_name in enumerate(tier_dirs, 1):
            t_path = os.path.join(self.base_dir, t_name)
            if not os.path.isdir(t_path):
                continue

            for root, _, files in os.walk(t_path):
                for f in sorted(files):
                    if f.startswith("test_") and f.endswith(".py"):
                        file_path = os.path.join(root, f)
                        items.extend(self._load_test_file(file_path, tier_idx))

        return items

    def _load_test_file(self, file_path: str, default_tier: int) -> List[TestItem]:
        """Import a test module and extract all test methods."""
        items: List[TestItem] = []
        mod_name = f"e2e_test_tier{default_tier}_{os.path.splitext(os.path.basename(file_path))[0]}"
        spec = importlib.util.spec_from_file_location(mod_name, file_path)
        if not spec or not spec.loader:
            return items

        module = importlib.util.module_from_spec(spec)
        sys.modules[mod_name] = module
        try:
            spec.loader.exec_module(module)
        except Exception as e:
            # Capture module import failure as an error test item
            class ImportErrorCase(unittest.TestCase):
                pass
            setattr(ImportErrorCase, "test_import_error", lambda s, err=e: (_ for _ in ()).throw(err))
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

        for attr_name in dir(module):
            attr = getattr(module, attr_name)
            if inspect.isclass(attr) and issubclass(attr, unittest.TestCase):
                if getattr(attr, "__module__", None) != module.__name__:
                    continue
                class_tier = getattr(attr, "__e2e_tier__", getattr(attr, "default_tier", default_tier))
                class_features = getattr(attr, "__e2e_features__", getattr(attr, "default_features", []))

                for m_name in dir(attr):
                    if m_name.startswith("test_"):
                        method = getattr(attr, m_name)
                        if callable(method):
                            m_tier = getattr(method, "__e2e_tier__", class_tier)
                            m_features = getattr(method, "__e2e_features__", class_features)
                            m_timeout = getattr(method, "__e2e_timeout__", self.default_timeout)
                            items.append(
                                TestItem(
                                    test_class=attr,
                                    method_name=m_name,
                                    module_path=file_path,
                                    tier=int(m_tier),
                                    features=list(m_features),
                                    timeout=float(m_timeout)
                                )
                            )

        return items

    def filter_tests(
        self,
        tests: List[TestItem],
        tier: Optional[str] = None,
        feature_filter: Optional[str] = None
    ) -> List[TestItem]:
        """
        Filter discovered tests by tier (1, 2, 3, 4, or 'all')
        and feature ID(s) (e.g. '5', '1,2,5', '1-12').
        """
        filtered = list(tests)

        # 1. Tier filtering
        if tier and tier.lower() != "all":
            try:
                target_tier = int(tier)
                filtered = [t for t in filtered if t.tier == target_tier]
            except ValueError:
                pass

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

    def run_tests(
        self,
        tests: List[TestItem],
        progress_callback: Optional[Callable[[TestCaseResult], None]] = None
    ) -> TestRunSummary:
        """
        Execute the filtered test cases with timeout enforcement.
        """
        summary = TestRunSummary()

        for item in tests:
            result = self._execute_single_test(item)
            summary.results.append(result)

            if progress_callback:
                progress_callback(result)

            if self.fail_fast and result.status in ("failed", "error"):
                break

        summary.compute_metrics()
        return summary

    def _execute_single_test(self, item: TestItem) -> TestCaseResult:
        """Execute a single test case within timeout boundaries."""
        mod_name = os.path.splitext(os.path.basename(item.module_path))[0]
        res = TestCaseResult(
            name=f"{item.test_class.__name__}.{item.method_name}",
            module=mod_name,
            tier=item.tier,
            features=item.features,
            status="passed",
            duration_sec=0.0
        )

        test_instance = item.test_class(item.method_name)
        start_time = time.perf_counter()

        has_sigalrm = hasattr(signal, "SIGALRM")
        old_handler = None

        try:
            if has_sigalrm and item.timeout > 0:
                old_handler = signal.signal(signal.SIGALRM, _timeout_handler)
                signal.alarm(int(item.timeout) + 1)

            # Standard unittest execution
            test_instance.setUp()
            test_method = getattr(test_instance, item.method_name)
            test_method()
            test_instance.tearDown()
            res.status = "passed"
        except AssertionError as e:
            res.status = "failed"
            res.error_message = str(e)
            res.error_traceback = traceback.format_exc()
            try:
                test_instance.tearDown()
            except Exception:
                pass
        except TimeoutException as e:
            res.status = "failed"
            res.error_message = f"Execution timed out after {item.timeout}s"
            res.error_traceback = traceback.format_exc()
            try:
                test_instance.tearDown()
            except Exception:
                pass
        except unittest.SkipTest as e:
            res.status = "skipped"
            res.error_message = str(e)
            try:
                test_instance.tearDown()
            except Exception:
                pass
        except Exception as e:
            res.status = "error"
            res.error_message = f"{type(e).__name__}: {e}"
            res.error_traceback = traceback.format_exc()
            try:
                test_instance.tearDown()
            except Exception:
                pass
        finally:
            if has_sigalrm and old_handler is not None:
                signal.alarm(0)
                signal.signal(signal.SIGALRM, old_handler)
            res.duration_sec = time.perf_counter() - start_time

        return res
