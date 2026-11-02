<?php

$token = Token::factory()->for(User::factory()->create(['email' => 'joe.doe@example.com', 'first_name' => 'joe', 'last_name' => 'doe']), 'buyer')->withNotifications()->create();
-----
<?php

$token = Token::factory()
    ->for(
        User::factory()
            ->create([
                'email' => 'joe.doe@example.com',
                'first_name' => 'joe',
                'last_name' => 'doe',
            ]),
        'buyer',
    )
    ->withNotifications()
    ->create();
