<?php

declare(strict_types=1);

namespace Jcergolj\RectorForLaravel\CustomRules;

use PhpParser\Node;
use PhpParser\Node\Expr\NullsafePropertyFetch;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Identifier;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use RectorLaravel\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class WrapLongPropertyAccessChainsRector extends AbstractRector implements ConfigurableRectorInterface
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
        return new RuleDefinition('Wrap inline property-access chains on overlong lines', [
            new CodeSample(
                "\$this->payload['section_1_question_1'] = \$this->completedQuestionnaire->sectionQuestions->sectionOne->questionOne;",
                "\$this->payload['section_1_question_1'] = \$this->completedQuestionnaire\n    ->sectionQuestions\n    ->sectionOne\n    ->questionOne;",
            ),
        ]);
    }

    /** @return array<class-string<Node>> */
    public function getNodeTypes(): array
    {
        return [PropertyFetch::class, NullsafePropertyFetch::class];
    }

    /** @param PropertyFetch|NullsafePropertyFetch $node */
    public function refactor(Node $node): ?Node
    {
        if ($node->getAttribute('wrap_long_property_chain_receiver', false)) {
            return null;
        }

        // Decide once for the complete contiguous property chain, including no-ops.
        $properties = [];
        $property = $node;
        while ($property instanceof PropertyFetch || $property instanceof NullsafePropertyFetch) {
            $properties[] = $property;
            $property->setAttribute('wrap_long_property_chain_receiver', true);
            $property = $property->var;
        }

        if ($node->getStartTokenPos() < 0 || $node->getStartLine() !== $node->getEndLine()) {
            return null;
        }
        $lines = explode("\n", $this->getFile()->getOriginalFileContent());
        $line = str_replace("\t", '    ', rtrim($lines[$node->getStartLine() - 1], "\r"));
        if (mb_strlen($line) <= $this->maxLineLength) {
            return null;
        }
        foreach ($properties as $property) {
            if (! $property->name instanceof Identifier) {
                return null;
            }
        }

        // Keep the first property beside its receiver; single fetches are indivisible.
        array_pop($properties);
        if ($properties === []) {
            return null;
        }
        // Skipped-rule probes share token objects with the real file.
        if (method_exists($this->skipper, 'matchSkip') && $this->skipper->matchSkip($this, $this->getFile()->getFilePath()) !== null) {
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
        foreach ($properties as $property) {
            for ($position = $property->var->getEndTokenPos() + 1; $position < $property->name->getStartTokenPos(); $position++) {
                if (in_array($tokens[$position]->id, [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR], true)) {
                    if (! str_contains($tokens[$position]->text, "\n")) {
                        $tokens[$position]->text = $newline.$indent.$tokens[$position]->text;
                        while ($position > 0 && $tokens[--$position]->id === T_WHITESPACE) {
                            $tokens[$position]->text = '';
                        }
                    }
                    break;
                }
            }
        }

        return $node;
    }
}
