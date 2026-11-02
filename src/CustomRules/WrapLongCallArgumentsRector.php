<?php

declare(strict_types=1);

namespace Jcergolj\RectorForLaravel\CustomRules;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Token;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use RectorLaravel\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class WrapLongCallArgumentsRector extends AbstractRector implements ConfigurableRectorInterface
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
        return new RuleDefinition('Wrap long inline call argument lists with one argument per line', [
            new CodeSample(
                "\$this->assertSame(\$this->completedQuestionnaire->sectionQuestions->sectionOne->questionOne, \$apiRequestParams['section_1_question_1']);",
                "\$this->assertSame(\n    \$this->completedQuestionnaire->sectionQuestions->sectionOne->questionOne,\n    \$apiRequestParams['section_1_question_1'],\n);",
            ),
        ]);
    }

    /** @return array<class-string<Node>> */
    public function getNodeTypes(): array
    {
        return [FuncCall::class, MethodCall::class, NullsafeMethodCall::class, StaticCall::class, New_::class];
    }

    /** @param FuncCall|MethodCall|NullsafeMethodCall|StaticCall|New_ $node */
    public function refactor(Node $node): ?Node
    {
        if ($node->isFirstClassCallable() || count($node->getArgs()) < 2 || $node->getStartTokenPos() < 0) {
            return null;
        }
        $tokens = $this->getFile()->getOldTokens();
        $close = $node->getEndTokenPos();
        if ($tokens[$close]->id !== ord(')')) {
            // Anonymous-class bodies and constructors without parentheses.
            return null;
        }

        // Scan backwards to the call's own opening parenthesis, not its receiver's.
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
        } while ($open > $node->getStartTokenPos());

        if ($tokens[$open]->line !== $tokens[$close]->line || str_contains($tokens[$open]->text, "\n")) {
            return null;
        }

        [$prefix, $suffix] = $this->currentLine($tokens, $open);
        if (mb_strlen(str_replace("\t", '    ', rtrim($prefix.$suffix, "\r"))) <= $this->maxLineLength) {
            return null;
        }

        // Skipped-rule probes share token objects with the real file.
        if (method_exists($this->skipper, 'matchSkip') && $this->skipper->matchSkip($this, $this->getFile()->getFilePath()) !== null) {
            return $node;
        }

        $base = str_replace("\t", '    ', substr($prefix, 0, strspn($prefix, " \t")));
        $newline = str_contains($this->getFile()->getOriginalFileContent(), "\r\n") ? "\r\n" : "\n";
        $this->newlineAfter($tokens, $open, $base.'    ', $newline);
        $args = $node->getArgs();
        foreach ($args as $index => $arg) {
            $next = $args[$index + 1] ?? null;
            $limit = $next?->getStartTokenPos() ?? $close;
            $comma = null;
            for ($position = $arg->getEndTokenPos() + 1; $position < $limit; $position++) {
                if ($tokens[$position]->id === ord(',')) {
                    $comma = $position;
                    break;
                }
            }
            if ($next !== null && $comma !== null) {
                $this->newlineAfter($tokens, $comma, $base.'    ', $newline);
            } elseif ($next === null && $comma === null) {
                // Keep token indices stable for Rector's format-preserving printer.
                $tokens[$arg->getEndTokenPos()]->text .= ',';
            }
        }
        $tokens[$close]->text = $newline.$base.$tokens[$close]->text;
        for ($position = $close - 1; $tokens[$position]->id === T_WHITESPACE; $position--) {
            $tokens[$position]->text = '';
        }

        return $node;
    }

    /**
     * @param  array<Token>  $tokens
     * @return array{string, string}
     */
    private function currentLine(array $tokens, int $open): array
    {
        $prefix = '';
        for ($position = $open - 1; $position >= 0; $position--) {
            $prefix = $tokens[$position]->text.$prefix;
            if (str_contains($prefix, "\n")) {
                $prefix = substr($prefix, strrpos($prefix, "\n") + 1);
                break;
            }
        }
        $suffix = '';
        for ($position = $open; isset($tokens[$position]); $position++) {
            $suffix .= $tokens[$position]->text;
            if (str_contains($suffix, "\n")) {
                $suffix = substr($suffix, 0, strpos($suffix, "\n"));
                break;
            }
        }

        return [$prefix, $suffix];
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
