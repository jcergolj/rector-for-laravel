<?php

namespace PrefixRule;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

abstract class AppTestCase extends TestCase {}

class LanguageTest extends AppTestCase
{
    public function test_accepts_english(): void
    {
        self::assertTrue(true);
    }

    #[DataProvider('languages')]
    public function test_rejects_invalid_language(string $language): void
    {
        self::assertNotSame('en', $language);
    }

    public static function languages(): array
    {
        return [['invalid']];
    }
}
-----
<?php

namespace PrefixRule;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

abstract class AppTestCase extends TestCase {}

class LanguageTest extends AppTestCase
{
    #[Test]
    public function accepts_english(): void
    {
        self::assertTrue(true);
    }

    #[Test]
    #[DataProvider('languages')]
    public function rejects_invalid_language(string $language): void
    {
        self::assertNotSame('en', $language);
    }

    public static function languages(): array
    {
        return [['invalid']];
    }
}
