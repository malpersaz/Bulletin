#!/usr/bin/env python3
"""
Adversarial Stress Test Suite for E2E-M2 Tier 1 Feature Coverage Suite.
Author: Challenger 2 (Empirical Challenger)

Tests:
1. Test isolation: varying subsets (--feature 1-5, --feature 13,19,25,31, reverse, shuffled)
2. Mock HTTP server concurrency, socket closure, and thread shutdown cleanliness
3. Filesystem sandbox cleanup, read-only tree removal, traversal defense
4. Docker / PHP bridge boundary stress, temporary file cleanup, timeout enforcement
"""

import glob
import os
import random
import shutil
import stat
import sys
import tempfile
import threading
import time
import urllib.request

# Ensure project root is in sys.path
PROJECT_ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", ".."))
sys.path.insert(0, PROJECT_ROOT)

from tests.e2e.harness.mock_http import MockHttpServer
from tests.e2e.harness.php_bridge import PhpBridge
from tests.e2e.harness.process import run_cli
from tests.e2e.harness.runner import TestRunnerEngine
from tests.e2e.harness.sandbox import Sandbox


def test_section(title: str):
    print(f"\n{'='*70}\n[CHALLENGER-TEST] {title}\n{'='*70}")


def run_isolation_tests():
    test_section("1. Test Isolation & Order Dependency")
    engine = TestRunnerEngine(base_dir=os.path.join(PROJECT_ROOT, "tests", "e2e"))
    all_tier1 = engine.filter_tests(engine.discover_tests(), tier="1")
    print(f"Discovered {len(all_tier1)} Tier 1 tests.")

    # 1.1 Subset 1: Features 1-5
    f1_5 = engine.filter_tests(all_tier1, feature_filter="1-5")
    print(f"\n--- Running Subset: Features 1-5 ({len(f1_5)} tests) ---")
    summary1 = engine.run_tests(f1_5)
    print(f"Results: Passed={summary1.passed}, Failed={summary1.failed}, Errors={summary1.errors}")
    assert summary1.failed == 0 and summary1.errors == 0, f"Features 1-5 failed: {summary1.failed} failures"
    assert summary1.passed == len(f1_5), f"Expected {len(f1_5)} passed, got {summary1.passed}"
    print(">>> PASS: Features 1-5 subset passed with 100% isolation.")

    # 1.2 Subset 2: Disjoint features 13, 19, 25, 31
    f_disjoint = engine.filter_tests(all_tier1, feature_filter="13,19,25,31")
    print(f"\n--- Running Disjoint Subset: Features 13, 19, 25, 31 ({len(f_disjoint)} tests) ---")
    summary2 = engine.run_tests(f_disjoint)
    print(f"Results: Passed={summary2.passed}, Failed={summary2.failed}, Errors={summary2.errors}")
    assert summary2.failed == 0 and summary2.errors == 0, f"Disjoint subset failed: {summary2.failed} failures"
    assert summary2.passed == len(f_disjoint), f"Expected {len(f_disjoint)} passed, got {summary2.passed}"
    print(">>> PASS: Disjoint subset (13, 19, 25, 31) passed with 100% isolation.")

    # 1.3 Reverse Order Execution
    print(f"\n--- Running Complete Tier 1 in REVERSE Order ({len(all_tier1)} tests) ---")
    reversed_tests = list(reversed(all_tier1))
    summary_rev = engine.run_tests(reversed_tests)
    print(f"Results: Passed={summary_rev.passed}, Skipped={summary_rev.skipped}, Failed={summary_rev.failed}, Errors={summary_rev.errors}")
    assert summary_rev.failed == 0 and summary_rev.errors == 0, f"Reverse order execution failed: {summary_rev.failed} failures"
    assert summary_rev.passed == 186 and summary_rev.skipped == 1, "Reverse order pass count mismatch"
    print(">>> PASS: Reverse execution completed with 0 failures (zero forward order dependence).")

    # 1.4 Random Shuffled Order Execution
    print(f"\n--- Running Complete Tier 1 in SHUFFLED Order (seed=4242) ---")
    shuffled_tests = list(all_tier1)
    random.Random(4242).shuffle(shuffled_tests)
    summary_shuf = engine.run_tests(shuffled_tests)
    print(f"Results: Passed={summary_shuf.passed}, Skipped={summary_shuf.skipped}, Failed={summary_shuf.failed}, Errors={summary_shuf.errors}")
    assert summary_shuf.failed == 0 and summary_shuf.errors == 0, f"Shuffled order execution failed: {summary_shuf.failed} failures"
    assert summary_shuf.passed == 186 and summary_shuf.skipped == 1, "Shuffled order pass count mismatch"
    print(">>> PASS: Shuffled execution completed with 0 failures (order independence confirmed).")


def run_mock_http_stress():
    test_section("2. Mock HTTP Server Concurrency & Shutdown Cleanliness")

    initial_threads = threading.active_count()
    print(f"Baseline active threads: {initial_threads}")

    # 2.1 Sequential Start/Stop Cycle (Resource Leak Check)
    print("\n--- Testing 10 sequential start/stop cycles ---")
    ports_used = []
    for i in range(10):
        srv = MockHttpServer(port=0)
        url = srv.start()
        ports_used.append(srv.port)
        srv.add_route("/ping", "pong")
        with urllib.request.urlopen(f"{url}/ping", timeout=2) as resp:
            body = resp.read().decode()
            assert body == "pong"
        srv.stop()
        assert not srv.is_running
        assert srv.server is None
        assert srv.thread is None

    current_threads = threading.active_count()
    print(f"Threads after 10 sequential servers: {current_threads} (baseline={initial_threads})")
    assert current_threads <= initial_threads + 1, f"Thread leak detected: {current_threads} > {initial_threads}"
    print(">>> PASS: No thread leaks across sequential MockHttpServer cycles.")

    # 2.2 Concurrency Stress Test: 50 concurrent requests
    print("\n--- Concurrency stress: 50 simultaneous requests across 10 worker threads ---")
    server = MockHttpServer(port=0)
    base_url = server.start()
    server.add_route("/item", {"status": "ok", "tier": 1})

    success_count = [0]
    errors = []
    lock = threading.Lock()

    def worker(worker_id: int):
        for req_idx in range(5):
            try:
                with urllib.request.urlopen(f"{base_url}/item?w={worker_id}&r={req_idx}", timeout=5) as resp:
                    data = resp.read()
                    assert resp.status == 200
                    assert b'"status": "ok"' in data
                    with lock:
                        success_count[0] += 1
            except Exception as e:
                with lock:
                    errors.append(f"Worker {worker_id} req {req_idx} error: {e}")

    threads = [threading.Thread(target=worker, args=(i,)) for i in range(10)]
    t0 = time.perf_counter()
    for t in threads:
        t.start()
    for t in threads:
        t.join(timeout=10.0)
    dur = time.perf_counter() - t0

    server.stop()
    print(f"Processed {success_count[0]}/50 concurrent requests in {dur:.3f}s. Errors: {len(errors)}")
    assert len(errors) == 0, f"Mock HTTP concurrent errors: {errors}"
    assert success_count[0] == 50, f"Expected 50 successful requests, got {success_count[0]}"

    # Verify thread cleanup after stopping
    time.sleep(0.1)
    final_threads = threading.active_count()
    print(f"Threads after concurrent test teardown: {final_threads} (baseline={initial_threads})")
    assert final_threads <= initial_threads + 1, "Thread leak after concurrent test"
    print(">>> PASS: MockHttpServer handled 50 concurrent requests without error or thread leaks.")


def run_sandbox_stress():
    test_section("3. Filesystem Sandbox Behavior & Teardown Cleanup")

    # 3.1 Check /tmp for existing sandboxes
    before_sandboxes = glob.glob(os.path.join(tempfile.gettempdir(), "af_e2e_sb_*"))
    print(f"Pre-test /tmp/af_e2e_sb_* count: {len(before_sandboxes)}")

    # 3.2 Basic Sandbox Lifecycle
    sb = Sandbox()
    root = sb.create()
    assert os.path.isdir(root)
    file1 = sb.write_file("sub/test.txt", "hello sandbox")
    assert os.path.exists(file1)
    assert sb.read_file("sub/test.txt") == "hello sandbox"
    sb.cleanup()
    assert not os.path.exists(root), "Sandbox root still exists after cleanup!"
    print(">>> PASS: Basic sandbox directory created and cleanly wiped.")

    # 3.3 Traversal Attack Stress Test
    sb = Sandbox()
    sb.create()
    traversal_blocked = False
    try:
        sb.path("../../../etc/passwd")
    except ValueError as e:
        traversal_blocked = True
        print(f"Sandbox traversal attack properly rejected: {e}")
    sb.cleanup()
    assert traversal_blocked, "Sandbox allowed directory traversal outside root!"
    print(">>> PASS: Traversal attack rejected with ValueError.")

    # 3.4 Read-Only / Restricted Permissions Tree Cleanup Stress Test
    print("\n--- Testing Sandbox cleanup of read-only files and directories ---")
    sb = Sandbox()
    root = sb.create()
    # Create nested structure: root/readonly_dir/nested_dir/locked_file.txt
    locked_dir = sb.mkdir("readonly_dir", "nested_dir")
    locked_file = sb.write_file("readonly_dir/nested_dir/locked_file.txt", "protected content")
    # Make file read-only (chmod 0444)
    os.chmod(locked_file, stat.S_IRUSR | stat.S_IRGRP | stat.S_IROTH)
    # Make parent directory read-only & non-executable (chmod 0555)
    os.chmod(locked_dir, stat.S_IRUSR | stat.S_IXUSR)
    parent_dir = os.path.dirname(locked_dir)
    os.chmod(parent_dir, stat.S_IRUSR | stat.S_IXUSR)

    # Now attempt cleanup
    sb.cleanup()
    assert not os.path.exists(root), f"Sandbox root survived read-only cleanup: {root}"
    print(">>> PASS: Sandbox successfully wiped read-only tree without permission error.")

    # 3.5 Broken Symlink Cleanup Stress Test
    print("\n--- Testing Sandbox cleanup of broken and external symlinks ---")
    sb = Sandbox()
    root = sb.create()
    symlink_path = os.path.join(root, "broken_link")
    os.symlink("/nonexistent_target_path_for_challenger_test", symlink_path)
    assert os.path.islink(symlink_path)
    sb.cleanup()
    assert not os.path.exists(root) and not os.path.islink(root), "Sandbox root survived symlink cleanup!"
    print(">>> PASS: Sandbox successfully wiped broken symlink.")

    # 3.6 Check /tmp for leaked sandboxes after test run
    after_sandboxes = glob.glob(os.path.join(tempfile.gettempdir(), "af_e2e_sb_*"))
    print(f"Post-test /tmp/af_e2e_sb_* count: {len(after_sandboxes)} (initial={len(before_sandboxes)})")
    new_leaks = set(after_sandboxes) - set(before_sandboxes)
    assert len(new_leaks) == 0, f"Leaked sandbox directories in /tmp: {new_leaks}"
    print(">>> PASS: Zero sandbox directories leaked in /tmp.")


def run_php_bridge_stress():
    test_section("4. Docker / PHP Bridge Boundaries Under Stress")

    bridge = PhpBridge(project_root=PROJECT_ROOT)
    docker_ok = bridge.is_docker_available()
    print(f"Docker bulletin_app available: {docker_ok}")

    # 4.1 Rapid Sequential PHP Evaluations (Leak Check)
    print("\n--- Testing 20 rapid sequential eval_json calls ---")
    t0 = time.perf_counter()
    for i in range(20):
        val = bridge.eval_json(f"echo json_encode(['iteration' => {i}, 'sq' => {i * i}]);")
        assert val["iteration"] == i
        assert val["sq"] == i * i
    dur = time.perf_counter() - t0
    print(f"Completed 20 PHP evaluations in {dur:.3f}s ({dur/20*1000:.1f}ms/eval).")
    print(">>> PASS: Rapid sequential PHP evaluations completed successfully.")

    # 4.2 Error Handling & Temp File Cleanup on Fatal PHP Code
    print("\n--- Testing Temp File Cleanup on Fatal PHP Error ---")
    temp_dir = tempfile.gettempdir()
    php_temps_before = glob.glob(os.path.join(temp_dir, "tmp*.php"))

    caught_runtime_error = False
    try:
        bridge.eval_json("syntax error this is not php code;")
    except RuntimeError as e:
        caught_runtime_error = True
        assert "PHP evaluation failed" in str(e) or "Parse error" in str(e)
    assert caught_runtime_error, "Failed to catch syntax error in eval_json"

    php_temps_after = glob.glob(os.path.join(temp_dir, "tmp*.php"))
    leaked_temps = set(php_temps_after) - set(php_temps_before)
    assert len(leaked_temps) == 0, f"Leaked temporary PHP scripts: {leaked_temps}"
    print(">>> PASS: Fatal PHP error cleanly handled and temporary script removed.")

    # 4.3 Subprocess Timeout Enforcement
    print("\n--- Testing Subprocess Timeout Enforcement ---")
    t_start = time.perf_counter()
    res = bridge.run_php_code("sleep(5); echo 'done';", timeout=1.0)
    elapsed = time.perf_counter() - t_start
    print(f"Subprocess timeout returned in {elapsed:.3f}s with exit code {res.exit_code}")
    assert elapsed < 3.0, f"Process did not terminate within timeout: took {elapsed}s"
    assert res.exit_code != 0, "Timed out command should have non-zero exit code"
    print(">>> PASS: Subprocess timeout properly enforced without hanging.")


def main():
    print(f"Starting Challenger 2 Empirical Stress Test Suite at {time.strftime('%Y-%m-%dT%H:%M:%SZ', time.gmtime())}")
    t_start = time.perf_counter()

    # Step 1: Sandbox stress
    run_sandbox_stress()

    # Step 2: Mock HTTP stress
    run_mock_http_stress()

    # Step 3: PHP bridge stress
    run_php_bridge_stress()

    # Step 4: Full suite isolation, subsets, reverse, shuffled
    run_isolation_tests()

    total_time = time.perf_counter() - t_start
    print(f"\n{'='*70}\nALL CHALLENGER EMPIRICAL STRESS TESTS PASSED in {total_time:.2f}s!\n{'='*70}")


if __name__ == "__main__":
    main()
