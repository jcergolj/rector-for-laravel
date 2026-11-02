<?php

declare(strict_types=1);

namespace Jcergolj\RectorForLaravel\Tests\CustomRules\BlankLineBetweenMultilinePropertyAssignmentsRector;

use Jcergolj\RectorForLaravel\CustomRules\BlankLineBetweenMultilinePropertyAssignmentsRector;
use Jcergolj\RectorForLaravel\CustomRules\WrapLongPropertyAccessChainsRector;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class BlankLineBetweenMultilinePropertyAssignmentsWorkflowTest extends TestCase
{
    #[DataProvider('provideOrders')]
    public function test_spacing_is_stable_with_pint_and_wrapping_enabled(bool $spacingFirst): void
    {
        $file = tempnam('/tmp/opencode', 'property-spacing-workflow-');
        $path = $file.'.php';
        $config = $file.'-config.php';
        $root = dirname(__DIR__, 3);
        $rules = [BlankLineBetweenMultilinePropertyAssignmentsRector::class, WrapLongPropertyAccessChainsRector::class];
        if (! $spacingFirst) {
            $rules = array_reverse($rules);
        }

        $parts = explode("\n-----\n", file_get_contents(__DIR__.'/Fixture/payload.php.inc'), 2);
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

    private function runCommand(array $arguments): void
    {
        $output = [];
        exec(implode(' ', array_map(escapeshellarg(...), $arguments)).' 2>&1', $output, $status);
        $this->assertSame(0, $status, implode("\n", $output));
    }
}
