"""Feature 34: Full Regression Suite & MCP Smoke Tests.

Validates regression testing and quality guards across the codebase:
- Core MCP PHP files pass syntax lint (php -l) without errors
- tools/verify_news_pipeline.sh passes all stages with valid editorial content
- tools/verify_news_pipeline.sh rejects missing mandatory arguments
- tools/module_boundary_guard.py reports zero boundary violations
- tools/code_separation_guard.py passes on core templates with zero violations
"""

from __future__ import annotations

import os
from pathlib import Path

from harness.base import E2ETestCase, feature, tier

ROOT_DIR = Path(__file__).resolve().parents[3]


@tier(1)
@feature(34)
class TestF34RegressionSuiteSmoke(E2ETestCase):
    """Tier 1 tests for full regression suite, smoke tests, and repository guards."""

    def test_f34_php_syntax_lint_all_mcp_and_task_files(self) -> None:
        """Core MCP tools and task PHP files have zero syntax errors (php -l)."""
        core_files = [
            "lib/McpServer.class.php",
            "lib/McpCrudToolsBase.class.php",
            "lib/McpNewsTools.class.php",
            "lib/McpFaqTools.class.php",
            "tasks/mcp_smoke.php",
        ]
        for f in core_files:
            res = self.run_cli(["docker", "exec", "bulletin_app", "php", "-l", f])
            self.assertExitCode(res, 0, f"PHP syntax lint failed on {f}")
            self.assertIn("No syntax errors detected", res.stdout)

    def test_f34_verify_news_pipeline_happy_path(self) -> None:
        """verify_news_pipeline.sh passes all stages when given compliant draft and dossier."""
        base_html = (
            ROOT_DIR
            / "tests"
            / "e2e"
            / "fixtures"
            / "editorial"
            / "draft_today_diyarbakir_park.html"
        ).read_text(encoding="utf-8")
        full_html = base_html + "<p>" + (
            "Diyarbakır kent merkezinde sürdürülen çevre düzenleme ve peyzaj çalışmaları kapsamında "
            "ekolojik park alanı vatandaşların dinlenme ve sosyal aktivite ihtiyaçlarına yanıt verecek "
            "modern donatılarla zenginleştirilmektedir. " * 3
        ) + "</p>"
        draft_path = self.sandbox.path("pipeline_valid_draft.html")
        with open(draft_path, "w", encoding="utf-8") as f:
            f.write(full_html)

        dossier_path = str(
            ROOT_DIR
            / "tests"
            / "e2e"
            / "fixtures"
            / "editorial"
            / "dossier_today_rudaw_park.json"
        )

        script_path = str(ROOT_DIR / "tools" / "verify_news_pipeline.sh")
        res = self.run_cli([
            "bash", script_path,
            "--no-network",
            "--draft", draft_path,
            "--dossier", dossier_path,
            "--headline", "Diyarbakır'da Ekolojik Kent Parkının İlk Etabı Açıldı",
            "--summary", "Diyarbakır merkezinde 45 dönümlük alanda inşa edilen ekolojik park bugün halkın hizmetine törenle açıldı.",
        ])
        self.assertExitCode(res, 0)
        self.assertIn("ALL PIPELINE VERIFICATION STAGES PASSED SUCCESSFULLY", res.stdout)

    def test_f34_verify_news_pipeline_rejects_missing_args(self) -> None:
        """verify_news_pipeline.sh exits with code 2 when invoked without required arguments."""
        script_path = str(ROOT_DIR / "tools" / "verify_news_pipeline.sh")
        res = self.run_cli(["bash", script_path])
        self.assertExitCode(res, 2)
        self.assertIn("Usage:", res.stdout)

    def test_f34_module_boundary_guard_zero_violations(self) -> None:
        """tools/module_boundary_guard.py passes full scan with zero forbidden module violations."""
        res = self.run_python(["tools/module_boundary_guard.py", "--all"])
        self.assertExitCode(res, 0)
        self.assertIn("Violations Found: 0", res.stdout)

    def test_f34_code_separation_on_core_templates(self) -> None:
        """tools/code_separation_guard.py reports zero violations on news.twig."""
        twig_path = "templates/amed/index/mod_news/news.twig"
        res = self.run_python(["tools/code_separation_guard.py", "--path", twig_path])
        self.assertExitCode(res, 0)
        self.assertIn("0 violations found", res.stdout)
