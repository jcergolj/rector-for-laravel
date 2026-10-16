<?php

declare(strict_types=1);

use Jcergolj\RectorForLaravel\CustomRules\AddBlankLineAfterPhpUnitAssertionRector;
use Jcergolj\RectorForLaravel\CustomRules\BlankLineBetweenTestFixturesRector;
use Jcergolj\RectorForLaravel\CustomRules\MiddlewareTestsFirstRector;
use Rector\CodingStyle\Rector\ClassMethod\NewlineBeforeNewAssignSetRector;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->rules([
        BlankLineBetweenTestFixturesRector::class,
        MiddlewareTestsFirstRector::class,
        AddBlankLineAfterPhpUnitAssertionRector::class,
        NewlineBeforeNewAssignSetRector::class,
    ]);
};
