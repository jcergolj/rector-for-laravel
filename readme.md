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

## 📄 License

This package is open-sourced software licensed under the [MIT license](LICENSE).
