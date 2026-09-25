<?php

namespace E2E\Harness;

use Exception;
use Throwable;

class AssertionException extends Exception {}

class Assertions
{
    private static int $assertionCount = 0;

    public static function resetCount(): void
    {
        self::$assertionCount = 0;
    }

    public static function getCount(): int
    {
        return self::$assertionCount;
    }

    public static function assertEquals(mixed $expected, mixed $actual, string $message = ''): void
    {
        self::$assertionCount++;
        if ($expected != $actual) {
            $msg = $message ?: sprintf("Failed asserting that %s matches expected %s", json_encode($actual), json_encode($expected));
            throw new AssertionException($msg);
        }
    }

    public static function assertSame(mixed $expected, mixed $actual, string $message = ''): void
    {
        self::$assertionCount++;
        if ($expected !== $actual) {
            $msg = $message ?: sprintf("Failed asserting that %s is strictly identical to %s", var_export($actual, true), var_export($expected, true));
            throw new AssertionException($msg);
        }
    }

    public static function assertNotEquals(mixed $expected, mixed $actual, string $message = ''): void
    {
        self::$assertionCount++;
        if ($expected == $actual) {
            $msg = $message ?: sprintf("Failed asserting that %s does not equal %s", json_encode($actual), json_encode($expected));
            throw new AssertionException($msg);
        }
    }

    public static function assertTrue(mixed $actual, string $message = ''): void
    {
        self::$assertionCount++;
        if ($actual !== true) {
            $msg = $message ?: sprintf("Failed asserting that %s is true", var_export($actual, true));
            throw new AssertionException($msg);
        }
    }

    public static function assertFalse(mixed $actual, string $message = ''): void
    {
        self::$assertionCount++;
        if ($actual !== false) {
            $msg = $message ?: sprintf("Failed asserting that %s is false", var_export($actual, true));
            throw new AssertionException($msg);
        }
    }

    public static function assertNull(mixed $actual, string $message = ''): void
    {
        self::$assertionCount++;
        if ($actual !== null) {
            $msg = $message ?: sprintf("Failed asserting that %s is null", var_export($actual, true));
            throw new AssertionException($msg);
        }
    }

    public static function assertNotNull(mixed $actual, string $message = ''): void
    {
        self::$assertionCount++;
        if ($actual === null) {
            $msg = $message ?: "Failed asserting that value is not null";
            throw new AssertionException($msg);
        }
    }

    public static function assertContains(mixed $needle, mixed $haystack, string $message = ''): void
    {
        self::$assertionCount++;
        if (is_array($haystack)) {
            if (!in_array($needle, $haystack, true)) {
                $msg = $message ?: sprintf("Failed asserting that array contains element %s", json_encode($needle));
                throw new AssertionException($msg);
            }
        } elseif (is_string($haystack)) {
            if (strpos($haystack, (string)$needle) === false) {
                $msg = $message ?: sprintf("Failed asserting that '%s' contains '%s'", $haystack, $needle);
                throw new AssertionException($msg);
            }
        } else {
            throw new AssertionException("assertContains target is neither array nor string");
        }
    }

    public static function assertNotContains(mixed $needle, mixed $haystack, string $message = ''): void
    {
        self::$assertionCount++;
        if (is_array($haystack)) {
            if (in_array($needle, $haystack, true)) {
                $msg = $message ?: sprintf("Failed asserting that array does not contain element %s", json_encode($needle));
                throw new AssertionException($msg);
            }
        } elseif (is_string($haystack)) {
            if (strpos($haystack, (string)$needle) !== false) {
                $msg = $message ?: sprintf("Failed asserting that '%s' does not contain '%s'", $haystack, $needle);
                throw new AssertionException($msg);
            }
        } else {
            throw new AssertionException("assertNotContains target is neither array nor string");
        }
    }

    public static function assertStringStartsWith(string $prefix, string $string, string $message = ''): void
    {
        self::$assertionCount++;
        if (!str_starts_with($string, $prefix)) {
            $msg = $message ?: sprintf("Failed asserting that '%s' starts with '%s'", $string, $prefix);
            throw new AssertionException($msg);
        }
    }

    public static function assertStringEndsWith(string $suffix, string $string, string $message = ''): void
    {
        self::$assertionCount++;
        if (!str_ends_with($string, $suffix)) {
            $msg = $message ?: sprintf("Failed asserting that '%s' ends with '%s'", $string, $suffix);
            throw new AssertionException($msg);
        }
    }

    public static function assertMatchesRegularExpression(string $pattern, string $string, string $message = ''): void
    {
        self::$assertionCount++;
        if (!preg_match($pattern, $string)) {
            $msg = $message ?: sprintf("Failed asserting that '%s' matches regular expression %s", $string, $pattern);
            throw new AssertionException($msg);
        }
    }

    public static function assertDoesNotMatchRegularExpression(string $pattern, string $string, string $message = ''): void
    {
        self::$assertionCount++;
        if (preg_match($pattern, $string)) {
            $msg = $message ?: sprintf("Failed asserting that '%s' does not match regular expression %s", $string, $pattern);
            throw new AssertionException($msg);
        }
    }

    public static function assertCount(int $expectedCount, mixed $countable, string $message = ''): void
    {
        self::$assertionCount++;
        $actualCount = is_countable($countable) ? count($countable) : 0;
        if ($actualCount !== $expectedCount) {
            $msg = $message ?: sprintf("Failed asserting that count %d matches expected %d", $actualCount, $expectedCount);
            throw new AssertionException($msg);
        }
    }

    public static function assertArrayHasKey(string|int $key, array $array, string $message = ''): void
    {
        self::$assertionCount++;
        if (!array_key_exists($key, $array)) {
            $msg = $message ?: sprintf("Failed asserting that array has key '%s'", $key);
            throw new AssertionException($msg);
        }
    }

    public static function assertArrayNotHasKey(string|int $key, array $array, string $message = ''): void
    {
        self::$assertionCount++;
        if (array_key_exists($key, $array)) {
            $msg = $message ?: sprintf("Failed asserting that array does not have key '%s'", $key);
            throw new AssertionException($msg);
        }
    }

    public static function assertFileExists(string $filename, string $message = ''): void
    {
        self::$assertionCount++;
        if (!file_exists($filename)) {
            $msg = $message ?: sprintf("Failed asserting that file '%s' exists", $filename);
            throw new AssertionException($msg);
        }
    }

    public static function assertFileDoesNotExist(string $filename, string $message = ''): void
    {
        self::$assertionCount++;
        if (file_exists($filename)) {
            $msg = $message ?: sprintf("Failed asserting that file '%s' does not exist", $filename);
            throw new AssertionException($msg);
        }
    }

    public static function assertThrows(callable $callback, string $expectedExceptionClass = 'Throwable', string $message = ''): Throwable
    {
        self::$assertionCount++;
        try {
            $callback();
        } catch (Throwable $e) {
            if ($e instanceof $expectedExceptionClass) {
                return $e;
            }
            $msg = $message ?: sprintf("Expected exception of type %s, but caught %s with message: %s", $expectedExceptionClass, get_class($e), $e->getMessage());
            throw new AssertionException($msg);
        }

        $msg = $message ?: sprintf("Expected exception of type %s was not thrown", $expectedExceptionClass);
        throw new AssertionException($msg);
    }
}
