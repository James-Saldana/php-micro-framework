<?php

declare(strict_types=1);

namespace App\Core\Container;

use App\Contracts\Container\ContainerInterface;
use Closure;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;

/**
 * Lightweight dependency injection container.
 */
final class Container implements ContainerInterface
{
    /** @var array<string, Closure|object> */
    private array $bindings = [];
    /** @var array<string, object> */
    private array $singletons = [];

    /**
     * @inheritDoc
     */
    public function bind(string $abstract, Closure|object|string $concrete, bool $singleton = false): void
    {
        if ($concrete instanceof Closure) {
            $this->bindings[$abstract] = function () use ($abstract, $concrete, $singleton) {
                if ($singleton && isset($this->singletons[$abstract])) {
                    return $this->singletons[$abstract];
                }
                $instance = $concrete($this);
                if ($singleton) {
                    $this->singletons[$abstract] = $instance;
                }
                return $instance;
            };
        } elseif (is_object($concrete)) {
            if ($singleton) {
                $this->singletons[$abstract] = $concrete;
            }
            $this->bindings[$abstract] = fn () => $concrete;
        } else {
            $this->bindings[$abstract] = function () use ($abstract, $concrete, $singleton) {
                if ($singleton && isset($this->singletons[$abstract])) {
                    return $this->singletons[$abstract];
                }
                $instance = $this->resolve($concrete);
                if ($singleton) {
                    $this->singletons[$abstract] = $instance;
                }
                return $instance;
            };
        }
    }

    /**
     * @inheritDoc
     */
    public function singleton(string $abstract, Closure|object|string $concrete): void
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * @inheritDoc
     */
    public function transient(string $abstract, Closure|object|string $concrete): void
    {
        $this->bind($abstract, $concrete, false);
    }

    /**
     * @inheritDoc
     */
    public function instance(string $abstract, object $instance): void
    {
        $this->singletons[$abstract] = $instance;
        $this->bindings[$abstract] = fn () => $instance;
    }

    /**
     * @inheritDoc
     */
    public function get(string $abstract): object
    {
        if (isset($this->singletons[$abstract])) {
            return $this->singletons[$abstract];
        }

        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]();
        }

        if (class_exists($abstract)) {
            return $this->resolve($abstract);
        }

        throw new InvalidArgumentException("Service [{$abstract}] not found in container.");
    }

    /**
     * @inheritDoc
     */
    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || isset($this->singletons[$abstract]);
    }

    /**
     * @inheritDoc
     */
    public function resolve(string $class): object
    {
        $reflector = new ReflectionClass($class);

        if (!$reflector->isInstantiable()) {
            throw new InvalidArgumentException("Class [{$class}] is not instantiable.");
        }

        $constructor = $reflector->getConstructor();

        if ($constructor === null) {
            return new $class();
        }

        $parameters = $constructor->getParameters();
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $type = $parameter->getType();

            if ($type === null) {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    throw new InvalidArgumentException(
                        "Cannot resolve parameter [{$parameter->getName()}] in [{$class}]"
                    );
                }
            } elseif ($type->isBuiltin()) {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    throw new InvalidArgumentException(
                        "Cannot resolve built-in type parameter [{$parameter->getName()}] in [{$class}]"
                    );
                }
            } else {
                $typeName = $type->getName();
                $dependencies[] = $this->get($typeName);
            }
        }

        return $reflector->newInstanceArgs($dependencies);
    }

    /**
     * @inheritDoc
     */
    public function call(callable $callable, array $parameters = []): mixed
    {
        if (is_array($callable) && isset($callable[0])) {
            $object = $callable[0];
            $method = $callable[1];

            if (is_string($object) && class_exists($object)) {
                $object = $this->get($object);
            }

            $reflection = new ReflectionClass($object);
            $methodReflection = $reflection->getMethod($method);
            $methodParameters = $methodReflection->getParameters();

            $args = $this->resolveMethodParameters($methodParameters, $parameters);
            return $methodReflection->invokeArgs($object, $args);
        }

        $reflection = new ReflectionFunction($callable);
        $functionParameters = $reflection->getParameters();

        $args = $this->resolveMethodParameters($functionParameters, $parameters);
        return $reflection->invokeArgs($args);
    }

    /**
     * Resolve method parameters with dependency injection.
     *
     * @param array<\ReflectionParameter> $parameters
     * @param array<string, mixed> $providedParameters
     * @return array<mixed>
     */
    private function resolveMethodParameters(array $parameters, array $providedParameters): array
    {
        $args = [];

        foreach ($parameters as $parameter) {
            $paramName = $parameter->getName();

            if (array_key_exists($paramName, $providedParameters)) {
                $args[] = $providedParameters[$paramName];
                continue;
            }

            $type = $parameter->getType();

            if ($type === null) {
                if ($parameter->isDefaultValueAvailable()) {
                    $args[] = $parameter->getDefaultValue();
                } else {
                    throw new InvalidArgumentException("Cannot resolve parameter [{$paramName}]");
                }
            } elseif ($type->isBuiltin()) {
                if ($parameter->isDefaultValueAvailable()) {
                    $args[] = $parameter->getDefaultValue();
                } else {
                    throw new InvalidArgumentException("Cannot resolve built-in type parameter [{$paramName}]");
                }
            } else {
                $typeName = $type->getName();
                $args[] = $this->get($typeName);
            }
        }

        return $args;
    }

    /**
     * Clear all registered services.
     */
    public function clear(): void
    {
        $this->bindings = [];
        $this->singletons = [];
    }

    /**
     * Get all registered bindings.
     *
     * @return array<string, Closure|object>
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }

    /**
     * Get all singleton instances.
     *
     * @return array<string, object>
     */
    public function getSingletons(): array
    {
        return $this->singletons;
    }
}
