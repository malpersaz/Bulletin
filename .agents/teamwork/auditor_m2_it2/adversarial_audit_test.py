#!/usr/bin/env python3
"""
Adversarial Verification Suite by Forensic Auditor M2-IT2
"""

import sys
import os
import subprocess
import json

sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), "../../skills/_shared")))
import editorial_http as eh

print("=== AUDITOR ADVERSARIAL TEST 1: Port Boundaries ===")
port_cases = [
    ("http://8.8.8.8:0/", False),
    ("http://8.8.8.8:1/", True),
    ("http://8.8.8.8:65535/", True),
    ("http://8.8.8.8:65536/", False),
    ("http://8.8.8.8:99999999999999999999999999/", False),
    ("http://8.8.8.8:80xyz/", False),
    ("http://8.8.8.8:!@#$/", False),
    ("http://8.8.8.8:null/", False),
    ("http://8.8.8.8:-999/", False),
    ("http://8.8.8.8:-1/", False),
    ("http://[::1]:70000/", False),
]

for url, expected_valid in port_cases:
    val, reason = eh.validate_public_url(url)
    assert val == expected_valid, f"URL {url} expected {expected_valid}, got {val} (reason: {reason})"
    if not expected_valid:
        assert len(reason) > 0, f"Expected non-empty failure reason for {url}"
    print(f"  [PASS] {url} -> valid={val}, reason={reason}")

print("\n=== AUDITOR ADVERSARIAL TEST 2: RFC 1035 Domain & Label Boundaries ===")
l63 = "a" * 63 + ".com"
v63, r63 = eh.validate_public_url(f"http://{l63}/")
assert "exceeds length limit" not in r63, f"Label of 63 chars should not exceed limit: {r63}"
print(f"  [PASS] Label 63 chars -> not rejected by length limit (reason: {r63})")

l64 = "a" * 64 + ".com"
v64, r64 = eh.validate_public_url(f"http://{l64}/")
assert v64 is False, "Label of 64 chars must be rejected"
assert "exceeds length limit" in r64, f"Expected length limit in reason, got {r64}"
print(f"  [PASS] Label 64 chars -> correctly rejected: {r64}")

long_url = "http://" + "x" * 100000 + ".org/"
v_long, r_long = eh.validate_public_url(long_url)
assert v_long is False
assert "exceeds length limit" in r_long
print(f"  [PASS] 100k chars hostname -> correctly rejected without crash: {r_long[:60]}...")

unicode_url = "http://" + "ö" * 64 + ".de/"
v_u, r_u = eh.validate_public_url(unicode_url)
assert v_u is False
print(f"  [PASS] Unicode 64 chars label -> correctly rejected: {r_u[:60]}...")

print("\n=== AUDITOR ADVERSARIAL TEST 3: Downstream Kevrast verify_facts_and_links.py ===")
kevrast_cases = [
    ("bad port 65536", "http://8.8.8.8:65536/"),
    ("bad port huge", "http://8.8.8.8:99999999999999999999/"),
    ("bad label 64", "http://" + "b" * 64 + ".com/"),
    ("bad bracket", "http://[invalid_host]:80/"),
    ("ssrf loopback", "http://127.0.0.1:8080/"),
    ("ssrf metadata", "http://169.254.169.254/latest/meta-data/"),
]

for name, target_url in kevrast_cases:
    draft_html = f'<p>Haber metni: <a href="{target_url}">kaynak baglantisi</a> burada.</p>'
    proc = subprocess.run(
        ["python3", ".agents/skills/kevrast/scripts/verify_facts_and_links.py", "--text", draft_html, "--json"],
        capture_output=True,
        text=True,
    )
    assert proc.returncode == 1, f"Expected returncode 1 for {name}, got {proc.returncode}"
    assert "Traceback" not in proc.stderr, f"Traceback leaked for {name}: {proc.stderr}"
    data = json.loads(proc.stdout)
    assert data["passed"] is False, f"Expected passed=False for {name}"
    assert data["status"] == "BLOCKED", f"Expected status=BLOCKED for {name}"
    audit_entry = data["url_audit"][0]
    assert audit_entry["reachable"] is False, f"Expected reachable=False for {name}"
    unreach_reason = audit_entry.get("unreachable_reason")
    print(f"  [PASS] Kevrast handled {name} cleanly -> exit 1, status=BLOCKED, unreachable_reason={unreach_reason}")

print("\n=== AUDITOR ADVERSARIAL TEST 4: Downstream Kevtemen check_freshness.py ===")
kevtemen_cases = [
    ("bad port 70000", "http://8.8.8.8:70000/"),
    ("bad port 65536", "http://8.8.8.8:65536/"),
    ("ssrf loopback", "http://127.0.0.1:8080/"),
    ("non-numeric port", "http://8.8.8.8:abc/"),
]

for name, target_url in kevtemen_cases:
    proc = subprocess.run(
        ["python3", ".agents/skills/kevtemen/scripts/check_freshness.py", target_url, "--json"],
        capture_output=True,
        text=True,
    )
    assert proc.returncode == 1, f"Expected returncode 1 for {name}, got {proc.returncode}"
    data = json.loads(proc.stdout)
    assert data["status"] in ("REJECTED", "BLOCKED")
    target_item = data["targets"][0]
    target_state = target_item.get("state")
    assert target_state == "unreachable", f"Expected state=unreachable for {name}, got {target_state}"
    assert target_item["same_day"] is False
    target_note = target_item.get("note")
    print(f"  [PASS] Kevtemen handled {name} cleanly -> state=unreachable, note={target_note}")

print("\nALL AUDITOR ADVERSARIAL STRESS TESTS PASSED EMPIRICALLY!")
