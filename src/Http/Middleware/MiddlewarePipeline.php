<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Request;
use App\Http\Response;
use Closure;

/**
 * Onion-style middleware pipeline.
 *
 * This class manages the execution of middleware in an onion-style pattern,
 * where each middleware wraps the next one. The request flows through
 * the middleware stack, and the response flows back through the same stack.
 */
final class MiddlewarePipeline
{
    /** @var array<MiddlewareInterface|callable> */
    private array $middleware = [];

    /**
     * Add a middleware to the pipeline.
     *
     * @param MiddlewareInterface|callable $middleware The middleware to add
     */
    public function add(MiddlewareInterface|callable $middleware): self
    {
        $this->middleware[] = $middleware;
        return $this;
    }

    /**
     * Prepend a middleware to the pipeline (executed first).
     *
     * @param MiddlewareInterface|callable $middleware The middleware to prepend
     */
    public function prepend(MiddlewareInterface|callable $middleware): self
    {
        array_unshift($this->middleware, $middleware);
        return $this;
    }

    /**
     * Get all middleware in the pipeline.
     *
     * @return array<MiddlewareInterface|callable>
     */
    public function all(): array
    {
        return $this->middleware;
    }

    /**
     * Clear all middleware.
     */
    public function clear(): self
    {
        $this->middleware = [];
        return $this;
    }

    /**
     * Execute the middleware pipeline with the given request and handler.
     *
     * @param Request $request The incoming request
     * @param Closure $handler The final handler to execute if no middleware short-circuits
     * @return Response The response after processing through the pipeline
     */
    public function handle(Request $request, Closure $handler): Response
    {
        // Build the pipeline from the inside out (onion style)
        $next = $handler;

        // Iterate through middleware in reverse order
        foreach (array_reverse($this->middleware) as $middleware) {
            $next = $this->wrapMiddleware($middleware, $next);
        }

        return $next($request);
    }

    /**
     * Wrap a middleware around the next handler.
     *
     * @param MiddlewareInterface|callable $middleware The middleware to wrap
     * @param callable $next The next handler in the pipeline
     * @return callable The wrapped handler
     */
    private function wrapMiddleware(MiddlewareInterface|callable $middleware, callable $next): callable
    {
        return function (Request $request) use ($middleware, $next): Response {
            if ($middleware instanceof MiddlewareInterface) {
                return $middleware->handle($request, $next);
            }

            // Support for callable middleware (simple closure-style)
            return $middleware($request, $next);
        };
    }

    /**
     * Create a pipeline with the given middleware and execute it.
     *
     * @param array<MiddlewareInterface|callable> $middleware Array of middleware
     * @param Request $request The incoming request
     * @param Closure $handler The final handler
     * @return Response The response
     */
    public static function run(
        array $middleware,
        Request $request,
        Closure $handler
    ): Response {
        $pipeline = new self();
        foreach ($middleware as $m) {
            $pipeline->add($m);
        }
        return $pipeline->handle($request, $handler);
    }
}
