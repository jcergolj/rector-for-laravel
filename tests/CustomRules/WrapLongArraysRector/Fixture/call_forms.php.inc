<?php

sendPayload(...$options, metadata: ['email' => 'joe.doe@example.com', 'first_name' => 'joe', 'last_name' => 'doe'], recipient: 'buyer');
$payload = new Payload(['email' => 'joe.doe@example.com', 'first_name' => 'joe', 'last_name' => 'doe'], /* recipient */ 'buyer');
$service?->send(['email' => 'joe.doe@example.com', 'first_name' => 'joe', 'last_name' => 'doe'], 'buyer', 'another argument');
Payload::send(['email' => 'joe.doe@example.com', 'first_name' => 'joe', 'last_name' => 'doe'], 'buyer', 'another argument');
-----
<?php

sendPayload(
    ...$options,
    metadata: [
        'email' => 'joe.doe@example.com',
        'first_name' => 'joe',
        'last_name' => 'doe',
    ],
    recipient: 'buyer',
);
$payload = new Payload(
    [
        'email' => 'joe.doe@example.com',
        'first_name' => 'joe',
        'last_name' => 'doe',
    ],
    /* recipient */ 'buyer',
);
$service?->send(
    [
        'email' => 'joe.doe@example.com',
        'first_name' => 'joe',
        'last_name' => 'doe',
    ],
    'buyer',
    'another argument',
);
Payload::send(
    [
        'email' => 'joe.doe@example.com',
        'first_name' => 'joe',
        'last_name' => 'doe',
    ],
    'buyer',
    'another argument',
);
