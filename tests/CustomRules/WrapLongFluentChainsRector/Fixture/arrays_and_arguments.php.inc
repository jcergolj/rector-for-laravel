<?php

sendNotification($builder->withRecipients(['first@example.com', 'second@example.com'])->withSubject('Confirm your customer account')->build(), $context);
$payload = [
    'notification' => $builder->withRecipients(['first@example.com', 'second@example.com'])->withSubject('Confirm your customer account')->build(),
];

Route::post(
    'password/reset',
    [ResetPasswordController::class, 'reset'],
)->middleware('throttle:5,10')->name('password.update');
-----
<?php

sendNotification($builder->withRecipients(['first@example.com', 'second@example.com'])
    ->withSubject('Confirm your customer account')
    ->build(), $context);
$payload = [
    'notification' => $builder->withRecipients(['first@example.com', 'second@example.com'])
        ->withSubject('Confirm your customer account')
        ->build(),
];

Route::post(
    'password/reset',
    [ResetPasswordController::class, 'reset'],
)->middleware('throttle:5,10')->name('password.update');
