<?php

namespace E2E\Harness;

use ReflectionClass;
use ReflectionMethod;
use Throwable;

class TestSuite
{
    private TestCase $testCase;

    public function __construct(TestCase $testCase)
    {
        $this->testCase = $testCase;
    }

    public function getTestCase(): TestCase
    {
        return $this->testCase;
    }

    /**
     * @return TestResult[]
     */
    public function run(?string $filter = null): array
    {
        $results = [];
        $reflector = new ReflectionClass($this->testCase);
        $methods = $reflector->getMethods(ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            $methodName = $method->getName();
            if (!str_starts_with($methodName, 'test')) {
                continue;
            }

            $fullName = $this->testCase->getName() . '::' . $methodName;
            if ($filter !== null && stripos($fullName, $filter) === false) {
                continue;
            }

            $startCount = Assertions::getCount();
            $startTime = microtime(true);
            $passed = false;
            $errorMsg = null;
            $errorTrace = null;

            try {
                $this->testCase->setUp();
                $this->testCase->$methodName();
                $this->testCase->tearDown();
                $passed = true;
            } catch (Throwable $e) {
                $passed = false;
                $errorMsg = $e->getMessage();
                $errorTrace = $e->getFile() . ':' . $e->getLine();
                try {
                    $this->testCase->tearDown();
                } catch (Throwable $ignored) {}
            }

            $durationMs = (microtime(true) - $startTime) * 1000.0;
            $assertionsRan = Assertions::getCount() - $startCount;

            $results[] = new TestResult(
                $methodName,
                $this->testCase->getName(),
                $this->testCase->getTier(),
                $passed,
                $errorMsg,
                $errorTrace,
                $durationMs,
                $assertionsRan
            );
        }

        return $results;
    }
}
