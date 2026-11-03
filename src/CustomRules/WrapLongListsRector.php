<?php

declare(strict_types=1);

namespace Jcergolj\RectorForLaravel\CustomRules;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Token;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\PhpParser\Node\FileNode;
use RectorLaravel\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class WrapLongListsRector extends AbstractRector implements ConfigurableRectorInterface
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
        return new RuleDefinition('Wrap long arrays, call arguments and parameter lists with one item per line', [
            new CodeSample(
                "\$this->assertSame(\$this->completedQuestionnaire->sectionQuestions->sectionOne->questionOne, \$apiRequestParams['section_1_question_1']);",
                "\$this->assertSame(\n    \$this->completedQuestionnaire->sectionQuestions->sectionOne->questionOne,\n    \$apiRequestParams['section_1_question_1'],\n);",
            ),
        ]);
    }

    /** @return array<class-string<Node>> */
    public function getNodeTypes(): array
    {
        return [FileNode::class];
    }

    public function refactor(Node $node): ?Node
    {
        $tokens = $this->getFile()->getOldTokens();
        $hasLine = false;
        foreach ($tokens as $token) {
            if ($token->id === T_LINE) {
                $hasLine = true;
                break;
            }
        }
        $lists = [];
        $this->collectLists($node, $tokens, $hasLine, $lists);
        if ($lists === []) {
            return null;
        }
        if (isset($this->skipper) && method_exists($this->skipper, 'matchSkip') && $this->skipper->matchSkip($this, $this->getFile()->getFilePath()) !== null) {
            return $node;
        }

        // Outer lists first: nested lists read the indentation introduced by their parents.
        usort($lists, static fn (array $a, array $b): int => $a[0] <=> $b[0]);
        $newline = str_contains($this->getFile()->getOriginalFileContent(), "\r\n") ? "\r\n" : "\n";
        foreach ($lists as [$open, $close, $items]) {
            if (str_contains($tokens[$open]->text, "\n")) {
                continue;
            }
            $prefix = $this->linePrefix($tokens, $open);
            $base = str_replace("\t", '    ', substr($prefix, 0, strspn($prefix, " \t")));
            $this->newlineAfter($tokens, $open, $base.'    ', $newline);
            foreach ($items as $index => $item) {
                $next = $items[$index + 1] ?? null;
                $limit = $next?->getStartTokenPos() ?? $close;
                $comma = null;
                for ($position = $item->getEndTokenPos() + 1; $position < $limit; $position++) {
                    if ($tokens[$position]->id === ord(',')) {
                        $comma = $position;
                        break;
                    }
                }
                if ($next !== null && $comma !== null) {
                    $this->newlineAfter($tokens, $comma, $base.'    ', $newline);
                } elseif ($next === null && $comma === null) {
                    $tokens[$item->getEndTokenPos()]->text .= ',';
                }
            }
            $tokens[$close]->text = $newline.$base.$tokens[$close]->text;
            for ($position = $close - 1; $tokens[$position]->id === T_WHITESPACE; $position--) {
                $tokens[$position]->text = '';
            }
        }

        return $node;
    }

    /**
     * @param  array<Token>  $tokens
     * @param  array<array{int, int, array<Node>}>  $lists
     */
    private function collectLists(Node $node, array $tokens, bool $hasLine, array &$lists): void
    {
        foreach ($node->getSubNodeNames() as $name) {
            foreach (is_array($node->$name) ? $node->$name : [$node->$name] as $child) {
                if ($child instanceof Node) {
                    $this->collectLists($child, $tokens, $hasLine, $lists);
                }
            }
        }

        $parameters = $node instanceof ClassMethod || $node instanceof Function_ || $node instanceof Closure || $node instanceof ArrowFunction;
        $items = $parameters ? $node->getParams() : ($node instanceof Array_ ? $node->items : ($node instanceof CallLike ? $node->getRawArgs() : []));
        if (count($items) < 2 || in_array(null, $items, true) || $node->getStartTokenPos() < 0 || ($parameters && $hasLine)) {
            return;
        }
        if ($node instanceof CallLike && $node->isFirstClassCallable()) {
            return;
        }
        $open = $node->getStartTokenPos();
        $close = $node->getEndTokenPos();
        if ($parameters) {
            while (! in_array($tokens[$open]->id, [T_FUNCTION, T_FN], true)) {
                $open++;
            }
            while ($tokens[$open]->id !== ord('(')) {
                $open++;
            }
            $close = $open;
            $depth = 0;
            do {
                $depth += ($tokens[$close]->id === ord('(') ? 1 : 0) - ($tokens[$close]->id === ord(')') ? 1 : 0);
                if ($depth === 0) {
                    break;
                }
                $close++;
            } while ($close <= $node->getEndTokenPos());
        } elseif ($node instanceof Array_) {
            if ($tokens[$open]->id === T_ARRAY) {
                while ($tokens[$open]->id !== ord('(')) {
                    $open++;
                }
            }
        } else {
            if ($tokens[$close]->id !== ord(')')) {
                return;
            }
            $open = $close;
            $depth = 0;
            do {
                $depth += ($tokens[$open]->id === ord(')') ? 1 : 0) - ($tokens[$open]->id === ord('(') ? 1 : 0);
                if ($depth === 0) {
                    break;
                }
                $open--;
            } while ($open > $node->getStartTokenPos());
        }
        $list = '';
        for ($position = $open; $position <= $close; $position++) {
            $list .= $tokens[$position]->text;
        }
        if (str_contains($list, "\n")) {
            return;
        }
        $line = $this->linePrefix($tokens, $open).$list;
        if (! $parameters) {
            for ($position = $close + 1; isset($tokens[$position]); $position++) {
                $text = $tokens[$position]->text;
                $line .= explode("\n", $text, 2)[0];
                if (str_contains($text, "\n")) {
                    break;
                }
            }
        }
        if (mb_strlen(str_replace("\t", '    ', rtrim($line, "\r"))) <= $this->maxLineLength) {
            return;
        }
        $lists[] = [$open, $close, $items];
    }

    /** @param array<Token> $tokens */
    private function linePrefix(array $tokens, int $open): string
    {
        $prefix = '';
        for ($position = $open - 1; $position >= 0; $position--) {
            $prefix = $tokens[$position]->text.$prefix;
            if (str_contains($prefix, "\n")) {
                return substr($prefix, strrpos($prefix, "\n") + 1);
            }
        }

        return $prefix;
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
