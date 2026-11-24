<?php

declare(strict_types=1);

namespace DtoExample\Data;

use DateTimeImmutable as Date;

readonly class Insight
{
    public function __construct(public string $title, public Date $createdAt) {}
}
