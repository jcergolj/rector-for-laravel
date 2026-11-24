<?php

use PHPUnit\Framework\Attributes\Depends as Needs;
use PHPUnit\Framework\TestCase;

class PrefixDependenciesTest extends TestCase
{
    public function test_produces(): int { return 1; }

    #[Needs('test_produces')]
    public function test_consumes(int $value): void {}

    public function test_legacy_producer(): void {}

    /** @depends clone test_legacy_producer */
    public function test_legacy_consumer(): void {}

    public function test_called(): void {}

    public function test_callable(): void {}

    public function helper(): void
    {
        $this->test_called();
        $callback = [$this, 'test_callable'];
    }
}
