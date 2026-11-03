<?php

Route::post('password/reset', [ResetPasswordController::class, 'reset'])->middleware('throttle:5,10')->name('password.update');
-----
<?php

Route::post('password/reset', [ResetPasswordController::class, 'reset'])
    ->middleware('throttle:5,10')
    ->name('password.update');
