<?php

namespace FixtureSpacing;

use PHPUnit\Framework\Attributes\Test;

class TokenTest extends \PHPUnit\Framework\TestCase
{
    #[Test]
    public function buyer_has_tokens(): void
    {
        $buyer = User::factory()->create();
        $firstToken = Token::factory()->for($buyer, 'buyer')->create();
        $secondToken = Token::factory()->for($buyer, 'buyer')->create();
    }
}
-----
<?php

namespace FixtureSpacing;

use PHPUnit\Framework\Attributes\Test;

class TokenTest extends \PHPUnit\Framework\TestCase
{
    #[Test]
    public function buyer_has_tokens(): void
    {
        $buyer = User::factory()->create();

        $firstToken = Token::factory()->for($buyer, 'buyer')->create();

        $secondToken = Token::factory()->for($buyer, 'buyer')->create();
    }
}
