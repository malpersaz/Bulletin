<?php

namespace E2E\Harness;

class TestRunner
{
    /** @var TestSuite[] */
    private array $suites = [];
    private bool $isJson = false;
    private ?int $targetTier = null;
    private ?string $filter = null;

    public function addSuite(TestSuite $suite): void
    {
        $this->suites[] = $suite;
    }

    public function setJsonMode(bool $json): void
    {
        $this->isJson = $json;
    }

    public function setTierFilter(?int $tier): void
    {
        $this->targetTier = $tier;
    }

    public function setNameFilter(?string $filter): void
    {
        $this->filter = $filter;
    }

    public function run(): int
    {
        $allResults = [];
        $startTime = microtime(true);
        $totalAssertions = 0;

        if (!$this->isJson) {
            echo "\033[1;36m========================================================================\033[0m\n";
            echo "\033[1;37m   AF/Bulletin Modernization & Synchronization — E2E Test Suite         \033[0m\n";
            echo "\033[1;36m========================================================================\033[0m\n\n";
            if ($this->targetTier !== null) {
                echo "\033[33mFiltering by Tier: " . $this->targetTier . "\033[0m\n";
            }
            if ($this->filter !== null) {
                echo "\033[33mFiltering by pattern: " . $this->filter . "\033[0m\n";
            }
            echo "\n";
        }

        foreach ($this->suites as $suite) {
            $testCase = $suite->getTestCase();
            if ($this->targetTier !== null && $testCase->getTier() !== $this->targetTier) {
                continue;
            }

            if (!$this->isJson) {
                echo sprintf("\033[1;34m[%s] (Tier %d)\033[0m\n", $testCase->getName(), $testCase->getTier());
            }

            $suiteResults = $suite->run($this->filter);
            foreach ($suiteResults as $result) {
                $allResults[] = $result;
                $totalAssertions += $result->assertions;

                if (!$this->isJson) {
                    if ($result->passed) {
                        echo sprintf("  \033[32m✔ [PASS]\033[0m %-60s \033[90m(%0.2f ms, %d assertions)\033[0m\n", $result->name, $result->durationMs, $result->assertions);
                    } else {
                        echo sprintf("  \033[31m✖ [FAIL]\033[0m %-60s \033[90m(%0.2f ms)\033[0m\n", $result->name, $result->durationMs);
                        echo sprintf("    \033[31mError: %s\033[0m\n", $result->errorMessage);
                        if ($result->errorTrace) {
                            echo sprintf("    \033[90mLocation: %s\033[0m\n", $result->errorTrace);
                        }
                    }
                }
            }

            if (!$this->isJson && count($suiteResults) > 0) {
                echo "\n";
            }
        }

        $totalDurationMs = (microtime(true) - $startTime) * 1000.0;
        $totalTests = count($allResults);
        $passedTests = count(array_filter($allResults, fn($r) => $r->passed));
        $failedTests = $totalTests - $passedTests;
        $passRate = $totalTests > 0 ? ($passedTests / $totalTests) * 100.0 : 0.0;

        if ($this->isJson) {
            $report = [
                'timestamp' => date('c'),
                'total_tests' => $totalTests,
                'passed_tests' => $passedTests,
                'failed_tests' => $failedTests,
                'pass_rate_percent' => round($passRate, 2),
                'total_assertions' => $totalAssertions,
                'duration_ms' => round($totalDurationMs, 2),
                'results' => array_map(function(TestResult $r) {
                    return [
                        'suite' => $r->suite,
                        'test' => $r->name,
                        'tier' => $r->tier,
                        'passed' => $r->passed,
                        'duration_ms' => round($r->durationMs, 2),
                        'assertions' => $r->assertions,
                        'error_message' => $r->errorMessage,
                        'error_trace' => $r->errorTrace,
                    ];
                }, $allResults)
            ];
            echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
        } else {
            echo "\033[1;36m------------------------------------------------------------------------\033[0m\n";
            echo "\033[1;37mE2E TEST RUN SUMMARY\033[0m\n";
            echo sprintf("  Total Tests:      \033[1;37m%d\033[0m\n", $totalTests);
            echo sprintf("  Passed:           \033[1;32m%d\033[0m\n", $passedTests);
            echo sprintf("  Failed:           \033[1;%sm%d\033[0m\n", $failedTests > 0 ? '31' : '32', $failedTests);
            echo sprintf("  Pass Rate:        \033[1;%sm%0.2f%%\033[0m\n", $failedTests > 0 ? '31' : '32', $passRate);
            echo sprintf("  Total Assertions: \033[1;37m%d\033[0m\n", $totalAssertions);
            echo sprintf("  Total Time:       \033[90m%0.2f ms\033[0m\n", $totalDurationMs);
            echo "\033[1;36m========================================================================\033[0m\n";
        }

        return $failedTests === 0 ? 0 : 1;
    }
}
