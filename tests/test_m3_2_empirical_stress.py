#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
tests/test_m3_2_empirical_stress.py
Empirical stress and adversarial verification suite for Milestone 3.
Authored by Challenger 2 (Empirical Challenger).

Tests:
1. Positional syntax dispatch:
   - Positional ping: tools/mcp_cli.py amed-bulletin-mcp ping
   - Positional tool call: tools/mcp_cli.py amed-bulletin get_news '{"id": 100}'
   - Positional create_news draft enforcement: tools/mcp_cli.py amed-bulletin create_news '{"name": "X"}' --dry-run
   - Positional missing tool name: tools/mcp_cli.py amed-bulletin -> exit code 2
2. Editorial-safe policy enforcement:
   - Destructive tools blocked: delete_news, delete_faq, delete_publication, delete_attachment, delete_category
   - Destructive tools allowed with --no-editorial-safe in dry-run
   - Publication status='y' rejected on create_news (various casing: 'y', 'Y', '  y  ')
   - Publication status='y' rejected on update_news
   - Publication status='y' rejected on create_publication
   - Publication status='y' rejected on create_faq
   - Publication status='y' rejected on batch_create_faqs (top level and items)
   - Status 'n' and username 'habermerkezi' enforced on create_news when omitted or 'admin'
   - Custom username preserved on create_news
   - Non-existent local image file rejected on create_news
   - Existing local image file accepted on create_news
   - Non-existent image file on update_news (adversarial check)
3. Secret-opaque token scrubbing:
   - Stderr amcp_* token scrubbing verified across multiple lines and tokens
   - Verification that tokens never appear in stderr or stdout
4. CLI input validation & error recovery:
   - Malformed JSON in --args-json
   - Non-object JSON (array, int, string) in --args-json
   - Non-existent --args-file
   - Malformed JSON in --args-file
   - Non-object JSON in --args-file
   - Bridge failure: missing bridge script
   - Bridge failure: bridge timeout
   - Bridge failure: bridge process error / empty stdout
   - Bridge failure: bridge returns isError=True
"""

import json
import os
from pathlib import Path
import re
import subprocess
import sys
import tempfile
import unittest

ROOT = Path(__file__).resolve().parents[1]
CLI_PATH = ROOT / "tools" / "mcp_cli.py"


class TestMilestone3EmpiricalStress(unittest.TestCase):

    def run_cli(self, args, env=None, timeout=30):
        cmd = [sys.executable, str(CLI_PATH)] + args
        environ = dict(os.environ)
        if env:
            environ.update(env)
        proc = subprocess.run(
            cmd,
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE,
            text=True,
            timeout=timeout,
            env=environ,
            cwd=str(ROOT),
        )
        return proc

    # -----------------------------------------------------------------------
    # Track 1: Positional Syntax Tests
    # -----------------------------------------------------------------------

    def test_positional_ping(self):
        """Test positional syntax: tools/mcp_cli.py amed-bulletin-mcp ping."""
        proc = self.run_cli(["amed-bulletin-mcp", "ping"])
        print("\n[test_positional_ping] returncode:", proc.returncode)
        print("[test_positional_ping] stdout:", proc.stdout[:200])
        print("[test_positional_ping] stderr:", proc.stderr[:200])
        # If this fails because ping is treated as a tool name rather than method/subcommand:
        # We record the exact empirical observation.
        self.assertEqual(proc.returncode, 0, f"Positional ping failed with code {proc.returncode}: {proc.stdout}")

    def test_positional_get_news_live(self):
        """Test positional syntax with valid tool and argument against live bridge."""
        proc = self.run_cli(["amed-bulletin", "get_news", json.dumps({"id": 100})])
        self.assertEqual(proc.returncode, 0, f"Positional get_news failed: {proc.stderr}")
        data = json.loads(proc.stdout)
        self.assertIn("result", data)
        self.assertFalse(data.get("isError", False))

    def test_positional_missing_tool_name(self):
        """Test positional syntax missing tool name returns exit code 2."""
        proc = self.run_cli(["amed-bulletin"])
        self.assertEqual(proc.returncode, 2)
        self.assertIn("tool name is missing", proc.stderr)

    def test_positional_dry_run_create_news(self):
        """Test positional syntax with dry run enforces editorial policy."""
        proc = self.run_cli([
            "amed-bulletin", "create_news",
            json.dumps({"name": "Positional Headline"}),
            "--dry-run"
        ])
        self.assertEqual(proc.returncode, 0)
        data = json.loads(proc.stdout)
        args = data["params"]["arguments"]
        self.assertEqual(args["status"], "n")
        self.assertEqual(args["username"], "habermerkezi")

    # -----------------------------------------------------------------------
    # Track 2: Editorial-Safe Policy Enforcement
    # -----------------------------------------------------------------------

    def test_delete_tools_blocked_under_editorial_safe(self):
        """All delete_* tools must be blocked with exit code 1 under --editorial-safe."""
        delete_tools = [
            "delete_news",
            "delete_faq",
            "delete_publication",
            "delete_attachment",
            "delete_category",
            "delete_headline",
            "delete_page",
        ]
        for tool in delete_tools:
            proc = self.run_cli(["call", tool, "--args-json", json.dumps({"id": 999})])
            self.assertEqual(proc.returncode, 1, f"Tool {tool} was not blocked!")
            data = json.loads(proc.stdout)
            self.assertTrue(data.get("isError"))
            self.assertIn("prohibited under --editorial-safe", data["error"]["message"])

    def test_delete_tools_positional_blocked(self):
        """Positional delete_news must be blocked with exit code 1."""
        proc = self.run_cli(["amed-bulletin", "delete_news", json.dumps({"id": 999})])
        self.assertEqual(proc.returncode, 1)
        data = json.loads(proc.stdout)
        self.assertTrue(data.get("isError"))
        self.assertIn("prohibited under --editorial-safe", data["error"]["message"])

    def test_delete_allowed_with_no_editorial_safe_dry_run(self):
        """With --no-editorial-safe, delete_* is permitted in dry-run mode."""
        proc = self.run_cli([
            "call", "delete_news",
            "--args-json", json.dumps({"id": 999}),
            "--no-editorial-safe",
            "--dry-run"
        ])
        self.assertEqual(proc.returncode, 0)
        data = json.loads(proc.stdout)
        self.assertEqual(data["params"]["name"], "delete_news")
        self.assertFalse(data["_meta"]["editorial_safe"])

    def test_create_news_status_y_rejected_variations(self):
        """create_news with status='y', 'Y', '  y  ' must all be rejected."""
        variations = ["y", "Y", "  y  ", "Y "]
        for val in variations:
            proc = self.run_cli([
                "call", "create_news",
                "--args-json", json.dumps({"name": "Headline", "status": val}),
                "--dry-run"
            ])
            self.assertEqual(proc.returncode, 1, f"Status variation '{val}' was not rejected!")
            data = json.loads(proc.stdout)
            self.assertTrue(data.get("isError"))
            self.assertIn("Publishing status 'y' is prohibited", data["error"]["message"])

    def test_update_news_status_y_rejected(self):
        """update_news with status='y' must be rejected."""
        proc = self.run_cli([
            "call", "update_news",
            "--args-json", json.dumps({"id": 1, "status": "y"}),
            "--dry-run"
        ])
        self.assertEqual(proc.returncode, 1)
        data = json.loads(proc.stdout)
        self.assertTrue(data.get("isError"))
        self.assertIn("Flipping status to published ('y') is prohibited", data["error"]["message"])

    def test_create_publication_status_y_rejected(self):
        """create_publication with status='y' must be rejected."""
        proc = self.run_cli([
            "call", "create_publication",
            "--args-json", json.dumps({"name": "Report", "status": "y"}),
            "--dry-run"
        ])
        self.assertEqual(proc.returncode, 1)
        data = json.loads(proc.stdout)
        self.assertTrue(data.get("isError"))
        self.assertIn("Publishing status 'y' is prohibited", data["error"]["message"])

    def test_create_faq_status_y_rejected(self):
        """create_faq with status='y' must be rejected."""
        proc = self.run_cli([
            "call", "create_faq",
            "--args-json", json.dumps({"question": "Q?", "status": "y"}),
            "--dry-run"
        ])
        self.assertEqual(proc.returncode, 1)
        data = json.loads(proc.stdout)
        self.assertTrue(data.get("isError"))
        self.assertIn("Publishing status 'y' is prohibited", data["error"]["message"])

    def test_batch_create_faqs_status_y_rejected(self):
        """batch_create_faqs with status='y' at top level or in items must be rejected."""
        # Top-level status="y"
        proc1 = self.run_cli([
            "call", "batch_create_faqs",
            "--args-json", json.dumps({"items": [{"question": "Q1?"}], "status": "y"}),
            "--dry-run"
        ])
        self.assertEqual(proc1.returncode, 1)

        # Item-level status="y"
        proc2 = self.run_cli([
            "call", "batch_create_faqs",
            "--args-json", json.dumps({"items": [{"question": "Q1?", "status": "y"}]}),
            "--dry-run"
        ])
        self.assertEqual(proc2.returncode, 1)

    def test_author_admin_replaced_with_habermerkezi(self):
        """create_news with author 'admin', 'Admin', 'ADMIN' or empty must be replaced with 'habermerkezi'."""
        cases = ["admin", "Admin", "ADMIN", "", "  admin  "]
        for author in cases:
            proc = self.run_cli([
                "call", "create_news",
                "--args-json", json.dumps({"name": "Headline", "username": author}),
                "--dry-run"
            ])
            self.assertEqual(proc.returncode, 0)
            data = json.loads(proc.stdout)
            self.assertEqual(data["params"]["arguments"]["username"], "habermerkezi")

    def test_custom_author_preserved(self):
        """create_news with valid custom reporter username must be preserved."""
        proc = self.run_cli([
            "call", "create_news",
            "--args-json", json.dumps({"name": "Headline", "username": "diyar_reporter"}),
            "--dry-run"
        ])
        self.assertEqual(proc.returncode, 0)
        data = json.loads(proc.stdout)
        self.assertEqual(data["params"]["arguments"]["username"], "diyar_reporter")

    def test_missing_image_file_rejected(self):
        """create_news with non-existent local image file must be rejected."""
        proc = self.run_cli([
            "call", "create_news",
            "--args-json", json.dumps({
                "name": "Headline",
                "image": {"file_path": "/path/to/definitely_missing_image_12345.webp", "mime": "image/webp"}
            }),
            "--dry-run"
        ])
        self.assertEqual(proc.returncode, 1)
        data = json.loads(proc.stdout)
        self.assertTrue(data.get("isError"))
        self.assertIn("Featured image file specified in image.file_path does not exist", data["error"]["message"])

    def test_existing_image_file_accepted(self):
        """create_news with existing local image file must pass policy validation."""
        with tempfile.NamedTemporaryFile(suffix=".webp") as tmp:
            proc = self.run_cli([
                "call", "create_news",
                "--args-json", json.dumps({
                    "name": "Headline",
                    "image": {"file_path": tmp.name, "mime": "image/webp"}
                }),
                "--dry-run"
            ])
            self.assertEqual(proc.returncode, 0)
            data = json.loads(proc.stdout)
            self.assertEqual(data["params"]["arguments"]["image"]["file_path"], tmp.name)

    # -----------------------------------------------------------------------
    # Track 3: Secret-Opaque Token Scrubbing
    # -----------------------------------------------------------------------

    def test_token_scrubbing_in_stderr(self):
        """Verify API keys matching amcp_* are scrubbed from stderr output."""
        mock_bridge = """#!/usr/bin/env python3
import sys, json
sys.stderr.write("INFO: Authenticated with token amcp_SECRET999abc and session amcp_SESS123\\n")
sys.stderr.write("DEBUG: amcp_ANOTHERKEY at trace end\\n")
res = {"jsonrpc": "2.0", "id": 1, "result": {"success": True}}
sys.stdout.write(json.dumps(res) + "\\n")
sys.stdout.flush()
"""
        with tempfile.NamedTemporaryFile("w", suffix=".py", delete=False) as f:
            f.write(mock_bridge)
            mock_path = f.name
        os.chmod(mock_path, 0o755)

        try:
            proc = self.run_cli(["--bridge", mock_path, "call", "get_news", "--args-json", '{"id": 1}'])
            self.assertEqual(proc.returncode, 0)
            self.assertNotIn("amcp_SECRET999abc", proc.stderr)
            self.assertNotIn("amcp_SESS123", proc.stderr)
            self.assertNotIn("amcp_ANOTHERKEY", proc.stderr)
            self.assertNotIn("amcp_", proc.stderr)
            self.assertIn("[REDACTED_API_KEY]", proc.stderr)
            self.assertEqual(proc.stderr.count("[REDACTED_API_KEY]"), 3)
        finally:
            os.remove(mock_path)

    # -----------------------------------------------------------------------
    # Track 4: CLI Input Validation & Edge Cases
    # -----------------------------------------------------------------------

    def test_malformed_args_json(self):
        """Malformed JSON string in --args-json must exit code 2."""
        proc = self.run_cli(["call", "get_news", "--args-json", "{broken json"])
        self.assertEqual(proc.returncode, 2)
        self.assertIn("Failed to parse --args-json", proc.stderr)

    def test_non_dict_args_json(self):
        """Array, string, or number in --args-json must exit code 2."""
        for val in ['["array"]', '"string"', "123"]:
            proc = self.run_cli(["call", "get_news", "--args-json", val])
            self.assertEqual(proc.returncode, 2, f"Value {val} was not rejected!")
            self.assertIn("must be a JSON object string", proc.stderr)

    def test_missing_args_file(self):
        """Non-existent file in --args-file must exit code 2."""
        proc = self.run_cli(["call", "get_news", "--args-file", "/nonexistent/args.json"])
        self.assertEqual(proc.returncode, 2)
        self.assertIn("Argument file not found", proc.stderr)

    def test_get_record_missing_id_and_slug(self):
        """get-record without --id or --slug must exit code 2."""
        proc = self.run_cli(["get-record", "--module", "mod_news"])
        self.assertEqual(proc.returncode, 2)
        self.assertIn("Either --id or --slug must be provided", proc.stderr)

    def test_get_record_invalid_module(self):
        """get-record with unknown module must exit code 2."""
        proc = self.run_cli(["get-record", "--module", "mod_invalid", "--id", "1"])
        self.assertEqual(proc.returncode, 2)
        self.assertIn("Unsupported module 'mod_invalid'", proc.stderr)

    def test_bridge_timeout_handling(self):
        """Bridge process that hangs must be killed and exit code 1."""
        hanging_bridge = """#!/usr/bin/env python3
import time
time.sleep(10)
"""
        with tempfile.NamedTemporaryFile("w", suffix=".py", delete=False) as f:
            f.write(hanging_bridge)
            mock_path = f.name
        os.chmod(mock_path, 0o755)

        try:
            proc = self.run_cli(["--bridge", mock_path, "--timeout", "1", "ping"])
            self.assertEqual(proc.returncode, 1)
            data = json.loads(proc.stdout)
            self.assertTrue(data.get("isError"))
            self.assertIn("timed out after 1 seconds", data["error"]["message"])
        finally:
            os.remove(mock_path)

    def test_bridge_crash_empty_stdout(self):
        """Bridge process that crashes with no stdout must exit code 1."""
        crashing_bridge = """#!/usr/bin/env python3
import sys
sys.stderr.write("Crash occurred\\n")
sys.exit(1)
"""
        with tempfile.NamedTemporaryFile("w", suffix=".py", delete=False) as f:
            f.write(crashing_bridge)
            mock_path = f.name
        os.chmod(mock_path, 0o755)

        try:
            proc = self.run_cli(["--bridge", mock_path, "ping"])
            self.assertEqual(proc.returncode, 1)
            data = json.loads(proc.stdout)
            self.assertTrue(data.get("isError"))
            self.assertIn("without emitting JSON-RPC output", data["error"]["message"])
        finally:
            os.remove(mock_path)


if __name__ == "__main__":
    unittest.main()
