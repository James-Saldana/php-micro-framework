<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Immutable HTTP request representation.
 *
 * This class provides a clean, object-oriented interface to the HTTP request,
 * including method, URI, headers, and body.
 */
final readonly class Request
{
    private string $method;
    private string $uri;
    private string $queryString;
    /** @var array<string, string> */
    private array $queryParams;
    /** @var array<string, string> */
    private array $headers;
    /** @var array<string, mixed> */
    private array $server;
    private string $body;
    /** @var array<string, mixed> */
    private array $parsedBody;
    /** @var array<string, mixed> */
    private array $attributes;

    /**
     * @param array<string, string> $headers
     * @param array<string, mixed> $server
     * @param array<string, mixed> $parsedBody
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        string $method,
        string $uri,
        string $queryString = '',
        array $queryParams = [],
        array $headers = [],
        array $server = [],
        string $body = '',
        array $parsedBody = [],
        array $attributes = []
    ) {
        $this->method = strtoupper($method);
        $this->uri = $uri;
        $this->queryString = $queryString;
        $this->queryParams = $queryParams;
        $this->headers = $headers;
        $this->server = $server;
        $this->body = $body;
        $this->parsedBody = $parsedBody;
        $this->attributes = $attributes;
    }

    /**
     * Get the HTTP method.
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Get the request URI (path + query string).
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Get the path part of the URI (without query string).
     */
    public function getPath(): string
    {
        if (($pos = strpos($this->uri, '?')) !== false) {
            return substr($this->uri, 0, $pos);
        }
        return $this->uri;
    }

    /**
     * Get the query string.
     */
    public function getQueryString(): string
    {
        return $this->queryString;
    }

    /**
     * Get all query parameters.
     *
     * @return array<string, string>
     */
    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    /**
     * Get a specific query parameter.
     *
     * @param string $key The parameter key
     * @param mixed $default Default value if not found
     * @return mixed
     */
    public function getQueryParam(string $key, mixed $default = null): mixed
    {
        return $this->queryParams[$key] ?? $default;
    }

    /**
     * Get all headers.
     *
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Get a specific header value.
     *
     * @param string $name Header name (case-insensitive)
     * @param string|null $default Default value if not found
     */
    public function getHeader(string $name, ?string $default = null): ?string
    {
        $name = strtolower($name);
        foreach ($this->headers as $key => $value) {
            if (strtolower($key) === $name) {
                return $value;
            }
        }
        return $default;
    }

    /**
     * Check if a header exists.
     */
    public function hasHeader(string $name): bool
    {
        return $this->getHeader($name) !== null;
    }

    /**
     * Get the raw request body.
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * Get the parsed request body (typically for POST/PUT/PATCH).
     *
     * @return array<string, mixed>
     */
    public function getParsedBody(): array
    {
        return $this->parsedBody;
    }

    /**
     * Get a specific value from the parsed body.
     *
     * @param string $key The key to retrieve
     * @param mixed $default Default value if not found
     * @return mixed
     */
    public function getParsedBodyParam(string $key, mixed $default = null): mixed
    {
        return $this->parsedBody[$key] ?? $default;
    }

    /**
     * Get all route attributes.
     *
     * @return array<string, mixed>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Get a specific route attribute.
     *
     * @param string $key The attribute key
     * @param mixed $default Default value if not found
     * @return mixed
     */
    public function getAttribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    /**
     * Check if an attribute exists.
     */
    public function hasAttribute(string $key): bool
    {
        return array_key_exists($key, $this->attributes);
    }

    /**
     * Get server variables.
     *
     * @return array<string, mixed>
     */
    public function getServer(): array
    {
        return $this->server;
    }

    /**
     * Get a specific server variable.
     *
     * @param string $key The server variable key
     * @param mixed $default Default value if not found
     * @return mixed
     */
    public function getServerParam(string $key, mixed $default = null): mixed
    {
        return $this->server[$key] ?? $default;
    }

    /**
     * Check if the request is AJAX (X-Requested-With: XMLHttpRequest).
     */
    public function isAjax(): bool
    {
        return strtolower($this->getHeader('X-Requested-With') ?? '') === 'xmlhttprequest';
    }

    /**
     * Check if the request expects JSON (Accept: application/json).
     */
    public function expectsJson(): bool
    {
        $accept = strtolower($this->getHeader('Accept') ?? '');
        return str_contains($accept, 'application/json');
    }

    /**
     * Check if the request is HTTPS.
     */
    public function isSecure(): bool
    {
        return ($this->server['HTTPS'] ?? '') === 'on'
            || ($this->server['SERVER_PORT'] ?? '') === '443'
            || ($this->server['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    }

    /**
     * Get the client IP address.
     */
    public function getClientIp(): string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        ];

        foreach ($headers as $header) {
            if (!empty($this->server[$header])) {
                $ip = $this->server[$header];
                // Handle comma-separated list (X-Forwarded-For)
                if (str_contains($ip, ',')) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return $this->server['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Get the request scheme (http or https).
     */
    public function getScheme(): string
    {
        return $this->isSecure() ? 'https' : 'http';
    }

    /**
     * Get the host.
     */
    public function getHost(): string
    {
        return $this->server['HTTP_HOST'] ?? $this->server['SERVER_NAME'] ?? 'localhost';
    }

    /**
     * Get the full URL.
     */
    public function getUrl(): string
    {
        $scheme = $this->getScheme();
        $host = $this->getHost();
        $uri = $this->getUri();

        // Ensure URI starts with /
        if ($uri && $uri[0] !== '/') {
            $uri = '/' . $uri;
        }

        return "{$scheme}://{$host}{$uri}";
    }

    /**
     * Create a request from superglobals.
     */
    public static function fromGlobals(): self
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $queryString = $_SERVER['QUERY_STRING'] ?? '';
        $queryParams = $_GET ?? [];

        // Normalize headers
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headerName = str_replace('_', '-', substr($key, 5));
                $headers[$headerName] = $value;
            }
        }

        // Add Content-Type and Content-Length
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['Content-Type'] = $_SERVER['CONTENT_TYPE'];
        }
        if (isset($_SERVER['CONTENT_LENGTH'])) {
            $headers['Content-Length'] = $_SERVER['CONTENT_LENGTH'];
        }

        // Get raw body
        $body = file_get_contents('php://input') ?: '';

        // Parse body based on content type
        $parsedBody = [];
        $contentType = $headers['Content-Type'] ?? '';

        if (str_contains($contentType, 'application/json')) {
            $decoded = json_decode($body, true);
            $parsedBody = is_array($decoded) ? $decoded : [];
        } elseif (str_contains($contentType, 'application/x-www-form-urlencoded')) {
            $parsedBody = $_POST ?? [];
        } elseif (str_contains($contentType, 'multipart/form-data')) {
            $parsedBody = $_POST ?? [];
            $parsedBody['_FILES'] = $_FILES ?? [];
        }

        return new self(
            $method,
            $uri,
            $queryString,
            $queryParams,
            $headers,
            $_SERVER,
            $body,
            $parsedBody,
            []
        );
    }
}
