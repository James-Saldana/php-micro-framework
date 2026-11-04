<?php

declare(strict_types=1);

namespace App\Http;

use App\Contracts\Http\Request\RequestInterface;

/**
 * Immutable HTTP request representation.
 */
final readonly class Request implements RequestInterface
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

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getPath(): string
    {
        if (($pos = strpos($this->uri, '?')) !== false) {
            return substr($this->uri, 0, $pos);
        }
        return $this->uri;
    }

    public function getQueryString(): string
    {
        return $this->queryString;
    }

    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    public function getQueryParam(string $key, mixed $default = null): mixed
    {
        return $this->queryParams[$key] ?? $default;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getHeader(string $name): ?string
    {
        $name = strtolower($name);
        foreach ($this->headers as $key => $value) {
            if (strtolower($key) === $name) {
                return $value;
            }
        }
        return null;
    }

    public function hasHeader(string $name): bool
    {
        return $this->getHeader($name) !== null;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getParsedBody(): array
    {
        return $this->parsedBody;
    }

    public function getParsedBodyParam(string $key, mixed $default = null): mixed
    {
        return $this->parsedBody[$key] ?? $default;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getAttribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    public function hasAttribute(string $key): bool
    {
        return array_key_exists($key, $this->attributes);
    }

    public function getServer(): array
    {
        return $this->server;
    }

    public function getServerParam(string $key, mixed $default = null): mixed
    {
        return $this->server[$key] ?? $default;
    }

    public function isAjax(): bool
    {
        return strtolower($this->getHeader('X-Requested-With') ?? '') === 'xmlhttprequest';
    }

    public function expectsJson(): bool
    {
        $accept = strtolower($this->getHeader('Accept') ?? '');
        return str_contains($accept, 'application/json');
    }

    public function isSecure(): bool
    {
        return ($this->server['HTTPS'] ?? '') === 'on'
            || ($this->server['SERVER_PORT'] ?? '') === '443'
            || ($this->server['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    }

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

    public function getScheme(): string
    {
        return $this->isSecure() ? 'https' : 'http';
    }

    public function getHost(): string
    {
        return $this->server['HTTP_HOST'] ?? $this->server['SERVER_NAME'] ?? 'localhost';
    }

    public function getUrl(): string
    {
        $scheme = $this->getScheme();
        $host = $this->getHost();
        $uri = $this->getUri();

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

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headerName = str_replace('_', '-', substr($key, 5));
                $headers[$headerName] = $value;
            }
        }

        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['Content-Type'] = $_SERVER['CONTENT_TYPE'];
        }
        if (isset($_SERVER['CONTENT_LENGTH'])) {
            $headers['Content-Length'] = $_SERVER['CONTENT_LENGTH'];
        }

        $body = file_get_contents('php://input') ?: '';

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
