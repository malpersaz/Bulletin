"""Feature 29: tools/mcp_cli.py Swarm CLI Wrapper.

Validates the secret-opaque stdio bridge CLI wrapper for amed-bulletin MCP:
- Read tool calls succeed, return JSON-RPC envelopes and log environment to stderr
- --editorial-safe policy blocks destructive tools (delete_*)
- --editorial-safe policy blocks direct publishing (status='y')
- Valid draft staging (status='n') passes validation and injects username='habermerkezi'
- get-record subcommand fetches record JSON and writes to disk
"""

from __future__ import annotations

import json
import os
from pathlib import Path

from harness.base import E2ETestCase, feature, tier


@tier(1)
@feature(29)
class TestF29McpCliBridge(E2ETestCase):
    """Tier 1 tests for tools/mcp_cli.py stdio bridge wrapper and safety policies."""

    def setUp(self) -> None:
        super().setUp()
        self.script_path = "tools/mcp_cli.py"

    def test_f29_mcp_cli_read_tool_call_success(self) -> None:
        """Invoking a read tool via mcp_cli.py succeeds with exit code 0 and returns JSON."""
        res = self.run_python([
            self.script_path,
            "call", "list_news_categories",
            "--args-json", json.dumps({"lang": "tr"}),
        ])
        self.assertExitCode(res, 0)
        # Parse stdout JSON
        # Filter stdout lines in case stdout has logger lines before {
        lines = [line for line in res.stdout.splitlines() if line.strip().startswith("{") or not line.strip().startswith("[")]
        stdout_json = "\n".join(lines).strip()
        start = stdout_json.find("{")
        end = stdout_json.rfind("}") + 1
        data = json.loads(stdout_json[start:end])

        self.assertIn("result", data)
        self.assertFalse(data.get("isError", False))

    def test_f29_editorial_safe_blocks_delete_mutation(self) -> None:
        """Destructive delete_* tools are blocked under default --editorial-safe policy."""
        res = self.run_python([
            self.script_path,
            "call", "delete_news",
            "--args-json", json.dumps({"id": 9999}),
        ])
        self.assertExitCode(res, 1)
        # Check stderr or stdout for rejection message
        combined = (res.stdout + " " + res.stderr).lower()
        self.assertTrue(
            "prohibited" in combined or "blocked" in combined or "destructive" in combined,
            f"Expected rejection message, got: {combined}",
        )

    def test_f29_editorial_safe_blocks_published_status(self) -> None:
        """Direct publishing with status='y' is prohibited under --editorial-safe."""
        res = self.run_python([
            self.script_path,
            "call", "create_news",
            "--args-json", json.dumps({"name": "Test News", "status": "y"}),
        ])
        self.assertExitCode(res, 1)
        combined = (res.stdout + " " + res.stderr).lower()
        self.assertTrue(
            "status 'y'" in combined or "draft" in combined or "prohibited" in combined,
            f"Expected status rejection, got: {combined}",
        )

    def test_f29_editorial_safe_allows_draft_staging(self) -> None:
        """Draft staging with status='n' passes safety validation and injects habermerkezi."""
        res = self.run_python([
            self.script_path,
            "call", "create_news",
            "--dry-run",
            "--args-json", json.dumps({"name": "Draft Headline", "status": "n"}),
        ])
        self.assertExitCode(res, 0)
        start = res.stdout.find("{")
        end = res.stdout.rfind("}") + 1
        data = json.loads(res.stdout[start:end])
        args = data.get("params", {}).get("arguments", {})
        self.assertEqual(args.get("status"), "n")
        self.assertEqual(args.get("username"), "habermerkezi")

    def test_f29_get_record_subcommand(self) -> None:
        """get-record retrieves CMS record and saves JSON-RPC envelope to disk."""
        out_file = self.sandbox.path("news_record_1.json")
        res = self.run_python([
            self.script_path,
            "get-record",
            "--module", "mod_news",
            "--id", "1",
            "--out", out_file,
        ])
        self.assertExitCode(res, 0)
        self.assertTrue(os.path.exists(out_file))

        with open(out_file, encoding="utf-8") as f:
            data = json.load(f)
        self.assertIn("result", data)
        self.assertFalse(data.get("isError", False))
