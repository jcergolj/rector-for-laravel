<?php

declare(strict_types=1);

use Jcergolj\RectorForLaravel\CustomRules\DatabaseHasToFieldAssertionsRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()->withConfiguredRule(DatabaseHasToFieldAssertionsRector::class, [
    'users' => [
        'model' => 'App\Models\User',
        'uniqueKey' => 'id',
        'columns' => ['id' => 'int', 'name' => 'string'],
    ],
]);
