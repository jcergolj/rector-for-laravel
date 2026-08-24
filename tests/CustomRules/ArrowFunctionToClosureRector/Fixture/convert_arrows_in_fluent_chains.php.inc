<?php

namespace Jcergolj\RectorForLaravel\Tests\CustomRules\ArrowFunctionToClosureRector\Fixture;

class Example
{
    public function run(object $builder, bool $active, string $column)
    {
        return $builder
            ->when($active, fn ($query) => $query->where('active', true))
            ->tap(fn ($query) => $query->orderBy($column));
    }
}
-----
<?php

namespace Jcergolj\RectorForLaravel\Tests\CustomRules\ArrowFunctionToClosureRector\Fixture;

class Example
{
    public function run(object $builder, bool $active, string $column)
    {
        return $builder
            ->when($active, function ($query) {
                return $query->where('active', true);
            })
            ->tap(function ($query) use ($column) {
                return $query->orderBy($column);
            });
    }
}
