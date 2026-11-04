<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Contracts\Http\Middleware\MiddlewareInterface;
use App\Http\Request;
use App\Http\Response;

/**
 * Middleware to convert empty strings to null in request input.
 */
final class ConvertEmptyStringsToNull implements MiddlewareInterface
{
    /**
     * Handle the request.
     */
    public function handle(Request $request, callable $next): Response
    {
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();

        if (!empty($parsedBody)) {
            $parsedBody = $this->convertEmptyToNull($parsedBody);
        }

        if (!empty($queryParams)) {
            $queryParams = $this->convertEmptyToNull($queryParams);
        }

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
     * Recursively convert empty strings to null in an array.
     *
     * @param array<string, mixed> $array
     * @return array<string, mixed>
     */
    private function convertEmptyToNull(array $array): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            if (is_string($value) && $value === '') {
                $result[$key] = null;
            } elseif (is_array($value)) {
                $result[$key] = $this->convertEmptyToNull($value);
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }
}
