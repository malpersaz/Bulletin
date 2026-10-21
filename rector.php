<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;
use Rector\Php84\Rector\Param\ExplicitNullableParamTypeRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/lib',
        __DIR__ . '/mod',
        __DIR__ . '/config',
        __DIR__ . '/tests',
    ])
    ->withSkip([
        __DIR__ . '/lib/swift',
        __DIR__ . '/vendor',
        __DIR__ . '/cache',
        __DIR__ . '/templates_c',
    ])
    ->withRules([
        ExplicitNullableParamTypeRector::class,
        AddOverrideAttributeToOverriddenMethodsRector::class,
    ]);
