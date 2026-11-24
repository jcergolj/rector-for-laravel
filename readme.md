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

If you use this package's default `rector.php` config, all 14 custom rules documented below are enabled.
Remove unwanted rules from your published config. In particular, the default config
assumes intentionally unguarded models and forward-only migrations: disable
`RemoveFillableFromUnguardedModelsRector` if you rely on `$fillable` allowlists, and
`RemoveMigrationDownMethodRector` if you need migration rollbacks.

### Opt-in: AdoptProjectFormRequestRector

Add this to your project's Rector configuration and set `withPaths()` or
`withSkip()` to limit the requests Rector processes if needed:

```php
use App\Http\Requests\AppFormRequest;
use Jcergolj\RectorForLaravel\CustomRules\AdoptProjectFormRequestRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([__DIR__.'/app/Http/Requests'])
    ->withConfiguredRule(AdoptProjectFormRequestRector::class, [
        AdoptProjectFormRequestRector::BASE_CLASS => AppFormRequest::class,
    ]);
```

The rule switches requests that directly extend Laravel's `FormRequest` to the
configured base and removes their redundant `authorize(): bool` method. Both
authorization methods must be public and contain only `return true;`. The base
must itself directly extend Laravel's `FormRequest`. It may declare unused
non-public helpers, but not lifecycle hooks, inherited method overrides,
properties, constants, interfaces, traits, or attributes. The rule leaves
validation and other methods untouched and skips unresolved classes and
conflicting imports. It is not registered in the package's default config.

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

### RemovePhpUnitTestPrefixRector (opt-in)

Changes `public function test_rejects_invalid_language()` to
`#[Test] public function rejects_invalid_language()` in PHPUnit `TestCase`
subclasses, including those using an application test base. It keeps existing
attributes, data providers, signatures, and bodies.

Register it explicitly in your Rector config; it is not in the package defaults:

```php
->withRules([
    \Jcergolj\RectorForLaravel\CustomRules\RemovePhpUnitTestPrefixRector::class,
])
```

The rule skips name collisions, dependency-bearing methods, and method
references it recognizes in the same file. It leaves repeated `test_` prefixes
alone so a second run cannot strip another prefix. Review cross-file and dynamic
references before applying the rule. External PHPUnit `--filter` patterns that
match the old method names may need updating.

### One DTO per PSR-4 file (opt-in diagnostic)

From the consumer project's root, explicitly pass the DTO directory to scan:

```sh
vendor/bin/rector-laravel-dto-check app/DataTransferObjects
```

The command checks only direct `.php` files in that directory against the
project's Composer PSR-4 mappings. It reports additional named classes and
their suggested destination paths, leaving the class named after the source
file in place. An existing destination is reported as a conflict. Files
without a matching primary class, with multiple namespaces, or with executable
top-level code (including conditional or anonymous class declarations) are not
split candidates.

This is read-only. Rector 2.8.4 and 2.8.5
[only diff and write the file being processed](https://github.com/rectorphp/rector/blob/2.8.5/src/Application/FileProcessor.php);
they do not expose an added-file operation that previews and applies both
halves of a split. The command does not remove classes, create files, or run as
part of Rector. For each suggestion you apply manually, keep the namespace,
imports, file-level declarations, class attributes, docblock, and class code
in the new file. Regenerate optimized Composer autoload files if you use them.

## Opt-in Custom Rules

### DatabaseHasToFieldAssertionsRector

Turns a standalone Laravel `$this->assertDatabaseHas()` into one row lookup
and a separate `assertSame()` for each expected column, including the key.
It is not enabled in the default config:

```php
use App\Models\User;
use Jcergolj\RectorForLaravel\CustomRules\DatabaseHasToFieldAssertionsRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withConfiguredRule(DatabaseHasToFieldAssertionsRector::class, [
        'users' => [
            'model' => User::class,
            'uniqueKey' => 'id',
            'columns' => ['id' => 'int', 'name' => 'string'],
        ],
    ]);
```

Only map a model to its fixed table and use a key that is unique on its own
in the database. The rule accepts the literal table name or the mapped
`Model::class`, and an inline array containing the key. List only columns
whose raw values come back from the test database as the declared `int` or
`string` and whose SQL equality agrees with strict PHP equality. For example,
omit strings with case-insensitive collation and columns that require
driver-specific type conversion.

The generated lookup uses Laravel's testing connection resolver and reads
raw columns without applying Eloquent scopes, soft-delete filters, accessors,
or casts. Calls with an explicit connection argument, dynamic values,
unlisted columns, or unsupported shapes stay unchanged, as do missing and
count assertions. Leave pivot tables unmapped and rewrite pivot assertions
manually; the rule does not infer relationships.

## Additional Custom Rules

These rules are also enabled in the default config. The examples below show how
to register or configure them when maintaining your own `rector.php`.

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
rules above, with one shared `MAX_LINE_LENGTH` target per rule.

### BlankLineBetweenStatementsRector

Adds a blank line between consecutive statements, even when they are closely
related. Applies to functions, methods, closures, nested control-flow bodies and
file-level code. Separates assignments, function/method/static calls (including
`throw_unless()` and `abort_if()`), and boundaries before and after control flow.
Consecutive `use` imports stay together, including function, constant and grouped
imports. Existing blank lines between imports are preserved.

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

When maintaining your own `rector.php`, register the rule as follows:

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

When maintaining your own `rector.php`, register the rule as follows:

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

## Controller and route check (opt-in diagnostic)

Run this from your Laravel project's root, independently of `rector.php`:

```sh
vendor/bin/rector-laravel-controller-check .
```

The command reads PHP files under `app/Http/Controllers` and `routes` without
booting Laravel or modifying files.

- Concrete controllers can declare any subset of Laravel's resource actions, or
  a lone `__invoke()`. Constructors and `middleware()` don't count as actions.
- Explicit `Route::get()`, `post()`, `put()`, `patch()`, `delete()`, `options()`,
  and `any()` calls targeting `[Controller::class, 'action']` or
  `'Controller@action'` are checked for custom actions and incorrect HTTP verbs.
  Literal paths are also checked for the expected suffix, such as
  `DELETE /posts/{post}` for `destroy` or `GET /posts/{post}/edit` for `edit`.
- Resource registrations and invokable routes don't require URL checks. The
  check does not infer resource names, evaluate dynamic routes, or expand route
  groups.

Exit status: `0` for no findings, `1` for violations, and `2` for usage or PHP parse errors.

---

## 📄 License

This package is open-sourced software licensed under the [MIT license](LICENSE).
