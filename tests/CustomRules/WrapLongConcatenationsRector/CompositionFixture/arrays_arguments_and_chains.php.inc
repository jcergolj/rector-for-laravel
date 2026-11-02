<?php

$message = Message::make(['subject' => trans('validation.attributes.email').trans('dashboard.on_the').trans('allocated-tokens.create.step_1'), 'active' => true], 'email')->forUser($user)->send();
-----
<?php

$message = Message::make(
    [
        'subject' => trans('validation.attributes.email')
            .trans('dashboard.on_the')
            .trans('allocated-tokens.create.step_1'),
        'active' => true,
    ],
    'email',
)
    ->forUser($user)
    ->send();
