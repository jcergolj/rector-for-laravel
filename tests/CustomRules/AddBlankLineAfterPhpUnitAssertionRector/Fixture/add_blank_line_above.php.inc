<?php

namespace Jcergolj\RectorForLaravel\Tests\CustomRules\AddBlankLineAfterPhpUnitAssertionRector\Fixture;

class ExampleTest extends \PHPUnit\Framework\TestCase
{
    public function testFirstAssertionGetsBlankLineAbove(): void
    {
        $user = User::factory()->create();
        $this->assertEquals(1, 1);
        $this->assertTrue(true);
    }
}
-----
<?php

namespace Jcergolj\RectorForLaravel\Tests\CustomRules\AddBlankLineAfterPhpUnitAssertionRector\Fixture;

class ExampleTest extends \PHPUnit\Framework\TestCase
{
    public function testFirstAssertionGetsBlankLineAbove(): void
    {
        $user = User::factory()->create();

        $this->assertEquals(1, 1);

        $this->assertTrue(true);
    }
}
