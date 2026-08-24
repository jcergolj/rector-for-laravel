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
- [`rector/laravel`](https://github.com/rectorphp/rector-laravel)

These will be pulled in automatically if not already installed.

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

If you use this package's default `rector.php` config, both custom rules below are enabled.

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

---

## 📄 License

This package is open-sourced software licensed under the [MIT license](LICENSE).
