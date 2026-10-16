<?php

namespace MiddlewareSkipped;

class ProductionController
{
    public function test_index(): void
    {
    }

    public function test_auth(): void
    {
        $this->assertMiddlewareIsApplied('auth');
    }
}

class NoWiringTest extends \PHPUnit\Framework\TestCase
{
    public function test_behavior(): void
    {
    }

    public function test_auth_middleware_is_applied(): void
    {
        $this->assertTrue(true);
    }

    public function helper(): void
    {
        $this->assertMiddlewareIsApplied('auth');
    }

    public static function testCases(): array
    {
        $response->assertMiddlewareIsApplied('auth');

        return [];
    }

    public function test_callback(): void
    {
        $callback = function () {
            $this->assertMiddlewareIsApplied('auth');
        };
        $arrow = fn () => $this->assertMiddlewareIsApplied('auth');
        $object = new class
        {
            public function run(): void
            {
                $this->assertMiddlewareIsApplied('auth');
            }
        };
        $callable = $this->assertMiddlewareIsApplied(...);
    }
}
