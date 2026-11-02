<?php

declare(strict_types=1);

namespace Jcergolj\RectorForLaravel\CustomRules;

use PhpParser\Node;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Token;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use RectorLaravel\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class WrapLongParameterListsRector extends AbstractRector implements ConfigurableRectorInterface
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
        return new RuleDefinition('Wrap long inline parameter lists with one parameter per line', [
            new CodeSample(
                'public function __construct(public readonly Team $team, public readonly array $pages, public readonly ?User $user = null, public readonly array $metadata = []) {}',
                "public function __construct(\n    public readonly Team \$team,\n    public readonly array \$pages,\n    public readonly ?User \$user = null,\n    public readonly array \$metadata = [],\n) {}",
            ),
        ]);
    }

    /** @return array<class-string<Node>> */
    public function getNodeTypes(): array
    {
        return [ClassMethod::class, Function_::class, Closure::class, ArrowFunction::class];
    }

    /** @param ClassMethod|Function_|Closure|ArrowFunction $node */
    public function refactor(Node $node): ?Node
    {
        if (count($node->getParams()) < 2 || $node->getStartTokenPos() < 0) {
            return null;
        }
        $tokens = $this->getFile()->getOldTokens();
        // Adding lines anywhere can change a later __LINE__ value as well.
        foreach ($tokens as $token) {
            if ($token->id === T_LINE) {
                return null;
            }
        }
        $open = $node->getStartTokenPos();
        // Attributes can contain parentheses too; find the declaration keyword first.
        while (! in_array($tokens[$open]->id, [T_FUNCTION, T_FN], true)) {
            $open++;
        }
        while ($tokens[$open]->id !== ord('(')) {
            $open++;
        }
        $close = $open;
        $depth = 0;
        do {
            if ($tokens[$close]->id === ord('(')) {
                $depth++;
            } elseif ($tokens[$close]->id === ord(')')) {
                $depth--;
            }
            if ($depth === 0) {
                break;
            }
            $close++;
        } while ($close <= $node->getEndTokenPos());

        $list = '';
        for ($position = $open; $position <= $close; $position++) {
            $list .= $tokens[$position]->text;
        }
        if (str_contains($list, "\n")) {
            return null;
        }

        $prefix = $this->currentLinePrefix($tokens, $open);
        // Long bodies, return types and closure captures are not parameter-list problems.
        if (mb_strlen(str_replace("\t", '    ', $prefix.$list)) <= $this->maxLineLength) {
            return null;
        }

        // Skipped-rule probes share token objects with the real file.
        if (method_exists($this->skipper, 'matchSkip') && $this->skipper->matchSkip($this, $this->getFile()->getFilePath()) !== null) {
            return $node;
        }

        $base = str_replace("\t", '    ', substr($prefix, 0, strspn($prefix, " \t")));
        $newline = str_contains($this->getFile()->getOriginalFileContent(), "\r\n") ? "\r\n" : "\n";
        $this->newlineAfter($tokens, $open, $base.'    ', $newline);
        $params = $node->getParams();
        foreach ($params as $index => $param) {
            $next = $params[$index + 1] ?? null;
            $limit = $next?->getStartTokenPos() ?? $close;
            $comma = null;
            for ($position = $param->getEndTokenPos() + 1; $position < $limit; $position++) {
                if ($tokens[$position]->id === ord(',')) {
                    $comma = $position;
                    break;
                }
            }
            if ($next !== null && $comma !== null) {
                $this->newlineAfter($tokens, $comma, $base.'    ', $newline);
            } elseif ($next === null && $comma === null) {
                // Keep token indices stable for Rector's format-preserving printer.
                $tokens[$param->getEndTokenPos()]->text .= ',';
            }
        }
        $tokens[$close]->text = $newline.$base.$tokens[$close]->text;
        for ($position = $close - 1; $tokens[$position]->id === T_WHITESPACE; $position--) {
            $tokens[$position]->text = '';
        }

        return $node;
    }

    /** @param array<Token> $tokens */
    private function currentLinePrefix(array $tokens, int $open): string
    {
        $prefix = '';
        for ($position = $open - 1; $position >= 0; $position--) {
            $prefix = $tokens[$position]->text.$prefix;
            if (str_contains($prefix, "\n")) {
                $prefix = substr($prefix, strrpos($prefix, "\n") + 1);
                break;
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
