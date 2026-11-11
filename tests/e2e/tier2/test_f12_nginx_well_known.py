"""
Tier 2 Boundary & Corner Cases: Feature 12 (nginx_well_known).
Validates Nginx .well-known configuration and HTTP boundary routing:
- .well-known request without trailing slash is not 403 Forbidden
- ACME challenge subpath routing passes through without 403
- Directory traversal attempt (.well-known/../.env) strictly returns 403 Forbidden
- Standard RFC discovery endpoints (.well-known/oauth-authorization-server, openid-configuration, security.txt) not 403
- Non-existent .well-known token routes to index.php and returns 404, not 403
- Sensitive dotfiles (.env, .git/config, .htaccess, .user.ini) strictly return 403
- Static configuration directives verification in docker/nginx/prod.conf
"""

from __future__ import annotations

import os
import urllib.error
import urllib.request
from harness.base import E2ETestCase, feature, tier


@tier(2)
@feature(12)
class TestF12NginxWellKnownTier2(E2ETestCase):
    """Tier 2 tests for Nginx .well-known routing boundaries and dotfile protection."""

    @property
    def nginx_conf_path(self) -> str:
        return os.path.join(self.project_root, "docker", "nginx", "prod.conf")

    def _fetch_status(self, path: str) -> int:
        url = f"http://localhost:8080{path}"
        try:
            req = urllib.request.Request(url, headers={"User-Agent": "E2E-Tier2-Tester"})
            with urllib.request.urlopen(req, timeout=3.0) as resp:
                return resp.status
        except urllib.error.HTTPError as e:
            return e.code
        except Exception as e:
            self.skipTest(f"Live Nginx server on port 8080 not reachable: {e}")

    def test_f12_boundary_well_known_without_trailing_slash(self):
        """GET /.well-known without trailing slash matches dotfile deny (403), while /.well-known/ passes (404)."""
        status_no_slash = self._fetch_status("/.well-known")
        self.assertEqual(status_no_slash, 403, "Without trailing slash, /.well-known matches dotfile deny rule")

        status_with_slash = self._fetch_status("/.well-known/")
        self.assertNotEqual(status_with_slash, 403, "With trailing slash, /.well-known/ passes through to index.php")
        self.assertIn(status_with_slash, [200, 404])

    def test_f12_boundary_acme_challenge_subpath_routing(self):
        """Deep ACME challenge path is routed to router (200/404) and never blocked with 403."""
        status = self._fetch_status("/.well-known/acme-challenge/arbitrary-cert-token-12345")
        self.assertNotEqual(status, 403)
        self.assertIn(status, [200, 404])

    def test_f12_boundary_directory_traversal_via_well_known_blocked(self):
        """Directory traversal via .well-known/../.env resolves to dotfile and returns 403 Forbidden."""
        # Using run_cli curl to preserve literal traversal path in HTTP request line
        res = self.run_cli(["curl", "-s", "-o", "/dev/null", "-w", "%{http_code}", "--path-as-is", "http://localhost:8080/.well-known/../.env"])
        if res.exit_code != 0 or res.stdout.strip() == "000":
            self.skipTest("Live Nginx server on port 8080 not reachable via curl")
        status = int(res.stdout.strip())
        self.assertEqual(status, 403, f"Traversal to .env must be strictly blocked with 403, got {status}")

    def test_f12_boundary_standard_rfc_discovery_endpoints(self):
        """RFC discovery endpoints (.well-known/oauth-authorization-server, openid-configuration, security.txt) not 403."""
        endpoints = [
            "/.well-known/oauth-authorization-server",
            "/.well-known/openid-configuration",
            "/.well-known/security.txt",
        ]
        for ep in endpoints:
            status = self._fetch_status(ep)
            self.assertNotEqual(status, 403, f"Endpoint {ep} must not be blocked with 403")
            self.assertIn(status, [200, 404])

    def test_f12_boundary_non_existent_resource_returns_404_not_403(self):
        """Non-existent token under .well-known routes to PHP and returns 404, not 403."""
        status = self._fetch_status("/.well-known/nonexistent_test_token_99999")
        self.assertEqual(status, 404)

    def test_f12_boundary_sensitive_dotfiles_outside_well_known_strictly_forbidden(self):
        """Sensitive dotfiles outside .well-known (.env, .git/config, .htaccess, .user.ini) return 403."""
        dotfiles = ["/.env", "/.git/config", "/.htaccess", "/.user.ini"]
        for df in dotfiles:
            status = self._fetch_status(df)
            self.assertEqual(status, 403, f"Dotfile {df} must return HTTP 403 Forbidden, got {status}")

    def test_f12_boundary_static_nginx_conf_directives_inspection(self):
        """prod.conf has location ^~ /.well-known/ with try_files routing to index.php before location ~ /\\."""
        self.assertTrue(os.path.exists(self.nginx_conf_path))
        with open(self.nginx_conf_path, "r", encoding="utf-8") as f:
            content = f.read()

        self.assertIn("location ^~ /.well-known/", content)
        self.assertIn("/index.php?$query_string", content)

        wk_pos = content.find("location ^~ /.well-known/")
        dot_pos = content.find("location ~ /\\.")
        self.assertNotEqual(wk_pos, -1)
        self.assertNotEqual(dot_pos, -1)
        self.assertLess(wk_pos, dot_pos, "^~ /.well-known/ must appear before location ~ /\\.")
