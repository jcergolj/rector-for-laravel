<?php

namespace Jcergolj\RectorForLaravel\Tests\CustomRules\PhpUnitTestDocblockToAttributeRector\Fixture;

use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    /**
     * @test
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
    #[Test]
    public function user_can_log_in(): void
    {
    }
}
