<?php

function buildReport($object): void
{
    $user = User::factory()->create();
    $name = $user->profile?->name;
    $invoice = Invoice::factory()->create();
    $total = $invoice->total;
    $enabled = true;
    $count = 0;
    $count = 1;
    $description = $later;
    $later = 'updated';
    $first = $object->value;
    $second = $object->otherValue;
}

class Report
{
    public function fill(): void
    {
        $this->user = User::factory()->create();
        $this->name = $this->user->name;
        $this->invoice = Invoice::factory()->create();
        $total = $this->invoice?->total;
        $this->enabled = true;
        $this->count = 0;
        $this->count = 1;
        $user = $this->user;
        $name = $user->name;
    }
}
-----
<?php

function buildReport($object): void
{
    $user = User::factory()->create();
    $name = $user->profile?->name;

    $invoice = Invoice::factory()->create();
    $total = $invoice->total;

    $enabled = true;

    $count = 0;
    $count = 1;

    $description = $later;
    $later = 'updated';

    $first = $object->value;

    $second = $object->otherValue;
}

class Report
{
    public function fill(): void
    {
        $this->user = User::factory()->create();
        $this->name = $this->user->name;

        $this->invoice = Invoice::factory()->create();
        $total = $this->invoice?->total;

        $this->enabled = true;

        $this->count = 0;
        $this->count = 1;

        $user = $this->user;
        $name = $user->name;
    }
}
