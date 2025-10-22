# Laravel Singletonize

Laravel Singletonize is a lightweight package that switches the default behaviour of the Laravel service container to resolve dependencies as singletons. Once installed, every concrete that is resolved without an explicit binding will automatically be cached and reused by the container.

## Compatibility

Laravel Singletonize is tested against Laravel 10.x, 11.x, and 12.x applications.

## Why this package?

Laravel's automatic resolution relies heavily on reflection to discover constructor dependencies on every request. For large codebases with many services, repeatedly reflecting on types becomes expensive and increases bootstrapping time. By promoting implicitly resolved services to singletons, the package ensures the reflection work is only performed once per service lifecycle, which can noticeably improve application performance.

This package was inspired by a Laravel core PR that aimed to improve container performance, although it was never merged into the framework itself: https://github.com/laravel/framework/pull/51209

## Installation

```bash
composer require huynt57/laravel-singletonize
```

The service provider is auto-discovered through Laravel's package discovery. If you prefer to register it manually, add the provider to your `config/app.php` file:

```php
'providers' => [
    Huynt57\LaravelSingletonize\SingletonizeServiceProvider::class,
],
```

## Configuration

Publish the configuration file to toggle the behaviour at runtime:

```bash
php artisan vendor:publish --tag=config --provider="Huynt57\\LaravelSingletonize\\SingletonizeServiceProvider"
```

The published file `config/laravel-singletonize.php` contains a single option:

```php
return [
    'enabled' => true,
];
```

Set `enabled` to `false` to restore Laravel's default behaviour of creating a fresh instance on every resolution unless it has been explicitly registered as a singleton.

## How it works

The package listens to the container's resolution lifecycle events. When a type is resolved for the first time, the resolved instance is cached and immediately re-registered in the container. Subsequent resolutions therefore reuse the same instance, effectively promoting every binding (including implicit bindings) to a singleton while still respecting contextual parameters.

Parameterised resolutions continue to return fresh instances, ensuring factories and runtime arguments behave as expected.

## Testing

A lightweight test harness is provided in `tests/run.php`. Execute the suite with:

```bash
composer test
```

The tests cover:

- Implicit binding reuse for classes without manual registration.
- Nested dependency reuse.
- Parameterised resolution exclusions.
- Closure bindings.
- Configuration toggling.
