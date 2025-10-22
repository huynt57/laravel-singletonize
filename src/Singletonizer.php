<?php

namespace Huynt57\LaravelSingletonize;

use Illuminate\Container\Container;

class Singletonizer
{
    protected Container $container;

    protected bool $enabled;

    /**
     * @var array<string, object>
     */
    protected array $instances = [];

    /**
     * @var array<int, string|null>
     */
    protected array $resolutionStack = [];

    protected bool $booted = false;

    /**
     * @var array<string, bool>
     */
    protected array $ignoredAbstracts;

    public function __construct(Container $container, bool $enabled = true, array $ignoredAbstracts = [])
    {
        $this->container = $container;
        $this->enabled = $enabled;
        $this->ignoredAbstracts = array_fill_keys($ignoredAbstracts, true);
    }

    public function boot(): void
    {
        if ($this->booted || ! $this->enabled) {
            return;
        }

        $this->booted = true;

        $this->container->beforeResolving(null, function ($abstract, array $parameters, Container $container) {
            $this->handleBeforeResolving($abstract, $parameters, $container);
        });

        $this->container->resolving(function ($object, Container $container) {
            $this->handleResolving($object, $container);
        });
    }

    protected function handleBeforeResolving($abstract, array $parameters, Container $container): void
    {
        if (! $this->enabled) {
            $this->resolutionStack[] = null;

            return;
        }

        if (! is_string($abstract)) {
            $this->resolutionStack[] = null;

            return;
        }

        if (isset($this->ignoredAbstracts[$abstract])) {
            $this->resolutionStack[] = null;

            return;
        }

        if ($this->shouldSkipParameters($parameters)) {
            $this->resolutionStack[] = null;

            return;
        }

        if (array_key_exists($abstract, $this->instances)) {
            $container->instance($abstract, $this->instances[$abstract]);

            return;
        }

        $this->resolutionStack[] = $abstract;
    }

    protected function handleResolving($object, Container $container): void
    {
        $abstract = array_pop($this->resolutionStack);

        if (! is_string($abstract)) {
            return;
        }

        if (isset($this->instances[$abstract]) || ! is_object($object)) {
            return;
        }

        $this->instances[$abstract] = $object;

        $container->instance($abstract, $object);
    }

    protected function shouldSkipParameters(array $parameters): bool
    {
        return ! empty($parameters);
    }
}
