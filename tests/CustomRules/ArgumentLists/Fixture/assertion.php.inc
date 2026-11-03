<?php

$this->assertSame($this->completedQuestionnaire->sectionQuestions->sectionOne->questionOne, $apiRequestParams['section_1_question_1']);
-----
<?php

$this->assertSame(
    $this->completedQuestionnaire->sectionQuestions->sectionOne->questionOne,
    $apiRequestParams['section_1_question_1'],
);
