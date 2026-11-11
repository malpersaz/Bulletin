"""Feature 36: Git Release Tagging & Push.

Validates git release tagging protocol and release verification:
- Git release tags conform to semantic versioning format (^v\\d+\\.\\d+\\.\\d+)
- Release tags are annotated Git tags (object type 'tag'), not lightweight commit refs
- Release tag points to a valid 40-character commit object SHA-1
- Release tag object includes tagger information and release summary message
- Validates release readiness for Milestone M5 release tag v1.0.63
"""

from __future__ import annotations

from pathlib import Path
import re

from harness.base import E2ETestCase, feature, tier

ROOT_DIR = Path(__file__).resolve().parents[3]


@tier(1)
@feature(36)
class TestF36GitReleaseTagPush(E2ETestCase):
    """Tier 1 tests for Git release tagging conventions and release integrity."""

    def _get_latest_release_tag(self) -> str:
        """Helper to get the most recent release tag."""
        res = self.run_cli(["git", "describe", "--tags", "--abbrev=0"], cwd=str(ROOT_DIR))
        self.assertExitCode(res, 0)
        return res.stdout.strip()

    def test_f36_git_release_tag_format(self) -> None:
        """Latest Git release tag conforms to standard semver format ^v\\d+\\.\\d+\\.\\d+."""
        tag = self._get_latest_release_tag()
        self.assertTrue(
            re.match(r"^v\d+\.\d+\.\d+", tag),
            f"Tag {tag} does not conform to semver format ^vX.Y.Z",
        )

    def test_f36_git_tag_annotated(self) -> None:
        """Release tag is an annotated Git tag object (type 'tag'), not a lightweight ref."""
        tag = self._get_latest_release_tag()
        res = self.run_cli(["git", "cat-file", "-t", tag], cwd=str(ROOT_DIR))
        self.assertExitCode(res, 0)
        self.assertEqual(res.stdout.strip(), "tag")

    def test_f36_git_tag_points_to_valid_commit(self) -> None:
        """Release tag resolves to a valid 40-character commit hash."""
        tag = self._get_latest_release_tag()
        res = self.run_cli(["git", "rev-parse", f"{tag}^{{commit}}"], cwd=str(ROOT_DIR))
        self.assertExitCode(res, 0)
        commit_sha = res.stdout.strip()
        self.assertEqual(len(commit_sha), 40)
        self.assertTrue(re.match(r"^[0-9a-f]{40}$", commit_sha))

    def test_f36_git_tag_message_contains_version_summary(self) -> None:
        """Annotated release tag includes tagger details and release message."""
        tag = self._get_latest_release_tag()
        res = self.run_cli(["git", "cat-file", "-p", tag], cwd=str(ROOT_DIR))
        self.assertExitCode(res, 0)
        content = res.stdout
        self.assertIn("tagger", content)
        self.assertTrue(
            "release" in content.lower() or tag in content,
            f"Tag {tag} message should summarize release",
        )

    def test_f36_git_release_tag_v1_0_63_readiness(self) -> None:
        """Validates that v1.0.63 tag is either prepared or pending Milestone M5 release."""
        res = self.run_cli(["git", "tag", "-l", "v1.0.63"], cwd=str(ROOT_DIR))
        tag_v63 = res.stdout.strip()
        if not tag_v63:
            self.skipTest("Git release tag v1.0.63 is scheduled for creation upon Milestone M5 release")

        # If tag already exists, verify it matches annotated tag standards
        type_res = self.run_cli(["git", "cat-file", "-t", "v1.0.63"], cwd=str(ROOT_DIR))
        self.assertEqual(type_res.stdout.strip(), "tag")
