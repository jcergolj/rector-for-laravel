<?php

declare(strict_types=1);

namespace Jcergolj\RectorForLaravel\CustomRules;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Expression;
use RectorLaravel\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class BlankLineBetweenUnrelatedAssignmentsRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Separate adjacent assignments to unrelated variables in any scope', [
            new CodeSample(
                "\$user = User::factory()->create();\n\$name = \$user->name;\n\$invoice = Invoice::factory()->create();",
                "\$user = User::factory()->create();\n\$name = \$user->name;\n\n\$invoice = Invoice::factory()->create();",
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

        $previousStmt = null;
        $previous = null;
        $hasChanged = false;

        foreach ($node->stmts as $stmt) {
            $current = $this->assignment($stmt);
            if ($previous !== null && $current !== null
                && $previous['target'] !== $current['target']
                && ! isset($current['reads'][$previous['target']])
                && ! isset($previous['reads'][$current['target']])) {
                $comments = $stmt->getComments();
                $startLine = $comments === [] ? $stmt->getStartLine() : $comments[0]->getStartLine();

                // PHP-Parser attaches trailing comments to the next statement. Leave them in place.
                $hasTrailingComment = $comments !== [] && $startLine <= $previousStmt->getEndLine();
                if (! $hasTrailingComment && $startLine - $previousStmt->getEndLine() <= 1) {
                    // Skipped-rule probes may share printer tokens with the real file.
                    if (isset($this->skipper) && method_exists($this->skipper, 'matchSkip') && $this->skipper->matchSkip($this, $this->getFile()->getFilePath()) !== null) {
                        return $node;
                    }
                    $hasChanged = $this->insertBlankLine($previousStmt) || $hasChanged;
                }
            }

            $previousStmt = $stmt;
            $previous = $current;
        }

        if (! $hasChanged) {
            return null;
        }
        return $node;
    }

    private function insertBlankLine(Stmt $previous): bool
    {
        $position = $previous->getEndTokenPos();
        if ($position < 0) {
            return false;
        }
        $tokens = $this->getFile()->getOldTokens();
        // Rector may visit the same tokens repeatedly before printing.
        if (str_contains($tokens[$position]->text, "\n")) {
            return false;
        }
        $next = $tokens[$position + 1] ?? null;
        if ($next?->id === T_WHITESPACE && substr_count($next->text, "\n") >= 2) {
            return false;
        }
        $newline = str_contains($this->getFile()->getOriginalFileContent(), "\r\n") ? "\r\n" : "\n";
        if ($next?->id === T_WHITESPACE && str_contains($next->text, "\n")) {
            $next->text = $newline.$next->text;
        } else {
            $lines = explode("\n", $this->getFile()->getOriginalFileContent());
            $line = $lines[$previous->getStartLine() - 1];
            $indent = substr($line, 0, strspn($line, " \t"));
            if ($next?->id === T_WHITESPACE) {
                $next->text = $newline.$newline.$indent;
            } else {
                $tokens[$position]->text .= $newline.$newline.$indent;
            }
        }

        return true;
    }

    /** @return array{target: string, reads: array<string, true>}|null */
    private function assignment(Node $node): ?array
    {
        if (! $node instanceof Expression || ! $node->expr instanceof Assign) {
            return null;
        }
        $target = $this->target($node->expr->var);
        if ($target === null) {
            return null;
        }
        $reads = $this->reads($node->expr->expr);

        return $reads === null ? null : ['target' => $target, 'reads' => $reads];
    }

    private function target(Node $node): ?string
    {
        if ($node instanceof Variable && is_string($node->name) && $node->name !== 'this') {
            return '$'.$node->name;
        }
        if ($node instanceof PropertyFetch && $node->var instanceof Variable
            && $node->var->name === 'this' && $node->name instanceof Identifier) {
            return '$this->'.$node->name->toString();
        }

        return null;
    }

    /** @return array<string, true>|null Null means a dependency cannot be determined safely. */
    private function reads(Node $node): ?array
    {
        if ($node instanceof Variable) {
            return is_string($node->name) ? ($node->name === 'this' ? [] : ['$'.$node->name => true]) : null;
        }
        if ($node instanceof ClassLike) {
            // Anonymous-class bodies have their own scope; constructor arguments are visited separately.
            return [];
        }
        if ($node instanceof Closure) {
            // Only explicit captures read surrounding locals, not the closure's parameters or body.
            $reads = [];
            foreach ($node->uses as $use) {
                $captured = $this->reads($use->var);
                if ($captured === null) {
                    return null;
                }
                $reads += $captured;
            }

            return $reads;
        }
        if ($node instanceof ArrowFunction) {
            $reads = $this->reads($node->expr);
            if ($reads !== null) {
                foreach ($node->params as $param) {
                    $parameter = $this->target($param->var);
                    unset($reads[$parameter]);
                }
            }

            return $reads;
        }
        if ($node instanceof Assign || $node instanceof Expr\AssignRef || $node instanceof Expr\AssignOp
            || $node instanceof Expr\PreInc || $node instanceof Expr\PostInc
            || $node instanceof Expr\PreDec || $node instanceof Expr\PostDec
            || $node instanceof Expr\Eval_ || $node instanceof Expr\Include_) {
            return null;
        }
        if ($node instanceof Expr\FuncCall && $node->name instanceof Node\Name
            && $this->isNames($node->name, ['compact', 'get_defined_vars', 'extract'])) {
            return null;
        }

        $reads = [];
        if ($node instanceof PropertyFetch || $node instanceof Expr\NullsafePropertyFetch) {
            if (! $node->name instanceof Identifier) {
                return null;
            }
            if ($node->var instanceof Variable && $node->var->name === 'this') {
                $reads['$this->'.$node->name->toString()] = true;
            }
        }
        foreach ($node->getSubNodeNames() as $name) {
            foreach (is_array($node->$name) ? $node->$name : [$node->$name] as $child) {
                if (! $child instanceof Node) {
                    continue;
                }
                $childReads = $this->reads($child);
                if ($childReads === null) {
                    return null;
                }
                $reads += $childReads;
            }
        }

        return $reads;
    }
}
