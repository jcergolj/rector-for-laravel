<?php

declare(strict_types=1);

namespace Jcergolj\RectorForLaravel\CustomRules;

use Illuminate\Database\Eloquent\Model;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeFinder;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use RectorLaravel\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * @see ModelQueryToDirectStaticCallRectorTest
 */
class ModelQueryToDirectStaticCallRector extends AbstractRector
{
    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
    ) {}

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Rewrite fluent model query chains that start with ::query() to direct static model calls',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
use App\Models\Invoice;

Invoice::query()->where('status', 'paid')->latest()->first();
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
use App\Models\Invoice;

Invoice::where('status', 'paid')->latest()->first();
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
        return [MethodCall::class];
    }

    /**
     * @param  MethodCall  $node
     */
    public function refactor(Node $node): ?Node
    {
        if (! $node->var instanceof StaticCall) {
            return null;
        }

        if (! $this->isName($node->var->name, 'query')) {
            return null;
        }

        if (! $this->isEloquentModelClass($node->var)) {
            return null;
        }

        $staticCall = new StaticCall($node->var->class, $node->name, $node->args);
        $this->mirrorComments($staticCall, $node);

        return $staticCall;
    }

    private function isEloquentModelClass(StaticCall $staticCall): bool
    {
        $resolvedType = $this->nodeTypeResolver->getType($staticCall->class);

        $classNames = $resolvedType->isClassString()->yes()
            ? $resolvedType->getClassStringObjectType()->getObjectClassNames()
            : $resolvedType->getObjectClassNames();

        foreach ($classNames as $className) {
            if (! $this->reflectionProvider->hasClass($className)) {
                continue;
            }

            $classReflection = $this->reflectionProvider->getClass($className);

            if ($classReflection instanceof ClassReflection && $classReflection->is(Model::class)) {
                return true;
            }
        }

        return $this->isLocalModelClass($staticCall);
    }

    private function isLocalModelClass(StaticCall $staticCall): bool
    {
        $className = $this->getName($staticCall->class);

        if ($className === null) {
            return false;
        }

        $nodeFinder = new NodeFinder;
        $class = $nodeFinder->findFirst(
            $this->getFile()->getNewStmts(),
            function (Node $node) use ($className): bool {
                return $node instanceof Class_ && $this->getName($node) === $className;
            }
        );

        if (! $class instanceof Class_ || ! $class->extends instanceof Node\Name) {
            return false;
        }

        return $this->isNames($class->extends, [Model::class, 'Model']);
    }
}
