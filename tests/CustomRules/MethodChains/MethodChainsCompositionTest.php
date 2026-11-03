<?php

declare(strict_types=1);

namespace Jcergolj\RectorForLaravel\Tests\CustomRules\MethodChains;

use PHPUnit\Framework\Attributes\DataProvider;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;

class MethodChainsCompositionTest extends AbstractRectorTestCase
{
    #[DataProvider('provideFixtures')]
    public function test_array_and_chain_rules_produce_the_combined_layout(string $fixture): void
    {
        $this->doTestFile($fixture);
    }

    public static function provideFixtures(): \Iterator
    {
        return self::yieldFilesFromDirectory(__DIR__.'/CompositionFixture');
    }

    public function provideConfigFilePath(): string
    {
        return __DIR__.'/config/arrays_first.php';
    }
}
