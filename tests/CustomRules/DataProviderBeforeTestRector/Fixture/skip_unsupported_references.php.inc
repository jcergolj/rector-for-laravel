<?php

namespace UnsupportedProviders;

class ExampleTest extends \PHPUnit\Framework\TestCase
{
    #[\PHPUnit\Framework\Attributes\DataProviderExternal(ExternalCases::class, 'values')]
    public function test_external(string $value): void {}

    #[\PHPUnit\Framework\Attributes\DataProvider('inheritedValues')]
    public function test_inherited(string $value): void {}

    #[\PHPUnit\Framework\Attributes\DataProvider(self::PROVIDER)]
    public function test_dynamic(string $value): void {}

    /** @dataProvider ExternalCases::values */
    public function test_legacy_external(string $value): void {}

    public static function values(): array { return [['value']]; }
}

class NotATest
{
    #[\PHPUnit\Framework\Attributes\DataProvider('values')]
    public function test_value(string $value): void {}

    public static function values(): array { return [['value']]; }
}

trait TestTrait
{
    #[\PHPUnit\Framework\Attributes\DataProvider('values')]
    public function test_value(string $value): void {}

    public static function values(): array { return [['value']]; }
}
