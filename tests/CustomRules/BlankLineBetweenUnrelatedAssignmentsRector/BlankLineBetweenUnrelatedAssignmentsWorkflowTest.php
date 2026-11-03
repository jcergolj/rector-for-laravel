<?php

declare(strict_types=1);

namespace Jcergolj\RectorForLaravel\Tests\CustomRules\BlankLineBetweenUnrelatedAssignmentsRector;

use Jcergolj\RectorForLaravel\CustomRules\BlankLineBetweenUnrelatedAssignmentsRector;
use Jcergolj\RectorForLaravel\CustomRules\WrapLongChainsRector;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class BlankLineBetweenUnrelatedAssignmentsWorkflowTest extends TestCase
{
    #[DataProvider('provideOrders')]
    public function test_spacing_is_stable_with_pint_and_wrapping_enabled(bool $spacingFirst): void
    {
        $file = tempnam('/tmp/opencode', 'property-spacing-workflow-');
        $path = $file.'.php';
        $config = $file.'-config.php';
        $root = dirname(__DIR__, 3);
        $rules = [BlankLineBetweenUnrelatedAssignmentsRector::class, WrapLongChainsRector::class];
        if (! $spacingFirst) {
            $rules = array_reverse($rules);
        }

        $parts = explode("\n-----\n", file_get_contents(__DIR__.'/CompositionFixture/unrelated_chains.php.inc'), 2);
        $source = $parts[0]."\n";

        try {
            file_put_contents($path, $source);
            file_put_contents($config, '<?php return \\Rector\\Config\\RectorConfig::configure()->withRules('.var_export($rules, true).');');
            $rector = [PHP_BINARY, $root.'/vendor/bin/rector', 'process', $path, '--config='.$config, '--no-progress-bar', '--no-ansi', '--clear-cache'];
            $pint = [PHP_BINARY, $root.'/vendor/bin/pint', $path, '--no-ansi'];

            $this->runCommand($rector);
            $this->assertSame($parts[1], file_get_contents($path));

            $this->runCommand($pint);
            $firstCycle = file_get_contents($path);
            $this->assertStringContainsString("->questionTwo;\n\n", $firstCycle);

            $this->runCommand($rector);
            $this->assertSame($firstCycle, file_get_contents($path));

            $this->runCommand($pint);
            $this->assertSame($firstCycle, file_get_contents($path));
        } finally {
            unlink($path);
            unlink($config);
            unlink($file);
        }
    }

    public static function provideOrders(): \Iterator
    {
        yield 'spacing first' => [true];
        yield 'wrapping first' => [false];
    }

    public function test_path_skips(): void
    {
        $file = tempnam('/tmp/opencode', 'assignment-spacing-skip-');
        $path = $file.'.php';
        $config = $file.'-config.php';
        $source = "<?php\n\n\$first = 1;\n\$second = 2;\n";

        try {
            file_put_contents($path, $source);
            file_put_contents($config, '<?php return \\Rector\\Config\\RectorConfig::configure()->withRules(['.BlankLineBetweenUnrelatedAssignmentsRector::class.'::class])->withSkip(['.BlankLineBetweenUnrelatedAssignmentsRector::class.'::class => ['.var_export($path, true).']]);');
            $this->runCommand([PHP_BINARY, dirname(__DIR__, 3).'/vendor/bin/rector', 'process', $path, '--config='.$config, '--no-progress-bar', '--no-ansi', '--clear-cache']);

            $this->assertSame($source, file_get_contents($path));
        } finally {
            unlink($path);
            unlink($config);
            unlink($file);
        }
    }

    public function test_preserves_runtime_behavior_and_crlf(): void
    {
        $source = <<<'PHP'
<?php

class Report
{
    public object $user;
    public string $name;
    public function fill(): array
    {
        $this->user = (object) ['name' => 'Jane'];
        $this->name = $this->user->name;
        $total = 10;
        $tax = $total * 0.2;
        $enabled = true;
        $base = 3;
        $callback = fn () => $base * 2;
        return [$this->name, $tax, $enabled, $callback()];
    }
}
$report = new Report;
echo json_encode($report->fill());
PHP;
        $source = str_replace("\n", "\r\n", $source)."\r\n";
        $file = tempnam('/tmp/opencode', 'assignment-spacing-runtime-');
        $path = $file.'.php';

        try {
            file_put_contents($path, $source);
            $expected = $this->runCommand([PHP_BINARY, $path]);
            $this->runCommand([PHP_BINARY, dirname(__DIR__, 3).'/vendor/bin/rector', 'process', $path, '--config='.__DIR__.'/config/configured_rule.php', '--no-progress-bar', '--no-ansi', '--clear-cache']);
            $output = file_get_contents($path);

            $this->assertStringContainsString("\$this->name = \$this->user->name;\r\n\r\n", $output);
            $this->assertStringNotContainsString("\n", str_replace("\r\n", '', $output));
            $this->assertSame($expected, $this->runCommand([PHP_BINARY, $path]));
            $this->runCommand([PHP_BINARY, '-l', $path]);
        } finally {
            unlink($path);
            unlink($file);
        }
    }

    private function runCommand(array $arguments): string
    {
        $output = [];
        exec(implode(' ', array_map(escapeshellarg(...), $arguments)).' 2>&1', $output, $status);
        $this->assertSame(0, $status, implode("\n", $output));

        return implode("\n", $output);
    }
}
