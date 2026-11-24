<?php

declare(strict_types=1);

namespace Jcergolj\RectorForLaravel\Tests\CustomRules\AdoptProjectFormRequestRector;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AdoptProjectFormRequestRectorTest extends TestCase
{
    #[DataProvider('fixtures')]
    public function test_rewrites_only_equivalent_requests(string $fixture, string $baseClass): void
    {
        [$input, $expected] = array_pad(explode("-----\n", file_get_contents(__DIR__.'/Fixture/'.$fixture.'.php.inc'), 2), 2, null);
        $file = tempnam('/tmp/opencode', 'form-request-');
        $path = $file.'.php';
        $config = $file.'-config.php';
        $root = dirname(__DIR__, 3);

        try {
            file_put_contents($path, $input);
            file_put_contents($config, '<?php return \Rector\Config\RectorConfig::configure()'
                .'->withAutoloadPaths(['.var_export(__DIR__.'/Source', true).'])'
                .'->withConfiguredRule(\Jcergolj\RectorForLaravel\CustomRules\AdoptProjectFormRequestRector::class, '
                .var_export(['base_class' => $baseClass], true).');');

            $command = [PHP_BINARY, $root.'/vendor/bin/rector', 'process', $path, '--config='.$config, '--no-progress-bar', '--no-ansi', '--debug', '--clear-cache'];

            $this->runRector($command);
            $this->assertSame($expected ?? $input, file_get_contents($path));

            $this->runRector($command);
            $this->assertSame($expected ?? $input, file_get_contents($path), 'Second Rector run changed the result.');
        } finally {
            unlink($config);
            unlink($path);
            unlink($file);
        }
    }

    public static function fixtures(): \Iterator
    {
        yield 'conversion and skips' => ['basic', 'App\Requests\AppFormRequest'];
        yield 'lifecycle override' => ['lifecycle', 'App\Requests\LifecycleFormRequest'];
        yield 'dynamic validation hook' => ['lifecycle', 'App\Requests\AfterFormRequest'];
        yield 'unresolved base' => ['lifecycle', 'App\Requests\MissingFormRequest'];
        yield 'grouped import conflict' => ['import_conflict', 'App\Requests\AppFormRequest'];
    }

    private function runRector(array $command): void
    {
        exec(implode(' ', array_map(escapeshellarg(...), $command)).' 2>&1', $output, $status);

        $this->assertSame(0, $status, implode("\n", $output));
    }
}
