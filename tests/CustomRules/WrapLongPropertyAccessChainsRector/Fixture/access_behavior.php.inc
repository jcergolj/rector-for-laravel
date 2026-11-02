<?php

$completedQuestionnaire->sectionQuestions->sectionOne->questionOne->answerValue = $completedQuestionnaire->sectionQuestions->sectionTwo->questionTwo->answerValue;
$reference =& $completedQuestionnaire->sectionQuestions->sectionOne->questionOne->answerValue; // Keep the reference and its comment.
$exists = isset($completedQuestionnaire->sectionQuestions->sectionOne->questionOne->answerValue, $completedQuestionnaire->sectionQuestions->sectionTwo->questionTwo->answerValue);
unset($completedQuestionnaire->sectionQuestions->sectionOne->questionOne->answerValue, $completedQuestionnaire->sectionQuestions->sectionTwo->questionTwo->answerValue);
-----
<?php

$completedQuestionnaire->sectionQuestions
    ->sectionOne
    ->questionOne
    ->answerValue = $completedQuestionnaire->sectionQuestions
        ->sectionTwo
        ->questionTwo
        ->answerValue;
$reference =& $completedQuestionnaire->sectionQuestions
    ->sectionOne
    ->questionOne
    ->answerValue; // Keep the reference and its comment.
$exists = isset($completedQuestionnaire->sectionQuestions
    ->sectionOne
    ->questionOne
    ->answerValue, $completedQuestionnaire->sectionQuestions
        ->sectionTwo
        ->questionTwo
        ->answerValue);
unset($completedQuestionnaire->sectionQuestions
    ->sectionOne
    ->questionOne
    ->answerValue, $completedQuestionnaire->sectionQuestions
        ->sectionTwo
        ->questionTwo
        ->answerValue);
