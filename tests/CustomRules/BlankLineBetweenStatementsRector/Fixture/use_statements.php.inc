<?php

namespace App;

use DateTime;
use DateTimeImmutable;
use RuntimeException as Failure;
use function strlen;
use function trim;
use const PHP_EOL;
use const PHP_VERSION;
use Vendor\Package\{First, Second};
use Vendor\Other\{Third, Fourth};
first();
second();
-----
<?php

namespace App;

use DateTime;
use DateTimeImmutable;
use RuntimeException as Failure;
use function strlen;
use function trim;
use const PHP_EOL;
use const PHP_VERSION;
use Vendor\Package\{First, Second};
use Vendor\Other\{Third, Fourth};

first();

second();
