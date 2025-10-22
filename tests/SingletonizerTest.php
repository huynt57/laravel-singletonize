<?php

use Codex\LaravelSingletonize\SingletonizeServiceProvider;
use Codex\LaravelSingletonize\Tests\Support\App\Application;

class SampleService
{
}

class DependentService
{
    public SampleService $sample;

    public function __construct(SampleService $sample)
    {
        $this->sample = $sample;
    }
}

class ParameterizedService
{
    public function __construct(public string $value)
    {
    }
}

class BindingService
{
}

return function (): array {
    $tests = [];

    $tests['resolves_same_instance_for_unbound_class'] = function () {
        $app = new Application();
        $app->register(SingletonizeServiceProvider::class);

        $first = $app->make(SampleService::class);
        $second = $app->make(SampleService::class);

        assert($first === $second, 'Container should return same instance for unbound classes.');
    };

    $tests['resolves_same_instance_for_nested_dependency'] = function () {
        $app = new Application();
        $app->register(SingletonizeServiceProvider::class);

        $first = $app->make(DependentService::class);
        $second = $app->make(DependentService::class);

        assert($first === $second, 'Container should return same instance for dependent classes.');
        assert($first->sample === $second->sample, 'Nested dependency should also be shared.');
    };

    $tests['resolves_new_instance_with_parameters'] = function () {
        $app = new Application();
        $app->register(SingletonizeServiceProvider::class);

        $first = $app->make(ParameterizedService::class, ['value' => 'first']);
        $second = $app->make(ParameterizedService::class, ['value' => 'second']);

        assert($first !== $second, 'Parameterized resolutions should not be shared.');
    };

    $tests['shares_bound_bindings'] = function () {
        $app = new Application();
        $app->register(SingletonizeServiceProvider::class);

        $app->bind(BindingService::class, function () {
            return new BindingService();
        });

        $first = $app->make(BindingService::class);
        $second = $app->make(BindingService::class);

        assert($first === $second, 'Bindings resolved through closures should be shared.');
    };

    $tests['creates_fresh_instance_after_forget'] = function () {
        $app = new Application();
        $app->register(SingletonizeServiceProvider::class);

        $original = $app->make(SampleService::class);

        $app->forgetInstance(SampleService::class);

        $replacement = $app->make(SampleService::class);

        assert($original !== $replacement, 'Forgetting an instance should produce a new object.');
        assert($replacement === $app->make(SampleService::class), 'Subsequent resolutions should reuse the replacement instance.');
    };

    $tests['creates_fresh_instances_after_forget_all'] = function () {
        $app = new Application();
        $app->register(SingletonizeServiceProvider::class);

        $first = $app->make(SampleService::class);

        $app->forgetInstances();

        $second = $app->make(SampleService::class);

        assert($first !== $second, 'Forgetting all instances should produce a new object.');
        assert($second === $app->make(SampleService::class), 'Subsequent resolutions should reuse the new instance.');
    };

    $tests['can_disable_through_config'] = function () {
        $app = new Application([
            'laravel-singletonize' => [
                'enabled' => false,
            ],
        ]);

        $app->register(SingletonizeServiceProvider::class);

        $first = $app->make(SampleService::class);
        $second = $app->make(SampleService::class);

        assert($first !== $second, 'Disabling via config should restore default behaviour.');
    };

    return $tests;
};
