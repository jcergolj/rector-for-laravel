<?php

Route::get('/')->name('home');
$query->where('active', true)->get();
$query->where('a single indivisible argument that is deliberately far longer than the soft target and cannot sensibly be split into separate values');
$profile = $this->customerWithAnExceptionallyLongPropertyName->completedQuestionnaire->sectionQuestions->sectionOne->questionOne;
$callable = $query->where('a first class callable reference is not a fluent invocation and must not be rewritten by this wrapping rule')->get(...);
$callable = Builder::withContext('a first class callable chain with several receiver calls must also keep its existing layout')->where('active', true)->get(...);
Builder::withContext('an existing multiline chain with several inline receiver calls must keep its existing layout')->where('active', true)->limit(100)
    ->get();
$query->where('already formatted')
    ->withSubject('an existing multiline chain is preserved even if one of its individual continuation lines remains longer than the soft target')
    ->get();
-----
<?php

Route::get('/')->name('home');
$query->where('active', true)->get();
$query->where('a single indivisible argument that is deliberately far longer than the soft target and cannot sensibly be split into separate values');
$profile = $this->customerWithAnExceptionallyLongPropertyName
    ->completedQuestionnaire
    ->sectionQuestions
    ->sectionOne
    ->questionOne;
$callable = $query->where('a first class callable reference is not a fluent invocation and must not be rewritten by this wrapping rule')->get(...);
$callable = Builder::withContext('a first class callable chain with several receiver calls must also keep its existing layout')->where('active', true)->get(...);
Builder::withContext('an existing multiline chain with several inline receiver calls must keep its existing layout')->where('active', true)->limit(100)
    ->get();
$query->where('already formatted')
    ->withSubject('an existing multiline chain is preserved even if one of its individual continuation lines remains longer than the soft target')
    ->get();
