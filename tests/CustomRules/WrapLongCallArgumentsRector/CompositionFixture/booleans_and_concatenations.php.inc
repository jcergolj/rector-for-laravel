<?php

if ($request->routeIs('licence-agreement.create') || $request->routeIs('licence-agreement.store') || $request->routeIs('download-licence-agreement')) {
    $message = trans('validation.attributes.email').formatMessage($this->completedQuestionnaire->sectionQuestions->sectionOne->questionOne, $apiRequestParams['section_1_question_1']).trans('dashboard.tab');
}
