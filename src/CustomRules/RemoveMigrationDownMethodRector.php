<?php

declare(strict_types=1);

namespace Jcergolj\RectorForLaravel\CustomRules;

use Illuminate\Database\Migrations\Migration;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeFinder;
use PHPStan\Type\ObjectType;
use RectorLaravel\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * @see RemoveMigrationDownMethodRectorTest
 */
class RemoveMigrationDownMethodRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Remove down() methods from Laravel migrations in opinionated codebases',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    public function up(): void
    {
    }

    public function down(): void
    {
    }
}
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    public function up(): void
    {
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
        return [Class_::class];
    }

    /**
     * @param  Class_  $node
     */
    public function refactor(Node $node): ?Node
    {
        if (! $this->isMigrationClass($node)) {
            return null;
        }

        $hasChanged = false;

        foreach ($node->stmts as $stmtKey => $stmt) {
            if (! $stmt instanceof ClassMethod) {
                continue;
            }

            if (! $this->isName($stmt, 'down')) {
                continue;
            }

            unset($node->stmts[$stmtKey]);
            $hasChanged = true;
        }

        if (! $hasChanged) {
            return null;
        }

        $node->stmts = array_values($node->stmts);

        return $node;
    }

    private function isMigrationClass(Class_ $class): bool
    {
        if ($this->isObjectType($class, new ObjectType(Migration::class))) {
            return true;
        }

        return $this->extendsMigrationInCurrentFile($class, []);
    }

    /**
     * @param  string[]  $visitedClassNames
     */
    private function extendsMigrationInCurrentFile(Class_ $class, array $visitedClassNames): bool
    {
        if (! $class->extends instanceof Node\Name) {
            return false;
        }

        if ($this->isNames($class->extends, [Migration::class, 'Migration'])) {
            return true;
        }

        $parentClassName = $this->getName($class->extends);

        if ($parentClassName === null || in_array($parentClassName, $visitedClassNames, true)) {
            return false;
        }

        $nodeFinder = new NodeFinder;
        $parentClass = $nodeFinder->findFirst(
            $this->getFile()->getNewStmts(),
            function (Node $node) use ($parentClassName): bool {
                return $node instanceof Class_ && $this->getName($node) === $parentClassName;
            }
        );

        if (! $parentClass instanceof Class_) {
            return false;
        }

        $visitedClassNames[] = $parentClassName;

        return $this->extendsMigrationInCurrentFile($parentClass, $visitedClassNames);
    }
}
