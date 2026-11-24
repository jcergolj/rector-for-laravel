<?php

use PHPUnit\Framework\Attributes\{Test as CaseTest};
use PHPUnit\Framework\TestCase;

class PrefixAliasedTest extends TestCase
{
    #[CaseTest]
    public function test_already_marked(): void {}

    public function test_needs_marking(): void {}
}
-----
<?php

use PHPUnit\Framework\Attributes\{Test as CaseTest};
use PHPUnit\Framework\TestCase;

class PrefixAliasedTest extends TestCase
{
    #[CaseTest]
    public function already_marked(): void {}

    #[CaseTest]
    public function needs_marking(): void {}
}
