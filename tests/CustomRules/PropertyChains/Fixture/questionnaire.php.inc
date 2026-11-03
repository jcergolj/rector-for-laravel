<?php

$this->payload['section_1_question_1'] = $this->completedQuestionnaire->sectionQuestions->sectionOne->questionOne->answerValue;
-----
<?php

$this->payload['section_1_question_1'] = $this->completedQuestionnaire
    ->sectionQuestions
    ->sectionOne
    ->questionOne
    ->answerValue;
