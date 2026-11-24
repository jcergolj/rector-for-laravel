<?php

declare(strict_types=1);

namespace Jcergolj\RectorForLaravel\Diagnostics;

use PhpParser\Error;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class ControllerRouteDiagnostic
{
    private const ACTIONS = [
        'index' => ['GET', 'collection'],
        'create' => ['GET', 'collection/create'],
        'store' => ['POST', 'collection'],
        'show' => ['GET', 'collection/{member}'],
        'edit' => ['GET', 'collection/{member}/edit'],
        'update' => ['PUT|PATCH', 'collection/{member}'],
        'destroy' => ['DELETE', 'collection/{member}'],
    ];

    /** @return list<string> */
    public function inspect(string $projectDirectory): array
    {
        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $finder = new NodeFinder;
        $diagnostics = [];

        foreach (['app/Http/Controllers' => Class_::class, 'routes' => StaticCall::class] as $directory => $nodeType) {
            foreach ($this->phpFiles($projectDirectory.'/'.$directory) as $file) {
                try {
                    $nodes = $parser->parse(file_get_contents($file)) ?? [];
                    $nodes = (new NodeTraverser(new NameResolver))->traverse($nodes);
                } catch (Error $exception) {
                    throw new Error($file.': '.$exception->getMessage());
                }

                foreach ($finder->findInstanceOf($nodes, $nodeType) as $node) {
                    if ($node instanceof Class_) {
                        foreach ($this->invalidActions($node) as $method) {
                            $diagnostics[] = "$file:{$method->getStartLine()}: {$method->name} is not a resource action or a lone __invoke().";
                        }
                    } else {
                        $problem = $this->routeProblem($node);
                        if ($problem !== null) {
                            $diagnostics[] = "$file:{$node->getStartLine()}: $problem";
                        }
                    }
                }
            }
        }

        return $diagnostics;
    }

    /** @return list<string> */
    private function phpFiles(string $directory): array
    {
        if (! is_dir($directory)) {
            return [];
        }

        $files = [];
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        sort($files);

        return $files;
    }

    /** @return list<ClassMethod> */
    private function invalidActions(Class_ $class): array
    {
        if ($class->isAnonymous() || $class->isAbstract()) {
            return [];
        }

        $actions = [];
        foreach ($class->getMethods() as $method) {
            $name = strtolower($method->name->toString());
            if ($method->isPublic() && ! in_array($name, ['__construct', 'middleware'], true)) {
                $actions[$name] = $method;
            }
        }

        if (count($actions) === 1 && isset($actions['__invoke'])) {
            return [];
        }

        return array_values(array_diff_key($actions, self::ACTIONS));
    }

    private function routeProblem(StaticCall $call): ?string
    {
        if (! $call->class instanceof Name || ! $call->name instanceof Identifier
            || ! in_array(strtolower($call->class->toString()), ['route', 'illuminate\support\facades\route'], true)
            || $call->isFirstClassCallable()) {
            return null;
        }

        $verb = strtoupper($call->name->toString());
        if (! in_array($verb, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'ANY'], true)) {
            return null;
        }

        $arguments = $call->getArgs();
        $uri = $arguments[0]->value ?? null;
        $target = $arguments[1]->value ?? null;
        $method = $this->routeAction($target);
        if ($method === null || $method === '__invoke') {
            return null;
        }

        if (! isset(self::ACTIONS[$method])) {
            return "Route targets non-resource action $method().";
        }

        [$verbs, $path] = self::ACTIONS[$method];
        $pathMatches = ! $uri instanceof String_ || $this->matchesPath($uri->value, $path);
        if (in_array($verb, explode('|', $verbs), true) && $pathMatches) {
            return null;
        }

        return "$method expects $verbs /$path.";
    }

    private function routeAction(?Expr $target): ?string
    {
        if ($target instanceof String_ && preg_match('/@(\w+)$/D', $target->value, $matches) === 1) {
            return strtolower($matches[1]);
        }

        if (! $target instanceof Array_ || count($target->items) !== 2) {
            return null;
        }

        [$controller, $action] = $target->items;
        if ($controller->key !== null || $action->key !== null
            || ! $controller->value instanceof ClassConstFetch
            || ! $controller->value->name instanceof Identifier
            || strtolower($controller->value->name->toString()) !== 'class'
            || ! $action->value instanceof String_) {
            return null;
        }

        return strtolower($action->value->value);
    }

    private function matchesPath(string $uri, string $path): bool
    {
        $collection = '[^/{}]+';
        $member = '\{[^/{}?]+\}';
        $suffix = strtr($path, ['collection' => $collection, '{member}' => $member]);

        return preg_match('~(?:^|/)'.$suffix.'$~D', trim($uri, '/')) === 1;
    }
}
