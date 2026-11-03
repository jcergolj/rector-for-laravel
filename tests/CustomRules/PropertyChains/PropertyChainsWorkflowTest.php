<?php

declare(strict_types=1);

namespace Jcergolj\RectorForLaravel\Tests\CustomRules\PropertyChains;

use Jcergolj\RectorForLaravel\CustomRules\WrapLongChainsRector;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PropertyChainsWorkflowTest extends TestCase
{
    #[DataProvider('provideFixtures')]
    public function test_rector_and_pint_are_stable(string $fixture, string $config): void
    {
        $parts = explode("\n-----\n", file_get_contents($fixture), 2);
        $source = $parts[0].(isset($parts[1]) ? "\n" : '');
        $file = tempnam('/tmp/opencode', 'property-workflow-');
        $path = $file.'.php';
        $root = dirname(__DIR__, 3);

        try {
            file_put_contents($path, $source);
            $rector = [PHP_BINARY, $root.'/vendor/bin/rector', 'process', $path, '--config='.$config, '--no-progress-bar', '--no-ansi', '--clear-cache'];
            $pint = [PHP_BINARY, $root.'/vendor/bin/pint', $path, '--no-ansi'];
            $this->runCommand($rector);
            if (isset($parts[1])) {
                $this->assertSame($parts[1], file_get_contents($path));
            }
            $ignoreCommas = basename($config) !== 'configured_rule.php';
            $this->assertSame($this->meaningfulTokens($source, $ignoreCommas), $this->meaningfulTokens(file_get_contents($path), $ignoreCommas));
            $this->runCommand([PHP_BINARY, '-l', $path]);
            $this->runCommand($pint);
            $firstCycle = file_get_contents($path);

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
        foreach (['arrays_first', 'properties_first'] as $order) {
            foreach (glob(__DIR__.'/CompositionFixture/*.php.inc') as $fixture) {
                yield $order.'/'.basename($fixture) => [$fixture, __DIR__.'/config/'.$order.'.php'];
            }
        }
    }

    public function test_custom_target_and_path_skip(): void
    {
        $file = tempnam('/tmp/opencode', 'property-config-');
        $path = $file.'.php';
        $config = $file.'-config.php';
        $source = "<?php\n\n\$value = \$object->first->second;\n";
        $root = dirname(__DIR__, 3);

        try {
            $rule = '\\'.WrapLongChainsRector::class;
            $configuration = "<?php\nreturn \\Rector\\Config\\RectorConfig::configure()->withConfiguredRule(".$rule."::class, ['max_line_length' => 20])";
            file_put_contents($config, $configuration.';');
            file_put_contents($path, $source);
            $command = [PHP_BINARY, $root.'/vendor/bin/rector', 'process', $path, '--config='.$config, '--no-progress-bar', '--no-ansi', '--clear-cache'];
            $this->runCommand($command);
            $this->assertStringContainsString("\n    ->second;", file_get_contents($path));

            file_put_contents($config, $configuration.'->withSkip(['.$rule.'::class => ['.var_export($path, true).']]);');
            file_put_contents($path, $source);
            $this->runCommand($command);
            $this->assertSame($source, file_get_contents($path));

            $source = "<?php\r\n\r\n\$value = \$object->first->second;\r\n";
            file_put_contents($config, $configuration.';');
            file_put_contents($path, $source);
            $this->runCommand($command);
            $this->assertSame(str_replace('->second', "\r\n    ->second", $source), file_get_contents($path));
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
        (new WrapLongChainsRector)->configure([WrapLongChainsRector::MAX_LINE_LENGTH => $target]);
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

    private function meaningfulTokens(string $source, bool $ignoreCommas): array
    {
        $tokens = array_filter(token_get_all($source), static fn ($token): bool => is_array($token)
            ? $token[0] !== T_WHITESPACE
            : (! $ignoreCommas || $token !== ','));

        return array_values(array_map(static fn ($token) => is_array($token) ? [$token[0], $token[1]] : $token, $tokens));
    }
}
