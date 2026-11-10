<?php

declare(strict_types=1);

namespace Jcergolj\RectorForLaravel\CustomRules;

use PhpParser\Node;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\InlineHTML;
use PhpParser\Node\Stmt\Nop;
use RectorLaravel\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class BlankLineBetweenStatementsRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Separate consecutive statements with a blank line', [
            new CodeSample("\$person = findPerson();\n\$person->save();", "\$person = findPerson();\n\n\$person->save();"),
        ]);
    }

    /** @return array<class-string<Node>> */
    public function getNodeTypes(): array
    {
        return [Stmt::class, Closure::class];
    }

    public function refactor(Node $node): ?Node
    {
        // Class members are declarations, not a list of executable statements.
        if ($node instanceof ClassLike || ! property_exists($node, 'stmts') || ! is_array($node->stmts)) {
            return null;
        }

        $previous = null;
        $hasChanged = false;

        foreach ($node->stmts as $stmt) {
            // Whitespace at a PHP/HTML boundary can be literal rendered output.
            if ($stmt instanceof InlineHTML) {
                $previous = null;

                continue;
            }

            if ($previous !== null && ! $stmt instanceof Nop) {
                // Skipped-rule probes may share printer tokens with the real file.
                if (isset($this->skipper) && method_exists($this->skipper, 'matchSkip') && $this->skipper->matchSkip($this, $this->getFile()->getFilePath()) !== null) {
                    return $node;
                }

                $hasChanged = $this->insertBlankLine($previous, $stmt) || $hasChanged;
            }

            // Nops also represent spacing inserted by the PHPUnit assertion rule.
            $previous = $stmt instanceof Nop ? null : $stmt;
        }

        return $hasChanged ? $node : null;
    }

    private function insertBlankLine(Stmt $previous, Stmt $current): bool
    {
        $position = $previous->getEndTokenPos();
        if ($position < 0) {
            return false;
        }

        $tokens = $this->getFile()->getOldTokens();
        // PHP-Parser attaches inline trailing comments to the following statement.
        // Insert after those comments, but before the following leading comments.
        foreach ($current->getComments() as $comment) {
            if ($comment->getStartLine() > $previous->getEndLine()) {
                break;
            }

            $position = $comment->getEndTokenPos();
        }

        // Reuse the assignment rule's token-based insertion: never change the AST layout.
        if (preg_match('/\n[ \t]*$/', $tokens[$position]->text) === 1) {
            return false;
        }

        $next = $tokens[$position + 1] ?? null;
        if ($next?->id === T_WHITESPACE && substr_count($next->text, "\n") >= 2) {
            return false;
        }

        $newline = str_contains($this->getFile()->getOriginalFileContent(), "\r\n") ? "\r\n" : "\n";
        if ($next?->id === T_WHITESPACE && str_contains($next->text, "\n")) {
            $next->text = $newline.ltrim($next->text, " \t");
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
}
