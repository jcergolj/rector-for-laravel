<?php

$result = Builder::make()->withPayload(['answer' => $this->completedQuestionnaire->sectionQuestions->sectionOne->questionOne->answerValue, 'label' => 'Answer'], 'section_1')->build();
-----
<?php

$result = Builder::make()
    ->withPayload(
        [
            'answer' => $this->completedQuestionnaire
                ->sectionQuestions
                ->sectionOne
                ->questionOne
                ->answerValue,
            'label' => 'Answer',
        ],
        'section_1',
    )
    ->build();
