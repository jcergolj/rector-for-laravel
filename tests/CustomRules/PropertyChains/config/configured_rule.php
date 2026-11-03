<?php

declare(strict_types=1);

use Jcergolj\RectorForLaravel\CustomRules\WrapLongChainsRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()->withRules([WrapLongChainsRector::class]);
