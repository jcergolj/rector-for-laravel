<?php

namespace Jcergolj\RectorForLaravel\Tests\RectorLaravel\ModelCastsPropertyToCastsMethodRector\Fixture;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $casts = [
        'age' => 'integer',
        'is_admin' => 'boolean',
    ];
}
-----
<?php

namespace Jcergolj\RectorForLaravel\Tests\RectorLaravel\ModelCastsPropertyToCastsMethodRector\Fixture;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected function casts(): array
    {
        return [
            'age' => 'integer',
            'is_admin' => 'boolean',
        ];
    }
}
