<?php

declare(strict_types=1);

namespace App\Core\Router;

use App\Core\Container\Container;
use App\Contracts\Http\Middleware\MiddlewareInterface;
use App\Enums\HttpMethod;
use App\Http\Request;
use App\Http\Response;
use Closure;

/**
 * Fast, expressive HTTP router.
 */
final class Router
{
    /** @var array<string, Route> */
    private array $routes = [];
    private string $currentPrefix = '';
    /** @var array<MiddlewareInterface|callable|string> */
    private array $currentMiddleware = [];
    /** @var array<string, Route> */
    private array $namedRoutes = [];

    /**
     * Create a route group with shared attributes.
     *
     * @param string $prefix The URI prefix
     * @param array<MiddlewareInterface|callable|string> $middleware Middleware for the group
     * @param Closure(Router): void $callback Callback to define routes
     */
    public function group(string $prefix, array $middleware = [], Closure $callback): self
    {
        $previousPrefix = $this->currentPrefix;
        $previousMiddleware = $this->currentMiddleware;

        $this->currentPrefix = $previousPrefix . $prefix;
        $this->currentMiddleware = array_merge($previousMiddleware, $middleware);

        $callback($this);

        $this->currentPrefix = $previousPrefix;
        $this->currentMiddleware = $previousMiddleware;

        return $this;
    }

    /**
     * Register a GET route.
     */
    public function get(string $path, array|callable $handlers): Route
    {
        return $this->addRoute(HttpMethod::GET, $path, $handlers);
    }

    /**
     * Register a POST route.
     */
    public function post(string $path, array|callable $handlers): Route
    {
        return $this->addRoute(HttpMethod::POST, $path, $handlers);
    }

    /**
     * Register a PUT route.
     */
    public function put(string $path, array|callable $handlers): Route
    {
        return $this->addRoute(HttpMethod::PUT, $path, $handlers);
    }

    /**
     * Register a PATCH route.
     */
    public function patch(string $path, array|callable $handlers): Route
    {
        return $this->addRoute(HttpMethod::PATCH, $path, $handlers);
    }

    /**
     * Register a DELETE route.
     */
    public function delete(string $path, array|callable $handlers): Route
    {
        return $this->addRoute(HttpMethod::DELETE, $path, $handlers);
    }

    /**
     * Register an OPTIONS route.
     */
    public function options(string $path, array|callable $handlers): Route
    {
        return $this->addRoute(HttpMethod::OPTIONS, $path, $handlers);
    }

    /**
     * Register a HEAD route.
     */
    public function head(string $path, array|callable $handlers): Route
    {
        return $this->addRoute(HttpMethod::HEAD, $path, $handlers);
    }

    /**
     * Register a route matching multiple HTTP methods.
     *
     * @param array<HttpMethod> $methods
     */
    public function match(array $methods, string $path, array|callable $handlers): Route
    {
        $route = null;
        foreach ($methods as $method) {
            $route = $this->addRoute($method, $path, $handlers);
        }
        return $route;
    }

    /**
     * Register a route matching all HTTP methods.
     */
    public function any(string $path, array|callable $handlers): Route
    {
        return $this->match(HttpMethod::all(), $path, $handlers);
    }

    /**
     * Add a route with the given method, path, and handlers.
     *
     * @param HttpMethod $method
     * @param array|callable $handlers
     */
    private function addRoute(HttpMethod $method, string $path, array|callable $handlers): Route
    {
        $fullPath = $this->currentPrefix . $path;
        $middleware = $this->currentMiddleware;

        $fullPath = $this->normalizePath($fullPath);

        $handler = null;

        if (is_callable($handlers)) {
            $handler = $handlers;
        } elseif (is_array($handlers) && count($handlers) > 0) {
            if (is_callable($handlers[0])) {
                $handler = $handlers[0];
                foreach (array_slice($handlers, 1) as $m) {
                    if (is_callable($m) || $m instanceof MiddlewareInterface) {
                        $middleware[] = $m;
                    }
                }
            }
        }

        if ($handler === null) {
            throw new \InvalidArgumentException('No handler provided for route');
        }

        $route = new Route($method, $fullPath, $handler, $middleware);
        $key = $method->value . ':' . $fullPath;
        $this->routes[$key] = $route;

        return $route;
    }

    /**
     * Normalize a path.
     */
    private function normalizePath(string $path): string
    {
        $path = preg_replace('#/+#', '/', $path);
        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = substr($path, 0, -1);
        }
        if ($path && $path[0] !== '/') {
            $path = '/' . $path;
        }
        return $path ?: '/';
    }

    /**
     * Dispatch a request to the appropriate route handler.
     */
    public function dispatch(Request $request, Container $container): Response
    {
        $method = HttpMethod::from($request->getMethod());
        $path = $request->getPath();

        $route = $this->findRoute($method, $path);

        if ($route === null) {
            $pathWithSlash = $path . '/';
            $route = $this->findRoute($method, $pathWithSlash);
        }

        if ($route === null) {
            $pathWithoutSlash = rtrim($path, '/') ?: '/';
            $route = $this->findRoute($method, $pathWithoutSlash);
        }

        if ($route === null) {
            return Response::notFound('Not Found');
        }

        $params = $this->extractParams($route, $path);

        $request = new Request(
            $request->getMethod(),
            $request->getUri(),
            $request->getQueryString(),
            $request->getQueryParams(),
            $request->getHeaders(),
            $request->getServer(),
            $request->getBody(),
            $request->getParsedBody(),
            array_merge($request->getAttributes(), $params)
        );

        return $this->executeRoute($route, $request, $container);
    }

    /**
     * Find a route that matches the method and path.
     */
    private function findRoute(HttpMethod $method, string $path): ?Route
    {
        foreach ($this->routes as $route) {
            if ($route->method !== $method) {
                continue;
            }
            if ($this->routeMatches($route, $path)) {
                return $route;
            }
        }
        return null;
    }

    /**
     * Check if a route matches the given path.
     */
    private function routeMatches(Route $route, string $path): bool
    {
        $routePath = $route->path;

        if ($routePath === $path) {
            return true;
        }

        if (str_contains($routePath, '{')) {
            $pattern = $this->compileRoutePattern($routePath);
            return preg_match($pattern, $path) === 1;
        }

        return false;
    }

    /**
     * Compile a route path into a regex pattern.
     */
    private function compileRoutePattern(string $path): string
    {
        $path = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\?\}/', '{__OPTIONAL_$1__}', $path);
        $path = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', '{__REQUIRED_$1__}', $path);

        $pattern = preg_quote($path, '#');

        $pattern = str_replace('{__REQUIRED_', '(?P<', $pattern);
        $pattern = str_replace('__}', '>[^/]+)', $pattern);

        $pattern = str_replace('{__OPTIONAL_', '(?P<', $pattern);
        $pattern = str_replace('__}', '>[^/]*)?', $pattern);

        return '#^' . $pattern . '$#';
    }

    /**
     * Extract route parameters from the path.
     *
     * @return array<string, string>
     */
    private function extractParams(Route $route, string $path): array
    {
        $routePath = $route->path;

        if (!str_contains($routePath, '{')) {
            return [];
        }

        $pattern = $this->compileRoutePattern($routePath);

        if (preg_match($pattern, $path, $matches)) {
            $params = [];
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = $value;
                }
            }
            return $params;
        }

        return [];
    }

    /**
     * Execute a route with its middleware.
     */
    private function executeRoute(Route $route, Request $request, Container $container): Response
    {
        $middleware = $route->middleware;
        $handler = $route->handler;

        $pipeline = new \App\Http\Middleware\MiddlewarePipeline();

        foreach ($middleware as $m) {
            if (is_string($m)) {
                if ($container->has($m)) {
                    $m = $container->get($m);
                } else {
                    $m = new $m();
                }
            }
            $pipeline->add($m);
        }

        $finalHandler = function (Request $request) use ($handler, $container): Response {
            return $this->callHandler($handler, $request, $container);
        };

        return $pipeline->handle($request, $finalHandler);
    }

    /**
     * Call a route handler with dependency injection.
     */
    private function callHandler(callable|array $handler, Request $request, Container $container): Response
    {
        if (is_array($handler)) {
            if (is_string($handler[0]) && class_exists($handler[0])) {
                $controller = $container->get($handler[0]);
                return $container->call([$controller, $handler[1]], ['request' => $request]);
            }
            return $container->call($handler, ['request' => $request]);
        }

        if ($handler instanceof Closure) {
            return $container->call($handler, ['request' => $request]);
        }

        return $container->call($handler, ['request' => $request]);
    }

    /**
     * Get all registered routes.
     *
     * @return array<Route>
     */
    public function getRoutes(): array
    {
        return array_values($this->routes);
    }

    /**
     * Get routes by HTTP method.
     *
     * @param HttpMethod $method
     * @return array<Route>
     */
    public function getRoutesByMethod(HttpMethod $method): array
    {
        $routes = [];
        foreach ($this->routes as $route) {
            if ($route->method === $method) {
                $routes[] = $route;
            }
        }
        return $routes;
    }

    /**
     * Clear all routes.
     */
    public function clear(): self
    {
        $this->routes = [];
        $this->namedRoutes = [];
        return $this;
    }
}

/**
 * Route data class.
 */
final readonly class Route
{
    public HttpMethod $method;
    public string $path;
    /** @var array<MiddlewareInterface|callable|string> */
    public array $middleware;
    /** @var callable|array{0: class-string|object, 1: string} */
    public $handler;
    public ?string $name;

    /**
     * @param HttpMethod $method
     * @param array<MiddlewareInterface|callable|string> $middleware
     * @param callable|array $handler
     */
    public function __construct(HttpMethod $method, string $path, callable|array $handler, array $middleware = [])
    {
        $this->method = $method;
        $this->path = $path;
        $this->handler = $handler;
        $this->middleware = $middleware;
        $this->name = null;
    }

    /**
     * Set the route name.
     */
    public function name(string $name): self
    {
        return new self($this->method, $this->path, $this->handler, $this->middleware);
    }

    /**
     * Add middleware to this route.
     *
     * @param MiddlewareInterface|callable|string ...$middleware
     */
    public function withMiddleware(MiddlewareInterface|callable|string ...$middleware): self
    {
        return new self(
            $this->method,
            $this->path,
            $this->handler,
            array_merge($this->middleware, $middleware)
        );
    }
}
