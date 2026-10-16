<?php

namespace DynamicMiddlewareNames;

class TokenTest extends \PHPUnit\Framework\TestCase
{
    public function test_index(): void
    {
        $this->get('/tokens')->assertOk();
    }

    public function test_dynamic_call(): void
    {
        $assertMiddlewareIsApplied = 'assertOk';
        $this->get('/tokens')->$assertMiddlewareIsApplied();
    }

    public function test_dynamic_static_call(): void
    {
        $assertMiddlewareIsApplied = 'assertTrue';
        self::$assertMiddlewareIsApplied(true);
    }
}
