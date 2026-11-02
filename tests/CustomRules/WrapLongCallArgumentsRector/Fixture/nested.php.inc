<?php

function render($request, $parameters)
{
    return outer(inner($request->completedQuestionnaire->sectionQuestions->sectionOne->questionOne, $parameters['section_1_question_1']), short('a', 'b'));
}

$value = $client
    ->forTeam($team)
    ->send($this->completedQuestionnaire->sectionQuestions->sectionOne->questionOne, $apiRequestParams['section_1_question_1']);
-----
<?php

function render($request, $parameters)
{
    return outer(
        inner(
            $request->completedQuestionnaire->sectionQuestions->sectionOne->questionOne,
            $parameters['section_1_question_1'],
        ),
        short('a', 'b'),
    );
}

$value = $client
    ->forTeam($team)
    ->send(
        $this->completedQuestionnaire->sectionQuestions->sectionOne->questionOne,
        $apiRequestParams['section_1_question_1'],
    );
