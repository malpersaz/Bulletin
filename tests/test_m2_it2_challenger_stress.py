#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Empirical Adversarial Stress Test Suite for Gate Conformance & Cross-Feature Interactions
Milestone 2 Iteration 2 (Challenger M2-IT2-2)

Targets:
- .agents/skills/kevtemen/scripts/check_freshness.py
- .agents/skills/kevrast/scripts/verify_facts_and_links.py

Coverage:
1. Multi-paragraph hallucination detection (P1 clean, P4 fake number, exit codes, diagnostics).
2. Freshness sentinel ISO fractional seconds, UTC-to-Istanbul conversion, and near-midnight diagnostics.
3. Mixed drafts with valid claims and malformed URLs (ports out of range, non-numeric, negative, oversized labels, malformed IPv6).
4. Subpath / prefix URL matching empirical challenge analysis.
"""

import json
import os
import subprocess
import sys
import tempfile
import unittest
from datetime import datetime, timedelta, timezone
from zoneinfo import ZoneInfo

# Add shared transport and script locations to sys.path
_REPO_ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), ".."))
sys.path.insert(0, os.path.join(_REPO_ROOT, ".agents/skills/_shared"))
sys.path.insert(0, os.path.join(_REPO_ROOT, ".agents/skills/kevtemen/scripts"))
sys.path.insert(0, os.path.join(_REPO_ROOT, ".agents/skills/kevrast/scripts"))

import editorial_http as eh
import check_freshness as cf
import verify_facts_and_links as vf

ISTANBUL = ZoneInfo("Europe/Istanbul")


class TestAllParagraphHallucinations(unittest.TestCase):
    """
    Challenge Task 1: Verify that verify_facts_and_links.py catches all-paragraph hallucinations
    (e.g., P1 clean, P4 fake number) and produces proper exit codes and diagnostics.
    """

    def setUp(self):
        self.url_primary = "https://ajansamed.com.tr/haber/2026/11/fidan-kampanyasi-987"
        self.url_secondary = "https://ajansamed.com.tr/haber/2026/11/park-bahceler-raporu-123"
        
        # Valid target 1: contains 15000 fidan and Bağlar
        target1_html = (
            "<html><head><title>Fidan Kampanyasi</title></head><body><article>"
            "<p>Diyarbakir Buyuksehir Belediyesi Baglar ilcesinde 15000 fidan dikti.</p>"
            + (" " * 600) +
            "</article></body></html>"
        )
        # Valid target 2: contains 250 park and Kayapınar
        target2_html = (
            "<html><head><title>Park Raporu</title></head><body><article>"
            "<p>Kayapinar ilcesinde 250 yeni park acildi.</p>"
            + (" " * 600) +
            "</article></body></html>"
        )
        eh.set_cached_response(self.url_primary, 200, target1_html, {"x-final-url": self.url_primary})
        eh.set_cached_response(self.url_secondary, 200, target2_html, {"x-final-url": self.url_secondary})

    def test_p1_clean_p4_fake_number_blocks_and_exits_1(self):
        """P1 cites URL with accurate figure (15 bin -> 15000), P4 cites same URL with hallucinated figure (99999)."""
        draft = f"""
        <p>Büyükşehir Belediyesi <a href="{self.url_primary}">açıklamasına göre</a> 15 bin fidan dikildi.</p>
        <p>Ekipler kentin dört bir yanında çalışmalarını aralıksız sürdürüyor.</p>
        <p>Fidan dikim seferberliğine vatandaşlar ve sivil toplum kuruluşları da destek veriyor.</p>
        <p>Ayrıca aynı çalışmada <a href="{self.url_primary}">belirtildiği üzere</a> 99999 ağaç kesildi.</p>
        """
        res = subprocess.run(
            ["python3", ".agents/skills/kevrast/scripts/verify_facts_and_links.py", "--text", draft, "--json", "--explain"],
            capture_output=True,
            text=True,
            cwd=_REPO_ROOT,
        )
        self.assertEqual(res.returncode, 1, f"Expected exit code 1 on hallucination, got {res.returncode}")
        self.assertNotIn("Traceback", res.stderr)
        
        data = json.loads(res.stdout)
        self.assertFalse(data["passed"])
        self.assertEqual(data["status"], "BLOCKED")
        self.assertEqual(len(data["url_audit"]), 2, "Expected 2 audit evaluations for the 2 citing blocks")
        
        # P1 must pass anchoring
        p1_entry = next((e for e in data["url_audit"] if e.get("paragraph_index") == 1), None)
        self.assertIsNotNone(p1_entry)
        self.assertEqual(p1_entry["anchoring"]["status"], "pass")
        
        # P4 must be blocked on anchoring
        p4_entry = next((e for e in data["url_audit"] if e.get("paragraph_index") == 4), None)
        self.assertIsNotNone(p4_entry)
        self.assertEqual(p4_entry["anchoring"]["status"], "block")
        self.assertIn("99999", p4_entry["anchoring"]["missing_numbers"])
        
        # Explain diagnostics must identify P4 missing number
        diag_p4 = next((d for d in data.get("diagnostics", []) if d.get("paragraph_index") == 4), None)
        self.assertIsNotNone(diag_p4)
        self.assertEqual(diag_p4["code"], "R3_MISSING_NUMBER")
        self.assertIn("99999", diag_p4["detail"])

    def test_p1_clean_p4_clean_passes_and_exits_0(self):
        """Both P1 and P4 cite URL with valid figures (15 bin and 15000)."""
        draft = f"""
        <p>Büyükşehir Belediyesi <a href="{self.url_primary}">açıklamasına göre</a> 15 bin fidan dikildi.</p>
        <p>Çalışmalar tüm hızıyla devam ediyor.</p>
        <p>Kent genelinde yeni fidan alanları açılıyor.</p>
        <p>Ekipler <a href="{self.url_primary}">bildirildiği üzere</a> toplam 15000 fidanı toprakla buluşturdu.</p>
        """
        res = subprocess.run(
            ["python3", ".agents/skills/kevrast/scripts/verify_facts_and_links.py", "--text", draft, "--json"],
            capture_output=True,
            text=True,
            cwd=_REPO_ROOT,
        )
        self.assertEqual(res.returncode, 0, f"Expected exit code 0 for clean draft, got {res.returncode}")
        data = json.loads(res.stdout)
        self.assertTrue(data["passed"])
        self.assertEqual(data["status"], "PASSED")

    def test_distinct_urls_p1_clean_p4_hallucinated_blocks(self):
        """P1 cites URL1 (clean), P4 cites URL2 with hallucinated figure (77777)."""
        draft = f"""
        <p>Büyükşehir Belediyesi <a href="{self.url_primary}">açıklamasına göre</a> 15 bin fidan dikildi.</p>
        <p>Çalışmalar devam ediyor.</p>
        <p>Park çalışmaları genişletiliyor.</p>
        <p>İlçe raporuna göre <a href="{self.url_secondary}">aktarıldığı üzere</a> 77777 park yapıldı.</p>
        """
        res = subprocess.run(
            ["python3", ".agents/skills/kevrast/scripts/verify_facts_and_links.py", "--text", draft, "--json"],
            capture_output=True,
            text=True,
            cwd=_REPO_ROOT,
        )
        self.assertEqual(res.returncode, 1)
        data = json.loads(res.stdout)
        self.assertFalse(data["passed"])
        self.assertEqual(data["status"], "BLOCKED")

    def test_entity_hallucination_in_p4_blocks(self):
        """P1 has valid entity (Bağlar), P4 cites same URL with fake entities (Kadıköy and Çankaya) and no figures."""
        draft = f"""
        <p>Büyükşehir Belediyesi <a href="{self.url_primary}">açıklamasına göre</a> Bağlar ilçesinde çalışma yapıldı.</p>
        <p>Çalışmalar devam ediyor.</p>
        <p>Yeni merkezler açılıyor.</p>
        <p>Projede <a href="{self.url_primary}">belirtildiği üzere</a> Kadıköy ve Çankaya semtlerinde bakım yapıldı.</p>
        """
        res = subprocess.run(
            ["python3", ".agents/skills/kevrast/scripts/verify_facts_and_links.py", "--text", draft, "--json"],
            capture_output=True,
            text=True,
            cwd=_REPO_ROOT,
        )
        self.assertEqual(res.returncode, 1)
        data = json.loads(res.stdout)
        self.assertFalse(data["passed"])
        p4_entry = next((e for e in data["url_audit"] if e.get("paragraph_index") == 4), None)
        self.assertIsNotNone(p4_entry)
        self.assertEqual(p4_entry["anchoring"]["status"], "block")

    def test_exact_normalized_number_rejects_substring(self):
        """Target has '150000'. Paragraph has '500'. '500' must NOT match '150000' as substring."""
        target_html = (
            "<html><head><title>Test</title></head><body><article>"
            "<p>Toplam 150000 kisi katildi.</p>"
            + (" " * 600) +
            "</article></body></html>"
        )
        test_url = "https://ajansamed.com.tr/haber/2026/11/tam-sayi-kontrol-001"
        eh.set_cached_response(test_url, 200, target_html, {"x-final-url": test_url})

        draft = f"<p>Açıklamada <a href=\"{test_url}\">belirtildiğine göre</a> 500 kişi katıldı.</p>"
        res = subprocess.run(
            ["python3", ".agents/skills/kevrast/scripts/verify_facts_and_links.py", "--text", draft, "--json"],
            capture_output=True,
            text=True,
            cwd=_REPO_ROOT,
        )
        self.assertEqual(res.returncode, 1)
        data = json.loads(res.stdout)
        self.assertFalse(data["passed"])
        self.assertIn("500", data["url_audit"][0]["anchoring"]["missing_numbers"])


class TestFreshnessSentinelIsoAndNearMidnight(unittest.TestCase):
    """
    Challenge Task 2: Verify that check_freshness.py correctly parses ISO fractional seconds,
    converts UTC to Europe/Istanbul today, and detects near-midnight attributes.
    """

    def test_iso_fractional_seconds_parsing(self):
        """Verify parse_iso_or_free preserves UTC conversion and supports fractional seconds."""
        cases = [
            ("2026-11-11T12:00:00.000Z", 2026, 11, 11, 15, 0),
            ("2026-11-11T12:00:00.123456Z", 2026, 11, 11, 15, 0),
            ("2026-11-11T12:00:00,789Z", 2026, 11, 11, 15, 0),
            ("2026-11-11T15:30:00.500+03:00", 2026, 11, 11, 15, 30),
            ("2026-11-11T07:30:00.999-05:00", 2026, 11, 11, 15, 30),
        ]
        for raw, exp_y, exp_m, exp_d, exp_h, exp_mi in cases:
            dt = cf.parse_iso_or_free(raw)
            self.assertIsNotNone(dt, f"Failed to parse {raw}")
            self.assertEqual((dt.year, dt.month, dt.day, dt.hour, dt.minute),
                             (exp_y, exp_m, exp_d, exp_h, exp_mi),
                             f"Mismatch for {raw}: got {dt}")
            self.assertEqual(str(dt.tzinfo), "Europe/Istanbul")

    def test_utc_converts_to_istanbul_today(self):
        """
        Published yesterday in UTC at 22:30:00Z.
        In Europe/Istanbul (+03:00), this is TODAY at 01:30:00.
        Must evaluate to same_day == True!
        """
        now_ist = cf.now_istanbul()
        today_ist = now_ist.date()
        yesterday_ist = today_ist - timedelta(days=1)
        
        # Construct UTC timestamp: yesterday_ist at 22:00:00 UTC -> today_ist at 01:00:00 Istanbul
        utc_ts = f"{yesterday_ist.strftime('%Y-%m-%d')}T22:00:00.000Z"
        html = f'<html><head><meta property="article:published_time" content="{utc_ts}"></head><body></body></html>'
        
        res = cf.check_target("test_utc_today", "file", html, "", None)
        self.assertTrue(res["same_day"], f"Expected same_day True for UTC crossing midnight into Istanbul today: {res}")
        self.assertEqual(res["pub_date"], today_ist.strftime("%d.%m.%Y"))

    def test_utc_converts_to_yesterday_near_midnight(self):
        """
        Published yesterday in UTC at 20:15:00Z.
        In Europe/Istanbul (+03:00), this is YESTERDAY at 23:15:00 (18 <= hour <= 23).
        Must evaluate to same_day == False AND is_yesterday_late_evening == True!
        """
        now_ist = cf.now_istanbul()
        today_ist = now_ist.date()
        yesterday_ist = today_ist - timedelta(days=1)
        
        # 20:15 UTC = 23:15 Istanbul (yesterday late evening)
        utc_ts = f"{yesterday_ist.strftime('%Y-%m-%d')}T20:15:00.000Z"
        html = f'<html><head><meta property="article:published_time" content="{utc_ts}"></head><body></body></html>'
        
        res = cf.check_target("test_near_midnight", "file", html, "", None)
        self.assertFalse(res["same_day"])
        self.assertTrue(res.get("is_yesterday_late_evening"), f"Expected is_yesterday_late_evening True, got: {res}")
        self.assertEqual(res["pub_date"], yesterday_ist.strftime("%d.%m.%Y"))

    def test_utc_converts_to_yesterday_afternoon(self):
        """
        Published yesterday in UTC at 14:00:00Z.
        In Europe/Istanbul (+03:00), this is YESTERDAY at 17:00:00 (hour < 18).
        Must evaluate to same_day == False AND is_yesterday_late_evening == False!
        """
        now_ist = cf.now_istanbul()
        today_ist = now_ist.date()
        yesterday_ist = today_ist - timedelta(days=1)
        
        utc_ts = f"{yesterday_ist.strftime('%Y-%m-%d')}T14:00:00.000Z"
        html = f'<html><head><meta property="article:published_time" content="{utc_ts}"></head><body></body></html>'
        
        res = cf.check_target("test_afternoon", "file", html, "", None)
        self.assertFalse(res["same_day"])
        self.assertFalse(res.get("is_yesterday_late_evening"), f"Expected is_yesterday_late_evening False for hour 17: {res}")

    def test_human_readable_near_midnight_diagnostic(self):
        """Verify human-readable output prints near-midnight diagnostic suffix."""
        now_ist = cf.now_istanbul()
        yesterday_ist = now_ist.date() - timedelta(days=1)
        utc_ts = f"{yesterday_ist.strftime('%Y-%m-%d')}T20:30:00.000Z"
        
        with tempfile.NamedTemporaryFile("w", suffix=".html", delete=False) as f:
            f.write(f'<html><head><meta property="article:published_time" content="{utc_ts}"></head><body></body></html>')
            tmp_path = f.name
            
        try:
            res = subprocess.run(
                ["python3", ".agents/skills/kevtemen/scripts/check_freshness.py", tmp_path],
                capture_output=True,
                text=True,
                cwd=_REPO_ROOT,
            )
            self.assertEqual(res.returncode, 1)
            self.assertIn("yesterday late-evening near-midnight", res.stdout)
            self.assertIn("BLOCKED: Content is stale", res.stdout)
        finally:
            if os.path.exists(tmp_path):
                os.remove(tmp_path)

    def test_jsonld_scoped_earliest_date_priority(self):
        """JSON-LD NewsArticle datePublished with fractional seconds; earliest date is selected."""
        html = """
        <html><head>
        <meta property="article:published_time" content="2026-11-11T10:00:00.000Z">
        <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "NewsArticle",
            "headline": "Test Headline",
            "datePublished": "2026-11-11T09:00:00.123Z",
            "dateModified": "2026-11-11T12:00:00.456Z"
        }
        </script>
        </head><body><article><p>Content</p></article></body></html>
        """
        pub = cf.extract_pub_date(html, "https://example.com/item")
        self.assertIsNotNone(pub)
        # 09:00 UTC = 12:00 Istanbul; 10:00 UTC = 13:00 Istanbul. Earliest is 09:00 UTC (12:00 Istanbul).
        self.assertEqual(pub["dt"].hour, 12)
        self.assertIn("09:00:00.123", pub["evidence"])

    def test_non_content_tags_stripped_before_date_scan(self):
        """Dates inside <nav> or <aside> must not be selected over article text."""
        now_ist = cf.now_istanbul()
        today_str = now_ist.strftime("%d.%m.%Y")
        
        html = f"""
        <html><body>
        <nav><p>Eski haber arsivi: 01.01.2020</p></nav>
        <article>
            <p>Guncel haber: {today_str}</p>
        </article>
        </body></html>
        """
        pub = cf.extract_pub_date(html, "https://example.com/article/test")
        self.assertIsNotNone(pub)
        self.assertEqual(pub["dt"].date(), now_ist.date())


class TestMalformedUrlsAndValidClaimsCombinations(unittest.TestCase):
    """
    Challenge Task 3: Test combinations of malformed URLs and valid claims in the same draft.
    Verifies that invalid ports, bad hostnames, and malformed syntax do not cause unhandled tracebacks
    and produce expected structured failure reports.
    """

    def setUp(self):
        self.valid_url = "https://ajansamed.com.tr/haber/2026/11/fidan-kampanyasi-987"
        target_html = (
            "<html><head><title>Fidan Kampanyasi</title><meta property=\"article:published_time\" content=\"2026-11-11T10:00:00.000Z\"></head><body><article>"
            "<p>Diyarbakir genelinde 15000 fidan dikildi.</p>"
            + (" " * 600) +
            "</article></body></html>"
        )
        eh.set_cached_response(self.valid_url, 200, target_html, {"x-final-url": self.valid_url})

    def _test_kevrast_combination(self, bad_url: str, expected_reason_fragment: str = ""):
        draft = f"""
        <p>Diyarbakır Büyükşehir Belediyesi <a href="{self.valid_url}">açıklamasına göre</a> 15 bin fidan dikildi.</p>
        <p>Ayrıca diğer kaynakta <a href="{bad_url}">belirtildiği üzere</a> çalışmalar genişletildi.</p>
        """
        res = subprocess.run(
            ["python3", ".agents/skills/kevrast/scripts/verify_facts_and_links.py", "--text", draft, "--json"],
            capture_output=True,
            text=True,
            cwd=_REPO_ROOT,
        )
        self.assertEqual(res.returncode, 1, f"Expected returncode 1 for {bad_url}, got {res.returncode}")
        self.assertNotIn("Traceback", res.stderr, f"Traceback leaked for {bad_url}: {res.stderr}")
        
        data = json.loads(res.stdout)
        self.assertFalse(data["passed"])
        self.assertEqual(data["status"], "BLOCKED")
        self.assertEqual(len(data["url_audit"]), 2)
        
        # First URL should be live and reachable
        aud_valid = next(a for a in data["url_audit"] if a["url"] == self.valid_url)
        self.assertTrue(aud_valid["liveness"]["live"])
        self.assertEqual(aud_valid["anchoring"]["status"], "pass")
        
        # Bad URL should be unreachable or imprecise
        aud_bad = next(a for a in data["url_audit"] if a["url"] == bad_url)
        self.assertFalse(aud_bad.get("reachable", False))
        if expected_reason_fragment:
            reason = str(aud_bad.get("unreachable_reason") or aud_bad.get("precision", {}).get("note"))
            self.assertIn(expected_reason_fragment.lower(), reason.lower())

    def test_kevrast_mixed_valid_and_port_70000(self):
        self._test_kevrast_combination("http://example.com:70000/haber", "port")

    def test_kevrast_mixed_valid_and_port_non_numeric(self):
        self._test_kevrast_combination("http://example.com:abc/haber", "port")

    def test_kevrast_mixed_valid_and_port_negative(self):
        self._test_kevrast_combination("http://example.com:-1/haber", "port")

    def test_kevrast_mixed_valid_and_oversized_label(self):
        self._test_kevrast_combination("http://" + ("a" * 70) + ".com/haber", "label")

    def test_kevrast_mixed_valid_and_malformed_ipv6(self):
        self._test_kevrast_combination("http://[invalid-ipv6/haber")

    def test_kevrast_mixed_valid_and_malformed_with_explain(self):
        """Verify that --explain runs without crashing on drafts with malformed URLs."""
        draft = f"""
        <p>Diyarbakır Büyükşehir Belediyesi <a href="{self.valid_url}">açıklamasına göre</a> 15 bin fidan dikildi.</p>
        <p>Ayrıca diğer kaynakta <a href="http://example.com:70000/haber">belirtildiği üzere</a> çalışmalar genişletildi.</p>
        """
        res = subprocess.run(
            ["python3", ".agents/skills/kevrast/scripts/verify_facts_and_links.py", "--text", draft, "--json", "--explain"],
            capture_output=True,
            text=True,
            cwd=_REPO_ROOT,
        )
        self.assertEqual(res.returncode, 1)
        self.assertNotIn("Traceback", res.stderr)
        data = json.loads(res.stdout)
        self.assertFalse(data["passed"])
        self.assertIn("diagnostics", data)
        # Should have R1_LIVENESS_FAIL diagnostic for bad URL
        bad_diag_codes = [d["code"] for d in data["diagnostics"] if d["url"] == "http://example.com:70000/haber"]
        self.assertIn("R1_LIVENESS_FAIL", bad_diag_codes)
        liveness_diag = next(d for d in data["diagnostics"] if d["url"] == "http://example.com:70000/haber" and d["code"] == "R1_LIVENESS_FAIL")
        self.assertIn("Invalid port", liveness_diag["detail"])

    def test_kevtemen_mixed_valid_and_port_70000(self):
        """Verify that check_freshness.py cleanly handles mixed valid and malformed URLs."""
        draft = f"""
        <p>Diyarbakır Büyükşehir Belediyesi <a href="{self.valid_url}">açıklamasına göre</a> fidan dikildi.</p>
        <p>Diğer kaynak: <a href="http://example.com:70000/haber">link</a></p>
        """
        res = subprocess.run(
            ["python3", ".agents/skills/kevtemen/scripts/check_freshness.py", "--text", draft, "--json"],
            capture_output=True,
            text=True,
            cwd=_REPO_ROOT,
        )
        self.assertEqual(res.returncode, 1)
        self.assertNotIn("Traceback", res.stderr)
        data = json.loads(res.stdout)
        self.assertFalse(data["passed"])
        
        # Valid URL is dated and same_day True
        t_valid = next(t for t in data["targets"] if t["target"] == self.valid_url)
        self.assertEqual(t_valid["state"], "dated")
        self.assertTrue(t_valid["same_day"])
        
        # Bad URL is marked unreachable
        t_bad = next(t for t in data["targets"] if t["target"] == "http://example.com:70000/haber")
        self.assertEqual(t_bad["state"], "unreachable")


class TestUrlPrefixCollisionObservation(unittest.TestCase):
    """
    Empirical Observation Test: Subpath / Prefix URL Matching in _find_all_citing_blocks.
    Stress-testing whether 'url in block' in _find_all_citing_blocks causes a paragraph
    citing a subpath URL (e.g. /news/diyarbakir/kayapinar) to be falsely evaluated against
    its parent URL (e.g. /news/diyarbakir).
    """

    def test_subpath_url_collision_empirical_trace(self):
        """Demonstrate that parent URL falsely matches child URL paragraph if 'url in block' is evaluated."""
        auditor = vf.KevrastAuditor()
        draft = """
        <p>Diyarbakır genelinde çalışmalar sürüyor: <a href="https://example.org/news/diyarbakir">genel haber</a>.</p>
        <p>Kayapınar ilçesinde 500 fidan dikildi: <a href="https://example.org/news/diyarbakir/kayapinar">ilçe haberi</a>.</p>
        """
        citing_parent = auditor._find_all_citing_blocks("https://example.org/news/diyarbakir", draft)
        citing_child = auditor._find_all_citing_blocks("https://example.org/news/diyarbakir/kayapinar", draft)

        parent_indices = [c[0] for c in citing_parent]
        child_indices = [c[0] for c in citing_child]

        # Empirical observation: P2 cited ONLY child URL, but parent_indices contains [1, 2]
        self.assertEqual(child_indices, [2])
        self.assertEqual(parent_indices, [1, 2],
                         "Empirical confirmation: 'url in block' causes parent URL to falsely claim P2")


if __name__ == "__main__":
    unittest.main()
