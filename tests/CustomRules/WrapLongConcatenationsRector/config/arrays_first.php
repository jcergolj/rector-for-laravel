<?php

declare(strict_types=1);

use Jcergolj\RectorForLaravel\CustomRules\WrapLongListsRector;
use Jcergolj\RectorForLaravel\CustomRules\WrapLongConcatenationsRector;
use Jcergolj\RectorForLaravel\CustomRules\WrapLongChainsRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()->withRules([
    WrapLongListsRector::class,
    WrapLongChainsRector::class,
    WrapLongConcatenationsRector::class,
]);
