<?php

namespace FixtureSpaced;

class TokenTest extends \PHPUnit\Framework\TestCase
{
    public function test_fixtures(): void
    {
        $buyer = User::factory()->create();

        $first = Token::factory()->create();


        $second = Token::factory()->create();

        // Keep the existing separator above this comment.
        $third = Token::factory()->create();
    }
}
