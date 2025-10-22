<?php

namespace Illuminate\Container;

use ArrayAccess;
use Closure;

class Container implements ArrayAccess
{
    /**
     * The current globally available container (if any).
     */
    protected static ?Container $instance = null;

    /**
     * The container's registered bindings.
     *
     * @var array<string, array{concrete: mixed, shared: bool}>
     */
    protected array $bindings = [];

    /**
     * The shared instance bindings.
     *
     * @var array<string, mixed>
     */
    protected array $instances = [];

    /**
     * The container's resolving callbacks.
     *
     * @var array<string, array<int, Closure>>
     */
    protected array $resolvingCallbacks = ['*' => []];

    /**
     * The container's global resolving callbacks.
     *
     * @var array<int, Closure>
     */
    protected array $globalResolvingCallbacks = [];

    /**
     * The container's before resolving callbacks.
     *
     * @var array<string, array<int, Closure>>
     */
    protected array $beforeResolvingCallbacks = ['*' => []];

    /**
     * The resolved binding map.
     *
     * @var array<string, bool>
     */
    protected array $resolved = [];

    /**
     * Create a new container instance.
     */
    public function __construct()
    {
        static::$instance = $this;
    }

    public function offsetExists(mixed $offset): bool
    {
        return $this->bound($offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->make($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->instance($offset, $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->bindings[$offset], $this->instances[$offset], $this->resolved[$offset]);
    }

    public function singleton(string $abstract, mixed $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    public function bind(string $abstract, mixed $concrete = null, bool $shared = false): void
    {
        if (is_null($concrete)) {
            $concrete = $abstract;
        }

        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'shared' => $shared,
        ];
    }

    public function instance(string $abstract, mixed $instance): mixed
    {
        $this->instances[$abstract] = $instance;
        $this->resolved[$abstract] = true;

        return $instance;
    }

    public function make(string $abstract, array $parameters = []): mixed
    {
        $abstract = $this->getAlias($abstract);

        $this->fireBeforeResolvingCallbacks($abstract, $parameters);

        if (empty($parameters) && array_key_exists($abstract, $this->instances)) {
            return $this->instances[$abstract];
        }

        $concrete = $this->getConcrete($abstract);

        if ($this->isBuildable($concrete, $abstract)) {
            $object = $this->build($concrete, $parameters);
        } else {
            $object = $this->make($concrete, $parameters);
        }

        if ($this->isShared($abstract)) {
            $this->instances[$abstract] = $object;
        }

        $this->resolved[$abstract] = true;

        $this->fireResolvingCallbacks($abstract, $object);

        return $object;
    }

    public function resolved(string $abstract): bool
    {
        $abstract = $this->getAlias($abstract);

        return isset($this->resolved[$abstract]) || array_key_exists($abstract, $this->instances);
    }

    public function bound(string $abstract): bool
    {
        return array_key_exists($abstract, $this->bindings) || array_key_exists($abstract, $this->instances);
    }

    protected function getConcrete(string $abstract): mixed
    {
        if (! isset($this->bindings[$abstract])) {
            return $abstract;
        }

        return $this->bindings[$abstract]['concrete'];
    }

    protected function isBuildable(mixed $concrete, string $abstract): bool
    {
        return $concrete === $abstract || $concrete instanceof Closure;
    }

    protected function isShared(string $abstract): bool
    {
        if (array_key_exists($abstract, $this->instances)) {
            return true;
        }

        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]['shared'];
        }

        return false;
    }

    protected function build(mixed $concrete, array $parameters = []): mixed
    {
        if ($concrete instanceof Closure) {
            return $concrete($this, $parameters);
        }

        if (! class_exists($concrete)) {
            throw new class("Target class [$concrete] does not exist.") extends \Exception {
            };
        }

        $reflector = new \ReflectionClass($concrete);

        if (! $reflector->isInstantiable()) {
            throw new class("Target class [$concrete] is not instantiable.") extends \Exception {
            };
        }

        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            return new $concrete;
        }

        $dependencies = [];

        foreach ($constructor->getParameters() as $parameter) {
            $parameterName = $parameter->getName();

            if (array_key_exists($parameterName, $parameters)) {
                $dependencies[] = $parameters[$parameterName];

                continue;
            }

            $dependency = $parameter->getType();

            if ($dependency && ! $dependency->isBuiltin()) {
                $dependencies[] = $this->make($dependency->getName());

                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $dependencies[] = $parameter->getDefaultValue();

                continue;
            }

            throw new class("Unresolvable dependency resolving [$concrete] parameter [$parameterName].") extends \Exception {
            };
        }

        return $reflector->newInstanceArgs($dependencies);
    }

    protected function fireBeforeResolvingCallbacks(string $abstract, array $parameters): void
    {
        foreach ($this->beforeResolvingCallbacks[$abstract] ?? [] as $callback) {
            $callback($abstract, $parameters, $this);
        }

        foreach ($this->beforeResolvingCallbacks['*'] ?? [] as $callback) {
            $callback($abstract, $parameters, $this);
        }
    }

    protected function fireResolvingCallbacks(string $abstract, mixed $object): void
    {
        foreach ($this->resolvingCallbacks[$abstract] ?? [] as $callback) {
            $callback($object, $this);
        }

        foreach ($this->globalResolvingCallbacks as $callback) {
            $callback($object, $this);
        }
    }

    public function beforeResolving(Closure|string|null $abstract, ?Closure $callback = null): static
    {
        if ($abstract instanceof Closure) {
            $callback = $abstract;
            $abstract = null;
        }

        $abstract = $abstract ?? '*';

        $this->beforeResolvingCallbacks[$abstract][] = $callback;

        return $this;
    }

    public function beforeResolvingAny(Closure $callback): static
    {
        return $this->beforeResolving('*', $callback);
    }

    public function resolving(Closure|string $abstract, ?Closure $callback = null): static
    {
        if ($abstract instanceof Closure) {
            $this->globalResolvingCallbacks[] = $abstract;

            return $this;
        }

        $this->resolvingCallbacks[$abstract][] = $callback;

        return $this;
    }

    public function hasInstance(string $abstract): bool
    {
        return array_key_exists($abstract, $this->instances);
    }

    public function flush(): void
    {
        $this->bindings = [];
        $this->instances = [];
        $this->resolvingCallbacks = ['*' => []];
        $this->globalResolvingCallbacks = [];
        $this->beforeResolvingCallbacks = ['*' => []];
        $this->resolved = [];
    }

    public function basePath(string $path = ''): string
    {
        return __DIR__.'/../../..'.($path ? DIRECTORY_SEPARATOR.ltrim($path, DIRECTORY_SEPARATOR) : '');
    }

    public function getAlias(string $abstract): string
    {
        return $abstract;
    }

    public function forgetInstance(string $abstract): void
    {
        unset($this->instances[$abstract], $this->resolved[$abstract]);
    }

    public function forgetInstances(): void
    {
        $this->instances = [];
    }

    public static function getInstance(): ?static
    {
        return static::$instance;
    }

    public static function setInstance(?Container $container): void
    {
        static::$instance = $container;
    }
}
