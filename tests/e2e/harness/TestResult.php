<?php

namespace E2E\Harness;

class TestResult
{
    public string $name;
    public string $suite;
    public int $tier;
    public bool $passed;
    public ?string $errorMessage = null;
    public ?string $errorTrace = null;
    public float $durationMs = 0.0;
    public int $assertions = 0;

    public function __construct(string $name, string $suite, int $tier, bool $passed, ?string $errorMessage = null, ?string $errorTrace = null, float $durationMs = 0.0, int $assertions = 0)
    {
        $this->name = $name;
        $this->suite = $suite;
        $this->tier = $tier;
        $this->passed = $passed;
        $this->errorMessage = $errorMessage;
        $this->errorTrace = $errorTrace;
        $this->durationMs = $durationMs;
        $this->assertions = $assertions;
    }
}
