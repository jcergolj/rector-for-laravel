<?php

declare(strict_types=1);

namespace Jcergolj\RectorForLaravel\Tests\CustomRules\WrapLongBooleanConditionsRector;

use Jcergolj\RectorForLaravel\CustomRules\WrapLongBooleanConditionsRector;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class WrapLongBooleanConditionsWorkflowTest extends TestCase
{
    #[DataProvider('provideFixtures')]
    public function test_rector_and_pint_preserve_tokens_and_are_stable(string $fixture, string $config): void
    {
        $parts = explode("\n-----\n", file_get_contents($fixture), 2);
        $file = tempnam('/tmp/opencode', 'boolean-workflow-');
        $path = $file.'.php';
        $root = dirname(__DIR__, 3);

        try {
            file_put_contents($path, rtrim($parts[0], "\n")."\n");
            $rector = [PHP_BINARY, $root.'/vendor/bin/rector', 'process', $path, '--config='.$config, '--no-progress-bar', '--no-ansi', '--clear-cache'];
            $pint = [PHP_BINARY, $root.'/vendor/bin/pint', $path, '--no-ansi'];
            $this->runCommand($rector);
            $output = file_get_contents($path);
            $composition = str_contains($fixture, '/CompositionFixture/');
            if (! $composition) {
                $this->assertSame($parts[1] ?? $parts[0], $output);
            }
            $this->assertSame($this->meaningfulTokens($parts[0], $composition), $this->meaningfulTokens($output, $composition));
            $this->runCommand([PHP_BINARY, '-l', $path]);
            $this->runCommand($pint);
            $firstCycle = file_get_contents($path);
            if ($composition) {
                $this->assertSame($parts[1], $firstCycle);
            }
            if (basename($fixture) === 'licence.php.inc') {
                $this->assertSame($output, $firstCycle, 'Pint must preserve the requested trailing-operator layout.');
            }

            $this->runCommand($rector);
            $this->assertSame($firstCycle, file_get_contents($path), 'Second Rector run changed the Pint output.');
            $this->runCommand($pint);
            $this->assertSame($firstCycle, file_get_contents($path), 'Second Pint run changed the output.');
        } finally {
            unlink($path);
            unlink($file);
        }
    }

    public static function provideFixtures(): \Iterator
    {
        foreach (glob(__DIR__.'/Fixture/*.php.inc') as $fixture) {
            yield basename($fixture) => [$fixture, __DIR__.'/config/configured_rule.php'];
        }

        foreach (['arrays_first', 'booleans_first'] as $order) {
            foreach (glob(__DIR__.'/CompositionFixture/*.php.inc') as $fixture) {
                yield $order.'/'.basename($fixture) => [$fixture, __DIR__.'/config/'.$order.'.php'];
            }
        }
    }

    public function test_custom_target_and_path_skip(): void
    {
        $file = tempnam('/tmp/opencode', 'boolean-config-');
        $path = $file.'.php';
        $config = $file.'-config.php';
        $source = "<?php\n\nif (\$request->ready() || \$request->waiting()) {\n}\n";
        $root = dirname(__DIR__, 3);

        try {
            $rule = '\\'.WrapLongBooleanConditionsRector::class;
            $configuration = '<?php return \\Rector\\Config\\RectorConfig::configure()';
            file_put_contents($config, $configuration.'->withRules(['.$rule.'::class]);');
            file_put_contents($path, $source);
            $command = [PHP_BINARY, $root.'/vendor/bin/rector', 'process', $path, '--config='.$config, '--no-progress-bar', '--no-ansi', '--clear-cache'];
            $this->runCommand($command);
            $this->assertSame($source, file_get_contents($path));

            $configuration .= '->withConfiguredRule('.$rule."::class, ['max_line_length' => 30])";
            file_put_contents($config, $configuration.';');
            $this->runCommand($command);
            $this->assertSame("<?php\n\nif (\n    \$request->ready() ||\n    \$request->waiting()\n) {\n}\n", file_get_contents($path));

            file_put_contents($config, $configuration.'->withSkip(['.$rule.'::class => ['.var_export($path, true).']]);');
            file_put_contents($path, $source);
            $this->runCommand($command);
            $this->assertSame($source, file_get_contents($path));
        } finally {
            unlink($path);
            unlink($config);
            unlink($file);
        }
    }

    #[DataProvider('provideInvalidTargets')]
    public function test_rejects_invalid_targets(mixed $target): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new WrapLongBooleanConditionsRector)->configure([WrapLongBooleanConditionsRector::MAX_LINE_LENGTH => $target]);
    }

    public static function provideInvalidTargets(): \Iterator
    {
        yield [0];
        yield [-1];
        yield ['120'];
        yield [null];
        yield [true];
    }

    private function runCommand(array $arguments): void
    {
        $output = [];
        exec(implode(' ', array_map(escapeshellarg(...), $arguments)).' 2>&1', $output, $status);
        $this->assertSame(0, $status, implode("\n", $output));
    }

    private function meaningfulTokens(string $source, bool $ignoreCommas = false): array
    {
        $tokens = array_filter(token_get_all($source), static fn ($token): bool => is_array($token)
            ? $token[0] !== T_WHITESPACE
            : (! $ignoreCommas || $token !== ','));

        return array_values(array_map(static fn ($token) => is_array($token) ? [$token[0], $token[1]] : $token, $tokens));
    }
}
