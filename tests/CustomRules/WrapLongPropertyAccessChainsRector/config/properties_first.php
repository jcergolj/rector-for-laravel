<?php

declare(strict_types=1);

use Jcergolj\RectorForLaravel\CustomRules\WrapLongArraysRector;
use Jcergolj\RectorForLaravel\CustomRules\WrapLongFluentChainsRector;
use Jcergolj\RectorForLaravel\CustomRules\WrapLongPropertyAccessChainsRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()->withRules([
    WrapLongPropertyAccessChainsRector::class,
    WrapLongFluentChainsRector::class,
    WrapLongArraysRector::class,
]);
