<?php

function sameLine(): void
{
    $first = 1; $second = 2;$third = 3;
    $user = new User; $name = $user->name;
}
-----
<?php

function sameLine(): void
{
    $first = 1;

    $second = 2;

    $third = 3;

    $user = new User; $name = $user->name;
}
