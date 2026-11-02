<?php

declare(strict_types=1);

namespace Jcergolj\RectorForLaravel\CustomRules;

use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp\Concat;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use RectorLaravel\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class WrapLongConcatenationsRector extends AbstractRector implements ConfigurableRectorInterface
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
        return new RuleDefinition('Wrap existing long concatenations with leading continuation dots', [
            new CodeSample(
                "\$attribute = trans('validation.attributes.email').trans('dashboard.on_the').trans('allocated-tokens.create.step_1').trans('dashboard.tab');",
                "\$attribute = trans('validation.attributes.email')\n    .trans('dashboard.on_the')\n    .trans('allocated-tokens.create.step_1')\n    .trans('dashboard.tab');",
            ),
        ]);
    }

    /** @return array<class-string<Node>> */
    public function getNodeTypes(): array
    {
        return [Concat::class];
    }

    /** @param Concat $node */
    public function refactor(Node $node): ?Node
    {
        if ($node->getAttribute('wrap_long_concatenation_child', false)) {
            return null;
        }

        // Decide once for the complete expression, including parenthesized groups.
        // Concatenations nested inside calls remain independent expressions.
        $this->markChildren($node);
        if ($node->getStartTokenPos() < 0 || $node->getStartLine() !== $node->getEndLine()) {
            return null;
        }
        $lines = explode("\n", $this->getFile()->getOriginalFileContent());
        $line = str_replace("\t", '    ', rtrim($lines[$node->getStartLine() - 1], "\r"));
        if (mb_strlen($line) <= $this->maxLineLength) {
            return null;
        }

        // Skipped-rule probes share token objects with the real file.
        if (method_exists($this->skipper, 'matchSkip') && $this->skipper->matchSkip($this, $this->getFile()->getFilePath()) !== null) {
            return $node;
        }

        $tokens = $this->getFile()->getOldTokens();
        $newline = str_contains($this->getFile()->getOriginalFileContent(), "\r\n") ? "\r\n" : "\n";
        // Earlier rules may already have indented an enclosing array or call.
        $prefix = '';
        for ($position = $node->getStartTokenPos() - 1; $position >= 0; $position--) {
            $prefix = $tokens[$position]->text.$prefix;
            if (str_contains($prefix, "\n")) {
                $prefix = substr($prefix, strrpos($prefix, "\n") + 1);
                break;
            }
        }
        $indent = str_replace("\t", '    ', substr($prefix, 0, strspn($prefix, " \t"))).'    ';
        foreach ($this->operatorPositions($node) as $position) {
            $tokens[$position]->text = $newline.$indent.$tokens[$position]->text;
            while ($position > 0 && $tokens[--$position]->id === T_WHITESPACE) {
                $tokens[$position]->text = '';
            }
        }

        return $node;
    }

    private function markChildren(Concat $node): void
    {
        foreach ([$node->left, $node->right] as $child) {
            if ($child instanceof Concat) {
                $child->setAttribute('wrap_long_concatenation_child', true);
                $this->markChildren($child);
            }
        }
    }

    /** @return list<int> */
    private function operatorPositions(Concat $node): array
    {
        $positions = [];
        foreach ([$node->left, $node->right] as $operand) {
            if ($operand instanceof Concat) {
                array_push($positions, ...$this->operatorPositions($operand));
            }
        }
        $tokens = $this->getFile()->getOldTokens();
        for ($position = $node->left->getEndTokenPos() + 1; $position < $node->right->getStartTokenPos(); $position++) {
            if ($tokens[$position]->id === ord('.')) {
                $positions[] = $position;
                break;
            }
        }

        return $positions;
    }
}
