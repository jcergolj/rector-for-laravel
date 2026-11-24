<?php

namespace Illuminate\Foundation\Testing {
    abstract class TestCase extends \PHPUnit\Framework\TestCase
    {
        protected function assertDatabaseHas($table, array $data = [], $connection = null) {}
        protected function getTable($table) {}
    }
}

namespace App\Tests {
    use Illuminate\Foundation\Testing\TestCase;

    final class UnsupportedTest extends TestCase
    {
        public function testPersistence(): void
        {
            $this->assertDatabaseHas('users', ['name' => 'Ada']);
            $this->assertDatabaseHas('users', ['id' => 42, 'name' => 'Ada'], 'archive');
            $this->assertDatabaseHas('team_user', ['id' => 42, 'name' => 'Ada']);
            $this->assertDatabaseHas('users', ['id' => 42, 'name' => $this->expectedName()]);
            $this->assertDatabaseHas('users', ['id' => 42, 'active' => true]);
            $this->assertDatabaseHas('users', ['id' => '42']);
            $this->assertDatabaseHas('users', ['id' => 41, 'id' => 42]);
            $this->assertDatabaseHas(table: 'users', data: ['id' => 42]);
            $this->assertDatabaseHas('users', ['id' => 42])->assertDatabaseCount('users', 1);
            $this->assertDatabaseMissing('users', ['id' => 42]);
            $this->assertDatabaseCount('users', 1);
        }
    }

    final class UnrelatedTest extends \PHPUnit\Framework\TestCase
    {
        public function testPersistence(): void
        {
            $this->assertDatabaseHas('users', ['id' => 42]);
        }
    }

    final class CustomAssertionTest extends TestCase
    {
        protected function assertDatabaseHas($table, array $data = [], $connection = null) {}

        public function testPersistence(): void
        {
            $this->assertDatabaseHas('users', ['id' => 42]);
        }
    }
}
