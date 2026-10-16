<?php

declare(strict_types=1);

namespace Jcergolj\RectorForLaravel\CustomRules;

use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Nop;
use Rector\PHPUnit\NodeAnalyzer\TestsNodeAnalyzer;
use Rector\Reflection\ReflectionResolver;
use RectorLaravel\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

class BlankLineBetweenTestFixturesRector extends AbstractRector
{
    public function __construct(
        private readonly TestsNodeAnalyzer $testsNodeAnalyzer,
        private readonly ReflectionResolver $reflectionResolver,
    ) {}

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Separate consecutive Laravel factory fixture assignments in PHPUnit tests', [
            new CodeSample(
                <<<'CODE_SAMPLE'
class ExampleTest extends \PHPUnit\Framework\TestCase
{
    public function test_tokens(): void
    {
        $buyer = User::factory()->create();
        $token = Token::factory()->for($buyer)->create();
    }
}
CODE_SAMPLE
                ,
                <<<'CODE_SAMPLE'
class ExampleTest extends \PHPUnit\Framework\TestCase
{
    public function test_tokens(): void
    {
        $buyer = User::factory()->create();

        $token = Token::factory()->for($buyer)->create();
    }
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
        return [ClassMethod::class];
    }

    /**
     * @param  ClassMethod  $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($node->isStatic() || ! $this->testsNodeAnalyzer->isInTestClass($node)
            || $this->reflectionResolver->resolveClassReflection($node)?->isTrait()) {
            return null;
        }

        if (! $this->testsNodeAnalyzer->isTestClassMethod($node) && ! $this->isName($node, 'setUp')) {
            return null;
        }

        $stmts = [];
        $previousStmt = null;
        $previousTarget = null;
        $hasChanged = false;

        foreach ($node->stmts ?? [] as $stmt) {
            $target = $this->fixtureTarget($stmt);

            if ($target !== null && $previousTarget !== null && $target !== $previousTarget) {
                $comments = $stmt->getComments();
                $startLine = $comments === [] ? $stmt->getStartLine() : $comments[0]->getStartLine();

                // Trailing comments are attached to the next statement by PHP-Parser.
                // Do not insert before them: that would detach them from their source line.
                $hasTrailingComment = $comments !== [] && $startLine <= $previousStmt->getEndLine();

                if (! $hasTrailingComment && $startLine - $previousStmt->getEndLine() <= 1) {
                    $stmts[] = new Nop;
                    $hasChanged = true;
                }
            }

            $stmts[] = $stmt;
            $previousStmt = $stmt;
            $previousTarget = $target;
        }

        if (! $hasChanged) {
            return null;
        }

        $node->stmts = $stmts;

        return $node;
    }

    private function fixtureTarget(Node $node): ?string
    {
        if (! $node instanceof Expression || ! $node->expr instanceof Assign) {
            return null;
        }

        $assign = $node->expr;
        $expr = $assign->expr;

        // Syntactic boundary: a named Class::factory() and its fluent method calls only.
        while ($expr instanceof MethodCall) {
            if (! $expr->name instanceof Identifier || $expr->isFirstClassCallable()) {
                return null;
            }

            $expr = $expr->var;
        }

        if (! $expr instanceof StaticCall || ! $expr->class instanceof Name
            || ! $this->isName($expr->name, 'factory') || $expr->isFirstClassCallable()) {
            return null;
        }

        if ($assign->var instanceof Variable && is_string($assign->var->name) && $assign->var->name !== 'this') {
            return '$'.$assign->var->name;
        }

        if ($assign->var instanceof PropertyFetch && $assign->var->var instanceof Variable
            && $this->isName($assign->var->var, 'this') && $assign->var->name instanceof Identifier) {
            return '$this->'.$assign->var->name->toString();
        }

        return null;
    }
}
