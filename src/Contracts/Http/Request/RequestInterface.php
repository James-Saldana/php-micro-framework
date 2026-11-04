<?php

declare(strict_types=1);

namespace App\Contracts\Http\Request;

/**
 * HTTP request interface.
 */
interface RequestInterface
{
    /**
     * Get the HTTP method.
     */
    public function getMethod(): string;

    /**
     * Get the request URI.
     */
    public function getUri(): string;

    /**
     * Get the path part of the URI.
     */
    public function getPath(): string;

    /**
     * Get the query string.
     */
    public function getQueryString(): string;

    /**
     * Get all query parameters.
     *
     * @return array<string, string>
     */
    public function getQueryParams(): array;

    /**
     * Get a specific query parameter.
     *
     * @param string $key The parameter key
     * @param mixed $default Default value if not found
     * @return mixed
     */
    public function getQueryParam(string $key, mixed $default = null): mixed;

    /**
     * Get all headers.
     *
     * @return array<string, string>
     */
    public function getHeaders(): array;

    /**
     * Get a specific header.
     */
    public function getHeader(string $name): ?string;

    /**
     * Check if a header exists.
     */
    public function hasHeader(string $name): bool;

    /**
     * Get the raw request body.
     */
    public function getBody(): string;

    /**
     * Get the parsed request body.
     *
     * @return array<string, mixed>
     */
    public function getParsedBody(): array;

    /**
     * Get a specific value from the parsed body.
     *
     * @param string $key The key
     * @param mixed $default Default value if not found
     * @return mixed
     */
    public function getParsedBodyParam(string $key, mixed $default = null): mixed;

    /**
     * Get all route attributes.
     *
     * @return array<string, mixed>
     */
    public function getAttributes(): array;

    /**
     * Get a specific route attribute.
     *
     * @param string $key The attribute key
     * @param mixed $default Default value if not found
     * @return mixed
     */
    public function getAttribute(string $key, mixed $default = null): mixed;

    /**
     * Check if an attribute exists.
     */
    public function hasAttribute(string $key): bool;

    /**
     * Get all server variables.
     *
     * @return array<string, mixed>
     */
    public function getServer(): array;

    /**
     * Get a specific server variable.
     *
     * @param string $key The server variable key
     * @param mixed $default Default value if not found
     * @return mixed
     */
    public function getServerParam(string $key, mixed $default = null): mixed;

    /**
     * Check if the request is AJAX.
     */
    public function isAjax(): bool;

    /**
     * Check if the request expects JSON.
     */
    public function expectsJson(): bool;

    /**
     * Check if the request is HTTPS.
     */
    public function isSecure(): bool;

    /**
     * Get the client IP address.
     */
    public function getClientIp(): string;

    /**
     * Get the request scheme (http or https).
     */
    public function getScheme(): string;

    /**
     * Get the host.
     */
    public function getHost(): string;

    /**
     * Get the full URL.
     */
    public function getUrl(): string;
}
