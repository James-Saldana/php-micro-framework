<?php

declare(strict_types=1);

namespace App\Core;

use App\Http\Request;
use App\Http\Response;
use Closure;

/**
 * Main application class that wires the framework together.
 *
 * This class provides a clean, object-oriented interface for:
 * - Configuring the application
 * - Registering routes and middleware
 * - Handling HTTP requests
 * - Managing the dependency injection container
 * - Centralized error handling
 */
final class App
{
    private Router $router;
    private Container $container;
    private ExceptionHandler $exceptionHandler;
    private bool $booted = false;
    /** @var array<callable> */
    private array $bootstrappers = [];
    /** @var array<MiddlewareInterface|callable|class-string> */
    private array $globalMiddleware = [];

    public function __construct()
    {
        $this->router = new Router();
        $this->container = new Container();
        $this->exceptionHandler = new ExceptionHandler();
    }

    /**
     * Get the router instance.
     */
    public function router(): Router
    {
        return $this->router;
    }

    /**
     * Get the container instance.
     */
    public function container(): Container
    {
        return $this->container;
    }

    /**
     * Get the exception handler instance.
     */
    public function exceptionHandler(): ExceptionHandler
    {
        return $this->exceptionHandler;
    }

    /**
     * Register a bootstrapper to run before handling requests.
     */
    public function bootstrap(callable $bootstrapper): self
    {
        $this->bootstrappers[] = $bootstrapper;
        return $this;
    }

    /**
     * Add global middleware that runs on every request.
     *
     * @param MiddlewareInterface|callable|class-string ...$middleware
     */
    public function middleware(MiddlewareInterface|callable|string ...$middleware): self
    {
        $this->globalMiddleware = array_merge($this->globalMiddleware, $middleware);
        return $this;
    }

    /**
     * Register a service in the container.
     *
     * @template T of object
     * @param string $abstract The service identifier
     * @param Closure|object|class-string<T> $concrete The service factory or instance
     * @param bool $singleton Whether the service should be a singleton
     */
    public function bind(string $abstract, Closure|object|string $concrete, bool $singleton = false): self
    {
        $this->container->bind($abstract, $concrete, $singleton);
        return $this;
    }

    /**
     * Register a singleton service.
     *
     * @template T of object
     * @param string $abstract The service identifier
     * @param Closure|object|class-string<T> $concrete The service factory or instance
     */
    public function singleton(string $abstract, Closure|object|string $concrete): self
    {
        $this->container->singleton($abstract, $concrete);
        return $this;
    }

    /**
     * Register a transient service.
     *
     * @template T of object
     * @param string $abstract The service identifier
     * @param Closure|object|class-string<T> $concrete The service factory or instance
     */
    public function transient(string $abstract, Closure|object|string $concrete): self
    {
        $this->container->transient($abstract, $concrete);
        return $this;
    }

    /**
     * Register an exception handler for a specific exception type.
     *
     * @template T of \Throwable
     * @param class-string<T> $exceptionClass The exception class to handle
     * @param callable(T, Request): Response $handler The handler function
     */
    public function handle(string $exceptionClass, callable $handler): self
    {
        $this->exceptionHandler->handle($exceptionClass, $handler);
        return $this;
    }

    /**
     * Set the application environment.
     */
    public function environment(string $environment): self
    {
        $this->exceptionHandler->setEnvironment($environment);
        return $this;
    }

    /**
     * Enable or disable debug mode.
     */
    public function debug(bool $debug = true): self
    {
        $this->exceptionHandler->setDebug($debug);
        return $this;
    }

    /**
     * Register a GET route.
     *
     * @param string $path The route path
     * @param array<MiddlewareInterface|callable|class-string>|callable|array{0: callable, 1: array} $handlers Handler or [handler, middleware]
     */
    public function get(string $path, ...$handlers): self
    {
        $this->router->get($path, ...$handlers);
        return $this;
    }

    /**
     * Register a POST route.
     *
     * @param string $path The route path
     * @param array<MiddlewareInterface|callable|class-string>|callable|array{0: callable, 1: array} $handlers Handler or [handler, middleware]
     */
    public function post(string $path, ...$handlers): self
    {
        $this->router->post($path, ...$handlers);
        return $this;
    }

    /**
     * Register a PUT route.
     *
     * @param string $path The route path
     * @param array<MiddlewareInterface|callable|class-string>|callable|array{0: callable, 1: array} $handlers Handler or [handler, middleware]
     */
    public function put(string $path, ...$handlers): self
    {
        $this->router->put($path, ...$handlers);
        return $this;
    }

    /**
     * Register a PATCH route.
     *
     * @param string $path The route path
     * @param array<MiddlewareInterface|callable|class-string>|callable|array{0: callable, 1: array} $handlers Handler or [handler, middleware]
     */
    public function patch(string $path, ...$handlers): self
    {
        $this->router->patch($path, ...$handlers);
        return $this;
    }

    /**
     * Register a DELETE route.
     *
     * @param string $path The route path
     * @param array<MiddlewareInterface|callable|class-string>|callable|array{0: callable, 1: array} $handlers Handler or [handler, middleware]
     */
    public function delete(string $path, ...$handlers): self
    {
        $this->router->delete($path, ...$handlers);
        return $this;
    }

    /**
     * Register an OPTIONS route.
     *
     * @param string $path The route path
     * @param array<MiddlewareInterface|callable|class-string>|callable|array{0: callable, 1: array} $handlers Handler or [handler, middleware]
     */
    public function options(string $path, ...$handlers): self
    {
        $this->router->options($path, ...$handlers);
        return $this;
    }

    /**
     * Register a HEAD route.
     *
     * @param string $path The route path
     * @param array<MiddlewareInterface|callable|class-string>|callable|array{0: callable, 1: array} $handlers Handler or [handler, middleware]
     */
    public function head(string $path, ...$handlers): self
    {
        $this->router->head($path, ...$handlers);
        return $this;
    }

    /**
     * Register a route that matches multiple HTTP methods.
     *
     * @param array<\App\Enums\HttpMethod> $methods The HTTP methods to match
     * @param string $path The route path
     * @param array<MiddlewareInterface|callable|class-string>|callable|array{0: callable, 1: array} $handlers Handler or [handler, middleware]
     */
    public function match(array $methods, string $path, ...$handlers): self
    {
        $this->router->match($methods, $path, ...$handlers);
        return $this;
    }

    /**
     * Register a route that matches all HTTP methods.
     *
     * @param string $path The route path
     * @param array<MiddlewareInterface|callable|class-string>|callable|array{0: callable, 1: array} $handlers Handler or [handler, middleware]
     */
    public function any(string $path, ...$handlers): self
    {
        $this->router->any($path, ...$handlers);
        return $this;
    }

    /**
     * Create a route group.
     *
     * @param string $prefix The URI prefix for all routes in the group
     * @param array<MiddlewareInterface|callable|class-string> $middleware Middleware for the group
     * @param Closure $callback Callback to define routes within the group
     */
    public function group(string $prefix, array $middleware = [], Closure $callback = null): self
    {
        $this->router->group($prefix, $middleware, $callback);
        return $this;
    }

    /**
     * Boot the application (run bootstrappers).
     */
    public function boot(): self
    {
        if ($this->booted) {
            return $this;
        }

        foreach ($this->bootstrappers as $bootstrapper) {
            $this->container->call($bootstrapper);
        }

        $this->booted = true;
        return $this;
    }

    /**
     * Handle an incoming request and return a response.
     *
     * @param Request $request The incoming request
     * @return Response The response
     */
    public function handle(Request $request): Response
    {
        $this->boot();

        try {
            // Apply global middleware
            $pipeline = new \App\Http\Middleware\MiddlewarePipeline();

            foreach ($this->globalMiddleware as $middleware) {
                if (is_string($middleware)) {
                    if ($this->container->has($middleware)) {
                        $middleware = $this->container->get($middleware);
                    } else {
                        $middleware = new $middleware();
                    }
                }
                $pipeline->add($middleware);
            }

            // Create the router handler
            $routerHandler = function (Request $request) use ($pipeline): Response {
                return $this->router->dispatch($request, $this->container);
            };

            // Handle with global middleware
            $response = $pipeline->handle($request, $routerHandler);

        } catch (\Throwable $exception) {
            $response = $this->exceptionHandler->handleException($exception, $request);
        }

        return $response;
    }

    /**
     * Run the application (for CLI or direct invocation).
     *
     * This method creates a request from superglobals, handles it,
     * and sends the response.
     */
    public function run(): void
    {
        $this->boot();

        try {
            $request = Request::fromGlobals();
            $response = $this->handle($request);
            $response->send();
        } catch (\Throwable $exception) {
            $request = Request::fromGlobals();
            $response = $this->exceptionHandler->handleException($exception, $request);
            $response->send();
        }
    }

    /**
     * Register PHP error and exception handlers.
     */
    public function registerHandlers(): self
    {
        $this->exceptionHandler->register();
        return $this;
    }

    /**
     * Get all registered routes.
     *
     * @return array<Route>
     */
    public function routes(): array
    {
        return $this->router->getRoutes();
    }

    /**
     * Check if the application is booted.
     */
    public function isBooted(): bool
    {
        return $this->booted;
    }
}
