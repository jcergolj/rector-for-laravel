<?php

declare(strict_types=1);

namespace Jcergolj\RectorForLaravel\Tests\CustomRules\BlankLineBetweenStatementsRector;

use Jcergolj\RectorForLaravel\CustomRules\AddBlankLineAfterPhpUnitAssertionRector;
use Jcergolj\RectorForLaravel\CustomRules\BlankLineBetweenStatementsRector;
use Jcergolj\RectorForLaravel\CustomRules\WrapLongBooleanConditionsRector;
use Jcergolj\RectorForLaravel\CustomRules\WrapLongChainsRector;
use Jcergolj\RectorForLaravel\CustomRules\WrapLongConcatenationsRector;
use Jcergolj\RectorForLaravel\CustomRules\WrapLongListsRector;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class BlankLineBetweenStatementsWorkflowTest extends TestCase
{
    #[DataProvider('provideOrders')]
    public function test_spacing_is_stable_with_assertions_wrapping_and_pint(bool $spacingFirst): void
    {
        $parts = explode("\n-----\n", file_get_contents(__DIR__.'/../BlankLineBetweenUnrelatedAssignmentsRector/CompositionFixture/unrelated_chains.php.inc'), 2);

        $rules = [BlankLineBetweenStatementsRector::class, AddBlankLineAfterPhpUnitAssertionRector::class, WrapLongChainsRector::class, WrapLongListsRector::class, WrapLongBooleanConditionsRector::class, WrapLongConcatenationsRector::class];

        if (! $spacingFirst) {
            $rules = array_reverse($rules);
        }

        $source = str_replace(['class QuestionnaireAnswers', 'function fill()'], ['class QuestionnaireAnswers extends \\PHPUnit\\Framework\\TestCase', 'function test_fill()'], $parts[0]);
        $source = str_replace("    }\n}", "        \$this->assertTrue(true);\n        \$this->assertSame(1, 1);\n    }\n}", $source)."\n";

        $expected = str_replace(['class QuestionnaireAnswers', 'function fill()'], ['class QuestionnaireAnswers extends \\PHPUnit\\Framework\\TestCase', 'function test_fill()'], $parts[1]);
        $expected = str_replace("    }\n}", "\n        \$this->assertTrue(true);\n\n        \$this->assertSame(1, 1);\n    }\n}", $expected);

        $file = tempnam('/tmp/opencode', 'statement-composition-');

        $path = $file.'.php';

        $config = $file.'-config.php';

        try {
            file_put_contents($path, $source);
            file_put_contents($config, '<?php return \\Rector\\Config\\RectorConfig::configure()->withRules('.var_export($rules, true).');');
            $this->runRector($path, $config);
            $this->assertSame($expected, file_get_contents($path));

            $this->runCommand([PHP_BINARY, dirname(__DIR__, 3).'/vendor/bin/pint', $path, '--no-ansi']);
            $formatted = file_get_contents($path);
            $this->runRector($path, $config);
            $this->assertSame($formatted, file_get_contents($path));

            $this->runCommand([PHP_BINARY, dirname(__DIR__, 3).'/vendor/bin/pint', $path, '--no-ansi']);
            $this->assertSame($formatted, file_get_contents($path));
        } finally {
            unlink($path);
            unlink($config);
            unlink($file);
        }
    }

    public static function provideOrders(): \Iterator
    {
        yield 'spacing first' => [true];
        yield 'spacing last' => [false];
    }

    #[DataProvider('provideWrappingFixtures')]
    public function test_spacing_around_wrapped_statements(string $fixture, bool $spacingFirst): void
    {
        $parts = explode("\n-----\n", file_get_contents(__DIR__.'/../'.$fixture), 2);

        $rules = [BlankLineBetweenStatementsRector::class, WrapLongChainsRector::class, WrapLongListsRector::class, WrapLongBooleanConditionsRector::class, WrapLongConcatenationsRector::class];

        if (! $spacingFirst) {
            $rules = array_reverse($rules);
        }

        $source = "<?php\n\nfirst();\n".substr($parts[0], strlen("<?php\n\n"))."\nafter();\n";

        $expected = "<?php\n\nfirst();\n\n".rtrim(substr($parts[1], strlen("<?php\n\n")))."\n\nafter();\n";

        $file = tempnam('/tmp/opencode', 'statement-wrapping-');

        $path = $file.'.php';

        $config = $file.'-config.php';

        try {
            file_put_contents($path, $source);
            file_put_contents($config, '<?php return \\Rector\\Config\\RectorConfig::configure()->withRules('.var_export($rules, true).');');
            $this->runRector($path, $config);
            $this->assertSame($expected, file_get_contents($path));

            $this->runRector($path, $config);
            $this->assertSame($expected, file_get_contents($path));
        } finally {
            unlink($path);
            unlink($config);
            unlink($file);
        }
    }

    public static function provideWrappingFixtures(): \Iterator
    {
        foreach (['WrapLongListsRector/Fixture/lists.php.inc', 'WrapLongBooleanConditionsRector/Fixture/licence.php.inc', 'WrapLongConcatenationsRector/Fixture/translations.php.inc'] as $fixture) {
            yield $fixture.' spacing first' => [$fixture, true];
            yield $fixture.' spacing last' => [$fixture, false];
        }
    }

    #[DataProvider('provideLineEndings')]
    public function test_line_endings_indentation_and_repeated_runs(string $newline): void
    {
        $source = "<?php\n\nfunction run(): void\n{\n\tfirst(); // Keep attached.   \n\tsecond();   \n\tthird();\n}\n";

        $expected = "<?php\n\nfunction run(): void\n{\n\tfirst(); // Keep attached.   \n\n\tsecond();\n\n\tthird();\n}\n";

        $file = tempnam('/tmp/opencode', 'statement-newlines-');

        $path = $file.'.php';

        try {
            file_put_contents($path, str_replace("\n", $newline, $source));
            $this->runRector($path, __DIR__.'/config/configured_rule.php');
            $this->assertSame(str_replace("\n", $newline, $expected), file_get_contents($path));

            $this->runRector($path, __DIR__.'/config/configured_rule.php');
            $this->assertSame(str_replace("\n", $newline, $expected), file_get_contents($path));

            $this->runCommand([PHP_BINARY, '-l', $path]);
        } finally {
            unlink($path);
            unlink($file);
        }
    }

    public static function provideLineEndings(): \Iterator
    {
        yield 'LF' => ["\n"];
        yield 'CRLF' => ["\r\n"];
    }

    #[DataProvider('provideSkips')]
    public function test_rule_skipping(bool $pathSkip): void
    {
        $file = tempnam('/tmp/opencode', 'statement-skip-');

        $path = $file.'.php';

        $config = $file.'-config.php';

        $source = "<?php\n\nfirst();\nsecond();\n";

        $skip = $pathSkip ? [BlankLineBetweenStatementsRector::class => [$path]] : [BlankLineBetweenStatementsRector::class];

        try {
            file_put_contents($path, $source);
            file_put_contents($config, '<?php return \\Rector\\Config\\RectorConfig::configure()->withRules(['.BlankLineBetweenStatementsRector::class.'::class])->withSkip('.var_export($skip, true).');');
            $this->runRector($path, $config);
            $this->assertSame($source, file_get_contents($path));
        } finally {
            unlink($path);
            unlink($config);
            unlink($file);
        }
    }

    public static function provideSkips(): \Iterator
    {
        yield 'path' => [true];
        yield 'global' => [false];
    }

    private function runRector(string $path, string $config): void
    {
        $this->runCommand([PHP_BINARY, dirname(__DIR__, 3).'/vendor/bin/rector', 'process', $path, '--config='.$config, '--no-progress-bar', '--no-ansi', '--clear-cache']);
    }

    private function runCommand(array $arguments): void
    {
        exec(implode(' ', array_map(escapeshellarg(...), $arguments)).' 2>&1', $output, $status);
        $this->assertSame(0, $status, implode("\n", $output));
    }
}
