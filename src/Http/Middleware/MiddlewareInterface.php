<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Request;
use App\Http\Response;

/**
 * Middleware interface for the onion-style middleware pipeline.
 *
 * Each middleware must implement the handle method which receives
 * the request, the next middleware in the pipeline, and returns a response.
 *
 * The middleware can:
 * - Process the request before calling $next
 * - Process the response after calling $next
 * - Short-circuit the pipeline by returning a response without calling $next
 */
interface MiddlewareInterface
{
    /**
     * Handle the request and return a response.
     *
     * @param Request $request The incoming HTTP request
     * @param callable $next The next middleware in the pipeline
     * @return Response The HTTP response
     */
    public function handle(Request $request, callable $next): Response;
}
