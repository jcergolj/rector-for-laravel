<?php

declare(strict_types=1);

namespace Jcergolj\RectorForLaravel\CustomRules;

use PhpParser\Node;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use RectorLaravel\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class WrapLongFluentChainsRector extends AbstractRector implements ConfigurableRectorInterface
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
        return new RuleDefinition('Wrap inline fluent call chains on overlong lines', [
            new CodeSample(
                "Route::post('password/reset', [ResetPasswordController::class, 'reset'])->middleware('throttle:5,10')->name('password.update');",
                "Route::post('password/reset', [ResetPasswordController::class, 'reset'])\n    ->middleware('throttle:5,10')\n    ->name('password.update');",
            ),
        ]);
    }

    public function getNodeTypes(): array
    {
        return [MethodCall::class, NullsafeMethodCall::class];
    }

    /** @param MethodCall|NullsafeMethodCall $node */
    public function refactor(Node $node): ?Node
    {
        if ($node->getAttribute('wrap_long_fluent_chain_receiver', false)) {
            return null;
        }

        // Decide once for the complete chain, not again for each shorter receiver.
        // Argument subexpressions remain independent chains.
        $receiver = $node->var;
        while ($receiver instanceof MethodCall || $receiver instanceof NullsafeMethodCall) {
            $receiver->setAttribute('wrap_long_fluent_chain_receiver', true);
            $receiver = $receiver->var;
        }

        $lines = explode("\n", $this->getFile()->getOriginalFileContent());
        $breaks = [];
        $this->collectChain($node, $lines, $breaks);
        if ($breaks === []) {
            return null;
        }

        // Skipped-rule probes share token objects with the real file.
        if (method_exists($this->skipper, 'matchSkip') && $this->skipper->matchSkip($this, $this->getFile()->getFilePath()) !== null) {
            return $node;
        }

        $tokens = $this->getFile()->getOldTokens();
        $newline = str_contains($this->getFile()->getOriginalFileContent(), "\r\n") ? "\r\n" : "\n";
        foreach ($breaks as $position => $indent) {
            $tokens[$position]->text = $newline.$indent.$tokens[$position]->text;
            while ($position > 0 && $tokens[--$position]->id === T_WHITESPACE) {
                $tokens[$position]->text = '';
            }
        }

        return $node;
    }

    /**
     * @param  array<int, string>  $lines
     * @param  array<int, string>  $breaks
     */
    private function collectChain(MethodCall|NullsafeMethodCall $chain, array $lines, array &$breaks): void
    {
        if ($chain->getStartTokenPos() < 0 || $chain->getStartLine() !== $chain->getEndLine()) {
            return;
        }

        $line = str_replace("\t", '    ', rtrim($lines[$chain->getStartLine() - 1], "\r"));
        if (mb_strlen($line) <= $this->maxLineLength) {
            return;
        }

        // An enclosing chain or another formatting rule may already have wrapped
        // these shared tokens without changing the original AST line numbers.
        $tokens = $this->getFile()->getOldTokens();
        for ($position = $chain->getStartTokenPos(); $position <= $chain->getEndTokenPos(); $position++) {
            if (str_contains($tokens[$position]->text, "\n")) {
                return;
            }
        }

        $calls = [];
        $call = $chain;
        while ($call instanceof MethodCall || $call instanceof NullsafeMethodCall) {
            if ($call->isFirstClassCallable()) {
                return;
            }
            $calls[] = $call;
            $call = $call->var;
        }

        if ($call instanceof CallLike && $call->isFirstClassCallable()) {
            return;
        }
        if (! $call instanceof CallLike) {
            // Keep the first method call beside a variable or property receiver.
            array_pop($calls);
        }
        if ($calls === []) {
            return;
        }

        $indent = substr($line, 0, strspn($line, ' ')).'    ';
        $tokens = $this->getFile()->getOldTokens();
        foreach ($calls as $method) {
            for ($position = $method->var->getEndTokenPos() + 1; $position < $method->name->getStartTokenPos(); $position++) {
                if (in_array($tokens[$position]->id, [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR], true)) {
                    $breaks[$position] = $indent;
                    break;
                }
            }
        }
    }
}
