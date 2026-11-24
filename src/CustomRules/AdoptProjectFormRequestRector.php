<?php

declare(strict_types=1);

namespace Jcergolj\RectorForLaravel\CustomRules;

use Illuminate\Foundation\Http\FormRequest;
use PhpParser\Node;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Nop;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\Stmt\TraitUse;
use PhpParser\Node\Stmt\Use_;
use PhpParser\NodeFinder;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\PhpParser\Node\FileNode;
use Rector\PhpParser\Parser\RectorParser;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class AdoptProjectFormRequestRector extends AbstractRector implements ConfigurableRectorInterface
{
    public const string BASE_CLASS = 'base_class';

    private const array DYNAMIC_VALIDATION_HOOKS = ['rules', 'validator', 'withvalidator', 'after'];

    private ?string $baseClass = null;

    public function __construct(
        private readonly ReflectionProvider $classReflectionProvider,
        private readonly RectorParser $rectorParser,
    ) {}

    /** @param array{base_class?: string} $configuration */
    public function configure(array $configuration): void
    {
        $baseClass = $configuration[self::BASE_CLASS] ?? null;

        if (! is_string($baseClass) || ltrim($baseClass, '\\') === '') {
            throw new \InvalidArgumentException('Configure base_class with the fully qualified project FormRequest class name.');
        }

        $this->baseClass = ltrim($baseClass, '\\');
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Adopt a configured project FormRequest with equivalent authorization and validation behavior', [
            new ConfiguredCodeSample(
                'class StoreRequest extends \Illuminate\Foundation\Http\FormRequest { public function authorize(): bool { return true; } }',
                'class StoreRequest extends \App\Http\Requests\AppFormRequest {}',
                [self::BASE_CLASS => 'App\Http\Requests\AppFormRequest'],
            ),
        ]);
    }

    /** @return array<class-string<Node>> */
    public function getNodeTypes(): array
    {
        return [Class_::class];
    }

    /** @param Class_ $node */
    public function refactor(Node $node): ?Node
    {
        $className = $this->getName($node);
        if ($this->baseClass === null || $className === null || strcasecmp($className, $this->baseClass) === 0
            || ! $this->directlyExtendsFormRequest($node)
            || ! $this->classReflectionProvider->hasClass($className)
            || ! $this->classReflectionProvider->hasClass(FormRequest::class)
            || $this->hasImportConflict($node, $this->baseClass)) {
            return null;
        }

        $authorize = $node->getMethod('authorize');
        if (! $this->isPermissiveAuthorize($authorize)) {
            return null;
        }

        $frameworkRequest = $this->classReflectionProvider->getClass(FormRequest::class);
        $helpers = $this->resolveBaseHelpers($this->baseClass, $frameworkRequest);

        if ($helpers === null || $this->mayDependOnParentChange($node, $frameworkRequest, $helpers)) {
            return null;
        }

        $node->extends = new FullyQualified($this->baseClass);
        $node->stmts = array_values(array_filter($node->stmts, static fn (Node $stmt): bool => $stmt !== $authorize));

        return $node;
    }

    private function directlyExtendsFormRequest(Class_ $class): bool
    {
        return $class->extends instanceof Name && $this->isName($class->extends, FormRequest::class);
    }

    /** @return list<string>|null Names of additional helpers, or null if the base cannot be verified. */
    private function resolveBaseHelpers(string $baseClass, ClassReflection $frameworkRequest): ?array
    {
        if (! $this->classReflectionProvider->hasClass($baseClass)) {
            return null;
        }

        $fileName = $this->classReflectionProvider->getClass($baseClass)->getFileName();
        if ($fileName === null || ! is_file($fileName)) {
            return null;
        }

        foreach ($this->rectorParser->parseFile($fileName) as $stmt) {
            foreach ($stmt instanceof Namespace_ ? $stmt->stmts : [$stmt] as $declaration) {
                if (! $declaration instanceof Class_ || ! $this->isName($declaration, $baseClass)) {
                    continue;
                }

                return $this->collectBaseHelpers($declaration, $frameworkRequest);
            }
        }

        return null;
    }

    /** @return list<string>|null */
    private function collectBaseHelpers(Class_ $base, ClassReflection $frameworkRequest): ?array
    {
        $authorize = $base->getMethod('authorize');
        if ($base->isFinal() || $base->isReadonly() || $base->attrGroups !== [] || $base->implements !== []
            || ! $this->directlyExtendsFormRequest($base) || ! $this->isPermissiveAuthorize($authorize)) {
            return null;
        }

        $helpers = [];
        foreach ($base->stmts as $member) {
            if ($member === $authorize || $member instanceof Nop) {
                continue;
            }

            if (! $member instanceof ClassMethod || $member->isPublic() || $member->isAbstract()
                || $member->isFinal() || $member->attrGroups !== []) {
                return null;
            }

            $name = strtolower($member->name->toString());
            // These Laravel hooks are detected by name, not declared on FormRequest.
            if (str_starts_with($name, '__') || in_array($name, self::DYNAMIC_VALIDATION_HOOKS, true)
                || $frameworkRequest->hasNativeMethod($name)) {
                return null;
            }

            $helpers[] = $name;
        }

        return $helpers;
    }

    /** @param list<string> $helpers */
    private function mayDependOnParentChange(Class_ $class, ClassReflection $frameworkRequest, array $helpers): bool
    {
        $finder = new NodeFinder;
        $membersAndAttributes = [...$class->stmts, ...$class->attrGroups];
        $unchangedParentCalls = [];
        foreach ($finder->findInstanceOf($membersAndAttributes, StaticCall::class) as $call) {
            if ($call->class instanceof Name && strtolower($call->class->toString()) === 'parent'
                && $call->name instanceof Identifier && strtolower($call->name->toString()) !== 'authorize'
                && $frameworkRequest->hasNativeMethod($call->name->toString())) {
                $unchangedParentCalls[spl_object_id($call->class)] = true;
            }
        }

        return $finder->findFirst($membersAndAttributes, static function (Node $node) use ($helpers, $unchangedParentCalls): bool {
            if ($node instanceof TraitUse || ($node instanceof Name && strtolower($node->toString()) === 'parent'
                && ! isset($unchangedParentCalls[spl_object_id($node)]))) {
                return true;
            }

            if ($helpers === []) {
                return false;
            }

            if ($node instanceof Identifier || $node instanceof String_) {
                $value = $node instanceof Identifier ? $node->toString() : $node->value;

                return in_array(strtolower($value), $helpers, true);
            }

            return ($node instanceof MethodCall || $node instanceof NullsafeMethodCall || $node instanceof StaticCall)
                && ! $node->name instanceof Identifier;
        }) !== null;
    }

    private function isPermissiveAuthorize(?ClassMethod $method): bool
    {
        if ($method === null || ! $method->isPublic() || $method->isStatic() || $method->isFinal() || $method->byRef
            || $method->attrGroups !== [] || $method->params !== []
            || ! $method->returnType instanceof Identifier || strtolower($method->returnType->toString()) !== 'bool'
            || count($method->stmts ?? []) !== 1) {
            return false;
        }

        $return = $method->stmts[0];

        return $return instanceof Return_
            && $return->expr instanceof ConstFetch
            && strtolower($return->expr->name->toString()) === 'true';
    }

    private function hasImportConflict(Class_ $class, string $baseClass): bool
    {
        $shortName = (new FullyQualified($baseClass))->getLast();
        $fileNode = $this->getFile()->getFileNode();
        if (! $fileNode instanceof FileNode) {
            return true;
        }

        foreach ($fileNode->stmts as $stmt) {
            if ($stmt instanceof Namespace_ && ($class->getStartFilePos() < $stmt->getStartFilePos()
                || $class->getEndFilePos() > $stmt->getEndFilePos())) {
                continue;
            }

            foreach ($stmt instanceof Namespace_ ? $stmt->stmts : [$stmt] as $import) {
                if (! $import instanceof Use_ && ! $import instanceof GroupUse) {
                    continue;
                }

                foreach ($import->uses as $use) {
                    if (($use->type | $import->type) !== Use_::TYPE_NORMAL || strcasecmp($use->getAlias()->toString(), $shortName) !== 0) {
                        continue;
                    }

                    $importedName = ($import instanceof GroupUse ? $import->prefix->toString().'\\' : '').$use->name->toString();
                    if (strcasecmp($importedName, $baseClass) !== 0) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
