<?php

namespace Jcergolj\RectorForLaravel\Tests\CustomRules\PhpUnitTestDocblockToAttributeRector\Fixture;

use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    /**
     * @test
     * @covers \App\Actions\LoginUser::handle
     */
    public function user_can_log_in(): void
    {
    }
}
-----
<?php

namespace Jcergolj\RectorForLaravel\Tests\CustomRules\PhpUnitTestDocblockToAttributeRector\Fixture;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    /**
     * @covers \App\Actions\LoginUser::handle
     */
    #[Test]
    public function user_can_log_in(): void
    {
    }
}
