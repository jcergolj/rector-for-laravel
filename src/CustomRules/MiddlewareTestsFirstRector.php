<?php

declare(strict_types=1);

namespace Jcergolj\RectorForLaravel\CustomRules;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use Rector\PhpParser\Node\BetterNodeFinder;
use Rector\PHPUnit\NodeAnalyzer\TestsNodeAnalyzer;
use RectorLaravel\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

class MiddlewareTestsFirstRector extends AbstractRector
{
    public function __construct(
        private readonly TestsNodeAnalyzer $testsNodeAnalyzer,
        private readonly BetterNodeFinder $betterNodeFinder,
    ) {}

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Group PHPUnit middleware-wiring tests before behavior tests', [
            new CodeSample(
                <<<'CODE_SAMPLE'
class ControllerTest extends \PHPUnit\Framework\TestCase
{
    public function test_index(): void {}
    public function test_auth(): void
    {
        $this->get('/')->assertMiddlewareIsApplied('auth');
    }
}
CODE_SAMPLE
                ,
                <<<'CODE_SAMPLE'
class ControllerTest extends \PHPUnit\Framework\TestCase
{
    public function test_auth(): void
    {
        $this->get('/')->assertMiddlewareIsApplied('auth');
    }
    public function test_index(): void {}
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
        if (! $this->testsNodeAnalyzer->isInTestClass($node)) {
            return null;
        }

        $testKeys = [];
        $middlewareTests = [];
        $otherTests = [];

        foreach ($node->stmts as $key => $stmt) {
            if (! $stmt instanceof ClassMethod || $stmt->isStatic()
                || ! $this->testsNodeAnalyzer->isTestClassMethod($stmt)) {
                continue;
            }

            $testKeys[] = $key;

            if ($this->hasMiddlewareAssertion($stmt)) {
                $middlewareTests[] = $stmt;
            } else {
                $otherTests[] = $stmt;
            }
        }

        $orderedTests = array_merge($middlewareTests, $otherTests);
        $hasChanged = false;

        // Only replace test slots; properties, lifecycle methods and providers stay in place.
        foreach ($testKeys as $index => $key) {
            if ($node->stmts[$key] !== $orderedTests[$index]) {
                $node->stmts[$key] = $orderedTests[$index];
                $hasChanged = true;
            }
        }

        return $hasChanged ? $node : null;
    }

    private function hasMiddlewareAssertion(ClassMethod $classMethod): bool
    {
        return $this->betterNodeFinder->findFirstInFunctionLikeScoped(
            $classMethod,
            function (Node $node): bool {
                return ($node instanceof MethodCall || $node instanceof StaticCall)
                    && $this->isName($node->name, 'assertMiddlewareIsApplied')
                    && ! $node->isFirstClassCallable();
            }
        ) !== null;
    }
}
