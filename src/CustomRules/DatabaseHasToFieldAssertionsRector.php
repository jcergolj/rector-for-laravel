<?php

declare(strict_types=1);

namespace Jcergolj\RectorForLaravel\CustomRules;

use Illuminate\Foundation\Testing\TestCase;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Nop;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\NodeTypeResolver\Node\AttributeKey;
use RectorLaravel\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class DatabaseHasToFieldAssertionsRector extends AbstractRector implements ConfigurableRectorInterface
{
    /** @var array<string, array{model: class-string, uniqueKey: string, columns: array<string, 'int'|'string'>}> */
    private array $tables = [];

    public function configure(array $configuration): void
    {
        $this->tables = $configuration;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Look up an explicitly mapped database row by its unique key and assert each field separately', [
            new ConfiguredCodeSample(
                '$this->assertDatabaseHas(\'users\', [\'id\' => 42]);',
                <<<'PHP'
                $persistedRow = $this->getConnection(null, 'users')->table('users')->where('id', 42)->first();

                $this->assertNotNull($persistedRow, 'Expected a row in users for id.');

                $this->assertSame(42, $persistedRow->id, 'id');
                PHP,
                ['users' => ['model' => 'App\Models\User', 'uniqueKey' => 'id', 'columns' => ['id' => 'int']]],
            ),
        ]);
    }

    /** @return array<class-string<Node>> */
    public function getNodeTypes(): array
    {
        return [ClassMethod::class];
    }

    /** @param ClassMethod $node */
    public function refactor(Node $node): ?Node
    {
        $scope = $node->getAttribute(AttributeKey::SCOPE);
        $class = $scope instanceof Scope ? $scope->getClassReflection() : null;
        if ($class === null || ! $class->is(TestCase::class)) {
            return null;
        }

        foreach (['assertDatabaseHas', 'getTable'] as $method) {
            if (! $class->hasNativeMethod($method) || $class->getNativeMethod($method)->getDeclaringClass()->getName() !== TestCase::class) {
                return null;
            }
        }

        $names = [];
        foreach ((new NodeFinder)->findInstanceOf($node, Variable::class) as $variable) {
            if (! is_string($variable->name)) {
                return null;
            }

            $names[$variable->name] = true;
        }

        $statements = [];
        $changed = false;
        foreach ($node->stmts ?? [] as $statement) {
            $name = 'persistedRow';
            for ($suffix = 2; isset($names[$name]); $suffix++) {
                $name = 'persistedRow'.$suffix;
            }

            $replacement = $statement instanceof Expression ? $this->rewrite($statement, $name) : null;
            if ($replacement === null) {
                $statements[] = $statement;

                continue;
            }

            $names[$name] = true;
            $changed = true;
            array_push($statements, ...$replacement);
        }

        if (! $changed) {
            return null;
        }

        $node->stmts = $statements;

        return $node;
    }

    /** @return list<Expression|Nop>|null */
    private function rewrite(Expression $statement, string $name): ?array
    {
        $call = $statement->expr;
        if (! $call instanceof MethodCall || ! $call->name instanceof Identifier || ! $this->isName($call->name, 'assertDatabaseHas')
            || ! $call->var instanceof Variable || $call->var->name !== 'this'
            || count($call->args) !== 2) {
            return null;
        }

        foreach ($call->args as $arg) {
            if (! $arg instanceof Arg || $arg->name !== null || $arg->unpack || $arg->byRef) {
                return null;
            }
        }

        [$target, $data] = $call->getArgs();
        $table = $this->resolveTable($target);
        if ($table === null || ! $data->value instanceof Array_ || $this->hasInlineComments($statement)) {
            return null;
        }

        $mapping = $this->tables[$table];
        $fields = [];
        foreach ($data->value->items as $item) {
            if (! $item->key instanceof String_ || $item->byRef || $item->unpack) {
                return null;
            }

            $column = $item->key->value;
            $type = match (true) {
                $item->value instanceof Int_ => 'int',
                $item->value instanceof String_ => 'string',
                default => null,
            };

            if ($type === null || ! preg_match('/\A[A-Za-z_][A-Za-z_0-9]*\z/', $column)
                || ($mapping['columns'][$column] ?? null) !== $type || isset($fields[$column])) {
                return null;
            }

            $fields[$column] = $item->value;
        }

        $key = $mapping['uniqueKey'];
        if (! isset($fields[$key])) {
            return null;
        }

        // Use Laravel's testing connection resolver and the raw table, not a scoped Eloquent query.
        $connection = new MethodCall(new Variable('this'), 'getConnection', [
            new Arg(new ConstFetch(new Name('null'))),
            $target,
        ]);
        $query = new MethodCall($connection, 'table', [new Arg(new String_($table))]);
        $query = new MethodCall($query, 'where', [new Arg(new String_($key)), new Arg(clone $fields[$key])]);
        $statement->expr = new Assign(new Variable($name), new MethodCall($query, 'first'));

        $statements = [
            $statement,
            new Nop,
            new Expression(new MethodCall(new Variable('this'), 'assertNotNull', [
                new Arg(new Variable($name)),
                new Arg(new String_("Expected a row in $table for $key.")),
            ])),
        ];

        foreach ($fields as $column => $value) {
            $statements[] = new Nop;
            $statements[] = new Expression(new MethodCall(new Variable('this'), 'assertSame', [
                new Arg($value),
                new Arg(new PropertyFetch(new Variable($name), $column)),
                new Arg(new String_($column)),
            ]));
        }

        return $statements;
    }

    private function resolveTable(Arg $target): ?string
    {
        $value = $target->value;
        if ($value instanceof String_) {
            return isset($this->tables[$value->value]) ? $value->value : null;
        }

        if (! $value instanceof ClassConstFetch || ! $value->class instanceof Name
            || ! $value->name instanceof Identifier || ! $this->isName($value->name, 'class')) {
            return null;
        }

        $model = $this->getName($value->class);
        $tables = array_keys(array_filter($this->tables, static fn (array $mapping): bool => $mapping['model'] === $model));

        return count($tables) === 1 ? $tables[0] : null;
    }

    private function hasInlineComments(Expression $statement): bool
    {
        $tokens = $this->getFile()->getOldTokens();
        for ($i = $statement->getStartTokenPos(); $i <= $statement->getEndTokenPos(); $i++) {
            if (in_array($tokens[$i]->id, [T_COMMENT, T_DOC_COMMENT], true)) {
                return true;
            }
        }

        return false;
    }
}
