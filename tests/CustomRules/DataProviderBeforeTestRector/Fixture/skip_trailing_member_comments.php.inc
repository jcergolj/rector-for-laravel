<?php

namespace TrailingProviderComments;

class ExampleTest extends \PHPUnit\Framework\TestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('values')]
    public function test_value(string $value): void {} // Keep with test.

    public static function values(): array { return [['value']]; }
}
