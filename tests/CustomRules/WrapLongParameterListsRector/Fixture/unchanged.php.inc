<?php

function shortList(string $first, string $second): void {}

function emptyList(): void {}

function singleParameter(string $oneIndivisibleParameterWithAnExtremelyLongNameThatMustNotBeRenamedOrSplitJustToForceTheLineUnderTheSoftTarget): void {}

function anExtremelyLongIndivisibleFunctionNameThatMustNotBeRenamedOrSplitJustToForceTheLineUnderTheSoftTargetAndIsAlreadyLong(): void {}

function multiline(
    string $firstParameterWithADescriptiveName, string $secondParameterWithADescriptiveName, string $thirdParameterWithADescriptiveName,
): void {}

function shortParameters(string $a, string $b): AnExtremelyLongIndivisibleReturnTypeThatIsOutsideTheScopeOfThisParameterWrappingRuleAndMustBeLeftAlone {}

$closure = function (string $a, string $b) use ($oneExtremelyLongIndivisibleCaptureThatIsOutsideTheScopeOfThisParameterWrappingRuleAndMustBeLeftAlone): void {};

$arrow = fn (string $a, string $b): string => 'This long body alone must not cause an otherwise short parameter list to expand unnecessarily.';

function multilineLiteral(string $literal = "first line
second line with a comma, and parentheses ()", string $otherParameterWithADescriptiveName = 'unchanged'): void {}

function heredoc(string $literal = <<<'TEXT'
Literal bytes, commas, parentheses () and URLs https://example.com/must/remain/unchanged/in/this/already/multiline/parameter/list
TEXT
, string $other = 'unchanged'): void {}
