"""Feature 32: Skill Documentation Synchronization.

Validates that all five repository editorial skills:
- Have valid YAML frontmatter matching their directory names
- Document key architectural contracts (mcp_cli.py, --mode master, Europe/Istanbul fail-closed gate, Stage 1/2 fact checks, and --scan-today radar)
"""

from __future__ import annotations

from pathlib import Path

from harness.base import E2ETestCase, feature, tier


@tier(1)
@feature(32)
class TestF32SkillDocsSync(E2ETestCase):
    """Tier 1 tests for skill markdown documentation synchronization."""

    def setUp(self) -> None:
        super().setUp()
        self.skills_dir = Path(__file__).resolve().parents[3] / ".agents" / "skills"

    def test_f32_skills_yaml_frontmatter_validity(self) -> None:
        """All five editorial skills contain valid YAML frontmatter with matching name."""
        skills = ["kevanger", "kevtemen", "kevkolin", "kevrast", "kevseo"]
        for s in skills:
            skill_path = self.skills_dir / s / "SKILL.md"
            self.assertTrue(skill_path.exists(), f"Skill file {skill_path} must exist")
            text = skill_path.read_text(encoding="utf-8")
            parts = text.split("---")
            self.assertGreaterEqual(len(parts), 3, f"SKILL.md for {s} must contain YAML frontmatter")
            frontmatter_lines = parts[1].strip().splitlines()
            front_dict = {}
            for line in frontmatter_lines:
                if ":" in line:
                    k, v = line.split(":", 1)
                    front_dict[k.strip()] = v.strip()

            self.assertEqual(front_dict.get("name"), s, f"Skill name in frontmatter must match directory name {s}")
            self.assertGreater(len(front_dict.get("description", "")), 20, f"Description for {s} must be substantial")

    def test_f32_kevanger_skill_documents_mcp_cli_and_master_image(self) -> None:
        """kevanger/SKILL.md documents mcp_cli.py and --mode master image pipeline."""
        content = (self.skills_dir / "kevanger" / "SKILL.md").read_text(encoding="utf-8")
        self.assertIn("mcp_cli.py", content, "kevanger must document mcp_cli.py")
        self.assertIn("--mode master", content, "kevanger must document --mode master")

    def test_f32_kevtemen_skill_documents_fail_closed_gate(self) -> None:
        """kevtemen/SKILL.md documents fail-closed gate and Europe/Istanbul calendar date."""
        content = (self.skills_dir / "kevtemen" / "SKILL.md").read_text(encoding="utf-8")
        self.assertIn("fail-closed", content.lower(), "kevtemen must document fail-closed behavior")
        self.assertIn("Europe/Istanbul", content, "kevtemen must document Europe/Istanbul timezone")

    def test_f32_kevrast_skill_documents_two_pass_and_all_paragraphs(self) -> None:
        """kevrast/SKILL.md documents Stage 1 (dossier) and Stage 2 (draft claim verification)."""
        content = (self.skills_dir / "kevrast" / "SKILL.md").read_text(encoding="utf-8")
        self.assertIn("Stage 1", content, "kevrast must document Stage 1 dossier gate")
        self.assertIn("Stage 2", content, "kevrast must document Stage 2 draft verification")

    def test_f32_kevkolin_skill_documents_scan_today_radar(self) -> None:
        """kevkolin/SKILL.md documents --scan-today feed radar discovery."""
        content = (self.skills_dir / "kevkolin" / "SKILL.md").read_text(encoding="utf-8")
        self.assertIn("--scan-today", content, "kevkolin must document --scan-today radar")
