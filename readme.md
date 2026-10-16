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

To enable either or both test rules, add them to your `rector.php`:

```php
use Jcergolj\RectorForLaravel\CustomRules\BlankLineBetweenTestFixturesRector;
use Jcergolj\RectorForLaravel\CustomRules\MiddlewareTestsFirstRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withRules([
        BlankLineBetweenTestFixturesRector::class,
        MiddlewareTestsFirstRector::class,
    ]);
```

Both rules are idempotent. Fixture spacing is compatible with the existing assertion
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
