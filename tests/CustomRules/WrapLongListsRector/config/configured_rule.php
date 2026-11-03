<?php

declare(strict_types=1);

use Jcergolj\RectorForLaravel\CustomRules\WrapLongListsRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()->withRules([WrapLongListsRector::class]);
