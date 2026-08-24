<?php

declare(strict_types=1);

namespace Jcergolj\RectorForLaravel\CustomRules;

use PhpParser\Node;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Stmt\Return_;
use Rector\Php72\NodeFactory\AnonymousFunctionFactory;
use RectorLaravel\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * @see ArrowFunctionToClosureRectorTest
 */
class ArrowFunctionToClosureRector extends AbstractRector
{
    public function __construct(
        private readonly AnonymousFunctionFactory $anonymousFunctionFactory,
    ) {}

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Rewrite arrow functions to equivalent closures with explicit use() captures',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
$names = array_map(fn (string $name): string => $prefix.$name, $users);
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
$names = array_map(function (string $name) use ($prefix): string {
    return $prefix.$name;
}, $users);
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
        return [ArrowFunction::class];
    }

    /**
     * @param  ArrowFunction  $node
     */
    public function refactor(Node $node): ?Node
    {
        $closure = $this->createClosureFromArrowFunction($node);
        $this->mirrorComments($closure, $node);

        return $closure;
    }

    private function createClosureFromArrowFunction(ArrowFunction $arrowFunction): Closure
    {
        $expr = $arrowFunction->expr;

        $this->traverseNodesWithCallable([$expr], function (Node $node): ?Node {
            if (! $node instanceof ArrowFunction) {
                return null;
            }

            $closure = $this->createClosureFromArrowFunction($node);
            $this->mirrorComments($closure, $node);

            return $closure;
        });

        $closure = $this->anonymousFunctionFactory->create(
            $arrowFunction->params,
            [new Return_($expr)],
            $arrowFunction->returnType,
            $arrowFunction->static,
        );

        $closure->byRef = $arrowFunction->byRef;

        return $closure;
    }
}
