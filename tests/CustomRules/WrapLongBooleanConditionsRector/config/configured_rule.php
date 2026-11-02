<?php

declare(strict_types=1);

use Jcergolj\RectorForLaravel\CustomRules\WrapLongBooleanConditionsRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()->withRules([WrapLongBooleanConditionsRector::class]);
