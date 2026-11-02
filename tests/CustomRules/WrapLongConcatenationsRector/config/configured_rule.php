<?php

declare(strict_types=1);

use Jcergolj\RectorForLaravel\CustomRules\WrapLongConcatenationsRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()->withRules([WrapLongConcatenationsRector::class]);
