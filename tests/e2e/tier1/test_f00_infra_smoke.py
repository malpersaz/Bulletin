"""
Infrastructure smoke test for Milestone E2E-M1.
Validates fixture catalog, TEST_INFRA.md presence, and harness execution.
"""

import json
import os
import unittest
from PIL import Image

from harness.base import E2ETestCase, feature, tier
from harness.php_bridge import PROJECT_ROOT


@tier(1)
@feature(34)
class TestE2EInfraSmoke(E2ETestCase):
    """Smoke verification of E2E testing infrastructure."""

    def test_test_infra_spec_exists(self):
        """Verify TEST_INFRA.md exists at project root with complete feature matrix."""
        infra_path = os.path.join(self.project_root, "TEST_INFRA.md")
        self.assertTrue(os.path.exists(infra_path), "TEST_INFRA.md must exist at project root")

        with open(infra_path, "r", encoding="utf-8") as f:
            content = f.read()

        self.assertIn("Comprehensive E2E Testing Infrastructure", content)
        self.assertIn("Complete 36-Feature Inventory", content)
        self.assertIn("Test Runner Specification", content)
        self.assertIn("run_e2e_tests.py", content)

    def test_fixtures_catalog(self):
        """Verify all initial fixtures exist and are valid."""
        # 1. Editorial fixtures
        draft_path = self.fixture_path("editorial", "draft_today_diyarbakir_park.html")
        self.assertTrue(os.path.exists(draft_path), f"Missing fixture {draft_path}")
        with open(draft_path, "r", encoding="utf-8") as f:
            draft_html = f.read()
        self.assertNoInlineStylesOrScripts(draft_html)
        self.assertHtmlTagPresent(draft_html, "h2")

        dossier_path = self.fixture_path("editorial", "dossier_today_rudaw_park.json")
        self.assertTrue(os.path.exists(dossier_path))
        with open(dossier_path, "r", encoding="utf-8") as f:
            dossier_data = json.load(f)
        self.assertJsonEnvelope(dossier_data, required_keys=["topic", "date", "sources"])

        # 2. Image fixtures
        hero_img_path = self.fixture_path("images", "rudaw_true_hero_1920x1080.jpg")
        self.assertTrue(os.path.exists(hero_img_path))
        with Image.open(hero_img_path) as im:
            self.assertEqual(im.size, (1920, 1080))

        master_webp_path = self.fixture_path("images", "sample_valid_master_1600x900.webp")
        self.assertTrue(os.path.exists(master_webp_path))
        with Image.open(master_webp_path) as im:
            self.assertEqual(im.size, (1600, 900))

        # 3. Feed fixtures
        feed_path = self.fixture_path("feeds", "rss_diyarbakir_today.xml")
        self.assertTrue(os.path.exists(feed_path))
        with open(feed_path, "r", encoding="utf-8") as f:
            feed_xml = f.read()
        self.assertIn("<rss version=\"2.0\">", feed_xml)

        # 4. MCP payloads
        mcp_req_path = self.fixture_path("mcp", "req_create_news_valid.json")
        self.assertTrue(os.path.exists(mcp_req_path))
        with open(mcp_req_path, "r", encoding="utf-8") as f:
            mcp_data = json.load(f)
        self.assertEqual(mcp_data.get("jsonrpc"), "2.0")
        self.assertEqual(mcp_data.get("method"), "tools/call")

    def test_mock_http_integration(self):
        """Verify embedded mock HTTP server routes and sandboxing."""
        import urllib.request

        server = self.mock_http
        server.add_route("/api/ping", {"status": "ok", "pong": True})

        url = server.get_url("/api/ping")
        resp = urllib.request.urlopen(url)
        self.assertEqual(resp.status, 200)
        data = json.loads(resp.read().decode())
        self.assertEqual(data["pong"], True)

    def test_php_core_syntax_lint(self):
        """Verify core MCP PHP files pass syntax check."""
        core_files = [
            "lib/McpServer.class.php",
            "lib/McpCrudToolsBase.class.php",
            "lib/McpNewsTools.class.php",
            "lib/McpFaqTools.class.php",
        ]
        for rel_path in core_files:
            full_path = os.path.join(self.project_root, rel_path)
            if os.path.exists(full_path):
                lint_res = self.php.lint_file(full_path)
                self.assertExitCode(lint_res, 0, f"PHP syntax lint failed on {rel_path}")


if __name__ == "__main__":
    unittest.main()
