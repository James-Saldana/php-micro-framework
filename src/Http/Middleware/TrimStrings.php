<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Contracts\Http\Middleware\MiddlewareInterface;
use App\Http\Request;
use App\Http\Response;

/**
 * Middleware to trim strings from request input.
 */
final class TrimStrings implements MiddlewareInterface
{
    /**
     * Handle the request.
     */
    public function handle(Request $request, callable $next): Response
    {
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();

        // Trim all string values in parsed body
        if (!empty($parsedBody)) {
            $parsedBody = $this->trimArray($parsedBody);
        }

        // Trim all string values in query params
        if (!empty($queryParams)) {
            $queryParams = $this->trimArray($queryParams);
        }

        // Create new request with trimmed values
        $request = new Request(
            $request->getMethod(),
            $request->getUri(),
            $request->getQueryString(),
            $queryParams,
            $request->getHeaders(),
            $request->getServer(),
            $request->getBody(),
            $parsedBody,
            $request->getAttributes()
        );

        return $next($request);
    }

    /**
     * Recursively trim strings in an array.
     *
     * @param array<string, mixed> $array
     * @return array<string, mixed>
     */
    private function trimArray(array $array): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            if (is_string($value)) {
                $result[$key] = trim($value);
            } elseif (is_array($value)) {
                $result[$key] = $this->trimArray($value);
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }
}
