<?php

namespace Huynt57\LaravelSingletonize;

use Illuminate\Container\Container;

class Singletonizer
{
    protected Container $container;

    protected bool $booted = false;

    /**
     * @var array<string, bool>
     */
    protected array $shared = [];

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $this->booted = true;

        $this->container->beforeResolving(null, function ($abstract, array $parameters, Container $container) {
            $this->ensureSharedBinding($abstract, $container);
        });
    }

    protected function ensureSharedBinding($abstract, Container $container): void
    {
        if (! is_string($abstract)) {
            return;
        }

        if (isset($this->shared[$abstract])) {
            return;
        }

        $this->shared[$abstract] = true;

        $container->resolving($abstract, function ($object, Container $container) use ($abstract) {
            $container->instance($abstract, $object);
        });

        $callback = function () use ($abstract) {
            if (isset($this->bindings[$abstract])) {
                $this->bindings[$abstract]['shared'] = true;

                return;
            }

            $this->bindings[$abstract] = [
                'concrete' => $abstract,
                'shared' => true,
            ];
        };

        ($callback->bindTo($container, $container))();
    }
}
