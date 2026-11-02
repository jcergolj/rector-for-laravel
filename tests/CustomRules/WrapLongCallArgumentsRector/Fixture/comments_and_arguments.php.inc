<?php

$value = renderReport(/* first */ $this->completedQuestionnaire->sectionQuestions->sectionOne->questionOne /* before comma */, /* second */ ...$apiRequestParams /* last */);
$value = Report::build(question: $this->completedQuestionnaire->sectionQuestions->sectionOne->questionOne, parameters: $apiRequestParams['section_1_question_1'],);
$value = renderReport(['reference' => &$reference, ...$extra], 'a long contextual argument that pushes this function call over the soft target');
-----
<?php

$value = renderReport(
    /* first */ $this->completedQuestionnaire->sectionQuestions->sectionOne->questionOne /* before comma */,
    /* second */ ...$apiRequestParams, /* last */
);
$value = Report::build(
    question: $this->completedQuestionnaire->sectionQuestions->sectionOne->questionOne,
    parameters: $apiRequestParams['section_1_question_1'],
);
$value = renderReport(
    ['reference' => &$reference, ...$extra],
    'a long contextual argument that pushes this function call over the soft target',
);
