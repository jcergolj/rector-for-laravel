<?php

declare(strict_types=1);

namespace Jcergolj\RectorForLaravel\Tests\CustomRules\ArgumentLists;

use Jcergolj\RectorForLaravel\CustomRules\WrapLongListsRector;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ArgumentListsWorkflowTest extends TestCase
{
    #[DataProvider('provideFixtures')]
    public function test_rector_and_pint_are_stable(string $fixture, string $config): void
    {
        $parts = explode("\n-----\n", file_get_contents($fixture), 2);
        $source = $parts[0].(isset($parts[1]) ? "\n" : '');
        $file = tempnam('/tmp/opencode', 'arguments-workflow-');
        $path = $file.'.php';
        $root = dirname(__DIR__, 3);

        try {
            file_put_contents($path, $source);
            $rector = $this->rectorCommand($path, $config);
            $pint = [PHP_BINARY, $root.'/vendor/bin/pint', $path, '--no-ansi'];
            $this->runCommand($rector);
            if (str_contains($fixture, '/CompositionFixture/') && isset($parts[1])) {
                $this->assertSame($parts[1], file_get_contents($path));
            }
            $this->assertSame($this->meaningfulTokens($source), $this->meaningfulTokens(file_get_contents($path)));
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

        foreach (['arrays_first', 'arguments_first'] as $order) {
            foreach (glob(__DIR__.'/CompositionFixture/*.php.inc') as $fixture) {
                yield $order.'/'.basename($fixture) => [$fixture, __DIR__.'/config/'.$order.'.php'];
            }
        }
    }

    public function test_custom_target_and_path_skip(): void
    {
        $file = tempnam('/tmp/opencode', 'arguments-config-');
        $path = $file.'.php';
        $config = $file.'-config.php';
        $source = "<?php\n\nsend('message', \$value);\n";

        try {
            $rule = '\\'.WrapLongListsRector::class;
            $configuration = "<?php\nreturn \\Rector\\Config\\RectorConfig::configure()->withConfiguredRule(".$rule."::class, ['max_line_length' => 20])";
            file_put_contents($config, $configuration.';');
            file_put_contents($path, $source);
            $command = $this->rectorCommand($path, $config);
            $this->runCommand($command);
            $this->assertSame("<?php\n\nsend(\n    'message',\n    \$value,\n);\n", file_get_contents($path));

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

    public function test_preserves_crlf(): void
    {
        $file = tempnam('/tmp/opencode', 'arguments-crlf-');
        $path = $file.'.php';
        $literal = "'".str_repeat('x', 110)."'";
        $source = "<?php\r\n\r\nsend(".$literal.", \$value);\r\n";

        try {
            file_put_contents($path, $source);
            $this->runCommand($this->rectorCommand($path, __DIR__.'/config/configured_rule.php'));
            $this->assertSame("<?php\r\n\r\nsend(\r\n    ".$literal.",\r\n    \$value,\r\n);\r\n", file_get_contents($path));
        } finally {
            unlink($path);
            unlink($file);
        }
    }

    public function test_keeps_evaluation_order_references_and_string_bytes(): void
    {
        $source = <<<'PHP'
<?php

$trace = [];
$target = "\0é\n";
function nextValue(string $value): string
{
    global $trace;
    $trace[] = $value;
    return $value;
}
function combine(string &$target, string $first, string $second): void
{
    $target .= $first.$second;
}
combine($target, nextValue('the first argument has enough text to force a sensible wrapping point in this call'), second: nextValue('second'));
combine($target, ...['first' => nextValue('third'), 'second' => nextValue('the fourth argument also makes this unpacked call exceed the soft target')]);
echo json_encode([$target, $trace]);
PHP;
        $file = tempnam('/tmp/opencode', 'arguments-runtime-');
        $path = $file.'.php';
        $root = dirname(__DIR__, 3);

        try {
            file_put_contents($path, $source);
            $expected = $this->runCommand([PHP_BINARY, $path]);
            $this->runCommand($this->rectorCommand($path, __DIR__.'/config/configured_rule.php'));
            $this->assertNotSame($source, file_get_contents($path));
            $this->assertSame($expected, $this->runCommand([PHP_BINARY, $path]));
            $this->runCommand([PHP_BINARY, $root.'/vendor/bin/pint', $path, '--no-ansi']);
            $this->assertSame($expected, $this->runCommand([PHP_BINARY, $path]));
        } finally {
            unlink($path);
            unlink($file);
        }
    }

    #[DataProvider('provideInvalidTargets')]
    public function test_rejects_invalid_targets(mixed $target): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new WrapLongListsRector)->configure([WrapLongListsRector::MAX_LINE_LENGTH => $target]);
    }

    public static function provideInvalidTargets(): \Iterator
    {
        yield [0];
        yield [-1];
        yield ['120'];
        yield [null];
        yield [true];
    }

    private function rectorCommand(string $path, string $config): array
    {
        return [PHP_BINARY, dirname(__DIR__, 3).'/vendor/bin/rector', 'process', $path, '--config='.$config, '--debug', '--no-progress-bar', '--no-ansi', '--clear-cache'];
    }

    private function runCommand(array $arguments): string
    {
        $output = [];
        exec(implode(' ', array_map(escapeshellarg(...), $arguments)).' 2>&1', $output, $status);
        $this->assertSame(0, $status, implode("\n", $output));

        return implode("\n", $output);
    }

    private function meaningfulTokens(string $source): array
    {
        $tokens = array_filter(token_get_all($source), static fn ($token): bool => is_array($token)
            ? $token[0] !== T_WHITESPACE
            : $token !== ',');

        return array_values(array_map(static fn ($token) => is_array($token) ? [$token[0], $token[1]] : $token, $tokens));
    }
}
