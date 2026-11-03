<?php

declare(strict_types=1);

namespace Jcergolj\RectorForLaravel\Tests\CustomRules\ArgumentLists;

use PHPUnit\Framework\Attributes\DataProvider;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;

class ArgumentListsTest extends AbstractRectorTestCase
{
    #[DataProvider('provideData')]
    public function test(string $filePath): void
    {
        $this->doTestFile($filePath);
    }

    public static function provideData(): \Iterator
    {
        return self::yieldFilesFromDirectory(__DIR__.'/Fixture');
    }

    #[DataProvider('provideData')]
    public function test_output_is_unchanged_on_a_second_run(string $filePath): void
    {
        $parts = explode("\n-----\n", file_get_contents($filePath), 2);
        $file = tempnam('/tmp/opencode', 'arguments-repeat-');
        $fixture = $file.'.php.inc';

        try {
            file_put_contents($fixture, $parts[1] ?? $parts[0]);
            $this->doTestFile($fixture);
        } finally {
            unlink($fixture);
            unlink($file);
        }
    }

    #[DataProvider('provideBoundaries')]
    public function test_line_length_boundaries(int $length, string $indent, string $character): void
    {
        $padding = $length - mb_strlen(str_replace("\t", '    ', $indent."send('', \$value);"));
        $literal = "'".str_repeat($character, $padding)."'";
        $source = "<?php\n\n".$indent.'send('.$literal.", \$value);\n";
        $base = str_replace("\t", '    ', $indent);
        $expected = $length > 120
            ? "<?php\n\n".$indent."send(\n".$base.'    '.$literal.",\n".$base."    \$value,\n".$base.");\n"
            : $source;
        $file = tempnam('/tmp/opencode', 'arguments-boundary-');
        $fixture = $file.'.php.inc';

        try {
            file_put_contents($fixture, $source."-----\n".$expected);
            $this->doTestFile($fixture);
        } finally {
            unlink($fixture);
            unlink($file);
        }
    }

    public static function provideBoundaries(): \Iterator
    {
        foreach ([119, 120, 121] as $length) {
            foreach (['', '    ', "\t"] as $indent) {
                foreach (['x', 'é'] as $character) {
                    yield [$length, $indent, $character];
                }
            }
        }
    }

    public function provideConfigFilePath(): string
    {
        return __DIR__.'/config/configured_rule.php';
    }
}
