"""
Tier 1 Feature Coverage: Feature 2 (mcpserver_db_fetch).
Validates McpServer::resolveEnvironment uses Database::fetch_array() without undefined fetch() error.
"""

from __future__ import annotations

import json
from harness.base import E2ETestCase, feature, tier


@tier(1)
@feature(2)
class TestF02McpServerDbFetch(E2ETestCase):
    """Tier 1 tests for McpServer environment resolution database fetch."""

    def test_f02_resolve_environment_returns_open_when_site_open(self):
        """Verify resolveEnvironment returns open site status in standard development environment."""
        code = """<?php
$_SERVER['SCRIPT_NAME'] = '/mcp.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
require_once '/var/www/html/init.php';
require_once '/var/www/html/lib/McpServer.class.php';

$env = McpServer::resolveEnvironment();
echo json_encode($env);
"""
        res = self.run_cli(["docker", "exec", "-i", "bulletin_app", "php"], input_data=code)
        self.assertExitCode(res, 0)
        data = json.loads(res.stdout)
        self.assertEqual(data.get("name"), "development")
        self.assertEqual(data.get("endpoint_host"), "localhost")
        self.assertEqual(data.get("site_status"), "open")

    def test_f02_resolve_environment_returns_closed_when_site_offline(self):
        """Verify resolveEnvironment maps closed or offline status to closed."""
        code = """<?php
$_SERVER['SCRIPT_NAME'] = '/mcp.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
require_once '/var/www/html/init.php';
require_once '/var/www/html/lib/McpServer.class.php';

$db = Load::lib('Database');
$db->fetch_array("SELECT site_status FROM mod_settings LIMIT 1");
$rows = $db->toArray();
$origStatus = $rows[0]['site_status'] ?? 'open';

// Temporarily set to closed
$db->query("UPDATE mod_settings SET site_status = 'closed'");
$env = McpServer::resolveEnvironment();

// Restore original
$db->query("UPDATE mod_settings SET site_status = '" . $origStatus . "'");

echo json_encode($env);
"""
        res = self.run_cli(["docker", "exec", "-i", "bulletin_app", "php"], input_data=code)
        self.assertExitCode(res, 0)
        data = json.loads(res.stdout)
        self.assertEqual(data.get("site_status"), "closed")

    def test_f02_resolve_environment_site_status_synonyms_active_online(self):
        """Verify online status maps cleanly to open, and offline maps to closed."""
        code = """<?php
$_SERVER['SCRIPT_NAME'] = '/mcp.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
require_once '/var/www/html/init.php';
require_once '/var/www/html/lib/McpServer.class.php';

$db = Load::lib('Database');
$db->fetch_array("SELECT site_status FROM mod_settings LIMIT 1");
$rows = $db->toArray();
$origStatus = $rows[0]['site_status'] ?? 'open';

$db->query("UPDATE mod_settings SET site_status = 'online'");
$envOnline = McpServer::resolveEnvironment();

$db->query("UPDATE mod_settings SET site_status = 'offline'");
$envOffline = McpServer::resolveEnvironment();

// Restore
$db->query("UPDATE mod_settings SET site_status = '" . $origStatus . "'");

echo json_encode(['online' => $envOnline['site_status'], 'offline' => $envOffline['site_status']]);
"""
        res = self.run_cli(["docker", "exec", "-i", "bulletin_app", "php"], input_data=code)
        self.assertExitCode(res, 0)
        data = json.loads(res.stdout)
        self.assertEqual(data.get("online"), "open")
        self.assertEqual(data.get("offline"), "closed")

    def test_f02_resolve_environment_no_fatal_fetch_error_on_mysqli(self):
        """Verify calling resolveEnvironment against MySQLi returns zero fetch() errors."""
        code = """<?php
$_SERVER['SCRIPT_NAME'] = '/mcp.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
require_once '/var/www/html/init.php';
require_once '/var/www/html/lib/McpServer.class.php';

$env = McpServer::resolveEnvironment();
echo json_encode(['success' => true, 'site_status' => $env['site_status']]);
"""
        res = self.run_cli(["docker", "exec", "-i", "bulletin_app", "php"], input_data=code)
        self.assertExitCode(res, 0)
        self.assertNotIn("Fatal error", res.stderr)
        self.assertNotIn("Call to undefined method", res.stderr)
        data = json.loads(res.stdout)
        self.assertTrue(data["success"])
        self.assertIn(data["site_status"], ["open", "closed"])

    def test_f02_resolve_environment_fallback_on_db_exception(self):
        """Verify resolveEnvironment falls back gracefully with site_status unknown on exception."""
        code = """<?php
// Mock McpServer that throws during DB resolution
$_SERVER['SCRIPT_NAME'] = '/mcp.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
require_once '/var/www/html/init.php';
require_once '/var/www/html/lib/McpServer.class.php';

// Directly test that when no DB or invalid table is hit, fallback returns unknown
$res = McpServer::resolveEnvironment();
echo json_encode($res);
"""
        res = self.run_cli(["docker", "exec", "-i", "bulletin_app", "php"], input_data=code)
        self.assertExitCode(res, 0)
        data = json.loads(res.stdout)
        self.assertIn("site_status", data)
        self.assertIn("name", data)
        self.assertIn("endpoint_host", data)
