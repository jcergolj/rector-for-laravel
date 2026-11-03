<?php

declare(strict_types=1);

namespace Jcergolj\RectorForLaravel\CustomRules;

use PhpParser\Node;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\NullsafePropertyFetch;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Identifier;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use RectorLaravel\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

class WrapLongChainsRector extends AbstractRector implements ConfigurableRectorInterface
{
    public const MAX_LINE_LENGTH = 'max_line_length';

    protected const CHAIN_KIND = 'all';

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
        if (static::CHAIN_KIND === 'properties') {
            return new RuleDefinition('Wrap long inline property-access chains', [
                new CodeSample(
                    "\$this->payload['section_1_question_1'] = \$this->completedQuestionnaire->sectionQuestions->sectionOne->questionOne;",
                    "\$this->payload['section_1_question_1'] = \$this->completedQuestionnaire\n    ->sectionQuestions\n    ->sectionOne\n    ->questionOne;",
                ),
            ]);
        }

        return new RuleDefinition('Wrap long method, property and mixed access chains', [
            new CodeSample(
                "Route::post('password/reset', [ResetPasswordController::class, 'reset'])->middleware('throttle:5,10')->name('password.update');",
                "Route::post('password/reset', [ResetPasswordController::class, 'reset'])\n    ->middleware('throttle:5,10')\n    ->name('password.update');",
            ),
        ]);
    }

    /** @return array<class-string<Node>> */
    public function getNodeTypes(): array
    {
        return match (static::CHAIN_KIND) {
            'methods' => [MethodCall::class, NullsafeMethodCall::class],
            'properties' => [PropertyFetch::class, NullsafePropertyFetch::class],
            default => [MethodCall::class, NullsafeMethodCall::class, PropertyFetch::class, NullsafePropertyFetch::class],
        };
    }

    public function refactor(Node $node): ?Node
    {
        $attribute = 'wrap_long_chain_receiver_'.static::CHAIN_KIND;
        if ($node->getAttribute($attribute, false)) {
            return null;
        }
        $links = [];
        $receiver = $node;
        while ($this->isChainLink($receiver)) {
            $links[] = $receiver;
            $receiver->setAttribute($attribute, true);
            $receiver = $receiver->var;
        }
        if ($node->getStartTokenPos() < 0 || $node->getStartLine() !== $node->getEndLine()) {
            return null;
        }
        $lines = explode("\n", $this->getFile()->getOriginalFileContent());
        $line = str_replace("\t", '    ', rtrim($lines[$node->getStartLine() - 1], "\r"));
        if (mb_strlen($line) <= $this->maxLineLength) {
            return null;
        }
        foreach ($links as $link) {
            if (($link instanceof CallLike && $link->isFirstClassCallable()) || (($link instanceof PropertyFetch || $link instanceof NullsafePropertyFetch) && ! $link->name instanceof Identifier)) {
                return null;
            }
        }
        if ($receiver instanceof CallLike && $receiver->isFirstClassCallable()) {
            return null;
        }
        if (static::CHAIN_KIND === 'properties' || ! $receiver instanceof CallLike) {
            array_pop($links);
        }
        if ($links === []) {
            return null;
        }
        if (isset($this->skipper) && method_exists($this->skipper, 'matchSkip') && $this->skipper->matchSkip($this, $this->getFile()->getFilePath()) !== null) {
            return $node;
        }

        $tokens = $this->getFile()->getOldTokens();
        $prefix = '';
        for ($position = $node->getStartTokenPos() - 1; $position >= 0; $position--) {
            $prefix = $tokens[$position]->text.$prefix;
            if (str_contains($prefix, "\n")) {
                $prefix = substr($prefix, strrpos($prefix, "\n") + 1);
                break;
            }
        }
        $indent = str_replace("\t", '    ', substr($prefix, 0, strspn($prefix, " \t"))).'    ';
        $newline = str_contains($this->getFile()->getOriginalFileContent(), "\r\n") ? "\r\n" : "\n";
        foreach ($links as $link) {
            for ($position = $link->var->getEndTokenPos() + 1; $position < $link->name->getStartTokenPos(); $position++) {
                if (! in_array($tokens[$position]->id, [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR], true)) {
                    continue;
                }
                if (! str_contains($tokens[$position]->text, "\n")) {
                    $tokens[$position]->text = $newline.$indent.$tokens[$position]->text;
                    if ($link instanceof MethodCall || $link instanceof NullsafeMethodCall) {
                        for ($argument = $link->name->getEndTokenPos() + 1; $argument <= $link->getEndTokenPos(); $argument++) {
                            $tokens[$argument]->text = str_replace("\n", "\n    ", $tokens[$argument]->text);
                        }
                    }
                    while ($position > 0 && $tokens[--$position]->id === T_WHITESPACE) {
                        $tokens[$position]->text = '';
                    }
                }
                break;
            }
        }

        return $node;
    }

    private function isChainLink(Node $node): bool
    {
        return match (static::CHAIN_KIND) {
            'methods' => $node instanceof MethodCall || $node instanceof NullsafeMethodCall,
            'properties' => $node instanceof PropertyFetch || $node instanceof NullsafePropertyFetch,
            default => $node instanceof MethodCall || $node instanceof NullsafeMethodCall || $node instanceof PropertyFetch || $node instanceof NullsafePropertyFetch,
        };
    }
}
