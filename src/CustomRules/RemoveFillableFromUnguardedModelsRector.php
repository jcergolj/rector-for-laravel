<?php

declare(strict_types=1);

namespace Jcergolj\RectorForLaravel\CustomRules;

use Illuminate\Database\Eloquent\Model;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Property;
use PhpParser\NodeFinder;
use PHPStan\Type\ObjectType;
use RectorLaravel\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * @see RemoveFillableFromUnguardedModelsRectorTest
 */
class RemoveFillableFromUnguardedModelsRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Remove the $fillable property from Eloquent models in unguarded-model codebases',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $fillable = ['name', 'email'];
}
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
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
        if (! $this->isEloquentModelClass($node)) {
            return null;
        }

        $hasChanged = false;

        foreach ($node->stmts as $stmtKey => $stmt) {
            if (! $stmt instanceof Property) {
                continue;
            }

            foreach ($stmt->props as $propertyKey => $property) {
                if (! $this->isName($property, 'fillable')) {
                    continue;
                }

                if (count($stmt->props) === 1) {
                    unset($node->stmts[$stmtKey]);
                } else {
                    unset($stmt->props[$propertyKey]);
                    $stmt->props = array_values($stmt->props);
                }

                $hasChanged = true;
                break;
            }
        }

        if (! $hasChanged) {
            return null;
        }

        $node->stmts = array_values($node->stmts);

        return $node;
    }

    private function isEloquentModelClass(Class_ $class): bool
    {
        if ($this->isObjectType($class, new ObjectType(Model::class))) {
            return true;
        }

        return $this->extendsModelInCurrentFile($class, []);
    }

    /**
     * @param  string[]  $visitedClassNames
     */
    private function extendsModelInCurrentFile(Class_ $class, array $visitedClassNames): bool
    {
        if (! $class->extends instanceof Node\Name) {
            return false;
        }

        if ($this->isNames($class->extends, [Model::class, 'Model'])) {
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

        return $this->extendsModelInCurrentFile($parentClass, $visitedClassNames);
    }
}
