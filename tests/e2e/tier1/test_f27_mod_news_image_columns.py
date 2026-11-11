"""Feature 27: mod_news image_alt & image_credit Columns.

Validates that mod_news table schema defines image_alt and image_credit columns,
and that McpNewsTools properly stores, retrieves, updates, and sanitizes these fields.
"""

from __future__ import annotations

import json
from pathlib import Path
import random

from harness.base import E2ETestCase, feature, tier


@tier(1)
@feature(27)
class TestF27ModNewsImageColumns(E2ETestCase):
    """Tier 1 tests for image_alt and image_credit in mod_news and MCP tools."""

    def test_f27_schema_columns_exist_in_sql(self) -> None:
        """SQL schema definition for mod_news defines image_alt and image_credit columns."""
        sql_path = Path(__file__).resolve().parents[3] / "mod" / "mod_news" / "mod_news.sql"
        content = sql_path.read_text(encoding="utf-8")

        self.assertIn("image_alt", content, "mod_news.sql must define image_alt")
        self.assertIn("image_credit", content, "mod_news.sql must define image_credit")

    def test_f27_mcp_create_news_with_media_credits(self) -> None:
        """createNews stores image_alt and image_credit on news record."""
        rnd = random.randint(1000, 9999)
        php_code = f"""<?php
        require 'init.php';
        require_once 'lib/McpCrudToolsBase.class.php';
        require_once 'lib/McpNewsTools.class.php';
        $db = Load::lib('Database');
        $tools = new McpNewsTools(1, ['admin', 'editor']);
        $created = $tools->createNews([
            'lang' => 'tr',
            'name' => 'Kredi Test Haberi {rnd}',
            'category_id' => 18,
            'summary' => 'Diyarbakır merkezinde hayata geçirilen çevre projeleri kent sakinleri tarafından ilgiyle karşılandı.',
            'content' => '<p>İçerik hakkında detaylar</p>',
            'title' => 'Başlık {rnd}',
            'keywords' => 'kredi, test',
            'description' => 'Açıklama',
            'image_alt' => 'Park Açılışı Fotoğrafı',
            'image_credit' => 'Fotoğraf: Amida Haber',
            'status' => 'n'
        ]);
        echo json_encode($created);
        $db->query('DELETE FROM mod_news WHERE id = ' . (int)$created['id']);
        """
        res = self.run_cli(["docker", "exec", "-i", "bulletin_app", "php"], input_data=php_code)
        self.assertExitCode(res, 0)
        data = json.loads(res.stdout.strip().splitlines()[-1])
        self.assertTrue(data.get("created"))
        self.assertIn("id", data)

    def test_f27_mcp_get_news_returns_media_credits(self) -> None:
        """getNews returns populated image_alt and image_credit fields."""
        rnd = random.randint(1000, 9999)
        php_code = f"""<?php
        require 'init.php';
        require_once 'lib/McpCrudToolsBase.class.php';
        require_once 'lib/McpNewsTools.class.php';
        $db = Load::lib('Database');
        $tools = new McpNewsTools(1, ['admin', 'editor']);
        $created = $tools->createNews([
            'lang' => 'tr',
            'name' => 'Kredi Get Test {rnd}',
            'category_id' => 18,
            'summary' => 'Diyarbakır merkezinde hayata geçirilen çevre projeleri kent sakinleri tarafından ilgiyle karşılandı.',
            'content' => '<p>İçerik</p>',
            'title' => 'Başlık {rnd}',
            'keywords' => 'kredi',
            'description' => 'Açıklama',
            'image_alt' => 'Ekolojik Park Kuşbakışı',
            'image_credit' => 'Fotoğraf: AA',
            'status' => 'n'
        ]);
        $got = $tools->getNews(['id' => $created['id']]);
        echo json_encode([
            'image_alt' => $got['item']['image_alt'],
            'image_credit' => $got['item']['image_credit']
        ]);
        $db->query('DELETE FROM mod_news WHERE id = ' . (int)$created['id']);
        """
        res = self.run_cli(["docker", "exec", "-i", "bulletin_app", "php"], input_data=php_code)
        self.assertExitCode(res, 0)
        data = json.loads(res.stdout.strip().splitlines()[-1])
        self.assertEqual(data.get("image_alt"), "Ekolojik Park Kuşbakışı")
        self.assertEqual(data.get("image_credit"), "Fotoğraf: AA")

    def test_f27_mcp_update_news_modifies_media_credits(self) -> None:
        """updateNews updates image_credit cleanly on existing record."""
        rnd = random.randint(1000, 9999)
        php_code = f"""<?php
        require 'init.php';
        require_once 'lib/McpCrudToolsBase.class.php';
        require_once 'lib/McpNewsTools.class.php';
        $db = Load::lib('Database');
        $tools = new McpNewsTools(1, ['admin', 'editor']);
        $created = $tools->createNews([
            'lang' => 'tr',
            'name' => 'Kredi Update Test {rnd}',
            'category_id' => 18,
            'summary' => 'Diyarbakır merkezinde hayata geçirilen çevre projeleri kent sakinleri tarafından ilgiyle karşılandı.',
            'content' => '<p>İçerik</p>',
            'title' => 'Başlık {rnd}',
            'keywords' => 'kredi',
            'description' => 'Açıklama',
            'image_alt' => 'İlk Alt',
            'image_credit' => 'Fotoğraf: İlk',
            'status' => 'n'
        ]);
        $tools->updateNews([
            'id' => $created['id'],
            'image_credit' => 'Wêne: Rûdaw'
        ]);
        $got = $tools->getNews(['id' => $created['id']]);
        echo json_encode(['image_credit' => $got['item']['image_credit']]);
        $db->query('DELETE FROM mod_news WHERE id = ' . (int)$created['id']);
        """
        res = self.run_cli(["docker", "exec", "-i", "bulletin_app", "php"], input_data=php_code)
        self.assertExitCode(res, 0)
        data = json.loads(res.stdout.strip().splitlines()[-1])
        self.assertEqual(data.get("image_credit"), "Wêne: Rûdaw")

    def test_f27_media_credits_xss_sanitization(self) -> None:
        """Raw HTML script tags in image_alt are sanitized before persistence."""
        rnd = random.randint(1000, 9999)
        php_code = f"""<?php
        require 'init.php';
        require_once 'lib/McpCrudToolsBase.class.php';
        require_once 'lib/McpNewsTools.class.php';
        $db = Load::lib('Database');
        $tools = new McpNewsTools(1, ['admin', 'editor']);
        $created = $tools->createNews([
            'lang' => 'tr',
            'name' => 'Kredi XSS Test {rnd}',
            'category_id' => 18,
            'summary' => 'Diyarbakır merkezinde hayata geçirilen çevre projeleri kent sakinleri tarafından ilgiyle karşılandı.',
            'content' => '<p>İçerik</p>',
            'title' => 'Başlık {rnd}',
            'keywords' => 'kredi',
            'description' => 'Açıklama',
            'image_alt' => '<script>alert(1)</script>Park',
            'image_credit' => 'Fotoğraf: AA',
            'status' => 'n'
        ]);
        $got = $tools->getNews(['id' => $created['id']]);
        echo json_encode(['image_alt' => $got['item']['image_alt']]);
        $db->query('DELETE FROM mod_news WHERE id = ' . (int)$created['id']);
        """
        res = self.run_cli(["docker", "exec", "-i", "bulletin_app", "php"], input_data=php_code)
        self.assertExitCode(res, 0)
        data = json.loads(res.stdout.strip().splitlines()[-1])
        self.assertNotIn("<script>", data.get("image_alt", ""))
        self.assertIn("Park", data.get("image_alt", ""))
