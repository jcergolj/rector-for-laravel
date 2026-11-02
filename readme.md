# Laravel Rector Rules

A reusable Rector package for Laravel projects, containing custom rules and default configuration to enforce consistent refactoring patterns across multiple projects.

---

## Installation

Install the package in your Laravel project:

```bash
composer require jcergolj/rector-for-laravel --dev
```

This package also depends on:

- [`rector/rector`](https://github.com/rectorphp/rector)
- [`driftingly/rector-laravel`](https://github.com/driftingly/rector-laravel)

These will be pulled in automatically if not already installed.

`driftingly/rector-laravel` 2.6.2 requires the abandoned
`symplify/rule-doc-generator-contracts` package, so Composer may print an abandonment
warning. It does not prevent installation. There is no suggested replacement; removing
it requires a change in the upstream Laravel Rector dependency, not simply deleting
it from this package's dependencies.

---

## ⚙️ Usage Options

You can use the default `rector.php` config file in two ways:

### ✅ Option 1: Publish the Config to Project Root (Laravel Style)

You can publish the default `rector.php` file to the root of your Laravel project using:

```bash
php artisan vendor:publish --tag=rector-for-laravel-config
```

This will copy `rector.php` from the package into the root of your Laravel project:

```bash
./rector.php
```

You can then modify this file to suit your project-specific rules and paths.

---

### 🟡 Option 2: Use the Config Directly from the Package

You can run Rector with the config file located in the package itself:

```bash
vendor/bin/rector process --config=vendor/jcergolj/rector-for-laravel/config/rector.php
```

This is a good option if you want to keep the default setup with no local modifications.

---

## Included Custom Rules

If you use this package's default `rector.php` config, all five custom rules below are enabled.

### AddBlankLineAfterPhpUnitAssertionRector

Adds a blank line around assertions inside PHPUnit test methods so assertion blocks are easier to scan.

```php
public function test_user_can_log_in(): void
{
    $user = User::factory()->create();
    $this->assertTrue(true);
    $this->assertSame('John', $user->name);
}
```

becomes:

```php
public function test_user_can_log_in(): void
{
    $user = User::factory()->create();

    $this->assertTrue(true);

    $this->assertSame('John', $user->name);
}
```

This rule only targets PHPUnit test methods.

### ArrowFunctionToClosureRector

Rewrites PHP arrow functions to equivalent closures with explicit `use (...)` captures.

```php
$names = array_map(fn (string $name): string => $prefix.$name, $users);
```

becomes:

```php
$names = array_map(function (string $name) use ($prefix): string {
    return $prefix.$name;
}, $users);
```

The rule preserves parameter types, defaults, variadics, references, `static`, declared return types, nested arrows, and arrows used inside fluent chains.

### ModelCastsPropertyToCastsMethodRector

Converts Eloquent `protected $casts = [...]` properties into `protected function casts(): array` methods.

```php
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $casts = [
        'age' => 'integer',
    ];
}
```

becomes:

```php
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected function casts(): array
    {
        return [
            'age' => 'integer',
        ];
    }
}
```

Enable this only on Laravel versions that support defining model casts via a `casts(): array` method. The rule skips classes that already define `casts()`.

### ModelQueryToDirectStaticCallRector

Rewrites fluent Eloquent chains that start with `Model::query()` to the shorter direct static form when the rewrite is safe.

```php
Invoice::query()->where('status', 'paid')->latest()->first();
Invoice::query()->with('customer')->orderBy('created_at', 'desc')->get();
```

becomes:

```php
Invoice::where('status', 'paid')->latest()->first();
Invoice::with('customer')->orderBy('created_at', 'desc')->get();
```

The rule intentionally skips cases where `query()` is not immediately followed by another method call, and skips non-Eloquent classes that happen to define a `query()` method.

### PhpUnitTestDocblockToAttributeRector

Converts PHPUnit `@test` docblock annotations to the native PHP 8 `#[Test]` attribute.

```php
use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    /**
     * @test
     */
    public function user_can_log_in(): void
    {
    }
}
```

becomes:

```php
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    #[Test]
    public function user_can_log_in(): void
    {
    }
}
```

The rule removes the `@test` line, drops the docblock entirely if it becomes empty, preserves unrelated annotations such as `@covers`, and avoids introducing duplicate `#[Test]` attributes.

## Opt-In Rules

The rules below are intentionally not enabled in this package's default `config/rector.php`.
The default config includes them as commented-out entries so they are easy to opt into explicitly.

### WrapLongArraysRector

Wraps inline arrays with multiple items on lines exceeding a configurable soft
target (120 characters by default, including indentation). Nested arrays are
expanded too. Enclosing multi-argument calls are expanded to one argument per line
when an array inside their arguments is wrapped:

```php
$token = Token::factory()->for(User::factory()->create(['email' => 'joe.doe@example.com', 'first_name' => 'joe', 'last_name' => 'doe']), 'buyer');
```

becomes:

```php
$token = Token::factory()->for(
    User::factory()->create([
        'email' => 'joe.doe@example.com',
        'first_name' => 'joe',
        'last_name' => 'doe',
    ]),
    'buyer',
);
```

Enable it explicitly in your `rector.php`:

```php
use Jcergolj\RectorForLaravel\CustomRules\WrapLongArraysRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withRules([
        WrapLongArraysRector::class,
    ]);
```

To change the soft target, replace `withRules()` with:

```php
->withConfiguredRule(WrapLongArraysRector::class, [
    WrapLongArraysRector::MAX_LINE_LENGTH => 100,
])
```

Exactly-at-target lines, existing multiline layouts, empty arrays and single-item
arrays are left unchanged. Length counts Unicode characters, with tabs counted as
four spaces. Keys, order, unpacking, references, comments and literal values are
preserved using syntax-guided token edits and Rector's format-preserving printer.
Both `[]` and `array()` syntax are supported. Run Pint afterward for normal project
styling; repeated Rector/Pint cycles are stable.

This is an optional readability convention, not a hard line-length limit or a
PSR-12 requirement to wrap every long line. Indivisible strings, URLs, regexes,
heredocs and other unsupported syntax are not split. General call/parameter/chain
wrapping, PHPDoc, Blade, HTML, JavaScript and CSS are outside this rule's scope.

### WrapLongFluentChainsRector

Wraps inline fluent call chains on lines exceeding a configurable **soft target**
(default: 120 characters, including indentation). Exactly 120 characters is unchanged.
Unicode characters count as one character; tabs count as four columns.

```php
Route::post('password/reset', [ResetPasswordController::class, 'reset'])->middleware('throttle:5,10')->name('password.update');
```

becomes:

```php
Route::post('password/reset', [ResetPasswordController::class, 'reset'])
    ->middleware('throttle:5,10')
    ->name('password.update');
```

To opt in, add the rule to your `rector.php`:

```php
use Jcergolj\RectorForLaravel\CustomRules\WrapLongFluentChainsRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withConfiguredRule(WrapLongFluentChainsRector::class, [
        WrapLongFluentChainsRector::MAX_LINE_LENGTH => 120,
    ]);
```

The target must be a positive integer. Alternatively, enable it with `withRules()`
to use the default target. It is **not enabled in the published default config**.

Supports method and nullsafe method continuations after static calls, function
calls, constructors, or another method call. The first call stays on the original
line; continuations receive one indentation level. Evaluation order, nullsafe
operators, comments, arguments, and literal values are preserved. Nested inline
chains in arguments and arrays are supported without expanding their argument lists.
When combined with `WrapLongArraysRector`, array and argument expansion can happen
in the same pass as chain wrapping, with nested indentation preserved. Composition
is tested with either rule registration order.

Short lines, single calls, property-only chains, first-class callable chains, and
already-multiline chains (including multiline arguments) are left alone. Run Pint
after Rector to normalize indentation in nested expressions. Repeating
`Rector → Pint → Rector → Pint` leaves the second cycle unchanged.

This is a readability convention, not a hard line-length limit or a guarantee that
every resulting line fits within 120 characters. Long indivisible arguments remain
unchanged; the rule does not split strings or introduce temporary variables.

### WrapLongBooleanConditionsRector

Wraps inline `if`, `elseif`, `while`, and `do/while` conditions rooted in `||` or
`&&` on lines exceeding a configurable soft target (default: 120 characters,
including indentation). Operators stay at the end of continued condition lines;
the final operand has no trailing operator:

```php
if (
    $request->routeIs('licence-agreement.create') ||
    $request->routeIs('licence-agreement.store') ||
    $request->routeIs('download-licence-agreement')
) {
    // Existing body unchanged.
}
```

Enable it explicitly in your `rector.php`:

```php
use Jcergolj\RectorForLaravel\CustomRules\WrapLongBooleanConditionsRector;

return RectorConfig::configure()
    ->withConfiguredRule(WrapLongBooleanConditionsRector::class, [
        WrapLongBooleanConditionsRector::MAX_LINE_LENGTH => 120,
    ]);
```

Alternatively, use `withRules()` for the default target. The target must be a
positive integer. This rule is **not enabled in the published default config**.
Exactly-at-target lines and existing multiline conditions are unchanged. Unicode
characters count as one character; tabs count as four columns.

Syntax-guided token edits preserve parentheses, precedence, short-circuiting,
comments, and literal values without extracting variables. Nested boolean groups
and nested statements are supported. Keyword operators (`and`, `or`, `xor`),
`for` headers, and boolean expressions outside these condition headers are left
alone. Indivisible operands may still exceed the soft target.

Run Pint afterward to normalize nested indentation. Tests verify stable
`Rector → Pint → Rector → Pint` cycles, including composition with array, enclosing
argument, and fluent-chain wrapping. This is an optional readability convention,
not a hard line-length limit or a PSR-12 requirement.

### BlankLineBetweenTestFixturesRector

Adds a blank line between adjacent Laravel factory fixture assignments in PHPUnit
test methods and `setUp()`, including tests extending your application's `Tests\TestCase`.

```php
$buyer = User::factory()->create();
$firstToken = Token::factory()->for($buyer, 'buyer')->create();
$secondToken = Token::factory()->for($buyer, 'buyer')->create();
```

becomes:

```php
$buyer = User::factory()->create();

$firstToken = Token::factory()->for($buyer, 'buyer')->create();

$secondToken = Token::factory()->for($buyer, 'buyer')->create();
```

The supported boundary is deliberately syntactic: both statements must be standalone
`=` assignments to different named local variables or `$this->namedProperty`, with
right-hand sides rooted in a named `Class::factory()` call, optionally followed by
fluent method calls. Multiline calls and leading comments are supported; existing
blank lines are preserved. No model type inference or Arrange-phase guessing is used.

Arbitrary assignments, repeated assignments to the same target, dynamic calls/targets,
references, destructuring, nested blocks/closures, traits, providers and helpers are
left alone. Pairs with a trailing inline comment on the first statement are also
left alone so the parser/printer cannot detach the comment from its original line.

### MiddlewareTestsFirstRector

Groups middleware-wiring tests before other PHPUnit tests, preserving order within
each group. It recognizes direct `assertMiddlewareIsApplied()` calls, not method
names. Calls inside closures or nested classes, and first-class callable references,
do not identify a middleware test. Dynamic method names are ignored.

```php
public function test_can_view_tokens(): void
{
    $this->get('/tokens')->assertOk();
}

public function test_auth_is_applied(): void
{
    $this->get('/tokens')->assertMiddlewareIsApplied('auth');
}
```

becomes:

```php
public function test_auth_is_applied(): void
{
    $this->get('/tokens')->assertMiddlewareIsApplied('auth');
}

public function test_can_view_tokens(): void
{
    $this->get('/tokens')->assertOk();
}
```

Only test-method slots are reordered, so properties, setup methods, helpers and
providers stay in place. Attributes, docblocks, associated leading comments, method
bodies and data-provider references move with their tests. Non-test classes are
unchanged. Classes with trailing inline member comments are skipped to preserve
comment ownership. The rule does not add or remove middleware coverage. Run Pint afterward
to normalize method separators if needed.

### DataProviderBeforeTestRector

Places each local PHPUnit data provider immediately above the first test that uses
it, so the input cases can be read before the test body. Shared providers appear
only once, above their first consuming test. Multiple providers are ordered by
their references on that test.

```php
public static function values(): array
{
    return [['value']];
}

#[\PHPUnit\Framework\Attributes\DataProvider('values')]
public function test_value(string $value): void
{
    $this->assertSame('value', $value);
}
```

Supports PHPUnit `#[DataProvider('method')]` attributes (including aliases and
named `methodName` arguments) and legacy `@dataProvider method` annotations.
Only providers declared in the same test class move; inherited, external and
non-literal references are left alone. Unused providers, non-test classes and
traits are unchanged. Attributes, docblocks and leading comments stay with their
methods. Classes with trailing inline member comments are skipped to preserve
comment ownership. Run Pint afterward to normalize method separators if needed.

The rule is idempotent and can be combined with `MiddlewareTestsFirstRector`.

To enable any of these test rules, add them to your `rector.php`:

```php
use Jcergolj\RectorForLaravel\CustomRules\BlankLineBetweenTestFixturesRector;
use Jcergolj\RectorForLaravel\CustomRules\DataProviderBeforeTestRector;
use Jcergolj\RectorForLaravel\CustomRules\MiddlewareTestsFirstRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withRules([
        BlankLineBetweenTestFixturesRector::class,
        MiddlewareTestsFirstRector::class,
        DataProviderBeforeTestRector::class,
    ]);
```

These rules are idempotent. Fixture spacing is compatible with the existing assertion
spacing rule and `NewlineBeforeNewAssignSetRector`; Pint's default Laravel preset
preserves blank lines between these assignments.

### RemoveFillableFromUnguardedModelsRector

Removes `$fillable` properties from Eloquent models for projects that intentionally run with unguarded models.

Enable it only if your application relies on `Model::unguard()` or an equivalent base-model convention. If your project depends on per-model mass-assignment allowlists, this rule is not safe to use.

To opt in, add the rule to your published `rector.php` config:

```php
use Jcergolj\RectorForLaravel\CustomRules\RemoveFillableFromUnguardedModelsRector;

return RectorConfig::configure()
    ->withRules([
        RemoveFillableFromUnguardedModelsRector::class,
    ]);
```

Example:

```php
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $fillable = [
        'name',
        'email',
    ];
}
```

becomes:

```php
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
}
```

### RemoveMigrationDownMethodRector

Removes `down()` methods from Laravel migrations for teams that intentionally do not keep migrations reversible.

This rule is opinionated and should only be enabled if your project explicitly treats migrations as forward-only. If your team expects rollback support from individual migration files, do not enable it.

To opt in, add the rule to your published `rector.php` config:

```php
use Jcergolj\RectorForLaravel\CustomRules\RemoveMigrationDownMethodRector;

return RectorConfig::configure()
    ->withRules([
        RemoveMigrationDownMethodRector::class,
    ]);
```

Example:

```php
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    public function up(): void
    {
    }

    public function down(): void
    {
    }
}
```

becomes:

```php
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    public function up(): void
    {
    }
}
```

---

## 📄 License

This package is open-sourced software licensed under the [MIT license](LICENSE).
