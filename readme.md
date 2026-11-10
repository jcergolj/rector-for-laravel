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

Requires PHP 8.3 or newer through `driftingly/rector-laravel` 2.7.
Running this package's PHPUnit development suite requires PHP 8.4.1 or newer.
Dependencies use stable releases by default.

`driftingly/rector-laravel` 2.7.3 requires the abandoned
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

Preserves types, captures and function signatures, including nested arrows.

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

Skips standalone `query()` calls and non-Eloquent classes.

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

Preserves other annotations and avoids duplicate `#[Test]` attributes.

## Opt-In Rules

These rules are disabled by default. Uncomment them in `config/rector.php` or register them as shown below.

All `WrapLong*` rules use a configurable **soft target of 120 characters**, including indentation.
They leave existing multiline layouts unchanged and may still produce lines over the target.
Targets must be positive integers; Unicode characters count as one and tabs as four spaces.
Run Pint after Rector for project styling.

### WrapLongListsRector

Wraps long inline arrays, call argument lists and declaration parameter lists with
one item per line. Supports nested arrays, function/method/constructor calls,
closures, arrow functions and promoted constructor parameters. Enclosing
multi-argument calls are expanded when their inline arrays need wrapping.

```php
use Jcergolj\RectorForLaravel\CustomRules\WrapLongListsRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withConfiguredRule(WrapLongListsRector::class, [
        WrapLongListsRector::MAX_LINE_LENGTH => 120,
    ]);
```

For example:

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

Supports both `[]` and `array()`. Skips empty and single-item lists, existing
multiline lists, first-class callable references and anonymous-class constructor
argument lists. Never splits strings. Parameter-list length is measured through
the closing parenthesis, excluding return types and bodies; parameter wrapping
is skipped in files containing `__LINE__`.

### WrapLongChainsRector

Wraps long method, property and mixed access chains before `->` or `?->`:

```php
$value = $this->completedQuestionnaireWithADescriptiveName->sectionQuestionsWithADescriptiveName->first()->questionOneWithADescriptiveName;
```

becomes:

```php
$value = $this->completedQuestionnaireWithADescriptiveName
    ->sectionQuestionsWithADescriptiveName
    ->first()
    ->questionOneWithADescriptiveName;
```

```php
use Jcergolj\RectorForLaravel\CustomRules\WrapLongChainsRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withConfiguredRule(WrapLongChainsRector::class, [
        WrapLongChainsRector::MAX_LINE_LENGTH => 120,
    ]);
```

Supports nested and nullsafe chains. Keeps the first access beside a variable
receiver, and skips indivisible single accesses, existing multiline chains,
dynamic property names and first-class callable chains.

The previous separate list and chain rules have been removed. Use the two merged
rules above, with one shared `MAX_LINE_LENGTH` target per rule. Both remain opt-in;
the five default custom rules are unchanged.

### BlankLineBetweenStatementsRector

Adds a blank line between consecutive statements, even when they are closely
related. Applies to functions, methods, closures, nested control-flow bodies and
file-level code. Separates assignments, function/method/static calls (including
`throw_unless()` and `abort_if()`), and boundaries before and after control flow.

```php
$person = $mapping->fresh();
$link = $person;
$link->associateWith($moodleUserId, 'manual');
EsrAudit::create(['action' => 'manual_link']);
```

becomes:

```php
$person = $mapping->fresh();

$link = $person;

$link->associateWith($moodleUserId, 'manual');

EsrAudit::create(['action' => 'manual_link']);
```

Enable it explicitly:

```php
use Jcergolj\RectorForLaravel\CustomRules\BlankLineBetweenStatementsRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withRules([
        BlankLineBetweenStatementsRector::class,
    ]);
```

Preserves existing blank lines, leading/trailing comment attachment, indentation
and LF/CRLF line endings. Does not add trailing spaces, blank lines immediately
inside braces, or spacing within a single statement (such as a `for` header).
Class members and `match` arms are not separated. Match-arm spacing is a separate
concern, not part of this rule. Works with `AddBlankLineAfterPhpUnitAssertionRector`
and all `WrapLong*` rules, in either registration order.

**Breaking change in v15:** `BlankLineBetweenUnrelatedAssignmentsRector` has been
removed without a compatibility alias. Replace its import and registration with
`BlankLineBetweenStatementsRector`, introduced in v14. Update any rule-specific
skip entries to use the new class as well. The replacement intentionally separates
all consecutive statements, including dependent assignments and calls, rather
than only unrelated assignments. It remains opt-in.

Keep existing manual blank lines and `AddBlankLineAfterPhpUnitAssertionRector`;
neither needs removing.

### WrapLongConcatenationsRector

Wraps long concatenations with dots at the end of continued lines:

```php
'attribute' => trans('validation.attributes.email').
    trans('dashboard.on_the').
    trans('allocated-tokens.create.step_1').
    trans('dashboard.tab'),
```

Enable it explicitly in your `rector.php`:

```php
use Jcergolj\RectorForLaravel\CustomRules\WrapLongConcatenationsRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withConfiguredRule(WrapLongConcatenationsRector::class, [
        WrapLongConcatenationsRector::MAX_LINE_LENGTH => 120,
    ]);
```

Supports nested concatenations in calls and arrays. Never splits string literals.

### WrapLongBooleanConditionsRector

Wraps long `if`, `elseif`, `while` and `do/while` conditions using `||` or `&&`,
with operators at the end of continued lines:

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

Preserves grouping and short-circuiting. Skips `and`, `or`, `xor`, `for` headers
and boolean expressions outside these conditions.

### MiddlewareTestsFirstRector

Moves tests calling `assertMiddlewareIsApplied()` before other tests, preserving order within each group.

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

Non-test members stay in place. Calls inside closures or nested classes do not count.
Skips classes with trailing inline member comments. Run Pint afterward if needed.

### DataProviderBeforeTestRector

Moves each local PHPUnit data provider above the first test that uses it.

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

Supports `#[DataProvider('method')]` and `@dataProvider method`.
Skips inherited, external and unused providers, and classes with trailing inline member comments.
Works with `MiddlewareTestsFirstRector`. Run Pint afterward if needed.

To enable any of these test rules, add them to your `rector.php`:

```php
use Jcergolj\RectorForLaravel\CustomRules\DataProviderBeforeTestRector;
use Jcergolj\RectorForLaravel\CustomRules\MiddlewareTestsFirstRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withRules([
        MiddlewareTestsFirstRector::class,
        DataProviderBeforeTestRector::class,
    ]);
```

### RemoveFillableFromUnguardedModelsRector

Removes `$fillable` properties from Eloquent models for projects that intentionally run with unguarded models.

**Only enable for unguarded models** (`Model::unguard()` or equivalent). Unsafe if you rely on `$fillable` allowlists.

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

**Only enable for forward-only migrations.** Do not use if your team needs migration rollbacks.

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
