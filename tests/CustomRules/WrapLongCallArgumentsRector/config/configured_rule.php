<?php

declare(strict_types=1);

use Jcergolj\RectorForLaravel\CustomRules\WrapLongCallArgumentsRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()->withRules([WrapLongCallArgumentsRector::class]);
