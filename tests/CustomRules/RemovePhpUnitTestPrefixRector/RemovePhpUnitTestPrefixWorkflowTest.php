<?php

declare(strict_types=1);

namespace Jcergolj\RectorForLaravel\Tests\CustomRules\RemovePhpUnitTestPrefixRector;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RemovePhpUnitTestPrefixWorkflowTest extends TestCase
{
    #[Test]
    public function a_second_run_leaves_the_migrated_test_unchanged(): void
    {
        [$input, $expected] = explode("\n-----\n", file_get_contents(__DIR__.'/Fixture/replace_prefix.php.inc'), 2);
        $temporaryFile = tempnam('/tmp/opencode', 'phpunit-test-prefix-');
        $path = $temporaryFile.'.php';
        $root = dirname(__DIR__, 3);

        try {
            file_put_contents($path, rtrim($input)."\n");

            $command = implode(' ', array_map('escapeshellarg', [
                PHP_BINARY, $root.'/vendor/bin/rector', 'process', $path,
                '--config', __DIR__.'/config/configured_rule.php', '--clear-cache',
            ])).' 2>&1';

            for ($run = 1; $run <= 2; $run++) {
                $output = [];
                exec($command, $output, $status);
                $this->assertSame(0, $status, implode("\n", $output));

                $this->assertSame($expected, file_get_contents($path), "Unexpected output after run $run");
            }
        } finally {
            unlink($path);
            unlink($temporaryFile);
        }
    }
}
