<?php

#[Example('declaration (attribute)')]
function &collectReports(#[Example('parameter (attribute)')] (First&Second)|Third &$report, ?User $user = null, string ...$additionalReports): array
{
    return $report;
}

$callback = static function &(First|Second &$reportWithADescriptiveName, ?User $userWithADescriptiveName = null, string ...$additionalReports) use (&$captured): array {
    return $captured;
};

$arrow = static fn &(First|Second &$reportWithADescriptiveName, ?User $userWithADescriptiveName = null, string ...$additionalReports): array => $reportWithADescriptiveName;
-----
<?php

#[Example('declaration (attribute)')]
function &collectReports(
    #[Example('parameter (attribute)')] (First&Second)|Third &$report,
    ?User $user = null,
    string ...$additionalReports,
): array
{
    return $report;
}

$callback = static function &(
    First|Second &$reportWithADescriptiveName,
    ?User $userWithADescriptiveName = null,
    string ...$additionalReports,
) use (&$captured): array {
    return $captured;
};

$arrow = static fn &(
    First|Second &$reportWithADescriptiveName,
    ?User $userWithADescriptiveName = null,
    string ...$additionalReports,
): array => $reportWithADescriptiveName;
