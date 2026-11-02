<?php

function buildPayload(&$reference, $more): array
{
    return ['buyer' => 'joe.doe@example.com', 'reference' => &$reference, /* Keep this note. */ 'enabled' => true, ...$more, 'url' => 'https://example.com/a?b=c'];
}
-----
<?php

function buildPayload(&$reference, $more): array
{
    return [
        'buyer' => 'joe.doe@example.com',
        'reference' => &$reference,
        /* Keep this note. */ 'enabled' => true,
        ...$more,
        'url' => 'https://example.com/a?b=c',
    ];
}
