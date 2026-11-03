<?php

function sendReport(string $firstReportWithADescriptiveName, string $secondReportWithADescriptiveName, array $metadata = []): void
{
    sendReport('a descriptive first argument for the report builder', 'another descriptive argument for the builder', ['first_name' => 'first', 'last_name' => 'last']);
}
-----
<?php

function sendReport(
    string $firstReportWithADescriptiveName,
    string $secondReportWithADescriptiveName,
    array $metadata = [],
): void
{
    sendReport(
        'a descriptive first argument for the report builder',
        'another descriptive argument for the builder',
        [
            'first_name' => 'first',
            'last_name' => 'last',
        ],
    );
}
