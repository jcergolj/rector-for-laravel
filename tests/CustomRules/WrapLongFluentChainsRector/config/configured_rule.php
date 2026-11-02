<?php

declare(strict_types=1);

use Jcergolj\RectorForLaravel\CustomRules\WrapLongFluentChainsRector;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->rule(WrapLongFluentChainsRector::class);
};
