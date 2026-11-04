<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * HTTP method enumeration.
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
    case CONNECT = 'CONNECT';
    case TRACE = 'TRACE';

    /**
     * Check if the given string is a valid HTTP method.
     */
    public static function isValid(string $method): bool
    {
        return in_array(strtoupper($method), array_column(self::cases(), 'value'), true);
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

    /**
     * Get methods that are typically used for reading data.
     *
     * @return array<self>
     */
    public static function readMethods(): array
    {
        return [self::GET, self::HEAD, self::OPTIONS];
    }

    /**
     * Get methods that are typically used for writing data.
     *
     * @return array<self>
     */
    public static function writeMethods(): array
    {
        return [self::POST, self::PUT, self::PATCH, self::DELETE];
    }

    /**
     * Check if this method is typically used for reading.
     */
    public function isReadMethod(): bool
    {
        return in_array($this, self::readMethods(), true);
    }

    /**
     * Check if this method is typically used for writing.
     */
    public function isWriteMethod(): bool
    {
        return in_array($this, self::writeMethods(), true);
    }
}
