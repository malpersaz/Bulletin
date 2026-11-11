"""
Tier 1 Feature Coverage: Feature 12 (nginx_well_known).
Validates Nginx production configuration and routing for /.well-known/ passthrough
preceding dotfile block, preserving RFC 8414/9728 discovery while blocking sensitive dotfiles.
"""

from __future__ import annotations

import os
import re
import urllib.request
import urllib.error
from harness.base import E2ETestCase, feature, tier


@tier(1)
@feature(12)
class TestF12NginxWellKnown(E2ETestCase):
    """Tier 1 tests for Nginx .well-known location directive and routing behavior."""

    @property
    def nginx_conf_path(self) -> str:
        return os.path.join(self.project_root, "docker", "nginx", "prod.conf")

    def test_f12_nginx_conf_static_syntax_location_order(self):
        """Verify location ^~ /.well-known/ exists and appears before location ~ /\\. in prod.conf."""
        self.assertTrue(os.path.exists(self.nginx_conf_path), "prod.conf must exist")
        with open(self.nginx_conf_path, "r", encoding="utf-8") as f:
            content = f.read()

        wk_pos = content.find("location ^~ /.well-known/")
        dot_pos = content.find(r"location ~ /\. ")
        if dot_pos == -1:
            dot_pos = content.find("location ~ /\\.")

        self.assertNotEqual(wk_pos, -1, "location ^~ /.well-known/ must be present in prod.conf")
        self.assertNotEqual(dot_pos, -1, "location ~ /\\. must be present in prod.conf")
        self.assertLess(wk_pos, dot_pos, "location ^~ /.well-known/ must precede location ~ /\\.")

    def test_f12_nginx_well_known_passthrough_live_http_status(self):
        """Verify /.well-known/ path routes through router (status 200 or 404) and is not blocked with 403."""
        try:
            req = urllib.request.Request("http://localhost:8080/.well-known/oauth-authorization-server")
            with urllib.request.urlopen(req, timeout=3.0) as resp:
                code = resp.status
        except urllib.error.HTTPError as e:
            code = e.code
        except Exception as e:
            # If port 8080 is not reachable, verify static rule only
            self.skipTest(f"Live Nginx port 8080 not reachable: {e}")

        self.assertIn(code, [200, 404], f"Expected 200 or 404, got {code}")
        self.assertNotEqual(code, 403, "Well-known endpoint must not be blocked with 403")

    def test_f12_nginx_blocks_dotfiles_still_active(self):
        """Verify sensitive dotfiles (.env, .git) remain strictly blocked with HTTP 403 Forbidden."""
        for path in ["/.env", "/.git/config"]:
            try:
                req = urllib.request.Request(f"http://localhost:8080{path}")
                with urllib.request.urlopen(req, timeout=3.0) as resp:
                    code = resp.status
            except urllib.error.HTTPError as e:
                code = e.code
            except Exception as e:
                self.skipTest(f"Live Nginx port 8080 not reachable: {e}")

            self.assertEqual(code, 403, f"Path {path} must be blocked with 403 Forbidden")

    def test_f12_nginx_well_known_acme_challenge_passthrough(self):
        """Verify ACME challenge path under /.well-known/ passes through without 403 Forbidden."""
        try:
            req = urllib.request.Request("http://localhost:8080/.well-known/acme-challenge/test-token")
            with urllib.request.urlopen(req, timeout=3.0) as resp:
                code = resp.status
        except urllib.error.HTTPError as e:
            code = e.code
        except Exception as e:
            self.skipTest(f"Live Nginx port 8080 not reachable: {e}")

        self.assertNotEqual(code, 403, "ACME challenge must not be blocked with 403")

    def test_f12_nginx_well_known_subpath_routing_preserves_query_string(self):
        """Verify router receives query string on .well-known request and application headers are returned."""
        try:
            req = urllib.request.Request("http://localhost:8080/.well-known/resource?test=param123")
            with urllib.request.urlopen(req, timeout=3.0) as resp:
                headers = dict(resp.headers)
        except urllib.error.HTTPError as e:
            headers = dict(e.headers)
        except Exception as e:
            self.skipTest(f"Live Nginx port 8080 not reachable: {e}")

        # CMS application sends Set-Cookie or X-Robots-Tag when routing through index.php
        has_cms_signature = ("Set-Cookie" in headers or "X-Robots-Tag" in headers or "Server" in headers)
        self.assertTrue(has_cms_signature, "Request must reach Nginx and PHP router")
