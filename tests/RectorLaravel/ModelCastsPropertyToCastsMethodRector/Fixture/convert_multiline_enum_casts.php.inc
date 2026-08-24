<?php

namespace Jcergolj\RectorForLaravel\Tests\RectorLaravel\ModelCastsPropertyToCastsMethodRector\Fixture;

use Illuminate\Database\Eloquent\Model;

enum UserStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}

class User extends Model
{
    protected $casts = [
        'status' => UserStatus::class,
        'settings' => 'array',
    ];
}
-----
<?php

namespace Jcergolj\RectorForLaravel\Tests\RectorLaravel\ModelCastsPropertyToCastsMethodRector\Fixture;

use Illuminate\Database\Eloquent\Model;

enum UserStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}

class User extends Model
{
    protected function casts(): array
    {
        return [
            'status' => UserStatus::class,
            'settings' => 'array',
        ];
    }
}
