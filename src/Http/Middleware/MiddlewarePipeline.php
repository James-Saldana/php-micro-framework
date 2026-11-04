<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Contracts\Http\Middleware\MiddlewareInterface;
use App\Http\Request;
use App\Http\Response;
use Closure;

/**
 * Onion-style middleware pipeline.
 */
final class MiddlewarePipeline
{
    /** @var array<MiddlewareInterface|callable> */
    private array $middleware = [];

    /**
     * Add a middleware to the pipeline.
     */
    public function add(MiddlewareInterface|callable $middleware): self
    {
        $this->middleware[] = $middleware;
        return $this;
    }

    /**
     * Prepend a middleware to the pipeline.
     */
    public function prepend(MiddlewareInterface|callable $middleware): self
    {
        array_unshift($this->middleware, $middleware);
        return $this;
    }

    /**
     * Get all middleware.
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
     * Execute the middleware pipeline.
     */
    public function handle(Request $request, Closure $handler): Response
    {
        $next = $handler;

        foreach (array_reverse($this->middleware) as $middleware) {
            $next = $this->wrapMiddleware($middleware, $next);
        }

        return $next($request);
    }

    /**
     * Wrap a middleware around the next handler.
     */
    private function wrapMiddleware(MiddlewareInterface|callable $middleware, callable $next): callable
    {
        return function (Request $request) use ($middleware, $next): Response {
            if ($middleware instanceof MiddlewareInterface) {
                return $middleware->handle($request, $next);
            }
            return $middleware($request, $next);
        };
    }

    /**
     * Create and run a pipeline with the given middleware.
     *
     * @param array<MiddlewareInterface|callable> $middleware
     * @param Request $request
     * @param Closure $handler
     * @return Response
     */
    public static function run(array $middleware, Request $request, Closure $handler): Response
    {
        $pipeline = new self();
        foreach ($middleware as $m) {
            $pipeline->add($m);
        }
        return $pipeline->handle($request, $handler);
    }
}
