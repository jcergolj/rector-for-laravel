<?php

namespace Jcergolj\RectorForLaravel\Tests\CustomRules\RemoveFillableFromUnguardedModelsRector\Fixture;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $fillable = ['name', 'email'];

    public function fullName(): string
    {
        return $this->name;
    }
}
-----
<?php

namespace Jcergolj\RectorForLaravel\Tests\CustomRules\RemoveFillableFromUnguardedModelsRector\Fixture;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    public function fullName(): string
    {
        return $this->name;
    }
}
