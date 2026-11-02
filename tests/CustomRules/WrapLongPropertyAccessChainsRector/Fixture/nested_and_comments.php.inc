<?php

function buildPayload($questionnaire): array
{
    // Keep the comment with the original assignment.
    $value = $questionnaire->completedQuestionnaire /* retain receiver comment */ ->sectionQuestions->sectionOne->questionOne->answerValue; // retain trailing comment
    $nullable = $questionnaire?->completedQuestionnaire?->sectionQuestions->sectionOne?->questionOne->answerValue ?? 'fallback';
    return ['answer' => consume($questionnaire->completedQuestionnaire->sectionQuestions->sectionOne->questionOne->answerValue)];
}
-----
<?php

function buildPayload($questionnaire): array
{
    // Keep the comment with the original assignment.
    $value = $questionnaire->completedQuestionnaire /* retain receiver comment */
        ->sectionQuestions
        ->sectionOne
        ->questionOne
        ->answerValue; // retain trailing comment
    $nullable = $questionnaire?->completedQuestionnaire
        ?->sectionQuestions
        ->sectionOne
        ?->questionOne
        ->answerValue ?? 'fallback';
    return ['answer' => consume($questionnaire->completedQuestionnaire
        ->sectionQuestions
        ->sectionOne
        ->questionOne
        ->answerValue)];
}
