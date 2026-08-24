<?php

namespace Jcergolj\RectorForLaravel\Tests\RectorLaravel\ModelCastsPropertyToCastsMethodRector\Fixture;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $casts = [
        'age' => 'integer',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'string',
        ];
    }
}
