<?php

declare(strict_types=1);

namespace Jcergolj\RectorForLaravel\CustomRules;

use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\Use_;
use PhpParser\NodeFinder;
use PHPStan\Reflection\ClassReflection;
use Rector\Php80\NodeAnalyzer\PhpAttributeAnalyzer;
use Rector\PHPUnit\Enum\PHPUnitAttribute;
use Rector\PHPUnit\Enum\PHPUnitClassName;
use Rector\Reflection\ReflectionResolver;
use Rector\StaticTypeMapper\ValueObject\Type\FullyQualifiedObjectType;
use RectorLaravel\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

class RemovePhpUnitTestPrefixRector extends AbstractRector
{
    public function __construct(
        private readonly ReflectionResolver $reflectionResolver,
        private readonly PhpAttributeAnalyzer $phpAttributeAnalyzer,
    ) {}

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Replace PHPUnit test_ prefixes with the #[Test] attribute', [
            new CodeSample(
                <<<'CODE_SAMPLE'
class ExampleTest extends \PHPUnit\Framework\TestCase
{
    public function test_rejects_invalid_language(): void {}
}
CODE_SAMPLE
                ,
                <<<'CODE_SAMPLE'
use PHPUnit\Framework\Attributes\Test;

class ExampleTest extends \PHPUnit\Framework\TestCase
{
    #[Test]
    public function rejects_invalid_language(): void {}
}
CODE_SAMPLE
            ),
        ]);
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Class_::class];
    }

    /**
     * @param  Class_  $node
     */
    public function refactor(Node $node): ?Node
    {
        $class = $this->reflectionResolver->resolveClassReflection($node);
        if (! $class instanceof ClassReflection || ! $class->isSubclassOf(PHPUnitClassName::TEST_CASE)) {
            return null;
        }

        $methods = [];
        foreach ($node->getMethods() as $method) {
            $methods[strtolower($method->name->toString())] = true;
        }

        $changed = false;
        foreach ($node->getMethods() as $method) {
            $oldName = $method->name->toString();
            if (! $method->isPublic() || $method->isStatic() || $method->isAbstract() || ! str_starts_with($oldName, 'test_')) {
                continue;
            }

            $newName = substr($oldName, 5);
            if (! preg_match('/^[a-z_\x80-\xff][a-z0-9_\x80-\xff]*$/iD', $newName)
                || str_starts_with($newName, '__') || str_starts_with($newName, 'test_')) {
                continue;
            }

            if (isset($methods[strtolower($newName)]) || $class->hasNativeMethod($newName)
                || $this->hasInheritedMethod($class, $newName) || $this->hasInheritedMethod($class, $oldName)
                || $this->hasDependency($method) || $this->hasFileReference($oldName)) {
                continue;
            }

            if (! $this->phpAttributeAnalyzer->hasPhpAttribute($method, PHPUnitAttribute::TEST)) {
                array_unshift($method->attrGroups, new AttributeGroup([new Attribute($this->testAttributeName())]));
            }

            $method->name = new Identifier($newName);
            $changed = true;
        }

        return $changed ? $node : null;
    }

    private function hasInheritedMethod(ClassReflection $class, string $name): bool
    {
        foreach ([...$class->getParents(), ...$class->getInterfaces()] as $ancestor) {
            if ($ancestor->hasNativeMethod($name)) {
                return true;
            }
        }

        return false;
    }

    private function hasDependency(ClassMethod $method): bool
    {
        if (preg_match('/@depends\b/i', $method->getDocComment()?->getText() ?? '')) {
            return true;
        }

        foreach ($method->attrGroups as $group) {
            foreach ($group->attrs as $attribute) {
                if (str_starts_with(strtolower($attribute->name->toString()), 'phpunit\\framework\\attributes\\depends')) {
                    return true;
                }
            }
        }

        return false;
    }

    private function hasFileReference(string $name): bool
    {
        $docReference = '/@(?:depends\s+(?:(?:clone|shallowClone)\s+)?|dataProvider\s+)(?:[^\s*]+::)?'
            .preg_quote($name, '/').'(?![a-z0-9_\x80-\xff])/i';

        return (new NodeFinder)->findFirst($this->getFile()->getNewStmts(), static function (Node $node) use ($name, $docReference): bool {
            // Literal method names cover PHPUnit metadata, callable arrays and reflection.
            if ($node instanceof String_) {
                return strcasecmp($node->value, $name) === 0 || str_ends_with(strtolower($node->value), '::'.strtolower($name));
            }

            if (($node instanceof MethodCall || $node instanceof NullsafeMethodCall || $node instanceof StaticCall)
                && $node->name instanceof Identifier && strcasecmp($node->name->toString(), $name) === 0) {
                return true;
            }

            return (bool) preg_match($docReference, $node->getDocComment()?->getText() ?? '');
        }) !== null;
    }

    private function testAttributeName(): Name
    {
        $file = $this->getFile()->getFileNode();
        if ($file === null || ($file->isNamespaced() && $file->getNamespace() === null)) {
            return new FullyQualified(PHPUnitAttribute::TEST);
        }

        $shortNameTaken = false;
        foreach ($file->getUsesAndGroupUses() as $use) {
            $prefix = $use instanceof GroupUse ? $use->prefix->toString().'\\' : '';
            foreach ($use->uses as $item) {
                if (($item->type ?: $use->type) !== Use_::TYPE_NORMAL) {
                    continue;
                }

                $alias = $item->getAlias()->toString();
                if (strcasecmp($prefix.$item->name->toString(), PHPUnitAttribute::TEST) === 0) {
                    return new Name($alias);
                }

                $shortNameTaken = $shortNameTaken || strcasecmp($alias, 'Test') === 0;
            }
        }

        foreach (($file->getNamespace() ?? $file)->stmts as $statement) {
            if ($statement instanceof ClassLike && strcasecmp($statement->name?->toString() ?? '', 'Test') === 0) {
                $shortNameTaken = true;
            }
        }

        if ($shortNameTaken) {
            return new FullyQualified(PHPUnitAttribute::TEST);
        }

        $file->getPendingImports()->addUseImport(new FullyQualifiedObjectType(PHPUnitAttribute::TEST));

        return new Name('Test');
    }
}
