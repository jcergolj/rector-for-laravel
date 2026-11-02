<?php

declare(strict_types=1);

namespace Jcergolj\RectorForLaravel\CustomRules;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Token;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\PhpParser\Node\FileNode;
use RectorLaravel\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class WrapLongArraysRector extends AbstractRector implements ConfigurableRectorInterface
{
    public const MAX_LINE_LENGTH = 'max_line_length';

    private int $maxLineLength = 120;

    public function configure(array $configuration): void
    {
        $length = $configuration[self::MAX_LINE_LENGTH] ?? 120;
        if (! is_int($length) || $length < 1) {
            throw new \InvalidArgumentException('max_line_length must be a positive integer.');
        }

        $this->maxLineLength = $length;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Wrap inline arrays on overlong lines, including enclosing multi-argument calls', [
            new CodeSample(
                "\$payload = ['email' => 'joe.doe@example.com', 'first_name' => 'joe', 'last_name' => 'doe', 'role' => 'buyer', 'active' => true];",
                "\$payload = [\n    'email' => 'joe.doe@example.com',\n    'first_name' => 'joe',\n    'last_name' => 'doe',\n    'role' => 'buyer',\n    'active' => true,\n];",
            ),
        ]);
    }

    /** @return array<class-string<Node>> */
    public function getNodeTypes(): array
    {
        return [FileNode::class];
    }

    /** @param FileNode $node */
    public function refactor(Node $node): ?Node
    {
        $tokens = $this->getFile()->getOldTokens();
        $lines = explode("\n", $this->getFile()->getOriginalFileContent());
        $lists = [];
        $this->collectLists($node, $lines, $lists);

        if ($lists === []) {
            return null;
        }

        // Rector probes skipped rules on a clone. Do not mutate shared printer tokens in a probe.
        if (method_exists($this->skipper, 'matchSkip') && $this->skipper->matchSkip($this, $this->getFile()->getFilePath()) !== null) {
            return $node;
        }

        foreach ($lists as [$list, $items]) {
            $close = $list->getEndTokenPos();
            if ($list instanceof Array_) {
                $open = $list->getStartTokenPos();
                if ($tokens[$open]->id === T_ARRAY) {
                    do {
                        $open++;
                    } while ($tokens[$open]->id !== ord('('));
                }
            } else {
                // Find the call's own parentheses, not parentheses inside an argument or receiver.
                $open = $close;
                $depth = 0;
                do {
                    if ($tokens[$open]->id === ord(')')) {
                        $depth++;
                    } elseif ($tokens[$open]->id === ord('(')) {
                        $depth--;
                    }
                    if ($depth === 0) {
                        break;
                    }
                    $open--;
                } while ($open > $list->getStartTokenPos());
            }

            $line = $lines[$list->getStartLine() - 1];
            $indent = strspn($line, " \t");
            $base = str_replace("\t", '    ', substr($line, 0, $indent));
            foreach ($lists as [$parent]) {
                if ($parent !== $list && $parent->getStartTokenPos() <= $open && $parent->getEndTokenPos() > $close) {
                    $base .= '    ';
                }
            }

            $this->newlineAfter($tokens, $open, $base.'    ');
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
                    $this->newlineAfter($tokens, $comma, $base.'    ');
                } elseif ($next === null) {
                    // Keep every original token index stable for format-preserving printing.
                    if ($comma === null) {
                        $tokens[$item->getEndTokenPos()]->text .= ',';
                    }
                    $this->newlineAfter($tokens, $comma ?? $item->getEndTokenPos(), $base);
                }
            }
        }

        return $node;
    }

    /**
     * @param  array<int, string>  $lines
     * @param  array<array{Array_|CallLike, array<Node>}>  $lists
     */
    private function collectLists(Node $node, array $lines, array &$lists): bool
    {
        $containsArray = false;
        foreach ($node->getSubNodeNames() as $name) {
            $children = is_array($node->$name) ? $node->$name : [$node->$name];
            foreach ($children as $child) {
                if ($child instanceof Node) {
                    $found = $this->collectLists($child, $lines, $lists);
                    // Arrays in a call's receiver do not belong to its argument list.
                    if (! $node instanceof CallLike || $name === 'args') {
                        $containsArray = $found || $containsArray;
                    }
                }
            }
        }

        $items = $node instanceof Array_ ? $node->items : ($node instanceof CallLike ? $node->getRawArgs() : []);
        if (count($items) < 2 || in_array(null, $items, true) || $node->getStartLine() !== $node->getEndLine()
            || $node->getStartTokenPos() < 0) {
            return $containsArray;
        }

        $line = str_replace("\t", '    ', rtrim($lines[$node->getStartLine() - 1], "\r"));
        if (mb_strlen($line) <= $this->maxLineLength || (! $node instanceof Array_ && ! $containsArray)) {
            return $containsArray;
        }

        // Anonymous-class constructors and other unsupported call-like syntax are left alone.
        $tokens = $this->getFile()->getOldTokens();
        if ($node instanceof CallLike && $tokens[$node->getEndTokenPos()]->id !== ord(')')) {
            return $containsArray;
        }

        $lists[] = [$node, $items];

        return true;
    }

    /** @param array<Token> $tokens */
    private function newlineAfter(array $tokens, int $position, string $indent): void
    {
        $newline = str_contains($this->getFile()->getOriginalFileContent(), "\r\n") ? "\r\n" : "\n";
        $tokens[$position]->text .= $newline.$indent;
        while (isset($tokens[++$position]) && $tokens[$position]->id === T_WHITESPACE) {
            $tokens[$position]->text = '';
        }
    }
}
