<?php

declare(strict_types=1);

use Jcergolj\RectorForLaravel\CustomRules\MiddlewareTestsFirstRector;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->rule(MiddlewareTestsFirstRector::class);
};
