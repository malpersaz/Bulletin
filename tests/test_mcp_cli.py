#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Unit test suite for tools/mcp_cli.py
Tests argument parsing, editorial safety policy, stdio bridge protocol,
mock bridge execution, and zero credential exposure.
"""

import json
import os
from pathlib import Path
import subprocess
import sys
import tempfile
import unittest

# Ensure workspace root is in sys.path
_ROOT = Path(__file__).resolve().parents[1]
if str(_ROOT) not in sys.path:
    sys.path.insert(0, str(_ROOT))

from tools import mcp_cli


class TestMcpCliPolicy(unittest.TestCase):
    def test_policy_blocks_delete_tools(self):
        for tool in ("delete_news", "delete_faq", "delete_publication", "delete_attachment"):
            with self.assertRaises(mcp_cli.EditorialPolicyViolation):
                mcp_cli.enforce_editorial_policy(tool, {"id": 1}, enabled=True)

    def test_policy_allows_delete_when_disabled(self):
        # When --no-editorial-safe is passed, policy check is bypassed
        res = mcp_cli.enforce_editorial_policy("delete_news", {"id": 1}, enabled=False)
        self.assertEqual(res, {"id": 1})

    def test_policy_blocks_create_news_status_published(self):
        with self.assertRaises(mcp_cli.EditorialPolicyViolation):
            mcp_cli.enforce_editorial_policy("create_news", {"name": "Test", "status": "y"})

    def test_policy_injects_create_news_draft_and_author(self):
        args = {"name": "Test", "content": "<p>Content</p>"}
        res = mcp_cli.enforce_editorial_policy("create_news", args)
        self.assertEqual(res["status"], "n")
        self.assertEqual(res["username"], "habermerkezi")

    def test_policy_replaces_admin_author_with_habermerkezi(self):
        args = {"name": "Test", "username": "admin"}
        res = mcp_cli.enforce_editorial_policy("create_news", args)
        self.assertEqual(res["username"], "habermerkezi")

    def test_policy_blocks_update_news_status_published(self):
        with self.assertRaises(mcp_cli.EditorialPolicyViolation):
            mcp_cli.enforce_editorial_policy("update_news", {"id": 1, "status": "y"})

    def test_policy_blocks_create_faq_status_published(self):
        with self.assertRaises(mcp_cli.EditorialPolicyViolation):
            mcp_cli.enforce_editorial_policy("create_faq", {"question": "Q?", "status": "y"})

    def test_policy_blocks_batch_faqs_published_items(self):
        with self.assertRaises(mcp_cli.EditorialPolicyViolation):
            mcp_cli.enforce_editorial_policy(
                "batch_create_faqs",
                {"items": [{"question": "Q1?", "status": "y"}]}
            )

    def test_policy_normalizes_batch_faqs_draft_status(self):
        args = {
            "items": [
                {"question": "Q1?", "answer": "A1"},
                {"question": "Q2?", "answer": "A2", "status": "n"}
            ]
        }
        res = mcp_cli.enforce_editorial_policy("batch_create_faqs", args)
        self.assertEqual(res["status"], "n")
        self.assertEqual(res["items"][0]["status"], "n")
        self.assertEqual(res["items"][1]["status"], "n")

    def test_policy_validates_image_file_path(self):
        # Non-existent file path must raise policy violation
        with self.assertRaises(mcp_cli.EditorialPolicyViolation):
            mcp_cli.enforce_editorial_policy(
                "create_news",
                {"name": "Test", "image": {"file_path": "/path/to/nonexistent/image.webp"}}
            )

        # Existing file path should pass
        with tempfile.NamedTemporaryFile() as tmp:
            res = mcp_cli.enforce_editorial_policy(
                "create_news",
                {"name": "Test", "image": {"file_path": tmp.name}}
            )
            self.assertEqual(res["image"]["file_path"], tmp.name)


class TestMcpCliBridgeAndDispatch(unittest.TestCase):
    def test_resolve_bridge_path_env_override(self):
        with tempfile.NamedTemporaryFile() as tmp:
            os.environ["MCP_CLI_BRIDGE"] = tmp.name
            try:
                resolved = mcp_cli.resolve_bridge_path()
                self.assertEqual(resolved, Path(tmp.name).resolve())
            finally:
                del os.environ["MCP_CLI_BRIDGE"]

    def test_resolve_bridge_path_not_found(self):
        with self.assertRaises(FileNotFoundError):
            mcp_cli.resolve_bridge_path(override_path="/non/existent/bridge.py")

    def test_extract_environment_name(self):
        payload = {
            "result": {
                "_meta": {"environment": {"name": "bulletin_prod", "host": "https://example.com"}}
            }
        }
        self.assertEqual(mcp_cli.extract_environment_name(payload), "bulletin_prod")

        payload_none = {"result": {}}
        self.assertEqual(mcp_cli.extract_environment_name(payload_none), "unknown")

    def test_dry_run_create_news(self):
        code = mcp_cli.parse_and_dispatch([
            "call", "create_news",
            "--args-json", json.dumps({"name": "Headline", "content": "<p>Content</p>"}),
            "--dry-run"
        ])
        self.assertEqual(code, 0)

    def test_dry_run_positional_syntax(self):
        code = mcp_cli.parse_and_dispatch([
            "amed-bulletin", "get_news",
            json.dumps({"id": 128}),
            "--dry-run"
        ])
        self.assertEqual(code, 0)

    def test_dry_run_get_record(self):
        code = mcp_cli.parse_and_dispatch([
            "get-record", "--module", "mod_news", "--id", "128", "--dry-run"
        ])
        self.assertEqual(code, 0)

    def test_mock_bridge_execution_and_scrubbing(self):
        # Create a mock bridge script that returns JSON-RPC and logs an amcp_ token on stderr
        mock_script_content = """#!/usr/bin/env python3
import sys, json
line = sys.stdin.readline()
sys.stderr.write("Connected using secret amcp_secret1234567890abcdef and token amcp_testtoken99\\n")
res = {
    "jsonrpc": "2.0",
    "id": 1,
    "result": {
        "item": {"id": 42, "name": "Mock News"},
        "_meta": {"environment": {"name": "mock_test_env"}}
    }
}
sys.stdout.write(json.dumps(res) + "\\n")
sys.stdout.flush()
"""
        with tempfile.NamedTemporaryFile("w", suffix=".py", delete=False) as tmp:
            tmp.write(mock_script_content)
            tmp_path = tmp.name

        try:
            os.chmod(tmp_path, 0o755)
            # Execute RPC with mock bridge
            code, response = mcp_cli.execute_rpc(
                bridge_path=Path(tmp_path),
                method="tools/call",
                params={"name": "get_news", "arguments": {"id": 42}},
            )
            self.assertEqual(code, 0)
            self.assertEqual(response["result"]["item"]["id"], 42)
            self.assertEqual(mcp_cli.extract_environment_name(response), "mock_test_env")
        finally:
            os.remove(tmp_path)


if __name__ == "__main__":
    unittest.main()
