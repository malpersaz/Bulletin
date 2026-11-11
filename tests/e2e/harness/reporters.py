"""
Test reporters for E2E testing framework.
Provides Console (ANSI color), TAP v13, JUnit XML, and JSON output formatters.
"""

from __future__ import annotations

import datetime
import json
import xml.etree.ElementTree as ET
from dataclasses import dataclass, field
from typing import Any, Dict, List, Optional


@dataclass
class TestCaseResult:
    """Represents the execution outcome of an individual test case."""
    name: str
    module: str
    tier: int
    features: List[int] = field(default_factory=list)
    status: str = "passed"  # "passed", "failed", "error", "skipped"
    duration_sec: float = 0.0
    error_message: Optional[str] = None
    error_traceback: Optional[str] = None

    @property
    def id(self) -> str:
        f_str = ",".join(str(f) for f in self.features) if self.features else "?"
        return f"[Tier {self.tier}][F{f_str}] {self.module}::{self.name}"


@dataclass
class TestRunSummary:
    """Aggregated metrics across the entire test run."""
    results: List[TestCaseResult] = field(default_factory=list)
    total: int = 0
    passed: int = 0
    failed: int = 0
    errors: int = 0
    skipped: int = 0
    duration_sec: float = 0.0
    start_time: str = field(default_factory=lambda: datetime.datetime.now(datetime.timezone.utc).isoformat())

    def compute_metrics(self) -> None:
        """Recalculate summary metrics from results list."""
        self.total = len(self.results)
        self.passed = sum(1 for r in self.results if r.status == "passed")
        self.failed = sum(1 for r in self.results if r.status == "failed")
        self.errors = sum(1 for r in self.results if r.status == "error")
        self.skipped = sum(1 for r in self.results if r.status == "skipped")
        self.duration_sec = sum(r.duration_sec for r in self.results)

    @property
    def is_success(self) -> bool:
        return self.failed == 0 and self.errors == 0


class BaseReporter:
    """Abstract base class for all test report formatters."""

    def format(self, summary: TestRunSummary) -> str:
        raise NotImplementedError

    def write_to_file(self, summary: TestRunSummary, output_path: str) -> None:
        content = self.format(summary)
        with open(output_path, "w", encoding="utf-8") as f:
            f.write(content)


class ConsoleReporter(BaseReporter):
    """Rich colored terminal reporter with summary table and error traces."""

    RESET = "\033[0m"
    BOLD = "\033[1m"
    RED = "\033[91m"
    GREEN = "\033[92m"
    YELLOW = "\033[93m"
    BLUE = "\033[94m"
    MAGENTA = "\033[95m"
    CYAN = "\033[96m"

    def __init__(self, use_color: bool = True, verbose: bool = False):
        self.use_color = use_color
        self.verbose = verbose

    def _c(self, text: str, color: str) -> str:
        return f"{color}{text}{self.RESET}" if self.use_color else text

    def format(self, summary: TestRunSummary) -> str:
        summary.compute_metrics()
        lines = []

        header = f"=== Amed Framework Bulletin E2E Test Suite Run ({summary.start_time}) ==="
        lines.append(self._c(header, self.BOLD + self.CYAN))
        lines.append("")

        # Tier breakdown
        tiers: Dict[int, Dict[str, int]] = {1: {}, 2: {}, 3: {}, 4: {}}
        for r in summary.results:
            t = r.tier if r.tier in tiers else 1
            tiers[t][r.status] = tiers[t].get(r.status, 0) + 1

        lines.append(self._c("--- Tier Summary ---", self.BOLD))
        for t in sorted(tiers.keys()):
            counts = tiers[t]
            t_total = sum(counts.values())
            t_pass = counts.get("passed", 0)
            t_fail = counts.get("failed", 0) + counts.get("error", 0)
            status_color = self.GREEN if t_fail == 0 and t_total > 0 else (self.RED if t_fail > 0 else self.YELLOW)
            line = f"  Tier {t}: Total={t_total:3d} | Passed={t_pass:3d} | Failed={t_fail:3d} | Skipped={counts.get('skipped', 0):3d}"
            lines.append(self._c(line, status_color))

        lines.append("")

        # Failures and errors
        failures = [r for r in summary.results if r.status in ("failed", "error")]
        if failures:
            lines.append(self._c("--- Failures & Errors ---", self.BOLD + self.RED))
            for idx, f in enumerate(failures, 1):
                lines.append(self._c(f"[{idx}] {f.id} ({f.status.upper()})", self.RED + self.BOLD))
                if f.error_message:
                    lines.append(f"    Message: {f.error_message}")
                if f.error_traceback and self.verbose:
                    lines.append("    Traceback:")
                    for tb_line in f.error_traceback.splitlines():
                        lines.append(f"      {tb_line}")
                lines.append("")

        # Final outcome banner
        lines.append(self._c("=" * 60, self.BOLD))
        if summary.is_success:
            res_str = f"OVERALL STATUS: PASSED (Total: {summary.total}, Passed: {summary.passed}, Duration: {summary.duration_sec:.2f}s)"
            lines.append(self._c(res_str, self.BOLD + self.GREEN))
        else:
            res_str = f"OVERALL STATUS: FAILED (Total: {summary.total}, Failed: {summary.failed}, Errors: {summary.errors}, Passed: {summary.passed}, Duration: {summary.duration_sec:.2f}s)"
            lines.append(self._c(res_str, self.BOLD + self.RED))
        lines.append(self._c("=" * 60, self.BOLD))

        return "\n".join(lines)


class TapReporter(BaseReporter):
    """TAP (Test Anything Protocol) version 13 reporter."""

    def format(self, summary: TestRunSummary) -> str:
        summary.compute_metrics()
        lines = [
            "TAP version 13",
            f"1..{summary.total}",
        ]

        for i, r in enumerate(summary.results, 1):
            f_str = ",".join(str(f) for f in r.features) if r.features else "none"
            desc = f"{r.module}::{r.name} [Tier {r.tier}] [Feature {f_str}]"
            if r.status == "passed":
                lines.append(f"ok {i} - {desc} (time={r.duration_sec:.3f}s)")
            elif r.status == "skipped":
                lines.append(f"ok {i} - {desc} # SKIP")
            else:
                lines.append(f"not ok {i} - {desc} (time={r.duration_sec:.3f}s)")
                lines.append("  ---")
                lines.append(f"  message: {json.dumps(r.error_message or 'Failure')}")
                lines.append(f"  severity: {r.status}")
                if r.error_traceback:
                    lines.append("  stack: |")
                    for t_line in r.error_traceback.splitlines():
                        lines.append(f"    {t_line}")
                lines.append("  ...")

        return "\n".join(lines)


class JUnitReporter(BaseReporter):
    """JUnit XML format reporter for CI/CD integration."""

    def format(self, summary: TestRunSummary) -> str:
        summary.compute_metrics()
        testsuites = ET.Element(
            "testsuites",
            name="AF_Bulletin_E2E",
            tests=str(summary.total),
            failures=str(summary.failed),
            errors=str(summary.errors),
            skipped=str(summary.skipped),
            time=f"{summary.duration_sec:.3f}"
        )

        # Group by tier
        tier_suites: Dict[int, List[TestCaseResult]] = {}
        for r in summary.results:
            tier_suites.setdefault(r.tier, []).append(r)

        for tier, tests in sorted(tier_suites.items()):
            t_total = len(tests)
            t_failed = sum(1 for t in tests if t.status == "failed")
            t_errors = sum(1 for t in tests if t.status == "error")
            t_skipped = sum(1 for t in tests if t.status == "skipped")
            t_time = sum(t.duration_sec for t in tests)

            suite = ET.SubElement(
                testsuites,
                "testsuite",
                name=f"Tier_{tier}",
                tests=str(t_total),
                failures=str(t_failed),
                errors=str(t_errors),
                skipped=str(t_skipped),
                time=f"{t_time:.3f}"
            )

            for t in tests:
                f_str = ",".join(str(f) for f in t.features) if t.features else "none"
                tc = ET.SubElement(
                    suite,
                    "testcase",
                    classname=t.module,
                    name=f"{t.name} (F:{f_str})",
                    time=f"{t.duration_sec:.3f}"
                )
                if t.status == "failed":
                    fail = ET.SubElement(tc, "failure", message=t.error_message or "Assertion Failed")
                    fail.text = t.error_traceback or ""
                elif t.status == "error":
                    err = ET.SubElement(tc, "error", message=t.error_message or "Runtime Error")
                    err.text = t.error_traceback or ""
                elif t.status == "skipped":
                    ET.SubElement(tc, "skipped", message=t.error_message or "Skipped")

        return ET.tostring(testsuites, encoding="utf-8", xml_declaration=True).decode("utf-8")


class JsonReporter(BaseReporter):
    """Machine-readable JSON reporter for programmatic aggregation."""

    def format(self, summary: TestRunSummary) -> str:
        summary.compute_metrics()
        data = {
            "version": "1.0.63",
            "framework": "Amed Framework Bulletin E2E",
            "timestamp": summary.start_time,
            "summary": {
                "total": summary.total,
                "passed": summary.passed,
                "failed": summary.failed,
                "errors": summary.errors,
                "skipped": summary.skipped,
                "duration_sec": round(summary.duration_sec, 4),
                "is_success": summary.is_success,
            },
            "tests": [
                {
                    "name": r.name,
                    "module": r.module,
                    "tier": r.tier,
                    "features": r.features,
                    "status": r.status,
                    "duration_sec": round(r.duration_sec, 4),
                    "error_message": r.error_message,
                    "error_traceback": r.error_traceback,
                }
                for r in summary.results
            ]
        }
        return json.dumps(data, indent=2)
