<?php

$value = $this->short->chain;
$value = $this->completedQuestionnaire
    ->sectionQuestions->sectionOne->questionOne->answerValue;
$value = $object->anExtremelyLongIndivisiblePropertyNameWhichCannotBeWrappedWithoutRenamingThePropertyOrIntroducingATemporaryVariable;
$value = $this->completedQuestionnaire->{$propertyNameWithSideEffects()}->sectionQuestions->sectionOne->questionOne->answerValue;
$value = $this->completedQuestionnaire->sectionQuestions->{$propertyNameWithSideEffects()}->sectionOne->questionOne->answerValue;
$value = 'An indivisible literal stays unchanged even when its contents contain ->property->access->syntax and it exceeds the soft target.';
$value = $query->where('some exceptionally long indivisible argument string that exceeds the soft target on its own without a useful property access chain')->get();
-----
<?php

$value = $this->short->chain;
$value = $this->completedQuestionnaire
    ->sectionQuestions->sectionOne->questionOne->answerValue;
$value = $object->anExtremelyLongIndivisiblePropertyNameWhichCannotBeWrappedWithoutRenamingThePropertyOrIntroducingATemporaryVariable;
$value = $this->completedQuestionnaire->{$propertyNameWithSideEffects()}->sectionQuestions->sectionOne->questionOne->answerValue;
$value = $this->completedQuestionnaire->sectionQuestions->{$propertyNameWithSideEffects()}->sectionOne->questionOne->answerValue;
$value = 'An indivisible literal stays unchanged even when its contents contain ->property->access->syntax and it exceeds the soft target.';
$value = $query->where('some exceptionally long indivisible argument string that exceeds the soft target on its own without a useful property access chain')
    ->get();
