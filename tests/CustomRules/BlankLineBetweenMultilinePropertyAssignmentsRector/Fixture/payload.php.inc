<?php

class QuestionnairePayload
{
    public function fill(): void
    {
        $this->payload['section_1_question_2'] = $this->completedQuestionnaire
            ->sectionQuestions
            ->sectionOne
            ->questionTwo;
        $this->payload['section_1_question_3'] = $this->completedQuestionnaire
            ->sectionQuestions
            ->sectionOne
            ->questionThree;
        $this->payload['section_1_question_4'] = $this->completedQuestionnaire
            ->sectionQuestions
            ->sectionOne
            ->questionFour;
    }
}
-----
<?php

class QuestionnairePayload
{
    public function fill(): void
    {
        $this->payload['section_1_question_2'] = $this->completedQuestionnaire
            ->sectionQuestions
            ->sectionOne
            ->questionTwo;

        $this->payload['section_1_question_3'] = $this->completedQuestionnaire
            ->sectionQuestions
            ->sectionOne
            ->questionThree;

        $this->payload['section_1_question_4'] = $this->completedQuestionnaire
            ->sectionQuestions
            ->sectionOne
            ->questionFour;
    }
}
