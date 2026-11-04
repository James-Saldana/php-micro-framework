<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * HTTP method enumeration for type-safe route definitions.
 */
enum HttpMethod: string
{
    case GET = 'GET';
    case POST = 'POST';
    case PUT = 'PUT';
    case PATCH = 'PATCH';
    case DELETE = 'DELETE';
    case OPTIONS = 'OPTIONS';
    case HEAD = 'HEAD';

    /**
     * Check if the given string is a valid HTTP method.
     */
    public static function isValid(string $method): bool
    {
        return in_array($method, array_column(self::cases(), 'value'), true);
    }

    /**
     * Get all HTTP methods as an array of strings.
     *
     * @return array<string>
     */
    public static function all(): array
    {
        return array_column(self::cases(), 'value');
    }
}
