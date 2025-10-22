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

Singletonize boots a small helper (`Huynt57\LaravelSingletonize\Singletonizer`) that hooks into Laravel's container before any concrete is resolved. The helper inspects the abstract being requested and, the first time it encounters a class name, rewrites the container's internal binding definition to:

1. Wrap the original concrete (class name, closure, or alias) in a memoized closure that stores the first resolved instance in `$container->instances`.
2. Mark the binding as `shared`, so future resolutions skip the normal reflection/build process.

Because the memoized closure still receives the container and the original parameter array, contextual bindings and parameterised `make()` calls continue to work. When parameters are provided, the memoized closure bypasses the cached instance and rebuilds the object, ensuring runtime factories keep their expected behaviour.

The helper only runs once per abstract per request, so the bookkeeping overhead is minimal while every implicitly resolved service becomes a singleton automatically.

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
