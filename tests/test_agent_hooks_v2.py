#!/usr/bin/env python3
"""Unit tests for Antigravity v2.32 af-cms-synchronizer hooks and portable plugin manifests."""

import json
import os
import shutil
import stat
import subprocess
import sys
import tempfile
import unittest

REPO_ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), ".."))
PLUGIN_DIR = os.path.join(REPO_ROOT, ".agents", "plugins", "af-cms-synchronizer")
SCRIPTS_DIR = os.path.join(PLUGIN_DIR, "scripts")
PRE_GUARD = os.path.join(SCRIPTS_DIR, "pre_tool_guard.py")
POST_VERIFIER = os.path.join(SCRIPTS_DIR, "post_tool_verifier.py")
STOP_GATE = os.path.join(SCRIPTS_DIR, "stop_quality_gate.py")


def run_hook(script_path, payload, args=None, cwd=None):
    """Invoke a hook script with JSON stdin and return (returncode, stdout_text, stderr_text, parsed_json)."""
    cmd = [sys.executable, script_path]
    if args:
        cmd.extend(args)
    res = subprocess.run(
        cmd,
        input=json.dumps(payload),
        text=True,
        capture_output=True,
        cwd=cwd or REPO_ROOT,
        timeout=15,
        check=False,
    )
    parsed = None
    if res.stdout and res.stdout.strip():
        try:
            parsed = json.loads(res.stdout)
        except json.JSONDecodeError:
            parsed = None
    return res.returncode, res.stdout, res.stderr, parsed


def init_temp_repo(base_dir, initial_files=None):
    """Create an isolated temporary git repository with an initial commit."""
    subprocess.run(["git", "init", "-q"], cwd=base_dir, check=True)
    subprocess.run(["git", "config", "user.email", "test@example.com"], cwd=base_dir, check=True)
    subprocess.run(["git", "config", "user.name", "Test User"], cwd=base_dir, check=True)

    if initial_files:
        for rel_path, content in initial_files.items():
            abs_path = os.path.join(base_dir, rel_path)
            os.makedirs(os.path.dirname(abs_path), exist_ok=True)
            with open(abs_path, "w", encoding="utf-8") as f:
                f.write(content)
    else:
        readme_path = os.path.join(base_dir, "README.md")
        with open(readme_path, "w", encoding="utf-8") as f:
            f.write("temp repo\n")

    subprocess.run(["git", "add", "."], cwd=base_dir, check=True)
    subprocess.run(["git", "commit", "-q", "-m", "init"], cwd=base_dir, check=True)


class TestPluginManifestsAndPortability(unittest.TestCase):
    """Verify portable configs, logo metadata, and v2.32 hook matchers."""

    def test_skills_json_is_portable(self):
        path = os.path.join(REPO_ROOT, ".agents", "skills.json")
        with open(path, "r", encoding="utf-8") as f:
            raw = f.read()
        self.assertNotIn("/home/", raw)
        self.assertNotIn("C:\\", raw)
        data = json.loads(raw)
        self.assertIn("entries", data)
        self.assertEqual(data["entries"][0]["path"], ".agents/skills")
        self.assertIn("_shared", data["entries"][0].get("exclude", []))

    def test_plugins_json_uses_entries_schema(self):
        path = os.path.join(REPO_ROOT, ".agents", "plugins.json")
        with open(path, "r", encoding="utf-8") as f:
            data = json.load(f)
        self.assertIn("entries", data)
        self.assertNotIn("plugins", data)
        self.assertEqual(data["entries"][0]["path"], ".agents/plugins")

    def test_plugin_json_metadata_and_logo_exist(self):
        path = os.path.join(PLUGIN_DIR, "plugin.json")
        with open(path, "r", encoding="utf-8") as f:
            data = json.load(f)
        self.assertEqual(data["name"], "af-cms-synchronizer")
        self.assertEqual(data["version"], "1.0.72")
        self.assertNotIn("keywords", data)
        logo_rel = data.get("logo", "")
        self.assertTrue(logo_rel)
        self.assertTrue(os.path.isfile(os.path.join(PLUGIN_DIR, logo_rel)))

    def test_hooks_json_matchers_and_executables(self):
        path = os.path.join(PLUGIN_DIR, "hooks.json")
        with open(path, "r", encoding="utf-8") as f:
            data = json.load(f)
        root = data["af-cms-synchronizer-hooks"]

        pre_matcher = root["PreToolUse"][0]["matcher"]
        post_matcher = root["PostToolUse"][0]["matcher"]
        for expected_tool in (
            "write_file",
            "edit_file",
            "multi_replace_file_content",
            "write_to_file",
            "replace_file_content",
        ):
            self.assertIn(expected_tool, pre_matcher)
            self.assertIn(expected_tool, post_matcher)
        self.assertIn("run_command", pre_matcher)

        self.assertIsInstance(root["Stop"], list)
        self.assertEqual(root["Stop"][0]["type"], "command")

        for script_path in (PRE_GUARD, POST_VERIFIER, STOP_GATE):
            mode = os.stat(script_path).st_mode
            self.assertTrue(bool(mode & stat.S_IXUSR), f"Not executable: {script_path}")


class TestPreToolGuard(unittest.TestCase):
    """Verify PreToolUse blocks real violations across all tool schemas with zero false positives."""

    def test_blocks_all_file_tool_schemas_for_inline_violations(self):
        cases = [
            ("write_file", {"Path": "templates/public/news.twig", "Content": '<div style="color:red">hi</div>'}),
            ("edit_file", {"Path": "templates/public/news.html", "Target": "a", "Replacement": '<button onclick="go()">x</button>'}),
            (
                "multi_replace_file_content",
                {
                    "TargetFile": "templates/admin/mod_news/index.twig",
                    "ReplacementChunks": [{"TargetContent": "x", "ReplacementContent": "<style>body{color:red}</style>"}],
                },
            ),
            ("write_to_file", {"TargetFile": "mod/news/helper/view.php", "CodeContent": "<?php echo '<script>alert(1)</script>'; ?>"}),
            ("replace_file_content", {"TargetFile": "templates/public/layout.twig", "ReplacementContent": '<span onmouseover="x()">y</span>'}),
        ]
        for tool_name, args in cases:
            rc, _, _, out = run_hook(PRE_GUARD, {"cwd": REPO_ROOT, "toolCall": {"name": tool_name, "args": args}})
            self.assertEqual(rc, 0)
            self.assertEqual(out["decision"], "deny", f"Expected deny for {tool_name}")

    def test_blocks_forbidden_modules_and_cross_repo_writes(self):
        payloads = [
            {"name": "write_file", "args": {"Path": "mod/product/ProductController.php", "Content": "<?php class X {}"}},
            {"name": "edit_file", "args": {"Path": "mod/news/controller/NewsController.php", "Replacement": "$tbl = 'mod_product';"}},
            {"name": "write_file", "args": {"Path": "../Business/mod/news/evil.php", "Content": "<?php"}},
            {"name": "write_file", "args": {"Path": "/etc/hosts", "Content": "127.0.0.1 evil"}},
        ]
        for tool_call in payloads:
            rc, _, _, out = run_hook(PRE_GUARD, {"cwd": REPO_ROOT, "toolCall": tool_call})
            self.assertEqual(rc, 0)
            self.assertEqual(out["decision"], "deny", f"Expected deny for {tool_call}")

    def test_allows_clean_idioms_and_exempt_paths(self):
        allowed_calls = [
            # Async CSS preload idiom
            {
                "name": "write_file",
                "args": {
                    "Path": "templates/public/base.twig",
                    "Content": '<link rel="preload" as="style" href="a.css" onload="this.onload=null;this.rel=\'stylesheet\'">\n',
                },
            },
            # External script + JSON-LD structured data
            {
                "name": "write_file",
                "args": {
                    "Path": "templates/public/article.twig",
                    "Content": (
                        '<script defer src="/static/js/app.js"></script>\n'
                        '<script type="application/ld+json">{"@type":"NewsArticle"}</script>\n'
                    ),
                },
            },
            # PHP variable assignments and Twig non-event attributes
            {
                "name": "edit_file",
                "args": {
                    "Path": "mod/news/controller/NewsController.php",
                    "Replacement": "$style = 'card';\n$this->style = 'compact';\n$attr = 'data-style=\"wide\" once=\"true\"';\n",
                },
            },
            # Comments containing examples
            {
                "name": "write_file",
                "args": {
                    "Path": "templates/public/notes.twig",
                    "Content": "{# <div style=\"color:red\">ignored</div> #}\n<!-- <button onclick=\"bad()\">ignored</button> -->\n",
                },
            },
            # Bulletin-native category & allowlisted hero slider helper
            {
                "name": "edit_file",
                "args": {
                    "Path": "mod/index/helper/mod_hero_slider.helper.php",
                    "Replacement": "// mod_news_category helper\n",
                },
            },
            # Exempt test/doc/artifact paths
            {
                "name": "write_file",
                "args": {
                    "Path": "tests/sample_fixture_test.php",
                    "Content": "<?php $s = '<div style=\"color:red\">'; $m = 'mod_product';\n",
                },
            },
            {
                "name": "write_file",
                "args": {
                    "Path": "/tmp/antigravity_scratch/plan.md",
                    "Content": "Artifact preview for mod_product notes\n",
                },
            },
        ]
        for tool_call in allowed_calls:
            rc, _, _, out = run_hook(
                PRE_GUARD,
                {
                    "cwd": REPO_ROOT,
                    "artifactDirectoryPath": "/tmp/antigravity_scratch",
                    "toolCall": tool_call,
                },
            )
            self.assertEqual(rc, 0)
            self.assertEqual(out["decision"], "allow", f"False positive deny on {tool_call}: {out}")

    def test_run_command_blocks_business_mutations_and_allows_commit_prose(self):
        blocked_cmds = [
            "cd ../Business && git status",
            "git -C /home/malpersaz/Desktop/projects/htdocs/AF/Business commit -m test",
            "echo test > ../Business/foo.txt",
            "cp README.md /home/malpersaz/Desktop/projects/htdocs/AF/Business/README.md",
            "sed -i 's/a/b/' ../Business/config/site.config.php",
            "python3 -c \"open('../Business/x.txt', 'w').write('x')\"",
            "php -r 'file_put_contents(\"../Business/x.php\", \"a\");'",
        ]
        for cmd in blocked_cmds:
            rc, _, _, out = run_hook(PRE_GUARD, {"cwd": REPO_ROOT, "toolCall": {"name": "run_command", "args": {"CommandLine": cmd}}})
            self.assertEqual(rc, 0)
            self.assertEqual(out["decision"], "deny", f"Expected deny for command: {cmd}")

        allowed_cmds = [
            'git commit -m "docs: guard against cd ../Business or cp into AF/Business"',
            "python3 tools/cross_repo_sync.py --sibling ../Business",
            "php -l config/site.config.php",
        ]
        for cmd in allowed_cmds:
            rc, _, _, out = run_hook(PRE_GUARD, {"cwd": REPO_ROOT, "toolCall": {"name": "run_command", "args": {"CommandLine": cmd}}})
            self.assertEqual(rc, 0)
            self.assertEqual(out["decision"], "allow", f"False positive deny for command: {cmd}: {out}")


class TestPostToolAndStopHooks(unittest.TestCase):
    """Verify PostToolUse contracts and Stop hook bounded loop + diff-scoped behavior."""

    def test_post_tool_hook_mode_always_returns_empty_json_and_zero(self):
        with tempfile.TemporaryDirectory() as tmpdir:
            init_temp_repo(tmpdir, {"templates/public/view.twig": "<div>ok</div>\n"})
            bad_php = os.path.join(tmpdir, "mod", "news", "broken.php")
            os.makedirs(os.path.dirname(bad_php), exist_ok=True)
            with open(bad_php, "w", encoding="utf-8") as f:
                f.write("<?php echo ;\n")

            payload = {
                "cwd": tmpdir,
                "workspacePaths": [tmpdir],
                "toolCall": {"name": "write_file", "args": {"Path": "mod/news/broken.php"}},
            }
            rc, stdout, _, out = run_hook(POST_VERIFIER, payload, cwd=tmpdir)
            self.assertEqual(rc, 0)
            self.assertEqual(out, {}, f"Unexpected PostToolUse stdout: {stdout}")
            # Must not pollute repo by creating logs/ when absent
            self.assertFalse(os.path.exists(os.path.join(tmpdir, "logs")))

    def test_post_tool_strict_mode_catches_php_and_diff_scoped_inline_issues(self):
        with tempfile.TemporaryDirectory() as tmpdir:
            init_temp_repo(
                tmpdir,
                {"templates/public/legacy.twig": '<button onclick="legacyFn()">Legacy</button>\n'},
            )
            # Clean edit to a file with untouched legacy inline handler must pass strict verifier
            legacy_file = os.path.join(tmpdir, "templates/public/legacy.twig")
            with open(legacy_file, "a", encoding="utf-8") as f:
                f.write('<div class="clean-row">Updated cleanly</div>\n')

            clean_payload = {
                "cwd": tmpdir,
                "workspacePaths": [tmpdir],
                "toolCall": {"name": "edit_file", "args": {"Path": "templates/public/legacy.twig"}},
            }
            rc, _, stderr, out = run_hook(POST_VERIFIER, clean_payload, args=["--strict"], cwd=tmpdir)
            self.assertEqual(rc, 0, f"Unexpected failure on untouched legacy line: {stderr}")
            self.assertEqual(out, {})

            # New inline style added in diff must fail strict verifier
            with open(legacy_file, "a", encoding="utf-8") as f:
                f.write('<span style="color:red">bad</span>\n')

            rc, _, stderr, _ = run_hook(POST_VERIFIER, clean_payload, args=["--strict"], cwd=tmpdir)
            self.assertEqual(rc, 1)
            self.assertIn("inline style attribute", stderr)

            if shutil.which("php"):
                broken_php = os.path.join(tmpdir, "mod/news/bad.php")
                os.makedirs(os.path.dirname(broken_php), exist_ok=True)
                with open(broken_php, "w", encoding="utf-8") as f:
                    f.write("<?php function broken( {\n")
                php_payload = {
                    "cwd": tmpdir,
                    "workspacePaths": [tmpdir],
                    "toolCall": {"name": "write_file", "args": {"Path": "mod/news/bad.php"}},
                }
                rc, _, stderr, _ = run_hook(POST_VERIFIER, php_payload, args=["--strict"], cwd=tmpdir)
                self.assertEqual(rc, 1)
                self.assertIn("PHP syntax error", stderr)

    def test_stop_gate_respects_continuation_and_termination_reasons(self):
        with tempfile.TemporaryDirectory() as tmpdir:
            init_temp_repo(tmpdir, {"templates/public/page.twig": "<div>ok</div>\n"})
            # Introduce an untracked inline violation
            with open(os.path.join(tmpdir, "templates/public/page.twig"), "w", encoding="utf-8") as f:
                f.write('<div style="color:red">bad</div>\n')

            # Normal completion must reject stop
            rc, _, _, out = run_hook(
                STOP_GATE,
                {"cwd": tmpdir, "workspacePaths": [tmpdir], "terminationReason": "NO_TOOL_CALL", "continuationActive": False},
                cwd=tmpdir,
            )
            self.assertEqual(rc, 0)
            self.assertEqual(out["decision"], "continue")
            self.assertIn("inline style attribute", out["reason"])

            # Bounded retry: continuationActive=True must allow stop to prevent infinite loop
            rc, _, _, out = run_hook(
                STOP_GATE,
                {"cwd": tmpdir, "workspacePaths": [tmpdir], "terminationReason": "NO_TOOL_CALL", "continuationActive": True},
                cwd=tmpdir,
            )
            self.assertEqual(rc, 0)
            self.assertEqual(out["decision"], "allow")

            # Cancellation/error termination reasons must always allow stop immediately
            for reason in ("USER_CANCELED", "ERROR", "MAX_INVOCATIONS", "MAX_TOKEN_BUDGET_EXCEEDED"):
                rc, _, _, out = run_hook(
                    STOP_GATE,
                    {"cwd": tmpdir, "workspacePaths": [tmpdir], "terminationReason": reason, "continuationActive": False},
                    cwd=tmpdir,
                )
                self.assertEqual(rc, 0)
                self.assertEqual(out["decision"], "allow", f"Stop gate must allow on {reason}")

    def test_stop_gate_handles_renamed_and_space_prefixed_porcelain_paths(self):
        with tempfile.TemporaryDirectory() as tmpdir:
            init_temp_repo(tmpdir, {"templates/public/old.twig": '<div class="ok">hello</div>\n'})
            subprocess.run(["git", "mv", "templates/public/old.twig", "templates/public/new.twig"], cwd=tmpdir, check=True)
            with open(os.path.join(tmpdir, "templates/public/new.twig"), "a", encoding="utf-8") as f:
                f.write('<script>alert("xss")</script>\n')

            rc, _, _, out = run_hook(
                STOP_GATE,
                {"cwd": tmpdir, "workspacePaths": [tmpdir], "terminationReason": "NO_TOOL_CALL", "continuationActive": False},
                cwd=tmpdir,
            )
            self.assertEqual(rc, 0)
            self.assertEqual(out["decision"], "continue")
            self.assertIn("templates/public/new.twig", out["reason"])

    def test_stop_gate_expands_untracked_nested_directories(self):
        with tempfile.TemporaryDirectory() as tmpdir:
            init_temp_repo(tmpdir)
            nested = os.path.join(tmpdir, "templates", "public", "new_feature", "widget.twig")
            os.makedirs(os.path.dirname(nested), exist_ok=True)
            with open(nested, "w", encoding="utf-8") as f:
                f.write("<style>.x{display:block}</style>\n")

            rc, _, _, out = run_hook(
                STOP_GATE,
                {"cwd": tmpdir, "workspacePaths": [tmpdir], "terminationReason": "NO_TOOL_CALL", "continuationActive": False},
                cwd=tmpdir,
            )
            self.assertEqual(rc, 0)
            self.assertEqual(out["decision"], "continue")
            self.assertIn("templates/public/new_feature/widget.twig", out["reason"])


if __name__ == "__main__":
    unittest.main()
