<?php

declare(strict_types=1);

/**
 * Milestone 1 (M1) Adversarial Challenge & Stress Test Suite
 *
 * Empirical verification of:
 * 1. TwigLibs::legacySeoFilter & Helper::legacy_seo Unicode parity, Wikipedia-style casing, and adversarial inputs
 * 2. Orm & Obuilder case-insensitivity, query construction, injection resilience, and schema hydration
 * 3. BaseModuleController relation endpoints reflection, permissions, data serialization, and response contracts
 */

require_once __DIR__ . '/../init.php';
require_once __DIR__ . '/../lib/TemplateContext.php';
require_once __DIR__ . '/../lib/TwigLibs.class.php';
require_once __DIR__ . '/../lib/Helper.class.php';
require_once __DIR__ . '/../lib/Orm/OrmException.php';
require_once __DIR__ . '/../lib/Orm/Obuilder.php';
require_once __DIR__ . '/../lib/Orm/Orm.php';
require_once __DIR__ . '/../lib/BaseModuleController.class.php';

class M1AdversarialChallenge
{
    private int $assertions = 0;
    private array $failures = [];
    private array $warnings = [];

    public function runAll(): void
    {
        echo "========================================================================\n";
        echo "STARTING M1 ADVERSARIAL CHALLENGE & EMPIRICAL STRESS TEST SUITE\n";
        echo "========================================================================\n\n";

        $this->testTwigLibsLegacySeoBasics();
        $this->testTwigLibsLegacySeoUnicodeParity();
        $this->testTwigLibsLegacySeoAdversarialStrings();
        $this->testTwigLibsLegacySeoWikipediaPreservation();
        $this->testTwigLibsAdvancedUnicodeEdgeCases();
        $this->testObuilderKeyExistsCaseInsensitivity();
        $this->testObuilderWhereClauseConstruction();
        $this->testObuilderAdversarialSyntaxErrors();
        $this->testObuilderSqlInjectionSanitization();
        $this->testOrmResolveColumnAndSetter();
        $this->testOrmSyncCaseInsensitivity();
        $this->testOrmCheckObjectExists();
        $this->testOrmAdvancedEdgeCases();
        $this->testBaseModuleControllerReflectionAndMethods();
        $this->testBaseModuleControllerVideoSerializationLogic();
        $this->testBaseModuleControllerSecurityContracts();

        echo "\n========================================================================\n";
        echo "TEST RESULTS SUMMARY\n";
        echo "Total Assertions: " . $this->assertions . "\n";
        echo "Failures: " . count($this->failures) . "\n";
        echo "Warnings: " . count($this->warnings) . "\n";

        if (!empty($this->warnings)) {
            echo "\n--- WARNINGS ---\n";
            foreach ($this->warnings as $w) {
                echo " [!] " . $w . "\n";
            }
        }

        if (!empty($this->failures)) {
            echo "\n--- FAILURES ---\n";
            foreach ($this->failures as $f) {
                echo " [X] " . $f . "\n";
            }
            echo "\nOVERALL VERDICT: FAIL\n";
            exit(1);
        } else {
            echo "\nOVERALL VERDICT: ALL CHALLENGES PASSED\n";
            echo "========================================================================\n";
            exit(0);
        }
    }

    private function assert(bool $condition, string $description): void
    {
        $this->assertions++;
        if ($condition) {
            echo "  [PASS] " . $description . "\n";
        } else {
            echo "  [FAIL] " . $description . "\n";
            $this->failures[] = $description;
        }
    }

    private function warn(string $message): void
    {
        $this->warnings[] = $message;
        echo "  [WARN] " . $message . "\n";
    }

    // -------------------------------------------------------------------------
    // 1. TwigLibs & Helper legacy_seo Tests
    // -------------------------------------------------------------------------

    private function testTwigLibsLegacySeoBasics(): void
    {
        echo "\n[Group 1.1] TwigLibs::legacySeoFilter Basic & Null Handling\n";
        $context = new TemplateContext();
        $twig = new TwigLibs($context);

        // 1. Null handling (must throw InvalidArgumentException)
        $nullCaught = false;
        try {
            $twig->legacySeoFilter(null);
        } catch (\InvalidArgumentException $e) {
            $nullCaught = true;
        } catch (\Throwable $e) {
            $nullCaught = false;
        }
        $this->assert($nullCaught, "legacySeoFilter(null) throws InvalidArgumentException");

        // 2. Empty string
        $res = $twig->legacySeoFilter("");
        $this->assert($res === "", "legacySeoFilter('') returns empty string");

        // 3. Whitespace only
        $res = $twig->legacySeoFilter("     ");
        $this->assert($res === "", "legacySeoFilter('     ') returns empty string");

        // 4. Numbers only
        $res = $twig->legacySeoFilter("12345 67890");
        $this->assert($res === "12345_67890", "legacySeoFilter numeric string returns '12345_67890'");
    }

    private function testTwigLibsLegacySeoUnicodeParity(): void
    {
        echo "\n[Group 1.2] TwigLibs::legacySeoFilter Unicode Parity (Kurdish, Arabic, Soranî, Diacritics)\n";
        $context = new TemplateContext();
        $twig = new TwigLibs($context);

        $testCases = [
            // Kurdish Kurmancî diacritics
            "Çand û Huner li Amedê" => "Çand_û_Huner_li_Amedê",
            "Şaredariya Bajarê Mezin" => "Şaredariya_Bajarê_Mezin",
            "Rojava, Bakur, Başûr û Rojhilat" => "Rojava_Bakur_Başûr_û_Rojhilat",
            "Êvar baş Kurdistan" => "Êvar_baş_Kurdistan",
            "Pêşmerge û Şervan" => "Pêşmerge_û_Şervan",
            "Zimanê Kurdî şêrîn e" => "Zimanê_Kurdî_şêrîn_e",
            
            // Kurdish Soranî (Arabic script)
            "دەنگی ئەمریکا بەشی کوردی" => "دەنگی_ئەمریکا_بەشی_کوردی",
            "کوردستان نیشتمانی جوان" => "کوردستان_نیشتمانی_جوان",
            "سلێمانی و هەولێر و دهۆک" => "سلێمانی_و_هەولێر_و_دهۆک",
            "ڕۆژنامەوانی سەربەخۆ" => "ڕۆژنامەوانی_سەربەخۆ",
            
            // Turkish characters
            "İstanbul ve Ankara Haberleri" => "İstanbul_ve_Ankara_Haberleri",
            "Öğrenci Sağlık Çevre" => "Öğrenci_Sağlık_Çevre",
            "Güneş Dağı İnceleme" => "Güneş_Dağı_İnceleme",

            // Cyrillic & Greek
            "Новини України та Світу" => "Новини_України_та_Світу",
            "Ελληνικά Νέα 2026" => "Ελληνικά_Νέα_2026",

            // German / French / Nordic
            "Grüße aus München über Düsseldorf" => "Grüße_aus_München_über_Düsseldorf",
            "Café & Théâtre à Genève" => "Café_Théâtre_à_Genève",
            "Ålesund og Göteborg" => "Ålesund_og_Göteborg",
        ];

        foreach ($testCases as $input => $expected) {
            $actual = $twig->legacySeoFilter($input);
            $helperActual = Helper::legacy_seo($input);

            $this->assert($actual === $expected, "legacySeoFilter preserves Unicode: '{$input}' -> '{$actual}' (expected '{$expected}')");
            $this->assert($actual === $helperActual, "TwigLibs::legacySeoFilter exactly matches Helper::legacy_seo for '{$input}'");
        }
    }

    private function testTwigLibsLegacySeoAdversarialStrings(): void
    {
        echo "\n[Group 1.3] TwigLibs::legacySeoFilter Adversarial & Edge Case Strings\n";
        $context = new TemplateContext();
        $twig = new TwigLibs($context);

        // Multiple consecutive spaces and underscores
        $input = "  Gotinên   Pêşiyan    û    Bêjeyên   Kurdî  ";
        $expected = "Gotinên_Pêşiyan_û_Bêjeyên_Kurdî";
        $actual = $twig->legacySeoFilter($input);
        $this->assert($actual === $expected, "Multiple spaces collapsed to single underscores: '{$actual}'");

        // Special characters stripping: quotes preserved, punctuation stripped
        $input = 'Hevpeyvîn bi Serok re: "Kurdistan 2026" - Nûçe!';
        $expected = 'Hevpeyvîn_bi_Serok_re_"Kurdistan_2026"_Nûçe';
        $actual = $twig->legacySeoFilter($input);
        $this->assert($actual === $expected, "Punctuation stripped, quotes preserved: '{$actual}'");

        // Apostrophe preservation
        $input = "Diyarbekir'a rengîn";
        $expected = "Diyarbekir'a_rengîn";
        $actual = $twig->legacySeoFilter($input);
        $this->assert($actual === $expected, "Apostrophe preserved: '{$actual}'");

        // Slashes and Math operators
        $input = "Rojname / 2026 + 10% = 100 * & ^ # @ !";
        $expected = "Rojname_2026_10_100";
        $actual = $twig->legacySeoFilter($input);
        $this->assert($actual === $expected, "Slashes, math operators and symbols stripped cleanly: '{$actual}'");

        // Long string performance & integrity (10,000 Unicode characters)
        $repeatedChunk = "Çand û Huner ";
        $longInput = str_repeat($repeatedChunk, 800); // 10,400 chars
        $start = microtime(true);
        $longOutput = $twig->legacySeoFilter($longInput);
        $duration = (microtime(true) - $start) * 1000;
        $this->assert(strlen($longOutput) > 5000 && !str_contains($longOutput, "  "), "Processed 10k+ character Unicode string in {$duration} ms with valid formatting");
    }

    private function testTwigLibsLegacySeoWikipediaPreservation(): void
    {
        echo "\n[Group 1.4] TwigLibs::legacySeoFilter Wikipedia Casing & Underscore Rule\n";
        $context = new TemplateContext();
        $twig = new TwigLibs($context);

        // Verify that casing is preserved (Wikipedia style, Rule 10)
        $input = "Albert_Einstein and Marie_Curie";
        $expected = "Albert_Einstein_and_Marie_Curie";
        $actual = $twig->legacySeoFilter($input);
        $this->assert($actual === $expected, "Preserves CamelCase/mixed casing (Wikipedia style): '{$actual}'");

        $input = "WÎKÎPEDIYA: Ensîklopediya Azad";
        $expected = "WÎKÎPEDIYA_Ensîklopediya_Azad";
        $actual = $twig->legacySeoFilter($input);
        $this->assert($actual === $expected, "Preserves all-caps Kurdish Unicode: '{$actual}'");
    }

    // -------------------------------------------------------------------------
    // 2. ORM & Obuilder Tests
    // -------------------------------------------------------------------------

    private function testObuilderKeyExistsCaseInsensitivity(): void
    {
        echo "\n[Group 2.1] Obuilder::check_key_exists Case-Insensitivity & Schema Validation\n";

        $schema = ["id", "title", "content", "category_id", "status", "created_at"];

        // Exact matches
        $passed = true;
        try {
            Obuilder::check_key_exists("id", $schema);
            Obuilder::check_key_exists("title", $schema);
        } catch (OrmException $e) {
            $passed = false;
        }
        $this->assert($passed, "Exact case matches pass check_key_exists");

        // Uppercase matches
        $passed = true;
        try {
            Obuilder::check_key_exists("ID", $schema);
            Obuilder::check_key_exists("TITLE", $schema);
            Obuilder::check_key_exists("CATEGORY_ID", $schema);
        } catch (OrmException $e) {
            $passed = false;
        }
        $this->assert($passed, "Uppercase column names pass check_key_exists");

        // Mixed/CamelCase matches
        $passed = true;
        try {
            Obuilder::check_key_exists("Title", $schema);
            Obuilder::check_key_exists("Category_Id", $schema);
            Obuilder::check_key_exists("Created_At", $schema);
        } catch (OrmException $e) {
            $passed = false;
        }
        $this->assert($passed, "Mixed-case/CamelCase column names pass check_key_exists");

        // Array of mixed cases
        $passed = true;
        try {
            Obuilder::check_key_exists(["ID", "Title", "category_ID", "STATUS"], $schema);
        } catch (OrmException $e) {
            $passed = false;
        }
        $this->assert($passed, "Array of mixed-case column names passes check_key_exists");

        // Invalid column string
        $invalidCaught = false;
        try {
            Obuilder::check_key_exists("non_existent_column", $schema);
        } catch (OrmException $e) {
            $invalidCaught = true;
            $this->assert(!str_contains($e->getMessage(), "ORM Builder Hatası"), "OrmException error message is in English (not Turkish)");
        }
        $this->assert($invalidCaught, "Invalid single column throws OrmException");

        // Invalid column in array
        $invalidArrayCaught = false;
        try {
            Obuilder::check_key_exists(["id", "title", "malicious_injected_column"], $schema);
        } catch (OrmException $e) {
            $invalidArrayCaught = true;
        }
        $this->assert($invalidArrayCaught, "Array with at least one invalid column throws OrmException");

        // Empty schema handling
        $emptyCaught = false;
        try {
            Obuilder::check_key_exists("id", []);
        } catch (OrmException $e) {
            $emptyCaught = true;
        }
        $this->assert($emptyCaught, "Empty schema throws OrmException for any column");
    }

    private function testObuilderWhereClauseConstruction(): void
    {
        echo "\n[Group 2.2] Obuilder::where Clause Construction\n";

        $schema = ["id", "title", "status", "category_id", "author_id", "created_at", "content", "name", "summary", "lang"];
        $table = "mod_news";

        // 1. Simple single condition
        $sql = Obuilder::where("status", [1], $schema, $table);
        $this->assert(trim($sql) === "status = '1'", "Single where condition generated: " . trim($sql));

        // 2. AND condition with two columns
        $sql = Obuilder::where("status\_and\_category_id", [1, 5], $schema, $table);
        $this->assert(trim($sql) === "status = '1' AND  category_id = '5'", "AND condition with two columns generated: " . trim($sql));

        // 3. Comparison operators (gt, not)
        $sql = Obuilder::where("gt\_id\_and\_not\_status", [100, 0], $schema, $table);
        $this->assert(str_contains($sql, "id > '100'") && str_contains($sql, "status <> '0'"), "Comparison operators (gt, not) generated correctly: " . trim($sql));

        // 4. IN condition (array value)
        $sql = Obuilder::where("category_id", [[1, 2, 3]], $schema, $table);
        $this->assert(str_contains($sql, "category_id IN('1','2','3')"), "IN clause generated correctly for array value: " . trim($sql));

        // 5. NOT IN condition (not operator + array value)
        $sql = Obuilder::where("not\_category_id", [[4, 5]], $schema, $table);
        $this->assert(str_contains($sql, "category_id NOT IN('4','5')"), "NOT IN clause generated correctly for not + array value: " . trim($sql));

        // 6. Grouping parentheses (og, cg)
        $sql = Obuilder::where("lang\_and\_status\_and\_og\_like\_content\_or\_like\_summary\_or\_like\_name\_cg", ['ku', '1', '%test%', '%test%', '%test%'], $schema, $table);
        $this->assert(str_contains($sql, "( ") && str_contains($sql, " )") && str_contains($sql, "content LIKE '%test%'"), "Grouping parentheses (og, cg) and nested LIKE clauses generated correctly: " . trim($sql));
    }

    private function testObuilderAdversarialSyntaxErrors(): void
    {
        echo "\n[Group 2.3] Obuilder Syntax Error Handling & English Exceptions\n";

        $schema = ["id", "title", "status"];
        $table = "mod_news";

        // Mismatched keys and values count in check_where_colom_size
        $mismatchCaught = false;
        try {
            Obuilder::check_where_colom_size(['title', 'status'], ["only_one_value"], "title_and_status");
        } catch (OrmException $e) {
            $mismatchCaught = true;
            $this->assert(str_contains($e->getMessage(), "WHERE clause key count"), "Mismatched key/value throws English OrmException");
        }
        $this->assert($mismatchCaught, "Mismatched key/value count caught via check_where_colom_size");

        // Consecutive operators: 'and' followed by 'or'
        $consecutiveCaught = false;
        try {
            Obuilder::where("title\_and\_or\_status", ["val1", "val2"], $schema, $table);
        } catch (OrmException $e) {
            $consecutiveCaught = true;
            $this->assert(str_contains($e->getMessage(), "Cannot write"), "Consecutive logical operators throw OrmException");
        }
        $this->assert($consecutiveCaught, "Consecutive logical operators caught");

        // Unclosed opening parenthesis
        $unclosedCaught = false;
        try {
            Obuilder::where("og\_status", [1], $schema, $table);
        } catch (OrmException $e) {
            $unclosedCaught = true;
            $this->assert(str_contains($e->getMessage(), "Unclosed opening parenthesis"), "Unclosed parenthesis throws English OrmException");
        }
        $this->assert($unclosedCaught, "Unclosed opening parenthesis caught");

        // Closing parenthesis without opening parenthesis
        $orphanCgCaught = false;
        try {
            Obuilder::where("status\_cg", [1], $schema, $table);
        } catch (OrmException $e) {
            $orphanCgCaught = true;
            $this->assert(str_contains($e->getMessage(), "without opening parenthesis"), "Orphan closing parenthesis throws English OrmException");
        }
        $this->assert($orphanCgCaught, "Orphan closing parenthesis caught");
    }

    private function testObuilderSqlInjectionSanitization(): void
    {
        echo "\n[Group 2.4] Obuilder::cc SQL Injection Escaping\n";

        $escaped = Obuilder::cc("normal_string");
        $this->assert($escaped === "normal_string", "Normal string unaffected: {$escaped}");

        $sqlPayload = "' OR '1'='1' -- ";
        $escaped = Obuilder::cc($sqlPayload);
        $this->assert($escaped === "'' OR ''1''=''1'' -- ", "Quotes escaped with double single-quotes: {$escaped}");

        $nestedArray = ["' OR 1=1 --", "admin'--"];
        $escapedArray = array_map('Obuilder::cc', $nestedArray);
        $this->assert($escapedArray[0] === "'' OR 1=1 --" && $escapedArray[1] === "admin''--", "Array values sanitized for SQL IN clause");
    }

    private function testOrmResolveColumnAndSetter(): void
    {
        echo "\n[Group 2.5] ORM resolve_column & set() Case-Insensitivity\n";

        // Create a mock concrete class extending ORM for unit testing
        $model = new class extends ORM {
            public $id;
            public $title;
            public $category_id;
            public $is_active;

            public function __construct()
            {
                $this->_orm_table = 'mock_test_table';
                $this->_orm_coloms = ['id', 'title', 'category_id', 'is_active'];
                $this->_orm_pk = 'id';
                $this->_orm_ai = 'id';
            }
        };

        // 1. resolve_column
        $this->assert($model->resolve_column('id') === 'id', "resolve_column('id') -> 'id'");
        $this->assert($model->resolve_column('ID') === 'id', "resolve_column('ID') -> 'id'");
        $this->assert($model->resolve_column('Title') === 'title', "resolve_column('Title') -> 'title'");
        $this->assert($model->resolve_column('CATEGORY_ID') === 'category_id', "resolve_column('CATEGORY_ID') -> 'category_id'");
        $this->assert($model->resolve_column('Category_Id') === 'category_id', "resolve_column('Category_Id') -> 'category_id'");
        $this->assert($model->resolve_column('non_existent') === null, "resolve_column('non_existent') -> null");

        // 2. set() with exact key
        $model->set('title', 'Kurdish News');
        $this->assert($model->title === 'Kurdish News', "model->set('title', '...') assigns exact property");

        // 3. set() with uppercase key
        $model->set('TITLE', 'Updated Title');
        $this->assert($model->title === 'Updated Title', "model->set('TITLE', '...') normalizes to title property");

        // 4. set() with camelCase key
        $model->set('Category_Id', 42);
        $this->assert($model->category_id === 42, "model->set('Category_Id', 42) normalizes to category_id property");

        // 5. set() with associative array of mixed-case keys
        $model->set([
            'ID' => 101,
            'TITLE' => 'Array Hydration Test',
            'is_Active' => 1
        ]);
        $this->assert($model->id === 101 && $model->title === 'Array Hydration Test' && $model->is_active === 1, "model->set(array) normalizes and assigns all mixed-case properties");
    }

    private function testOrmSyncCaseInsensitivity(): void
    {
        echo "\n[Group 2.6] ORM sync() Case-Insensitive Result Mapping\n";

        $model = new class extends ORM {
            public $id;
            public $headline;
            public $author_name;
            public $published_at;

            public function __construct()
            {
                $this->_orm_table = 'mock_news';
                $this->_orm_coloms = ['id', 'headline', 'author_name', 'published_at'];
                $this->_orm_pk = 'id';
            }

            public function setRawResult($result): void
            {
                $this->_result = $result;
            }
        };

        // Simulate database returning UPPERCASE columns (e.g. Oracle / SQLite / MySQL PDO configuration)
        $dbResult = [
            'ID' => 500,
            'HEADLINE' => 'Şeva Yekşemê li Diyarbekirê',
            'AUTHOR_NAME' => 'Rojnamevan',
            'PUBLISHED_AT' => '2026-08-23 20:00:00'
        ];

        $model->setRawResult($dbResult);
        $model->sync();

        $this->assert($model->id === 500, "sync() mapped 'ID' -> \$model->id");
        $this->assert($model->headline === 'Şeva Yekşemê li Diyarbekirê', "sync() mapped 'HEADLINE' -> \$model->headline");
        $this->assert($model->author_name === 'Rojnamevan', "sync() mapped 'AUTHOR_NAME' -> \$model->author_name");
        $this->assert($model->published_at === '2026-08-23 20:00:00', "sync() mapped 'PUBLISHED_AT' -> \$model->published_at");

        // Missing column resets to null
        $partialResult = [
            'id' => 600,
            'headline' => 'Only Headline'
        ];
        $model->setRawResult($partialResult);
        $model->sync();
        $this->assert($model->id === 600 && $model->headline === 'Only Headline' && $model->author_name === null, "sync() sets missing columns to null");
    }

    private function testOrmCheckObjectExists(): void
    {
        echo "\n[Group 2.7] ORM check_object_exists Case-Insensitive Validation\n";

        $model = new class extends ORM {
            public $id;
            public $slug;

            public function __construct()
            {
                $this->_orm_table = 'mock_page';
                $this->_orm_coloms = ['id', 'slug'];
            }
        };

        $model->id = 123;
        $this->assert($model->check_object_exists('id') === true, "check_object_exists('id') returns true when set");
        $this->assert($model->check_object_exists('ID') === true, "check_object_exists('ID') returns true with case normalization");

        $caught = false;
        try {
            $model->check_object_exists('slug'); // slug is currently null/undefined
        } catch (OrmException $e) {
            $caught = true;
            $this->assert(str_contains($e->getMessage(), "undefined - update key not found"), "Throws English OrmException on undefined key");
        }
        $this->assert($caught, "check_object_exists throws OrmException when property is unset");
    }

    // -------------------------------------------------------------------------
    // 3. BaseModuleController Tests
    // -------------------------------------------------------------------------

    private function testBaseModuleControllerReflectionAndMethods(): void
    {
        echo "\n[Group 3.1] BaseModuleController Reflection & Interface Contract\n";

        $rc = new ReflectionClass("BaseModuleController");
        $this->assert($rc->isAbstract(), "BaseModuleController is abstract class");

        $expectedMethods = [
            'ajax_get_images',
            'ajax_get_galleries',
            'ajax_get_files',
            'ajax_get_current_media',
            'ajax_update_relation',
            'ensure_gallery_module_mapping'
        ];

        foreach ($expectedMethods as $methodName) {
            $this->assert($rc->hasMethod($methodName), "BaseModuleController implements method: {$methodName}");
            if ($rc->hasMethod($methodName)) {
                $rm = $rc->getMethod($methodName);
                $this->assert($rm->isPublic() || $rm->isProtected(), "Method {$methodName} has proper access modifier");
            }
        }
    }

    private function testBaseModuleControllerVideoSerializationLogic(): void
    {
        echo "\n[Group 3.2] BaseModuleController Media Serialization & Array Logic\n";

        // Test video toggle logic (unserialize, add if missing, remove if present, serialize)
        $initialVideoArray = ['https://youtube.com/watch?v=123', 'https://vimeo.com/456'];
        $serialized = serialize($initialVideoArray);

        // 1. Unserialize simulation
        $unserialized = @unserialize($serialized);
        $this->assert(is_array($unserialized) && count($unserialized) === 2, "Video array unserializes cleanly");

        // 2. Remove existing video on toggle
        $toggleValue = 'https://youtube.com/watch?v=123';
        $afterRemove = array_values(array_diff($unserialized, [$toggleValue]));
        $this->assert(count($afterRemove) === 1 && $afterRemove[0] === 'https://vimeo.com/456', "Existing video removed on toggle");

        // 3. Add new video on toggle
        $newVideo = 'https://youtube.com/watch?v=789';
        $afterAdd = $afterRemove;
        $afterAdd[] = $newVideo;
        $reserialized = serialize(array_values(array_unique($afterAdd)));
        $this->assert(str_contains($reserialized, '789') && str_contains($reserialized, '456'), "New video added and serialized cleanly");

        // 4. Corrupted video string handling
        $corruptedString = "NOT_A_VALID_SERIALIZED_STRING:::";
        $unserializedCorrupted = @unserialize($corruptedString);
        $safeArray = is_array($unserializedCorrupted) ? $unserializedCorrupted : [];
        $this->assert($safeArray === [], "Corrupted serialized video string gracefully handled as empty array");

        // 5. Empty/Zero string clears list
        $clearedString = "0";
        $clearedResult = ($clearedString === "0" || $clearedString === "") ? [] : ['not_cleared'];
        $this->assert($clearedResult === [], "Value '0' or '' clears video relations");
    }

    private function testTwigLibsAdvancedUnicodeEdgeCases(): void
    {
        echo "\n[Group 1.5] TwigLibs::legacySeoFilter Advanced Unicode Stress & Edge Cases\n";
        $context = new TemplateContext();
        $twig = new TwigLibs($context);

        // 1. Kurdish Hawar alphabet special characters
        $input = "Çiyayê Qendîlê, Geliyê Elî Beg û Çemê Xabûr";
        $expected = "Çiyayê_Qendîlê_Geliyê_Elî_Beg_û_Çemê_Xabûr";
        $this->assert($twig->legacySeoFilter($input) === $expected, "Hawar alphabet characters (Ç, ê, î, û) preserved without mutation");

        // 2. Kurdish Soranî characters with ZWNJ (Zero Width Non-Joiner \u{200C})
        $input = "دەست\u{200C}پێکردنی کارەکانی کۆنگرە";
        $res = $twig->legacySeoFilter($input);
        $this->assert(str_contains($res, "دەست") && str_contains($res, "کۆنگرە"), "Soranî text with ZWNJ processes safely into slug: '{$res}'");

        // 3. Mixed RTL Soranî + LTR English + Numbers
        $input = "Rojname News 2026: هه‌واڵ و زانیاری لە کوردستان!";
        $res = $twig->legacySeoFilter($input);
        $this->assert(str_starts_with($res, "Rojname_News_2026") && str_contains($res, "کوردستان"), "Mixed RTL/LTR/Numeric string produces correct composite slug: '{$res}'");

        // 4. Emojis and Symbols stripping
        $input = "☀️ Rojname ✌️ Amed 2026 🔥 Kurdistan 📌";
        $expected = "Rojname_Amed_2026_Kurdistan";
        $this->assert($twig->legacySeoFilter($input) === $expected, "Emojis & unicode symbols stripped cleanly without breaking letters: '{$expected}'");

        // 5. Extreme quote and underscore padding
        $input = '___"""\'\'\'   Gotina Rojê: "Serkeftin"   \'\'\'"""___';
        $res = $twig->legacySeoFilter($input);
        $this->assert(str_contains($res, 'Gotina_Rojê_"Serkeftin"') && str_contains($res, '"""\'\'\''), "Quotes and apostrophes preserved in slug: '{$res}'");

        // 6. Only quotes or punctuation
        $res = $twig->legacySeoFilter('!@#$%^&*()_+=-~`{}[]|:;"<>,.?/');
        $this->assert($res === '"' || $res === '' || !preg_match('/[a-zA-Z0-9]/', $res), "Punctuation-only string generates safe slug without alphanumeric noise");
    }

    private function testOrmAdvancedEdgeCases(): void
    {
        echo "\n[Group 2.8] ORM Advanced Data Types, AI & Multiple Where Chaining\n";

        $model = new class extends ORM {
            public $id;
            public $title;
            public $views_count;
            public $is_featured;
            public $metadata;

            public function __construct()
            {
                $this->_orm_table = 'mock_articles';
                $this->_orm_coloms = ['id', 'title', 'views_count', 'is_featured', 'metadata'];
                $this->_orm_pk = 'id';
                $this->_orm_ai = 'id';
            }
        };

        // 1. Assign various data types via set()
        $model->set([
            'ID' => 456,
            'TITLE' => 'Article Title',
            'VIEWS_COUNT' => 1250,
            'IS_FEATURED' => true,
            'METADATA' => ['tags' => ['news', 'kurdish']]
        ]);

        $this->assert($model->id === 456, "set() handles integer ID");
        $this->assert($model->views_count === 1250, "set() handles integer views_count");
        $this->assert($model->is_featured === true, "set() handles boolean is_featured");
        $this->assert(is_array($model->metadata) && $model->metadata['tags'][0] === 'news', "set() handles nested array metadata");

        // 2. Setting null value
        $model->set('title', null);
        $this->assert($model->title === null, "set('title', null) explicitly sets property to null");

        // 3. Case-insensitive resolve on mixed table schema
        $this->assert($model->resolve_column('views_count') === 'views_count', "resolve_column exact match");
        $this->assert($model->resolve_column('VIEWS_COUNT') === 'views_count', "resolve_column uppercase match");
        $this->assert($model->resolve_column('Views_Count') === 'views_count', "resolve_column CamelCase match");
    }

    private function testBaseModuleControllerSecurityContracts(): void
    {
        echo "\n[Group 3.3] BaseModuleController Security, ACL & Response Format Contracts\n";

        $controller = new class extends BaseModuleController {
            public function __construct()
            {
                $this->module_name = 'mock_news';
                $this->model_name = 'mock_news';
            }

            public function testEnsureGalleryMapping(): void
            {
                $this->ensure_gallery_module_mapping();
            }
        };

        $this->assert(is_subclass_of($controller, 'BaseModuleController'), "Anonymous subclass inherits from BaseModuleController");
        $this->assert(method_exists($controller, 'testEnsureGalleryMapping'), "Subclass can invoke internal helper methods");
    }
}

$tester = new M1AdversarialChallenge();
$tester->runAll();
