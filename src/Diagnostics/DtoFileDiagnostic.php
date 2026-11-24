<?php

declare(strict_types=1);

namespace Jcergolj\RectorForLaravel\Diagnostics;

use Composer\Autoload\ClassLoader;
use InvalidArgumentException;
use PhpParser\Error;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Declare_;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Nop;
use PhpParser\Node\Stmt\Use_;
use PhpParser\ParserFactory;

final class DtoFileDiagnostic
{
    /**
     * @return list<string>
     */
    public function inspect(string $directory, ClassLoader $loader): array
    {
        $path = realpath($directory);
        if ($path === false || ! is_dir($path)) {
            throw new InvalidArgumentException("Not a directory: $directory");
        }

        $namespaces = $this->namespacesForDirectory($path, $loader);
        if ($namespaces === []) {
            throw new InvalidArgumentException("Directory is not covered by a Composer PSR-4 mapping: $directory");
        }

        $filenames = scandir($path);
        if ($filenames === false) {
            throw new InvalidArgumentException("Cannot read directory: $directory");
        }

        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $diagnostics = [];
        foreach ($filenames as $filename) {
            $source = $path.'/'.$filename;
            if (! str_ends_with($filename, '.php') || ! is_file($source) || is_link($source)) {
                continue;
            }

            $contents = file_get_contents($source);
            if ($contents === false) {
                $diagnostics[] = "$source: skipped (cannot read file)";

                continue;
            }

            try {
                $statements = $parser->parse($contents);
            } catch (Error $error) {
                $diagnostics[] = "$source: skipped (".$error->getMessage().')';

                continue;
            }

            $namespace = $this->simpleNamespace($statements);
            $namespaceName = $namespace?->name?->toString();
            if ($namespaceName === null || ! in_array($namespaceName, $namespaces, true)) {
                continue;
            }

            $classes = [];
            foreach ($namespace->stmts as $statement) {
                if ($statement instanceof Class_ && $statement->name !== null) {
                    $classes[] = $statement->name->toString();
                } elseif (! $statement instanceof Use_ && ! $statement instanceof GroupUse && ! $statement instanceof Nop) {
                    continue 2;
                }
            }

            $primary = substr($filename, 0, -4);
            if (! in_array($primary, $classes, true)) {
                continue;
            }

            foreach ($classes as $class) {
                if ($class === $primary) {
                    continue;
                }

                $destination = "$path/$class.php";
                $className = "$namespaceName\\$class";
                $diagnostics[] = file_exists($destination) || is_link($destination)
                    ? "$source: cannot move $className; destination already exists: $destination"
                    : "$source: move $className to $destination";
            }
        }

        return $diagnostics;
    }

    /**
     * @return list<string>
     */
    private function namespacesForDirectory(string $directory, ClassLoader $loader): array
    {
        $namespaces = [];
        foreach ($loader->getPrefixesPsr4() as $prefix => $roots) {
            foreach ($roots as $root) {
                $root = realpath($root);
                if ($root === false) {
                    continue;
                }

                if ($directory === $root || str_starts_with($directory.'/', rtrim($root, '/').'/')) {
                    $relative = trim(substr($directory, strlen($root)), '/');
                    $namespaces[] = rtrim($prefix.str_replace('/', '\\', $relative), '\\');
                }
            }
        }

        return $namespaces;
    }

    /**
     * @param  Stmt[]  $statements
     */
    private function simpleNamespace(array $statements): ?Namespace_
    {
        $namespace = null;
        foreach ($statements as $statement) {
            if ($statement instanceof Namespace_ && $namespace === null) {
                $namespace = $statement;
            } elseif (! $statement instanceof Nop
                && ! ($namespace === null && $statement instanceof Declare_ && $statement->stmts === null)) {
                return null;
            }
        }

        return $namespace;
    }
}
