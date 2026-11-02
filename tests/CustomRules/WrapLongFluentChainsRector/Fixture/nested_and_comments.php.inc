<?php

function notify($service, $recipient)
{
    // Keep the chain comment.
    return $service->withRecipient($recipient) /* recipient resolved */ ->withSubject('Your customer confirmation notification')->send(); // Keep the result comment.
}

$handler = fn ($service) => $service->withContext('customer-confirmation-notifications')->withSubject('Please confirm your account')->send();
-----
<?php

function notify($service, $recipient)
{
    // Keep the chain comment.
    return $service->withRecipient($recipient) /* recipient resolved */
        ->withSubject('Your customer confirmation notification')
        ->send(); // Keep the result comment.
}

$handler = fn ($service) => $service->withContext('customer-confirmation-notifications')
    ->withSubject('Please confirm your account')
    ->send();
