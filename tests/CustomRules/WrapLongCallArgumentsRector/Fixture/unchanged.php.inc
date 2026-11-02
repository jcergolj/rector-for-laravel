<?php

send('short', $value);
send();
send('a very long indivisible single argument which exceeds the soft target but has no sensible argument separator to wrap at');
$callable = $this->completedQuestionnaire->sectionQuestions->sectionOne->questionOne->aDeliberatelyLongFirstClassCallableMethodName(...);
$callable = SomeDeliberatelyLongClassNameUsedOnlyToMakeThisFirstClassCallableReferenceExceedTheConfiguredTarget::aLongMethodName(...);
$callable = someDeliberatelyLongFunctionNameUsedOnlyToMakeThisFirstClassCallableReferenceExceedTheConfiguredLineLengthTarget(...);
send(
    'a very long indivisible argument in an already multiline call which must not be split even if it exceeds the soft target',
    $value,
);
send('a long contextual argument that makes this call longer than the configured soft target', "first line
second line");
send(<<<'TEXT'
a long nowdoc argument whose string bytes and line breaks must remain completely unchanged
TEXT, $value);
$value = new class('a long contextual argument that makes this constructor longer than the configured soft target', $value) {};
