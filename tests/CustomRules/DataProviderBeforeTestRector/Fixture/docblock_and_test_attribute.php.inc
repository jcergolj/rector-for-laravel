<?php

namespace LegacyProviders;

class ExampleTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider values
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function accepts_value(string $value): void {}

    public static function values(): array { return [['value']]; }
}
-----
<?php

namespace LegacyProviders;

class ExampleTest extends \PHPUnit\Framework\TestCase
{
    public static function values(): array { return [['value']]; }
    /**
     * @dataProvider values
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function accepts_value(string $value): void {}
}
