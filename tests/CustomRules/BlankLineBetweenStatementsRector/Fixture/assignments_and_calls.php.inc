<?php

function linkPerson($mapping, $moodleUserId): void
{
    $person = $mapping->fresh();
    $link = $person;
    $before = $link->only(['moodle_user_id', 'held', 'method']);
    $link->associateWith($moodleUserId, 'manual');
    EsrAudit::create(['action' => 'manual_link']);
    throw_unless($before, new RuntimeException);
    abort_if($before === [], 404);
}
-----
<?php

function linkPerson($mapping, $moodleUserId): void
{
    $person = $mapping->fresh();

    $link = $person;

    $before = $link->only(['moodle_user_id', 'held', 'method']);

    $link->associateWith($moodleUserId, 'manual');

    EsrAudit::create(['action' => 'manual_link']);

    throw_unless($before, new RuntimeException);

    abort_if($before === [], 404);
}
