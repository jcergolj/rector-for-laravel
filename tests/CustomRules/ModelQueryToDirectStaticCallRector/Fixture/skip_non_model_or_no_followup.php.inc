<?php

namespace Jcergolj\RectorForLaravel\Tests\CustomRules\ModelQueryToDirectStaticCallRector\Fixture;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model {}

class SearchIndex
{
    public static function query(): self
    {
        return new self;
    }

    public function where(string $column, mixed $value): self
    {
        return $this;
    }
}

class Example
{
    public function run(): void
    {
        Invoice::query();
        SearchIndex::query()->where('status', 'active');
    }
}
