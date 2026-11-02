<?php

$message = "An interpolated message for {$user->name} with escaped bytes \n \t and some deliberately long text".'https://example.com/a/long/path?foo=bar&baz=qux'.'/[a-z]+\.[0-9]+/';
-----
<?php

$message = "An interpolated message for {$user->name} with escaped bytes \n \t and some deliberately long text".
    'https://example.com/a/long/path?foo=bar&baz=qux'.
    '/[a-z]+\.[0-9]+/';
