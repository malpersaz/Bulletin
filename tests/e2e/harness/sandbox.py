"""
Filesystem sandbox manager for isolated E2E test execution.
Creates temporary isolated directories and ensures automatic cleanup on completion.
"""

from __future__ import annotations

import os
import shutil
import stat
import tempfile
from typing import Optional, Union


class Sandbox:
    """
    Manages an isolated temporary directory for test file generation,
    image processing, and artifact assertions.
    """

    def __init__(self, prefix: str = "af_e2e_sb_", preserve_on_error: bool = False):
        self.prefix = prefix
        self.preserve_on_error = preserve_on_error
        self.root: Optional[str] = None
        self._active = False

    def __enter__(self) -> "Sandbox":
        self.create()
        return self

    def __exit__(self, exc_type, exc_val, exc_tb) -> None:
        if exc_type and self.preserve_on_error:
            # Leave directory intact for inspection
            return
        self.cleanup()

    def create(self) -> str:
        """Create the temporary sandbox directory if not already created."""
        if not self._active:
            self.root = os.path.realpath(tempfile.mkdtemp(prefix=self.prefix))
            self._active = True
        return self.root  # type: ignore

    @staticmethod
    def _ensure_writable_tree(top: str) -> None:
        """Recursively ensure top directory and all descendants are writable and searchable."""
        try:
            os.chmod(top, stat.S_IRWXU)
        except Exception:
            pass
        if os.path.isdir(top):
            try:
                with os.scandir(top) as it:
                    for entry in it:
                        Sandbox._ensure_writable_tree(entry.path)
            except Exception:
                pass

    def cleanup(self) -> None:
        """Remove the sandbox directory and all its contents cleanly without permission leaks."""
        if self._active and self.root and os.path.exists(self.root):
            try:
                self._ensure_writable_tree(self.root)

                def _handle_remove_readonly(func, path, exc_info):
                    try:
                        parent = os.path.dirname(path)
                        if parent and os.path.exists(parent):
                            os.chmod(parent, stat.S_IRWXU)
                        os.chmod(path, stat.S_IRWXU)
                        if os.path.isdir(path):
                            os.rmdir(path)
                        else:
                            os.unlink(path)
                    except Exception:
                        pass

                shutil.rmtree(self.root, onerror=_handle_remove_readonly)
            except Exception:
                try:
                    shutil.rmtree(self.root, ignore_errors=True)
                except Exception:
                    pass
            finally:
                self._active = False
                self.root = None

    def path(self, *parts: str) -> str:
        """Get an absolute path within the sandbox, enforcing containment against traversal attacks."""
        if not self._active or not self.root:
            self.create()
        root = os.path.realpath(self.root)
        target = os.path.realpath(os.path.join(root, *parts))
        try:
            if os.path.commonpath([target, root]) != root:
                raise ValueError(f"Path traversal detected: {parts!r} escapes sandbox root '{root}'")
        except ValueError as err:
            raise ValueError(f"Path traversal detected: {parts!r} escapes sandbox root '{root}'") from err
        return target

    def mkdir(self, *parts: str) -> str:
        """Create a subdirectory inside the sandbox and return its path."""
        p = self.path(*parts)
        os.makedirs(p, exist_ok=True)
        return p

    def write_file(self, filename: str, content: Union[str, bytes], encoding: str = "utf-8") -> str:
        """Write content to a file inside the sandbox and return its path."""
        target_path = self.path(filename)
        os.makedirs(os.path.dirname(target_path), exist_ok=True)
        if isinstance(content, bytes):
            with open(target_path, "wb") as f:
                f.write(content)
        else:
            with open(target_path, "w", encoding=encoding) as f:
                f.write(content)
        return target_path

    def read_file(self, filename: str, encoding: str = "utf-8") -> str:
        """Read text from a file inside the sandbox."""
        target_path = self.path(filename)
        with open(target_path, "r", encoding=encoding) as f:
            return f.read()

    def copy_file(self, src: str, dst_filename: Optional[str] = None) -> str:
        """Copy an existing source file into the sandbox."""
        if dst_filename is None:
            dst_filename = os.path.basename(src)
        dst = self.path(dst_filename)
        os.makedirs(os.path.dirname(dst), exist_ok=True)
        shutil.copy2(src, dst)
        return dst

    def exists(self, filename: str) -> bool:
        """Check if a file or directory exists in the sandbox."""
        try:
            return os.path.exists(self.path(filename))
        except ValueError:
            return False
