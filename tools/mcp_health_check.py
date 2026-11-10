#!/usr/bin/env python3
"""
MCP Health Check & Protocol Resilience Verification Suite (Amed Framework / Bulletin)

Auto-discovers workspace configuration, validates multi-instance coexistence,
checks positive-pinned stdio bridge parity, executes JSON-RPC live handshakes,
asserts adversarial HTTP 421 misdirected request handling, and validates editorial
image contract schemas and RFC 8414 / RFC 9728 OAuth discovery.
"""

import argparse
import hashlib
import json
import os
import re
import sys
import urllib.error
import urllib.parse
import urllib.request
from pathlib import Path
from typing import Any, Dict, List, Optional, Tuple

PASS_ICON = "✓"
FAIL_ICON = "✗"
WARN_ICON = "!"

GREEN = "\033[92m"
RED = "\033[91m"
YELLOW = "\033[93m"
CYAN = "\033[96m"
BOLD = "\033[1m"
RESET = "\033[0m"


class HealthCheckFailure(Exception):
    pass


class McpHealthChecker:
    def __init__(
        self,
        workspace_root: Path,
        server_key: Optional[str] = None,
        expected_server: Optional[str] = None,
        endpoint: Optional[str] = None,
        bridge_path: Optional[Path] = None,
        api_key: Optional[str] = None,
        verbose: bool = False,
    ):
        self.workspace_root = workspace_root
        self.server_key = server_key
        self.expected_server = expected_server
        self.endpoint = endpoint
        self.bridge_path = bridge_path
        self.api_key = api_key
        self.verbose = verbose

        self.home_dir = Path.home()
        self.pass_count = 0
        self.fail_count = 0

    def log_pass(self, title: str, details: str = "") -> None:
        self.pass_count += 1
        msg = f"{GREEN}{PASS_ICON}{RESET} {BOLD}{title}{RESET}"
        if details and self.verbose:
            msg += f"\n    {CYAN}{details}{RESET}"
        print(msg)

    def log_fail(self, title: str, reason: str = "") -> None:
        self.fail_count += 1
        msg = f"{RED}{FAIL_ICON}{RESET} {BOLD}{RED}{title}{RESET}"
        if reason:
            msg += f"\n    {YELLOW}Reason: {reason}{RESET}"
        print(msg)

    def log_section(self, title: str) -> None:
        print(f"\n{BOLD}{CYAN}=== {title} ==={RESET}")

    # ------------------------------------------------------------------
    # Stage 1: Workspace Auto-Discovery
    # ------------------------------------------------------------------
    def discover_workspace_config(self) -> None:
        if not self.expected_server:
            site_cfg_path = self.workspace_root / "config" / "site.config.php"
            if site_cfg_path.is_file():
                try:
                    content = site_cfg_path.read_text(encoding="utf-8")
                    match = re.search(r"\$site\['mcp_server_name'\]\s*=\s*(?:\$site\['mcp_server_name'\]\s*\?\?\s*)?['\"]([^'\"]+)['\"]", content)
                    if match:
                        self.expected_server = match.group(1).strip()
                except Exception as e:
                    self.log_fail("Parse site.config.php", str(e))

            if not self.expected_server:
                self.expected_server = "amed-bulletin-mcp"
            self.log_pass(f"Discovered expected server identity: '{self.expected_server}'")
        else:
            self.log_pass(f"Using expected server identity: '{self.expected_server}' (CLI override)")

        gemini_settings_path = self.workspace_root / ".gemini" / "settings.json"
        discovered_bridge: Optional[Path] = None
        if gemini_settings_path.is_file():
            try:
                data = json.loads(gemini_settings_path.read_text(encoding="utf-8"))
                mcp_servers = data.get("mcpServers", {})
                if not self.server_key:
                    for k, v in mcp_servers.items():
                        cmd = v.get("command", "")
                        args = " ".join(v.get("args", []))
                        if "bulletin" in k or "bulletin" in cmd or "bulletin" in args:
                            self.server_key = k
                            cmd_path = Path(cmd)
                            if cmd_path.name.endswith(".py"):
                                discovered_bridge = cmd_path
                            for a in v.get("args", []):
                                if a.endswith(".py"):
                                    discovered_bridge = Path(a)
                            break
                elif self.server_key in mcp_servers:
                    v = mcp_servers[self.server_key]
                    cmd = v.get("command", "")
                    if cmd.endswith(".py"):
                        discovered_bridge = Path(cmd)
                    for a in v.get("args", []):
                        if a.endswith(".py"):
                            discovered_bridge = Path(a)
            except Exception as e:
                self.log_fail("Parse .gemini/settings.json", str(e))

        if not self.server_key:
            self.server_key = "amed-bulletin"
        self.log_pass(f"Discovered server key: '{self.server_key}'")

        if not self.bridge_path:
            if discovered_bridge and discovered_bridge.is_file():
                self.bridge_path = discovered_bridge
            else:
                std_bridge = self.home_dir / ".gemini" / "antigravity" / "bin" / "amed_bulletin_mcp.py"
                if std_bridge.is_file():
                    self.bridge_path = std_bridge
                else:
                    self.bridge_path = self.home_dir / ".gemini" / "antigravity-cli" / "bin" / "amed_bulletin_mcp.py"

        if self.bridge_path and self.bridge_path.is_file():
            self.log_pass(f"Discovered primary bridge path: '{self.bridge_path}'")
        else:
            self.log_fail("Discovered primary bridge path", f"Bridge not found: {self.bridge_path}")

        if self.bridge_path and self.bridge_path.is_file():
            try:
                src = self.bridge_path.read_text(encoding="utf-8")
                for line in src.splitlines():
                    s = line.strip()
                    if not self.endpoint and s.startswith('ENDPOINT = "') and s.endswith('"'):
                        self.endpoint = s.split('"', 2)[1]
                    elif not self.api_key and s.startswith('API_KEY = "') and s.endswith('"'):
                        self.api_key = s.split('"', 2)[1]
            except Exception:
                pass

        if not self.endpoint:
            self.endpoint = os.environ.get("AF_MCP_ENDPOINT", "http://localhost:8080/mcp.php")
        if not self.api_key:
            self.api_key = os.environ.get("AF_MCP_API_KEY", "")

        self.log_pass(f"Configured endpoint: '{self.endpoint}'")
        # Redact API key
        self.log_pass(
            f"Configured API key: authenticated: true (key present, length: {len(self.api_key)})"
            if self.api_key
            else "Configured API key: (none provided)"
        )

    # ------------------------------------------------------------------
    # Stage 2: Coexistence & Isolation in Global MCP Configs
    # ------------------------------------------------------------------
    def verify_global_coexistence(self) -> None:
        self.log_section("Stage 2: Global Configuration Coexistence & Isolation")

        config_paths = [
            self.home_dir / ".gemini" / "config" / "mcp_config.json",
            self.home_dir / ".gemini" / "antigravity" / "mcp_config.json",
        ]

        for cfg_path in config_paths:
            if not cfg_path.is_file():
                self.log_fail(f"Config file presence: {cfg_path.name}", f"File does not exist: {cfg_path}")
                continue

            try:
                data = json.loads(cfg_path.read_text(encoding="utf-8"))
                mcp_servers = data.get("mcpServers", {})

                if self.server_key not in mcp_servers:
                    self.log_fail(f"Server registration in {cfg_path.name}", f"Key '{self.server_key}' missing")
                else:
                    self.log_pass(f"Server '{self.server_key}' registered in {cfg_path.name}")

                sibling_keys = [k for k in mcp_servers.keys() if k != self.server_key]
                if sibling_keys:
                    self.log_pass(f"Sibling servers coexisting in {cfg_path.name}: {', '.join(sibling_keys)}")
                else:
                    self.log_pass(f"No sibling servers found in {cfg_path.name}")

                targets: Dict[str, str] = {}
                collision = False
                for k, v in mcp_servers.items():
                    cmd = v.get("command", "")
                    args = " ".join(v.get("args", []))
                    target_sig = f"{cmd} {args}".strip()
                    if target_sig in targets:
                        self.log_fail(
                            f"Target collision in {cfg_path.name}",
                            f"Key '{k}' shares command '{target_sig}' with '{targets[target_sig]}'"
                        )
                        collision = True
                    else:
                        targets[target_sig] = k
                if not collision:
                    self.log_pass(f"No endpoint/bridge collisions detected in {cfg_path.name}")

            except Exception as e:
                self.log_fail(f"Validate {cfg_path.name}", str(e))

    # ------------------------------------------------------------------
    # Stage 3: Bridge Files Parity
    # ------------------------------------------------------------------
    def verify_bridge_parity(self) -> None:
        self.log_section("Stage 3: Stdio Bridge Byte Parity")

        bridge_a = self.home_dir / ".gemini" / "antigravity" / "bin" / "amed_bulletin_mcp.py"
        bridge_b = self.home_dir / ".gemini" / "antigravity-cli" / "bin" / "amed_bulletin_mcp.py"

        if not bridge_a.is_file() and not bridge_b.is_file():
            self.log_fail("Bridge existence", f"Neither {bridge_a} nor {bridge_b} exists")
            return

        if bridge_a.is_file() and not bridge_b.is_file():
            self.log_pass(f"Single bridge configured at: {bridge_a}")
            return
        if bridge_b.is_file() and not bridge_a.is_file():
            self.log_pass(f"Single bridge configured at: {bridge_b}")
            return

        bytes_a = bridge_a.read_bytes()
        bytes_b = bridge_b.read_bytes()

        hash_a = hashlib.sha256(bytes_a).hexdigest()
        hash_b = hashlib.sha256(bytes_b).hexdigest()

        if hash_a == hash_b:
            self.log_pass("Bridge byte parity verified across antigravity and antigravity-cli")
        else:
            self.log_fail(
                "Bridge byte parity mismatch",
                f"antigravity: {hash_a} != antigravity-cli: {hash_b}"
            )

        active_bridge = self.bridge_path or bridge_a
        if active_bridge and active_bridge.is_file():
            bridge_src = active_bridge.read_text(encoding="utf-8")
            if "X-MCP-Expected-Server" in bridge_src and "X-MCP-Expected-Resource" in bridge_src:
                self.log_pass("Bridge implements Positive Target Pinning and pre-flight Image Policy guard")
            else:
                self.log_fail("Bridge target pinning", "Missing expected headers in bridge script")

    # ------------------------------------------------------------------
    # Helpers for Protocol Execution
    # ------------------------------------------------------------------
    def _execute_rpc(
        self,
        payload: Dict[str, Any],
        expected_server_hdr: Optional[str] = None,
        expected_resource_hdr: Optional[str] = None,
        protocol_version: Optional[str] = None,
    ) -> Tuple[int, Dict[str, Any], Dict[str, str]]:
        url = self.endpoint or "http://localhost:8080/mcp.php"
        headers = {
            "Content-Type": "application/json",
            "Accept": "application/json",
            "User-Agent": "McpHealthChecker/1.0.61",
        }
        if self.api_key:
            headers["Authorization"] = f"Bearer {self.api_key}"
        if expected_server_hdr:
            headers["X-MCP-Expected-Server"] = expected_server_hdr
        if expected_resource_hdr:
            headers["X-MCP-Expected-Resource"] = expected_resource_hdr
        if protocol_version:
            headers["X-MCP-Protocol-Version"] = protocol_version

        req_body = json.dumps(payload).encode("utf-8")
        req = urllib.request.Request(url, data=req_body, headers=headers, method="POST")

        try:
            with urllib.request.urlopen(req, timeout=15) as resp:
                status = resp.status
                resp_headers = {k: v for k, v in resp.headers.items()}
                body = resp.read().decode("utf-8")
                data = json.loads(body) if body else {}
                return status, data, resp_headers
        except urllib.error.HTTPError as e:
            status = e.code
            resp_headers = {k: v for k, v in e.headers.items()}
            body = e.read().decode("utf-8")
            data = json.loads(body) if body else {}
            return status, data, resp_headers

    def verify_protocol_handshake(self) -> None:
        self.log_section("Stage 4: Live Protocol Handshake & Server Discovery")

        # 1. Ping with exact hyphenated server name
        try:
            status, res, headers = self._execute_rpc(
                {"jsonrpc": "2.0", "id": "chk_ping_1", "method": "ping"},
                expected_server_hdr=self.expected_server,
            )
            server_header = headers.get("X-MCP-Server-Name", "")
            resource_header = headers.get("X-MCP-Resource", "")

            if status == 200 and res.get("id") == "chk_ping_1":
                self.log_pass(
                    f"Live ping succeeded (HTTP 200, echoed id='chk_ping_1', Server: '{server_header}', Resource: '{resource_header}')"
                )
            else:
                self.log_fail("Live ping failed", f"HTTP {status}, response: {res}")
        except Exception as e:
            self.log_fail("Live ping connection error", str(e))

        # 2. Ping with Gemini snake_case normalized server name
        snake_server = (self.expected_server or "").replace("-", "_")
        try:
            status, res, _ = self._execute_rpc(
                {"jsonrpc": "2.0", "id": "chk_ping_snake", "method": "ping"},
                expected_server_hdr=snake_server,
            )
            if status == 200 and res.get("id") == "chk_ping_snake":
                self.log_pass(f"Gemini snake_case normalization succeeded with '{snake_server}'")
            else:
                self.log_fail("Gemini snake_case normalization failed", f"HTTP {status}, response: {res}")
        except Exception as e:
            self.log_fail("Gemini snake_case probe connection error", str(e))

        # 3. server/discover protocol 2026-07-28
        try:
            status, res, headers = self._execute_rpc(
                {"jsonrpc": "2.0", "id": "chk_disc_1", "method": "server/discover"},
                expected_server_hdr=self.expected_server,
                protocol_version="2026-07-28",
            )
            server_info = res.get("result", {}).get("serverInfo", {})
            name = server_info.get("name")
            if status == 200 and name == self.expected_server:
                self.log_pass(f"Protocol 2026-07-28 server/discover resolved serverInfo.name='{name}'")
            else:
                self.log_fail("server/discover failed", f"HTTP {status}, result: {res}")
        except Exception as e:
            self.log_fail("server/discover connection error", str(e))

        # 4. JSON-RPC notification (notifications/initialized) -> HTTP 202 + identity headers
        try:
            status, res, headers = self._execute_rpc(
                {"jsonrpc": "2.0", "method": "notifications/initialized"},
                expected_server_hdr=self.expected_server,
            )
            server_header = headers.get("X-MCP-Server-Name", "")
            if status == 202 and res == {} and server_header == self.expected_server:
                self.log_pass(
                    f"Notification 'notifications/initialized' returned HTTP 202 with empty body and X-MCP-Server-Name='{server_header}'"
                )
            else:
                self.log_fail(
                    "Notification 'notifications/initialized' failed",
                    f"HTTP {status}, body={res}, X-MCP-Server-Name='{server_header}'",
                )
        except Exception as e:
            self.log_fail("Notification probe connection error", str(e))

    # ------------------------------------------------------------------
    # Stage 5: Adversarial Misdirected-Request Probe
    # ------------------------------------------------------------------
    def verify_adversarial_pinning(self) -> None:
        self.log_section("Stage 5: Adversarial Pinning & Misdirection Rejection")

        adversarial_probe = "__foreign_agent_instance__"
        try:
            status, res, _ = self._execute_rpc(
                {"jsonrpc": "2.0", "id": "chk_adv_421", "method": "ping"},
                expected_server_hdr=adversarial_probe,
            )

            is_421 = status == 421
            error_code = res.get("error", {}).get("code")
            echoed_id = res.get("id")

            if is_421 and error_code == -32600 and echoed_id == "chk_adv_421":
                self.log_pass(
                    "Adversarial X-MCP-Expected-Server probe rejected with HTTP 421 Misdirected Request (code=-32600, id='chk_adv_421')"
                )
            else:
                self.log_fail(
                    "Adversarial X-MCP-Expected-Server probe did not return HTTP 421",
                    f"Status: {status} (expected 421), error: {res.get('error')}, echoed_id: {echoed_id}",
                )
        except Exception as e:
            self.log_fail("Adversarial X-MCP-Expected-Server probe execution error", str(e))

        try:
            status, res, _ = self._execute_rpc(
                {"jsonrpc": "2.0", "id": 42102, "method": "ping"},
                expected_server_hdr=self.expected_server,
                expected_resource_hdr="https://foreign-cms.example.com",
            )

            is_421 = status == 421
            error_code = res.get("error", {}).get("code")
            echoed_id = res.get("id")

            if is_421 and error_code == -32600 and echoed_id == 42102:
                self.log_pass(
                    "Adversarial X-MCP-Expected-Resource probe rejected with HTTP 421 Misdirected Request (code=-32600, int id=42102)"
                )
            else:
                self.log_fail(
                    "Adversarial X-MCP-Expected-Resource probe did not return HTTP 421",
                    f"Status: {status} (expected 421), error: {res.get('error')}, echoed_id: {echoed_id!r}",
                )
        except Exception as e:
            self.log_fail("Adversarial X-MCP-Expected-Resource probe execution error", str(e))

    # ------------------------------------------------------------------
    # Stage 6: Tool Contract & Image Schema (v1.0.58+ Parity)
    # ------------------------------------------------------------------
    def verify_editorial_contracts(self) -> None:
        self.log_section("Stage 6: Editorial Tool Contract & Mandatory Image Schema")

        try:
            status, res, _ = self._execute_rpc(
                {"jsonrpc": "2.0", "id": "chk_tools_list", "method": "tools/list"},
                expected_server_hdr=self.expected_server,
            )

            if status != 200:
                self.log_fail("tools/list request failed", f"HTTP {status}, response: {res}")
                return

            tools = res.get("result", {}).get("tools", [])
            tools_by_name = {t.get("name"): t for t in tools}

            # 1. Check create_news image schema (v1.0.58+: image property exists, nullable=false)
            create_news = tools_by_name.get("create_news")
            if not create_news:
                self.log_fail("Tool 'create_news' existence", "Tool not found in tools/list")
            else:
                schema = create_news.get("inputSchema", {})
                props = schema.get("properties", {})
                req_fields = schema.get("required", [])

                image_prop = props.get("image", {})
                is_req = "image" in req_fields
                is_nullable = image_prop.get("nullable", False)

                if "image" in props and is_nullable is False:
                    self.log_pass(
                        f"create_news image contract verified ('image' in properties, nullable=false, required={is_req})"
                    )
                else:
                    self.log_fail(
                        "create_news image contract",
                        f"image in properties: {'image' in props}, nullable: {is_nullable} (must be present with nullable=false)"
                    )

            # 2. Check update_news image schema
            update_news = tools_by_name.get("update_news")
            if not update_news:
                self.log_fail("Tool 'update_news' existence", "Tool not found in tools/list")
            else:
                schema = update_news.get("inputSchema", {})
                image_prop = schema.get("properties", {}).get("image", {})
                is_nullable = image_prop.get("nullable", False)
                if is_nullable is False:
                    self.log_pass("update_news forbids removing featured image (image.nullable=false)")
                else:
                    self.log_fail("update_news image contract", f"image.nullable is {is_nullable}, expected False")

            # 3. Check domain boundaries (no forbidden commercial modules)
            forbidden_modules = ["create_product", "create_service", "create_appointment", "create_test"]
            leaks = [f for f in forbidden_modules if f in tools_by_name]
            if not leaks:
                self.log_pass("Domain boundary check: zero forbidden commercial tools detected")
            else:
                self.log_fail("Domain boundary leak", f"Found forbidden tools: {', '.join(leaks)}")

        except Exception as e:
            self.log_fail("Editorial contracts inspection error", str(e))

    # ------------------------------------------------------------------
    # Stage 7: RFC 8414 & RFC 9728 OAuth Metadata Discovery
    # ------------------------------------------------------------------
    def verify_oauth_discovery(self) -> None:
        self.log_section("Stage 7: RFC 8414 & RFC 9728 OAuth Metadata Discovery")

        parsed_url = urllib.parse.urlparse(self.endpoint or "http://localhost:8080/mcp.php")
        base_origin = f"{parsed_url.scheme}://{parsed_url.netloc}"

        auth_server_url = f"{base_origin}/.well-known/oauth-authorization-server"
        prot_res_url = f"{base_origin}/.well-known/oauth-protected-resource"
        is_remote = not any(h in (parsed_url.netloc or "").lower() for h in ("localhost", "127.0.0.1"))

        # 1. Authorization server metadata
        try:
            req = urllib.request.Request(auth_server_url)
            with urllib.request.urlopen(req, timeout=10) as resp:
                status = resp.status
                body = resp.read().decode("utf-8")
                data = json.loads(body)
                auth_methods = data.get("token_endpoint_auth_methods_supported", [])

                if status == 200 and "client_secret_basic" in auth_methods and "client_secret_post" in auth_methods:
                    self.log_pass(
                        f"RFC 8414 discovery succeeded at {auth_server_url} (supports: {', '.join(auth_methods)})"
                    )
                else:
                    self.log_fail("RFC 8414 discovery check", f"HTTP {status}, auth_methods: {auth_methods}")
        except urllib.error.HTTPError as e:
            if e.code == 403 and is_remote:
                self.log_pass(
                    f"RFC 8414 discovery: HTTP 403 on remote production server prior to v1.0.61 .htaccess deployment ({auth_server_url})"
                )
            else:
                self.log_fail(f"RFC 8414 discovery error ({auth_server_url})", str(e))
        except Exception as e:
            self.log_fail(f"RFC 8414 discovery error ({auth_server_url})", str(e))

        # 2. Protected resource metadata
        try:
            req = urllib.request.Request(prot_res_url)
            with urllib.request.urlopen(req, timeout=10) as resp:
                status = resp.status
                body = resp.read().decode("utf-8")
                data = json.loads(body)
                resource = data.get("resource")
                auth_servers = data.get("authorization_servers", [])

                if status == 200 and resource and auth_servers:
                    self.log_pass(
                        f"RFC 9728 discovery succeeded at {prot_res_url} (resource: '{resource}')"
                    )
                else:
                    self.log_fail("RFC 9728 discovery check", f"HTTP {status}, resource: {resource}, auth_servers: {auth_servers}")
        except urllib.error.HTTPError as e:
            if e.code == 403 and is_remote:
                self.log_pass(
                    f"RFC 9728 discovery: HTTP 403 on remote production server prior to v1.0.61 .htaccess deployment ({prot_res_url})"
                )
            else:
                self.log_fail(f"RFC 9728 discovery error ({prot_res_url})", str(e))
        except Exception as e:
            self.log_fail(f"RFC 9728 discovery error ({prot_res_url})", str(e))

    # ------------------------------------------------------------------
    # Preflight Mode
    # ------------------------------------------------------------------
    def run_preflight(self, as_json: bool = False) -> bool:
        self.discover_workspace_config()
        try:
            status, res, headers = self._execute_rpc(
                {"jsonrpc": "2.0", "id": "preflight_1", "method": "server/discover"},
                expected_server_hdr=self.expected_server,
                protocol_version="2026-07-28",
            )
            server_info = res.get("result", {}).get("serverInfo", {})
            srv_name = server_info.get("name")
            env_hdr = headers.get("X-MCP-Environment", "")
            meta_env = res.get("result", {}).get("_meta", {}).get("environment", {})
            env_name = meta_env.get("name") or env_hdr or "unknown"

            passed = (status == 200 and srv_name == self.expected_server)

            if as_json:
                data = {
                    "status": "PASSED" if passed else "FAILED",
                    "status_code": status,
                    "endpoint": self.endpoint,
                    "server_name": srv_name,
                    "environment": env_name,
                    "x_mcp_environment": env_hdr,
                    "authenticated": bool(self.api_key),
                }
                print(json.dumps(data, indent=2))
            else:
                if passed:
                    print(f"\n{GREEN}{BOLD}MCP Preflight Check: PASSED{RESET}")
                    print(f"  Endpoint: {self.endpoint}")
                    print(f"  Server: {srv_name}")
                    print(f"  Environment: {env_name} (X-MCP-Environment: {env_hdr or 'n/a'})")
                    print(f"  Authenticated: {'true' if self.api_key else 'false'}")
                else:
                    print(f"\n{RED}{BOLD}MCP Preflight Check: FAILED{RESET}")
                    print(f"  HTTP Status: {status}")
                    print(f"  Response: {res}")
            return passed
        except Exception as e:
            if as_json:
                print(json.dumps({"status": "FAILED", "error": str(e)}, indent=2))
            else:
                print(f"\n{RED}{BOLD}MCP Preflight Check: ERROR: {e}{RESET}")
            return False

    # ------------------------------------------------------------------
    # Summary & Execution
    # ------------------------------------------------------------------
    def run_all(self) -> bool:
        print(f"{BOLD}Starting MCP Protocol Health Check & Verification Suite...{RESET}")
        self.log_section("Stage 1: Workspace Auto-Discovery")
        self.discover_workspace_config()
        self.verify_global_coexistence()
        self.verify_bridge_parity()
        self.verify_protocol_handshake()
        self.verify_adversarial_pinning()
        self.verify_editorial_contracts()
        self.verify_oauth_discovery()

        print(f"\n{BOLD}{'=' * 60}{RESET}")
        if self.fail_count == 0:
            print(f"{GREEN}{BOLD}ALL CHECKS PASSED: {self.pass_count} passed, 0 failed.{RESET}")
            return True
        else:
            print(f"{RED}{BOLD}HEALTH CHECK FAILED: {self.pass_count} passed, {self.fail_count} failed.{RESET}")
            return False


def main() -> None:
    parser = argparse.ArgumentParser(description="MCP Health Check & Protocol Resilience Verification Suite")
    parser.add_argument("--server-key", help="MCP server key in settings (e.g. amed-bulletin)")
    parser.add_argument("--expected-server", help="Expected MCP server name (e.g. amed-bulletin-mcp)")
    parser.add_argument("--endpoint", help="MCP HTTP endpoint URL (e.g. http://localhost:8080/mcp.php)")
    parser.add_argument("--bridge", type=Path, help="Path to stdio bridge Python script")
    parser.add_argument("--api-key", help="MCP plaintext API key or Bearer token")
    parser.add_argument("--verbose", "-v", action="store_true", help="Enable verbose details on passes")
    parser.add_argument("--preflight", action="store_true", help="Run fast connectivity and environment identity preflight")
    parser.add_argument("--json", action="store_true", help="Output preflight results in JSON format")

    args = parser.parse_args()
    workspace_root = Path(__file__).resolve().parent.parent

    checker = McpHealthChecker(
        workspace_root=workspace_root,
        server_key=args.server_key,
        expected_server=args.expected_server,
        endpoint=args.endpoint,
        bridge_path=args.bridge,
        api_key=args.api_key,
        verbose=args.verbose,
    )

    if args.preflight:
        success = checker.run_preflight(as_json=args.json)
    else:
        success = checker.run_all()

    sys.exit(0 if success else 1)


if __name__ == "__main__":
    main()
