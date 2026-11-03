<?php

declare(strict_types=1);

namespace Jcergolj\RectorForLaravel\Tests\CustomRules\ArrayLists;

use PHPUnit\Framework\Attributes\DataProvider;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;

class ArrayListsTest extends AbstractRectorTestCase
{
    #[DataProvider('provideData')]
    public function test(string $filePath): void
    {
        $this->doTestFile($filePath);
    }

    #[DataProvider('provideData')]
    public function test_output_is_unchanged_on_a_second_run(string $filePath): void
    {
        $parts = explode("\n-----\n", file_get_contents($filePath), 2);
        $fixture = tempnam('/tmp/opencode', 'wrap-arrays-').'.php.inc';
        try {
            file_put_contents($fixture, $parts[1] ?? $parts[0]);
            $this->doTestFile($fixture);
        } finally {
            unlink($fixture);
            unlink(substr($fixture, 0, -8));
        }
    }

    public static function provideData(): \Iterator
    {
        return self::yieldFilesFromDirectory(__DIR__.'/Fixture');
    }

    #[DataProvider('provideBoundaryLengths')]
    public function test_soft_target_counts_indentation_and_unicode(int $length, string $indent, string $character): void
    {
        $prefix = $indent."\$values = ['";
        $suffix = "', 'second'];";
        $padding = str_repeat($character, $length - mb_strlen(str_replace("\t", '    ', $prefix.$suffix)));
        $line = $prefix.$padding.$suffix;
        $input = "<?php\n\n".$line."\n";
        $expected = $input;
        if ($length > 120) {
            $base = str_replace("\t", '    ', $indent);
            $expected = "<?php\n\n".$indent."\$values = [\n".$base."    '".$padding."',\n".$base."    'second',\n".$base."];\n";
        }

        $temporaryFile = tempnam('/tmp/opencode', 'wrap-boundary-');
        $fixture = $temporaryFile.'.php.inc';
        try {
            file_put_contents($fixture, $input."-----\n".$expected);
            $this->doTestFile($fixture);
        } finally {
            unlink($fixture);
            unlink($temporaryFile);
        }
    }

    public static function provideBoundaryLengths(): \Iterator
    {
        foreach ([119, 120, 121] as $length) {
            yield $length.' spaces' => [$length, '    ', 'a'];
            yield $length.' tab and unicode' => [$length, "\t", 'é'];
        }
    }

    public function provideConfigFilePath(): string
    {
        return __DIR__.'/config/configured_rule.php';
    }
}
