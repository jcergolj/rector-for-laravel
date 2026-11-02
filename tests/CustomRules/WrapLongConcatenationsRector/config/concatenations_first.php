<?php

declare(strict_types=1);

use Jcergolj\RectorForLaravel\CustomRules\WrapLongArraysRector;
use Jcergolj\RectorForLaravel\CustomRules\WrapLongConcatenationsRector;
use Jcergolj\RectorForLaravel\CustomRules\WrapLongFluentChainsRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()->withRules([
    WrapLongConcatenationsRector::class,
    WrapLongFluentChainsRector::class,
    WrapLongArraysRector::class,
]);
