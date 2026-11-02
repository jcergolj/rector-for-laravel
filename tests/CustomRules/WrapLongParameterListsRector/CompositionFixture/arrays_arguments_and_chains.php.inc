<?php

function reports(string $firstReportWithADescriptiveName, array $metadata = ['first_name' => 'first', 'last_name' => 'last', 'email' => 'person@example.com']): void
{
    $builder->configure('a descriptive first argument for the report builder', 'another descriptive argument for the builder')->withMetadata(['first_name' => 'first', 'last_name' => 'last', 'email' => 'person@example.com'])->build();
    register('a descriptive callback registration name', function (string $firstParameterWithADescriptiveName, string $secondParameterWithADescriptiveName, array $options = []): void {}, ['enabled' => true, 'report' => 'team']);
}
-----
<?php

function reports(string $firstReportWithADescriptiveName, array $metadata = [
    'first_name' => 'first',
    'last_name' => 'last',
    'email' => 'person@example.com',
]): void
{
    $builder->configure(
        'a descriptive first argument for the report builder',
        'another descriptive argument for the builder',
    )
        ->withMetadata([
            'first_name' => 'first',
            'last_name' => 'last',
            'email' => 'person@example.com',
        ])
        ->build();
    register(
        'a descriptive callback registration name',
        function (
            string $firstParameterWithADescriptiveName,
            string $secondParameterWithADescriptiveName,
            array $options = [],
        ): void {},
        [
            'enabled' => true,
            'report' => 'team',
        ],
    );
}
