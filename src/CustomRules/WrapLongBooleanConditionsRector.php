<?php

declare(strict_types=1);

namespace Jcergolj\RectorForLaravel\CustomRules;

use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp\BooleanAnd;
use PhpParser\Node\Expr\BinaryOp\BooleanOr;
use PhpParser\Node\Stmt\Do_;
use PhpParser\Node\Stmt\ElseIf_;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\While_;
use PhpParser\Token;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use RectorLaravel\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class WrapLongBooleanConditionsRector extends AbstractRector implements ConfigurableRectorInterface
{
    public const MAX_LINE_LENGTH = 'max_line_length';

    private int $maxLineLength = 120;

    public function configure(array $configuration): void
    {
        $length = array_key_exists(self::MAX_LINE_LENGTH, $configuration) ? $configuration[self::MAX_LINE_LENGTH] : 120;
        if (! is_int($length) || $length < 1) {
            throw new \InvalidArgumentException('max_line_length must be a positive integer.');
        }

        $this->maxLineLength = $length;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Wrap long boolean conditions with trailing operators', [
            new CodeSample(
                "if (\$request->routeIs('licence-agreement.create') || \$request->routeIs('licence-agreement.store') || \$request->routeIs('download-licence-agreement')) {\n}",
                "if (\n    \$request->routeIs('licence-agreement.create') ||\n    \$request->routeIs('licence-agreement.store') ||\n    \$request->routeIs('download-licence-agreement')\n) {\n}",
            ),
        ]);
    }

    /** @return array<class-string<Node>> */
    public function getNodeTypes(): array
    {
        return [If_::class, ElseIf_::class, While_::class, Do_::class];
    }

    /** @param If_|ElseIf_|While_|Do_ $node */
    public function refactor(Node $node): ?Node
    {
        if ((! $node->cond instanceof BooleanAnd && ! $node->cond instanceof BooleanOr)
            || $node->getStartTokenPos() < 0 || $node->cond->getStartTokenPos() < 0) {
            return null;
        }

        $tokens = $this->getFile()->getOldTokens();
        [$keyword, $open, $close] = $this->findHeader($node, $tokens);
        $lines = explode("\n", $this->getFile()->getOriginalFileContent());
        $line = str_replace("\t", '    ', rtrim($lines[$tokens[$open]->line - 1], "\r"));
        if ($tokens[$open]->line !== $tokens[$close]->line || str_contains($tokens[$open]->text, "\n")
            || mb_strlen($line) <= $this->maxLineLength) {
            return null;
        }

        // Skipped-rule probes share token objects with the real file.
        if (method_exists($this->skipper, 'matchSkip') && $this->skipper->matchSkip($this, $this->getFile()->getFilePath()) !== null) {
            return $node;
        }

        $newline = str_contains($this->getFile()->getOriginalFileContent(), "\r\n") ? "\r\n" : "\n";
        // Earlier wrapping rules may have moved this header to a deeper line.
        $prefix = '';
        for ($position = $keyword - 1; $position >= 0; $position--) {
            $prefix = $tokens[$position]->text.$prefix;
            if (str_contains($prefix, "\n")) {
                $prefix = substr($prefix, strrpos($prefix, "\n") + 1);
                break;
            }
        }
        $indent = str_replace("\t", '    ', substr($prefix, 0, strspn($prefix, " \t")));
        $this->newlineAfter($tokens, $open, $indent.'    ', $newline);
        $this->wrapOperators($node->cond, $tokens, $indent.'    ', $newline);
        $tokens[$close]->text = $newline.$indent.$tokens[$close]->text;
        for ($position = $close - 1; $tokens[$position]->id === T_WHITESPACE; $position--) {
            $tokens[$position]->text = '';
        }

        return $node;
    }

    /**
     * @param  If_|ElseIf_|While_|Do_  $node
     * @param  array<Token>  $tokens
     * @return array{int, int, int}
     */
    private function findHeader(Node $node, array $tokens): array
    {
        // Search backwards so do/while bodies cannot supply the wrong parentheses.
        $keyword = $node->cond->getStartTokenPos();
        while ($keyword > $node->getStartTokenPos() && ! in_array($tokens[$keyword]->id, [T_IF, T_ELSEIF, T_WHILE], true)) {
            $keyword--;
        }
        $open = $keyword + 1;
        while ($tokens[$open]->id !== ord('(')) {
            $open++;
        }
        $depth = 1;
        $close = $open;
        while ($depth > 0) {
            $id = $tokens[++$close]->id;
            if ($id === ord('(')) {
                $depth++;
            } elseif ($id === ord(')')) {
                $depth--;
            }
        }

        return [$keyword, $open, $close];
    }

    /** @param array<Token> $tokens */
    private function wrapOperators(Node $node, array $tokens, string $indent, string $newline): void
    {
        if (! $node instanceof BooleanAnd && ! $node instanceof BooleanOr) {
            return;
        }
        $this->wrapOperators($node->left, $tokens, $indent, $newline);
        $this->wrapOperators($node->right, $tokens, $indent, $newline);
        for ($position = $node->left->getEndTokenPos() + 1; $position < $node->right->getStartTokenPos(); $position++) {
            if (in_array($tokens[$position]->id, [T_BOOLEAN_AND, T_BOOLEAN_OR], true)) {
                $this->newlineAfter($tokens, $position, $indent, $newline);
                break;
            }
        }
    }

    /** @param array<Token> $tokens */
    private function newlineAfter(array $tokens, int $position, string $indent, string $newline): void
    {
        $tokens[$position]->text .= $newline.$indent;
        while (isset($tokens[++$position]) && $tokens[$position]->id === T_WHITESPACE) {
            $tokens[$position]->text = '';
        }
    }
}
