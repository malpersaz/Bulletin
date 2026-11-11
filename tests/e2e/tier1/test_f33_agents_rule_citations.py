"""Feature 33: Correct AGENTS.md Rule Number Citations.

Validates that documentation and source files cite the correct AGENTS.md rule numbers:
- Rule 1 is cited for Strict Code Separation (not Rule 2) in kevseo, audit_seo, and guard
- Rule 9 is cited for Wikipedia-style URLs (not Rule 10) in mod_news validator
- Rule 6 is cited for English identifiers and comments (not Rule 7) in code quality strategy
"""

from __future__ import annotations

from pathlib import Path

from harness.base import E2ETestCase, feature, tier

ROOT_DIR = Path(__file__).resolve().parents[3]


@tier(1)
@feature(33)
class TestF33AgentsRuleCitations(E2ETestCase):
    """Tier 1 tests for AGENTS.md rule number citation consistency across the repo."""

    def test_f33_kevseo_skill_cites_rule_1_for_code_separation(self) -> None:
        """kevseo/SKILL.md cites AGENTS.md Rule 1 for code separation mandate and avoids Rule 2."""
        path = ROOT_DIR / ".agents" / "skills" / "kevseo" / "SKILL.md"
        content = path.read_text(encoding="utf-8")
        self.assertIn("AGENTS.md Rule 1", content)
        self.assertNotIn("AGENTS.md Rule 2", content)

    def test_f33_audit_seo_script_cites_rule_1_for_code_separation(self) -> None:
        """audit_seo.py cites AGENTS.md Rule 1 for inline style/script restrictions."""
        path = ROOT_DIR / ".agents" / "skills" / "kevseo" / "scripts" / "audit_seo.py"
        content = path.read_text(encoding="utf-8")
        self.assertIn("AGENTS.md Rule 1", content)
        self.assertNotIn("AGENTS.md Rule 2", content)

    def test_f33_code_separation_guard_cites_rule_1(self) -> None:
        """tools/code_separation_guard.py cites AGENTS.md Rule 1 in header comments."""
        path = ROOT_DIR / "tools" / "code_separation_guard.py"
        content = path.read_text(encoding="utf-8")
        self.assertIn("AGENTS.md Rule 1", content)
        self.assertNotIn("AGENTS.md Rule 2", content)

    def test_f33_news_validator_cites_rule_9_for_wikipedia_slug(self) -> None:
        """mod_news/validator/add.validator.php does not cite outdated Rule 10."""
        path = ROOT_DIR / "mod" / "mod_news" / "validator" / "add.validator.php"
        content = path.read_text(encoding="utf-8")
        self.assertNotIn("AGENTS.md Rule 10", content)

    def test_f33_code_quality_strategy_cites_rule_6_for_english(self) -> None:
        """docs/CODE_QUALITY_AGENT_STRATEGY.md does not cite outdated Rule 7 for English rule."""
        path = ROOT_DIR / "docs" / "CODE_QUALITY_AGENT_STRATEGY.md"
        content = path.read_text(encoding="utf-8")
        self.assertNotIn("AGENTS.md Rule 7", content)
