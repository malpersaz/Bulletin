#!/usr/bin/env python3
"""
AF/Bulletin Unified E2E Test Runner.
Executes test suites across Tiers 1-4 with filtering, timeout management,
and multiple report formats (Console, JSON, TAP v13, JUnit XML).
"""

from __future__ import annotations

import argparse
import os
import sys

# Ensure tests/e2e and project root are in sys.path
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
PROJECT_ROOT = os.path.dirname(os.path.dirname(SCRIPT_DIR))
if SCRIPT_DIR not in sys.path:
    sys.path.insert(0, SCRIPT_DIR)
if PROJECT_ROOT not in sys.path:
    sys.path.insert(0, PROJECT_ROOT)

from harness.mock_http import MockHttpServer
from harness.reporters import (
    ConsoleReporter,
    JsonReporter,
    JUnitReporter,
    TapReporter,
    TestCaseResult,
    TestRunSummary,
)
from harness.runner import TestRunnerEngine


class CustomArgumentParser(argparse.ArgumentParser):
    """Override error exit code to 2 per CLI contract."""
    def error(self, message: str):
        sys.stderr.write(f"Configuration/CLI Error: {message}\n")
        self.print_help(sys.stderr)
        sys.exit(2)


def _validate_tier_arg(val: str) -> str:
    if val.lower() == "all":
        return val
    try:
        int(val)
    except ValueError:
        raise argparse.ArgumentTypeError(f"Invalid tier '{val}': must be an integer (1, 2, 3, 4) or 'all'")
    return val


def _validate_feature_arg(val: str) -> str:
    try:
        TestRunnerEngine._parse_feature_filter(val)
    except ValueError as e:
        raise argparse.ArgumentTypeError(str(e))
    return val


def parse_arguments() -> argparse.Namespace:
    parser = CustomArgumentParser(
        description="Amed Framework Bulletin Unified E2E Test Runner (v1.0.63)"
    )
    parser.add_argument(
        "--tier",
        type=_validate_tier_arg,
        default="all",
        help="Filter tests by tier (1: Feature Coverage, 2: Boundaries, 3: Combinations, 4: Workflows, all: Sequential)"
    )
    parser.add_argument(
        "-f", "--feature",
        type=_validate_feature_arg,
        default=None,
        help="Filter by feature ID (1 through 36, or comma-separated e.g. 1,2,5, or range 1-12)"
    )
    parser.add_argument(
        "--format",
        choices=["console", "json", "tap", "junit"],
        default="console",
        help="Report output format (default: console)"
    )
    parser.add_argument(
        "-o", "--output",
        type=str,
        default=None,
        help="Write report output to specified file path instead of stdout"
    )
    parser.add_argument(
        "-v", "--verbose",
        action="store_true",
        help="Display verbose logging, subprocess streams, and diagnostic traces"
    )
    parser.add_argument(
        "-x", "--fail-fast",
        action="store_true",
        help="Abort test execution immediately upon the first failure or error"
    )
    parser.add_argument(
        "--no-network",
        action="store_true",
        help="Run tests in offline mode using mock HTTP server and static fixtures"
    )
    parser.add_argument(
        "--timeout",
        type=float,
        default=30.0,
        help="Maximum duration per test case in seconds (default: 30.0)"
    )
    parser.add_argument(
        "--list",
        action="store_true",
        help="List all discovered test cases without executing them"
    )

    return parser.parse_args()


def list_tests(tests: list) -> None:
    """Print discovered test catalog."""
    print("=== Discovered E2E Test Cases ===")
    if not tests:
        print("  No tests found matching criteria.")
        return

    # Group by tier
    by_tier: dict = {}
    for t in tests:
        by_tier.setdefault(t.tier, []).append(t)

    for tier_num in sorted(by_tier.keys()):
        tier_tests = by_tier[tier_num]
        print(f"\n[Tier {tier_num}] ({len(tier_tests)} tests):")
        for item in tier_tests:
            f_str = ",".join(str(f) for f in item.features) if item.features else "none"
            print(f"  - {item.id} (Features: {f_str})")

    print(f"\nTotal Discovered Tests: {len(tests)}")


def main() -> int:
    try:
        args = parse_arguments()
    except SystemExit as e:
        return e.code if isinstance(e.code, int) else 2

    # Set environment variables
    if args.no_network:
        os.environ["AF_E2E_NO_NETWORK"] = "1"

    engine = TestRunnerEngine(
        base_dir=SCRIPT_DIR,
        default_timeout=args.timeout,
        fail_fast=args.fail_fast,
        verbose=args.verbose
    )

    # 1. Discover all tests
    all_tests = engine.discover_tests()

    # 2. Filter tests
    selected_tests = engine.filter_tests(
        all_tests,
        tier=args.tier,
        feature_filter=args.feature
    )

    # 3. Handle --list
    if args.list:
        list_tests(selected_tests)
        return 0

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

    # 6. Format report
    if args.format == "json":
        reporter = JsonReporter()
    elif args.format == "tap":
        reporter = TapReporter()
    elif args.format == "junit":
        reporter = JUnitReporter()
    else:
        reporter = ConsoleReporter(use_color=sys.stdout.isatty(), verbose=args.verbose)

    report_content = reporter.format(summary)

    # 7. Output report
    if args.output:
        os.makedirs(os.path.dirname(os.path.abspath(args.output)), exist_ok=True)
        with open(args.output, "w", encoding="utf-8") as f:
            f.write(report_content)
        if args.format == "console":
            print(f"Test report written to {args.output}")
    else:
        print(report_content)

    # Return exit code: 0 on all pass, 1 on any failure/error
    return 0 if summary.is_success else 1


if __name__ == "__main__":
    sys.exit(main())
