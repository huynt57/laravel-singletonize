<?php

namespace Illuminate\Support;

use Illuminate\Container\Container;

abstract class ServiceProvider
{
    public Container $app;

    public function __construct(Container $app)
    {
        $this->app = $app;
    }

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        //
    }

    protected function mergeConfigFrom(string $path, string $key): void
    {
        $config = $this->app['config'] ?? null;

        if (! $config || ! method_exists($config, 'get')) {
            return;
        }

        $existing = $config->get($key, []);
        $defaults = file_exists($path) ? include $path : [];

        $config->set($key, array_merge($defaults, $existing));
    }

    protected function publishes(array $paths, ?string $group = null): void
    {
        // Publishing is ignored in the testing stub.
    }
}
