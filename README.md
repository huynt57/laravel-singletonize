# Laravel Singletonize

Laravel Singletonize is a lightweight package that switches the default behaviour of the Laravel service container to resolve dependencies as singletons. Once installed, every concrete that is resolved without an explicit binding will automatically be cached and reused by the container.

## Installation

```bash
composer require codex/laravel-singletonize
```

The service provider is auto-discovered through Laravel's package discovery. If you prefer to register it manually, add the provider to your `config/app.php` file:

```php
'providers' => [
    Codex\LaravelSingletonize\SingletonizeServiceProvider::class,
],
```

## Configuration

Publish the configuration file to toggle the behaviour at runtime:

```bash
php artisan vendor:publish --tag=config --provider="Codex\\LaravelSingletonize\\SingletonizeServiceProvider"
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
