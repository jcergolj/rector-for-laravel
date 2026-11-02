<?php

declare(strict_types=1);

namespace Jcergolj\RectorForLaravel\Tests\CustomRules\WrapLongBooleanConditionsRector;

use PHPUnit\Framework\Attributes\DataProvider;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;

class WrapLongBooleanConditionsRectorTest extends AbstractRectorTestCase
{
    #[DataProvider('provideFixtures')]
    public function test_wraps_conditions(string $fixture): void
    {
        $this->doTestFile($fixture);
    }

    public static function provideFixtures(): \Iterator
    {
        return self::yieldFilesFromDirectory(__DIR__.'/Fixture');
    }

    #[DataProvider('provideFixtures')]
    public function test_output_is_unchanged_on_a_second_run(string $fixture): void
    {
        $parts = explode("\n-----\n", file_get_contents($fixture), 2);
        $this->assertFixture($parts[1] ?? $parts[0]);
    }

    #[DataProvider('provideBoundaries')]
    public function test_line_length_boundaries(int $length, string $indent, string $character, string $newline): void
    {
        $template = $indent."if (\$request->routeIs('%s') || \$ready) {";
        $padding = $length - mb_strlen(str_replace("\t", '    ', sprintf($template, '')));
        $operand = "\$request->routeIs('".str_repeat($character, $padding)."')";
        $source = '<?php'.$newline.$newline.sprintf($template, str_repeat($character, $padding)).$newline.$indent.'}'.$newline;
        $base = str_replace("\t", '    ', $indent);
        $expected = $length > 120
            ? '<?php'.$newline.$newline.$indent.'if ('.$newline.$base.'    '.$operand.' ||'.$newline.$base.'    $ready'.$newline.$base.') {'.$newline.$indent.'}'.$newline
            : $source;
        $this->assertFixture($source."\n-----\n".$expected);
    }

    public static function provideBoundaries(): \Iterator
    {
        foreach ([119, 120, 121] as $length) {
            foreach (['', '    ', "\t"] as $indent) {
                foreach (['x', 'é'] as $character) {
                    foreach (["\n", "\r\n"] as $newline) {
                        yield [$length, $indent, $character, $newline];
                    }
                }
            }
        }
    }

    private function assertFixture(string $source): void
    {
        $file = tempnam('/tmp/opencode', 'boolean-fixture-');
        $path = $file.'.php.inc';

        try {
            file_put_contents($path, $source);
            $this->doTestFile($path);
        } finally {
            unlink($path);
            unlink($file);
        }
    }

    public function provideConfigFilePath(): string
    {
        return __DIR__.'/config/configured_rule.php';
    }
}
