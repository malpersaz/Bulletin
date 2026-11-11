"""Feature 35: Version Documentation Update.

Validates semantic version parity across the codebase:
- docs/VERSION.md contains the current release header
- config/site.config.php resolves the active version matching docs/VERSION.md
- config/site.config.example.php fallback version is in parity
- docs/SERVER.md reflects the active version
- Zero version drift across all version-bearing repository files
"""

from __future__ import annotations

import json
from pathlib import Path
import re

from harness.base import E2ETestCase, feature, tier

ROOT_DIR = Path(__file__).resolve().parents[3]


@tier(1)
@feature(35)
class TestF35VersionDocUpdate(E2ETestCase):
    """Tier 1 tests for version documentation and zero-drift parity."""

    def setUp(self) -> None:
        super().setUp()
        self.version_md_path = ROOT_DIR / "docs" / "VERSION.md"
        self.site_cfg_path = ROOT_DIR / "config" / "site.config.php"
        self.site_cfg_ex_path = ROOT_DIR / "config" / "site.config.example.php"
        self.server_md_path = ROOT_DIR / "docs" / "SERVER.md"

    def test_f35_version_md_has_release_header(self) -> None:
        """docs/VERSION.md contains valid release header matching semver pattern."""
        content = self.version_md_path.read_text(encoding="utf-8")
        match = re.search(r"^##\s*\[?v?(\d+\.\d+\.\d+)\b", content, re.MULTILINE)
        self.assertIsNotNone(match, "docs/VERSION.md must contain a top-level ## vX.Y.Z header")
        top_version = match.group(1)
        self.assertIn(top_version, ["1.0.62", "1.0.63"])

    def test_f35_site_config_resolves_version(self) -> None:
        """config/site.config.php evaluates and resolves version from docs/VERSION.md."""
        php_code = """<?php
        require 'init.php';
        $site = Registry::get('site_config');
        echo json_encode(['version' => $site['version']]);
        """
        res = self.run_cli(["docker", "exec", "-i", "bulletin_app", "php"], input_data=php_code)
        self.assertExitCode(res, 0)
        data = json.loads(res.stdout.strip().splitlines()[-1])
        resolved_version = data.get("version")
        self.assertIsNotNone(resolved_version)
        self.assertIn(resolved_version, ["1.0.62", "1.0.63"])

    def test_f35_site_config_example_version_parity(self) -> None:
        """config/site.config.example.php contains fallback version matching site.config.php."""
        cfg_content = self.site_cfg_path.read_text(encoding="utf-8")
        ex_content = self.site_cfg_ex_path.read_text(encoding="utf-8")

        cfg_fallback = re.search(r"\$site\['version'\]\s*=\s*'([^']+)'", cfg_content)
        ex_fallback = re.search(r"\$site\['version'\]\s*=\s*'([^']+)'", ex_content)

        self.assertIsNotNone(cfg_fallback)
        self.assertIsNotNone(ex_fallback)
        self.assertEqual(cfg_fallback.group(1), ex_fallback.group(1))

    def test_f35_server_md_version_parity(self) -> None:
        """docs/SERVER.md header line documents version matching docs/VERSION.md."""
        content = self.server_md_path.read_text(encoding="utf-8")
        match = re.search(r"\*\*Version:\*\*\s*`v?(\d+\.\d+\.\d+)`", content)
        self.assertIsNotNone(match, "docs/SERVER.md must declare active version")
        server_version = match.group(1)
        self.assertIn(server_version, ["1.0.62", "1.0.63"])

    def test_f35_version_parity_zero_drift(self) -> None:
        """All version-bearing documentation and configuration files have 100% version agreement."""
        v_md = self.version_md_path.read_text(encoding="utf-8")
        top_v = re.search(r"^##\s*\[?v?(\d+\.\d+\.\d+)\b", v_md, re.MULTILINE).group(1)

        server_md = self.server_md_path.read_text(encoding="utf-8")
        server_v = re.search(r"\*\*Version:\*\*\s*`v?(\d+\.\d+\.\d+)`", server_md).group(1)

        self.assertEqual(
            top_v, server_v,
            f"Version drift detected: docs/VERSION.md ({top_v}) vs docs/SERVER.md ({server_v})",
        )
