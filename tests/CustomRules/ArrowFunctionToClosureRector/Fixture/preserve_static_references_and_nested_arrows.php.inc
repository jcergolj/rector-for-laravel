<?php

namespace Jcergolj\RectorForLaravel\Tests\CustomRules\ArrowFunctionToClosureRector\Fixture;

class Example
{
    public function run(array $items, string $prefix, string &$name)
    {
        $formatter = static fn & (string & $value, string ...$parts) => $items[0]->map(fn ($item) => $prefix.$item.$value.implode('-', $parts));

        return $formatter($name, 'a', 'b');
    }
}
-----
<?php

namespace Jcergolj\RectorForLaravel\Tests\CustomRules\ArrowFunctionToClosureRector\Fixture;

class Example
{
    public function run(array $items, string $prefix, string &$name)
    {
        $formatter = static function &(string & $value, string ...$parts) use ($items, $prefix) {
            return $items[0]->map(function ($item) use ($prefix, $value, $parts) {
                return $prefix.$item.$value.implode('-', $parts);
            });
        };

        return $formatter($name, 'a', 'b');
    }
}
