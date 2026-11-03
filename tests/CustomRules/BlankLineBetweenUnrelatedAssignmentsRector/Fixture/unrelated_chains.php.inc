<?php

class QuestionnaireAnswers
{
    public function fill(): void
    {
        $this->secondQuestionAnswerWithADescriptiveName = $this->completedQuestionnaire->sectionQuestions->sectionOne->questionTwo;
        $this->thirdQuestionAnswerWithADescriptiveName = $this->completedQuestionnaire->sectionQuestions->sectionOne->questionThree;
        $this->fourthQuestionAnswerWithADescriptiveName = $this->completedQuestionnaire->sectionQuestions->sectionOne->questionFour;
    }
}
-----
<?php

class QuestionnaireAnswers
{
    public function fill(): void
    {
        $this->secondQuestionAnswerWithADescriptiveName = $this->completedQuestionnaire->sectionQuestions->sectionOne->questionTwo;

        $this->thirdQuestionAnswerWithADescriptiveName = $this->completedQuestionnaire->sectionQuestions->sectionOne->questionThree;

        $this->fourthQuestionAnswerWithADescriptiveName = $this->completedQuestionnaire->sectionQuestions->sectionOne->questionFour;
    }
}
