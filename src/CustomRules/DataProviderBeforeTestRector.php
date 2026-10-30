<?php

declare(strict_types=1);

namespace Jcergolj\RectorForLaravel\CustomRules;

use PhpParser\Node;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use Rector\PHPUnit\NodeAnalyzer\TestsNodeAnalyzer;
use RectorLaravel\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

class DataProviderBeforeTestRector extends AbstractRector
{
    public function __construct(
        private readonly TestsNodeAnalyzer $testsNodeAnalyzer,
    ) {}

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Place local PHPUnit data providers immediately before their first consuming test', [
            new CodeSample(
                <<<'CODE_SAMPLE'
class ExampleTest extends \PHPUnit\Framework\TestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('cases')]
    public function test_value(string $value): void {}
    public static function cases(): array { return [['value']]; }
}
CODE_SAMPLE
                ,
                <<<'CODE_SAMPLE'
class ExampleTest extends \PHPUnit\Framework\TestCase
{
    public static function cases(): array { return [['value']]; }
    #[\PHPUnit\Framework\Attributes\DataProvider('cases')]
    public function test_value(string $value): void {}
}
CODE_SAMPLE
            ),
        ]);
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Class_::class];
    }

    /**
     * @param  Class_  $node
     */
    public function refactor(Node $node): ?Node
    {
        if (! $this->testsNodeAnalyzer->isInTestClass($node) || $this->hasTrailingMemberComment($node)) {
            return null;
        }

        $methods = [];
        $providersByTest = [];
        $providers = [];

        foreach ($node->getMethods() as $method) {
            $methods[strtolower($method->name->toString())] = $method;
        }

        foreach ($node->getMethods() as $method) {
            if (! $this->testsNodeAnalyzer->isTestClassMethod($method)) {
                continue;
            }

            $testName = strtolower($method->name->toString());

            foreach ($this->providerNames($method) as $providerName) {
                $providerName = strtolower($providerName);
                $provider = $methods[$providerName] ?? null;

                if ($provider === null || $this->testsNodeAnalyzer->isTestClassMethod($provider)) {
                    continue;
                }

                $providersByTest[$testName][$providerName] = $provider;
                $providers[$providerName] = $provider;
            }
        }

        $ordered = [];
        $placed = [];

        foreach ($node->stmts as $stmt) {
            if ($stmt instanceof ClassMethod) {
                $name = strtolower($stmt->name->toString());

                if (isset($providers[$name])) {
                    continue;
                }

                foreach ($providersByTest[$name] ?? [] as $providerName => $provider) {
                    if (! isset($placed[$providerName])) {
                        $ordered[] = $provider;
                        $placed[$providerName] = true;
                    }
                }
            }

            $ordered[] = $stmt;
        }

        if ($ordered === $node->stmts) {
            return null;
        }

        $node->stmts = $ordered;

        return $node;
    }

    /**
     * @return list<string>
     */
    private function providerNames(ClassMethod $method): array
    {
        $names = [];

        foreach ($method->attrGroups as $group) {
            foreach ($group->attrs as $attribute) {
                if (! $this->isName($attribute->name, 'PHPUnit\Framework\Attributes\DataProvider')) {
                    continue;
                }

                foreach ($attribute->args as $position => $argument) {
                    if (($argument->name === null && $position === 0)
                        || $argument->name?->toString() === 'methodName') {
                        if ($argument->value instanceof String_) {
                            $names[] = $argument->value->value;
                        }
                    }
                }
            }
        }

        $docComment = $method->getDocComment();

        if ($docComment !== null) {
            preg_match_all('/@dataProvider\s+([a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*)\s*(?=\r?$|\*\/)/m', $docComment->getText(), $matches);
            $names = array_merge($names, $matches[1]);
        }

        return $names;
    }

    private function hasTrailingMemberComment(Class_ $class): bool
    {
        $previousStmt = null;

        foreach ($class->stmts as $stmt) {
            $comments = $stmt->getComments();

            // PHP-Parser attaches trailing member comments to the following member.
            if ($previousStmt !== null && $comments !== []
                && $comments[0]->getStartLine() <= $previousStmt->getEndLine()) {
                return true;
            }

            $previousStmt = $stmt;
        }

        return false;
    }
}
