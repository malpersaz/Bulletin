<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\CodeQuality\Rector\Class_\CompleteDynamicPropertiesRector;
use Rector\Php73\Rector\FuncCall\SetCookieRector;
use Rector\Php54\Rector\Array_\LongArrayToShortArrayRector;
use Rector\CodeQuality\Rector\Include_\AbsolutizeRequireAndIncludePathRector;
use Rector\CodeQuality\Rector\If_\CombineIfRector;
use Rector\CodeQuality\Rector\If_\CompleteMissingIfElseBracketRector;
use Rector\CodeQuality\Rector\If_\ConsecutiveNullCompareReturnsToNullCoalesceQueueRector;
use Rector\CodeQuality\Rector\ClassConstFetch\ConvertStaticPrivateConstantToSelfRector;
use Rector\CodeQuality\Rector\If_\ExplicitBoolCompareRector;
// use Rector\CodeQuality\Rector\ClassMethod\ExplicitReturnNullRector;
use Rector\CodeQuality\Rector\Identical\FlipTypeControlToUseExclusiveTypeRector;
use Rector\CodeQuality\Rector\For_\ForRepeatedCountToOwnVariableRector;
use Rector\CodeQuality\Rector\Foreach_\ForeachItemsAssignToEmptyArrayToAssignRector;
use Rector\CodeQuality\Rector\Foreach_\ForeachToInArrayRector;
use Rector\CodeQuality\Rector\ClassMethod\InlineArrayReturnAssignRector;
use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\CodeQuality\Rector\ClassMethod\OptionalParametersAfterRequiredRector;
use Rector\CodeQuality\Rector\BooleanAnd\RemoveUselessIsObjectCheckRector;
use Rector\CodeQuality\Rector\BooleanNot\ReplaceMultipleBooleanNotRector;
use Rector\CodeQuality\Rector\If_\ShortenElseIfRector;
use Rector\CodeQuality\Rector\Identical\SimplifyConditionsRector;
use Rector\CodeQuality\Rector\BooleanAnd\SimplifyEmptyArrayCheckRector;
use Rector\CodeQuality\Rector\Empty_\SimplifyEmptyCheckOnEmptyArrayRector;
use Rector\CodeQuality\Rector\If_\SimplifyIfElseToTernaryRector;
use Rector\CodeQuality\Rector\If_\SimplifyIfNotNullReturnRector;
use Rector\CodeQuality\Rector\FuncCall\SimplifyRegexPatternRector;
use Rector\CodeQuality\Rector\FuncCall\SimplifyStrposLowerRector;
use Rector\CodeQuality\Rector\FunctionLike\SimplifyUselessVariableRector;
use Rector\CodeQuality\Rector\Ternary\UnnecessaryTernaryExpressionRector;
use Rector\CodeQuality\Rector\Equal\UseIdenticalOverEqualWithSameTypeRector;
use Rector\CodingStyle\Rector\FuncCall\CountArrayToEmptyArrayComparisonRector;
use Rector\CodingStyle\Rector\If_\NullableCompareToNullRector;
use Rector\EarlyReturn\Rector\If_\RemoveAlwaysElseRector;


return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/lib',
        __DIR__ . '/mod',
    ])
    // Uncomment to use predefined PHP version rules
    // ->withPhpSets(php82: true) // Adjust for your target PHP version
    ->withRules([
        RemoveAlwaysElseRector::class,
        NullableCompareToNullRector::class,
        // CountArrayToEmptyArrayComparisonRector::class,
        UseIdenticalOverEqualWithSameTypeRector::class,
        UnnecessaryTernaryExpressionRector::class,
        SimplifyUselessVariableRector::class,
        SimplifyStrposLowerRector::class,
        SimplifyRegexPatternRector::class,
        SimplifyIfNotNullReturnRector::class,
        SimplifyIfElseToTernaryRector::class,
        SimplifyEmptyCheckOnEmptyArrayRector::class,
        SimplifyEmptyArrayCheckRector::class,
        SimplifyConditionsRector::class,
        // ShortenElseIfRector::class,
        ReplaceMultipleBooleanNotRector::class,
        RemoveUselessIsObjectCheckRector::class,
        OptionalParametersAfterRequiredRector::class,
        InlineConstructorDefaultToPropertyRector::class,
        InlineArrayReturnAssignRector::class,
        ForeachToInArrayRector::class,
        ForeachItemsAssignToEmptyArrayToAssignRector::class,
        ForRepeatedCountToOwnVariableRector::class,
        FlipTypeControlToUseExclusiveTypeRector::class,
        // ExplicitReturnNullRector::class,
        ExplicitBoolCompareRector::class,
        ConvertStaticPrivateConstantToSelfRector::class,
        ConsecutiveNullCompareReturnsToNullCoalesceQueueRector::class,
        CompleteMissingIfElseBracketRector::class,
        CombineIfRector::class,
        AbsolutizeRequireAndIncludePathRector::class,
        LongArrayToShortArrayRector::class,
        SetCookieRector::class,
        CompleteDynamicPropertiesRector::class,
    ])
    ->withSkip([
        // Add files or directories to skip if needed
        // __DIR__ . '/lib/some-file-to-skip.php',
    ])
    ->withTypeCoverageLevel(0) // Disable type coverage check
    ->withDeadCodeLevel(0)     // Disable dead code detection
    ->withCodeQualityLevel(0); // Disable code quality checks