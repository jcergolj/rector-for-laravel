<?php

function callbacks(): void
{
    $user = new User;
    $callback = function () use ($user) { return $user->name; };
    $invoice = new Invoice;
    $arrow = fn () => $invoice->total;
    $name = 'outer';
    $shadowed = fn ($name) => $name;
    $user = new User;
    $local = function () { $user = 'local'; return $user; };
    $invoice = new Invoice;
    $object = new class($invoice) {
        public function __construct($invoice) {}
    };
    $name = 'outer';
    $object = new class {
        public function name($name) { return $name; }
    };
}
-----
<?php

function callbacks(): void
{
    $user = new User;
    $callback = function () use ($user) { return $user->name; };

    $invoice = new Invoice;
    $arrow = fn () => $invoice->total;

    $name = 'outer';

    $shadowed = fn ($name) => $name;

    $user = new User;

    $local = function () { $user = 'local'; return $user; };

    $invoice = new Invoice;
    $object = new class($invoice) {
        public function __construct($invoice) {}
    };

    $name = 'outer';

    $object = new class {
        public function name($name) { return $name; }
    };
}
