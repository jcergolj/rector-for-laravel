<?php

declare(strict_types=1);

use Jcergolj\RectorForLaravel\CustomRules\WrapLongListsRector;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->rule(WrapLongListsRector::class);
};
