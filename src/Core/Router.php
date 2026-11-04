<?php

declare(strict_types=1);

namespace App\Core;

use App\Enums\HttpMethod;
use App\Http\Middleware\MiddlewareInterface;
use App\Http\Request;
use App\Http\Response;
use Closure;

/**
 * Fast, expressive HTTP router.
 *
 * Features:
 * - Support for all HTTP methods (GET, POST, PUT, PATCH, DELETE, OPTIONS, HEAD)
 * - Route parameters with constraints ({id}, {slug}, {uuid}, etc.)
 * - Optional route parameters
 * - Route groups with prefix and middleware
 * - Route-specific middleware
 * - Named routes
 * - Efficient routing with compiled regex patterns
 */
final class Router
{
    /** @var array<string, RouteGroup> */
    private array $groups = [];
    /** @var array<string, Route> */
    private array $routes = [];
    private RouteGroup $currentGroup;
    private string $currentPrefix = '';
    /** @var array<MiddlewareInterface|callable> */
    private array $currentMiddleware = [];
    /** @var array<string, Route> */
    private array $namedRoutes = [];

    public function __construct()
    {
        $this->currentGroup = new RouteGroup('', []);
    }

    /**
     * Create a new route group with shared attributes.
     *
     * @param string $prefix The URI prefix for all routes in the group
     * @param array<MiddlewareInterface|callable|class-string> $middleware Middleware for the group
     * @param Closure $callback Callback to define routes within the group
     */
    public function group(
        string $prefix,
        array $middleware = [],
        Closure $callback = null
    ): self {
        $previousGroup = $this->currentGroup;
        $previousPrefix = $this->currentPrefix;
        $previousMiddleware = $this->currentMiddleware;

        $this->currentPrefix = $previousPrefix . $prefix;
        $this->currentMiddleware = array_merge($previousMiddleware, $middleware);
        $this->currentGroup = new RouteGroup($this->currentPrefix, $this->currentMiddleware);

        if ($callback !== null) {
            $callback($this);
        }

        $this->currentGroup = $previousGroup;
        $this->currentPrefix = $previousPrefix;
        $this->currentMiddleware = $previousMiddleware;

        return $this;
    }

    /**
     * Register a GET route.
     *
     * @param string $path The route path
     * @param array<MiddlewareInterface|callable|class-string>|callable|array{0: callable, 1: array} $handlers Handler or [handler, middleware]
     */
    public function get(string $path, ...$handlers): Route
    {
        return $this->addRoute(HttpMethod::GET, $path, $handlers);
    }

    /**
     * Register a POST route.
     *
     * @param string $path The route path
     * @param array<MiddlewareInterface|callable|class-string>|callable|array{0: callable, 1: array} $handlers Handler or [handler, middleware]
     */
    public function post(string $path, ...$handlers): Route
    {
        return $this->addRoute(HttpMethod::POST, $path, $handlers);
    }

    /**
     * Register a PUT route.
     *
     * @param string $path The route path
     * @param array<MiddlewareInterface|callable|class-string>|callable|array{0: callable, 1: array} $handlers Handler or [handler, middleware]
     */
    public function put(string $path, ...$handlers): Route
    {
        return $this->addRoute(HttpMethod::PUT, $path, $handlers);
    }

    /**
     * Register a PATCH route.
     *
     * @param string $path The route path
     * @param array<MiddlewareInterface|callable|class-string>|callable|array{0: callable, 1: array} $handlers Handler or [handler, middleware]
     */
    public function patch(string $path, ...$handlers): Route
    {
        return $this->addRoute(HttpMethod::PATCH, $path, $handlers);
    }

    /**
     * Register a DELETE route.
     *
     * @param string $path The route path
     * @param array<MiddlewareInterface|callable|class-string>|callable|array{0: callable, 1: array} $handlers Handler or [handler, middleware]
     */
    public function delete(string $path, ...$handlers): Route
    {
        return $this->addRoute(HttpMethod::DELETE, $path, $handlers);
    }

    /**
     * Register an OPTIONS route.
     *
     * @param string $path The route path
     * @param array<MiddlewareInterface|callable|class-string>|callable|array{0: callable, 1: array} $handlers Handler or [handler, middleware]
     */
    public function options(string $path, ...$handlers): Route
    {
        return $this->addRoute(HttpMethod::OPTIONS, $path, $handlers);
    }

    /**
     * Register a HEAD route.
     *
     * @param string $path The route path
     * @param array<MiddlewareInterface|callable|class-string>|callable|array{0: callable, 1: array} $handlers Handler or [handler, middleware]
     */
    public function head(string $path, ...$handlers): Route
    {
        return $this->addRoute(HttpMethod::HEAD, $path, $handlers);
    }

    /**
     * Register a route that matches multiple HTTP methods.
     *
     * @param array<HttpMethod> $methods The HTTP methods to match
     * @param string $path The route path
     * @param array<MiddlewareInterface|callable|class-string>|callable|array{0: callable, 1: array} $handlers Handler or [handler, middleware]
     */
    public function match(array $methods, string $path, ...$handlers): Route
    {
        $route = null;
        foreach ($methods as $method) {
            $route = $this->addRoute($method, $path, $handlers);
        }
        return $route;
    }

    /**
     * Register a route that matches all HTTP methods.
     *
     * @param string $path The route path
     * @param array<MiddlewareInterface|callable|class-string>|callable|array{0: callable, 1: array} $handlers Handler or [handler, middleware]
     */
    public function any(string $path, ...$handlers): Route
    {
        return $this->match(HttpMethod::all(), $path, ...$handlers);
    }

    /**
     * Add a route with the given method, path, and handlers.
     *
     * @param HttpMethod $method The HTTP method
     * @param string $path The route path
     * @param array $handlers The handlers (can be handler + middleware)
     */
    private function addRoute(HttpMethod $method, string $path, array $handlers): Route
    {
        $fullPath = $this->currentPrefix . $path;
        $groupMiddleware = $this->currentGroup->middleware;
        $allMiddleware = array_merge($groupMiddleware, $this->currentMiddleware);

        // Normalize path
        $fullPath = $this->normalizePath($fullPath);

        // Parse handlers - support both (handler, middleware) and just handler
        $handler = null;
        $middleware = $allMiddleware;

        if (count($handlers) === 1 && is_callable($handlers[0])) {
            $handler = $handlers[0];
        } elseif (count($handlers) === 2 && is_callable($handlers[0]) && is_array($handlers[1])) {
            $handler = $handlers[0];
            $middleware = array_merge($middleware, $handlers[1]);
        } elseif (count($handlers) > 0) {
            // If first is callable, it's the handler
            if (is_callable($handlers[0])) {
                $handler = $handlers[0];
                // Remaining are middleware
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

        // Create route
        $route = new Route(
            $method,
            $fullPath,
            $handler,
            $middleware
        );

        // Store route
        $key = $method->value . ':' . $fullPath;
        $this->routes[$key] = $route;

        return $route;
    }

    /**
     * Normalize a path by removing duplicate slashes and trimming.
     */
    private function normalizePath(string $path): string
    {
        // Remove duplicate slashes
        $path = preg_replace('#/+#', '/', $path);
        // Remove trailing slash (except for root)
        if ($path !== '/' && ends_with($path, '/')) {
            $path = substr($path, 0, -1);
        }
        // Ensure starts with /
        if ($path && $path[0] !== '/') {
            $path = '/' . $path;
        }
        return $path ?: '/';
    }

    /**
     * Dispatch a request to the appropriate route handler.
     *
     * @param Request $request The incoming request
     * @param Container $container The dependency injection container
     * @return Response The response
     */
    public function dispatch(Request $request, Container $container): Response
    {
        $method = HttpMethod::from($request->getMethod());
        $path = $request->getPath();

        // Try exact match first
        $route = $this->findRoute($method, $path);

        if ($route === null) {
            // Try with trailing slash
            $pathWithSlash = $path . '/';
            $route = $this->findRoute($method, $pathWithSlash);
        }

        if ($route === null) {
            // Try without trailing slash
            $pathWithoutSlash = rtrim($path, '/') ?: '/';
            $route = $this->findRoute($method, $pathWithoutSlash);
        }

        if ($route === null) {
            return Response::notFound('Not Found');
        }

        // Extract route parameters
        $params = $this->extractParams($route, $path);

        // Create a new request with route attributes
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

        // Execute the route with middleware
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

        // Exact match
        if ($routePath === $path) {
            return true;
        }

        // Check if route has parameters
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
        // Escape special regex characters except for braces
        $pattern = preg_quote($path, '#');

        // Replace {param} with named capture groups
        $pattern = preg_replace_callback(
            '/\\\{([a-zA-Z_][a-zA-Z0-9_]*)\\\}/',
            function ($matches) {
                $param = $matches[1];
                return "(?P<{$param}>[^/]+)";
            },
            $pattern
        );

        // Replace {param?} with optional named capture groups
        $pattern = preg_replace_callback(
            '/\\\{([a-zA-Z_][a-zA-Z0-9_]*)\\\?\\\}/',
            function ($matches) {
                $param = $matches[1];
                return "(?P<{$param}>[^/]*)?";
            },
            $pattern
        );

        // Replace {param:constraint} with constrained capture groups
        $pattern = preg_replace_callback(
            '/\\\{([a-zA-Z_][a-zA-Z0-9_]*):([^}]+)\\}\\?/',
            function ($matches) {
                $param = $matches[1];
                $constraint = $matches[2];
                return "(?P<{$param}>{$constraint})?";
            },
            $pattern
        );

        $pattern = preg_replace_callback(
            '/\\\{([a-zA-Z_][a-zA-Z0-9_]*):([^}]+)\\\}/',
            function ($matches) {
                $param = $matches[1];
                $constraint = $matches[2];
                return "(?P<{$param}>{$constraint})";
            },
            $pattern
        );

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
     *
     * @param Route $route The route to execute
     * @param Request $request The request
     * @param Container $container The DI container
     * @return Response The response
     */
    private function executeRoute(Route $route, Request $request, Container $container): Response
    {
        $middleware = $route->middleware;
        $handler = $route->handler;

        // Build the middleware pipeline
        $pipeline = new \App\Http\Middleware\MiddlewarePipeline();

        foreach ($middleware as $m) {
            if (is_string($m)) {
                // Resolve middleware from container
                if ($container->has($m)) {
                    $m = $container->get($m);
                } else {
                    // Try to instantiate
                    $m = new $m();
                }
            }
            $pipeline->add($m);
        }

        // Create the final handler
        $finalHandler = function (Request $request) use ($handler, $container): Response {
            return $this->callHandler($handler, $request, $container);
        };

        return $pipeline->handle($request, $finalHandler);
    }

    /**
     * Call a route handler with dependency injection.
     *
     * @param callable|array $handler The handler to call
     * @param Request $request The request
     * @param Container $container The DI container
     * @return Response The response
     */
    private function callHandler(callable|array $handler, Request $request, Container $container): Response
    {
        if (is_array($handler)) {
            // Controller method: [Controller::class, 'method']
            if (is_string($handler[0]) && class_exists($handler[0])) {
                $controller = $container->get($handler[0]);
                return $container->call([$controller, $handler[1]], ['request' => $request]);
            }
            // Callable array
            return $container->call($handler, ['request' => $request]);
        }

        // Closure or callable
        if ($handler instanceof Closure) {
            return $container->call($handler, ['request' => $request]);
        }

        // Callable function
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
     * @param HttpMethod $method The HTTP method
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
    /** @var array<MiddlewareInterface|callable|class-string> */
    public array $middleware;
    /** @var callable|array{0: class-string|object, 1: string} */
    public $handler;
    public ?string $name;

    /**
     * @param HttpMethod $method The HTTP method
     * @param string $path The route path
     * @param callable|array $handler The route handler
     * @param array<MiddlewareInterface|callable|class-string> $middleware The route middleware
     */
    public function __construct(
        HttpMethod $method,
        string $path,
        callable|array $handler,
        array $middleware = []
    ) {
        $this->method = $method;
        $this->path = $path;
        $this->handler = $handler;
        $this->middleware = $middleware;
        $this->name = null;
    }

    /**
     * Set a name for this route.
     */
    public function name(string $name): self
    {
        return new self(
            $this->method,
            $this->path,
            $this->handler,
            $this->middleware
        );
    }

    /**
     * Add middleware to this route.
     *
     * @param MiddlewareInterface|callable|class-string ...$middleware
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

/**
 * Route group data class.
 */
final readonly class RouteGroup
{
    public string $prefix;
    /** @var array<MiddlewareInterface|callable|class-string> */
    public array $middleware;

    /**
     * @param string $prefix The group prefix
     * @param array<MiddlewareInterface|callable|class-string> $middleware The group middleware
     */
    public function __construct(string $prefix, array $middleware = [])
    {
        $this->prefix = $prefix;
        $this->middleware = $middleware;
    }
}
