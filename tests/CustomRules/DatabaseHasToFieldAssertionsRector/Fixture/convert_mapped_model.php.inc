<?php

namespace Illuminate\Foundation\Testing {
    trait DatabaseAssertions
    {
        protected function assertDatabaseHas($table, array $data = [], $connection = null) {}
        protected function getTable($table) {}
    }

    abstract class TestCase extends \PHPUnit\Framework\TestCase
    {
        use DatabaseAssertions;
    }
}

namespace App\Tests {
    use App\Models\User as Account;
    use Illuminate\Foundation\Testing\TestCase;

    final class ExampleTest extends TestCase
    {
        public function testPersistence(): void
        {
            $this->assertDatabaseHas(Account::class, ['id' => 42, 'name' => 'Ada']);
            $this->assertDatabaseHas('users', ['id' => 43]);
        }
    }
}
-----
<?php

namespace Illuminate\Foundation\Testing {
    trait DatabaseAssertions
    {
        protected function assertDatabaseHas($table, array $data = [], $connection = null) {}
        protected function getTable($table) {}
    }

    abstract class TestCase extends \PHPUnit\Framework\TestCase
    {
        use DatabaseAssertions;
    }
}

namespace App\Tests {
    use App\Models\User as Account;
    use Illuminate\Foundation\Testing\TestCase;

    final class ExampleTest extends TestCase
    {
        public function testPersistence(): void
        {
            $persistedRow = $this->getConnection(null, Account::class)->table('users')->where('id', 42)->first();

            $this->assertNotNull($persistedRow, 'Expected a row in users for id.');

            $this->assertSame(42, $persistedRow->id, 'id');

            $this->assertSame('Ada', $persistedRow->name, 'name');
            $persistedRow2 = $this->getConnection(null, 'users')->table('users')->where('id', 43)->first();

            $this->assertNotNull($persistedRow2, 'Expected a row in users for id.');

            $this->assertSame(43, $persistedRow2->id, 'id');
        }
    }
}
