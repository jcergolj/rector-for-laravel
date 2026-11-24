<?php

declare(strict_types=1);

namespace Jcergolj\RectorForLaravel\Tests\Diagnostics\ControllerRouteDiagnostic;

use PHPUnit\Framework\TestCase;

final class ControllerRouteDiagnosticTest extends TestCase
{
    public function test_it_reports_controller_actions_and_route_mismatches(): void
    {
        [$output, $status] = $this->check('Invalid');

        self::assertSame(1, $status);
        self::assertCount(4, $output);
        self::assertMatchesRegularExpression('/PostController\.php:\d+:/', $output[0]);
        self::assertStringContainsString('publish', $output[0]);
        self::assertStringContainsString('publish', $output[1]);
        self::assertStringContainsString('destroy expects DELETE', $output[2]);
        self::assertStringContainsString('edit expects GET', $output[3]);
    }

    public function test_it_accepts_resource_and_invokable_controllers_and_routes(): void
    {
        self::assertSame([[], 0], $this->check('Valid'));
    }

    private function check(string $fixture): array
    {
        $command = escapeshellarg(PHP_BINARY).' '.escapeshellarg(__DIR__.'/../../../bin/rector-laravel-controller-check');
        exec($command.' '.escapeshellarg(__DIR__.'/Fixture/'.$fixture).' 2>&1', $output, $status);

        return [$output, $status];
    }
}
