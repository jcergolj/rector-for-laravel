<?php

use PHPUnit\Framework\TestCase;

class PrefixNotATest
{
    public function test_untouched(): void {}
}

abstract class PrefixBaseTest extends TestCase
{
    protected function Inherited(): void {}
}

class PrefixCollisionTest extends PrefixBaseTest
{
    public function test_inherited(): void {}
    public function test_local(): void {}
    private function Local(): void {}

    protected function test_hidden(): void {}
    public static function test_static(): void {}
    public function test_(): void {}
    public function test_123(): void {}
    public function test___construct(): void {}
    public function test_test_repeated(): void {}
}
