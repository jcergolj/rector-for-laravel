<?php

$value = $this->completedQuestionnaireWithADescriptiveName->sectionQuestionsWithADescriptiveName->first()?->questionOneWithADescriptiveName;
$value = $this->completedQuestionnaireWithADescriptiveName?->sectionQuestionsWithADescriptiveName->first()->formatWithADescriptiveName();
-----
<?php

$value = $this->completedQuestionnaireWithADescriptiveName
    ->sectionQuestionsWithADescriptiveName
    ->first()
    ?->questionOneWithADescriptiveName;
$value = $this->completedQuestionnaireWithADescriptiveName
    ?->sectionQuestionsWithADescriptiveName
    ->first()
    ->formatWithADescriptiveName();
