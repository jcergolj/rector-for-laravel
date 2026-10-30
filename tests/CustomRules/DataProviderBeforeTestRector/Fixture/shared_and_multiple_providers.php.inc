<?php

namespace SharedProviders;

class ExampleTest extends \PHPUnit\Framework\TestCase
{
    public static function unused(): array { return []; }

    public static function second(): array { return [[2]]; }

    public static function shared(): array { return [[1]]; }

    public function test_unrelated(): void {}

    #[\PHPUnit\Framework\Attributes\DataProvider('shared')]
    #[\PHPUnit\Framework\Attributes\DataProvider('second')]
    public function test_first(int $value): void {}

    #[\PHPUnit\Framework\Attributes\DataProvider('shared')]
    public function test_second(int $value): void {}
}
-----
<?php

namespace SharedProviders;

class ExampleTest extends \PHPUnit\Framework\TestCase
{
    public static function unused(): array { return []; }

    public function test_unrelated(): void {}
    public static function shared(): array { return [[1]]; }
    public static function second(): array { return [[2]]; }

    #[\PHPUnit\Framework\Attributes\DataProvider('shared')]
    #[\PHPUnit\Framework\Attributes\DataProvider('second')]
    public function test_first(int $value): void {}

    #[\PHPUnit\Framework\Attributes\DataProvider('shared')]
    public function test_second(int $value): void {}
}
