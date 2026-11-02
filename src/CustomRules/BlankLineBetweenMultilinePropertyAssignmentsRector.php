<?php

declare(strict_types=1);

namespace Jcergolj\RectorForLaravel\CustomRules;

use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\NullsafePropertyFetch;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Nop;
use RectorLaravel\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class BlankLineBetweenMultilinePropertyAssignmentsRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Separate adjacent assignments with multiline property-access chains', [
            new CodeSample(
                "\$first = \$object->section\n    ->first;\n\$second = \$object->section\n    ->second;",
                "\$first = \$object->section\n    ->first;\n\n\$second = \$object->section\n    ->second;",
            ),
        ]);
    }

    /** @return array<class-string<Node>> */
    public function getNodeTypes(): array
    {
        return [Stmt::class, Closure::class];
    }

    public function refactor(Node $node): ?Node
    {
        if (! property_exists($node, 'stmts') || ! is_array($node->stmts)) {
            return null;
        }

        $stmts = [];
        $previous = null;
        $hasChanged = false;

        foreach ($node->stmts as $stmt) {
            if ($previous !== null && $this->isMultilinePropertyAssignment($previous)
                && $this->isMultilinePropertyAssignment($stmt)) {
                $comments = $stmt->getComments();
                $startLine = $comments === [] ? $stmt->getStartLine() : $comments[0]->getStartLine();

                // A trailing comment may belong to the previous statement.
                if ($startLine - $previous->getEndLine() === 1) {
                    $stmts[] = new Nop;
                    $hasChanged = true;
                }
            }

            $stmts[] = $stmt;
            $previous = $stmt;
        }

        if (! $hasChanged) {
            return null;
        }

        $node->stmts = $stmts;

        return $node;
    }

    private function isMultilinePropertyAssignment(Node $node): bool
    {
        if (! $node instanceof Expression || ! $node->expr instanceof Assign) {
            return false;
        }

        $expr = $node->expr->expr;
        if (! $expr instanceof PropertyFetch && ! $expr instanceof NullsafePropertyFetch) {
            return false;
        }

        return ($expr->var instanceof PropertyFetch || $expr->var instanceof NullsafePropertyFetch)
            && $expr->getStartLine() < $expr->getEndLine();
    }
}
