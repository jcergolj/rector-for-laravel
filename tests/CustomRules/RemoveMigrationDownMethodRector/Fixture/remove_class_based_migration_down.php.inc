<?php

namespace Jcergolj\RectorForLaravel\Tests\CustomRules\RemoveMigrationDownMethodRector\Fixture;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
}
-----
<?php

namespace Jcergolj\RectorForLaravel\Tests\CustomRules\RemoveMigrationDownMethodRector\Fixture;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
        });
    }
}
