<?php

namespace MiddlewareOrdering;

class ControllerTest extends \PHPUnit\Framework\TestCase
{
    protected $buyer;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_can_view_tokens(): void
    {
        $this->get('/tokens')->assertOk();
    }

    // Check the controller's middleware wiring.
    public function test_auth_is_applied(): void
    {
        $response = $this->get('/tokens');

        $response->assertMiddlewareIsApplied('auth');
    }
}
-----
<?php

namespace MiddlewareOrdering;

class ControllerTest extends \PHPUnit\Framework\TestCase
{
    protected $buyer;

    protected function setUp(): void
    {
        parent::setUp();
    }

    // Check the controller's middleware wiring.
    public function test_auth_is_applied(): void
    {
        $response = $this->get('/tokens');

        $response->assertMiddlewareIsApplied('auth');
    }
    public function test_can_view_tokens(): void
    {
        $this->get('/tokens')->assertOk();
    }
}
