<?php

namespace ProviderMetadata;

use PHPUnit\Framework\Attributes\DataProvider as Cases;
use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    private string $value = 'value';

    protected function setUp(): void {}

    /** Test documentation. */
    #[Cases(methodName: 'values')]
    public function test_value(string $value): void
    {
        $this->assertSame($this->value, $value);
    }

    private function helper(): void {}

    /** @return array<string, array{string}> */
    public static function values(): array
    {
        return ['named case' => ['value']];
    }
}
-----
<?php

namespace ProviderMetadata;

use PHPUnit\Framework\Attributes\DataProvider as Cases;
use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    private string $value = 'value';

    protected function setUp(): void {}
    /** @return array<string, array{string}> */
    public static function values(): array
    {
        return ['named case' => ['value']];
    }

    /** Test documentation. */
    #[Cases(methodName: 'values')]
    public function test_value(string $value): void
    {
        $this->assertSame($this->value, $value);
    }

    private function helper(): void {}
}
