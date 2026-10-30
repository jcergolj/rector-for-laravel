<?php

namespace ProviderMiddlewareOrder;

class ExampleTest extends \PHPUnit\Framework\TestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('values')]
    public function test_value(string $value): void {}

    #[\PHPUnit\Framework\Attributes\DataProvider('routes')]
    public function test_auth(string $route): void
    {
        $this->get($route)->assertMiddlewareIsApplied('auth');
    }

    public static function values(): array { return [['value']]; }

    public static function routes(): array { return [['/']]; }
}
-----
<?php

namespace ProviderMiddlewareOrder;

class ExampleTest extends \PHPUnit\Framework\TestCase
{
    public static function routes(): array { return [['/']]; }

    #[\PHPUnit\Framework\Attributes\DataProvider('routes')]
    public function test_auth(string $route): void
    {
        $this->get($route)->assertMiddlewareIsApplied('auth');
    }

    public static function values(): array { return [['value']]; }

    #[\PHPUnit\Framework\Attributes\DataProvider('values')]
    public function test_value(string $value): void {}
}
