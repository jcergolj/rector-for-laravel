<?php

use App\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PrefixImportCollisionTest extends TestCase
{
    #[Test]
    public function test_example(): void {}
}
-----
<?php

use App\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PrefixImportCollisionTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    #[Test]
    public function example(): void {}
}
