<?php

declare(strict_types=1);

use Jcergolj\RectorForLaravel\CustomRules\BlankLineBetweenStatementsRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()->withRules([BlankLineBetweenStatementsRector::class]);
