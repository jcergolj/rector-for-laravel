<?php

namespace TrailingMiddlewareComments;

class TokenTest extends \PHPUnit\Framework\TestCase
{
    public function test_index(): void
    {
        $this->get('/tokens')->assertOk();
    } // This note belongs to the behavior test.

    public function test_auth(): void
    {
        $this->get('/tokens')->assertMiddlewareIsApplied('auth');
    }
}

class FinalCommentTest extends \PHPUnit\Framework\TestCase
{
    public function test_index(): void
    {
        $this->get('/tokens')->assertOk();
    }

    public function test_auth(): void
    {
        $this->get('/tokens')->assertMiddlewareIsApplied('auth');
    } // This note belongs to the middleware test.
}
