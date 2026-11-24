<?php

declare(strict_types=1);

namespace Jcergolj\RectorForLaravel\Tests\Diagnostics\DtoFileDiagnostic;

use Composer\Autoload\ClassLoader;
use InvalidArgumentException;
use Jcergolj\RectorForLaravel\Diagnostics\DtoFileDiagnostic;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class DtoFileDiagnosticTest extends TestCase
{
    public function test_reports_moves_and_conflicts_without_changing_files(): void
    {
        $root = __DIR__.'/Fixture/Before';
        $directory = $root.'/Data';
        $loader = new ClassLoader;
        $loader->addPsr4('DtoExample\\', $root);
        $diagnostic = new DtoFileDiagnostic;
        $before = array_map('file_get_contents', glob($directory.'/*.php'));

        $expected = [
            "$directory/Blocked.php: cannot move DtoExample\\Data\\Taken; destination already exists: $directory/Taken.php",
            "$directory/Payload.php: move DtoExample\\Data\\Summary to $directory/Summary.php",
            "$directory/Payload.php: move DtoExample\\Data\\Insight to $directory/Insight.php",
        ];

        $this->assertSame($expected, $diagnostic->inspect($directory, $loader));
        $this->assertSame($expected, $diagnostic->inspect($directory, $loader));
        $this->assertSame($before, array_map('file_get_contents', glob($directory.'/*.php')));
    }

    public function test_refuses_a_directory_outside_the_mapping(): void
    {
        $loader = new ClassLoader;
        $loader->addPsr4('DtoExample\\', __DIR__.'/Fixture/Before');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Directory is not covered by a Composer PSR-4 mapping');

        (new DtoFileDiagnostic)->inspect(__DIR__.'/Fixture/After/Data', $loader);
    }

    #[DataProvider('movedClasses')]
    #[RunInSeparateProcess]
    public function test_manually_split_classes_autoload_on_their_own(string $name): void
    {
        $root = __DIR__.'/Fixture/After';
        $loader = new ClassLoader;
        $loader->addPsr4('DtoExample\\', $root);
        $loader->register();
        $class = 'DtoExample\\Data\\'.$name;

        try {
            $this->assertSame([], (new DtoFileDiagnostic)->inspect($root.'/Data', $loader));
            $this->assertFalse(class_exists($class, false));
            $this->assertTrue(class_exists($class));
            $this->assertSame($root.'/Data/'.$name.'.php', (new ReflectionClass($class))->getFileName());
            $this->assertFalse(class_exists('DtoExample\\Data\\Payload', false));
        } finally {
            $loader->unregister();
        }
    }

    public static function movedClasses(): iterable
    {
        yield ['Summary'];
        yield ['Insight'];
    }
}
