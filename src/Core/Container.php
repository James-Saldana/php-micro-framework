<?php

declare(strict_types=1);

namespace App\Core;

use Closure;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;

/**
 * Lightweight dependency injection container.
 *
 * This is a simple, fast container that supports:
 * - Service registration with closures or instances
 * - Singleton and transient services
 * - Automatic resolution of class dependencies
 * - Named service registration
 *
 * Unlike complex DI containers, this one is intentionally minimal
 * and does not support auto-wiring or complex configuration.
 */
final class Container
{
    /** @var array<string, Closure|object> */
    private array $bindings = [];
    /** @var array<string, object> */
    private array $singletons = [];

    /**
     * Register a service in the container.
     *
     * @template T of object
     * @param string $abstract The service identifier (class name or interface)
     * @param Closure|object|class-string<T> $concrete The service factory or instance
     * @param bool $singleton Whether the service should be a singleton
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
            // Class name - auto-resolve
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
     * Register a singleton service.
     *
     * @template T of object
     * @param string $abstract The service identifier
     * @param Closure|object|class-string<T> $concrete The service factory or instance
     */
    public function singleton(string $abstract, Closure|object|string $concrete): void
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * Register a transient service (new instance each time).
     *
     * @template T of object
     * @param string $abstract The service identifier
     * @param Closure|object|class-string<T> $concrete The service factory or instance
     */
    public function transient(string $abstract, Closure|object|string $concrete): void
    {
        $this->bind($abstract, $concrete, false);
    }

    /**
     * Register a service instance directly.
     *
     * @template T of object
     * @param string $abstract The service identifier
     * @param T $instance The service instance
     */
    public function instance(string $abstract, object $instance): void
    {
        $this->singletons[$abstract] = $instance;
        $this->bindings[$abstract] = fn () => $instance;
    }

    /**
     * Resolve a service from the container.
     *
     * @template T of object
     * @param string $abstract The service identifier
     * @return T The resolved service instance
     * @throws InvalidArgumentException If the service cannot be resolved
     */
    public function get(string $abstract): object
    {
        // Check singletons first
        if (isset($this->singletons[$abstract])) {
            return $this->singletons[$abstract];
        }

        // Check bindings
        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]();
        }

        // Try to auto-resolve the class
        if (class_exists($abstract)) {
            return $this->resolve($abstract);
        }

        throw new InvalidArgumentException("Service [{$abstract}] not found in container.");
    }

    /**
     * Check if a service is registered.
     */
    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || isset($this->singletons[$abstract]);
    }

    /**
     * Resolve a class with its constructor dependencies.
     *
     * @template T of object
     * @param class-string<T> $class The class to resolve
     * @return T The resolved instance
     * @throws ReflectionException If the class cannot be reflected
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
     * Call a callable with dependency injection.
     *
     * @param callable $callable The callable to invoke
     * @param array<string, mixed> $parameters Optional parameters to pass
     * @return mixed The result of the callable
     */
    public function call(callable $callable, array $parameters = []): mixed
    {
        if (is_array($callable) && isset($callable[0])) {
            // Method call on an object
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

        // Closure or function
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

            // Check if parameter is provided
            if (array_key_exists($paramName, $providedParameters)) {
                $args[] = $providedParameters[$paramName];
                continue;
            }

            $type = $parameter->getType();

            if ($type === null) {
                if ($parameter->isDefaultValueAvailable()) {
                    $args[] = $parameter->getDefaultValue();
                } else {
                    throw new InvalidArgumentException(
                        "Cannot resolve parameter [{$paramName}]"
                    );
                }
            } elseif ($type->isBuiltin()) {
                if ($parameter->isDefaultValueAvailable()) {
                    $args[] = $parameter->getDefaultValue();
                } else {
                    throw new InvalidArgumentException(
                        "Cannot resolve built-in type parameter [{$paramName}]"
                    );
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
