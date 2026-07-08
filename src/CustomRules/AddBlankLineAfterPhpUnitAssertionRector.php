<?php

declare(strict_types=1);

namespace Jcergolj\RectorForLaravel\CustomRules;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Nop;
use Rector\PHPUnit\NodeAnalyzer\AssertCallAnalyzer;
use Rector\PHPUnit\NodeAnalyzer\TestsNodeAnalyzer;
use RectorLaravel\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * @see AddBlankLineAfterPhpUnitAssertionRectorTest
 */
final class AddBlankLineAfterPhpUnitAssertionRector extends AbstractRector
{
    public function __construct(
        private readonly TestsNodeAnalyzer $testsNodeAnalyzer,
        private readonly AssertCallAnalyzer $assertCallAnalyzer,
    ) {}

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Add a blank line after each PHPUnit assertion in test methods',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
class ExampleTest extends \PHPUnit\Framework\TestCase
{
    public function testFoo(): void
    {
        $this->assertTrue(true);
        $this->assertEquals(1, 1);
    }
}
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
class ExampleTest extends \PHPUnit\Framework\TestCase
{
    public function testFoo(): void
    {
        $this->assertTrue(true);

        $this->assertEquals(1, 1);
    }
}
CODE_SAMPLE
                ),
            ]
        );
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [ClassMethod::class];
    }

    /**
     * @param  ClassMethod  $node
     */
    public function refactor(Node $node): ?Node
    {
        if (! $this->testsNodeAnalyzer->isInTestClass($node)) {
            return null;
        }

        if (! $this->testsNodeAnalyzer->isTestClassMethod($node)) {
            return null;
        }

        if ($node->stmts === null || $node->stmts === []) {
            return null;
        }

        $hasChanged = false;
        $totalKeys = array_key_last($node->stmts);

        $this->addBlankLineAboveFirstAssertion($node, $totalKeys, $hasChanged);

        for ($key = 0; $key < $totalKeys; $key++) {
            $stmt = $node->stmts[$key];

            if (! $stmt instanceof Expression) {
                continue;
            }

            $expr = $stmt->expr;
            if (! $expr instanceof MethodCall && ! $expr instanceof StaticCall) {
                continue;
            }

            if (! $this->assertCallAnalyzer->isAssertMethodCall($expr)) {
                continue;
            }

            $nextStmt = $node->stmts[$key + 1];

            if ($nextStmt instanceof Nop) {
                continue;
            }

            if ($nextStmt->getStartLine() - $stmt->getEndLine() > 1) {
                continue;
            }

            array_splice($node->stmts, $key + 1, 0, [new Nop]);
            $hasChanged = true;
            $totalKeys++;
            $key++;
        }

        return $hasChanged ? $node : null;
    }

    private function addBlankLineAboveFirstAssertion(ClassMethod $node, int &$totalKeys, bool &$hasChanged): void
    {
        foreach ($node->stmts as $key => $stmt) {
            if (! $stmt instanceof Expression) {
                continue;
            }

            $expr = $stmt->expr;
            if (! $expr instanceof MethodCall && ! $expr instanceof StaticCall) {
                continue;
            }

            if (! $this->assertCallAnalyzer->isAssertMethodCall($expr)) {
                continue;
            }

            if ($key === 0) {
                return;
            }

            $previousStmt = $node->stmts[$key - 1];

            if ($previousStmt instanceof Nop) {
                return;
            }

            if ($stmt->getStartLine() - $previousStmt->getEndLine() > 1) {
                return;
            }

            array_splice($node->stmts, $key, 0, [new Nop]);
            $hasChanged = true;
            $totalKeys++;

            return;
        }
    }
}
