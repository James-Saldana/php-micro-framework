<?php

declare(strict_types=1);

namespace App\Contracts\Container;

/**
 * Container interface for dependency injection.
 */
interface ContainerInterface
{
    /**
     * Register a binding in the container.
     *
     * @param string $abstract The abstract type (class or interface)
     * @param \Closure|object|string $concrete The concrete implementation
     * @param bool $singleton Whether to treat as singleton
     */
    public function bind(string $abstract, \Closure|object|string $concrete, bool $singleton = false): void;

    /**
     * Register a singleton binding.
     *
     * @param string $abstract The abstract type
     * @param \Closure|object|string $concrete The concrete implementation
     */
    public function singleton(string $abstract, \Closure|object|string $concrete): void;

    /**
     * Register a transient binding.
     *
     * @param string $abstract The abstract type
     * @param \Closure|object|string $concrete The concrete implementation
     */
    public function transient(string $abstract, \Closure|object|string $concrete): void;

    /**
     * Register an existing instance.
     *
     * @param string $abstract The abstract type
     * @param object $instance The instance to register
     */
    public function instance(string $abstract, object $instance): void;

    /**
     * Resolve a binding from the container.
     *
     * @param string $abstract The abstract type
     * @return object The resolved instance
     * @throws \InvalidArgumentException If binding not found
     */
    public function get(string $abstract): object;

    /**
     * Check if a binding exists.
     */
    public function has(string $abstract): bool;

    /**
     * Resolve a class with dependency injection.
     *
     * @param string $class The class to resolve
     * @return object The resolved instance
     */
    public function resolve(string $class): object;

    /**
     * Call a callable with dependency injection.
     *
     * @param callable $callable The callable to invoke
     * @param array<string, mixed> $parameters Optional parameters
     * @return mixed The result of the callable
     */
    public function call(callable $callable, array $parameters = []): mixed;
}
