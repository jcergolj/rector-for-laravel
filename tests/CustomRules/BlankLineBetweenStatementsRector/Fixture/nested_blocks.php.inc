<?php

class StatementScopes
{
    public int $first = 1;
    public int $second = 2;
    public function run($fields): void
    {
        $this->number($fields[16], 15, true);
        $this->number($fields[21], 15);
        foreach ([9, 10, 26] as $index) {
            $this->date($fields[$index], 'Ymd');
            $this->date($fields[25], 'Ymd His', true);
        }
        $callback = function () use ($fields) {
            $value = $fields[0];
            if ($value) {
                first();
                second();
            } elseif ($fields) {
                third();
                fourth();
            } else {
                fifth();
                sixth();
            }
            return match ($value) {
                1 => first(),
                default => second(),
            };
        };
        try {
            first();
            second();
        } catch (Throwable $exception) {
            report($exception);
            third();
        } finally {
            fourth();
            fifth();
        }
        for ($i = 0, $j = 1; $i < 2; $i++, $j++) {
            first();
            second();
        }
        switch ($fields[0]) {
            case 1:
                first();
                break;
            default:
                second();
                break;
        }
        do {
            first();
            second();
        } while (false);
        while (false) {
            third();
            fourth();
        }
        $callback();
    }
    public function other(): void {}
}
$object = new StatementScopes;
$object->run([]);
-----
<?php

class StatementScopes
{
    public int $first = 1;
    public int $second = 2;
    public function run($fields): void
    {
        $this->number($fields[16], 15, true);

        $this->number($fields[21], 15);

        foreach ([9, 10, 26] as $index) {
            $this->date($fields[$index], 'Ymd');

            $this->date($fields[25], 'Ymd His', true);
        }

        $callback = function () use ($fields) {
            $value = $fields[0];

            if ($value) {
                first();

                second();
            } elseif ($fields) {
                third();

                fourth();
            } else {
                fifth();

                sixth();
            }

            return match ($value) {
                1 => first(),
                default => second(),
            };
        };

        try {
            first();

            second();
        } catch (Throwable $exception) {
            report($exception);

            third();
        } finally {
            fourth();

            fifth();
        }

        for ($i = 0, $j = 1; $i < 2; $i++, $j++) {
            first();

            second();
        }

        switch ($fields[0]) {
            case 1:
                first();

                break;
            default:
                second();

                break;
        }

        do {
            first();

            second();
        } while (false);

        while (false) {
            third();

            fourth();
        }

        $callback();
    }
    public function other(): void {}
}

$object = new StatementScopes;

$object->run([]);
