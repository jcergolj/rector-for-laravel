<?php

namespace Tests;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
}

namespace MiddlewareMetadata;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class TokenControllerTest extends \Tests\TestCase
{
    protected $buyer;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    #[DataProvider('tokens')]
    public function can_view(string $token): void
    {
        $this->get($token)->assertOk();
    }

    /**
     * Wiring stays with this test.
     * @test
     */
    public function auth(): void
    {
        $this->get('/tokens')->assertMiddlewareIsApplied('auth');
    }

    public static function tokens(): array
    {
        return [['first']];
    }

    public function test_can_delete(): void
    {
        $this->delete('/tokens/1')->assertOk();
    }

    // Verify wiring even though the name does not mention middleware.
    #[Test]
    public function buyer_access(): void
    {
        $response = $this->get('/tokens');

        $response->assertMiddlewareIsApplied('buyer');
    }
}
-----
<?php

namespace Tests;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
}

namespace MiddlewareMetadata;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class TokenControllerTest extends \Tests\TestCase
{
    protected $buyer;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Wiring stays with this test.
     * @test
     */
    public function auth(): void
    {
        $this->get('/tokens')->assertMiddlewareIsApplied('auth');
    }
    // Verify wiring even though the name does not mention middleware.
    #[Test]
    public function buyer_access(): void
    {
        $response = $this->get('/tokens');

        $response->assertMiddlewareIsApplied('buyer');
    }

    public static function tokens(): array
    {
        return [['first']];
    }
    #[Test]
    #[DataProvider('tokens')]
    public function can_view(string $token): void
    {
        $this->get($token)->assertOk();
    }

    public function test_can_delete(): void
    {
        $this->delete('/tokens/1')->assertOk();
    }
}
