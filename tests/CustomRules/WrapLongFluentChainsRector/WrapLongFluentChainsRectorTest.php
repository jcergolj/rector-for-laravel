<?php

declare(strict_types=1);

namespace Jcergolj\RectorForLaravel\Tests\CustomRules\WrapLongFluentChainsRector;

use PHPUnit\Framework\Attributes\DataProvider;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;

class WrapLongFluentChainsRectorTest extends AbstractRectorTestCase
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
        $temporaryFile = tempnam('/tmp/opencode', 'rector-chains-');
        $fixture = $temporaryFile.'.php.inc';

        try {
            file_put_contents($fixture, $parts[1] ?? $parts[0]);
            $this->doTestFile($fixture);
        } finally {
            unlink($fixture);
            unlink($temporaryFile);
        }
    }

    public static function provideData(): \Iterator
    {
        return self::yieldFilesFromDirectory(__DIR__.'/Fixture');
    }

    #[DataProvider('provideBoundaries')]
    public function test_line_length_boundaries(int $length, string $indent, string $character): void
    {
        $template = $indent."\$query->where('%s')->get();";
        $padding = $length - mb_strlen(str_replace("\t", '    ', sprintf($template, '')));
        $line = sprintf($template, str_repeat($character, $padding));
        $source = "<?php\n\n".$line."\n";
        $expected = $length > 120
            ? "<?php\n\n".str_replace('->get()', "\n".str_replace("\t", '    ', $indent).'    ->get()', $line)."\n"
            : $source;
        $temporaryFile = tempnam('/tmp/opencode', 'rector-boundary-');
        $fixture = $temporaryFile.'.php.inc';

        try {
            file_put_contents($fixture, $source."-----\n".$expected);
            $this->doTestFile($fixture);
        } finally {
            unlink($fixture);
            unlink($temporaryFile);
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
