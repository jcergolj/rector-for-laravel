<?php

declare(strict_types=1);

namespace Jcergolj\RectorForLaravel\Tests\CustomRules\ParameterLists;

use Jcergolj\RectorForLaravel\CustomRules\WrapLongListsRector;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ParameterListsWorkflowTest extends TestCase
{
    #[DataProvider('provideFixtures')]
    public function test_rector_and_pint_are_stable(string $fixture, string $config): void
    {
        $parts = explode("\n-----\n", file_get_contents($fixture), 2);
        $source = $parts[0].(isset($parts[1]) ? "\n" : '');
        $file = tempnam('/tmp/opencode', 'parameters-workflow-');
        $path = $file.'.php';
        $root = dirname(__DIR__, 3);

        try {
            file_put_contents($path, $source);
            $rector = $this->rectorCommand($path, $config);
            $pint = [PHP_BINARY, $root.'/vendor/bin/pint', $path, '--no-ansi'];
            $this->runCommand($rector);
            $this->assertSame($parts[1] ?? $source, file_get_contents($path));
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

        foreach (['arrays_first', 'parameters_first'] as $order) {
            foreach (glob(__DIR__.'/CompositionFixture/*.php.inc') as $fixture) {
                yield $order.'/'.basename($fixture) => [$fixture, __DIR__.'/config/'.$order.'.php'];
            }
        }
    }

    public function test_custom_target_and_path_skip(): void
    {
        $file = tempnam('/tmp/opencode', 'parameters-config-');
        $path = $file.'.php';
        $config = $file.'-config.php';
        $source = "<?php\n\nfunction report(string \$a, string \$b): void {}\n";

        try {
            $rule = '\\'.WrapLongListsRector::class;
            $configuration = "<?php\nreturn \\Rector\\Config\\RectorConfig::configure()->withConfiguredRule(".$rule."::class, ['max_line_length' => 20])";
            file_put_contents($config, $configuration.';');
            file_put_contents($path, $source);
            $command = $this->rectorCommand($path, $config);
            $this->runCommand($command);
            $this->assertSame("<?php\n\nfunction report(\n    string \$a,\n    string \$b,\n): void {}\n", file_get_contents($path));

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
        $file = tempnam('/tmp/opencode', 'parameters-crlf-');
        $path = $file.'.php';
        $literal = "'".str_repeat('x', 110)."'";
        $source = "<?php\r\n\r\nfunction report(string \$a = ".$literal.", string \$b = ''): void {}\r\n";

        try {
            file_put_contents($path, $source);
            $this->runCommand($this->rectorCommand($path, __DIR__.'/config/configured_rule.php'));
            $this->assertSame("<?php\r\n\r\nfunction report(\r\n    string \$a = ".$literal.",\r\n    string \$b = '',\r\n): void {}\r\n", file_get_contents($path));
        } finally {
            unlink($path);
            unlink($file);
        }
    }

    public function test_preserves_runtime_behavior(): void
    {
        $source = <<<'PHP'
<?php

#[Attribute(Attribute::TARGET_PARAMETER)]
class Label
{
    public function __construct(public string $value) {}
}
class Report
{
    public function __construct(#[Label('report, ()')] public readonly string $reportNameWithADescriptiveName, public readonly array $reportMetadataWithADescriptiveName = ['value' => "\0é\n"]) {}
}
function &updateReport(#[Label('reference')] string &$reportNameWithADescriptiveName, string $suffixWithADescriptiveName = ' default', string ...$additionalSuffixesWithDescriptiveNames): string
{
    $reportNameWithADescriptiveName .= $suffixWithADescriptiveName.implode('', $additionalSuffixesWithDescriptiveNames);
    return $reportNameWithADescriptiveName;
}
$name = 'report';
$reference =& updateReport($name, ' first', ' second');
$reference .= ' reference';
$closure = static function &(string &$reportNameWithADescriptiveName, string $suffixWithADescriptiveName = ' closure', string ...$additionalSuffixesWithDescriptiveNames) use (&$name): string {
    $name .= $suffixWithADescriptiveName.implode('', $additionalSuffixesWithDescriptiveNames);
    return $reportNameWithADescriptiveName;
};
$closure($name);
$arrow = static fn (string $reportNameWithADescriptiveName, string $suffixWithADescriptiveName = ' arrow', string ...$additionalSuffixesWithDescriptiveNames): string => $reportNameWithADescriptiveName.$suffixWithADescriptiveName.implode('', $additionalSuffixesWithDescriptiveNames);
$report = new Report($arrow($name));
$parameter = (new ReflectionFunction('updateReport'))->getParameters()[0];
echo json_encode([$name, $reference, $report, $parameter->getAttributes()[0]->newInstance()->value]);
PHP;
        $file = tempnam('/tmp/opencode', 'parameters-runtime-');
        $path = $file.'.php';

        try {
            file_put_contents($path, $source);
            $expected = $this->runCommand([PHP_BINARY, $path]);
            $this->runCommand($this->rectorCommand($path, __DIR__.'/config/configured_rule.php'));
            $this->assertNotSame($source, file_get_contents($path));
            $this->assertSame($expected, $this->runCommand([PHP_BINARY, $path]));
            $this->runCommand([PHP_BINARY, dirname(__DIR__, 3).'/vendor/bin/pint', $path, '--no-ansi']);
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
