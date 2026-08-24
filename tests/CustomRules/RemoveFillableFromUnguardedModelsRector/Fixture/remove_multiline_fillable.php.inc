<?php

namespace Jcergolj\RectorForLaravel\Tests\CustomRules\RemoveFillableFromUnguardedModelsRector\Fixture;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $fillable = [
        'name',
        'email',
        'password',
    ];
}
-----
<?php

namespace Jcergolj\RectorForLaravel\Tests\CustomRules\RemoveFillableFromUnguardedModelsRector\Fixture;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
}
