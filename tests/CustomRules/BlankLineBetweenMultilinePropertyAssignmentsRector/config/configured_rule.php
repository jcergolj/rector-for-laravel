<?php

declare(strict_types=1);

use Jcergolj\RectorForLaravel\CustomRules\BlankLineBetweenMultilinePropertyAssignmentsRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withRules([BlankLineBetweenMultilinePropertyAssignmentsRector::class]);
