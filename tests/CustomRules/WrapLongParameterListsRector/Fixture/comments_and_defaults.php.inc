<?php

function reports(/* keep before type */ string $firstReportWithADescriptiveName, /* keep before reference */ array &$secondReportWithADescriptiveName = [], string $literal = 'comma, parentheses () and é\n' /* keep after default */,): void {}

function defaults(DateTimeImmutable $when = new DateTimeImmutable('2026-01-01', new DateTimeZone('UTC')), string $pattern = '/[(),]/', string $url = 'https://example.com/a/very/long/path'): void {}
-----
<?php

function reports(
    /* keep before type */ string $firstReportWithADescriptiveName,
    /* keep before reference */ array &$secondReportWithADescriptiveName = [],
    string $literal = 'comma, parentheses () and é\n' /* keep after default */,
): void {}

function defaults(
    DateTimeImmutable $when = new DateTimeImmutable('2026-01-01', new DateTimeZone('UTC')),
    string $pattern = '/[(),]/',
    string $url = 'https://example.com/a/very/long/path',
): void {}
