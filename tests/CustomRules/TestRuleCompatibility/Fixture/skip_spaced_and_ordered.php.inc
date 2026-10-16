<?php

namespace StableCombinedTestRules;

class TokenTest extends \PHPUnit\Framework\TestCase
{
    public function test_auth(): void
    {
        $this->get('/tokens')->assertMiddlewareIsApplied('auth');
    }

    public function test_tokens(): void
    {
        $buyer = User::factory()->create();

        $token = Token::factory()->for($buyer)->create();

        $this->assertNotNull($buyer);

        $this->assertNotNull($token);
    }
}
