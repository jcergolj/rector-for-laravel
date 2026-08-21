<?php

namespace Jcergolj\RectorForLaravel\Tests\CustomRules\AddBlankLineAfterPhpUnitAssertionRector\Fixture;

class ExampleTest extends \PHPUnit\Framework\TestCase
{
    public function testUserName(): void
    {
        $user = User::where('email', 'john@example.com')->first();
        $this->assertSame('John Doe', $user->name);
    }
}
-----
<?php

namespace Jcergolj\RectorForLaravel\Tests\CustomRules\AddBlankLineAfterPhpUnitAssertionRector\Fixture;

class ExampleTest extends \PHPUnit\Framework\TestCase
{
    public function testUserName(): void
    {
        $user = User::where('email', 'john@example.com')->first();

        $this->assertSame('John Doe', $user->name);
    }
}
