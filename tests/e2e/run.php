<?php

declare(strict_types=1);

/**
 * AF/Bulletin Modernization & Synchronization
 * Opaque-Box Comprehensive Multi-Tier E2E Test Suite Runner
 *
 * Usage:
 *   php tests/e2e/run.php [OPTIONS]
 *
 * Options:
 *   --tier=<0-5>       Filter by tier level (0: Quality Gates, 1: Features, 2: Boundary/Corner, 3: Cross-Feature, 4: Scenarios, 5: Adversarial)
 *   --filter=<string>  Filter tests by suite or test method name
 *   --json             Output results in structured JSON format
 *   --help             Display help information
 */

$rootDir = dirname(__DIR__, 2);

if (file_exists($rootDir . '/init.php')) {
    require_once $rootDir . '/init.php';
}

// Harness classes
require_once __DIR__ . '/harness/Assertions.php';
require_once __DIR__ . '/harness/TestResult.php';
require_once __DIR__ . '/harness/TestCase.php';
require_once __DIR__ . '/harness/TestSuite.php';
require_once __DIR__ . '/harness/TestRunner.php';
require_once __DIR__ . '/harness/MockDb.php';
require_once __DIR__ . '/harness/MockSession.php';
require_once __DIR__ . '/harness/MockRequest.php';
require_once __DIR__ . '/harness/StaticAuditor.php';
require_once __DIR__ . '/harness/TwigAuditor.php';
require_once __DIR__ . '/harness/CssAuditor.php';

// Fixtures
require_once __DIR__ . '/fixtures/ModuleFixtures.php';
require_once __DIR__ . '/fixtures/ContentFixtures.php';
require_once __DIR__ . '/fixtures/FaqFixtures.php';
require_once __DIR__ . '/fixtures/SeoFixtures.php';
require_once __DIR__ . '/fixtures/NewsletterFixtures.php';
require_once __DIR__ . '/fixtures/SettingsFixtures.php';
require_once __DIR__ . '/fixtures/LanguageFixtures.php';

// Test Suites
require_once __DIR__ . '/suites/Tier0QualityContractsTest.php';
require_once __DIR__ . '/suites/Tier1FeatureCoverageTest.php';
require_once __DIR__ . '/suites/Tier2BoundariesCornersTest.php';
require_once __DIR__ . '/suites/Tier3CrossFeatureTest.php';
require_once __DIR__ . '/suites/Tier4RealWorldScenariosTest.php';
require_once __DIR__ . '/suites/Tier5AdversarialHardeningTest.php';

use E2E\Harness\TestRunner;
use E2E\Harness\TestSuite;
use E2E\Suites\Tier0QualityContractsTest;
use E2E\Suites\Tier1FeatureCoverageTest;
use E2E\Suites\Tier2BoundariesCornersTest;
use E2E\Suites\Tier3CrossFeatureTest;
use E2E\Suites\Tier4RealWorldScenariosTest;
use E2E\Suites\Tier5AdversarialHardeningTest;

$options = getopt('', ['tier:', 'json', 'filter:', 'help']);

if (isset($options['help'])) {
    echo "Usage: php tests/e2e/run.php [OPTIONS]\n\n";
    echo "Options:\n";
    echo "  --tier=<0-5>       Filter tests by tier level (0: Quality, 1: Features, 2: Boundary, 3: Cross, 4: Scenarios, 5: Adversarial)\n";
    echo "  --filter=<string>  Filter tests by suite or test method name\n";
    echo "  --json             Output results in structured JSON format\n";
    echo "  --help             Display this help message\n";
    exit(0);
}

$runner = new TestRunner();

if (isset($options['json'])) {
    $runner->setJsonMode(true);
}

if (isset($options['tier'])) {
    $runner->setTierFilter((int)$options['tier']);
}

if (isset($options['filter'])) {
    $runner->setNameFilter((string)$options['filter']);
}

$runner->addSuite(new TestSuite(new Tier0QualityContractsTest()));
$runner->addSuite(new TestSuite(new Tier1FeatureCoverageTest()));
$runner->addSuite(new TestSuite(new Tier2BoundariesCornersTest()));
$runner->addSuite(new TestSuite(new Tier3CrossFeatureTest()));
$runner->addSuite(new TestSuite(new Tier4RealWorldScenariosTest()));
$runner->addSuite(new TestSuite(new Tier5AdversarialHardeningTest()));

$exitCode = $runner->run();
exit($exitCode);
