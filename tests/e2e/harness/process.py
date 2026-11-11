"""
Subprocess execution wrapper for E2E tests.
Provides run_cli, run_python, run_php with timeout management, duration tracking,
and structured output capture.
"""

from __future__ import annotations

import json
import os
import subprocess
import sys
import time
from dataclasses import dataclass
from typing import Any, Dict, List, Optional, Union


@dataclass
class ProcessResult:
    """Holds captured execution details of a spawned subprocess."""
    cmd: List[str]
    exit_code: int
    stdout: str
    stderr: str
    duration_sec: float
    timed_out: bool = False

    def json(self) -> Any:
        """Parse stdout as JSON."""
        return json.loads(self.stdout)

    def success(self) -> bool:
        """Return True if exit code is 0 and process did not time out."""
        return self.exit_code == 0 and not self.timed_out


def run_cli(
    cmd: Union[str, List[str]],
    cwd: Optional[str] = None,
    env: Optional[Dict[str, str]] = None,
    timeout: float = 30.0,
    input_data: Optional[str] = None,
    shell: bool = False
) -> ProcessResult:
    """
    Execute a CLI command synchronously, capturing stdout, stderr, and exit code.
    """
    if isinstance(cmd, str) and not shell:
        import shlex
        args = shlex.split(cmd)
    elif isinstance(cmd, str) and shell:
        args = cmd  # type: ignore
    else:
        args = list(cmd)

    full_env = os.environ.copy()
    if env:
        full_env.update(env)

    start_time = time.perf_counter()
    timed_out = False
    try:
        proc = subprocess.run(
            args,
            cwd=cwd,
            env=full_env,
            input=input_data,
            text=True,
            capture_output=True,
            timeout=timeout,
            shell=shell
        )
        duration = time.perf_counter() - start_time
        return ProcessResult(
            cmd=args if isinstance(args, list) else [args],
            exit_code=proc.returncode,
            stdout=proc.stdout or "",
            stderr=proc.stderr or "",
            duration_sec=duration,
            timed_out=False
        )
    except subprocess.TimeoutExpired as e:
        duration = time.perf_counter() - start_time
        stdout = e.stdout if isinstance(e.stdout, str) else (e.stdout.decode("utf-8", "replace") if e.stdout else "")
        stderr = e.stderr if isinstance(e.stderr, str) else (e.stderr.decode("utf-8", "replace") if e.stderr else "")
        return ProcessResult(
            cmd=args if isinstance(args, list) else [args],
            exit_code=-1,
            stdout=stdout,
            stderr=stderr + f"\nProcess timed out after {timeout} seconds.",
            duration_sec=duration,
            timed_out=True
        )


def run_python(
    args: Union[str, List[str]],
    cwd: Optional[str] = None,
    env: Optional[Dict[str, str]] = None,
    timeout: float = 30.0,
    input_data: Optional[str] = None
) -> ProcessResult:
    """
    Execute a Python script or module using the current Python interpreter.
    """
    if isinstance(args, str):
        import shlex
        cmd = [sys.executable] + shlex.split(args)
    else:
        cmd = [sys.executable] + list(args)

    return run_cli(cmd=cmd, cwd=cwd, env=env, timeout=timeout, input_data=input_data)


def run_php(
    args: Union[str, List[str]],
    cwd: Optional[str] = None,
    env: Optional[Dict[str, str]] = None,
    timeout: float = 30.0,
    input_data: Optional[str] = None,
    php_binary: str = "php"
) -> ProcessResult:
    """
    Execute a PHP command or script using the PHP CLI.
    """
    if isinstance(args, str):
        import shlex
        cmd = [php_binary] + shlex.split(args)
    else:
        cmd = [php_binary] + list(args)

    return run_cli(cmd=cmd, cwd=cwd, env=env, timeout=timeout, input_data=input_data)
