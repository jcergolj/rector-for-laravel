<?php

namespace Tests;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
}

namespace FixtureSetup;

class TokenTest extends \Tests\TestCase
{
    protected $buyer;

    protected $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buyer = User::factory()->create();
        // This token belongs to the buyer.
        $this->token = Token::factory()
            ->for($this->buyer, 'buyer')
            ->create([
                'name' => 'first',
            ]);
        $other = Token::factory()->make();
    }

    /** @test */
    public function comments_stay_with_fixtures(): void
    {
        $buyer = User::factory()->create(); // Keep this note here.
        $first = Token::factory()->create();
        /** @var Token $second */
        $second = Token::factory()->create();
    }
}
-----
<?php

namespace Tests;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
}

namespace FixtureSetup;

class TokenTest extends \Tests\TestCase
{
    protected $buyer;

    protected $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buyer = User::factory()->create();

        // This token belongs to the buyer.
        $this->token = Token::factory()
            ->for($this->buyer, 'buyer')
            ->create([
                'name' => 'first',
            ]);

        $other = Token::factory()->make();
    }

    /** @test */
    public function comments_stay_with_fixtures(): void
    {
        $buyer = User::factory()->create(); // Keep this note here.
        $first = Token::factory()->create();

        /** @var Token $second */
        $second = Token::factory()->create();
    }
}
