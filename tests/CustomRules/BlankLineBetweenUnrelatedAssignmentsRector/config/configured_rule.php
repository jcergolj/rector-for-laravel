<?php

declare(strict_types=1);

use Jcergolj\RectorForLaravel\CustomRules\BlankLineBetweenUnrelatedAssignmentsRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()->withRules([BlankLineBetweenUnrelatedAssignmentsRector::class]);
