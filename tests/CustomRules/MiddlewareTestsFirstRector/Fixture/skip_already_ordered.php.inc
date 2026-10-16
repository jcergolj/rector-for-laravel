<?php

namespace MiddlewareOrdered;

class OrderedTest extends \PHPUnit\Framework\TestCase
{
    protected $buyer;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_auth(): void
    {
        $this->assertMiddlewareIsApplied('auth');
    }

    public function test_buyer(): void
    {
        $this->assertMiddlewareIsApplied('buyer');
    }

    public function test_index(): void
    {
        $this->assertTrue(true);
    }
}
