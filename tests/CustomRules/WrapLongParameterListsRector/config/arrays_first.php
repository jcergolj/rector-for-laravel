<?php

declare(strict_types=1);

use Jcergolj\RectorForLaravel\CustomRules\WrapLongArraysRector;
use Jcergolj\RectorForLaravel\CustomRules\WrapLongCallArgumentsRector;
use Jcergolj\RectorForLaravel\CustomRules\WrapLongFluentChainsRector;
use Jcergolj\RectorForLaravel\CustomRules\WrapLongParameterListsRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()->withRules([
    WrapLongArraysRector::class,
    WrapLongCallArgumentsRector::class,
    WrapLongFluentChainsRector::class,
    WrapLongParameterListsRector::class,
]);
