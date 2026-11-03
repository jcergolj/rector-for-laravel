<?php

declare(strict_types=1);

namespace Jcergolj\RectorForLaravel\Tests\CustomRules\WrapLongChainsRector;

use PHPUnit\Framework\Attributes\DataProvider;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;

class WrapLongChainsRectorTest extends AbstractRectorTestCase
{
    #[DataProvider('provideData')]
    public function test(string $filePath): void
    {
        $this->doTestFile($filePath);
    }

    #[DataProvider('provideData')]
    public function test_output_is_unchanged_on_a_second_run(string $filePath): void
    {
        $parts = explode("\n-----\n", file_get_contents($filePath), 2);
        $file = tempnam('/tmp/opencode', 'chains-repeat-');
        $fixture = $file.'.php.inc';

        try {
            file_put_contents($fixture, $parts[1] ?? $parts[0]);
            $this->doTestFile($fixture);
        } finally {
            unlink($fixture);
            unlink($file);
        }
    }

    public static function provideData(): \Iterator
    {
        yield from self::yieldFilesFromDirectory(__DIR__.'/Fixture');
        foreach (['MethodChains', 'PropertyChains'] as $rule) {
            foreach (glob(dirname(__DIR__).'/'.$rule.'/Fixture/*.php.inc') as $fixture) {
                yield $rule.'/'.basename($fixture) => [$fixture];
            }
        }
    }

    public function provideConfigFilePath(): string
    {
        return __DIR__.'/config/configured_rule.php';
    }
}
