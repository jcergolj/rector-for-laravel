<?php

function outer(): void
{
    $callback = function (string $firstParameterWithADescriptiveName, string $secondParameterWithADescriptiveName, array $options = []): void {
        $nested = fn (string $firstParameterWithADescriptiveName, string $secondParameterWithADescriptiveName, array $options = []): string => $firstParameterWithADescriptiveName;
    };
}
-----
<?php

function outer(): void
{
    $callback = function (
        string $firstParameterWithADescriptiveName,
        string $secondParameterWithADescriptiveName,
        array $options = [],
    ): void {
        $nested = fn (
            string $firstParameterWithADescriptiveName,
            string $secondParameterWithADescriptiveName,
            array $options = [],
        ): string => $firstParameterWithADescriptiveName;
    };
}
