<?php

namespace Jcergolj\RectorForLaravel\Tests\CustomRules\ArrowFunctionToClosureRector\Fixture;

class Example
{
    public function run(array $users, string $prefix): array
    {
        return array_map(fn (string $name, string $suffix = '!'): string => $prefix.$name.$suffix, $users);
    }
}
-----
<?php

namespace Jcergolj\RectorForLaravel\Tests\CustomRules\ArrowFunctionToClosureRector\Fixture;

class Example
{
    public function run(array $users, string $prefix): array
    {
        return array_map(function (string $name, string $suffix = '!') use ($prefix): string {
            return $prefix.$name.$suffix;
        }, $users);
    }
}
