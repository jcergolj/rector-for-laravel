<?php

declare(strict_types=1);

use Jcergolj\RectorForLaravel\CustomRules\WrapLongArraysRector;
use Jcergolj\RectorForLaravel\CustomRules\WrapLongFluentChainsRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()->withRules([
    WrapLongArraysRector::class,
    WrapLongFluentChainsRector::class,
]);
