<?php

$short = prefix().suffix();
$alreadyWrapped = prefix('a sufficiently long prefix argument to make this expression exceed the soft line target')
    .middle().suffix();
$indivisible = 'a very long literal that has no concatenation operator and must not be split even though it exceeds the configured soft line target';
$multiline = prefix('a sufficiently long prefix argument to make this expression exceed the soft line target')."first line
second line".suffix();
$heredoc = <<<'TEXT'
a very long literal that has no concatenation operator and must not be split even though it exceeds the configured soft line target
TEXT;
$withHeredoc = prefix().<<<TEXT
keep this string's bytes and its line breaks intact
TEXT;
