<?php

declare(strict_types=1);

namespace DtoExample\Data;

use DateTimeImmutable as Date;

/** A summary for the payload. */
#[\AllowDynamicProperties]
class Summary
{
    public function __construct(public Date $date) {}
}
