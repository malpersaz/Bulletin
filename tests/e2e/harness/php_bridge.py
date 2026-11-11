"""
PHP execution bridge for E2E tests.
Enables running isolated PHP evaluations, MCP server tool checks,
and ORM model assertions against host MockDb or Docker bulletin_app.
"""

from __future__ import annotations

import json
import os
import shutil
import tempfile
from typing import Any, Dict, List, Optional, Union

from .process import ProcessResult, run_cli, run_php


PROJECT_ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "..", ".."))


class PhpBridge:
    """
    Bridge utility to execute PHP code and test MCP/ORM components.
    """

    def __init__(self, project_root: Optional[str] = None, php_binary: str = "php"):
        self.project_root = project_root or PROJECT_ROOT
        self.php_binary = php_binary
        self._docker_available: Optional[bool] = None

    def is_docker_available(self) -> bool:
        """Check if Docker and bulletin_app container are running."""
        if self._docker_available is not None:
            return self._docker_available

        if not shutil.which("docker"):
            self._docker_available = False
            return False

        res = run_cli(["docker", "ps", "--filter", "name=bulletin_app", "--format", "{{.Names}}"], timeout=5.0)
        self._docker_available = (res.exit_code == 0 and "bulletin_app" in res.stdout)
        return self._docker_available

    def run_php_code(
        self,
        code: str,
        timeout: float = 30.0,
        env: Optional[Dict[str, str]] = None,
        use_mock_db: bool = True
    ) -> ProcessResult:
        """
        Execute an inline PHP code snippet in a clean subprocess.
        If use_mock_db is True, prepends mock db setup if needed.
        """
        # Wrap PHP code with standard error reporting and cwd
        full_script = f"""<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);
define('PROJECT_ROOT', {json.dumps(self.project_root)});
chdir(PROJECT_ROOT);

{code}
"""
        with tempfile.NamedTemporaryFile("w", suffix=".php", delete=False) as f:
            f.write(full_script)
            tmp_path = f.name

        try:
            res = run_php(
                [tmp_path],
                cwd=self.project_root,
                env=env,
                timeout=timeout,
                php_binary=self.php_binary
            )
            return res
        finally:
            if os.path.exists(tmp_path):
                os.remove(tmp_path)

    def eval_json(
        self,
        code: str,
        timeout: float = 30.0,
        env: Optional[Dict[str, str]] = None
    ) -> Any:
        """
        Execute PHP code expected to print JSON, and return parsed Python structure.
        """
        res = self.run_php_code(code, timeout=timeout, env=env)
        if res.exit_code != 0:
            raise RuntimeError(
                f"PHP evaluation failed with exit code {res.exit_code}:\n"
                f"--- STDOUT ---\n{res.stdout}\n"
                f"--- STDERR ---\n{res.stderr}"
            )
        try:
            return json.loads(res.stdout.strip())
        except json.JSONDecodeError as e:
            raise RuntimeError(
                f"Failed to decode PHP JSON output: {e}\nRaw output:\n{res.stdout}"
            ) from e

    def lint_file(self, file_path: str) -> ProcessResult:
        """Run php -l on a given PHP file to verify syntax."""
        abs_path = os.path.abspath(file_path)
        return run_php(["-l", abs_path], cwd=self.project_root)

    def run_mcp_smoke(self, timeout: float = 60.0) -> ProcessResult:
        """Run the repository MCP smoke test suite."""
        smoke_script = os.path.join(self.project_root, "tasks", "mcp_smoke.php")
        if self.is_docker_available():
            return run_cli(
                ["docker", "exec", "bulletin_app", "php", "/var/www/html/tasks/mcp_smoke.php"],
                timeout=timeout
            )
        else:
            return run_php([smoke_script], cwd=self.project_root, timeout=timeout)
