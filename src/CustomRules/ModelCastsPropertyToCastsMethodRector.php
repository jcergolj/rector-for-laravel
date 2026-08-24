<?php

declare(strict_types=1);

namespace Jcergolj\RectorForLaravel\CustomRules;

use Illuminate\Database\Eloquent\Model;
use PhpParser\BuilderFactory;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeFinder;
use PHPStan\PhpDocParser\Ast\PhpDoc\ReturnTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode;
use PHPStan\Type\ObjectType;
use Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Rector\Comments\NodeDocBlock\DocBlockUpdater;
use RectorLaravel\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * @see ModelCastsPropertyToCastsMethodRectorTest
 */
class ModelCastsPropertyToCastsMethodRector extends AbstractRector
{
    public function __construct(
        private readonly BuilderFactory $builderFactory,
        private readonly PhpDocInfoFactory $phpDocInfoFactory,
        private readonly DocBlockUpdater $docBlockUpdater,
    ) {}

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Convert Eloquent $casts properties to protected casts(): array methods',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $casts = [
        'age' => 'integer',
    ];
}
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected function casts(): array
    {
        return [
            'age' => 'integer',
        ];
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
        if (! $this->isEloquentModelClass($node)) {
            return null;
        }

        foreach ($node->stmts as $stmt) {
            if ($stmt instanceof ClassMethod && $this->isName($stmt, 'casts')) {
                return null;
            }
        }

        foreach ($node->stmts as $index => $stmt) {
            if (! $stmt instanceof Property || ! $this->isName($stmt, 'casts') || ! $stmt->isProtected()) {
                continue;
            }

            $castsProperty = $stmt->props[0] ?? null;

            if ($castsProperty === null) {
                return null;
            }

            $method = $this->builderFactory->method('casts')
                ->setReturnType('array')
                ->makeProtected();

            if ($stmt->getDocComment() instanceof Doc) {
                $method->setDocComment($stmt->getDocComment());
            }

            $method->addStmt(new Return_($castsProperty->default));
            $methodNode = $method->getNode();
            $this->restorePhpDoc($methodNode);

            $node->stmts[$index] = $methodNode;

            return $node;
        }

        return null;
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

    private function restorePhpDoc(ClassMethod $methodNode): void
    {
        $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($methodNode);
        $varTagValueNode = $phpDocInfo->getVarTagValueNode();

        if (! $varTagValueNode instanceof VarTagValueNode) {
            return;
        }

        $phpDocInfo->addTagValueNode(new ReturnTagValueNode($varTagValueNode->type, $varTagValueNode->description));
        $phpDocInfo->removeByType(VarTagValueNode::class);
        $this->docBlockUpdater->updateRefactoredNodeWithPhpDocInfo($methodNode);
    }
}
