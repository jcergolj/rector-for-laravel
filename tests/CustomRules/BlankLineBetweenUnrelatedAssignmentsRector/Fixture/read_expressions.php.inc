<?php

function expressions(): void
{
    $user = new User;
    $message = "Hello {$user->name}";
    $invoice = new Invoice;
    $arguments = ['invoice' => $invoice];
    $result = send(...$arguments);
    $factory = Factory::class;
    $model = $factory::make();
    $className = $model::class;
    $first = 1;
    $combined = $first + $later;
    $later = 2;
    $first = 1;

    $relatedButAlreadySeparated = $first;
}
-----
<?php

function expressions(): void
{
    $user = new User;
    $message = "Hello {$user->name}";

    $invoice = new Invoice;
    $arguments = ['invoice' => $invoice];
    $result = send(...$arguments);

    $factory = Factory::class;
    $model = $factory::make();
    $className = $model::class;

    $first = 1;
    $combined = $first + $later;
    $later = 2;

    $first = 1;

    $relatedButAlreadySeparated = $first;
}
