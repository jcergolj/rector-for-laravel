<?php

function payloadRows($value): array
{
    return [
        ['email' => ('joe.doe@example.com'), 'first_name' => 'joe', 'last_name' => 'doe', 'value' => ($value /* exact value */)],
        ['email' => 'jane.doe@example.com', 'first_name' => 'jane', 'last_name' => 'doe', 'value' => "literal\\nnot a newline"],
    ];
}
-----
<?php

function payloadRows($value): array
{
    return [
        [
            'email' => ('joe.doe@example.com'),
            'first_name' => 'joe',
            'last_name' => 'doe',
            'value' => ($value /* exact value */),
        ],
        [
            'email' => 'jane.doe@example.com',
            'first_name' => 'jane',
            'last_name' => 'doe',
            'value' => "literal\\nnot a newline",
        ],
    ];
}
