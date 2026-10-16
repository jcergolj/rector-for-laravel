<?php

namespace FixtureSkipped;

class ProductionService
{
    public function test_fixtures(): void
    {
        $buyer = User::factory()->create();
        $token = Token::factory()->create();
    }

    public function setUp(): void
    {
        $buyer = User::factory()->create();
        $token = Token::factory()->create();
    }
}

trait FixtureTrait
{
    public function test_fixtures(): void
    {
        $buyer = User::factory()->create();
        $token = Token::factory()->create();
    }
}

class TokenTest extends \PHPUnit\Framework\TestCase
{
    public function helper(): void
    {
        $buyer = User::factory()->create();
        $token = Token::factory()->create();
    }

    public static function testCases(): array
    {
        $buyer = User::factory()->create();
        $token = Token::factory()->create();

        return [[$buyer, $token]];
    }

    public function test_unsupported_forms(): void
    {
        $first = 1;
        $second = 2;
        $buyer = User::factory()->create();
        $buyer = User::factory()->make();
        $tokens[] = Token::factory()->create();
        $this->{$property} = Token::factory()->create();
        $$variable = Token::factory()->create();
        [$one, $two] = Token::factory()->create();
        $reference =& $buyer;
        $chained = $other = Token::factory()->create();
        $dynamic = $model::factory()->create();
        $callable = Token::factory(...);
        $builderCallable = Token::factory()->create(...);
        $dynamicMethod = Token::factory()->{$method}();
        $nullsafe = Token::factory()?->create();
        $response = $this->get('/tokens');
        $token = new Token;
        $lookup = Token::first();

        if (true) {
            $nested = Token::factory()->create();
            $another = Token::factory()->create();
        }

        $callback = function () {
            $nested = Token::factory()->create();
            $another = Token::factory()->create();
        };
    }
}
