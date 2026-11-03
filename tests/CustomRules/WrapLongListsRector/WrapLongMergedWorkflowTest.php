<?php

declare(strict_types=1);

namespace Jcergolj\RectorForLaravel\Tests\CustomRules\WrapLongListsRector;

use Jcergolj\RectorForLaravel\CustomRules\WrapLongChainsRector;
use Jcergolj\RectorForLaravel\CustomRules\WrapLongListsRector;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class WrapLongMergedWorkflowTest extends TestCase
{
    #[DataProvider('provideOrders')]
    public function test_merged_rules_and_pint_are_stable(array $rules): void
    {
        $this->withFiles(function (string $path, string $config) use ($rules): void {
            $source = <<<'PHP'
<?php

function reports(string $firstReportWithADescriptiveName, string $secondReportWithADescriptiveName, array $reportOptionsWithADescriptiveName = []): void
{
    $builder->configure('a descriptive first argument for the report builder', 'another descriptive argument for the builder')->metadata->withValues(['first_name' => 'first', 'last_name' => 'last', 'email' => 'person@example.com'])->build();
}
PHP;
            file_put_contents($path, $source);
            file_put_contents($config, '<?php return \Rector\Config\RectorConfig::configure()->withRules('.var_export($rules, true).');');
            $this->runRector($path, $config);
            $output = file_get_contents($path);

            $this->assertStringContainsString("function reports(\n", $output);
            $this->assertStringContainsString("->configure(\n", $output);
            $this->assertStringContainsString("\n        ->metadata\n", $output);
            $this->assertStringContainsString("'first_name' => 'first',\n", $output);
            $this->assertSame($this->meaningfulTokens($source), $this->meaningfulTokens($output));
            $this->runCommand([PHP_BINARY, '-l', $path]);
            $pint = [PHP_BINARY, dirname(__DIR__, 3).'/vendor/bin/pint', $path, '--no-ansi'];
            $this->runCommand($pint);
            $firstCycle = file_get_contents($path);

            $this->runRector($path, $config);
            $this->assertSame($firstCycle, file_get_contents($path));
            $this->runCommand($pint);
            $this->assertSame($firstCycle, file_get_contents($path));
        });
    }

    public static function provideOrders(): \Iterator
    {
        yield 'lists first' => [[WrapLongListsRector::class, WrapLongChainsRector::class]];
        yield 'chains first' => [[WrapLongChainsRector::class, WrapLongListsRector::class]];
    }

    #[DataProvider('provideRules')]
    public function test_custom_target_path_skip_and_crlf(string $rule, string $source): void
    {
        $this->withFiles(function (string $path, string $config) use ($rule, $source): void {
            $source = "<?php\r\n\r\n".$source."\r\n";
            $configuration = '<?php return \Rector\Config\RectorConfig::configure()->withConfiguredRule('.var_export($rule, true).", ['max_line_length' => 20])";
            file_put_contents($path, $source);
            file_put_contents($config, $configuration.';');
            $this->runRector($path, $config);
            $output = file_get_contents($path);

            $this->assertNotSame($source, $output);
            $this->assertStringNotContainsString("\n", str_replace("\r\n", '', $output));

            file_put_contents($path, $source);
            file_put_contents($config, $configuration.'->withSkip(['.var_export($rule, true).' => ['.var_export($path, true).']]);');
            $this->runRector($path, $config);
            $this->assertSame($source, file_get_contents($path));
        });
    }

    public static function provideRules(): \Iterator
    {
        yield [WrapLongListsRector::class, "\$values = ['first', 'second', 'third'];"];
        yield [WrapLongChainsRector::class, '$value = $report->customer->address->format();'];
    }

    #[DataProvider('provideInvalidTargets')]
    public function test_rejects_invalid_targets(string $rule, mixed $target): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new $rule)->configure(['max_line_length' => $target]);
    }

    public static function provideInvalidTargets(): \Iterator
    {
        foreach ([WrapLongListsRector::class, WrapLongChainsRector::class] as $rule) {
            foreach ([0, -1, '120', null, true] as $target) {
                yield [$rule, $target];
            }
        }
    }

    private function withFiles(callable $test): void
    {
        $file = tempnam('/tmp/opencode', 'merged-workflow-');
        $path = $file.'.php';
        $config = $file.'-config.php';
        try {
            $test($path, $config);
        } finally {
            foreach ([$path, $config, $file] as $created) {
                if (file_exists($created)) {
                    unlink($created);
                }
            }
        }
    }

    private function runRector(string $path, string $config): void
    {
        $this->runCommand([PHP_BINARY, dirname(__DIR__, 3).'/vendor/bin/rector', 'process', $path, '--config='.$config, '--debug', '--no-progress-bar', '--no-ansi', '--clear-cache']);
    }

    private function runCommand(array $arguments): void
    {
        exec(implode(' ', array_map(escapeshellarg(...), $arguments)).' 2>&1', $output, $status);
        $this->assertSame(0, $status, implode("\n", $output));
    }

    private function meaningfulTokens(string $source): array
    {
        $tokens = array_filter(token_get_all($source), static fn ($token): bool => is_array($token) ? $token[0] !== T_WHITESPACE : $token !== ',');

        return array_values(array_map(static fn ($token) => is_array($token) ? [$token[0], $token[1]] : $token, $tokens));
    }
}
