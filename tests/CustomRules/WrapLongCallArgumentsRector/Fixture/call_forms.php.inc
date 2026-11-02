<?php

$value = renderReport($this->completedQuestionnaire->sectionQuestions->sectionOne->questionOne, $apiRequestParams['section_1_question_1']);
$value = Report::build($this->completedQuestionnaire->sectionQuestions->sectionOne->questionOne, $apiRequestParams['section_1_question_1']);
$value = $report?->build($this->completedQuestionnaire->sectionQuestions->sectionOne->questionOne, $apiRequestParams['section_1_question_1']);
$value = new Report($this->completedQuestionnaire->sectionQuestions->sectionOne->questionOne, $apiRequestParams['section_1_question_1']);
$value = $callback($this->completedQuestionnaire->sectionQuestions->sectionOne->questionOne, $apiRequestParams['section_1_question_1']);
$value = ($factory())($this->completedQuestionnaire->sectionQuestions->sectionOne->questionOne, $apiRequestParams['section_1_question_1']);
$value = new $class($this->completedQuestionnaire->sectionQuestions->sectionOne->questionOne, $apiRequestParams['section_1_question_1']);
$value = renderReport(($this->completedQuestionnaire->sectionQuestions->sectionOne->questionOne), ($apiRequestParams['section_1_question_1']));
-----
<?php

$value = renderReport(
    $this->completedQuestionnaire->sectionQuestions->sectionOne->questionOne,
    $apiRequestParams['section_1_question_1'],
);
$value = Report::build(
    $this->completedQuestionnaire->sectionQuestions->sectionOne->questionOne,
    $apiRequestParams['section_1_question_1'],
);
$value = $report?->build(
    $this->completedQuestionnaire->sectionQuestions->sectionOne->questionOne,
    $apiRequestParams['section_1_question_1'],
);
$value = new Report(
    $this->completedQuestionnaire->sectionQuestions->sectionOne->questionOne,
    $apiRequestParams['section_1_question_1'],
);
$value = $callback(
    $this->completedQuestionnaire->sectionQuestions->sectionOne->questionOne,
    $apiRequestParams['section_1_question_1'],
);
$value = ($factory())(
    $this->completedQuestionnaire->sectionQuestions->sectionOne->questionOne,
    $apiRequestParams['section_1_question_1'],
);
$value = new $class(
    $this->completedQuestionnaire->sectionQuestions->sectionOne->questionOne,
    $apiRequestParams['section_1_question_1'],
);
$value = renderReport(
    ($this->completedQuestionnaire->sectionQuestions->sectionOne->questionOne),
    ($apiRequestParams['section_1_question_1']),
);
