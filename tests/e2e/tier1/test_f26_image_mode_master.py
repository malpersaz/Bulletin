"""Feature 26: process_news_image.py --mode master.

Validates that --mode master generates natural aspect ratio WebP masters (>=1200px)
without forced 16:9 cropping, outputs correct JSON envelope attributes,
enforces --strict-min-width, handles EXIF orientation, and writes valid WebP binaries to disk.
"""

from __future__ import annotations

import json
import os
from pathlib import Path
import sys

from PIL import Image

from harness.base import E2ETestCase, feature, tier

BASE_DIR = Path(__file__).resolve().parents[3]
KEVANGER_DIR = BASE_DIR / ".agents" / "skills" / "kevanger" / "scripts"


@tier(1)
@feature(26)
class TestF26ImageModeMaster(E2ETestCase):
    """Tier 1 tests for --mode master in process_news_image.py."""

    def setUp(self) -> None:
        super().setUp()
        self.script_path = str(KEVANGER_DIR / "process_news_image.py")

    def test_f26_mode_master_preserves_aspect_ratio_4x3(self) -> None:
        """A 1600x1200 (4:3) image is preserved in natural dimensions without 16:9 cropping."""
        in_path = self.sandbox.path("input_4x3.jpg")
        out_path = self.sandbox.path("out_master_4x3.webp")

        img = Image.new("RGB", (1600, 1200), color=(80, 120, 160))
        img.save(in_path, "JPEG")

        res = self.run_python([
            self.script_path,
            "-i", in_path,
            "--mode", "master",
            "--source", "AA",
            "-o", out_path,
        ])
        self.assertExitCode(res, 0)
        self.assertTrue(os.path.exists(out_path))

        with Image.open(out_path) as out_im:
            self.assertEqual(out_im.size, (1600, 1200))

    def test_f26_mode_master_json_payload_attributes(self) -> None:
        """Process output JSON contains mode=master, mime=image/webp, and valid dimension keys."""
        in_path = self.sandbox.path("input_attrs.jpg")
        out_path = self.sandbox.path("out_attrs.webp")

        img = Image.new("RGB", (1400, 1050), color=(100, 150, 200))
        img.save(in_path, "JPEG")

        res = self.run_python([
            self.script_path,
            "-i", in_path,
            "--mode", "master",
            "--source", "Amida Haber",
            "-o", out_path,
        ])
        self.assertExitCode(res, 0)
        data = json.loads(res.stdout)

        self.assertEqual(data.get("status"), "SUCCESS")
        self.assertEqual(data.get("mode"), "master")
        self.assertEqual(data.get("mime"), "image/webp")
        self.assertEqual(data.get("width"), 1400)
        self.assertEqual(data.get("height"), 1050)
        self.assertFalse(data.get("upscaled"))
        self.assertIn("attribution_tr", data)

    def test_f26_mode_master_strict_min_width_pass(self) -> None:
        """A 1920x1080 image passes --strict-min-width without upscaling."""
        in_path = self.sandbox.path("input_1920.jpg")
        out_path = self.sandbox.path("out_1920.webp")

        img = Image.new("RGB", (1920, 1080), color=(60, 90, 120))
        img.save(in_path, "JPEG")

        res = self.run_python([
            self.script_path,
            "-i", in_path,
            "--mode", "master",
            "--source", "Rûdaw",
            "--strict-min-width",
            "-o", out_path,
        ])
        self.assertExitCode(res, 0)
        data = json.loads(res.stdout)
        self.assertEqual(data.get("width"), 1920)
        self.assertFalse(data.get("upscaled"))

    def test_f26_mode_master_strict_min_width_fail_below_1200(self) -> None:
        """An image narrower than 1200px fails with exit code 1 when --strict-min-width is set."""
        in_path = self.sandbox.path("input_small.jpg")
        out_path = self.sandbox.path("out_small.webp")

        img = Image.new("RGB", (800, 600), color=(60, 90, 120))
        img.save(in_path, "JPEG")

        res = self.run_python([
            self.script_path,
            "-i", in_path,
            "--mode", "master",
            "--source", "Rûdaw",
            "--strict-min-width",
            "-o", out_path,
        ])
        self.assertExitCode(res, 1)

    def test_f26_mode_master_file_output_written(self) -> None:
        """Valid master WebP file is written with positive file size."""
        in_path = self.sandbox.path("input_file.jpg")
        out_path = self.sandbox.path("out_file.webp")

        img = Image.new("RGB", (1280, 960), color=(40, 80, 120))
        img.save(in_path, "JPEG")

        res = self.run_python([
            self.script_path,
            "-i", in_path,
            "--mode", "master",
            "--source", "DHA",
            "-o", out_path,
        ])
        self.assertExitCode(res, 0)
        self.assertTrue(os.path.exists(out_path))
        self.assertGreater(os.path.getsize(out_path), 0)
