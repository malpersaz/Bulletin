#!/usr/bin/env python3
"""
Bug Remediation Verification Script for Challenger M2-1
Verifies unhandled exceptions in validate_public_url() and downstream crash in verify_facts_and_links.py are resolved.
"""

import json
import os
import subprocess
import sys

sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), "../.agents/skills/_shared")))
import editorial_http as eh

def test_reproduce_port_out_of_range():
    print("--- Bug 1: Port out of range (70000) ---")
    val, reason = eh.validate_public_url("http://example.com:70000/")
    assert val is False, f"Expected False for port 70000, got {val}"
    assert "Invalid port" in reason or "out of range" in reason
    print("PASSED: validate_public_url returned (False, reason) without ValueError:", reason)

def test_reproduce_port_non_numeric():
    print("\n--- Bug 2: Non-numeric port (abc) ---")
    val, reason = eh.validate_public_url("http://example.com:abc/")
    assert val is False, f"Expected False for port abc, got {val}"
    assert "Invalid port" in reason
    print("PASSED: validate_public_url returned (False, reason) without ValueError:", reason)

def test_reproduce_negative_port():
    print("\n--- Bug 3: Negative port (-1) ---")
    val, reason = eh.validate_public_url("http://example.com:-1/")
    assert val is False, f"Expected False for port -1, got {val}"
    assert "Invalid port" in reason or "out of range" in reason
    print("PASSED: validate_public_url returned (False, reason) without ValueError:", reason)

def test_reproduce_oversized_label_idna():
    print("\n--- Bug 4: Oversized domain label (IDNA UnicodeEncodeError) ---")
    val, reason = eh.validate_public_url("http://" + "a" * 10000 + ".com/")
    assert val is False, f"Expected False for oversized domain, got {val}"
    assert len(reason) > 0
    print("PASSED: validate_public_url returned (False, reason) without UnicodeEncodeError:", reason[:80] + "...")

def test_reproduce_downstream_crash():
    print("\n--- Bug 5: Downstream crash in verify_facts_and_links.py ---")
    draft = '<p>Son dakika: <a href="http://example.com:70000/">resmi aciklama</a> yapildi.</p>'
    res = subprocess.run(
        ["python3", ".agents/skills/kevrast/scripts/verify_facts_and_links.py", "--text", draft, "--json"],
        capture_output=True,
        text=True,
    )
    print("Return code:", res.returncode)
    assert res.returncode == 1, f"Expected exit code 1, got {res.returncode}"
    assert "ValueError" not in res.stderr
    assert "Traceback" not in res.stderr
    data = json.loads(res.stdout)
    assert data["passed"] is False
    assert data["status"] == "BLOCKED"
    assert data["url_audit"][0]["reachable"] is False
    assert "Invalid port" in data["url_audit"][0]["unreachable_reason"]
    print("PASSED: verify_facts_and_links emitted valid JSON report without tracebacks!")

if __name__ == "__main__":
    test_reproduce_port_out_of_range()
    test_reproduce_port_non_numeric()
    test_reproduce_negative_port()
    test_reproduce_oversized_label_idna()
    test_reproduce_downstream_crash()
    print("\nALL 5 BUGS VERIFIED RESOLVED.")
