<?php

declare(strict_types=1);

use Jcergolj\RectorForLaravel\CustomRules\WrapLongListsRector;
use Jcergolj\RectorForLaravel\CustomRules\WrapLongBooleanConditionsRector;
use Jcergolj\RectorForLaravel\CustomRules\WrapLongChainsRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()->withRules([
    WrapLongListsRector::class,
    WrapLongBooleanConditionsRector::class,
    WrapLongChainsRector::class,
]);
