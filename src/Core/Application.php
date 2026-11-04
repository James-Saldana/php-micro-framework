<?php

declare(strict_types=1);

namespace App\Core;

use App\Contracts\Http\Middleware\MiddlewareInterface;
use App\Core\Container\Container;
use App\Core\Router\Router;
use App\Exceptions\Handler as ExceptionHandler;
use App\Http\Request;
use App\Http\Response;
use Closure;

/**
 * The application class.
 *
 * This is the main entry point for the framework, providing a clean interface
 * for configuring and running the application.
 */
final class Application
{
    private Router $router;
    private Container $container;
    private ExceptionHandler $exceptionHandler;
    private bool $booted = false;
    /** @var array<callable> */
    private array $bootstrappers = [];
    /** @var array<MiddlewareInterface|callable|string> */
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
     * Add global middleware.
     *
     * @param MiddlewareInterface|callable|string ...$middleware
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
     * @param string $abstract
     * @param Closure|object|string $concrete
     * @param bool $singleton
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
     * @param string $abstract
     * @param Closure|object|string $concrete
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
     * @param string $abstract
     * @param Closure|object|string $concrete
     */
    public function transient(string $abstract, Closure|object|string $concrete): self
    {
        $this->container->transient($abstract, $concrete);
        return $this;
    }

    /**
     * Register an exception handler.
     *
     * @template T of \Throwable
     * @param class-string<T> $exceptionClass
     * @param callable(T, Request): Response $handler
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
     */
    public function get(string $path, array|callable $handlers): self
    {
        $this->router->get($path, $handlers);
        return $this;
    }

    /**
     * Register a POST route.
     */
    public function post(string $path, array|callable $handlers): self
    {
        $this->router->post($path, $handlers);
        return $this;
    }

    /**
     * Register a PUT route.
     */
    public function put(string $path, array|callable $handlers): self
    {
        $this->router->put($path, $handlers);
        return $this;
    }

    /**
     * Register a PATCH route.
     */
    public function patch(string $path, array|callable $handlers): self
    {
        $this->router->patch($path, $handlers);
        return $this;
    }

    /**
     * Register a DELETE route.
     */
    public function delete(string $path, array|callable $handlers): self
    {
        $this->router->delete($path, $handlers);
        return $this;
    }

    /**
     * Register an OPTIONS route.
     */
    public function options(string $path, array|callable $handlers): self
    {
        $this->router->options($path, $handlers);
        return $this;
    }

    /**
     * Register a HEAD route.
     */
    public function head(string $path, array|callable $handlers): self
    {
        $this->router->head($path, $handlers);
        return $this;
    }

    /**
     * Register a route matching multiple HTTP methods.
     *
     * @param array<\App\Enums\HttpMethod> $methods
     */
    public function match(array $methods, string $path, array|callable $handlers): self
    {
        $this->router->match($methods, $path, $handlers);
        return $this;
    }

    /**
     * Register a route matching all HTTP methods.
     */
    public function any(string $path, array|callable $handlers): self
    {
        $this->router->any($path, $handlers);
        return $this;
    }

    /**
     * Create a route group.
     */
    public function group(string $prefix, array $middleware = [], Closure $callback): self
    {
        $this->router->group($prefix, $middleware, $callback);
        return $this;
    }

    /**
     * Boot the application.
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
     * Handle an incoming request.
     */
    public function handle(Request $request): Response
    {
        $this->boot();

        try {
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

            $routerHandler = function (Request $request) use ($pipeline): Response {
                return $this->router->dispatch($request, $this->container);
            };

            $response = $pipeline->handle($request, $routerHandler);

        } catch (\Throwable $exception) {
            $response = $this->exceptionHandler->handleException($exception, $request);
        }

        return $response;
    }

    /**
     * Run the application.
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
     * @return array<\App\Core\Router\Route>
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
