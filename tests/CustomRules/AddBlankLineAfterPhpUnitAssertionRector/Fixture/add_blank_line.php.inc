<?php

namespace Jcergolj\RectorForLaravel\Tests\CustomRules\AddBlankLineAfterPhpUnitAssertionRector\Fixture;

class ExampleTest extends \PHPUnit\Framework\TestCase
{
    public function testAssertionsGetBlankLine(): void
    {
        $this->assertTrue(true);
        $this->assertEquals(1, 1);
    }
}
-----
<?php

namespace Jcergolj\RectorForLaravel\Tests\CustomRules\AddBlankLineAfterPhpUnitAssertionRector\Fixture;

class ExampleTest extends \PHPUnit\Framework\TestCase
{
    public function testAssertionsGetBlankLine(): void
    {
        $this->assertTrue(true);

        $this->assertEquals(1, 1);
    }
}
