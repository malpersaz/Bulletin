<?php

namespace E2E\Harness;

use Throwable;

abstract class TestCase
{
    abstract public function getTier(): int;
    abstract public function getName(): string;

    public function setUp(): void {}
    public function tearDown(): void {}

    protected function assertEquals(mixed $expected, mixed $actual, string $message = ''): void
    {
        Assertions::assertEquals($expected, $actual, $message);
    }

    protected function assertSame(mixed $expected, mixed $actual, string $message = ''): void
    {
        Assertions::assertSame($expected, $actual, $message);
    }

    protected function assertNotEquals(mixed $expected, mixed $actual, string $message = ''): void
    {
        Assertions::assertNotEquals($expected, $actual, $message);
    }

    protected function assertTrue(mixed $actual, string $message = ''): void
    {
        Assertions::assertTrue($actual, $message);
    }

    protected function assertFalse(mixed $actual, string $message = ''): void
    {
        Assertions::assertFalse($actual, $message);
    }

    protected function assertNull(mixed $actual, string $message = ''): void
    {
        Assertions::assertNull($actual, $message);
    }

    protected function assertNotNull(mixed $actual, string $message = ''): void
    {
        Assertions::assertNotNull($actual, $message);
    }

    protected function assertContains(mixed $needle, mixed $haystack, string $message = ''): void
    {
        Assertions::assertContains($needle, $haystack, $message);
    }

    protected function assertNotContains(mixed $needle, mixed $haystack, string $message = ''): void
    {
        Assertions::assertNotContains($needle, $haystack, $message);
    }

    protected function assertStringContainsString(string $needle, string $haystack, string $message = ''): void
    {
        Assertions::assertContains($needle, $haystack, $message);
    }

    protected function assertStringNotContainsString(string $needle, string $haystack, string $message = ''): void
    {
        Assertions::assertNotContains($needle, $haystack, $message);
    }

    protected function assertStringStartsWith(string $prefix, string $string, string $message = ''): void
    {
        Assertions::assertStringStartsWith($prefix, $string, $message);
    }

    protected function assertStringEndsWith(string $suffix, string $string, string $message = ''): void
    {
        Assertions::assertStringEndsWith($suffix, $string, $message);
    }

    protected function assertMatchesRegularExpression(string $pattern, string $string, string $message = ''): void
    {
        Assertions::assertMatchesRegularExpression($pattern, $string, $message);
    }

    protected function assertDoesNotMatchRegularExpression(string $pattern, string $string, string $message = ''): void
    {
        Assertions::assertDoesNotMatchRegularExpression($pattern, $string, $message);
    }

    protected function assertCount(int $expectedCount, mixed $countable, string $message = ''): void
    {
        Assertions::assertCount($expectedCount, $countable, $message);
    }

    protected function assertArrayHasKey(string|int $key, array $array, string $message = ''): void
    {
        Assertions::assertArrayHasKey($key, $array, $message);
    }

    protected function assertArrayNotHasKey(string|int $key, array $array, string $message = ''): void
    {
        Assertions::assertArrayNotHasKey($key, $array, $message);
    }

    protected function assertIsArray(mixed $actual, string $message = ''): void
    {
        $this->assertTrue(is_array($actual), $message ?: "Expected array");
    }

    protected function assertNotEmpty(mixed $actual, string $message = ''): void
    {
        $this->assertTrue(!empty($actual), $message ?: "Expected non-empty value");
    }

    protected function assertFileExists(string $filename, string $message = ''): void
    {
        Assertions::assertFileExists($filename, $message);
    }

    protected function assertFileDoesNotExist(string $filename, string $message = ''): void
    {
        Assertions::assertFileDoesNotExist($filename, $message);
    }

    protected function assertThrows(callable $callback, string $expectedExceptionClass = 'Throwable', string $message = ''): Throwable
    {
        return Assertions::assertThrows($callback, $expectedExceptionClass, $message);
    }
}
