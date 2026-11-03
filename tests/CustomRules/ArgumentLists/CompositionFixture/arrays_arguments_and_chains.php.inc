<?php

$message = Message::make(['subject' => trans('validation.attributes.email').trans('dashboard.on_the').trans('allocated-tokens.create.step_1'), 'active' => true], 'email')->forUser($this->completedQuestionnaire->sectionQuestions->sectionOne->questionOne, $apiRequestParams['section_1_question_1'])->send();
-----
<?php

$message = Message::make(
    [
        'subject' => trans('validation.attributes.email').
            trans('dashboard.on_the').
            trans('allocated-tokens.create.step_1'),
        'active' => true,
    ],
    'email',
)
    ->forUser(
        $this->completedQuestionnaire
            ->sectionQuestions
            ->sectionOne
            ->questionOne,
        $apiRequestParams['section_1_question_1'],
    )
    ->send();
