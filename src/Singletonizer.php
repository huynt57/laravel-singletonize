<?php

namespace Huynt57\LaravelSingletonize;

use Closure;
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

        $this->container->beforeResolving(function ($abstract, array $parameters, Container $container) {
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

        $singletonizer = $this;

        $callback = function () use ($abstract, $container, $singletonizer) {
            if (isset($this->bindings[$abstract])) {
                $binding = $this->bindings[$abstract];

                $this->bindings[$abstract]['concrete'] = $singletonizer->createSharedConcrete(
                    $container,
                    $abstract,
                    $binding['concrete']
                );

                $this->bindings[$abstract]['shared'] = true;

                return;
            }

            $this->bindings[$abstract] = [
                'concrete' => $singletonizer->createSharedConcrete($container, $abstract, $abstract),
                'shared' => true,
            ];
        };

        ($callback->bindTo($container, $container))();
    }

    public function createSharedConcrete(Container $container, string $abstract, mixed $concrete): Closure
    {
        $factory = function (Container $container, array $parameters = []) use ($abstract, $concrete) {
            if (isset($this->instances[$abstract])) {
                return $this->instances[$abstract];
            }

            if ($concrete === $abstract) {
                $object = $this->build($concrete, $parameters);
            } elseif ($concrete instanceof Closure) {
                $object = $concrete($container, $parameters);
            } else {
                $object = $this->make($concrete, $parameters);
            }

            return $this->instances[$abstract] = $object;
        };

        return $factory->bindTo($container, $container);
    }
}
