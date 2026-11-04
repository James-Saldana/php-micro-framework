<?php

declare(strict_types=1);

namespace App\Contracts\Http\Middleware;

use App\Http\Request;
use App\Http\Response;

/**
 * Middleware interface for the pipeline.
 */
interface MiddlewareInterface
{
    /**
     * Process the request and return a response.
     *
     * @param Request $request The incoming request
     * @param callable $next The next middleware in the pipeline
     * @return Response The response
     */
    public function handle(Request $request, callable $next): Response;
}
