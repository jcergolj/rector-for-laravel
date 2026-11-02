<?php

function report(string $firstParameterWithAnExtremelyDescriptiveName = '', int $secondParameterWithADescriptiveName = __LINE__): int
{
    return $secondParameterWithADescriptiveName;
}

function anotherReport(string $firstParameterWithAnExtremelyDescriptiveName = '', string $secondParameterWithADescriptiveName = ''): void {}

echo __LINE__;
