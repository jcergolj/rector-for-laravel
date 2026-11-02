<?php

$rules = ['buyer' => ['required', 'email', 'max:255', 'unique:users,email'], 'address' => ['required', 'string', 'max:255'], 'active' => true];
-----
<?php

$rules = [
    'buyer' => [
        'required',
        'email',
        'max:255',
        'unique:users,email',
    ],
    'address' => [
        'required',
        'string',
        'max:255',
    ],
    'active' => true,
];
