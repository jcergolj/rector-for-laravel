<?php

declare(strict_types=1);

use Jcergolj\RectorForLaravel\CustomRules\WrapLongParameterListsRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()->withRules([WrapLongParameterListsRector::class]);
