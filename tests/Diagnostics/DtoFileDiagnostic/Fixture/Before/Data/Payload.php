<?php

declare(strict_types=1);

namespace DtoExample\Data;

use DateTimeImmutable as Date;

readonly class Payload
{
    public function __construct(public Summary $summary, public Insight $insight, public Date $createdAt) {}
}

/** A summary for the payload. */
#[\AllowDynamicProperties]
class Summary
{
    public function __construct(public Date $date) {}
}

readonly class Insight
{
    public function __construct(public string $title, public Date $createdAt) {}
}
