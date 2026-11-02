<?php

declare(strict_types=1);

use Jcergolj\RectorForLaravel\CustomRules\WrapLongArraysRector;
use Jcergolj\RectorForLaravel\CustomRules\WrapLongBooleanConditionsRector;
use Jcergolj\RectorForLaravel\CustomRules\WrapLongCallArgumentsRector;
use Jcergolj\RectorForLaravel\CustomRules\WrapLongConcatenationsRector;
use Jcergolj\RectorForLaravel\CustomRules\WrapLongFluentChainsRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()->withRules([
    WrapLongCallArgumentsRector::class,
    WrapLongConcatenationsRector::class,
    WrapLongBooleanConditionsRector::class,
    WrapLongFluentChainsRector::class,
    WrapLongArraysRector::class,
]);
