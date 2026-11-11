#!/usr/bin/env python3
"""
Empirical Mutation Testing & Negative Sensitivity Verification Suite.
Author: Challenger 1 (Empirical Challenger)
Milestone: E2E-M2: Tier 1 Feature Coverage Suite

Verifies:
1. Negative sensitivity across 12 architectural domains:
   Intentionally mutates expectations/assertions, runs the test runner,
   confirms that the test suite FAILS with exit code 1 and accurate error messages.
2. Self-healing / reversion: confirms that restoring the original code restores exit code 0.
3. Fail-fast verification (-x / --fail-fast): confirms runner immediately halts on first failure.
"""

import json
import os
import subprocess
import sys
import time

PROJECT_ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", ".."))
RUNNER = os.path.join(PROJECT_ROOT, "tests", "e2e", "run_e2e_tests.py")


def log(msg: str):
    print(f"[CHALLENGER-MUTATION] {msg}")


class MutationTestEngine:
    def __init__(self):
        self.passed_mutations = 0
        self.failed_mutations = 0
        self.results = []

    def run_cmd(self, cmd: list[str]) -> tuple[int, str, str]:
        p = subprocess.run(cmd, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True, cwd=PROJECT_ROOT)
        return p.returncode, p.stdout, p.stderr

    def verify_mutation(
        self,
        name: str,
        feature_id: int,
        target_file: str,
        original_pattern: str,
        mutated_replacement: str
    ):
        full_path = os.path.join(PROJECT_ROOT, target_file)
        log(f"Testing Mutation: {name} (Feature {feature_id}) on {target_file}")

        with open(full_path, "r", encoding="utf-8") as f:
            content = f.read()

        if original_pattern not in content:
            raise RuntimeError(f"Original pattern not found in {target_file}:\n{original_pattern}")

        # 1. Apply mutation
        mutated_content = content.replace(original_pattern, mutated_replacement, 1)
        with open(full_path, "w", encoding="utf-8") as f:
            f.write(mutated_content)

        try:
            # 2. Run runner for this feature
            cmd = [
                sys.executable,
                RUNNER,
                "--tier", "1",
                "--feature", str(feature_id),
                "--no-network",
                "--format", "json"
            ]
            code, stdout, stderr = self.run_cmd(cmd)

            # 3. Assert runner reported FAILURE (exit code 1)
            if code != 1:
                log(f"❌ FAIL: Expected exit code 1 under mutation, got {code}!")
                self.failed_mutations += 1
                self.results.append({"name": name, "status": "FAILED", "reason": f"Exit code {code} != 1"})
                return

            try:
                data = json.loads(stdout)
                failed_count = data.get("summary", {}).get("failed", 0)
                error_count = data.get("summary", {}).get("errors", 0)
                is_success = data.get("summary", {}).get("is_success", True)
            except Exception as e:
                log(f"❌ FAIL: Output is not valid JSON: {e}\nStdout: {stdout[:200]}")
                self.failed_mutations += 1
                self.results.append({"name": name, "status": "FAILED", "reason": "Malformed JSON"})
                return

            if (failed_count + error_count) == 0 or is_success:
                log(f"❌ FAIL: Mutation did NOT trigger test failure! failed={failed_count}, errors={error_count}")
                self.failed_mutations += 1
                self.results.append({"name": name, "status": "FAILED", "reason": "No failures reported"})
                return

            log(f"✅ PASS: Mutation caught as expected (code 1, failed={failed_count}, errors={error_count})")

        finally:
            # 4. Revert mutation immediately
            with open(full_path, "w", encoding="utf-8") as f:
                f.write(content)

        # 5. Confirm baseline passes again after revert
        code_rev, stdout_rev, _ = self.run_cmd([
            sys.executable,
            RUNNER,
            "--tier", "1",
            "--feature", str(feature_id),
            "--no-network",
            "--format", "json"
        ])
        if code_rev != 0:
            log(f"❌ FATAL: Reversion failed! Feature {feature_id} does not pass baseline. code={code_rev}")
            self.failed_mutations += 1
            self.results.append({"name": name, "status": "FAILED", "reason": "Reversion failed"})
            return

        self.passed_mutations += 1
        self.results.append({"name": name, "status": "PASSED", "feature": feature_id})

    def verify_fail_fast(self):
        log("Testing --fail-fast (-x) behavior under mutation")
        target_file = os.path.join(PROJECT_ROOT, "tests/e2e/tier1/test_f01_orm_conjunctive_filters.py")
        with open(target_file, "r", encoding="utf-8") as f:
            content = f.read()

        orig = 'self.assertIn("id = 42", res["where"])'
        mutated = 'self.assertIn("FAIL_FAST_TRIGGER", res["where"])'
        if orig not in content:
            raise RuntimeError(f"Original pattern for fail-fast not found in {target_file}")

        mutated_content = content.replace(orig, mutated, 1)
        with open(target_file, "w", encoding="utf-8") as f:
            f.write(mutated_content)

        try:
            # Run without --fail-fast
            code_all, out_all, _ = self.run_cmd([
                sys.executable, RUNNER, "--tier", "1", "--feature", "1", "--no-network", "--format", "json"
            ])
            data_all = json.loads(out_all)
            total_executed_without_ff = len(data_all.get("tests", []))

            # Run with --fail-fast
            code_ff, out_ff, _ = self.run_cmd([
                sys.executable, RUNNER, "--tier", "1", "--feature", "1", "--no-network", "--fail-fast", "--format", "json"
            ])
            data_ff = json.loads(out_ff)
            total_executed_with_ff = len(data_ff.get("tests", []))

            log(f"Execution counts: without --fail-fast: {total_executed_without_ff}, with --fail-fast: {total_executed_with_ff}")
            # The test order in F01 has test_f01_add_eq_and_like_combination_conjunctive, test_f01_add_eq_filter_multiple_conjunctive_chaining, test_f01_add_eq_filter_single_column_numeric_and_string (which we mutated, 3rd)
            if total_executed_with_ff < total_executed_without_ff:
                log(f"✅ PASS: --fail-fast aborted execution immediately at {total_executed_with_ff} tests (vs {total_executed_without_ff})!")
                self.passed_mutations += 1
                self.results.append({"name": "Fail-Fast Halting", "status": "PASSED"})
            else:
                log(f"❌ FAIL: --fail-fast did not halt! with_ff={total_executed_with_ff}, without_ff={total_executed_without_ff}")
                self.failed_mutations += 1
                self.results.append({"name": "Fail-Fast Halting", "status": "FAILED"})
        finally:
            with open(target_file, "w", encoding="utf-8") as f:
                f.write(content)


def main():
    engine = MutationTestEngine()
    start_time = time.time()
    log("Starting Empirical Mutation Testing Suite...")

    # Mutation 1: F01 ORM Filtering
    engine.verify_mutation(
        name="ORM Conjunctive Filter Inversion",
        feature_id=1,
        target_file="tests/e2e/tier1/test_f01_orm_conjunctive_filters.py",
        original_pattern='self.assertIn("id = 42", res["where"])',
        mutated_replacement='self.assertIn("id = 99999", res["where"])'
    )

    # Mutation 2: F05 Leaf Category Check
    engine.verify_mutation(
        name="Leaf Category Check Creation Assertion Inversion",
        feature_id=5,
        target_file="tests/e2e/tier1/test_f05_leaf_category_check.py",
        original_pattern='self.assertTrue(data.get("created"))',
        mutated_replacement='self.assertFalse(data.get("created"))'
    )

    # Mutation 3: F06 Mandatory makeSeo
    engine.verify_mutation(
        name="Mandatory makeSeo Expected Slug Inversion",
        feature_id=6,
        target_file="tests/e2e/tier1/test_f06_mandatory_make_seo.py",
        original_pattern='self.assertEqual(slug, "Diyarbakır_Sur_Kültür_Festivali")',
        mutated_replacement='self.assertEqual(slug, "WRONG_SLUG_FAIL")'
    )

    # Mutation 4: F13 Shared HTTP Client SSRF Guard
    engine.verify_mutation(
        name="Editorial HTTP Status Code Inversion",
        feature_id=13,
        target_file="tests/e2e/tier1/test_f13_editorial_http.py",
        original_pattern='self.assertEqual(status_code, 200)',
        mutated_replacement='self.assertEqual(status_code, 500)'
    )

    # Mutation 5: F14 Freshness Sentinel TLS & SSRF
    engine.verify_mutation(
        name="Freshness Sentinel Passed Assertion Inversion",
        feature_id=14,
        target_file="tests/e2e/tier1/test_f14_freshness_tls_ssrf.py",
        original_pattern='self.assertTrue(data["passed"])',
        mutated_replacement='self.assertFalse(data["passed"])'
    )

    # Mutation 6: F19 Facts All Paragraphs Audit
    engine.verify_mutation(
        name="Facts All Paragraphs Present Paragraph Inversion",
        feature_id=19,
        target_file="tests/e2e/tier1/test_f19_facts_all_paragraphs.py",
        original_pattern='self.assertIn(2, indices, "Paragraph 2 must be present in url_audit")',
        mutated_replacement='self.assertIn(999, indices, "Mutated: paragraph 999 expected")'
    )

    # Mutation 7: F20 Facts Clean Numbers
    engine.verify_mutation(
        name="Facts Clean Number Filter Inversion",
        feature_id=20,
        target_file="tests/e2e/tier1/test_f20_facts_clean_numbers.py",
        original_pattern='self.assertNotIn("88", nums)',
        mutated_replacement='self.assertIn("88", nums)'
    )

    # Mutation 8: F24 Next.js Image Unwrap
    engine.verify_mutation(
        name="Next.js Image URL Unwrap Pattern Inversion",
        feature_id=24,
        target_file="tests/e2e/tier1/test_f24_image_nextjs_unwrap.py",
        original_pattern='self.assertEqual(unwrapped, "https://cdn.rudaw.net/images/park_lead.jpg")',
        mutated_replacement='self.assertEqual(unwrapped, "https://wrong.url.jpg")'
    )

    # Mutation 9: F28 Twig Hero Figure JSON-LD
    engine.verify_mutation(
        name="Twig Hero Figure Tag Inversion",
        feature_id=28,
        target_file="tests/e2e/tier1/test_f28_twig_hero_figure_jsonld.py",
        original_pattern='self.assertIn(\'<figure class="detail-hero">\', self.twig_content)',
        mutated_replacement='self.assertIn(\'<figure class="nonexistent-hero">\', self.twig_content)'
    )

    # Mutation 10: F29 MCP CLI Bridge Safe Mode
    engine.verify_mutation(
        name="MCP CLI Read Tool Error Inversion",
        feature_id=29,
        target_file="tests/e2e/tier1/test_f29_mcp_cli_bridge.py",
        original_pattern='self.assertFalse(data.get("isError", False))',
        mutated_replacement='self.assertTrue(data.get("isError", False))'
    )

    # Mutation 11: F31 SEO Alignment
    engine.verify_mutation(
        name="SEO Alignment Status Inversion",
        feature_id=31,
        target_file="tests/e2e/tier1/test_f31_audit_seo_alignment.py",
        original_pattern='self.assertEqual(data.get("status"), "PASSED")',
        mutated_replacement='self.assertEqual(data.get("status"), "FAILED")'
    )

    # Mutation 12: F35 Version Parity
    engine.verify_mutation(
        name="Version Parity Baseline Drift Mutation",
        feature_id=35,
        target_file="tests/e2e/tier1/test_f35_version_doc_update.py",
        original_pattern='self.assertIn(top_version, ["1.0.62", "1.0.63"])',
        mutated_replacement='self.assertIn(top_version, ["9.9.99"])'
    )

    # Fail-Fast test
    engine.verify_fail_fast()

    elapsed = time.time() - start_time
    log("=" * 60)
    log(f"Mutation Testing Completed in {elapsed:.2f}s")
    log(f"Passed: {engine.passed_mutations} / {engine.passed_mutations + engine.failed_mutations}")
    log(f"Failed: {engine.failed_mutations}")
    log("=" * 60)

    if engine.failed_mutations > 0:
        log("❌ SOME MUTATION TESTS FAILED!")
        sys.exit(1)
    else:
        log("🎉 ALL MUTATION AND NEGATIVE SENSITIVITY TESTS PASSED!")
        sys.exit(0)


if __name__ == "__main__":
    main()
