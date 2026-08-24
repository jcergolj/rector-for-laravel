<?php

namespace Jcergolj\RectorForLaravel\Tests\CustomRules\ModelQueryToDirectStaticCallRector\Fixture;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model {}

class Example
{
    public function run(): void
    {
        Invoice::query()->where('status', 'paid')->latest()->first();
        Invoice::query()->with('customer')->orderBy('created_at', 'desc')->get();
    }
}
-----
<?php

namespace Jcergolj\RectorForLaravel\Tests\CustomRules\ModelQueryToDirectStaticCallRector\Fixture;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model {}

class Example
{
    public function run(): void
    {
        Invoice::where('status', 'paid')->latest()->first();
        Invoice::with('customer')->orderBy('created_at', 'desc')->get();
    }
}
