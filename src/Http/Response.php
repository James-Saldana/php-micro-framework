<?php

declare(strict_types=1);

namespace App\Http;

use App\Enums\HttpStatus;

/**
 * HTTP response builder and representation.
 *
 * This class provides a fluent interface for building HTTP responses
 * with status codes, headers, and body content.
 */
final class Response
{
    private int $statusCode = HttpStatus::OK->value;
    private string $reasonPhrase = '';
    /** @var array<string, string> */
    private array $headers = [];
    private string $body = '';
    private bool $sent = false;

    /**
     * Create a new response with the given status code and body.
     *
     * @param int $statusCode HTTP status code
     * @param string $body Response body
     */
    public function __construct(int $statusCode = HttpStatus::OK->value, string $body = '')
    {
        $this->statusCode = $statusCode;
        $this->body = $body;
    }

    /**
     * Create a response with the given status enum.
     */
    public static function withStatus(HttpStatus $status, string $body = ''): self
    {
        return new self($status->value, $body);
    }

    /**
     * Create a JSON response.
     *
     * @param mixed $data Data to encode as JSON
     * @param int $statusCode HTTP status code
     * @param int $flags JSON encode flags
     */
    public static function json(
        mixed $data,
        int $statusCode = HttpStatus::OK->value,
        int $flags = JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
    ): self {
        $body = json_encode($data, $flags);
        return (new self($statusCode, $body))
            ->withHeader('Content-Type', 'application/json; charset=utf-8');
    }

    /**
     * Create a plain text response.
     */
    public static function text(string $text, int $statusCode = HttpStatus::OK->value): self
    {
        return (new self($statusCode, $text))
            ->withHeader('Content-Type', 'text/plain; charset=utf-8');
    }

    /**
     * Create a HTML response.
     */
    public static function html(string $html, int $statusCode = HttpStatus::OK->value): self
    {
        return (new self($statusCode, $html))
            ->withHeader('Content-Type', 'text/html; charset=utf-8');
    }

    /**
     * Create a redirect response.
     */
    public static function redirect(string $url, int $statusCode = HttpStatus::FOUND->value): self
    {
        return (new self($statusCode))
            ->withHeader('Location', $url);
    }

    /**
     * Create an empty response with the given status code.
     */
    public static function empty(int $statusCode = HttpStatus::NO_CONTENT->value): self
    {
        return new self($statusCode);
    }

    /**
     * Create a 200 OK response.
     */
    public static function ok(string $body = ''): self
    {
        return new self(HttpStatus::OK->value, $body);
    }

    /**
     * Create a 201 Created response.
     */
    public static function created(string $body = '', string $location = ''): self
    {
        $response = new self(HttpStatus::CREATED->value, $body);
        if ($location !== '') {
            $response = $response->withHeader('Location', $location);
        }
        return $response;
    }

    /**
     * Create a 204 No Content response.
     */
    public static function noContent(): self
    {
        return new self(HttpStatus::NO_CONTENT->value);
    }

    /**
     * Create a 400 Bad Request response.
     */
    public static function badRequest(string $message = 'Bad Request'): self
    {
        return new self(HttpStatus::BAD_REQUEST->value, $message);
    }

    /**
     * Create a 401 Unauthorized response.
     */
    public static function unauthorized(string $message = 'Unauthorized'): self
    {
        return new self(HttpStatus::UNAUTHORIZED->value, $message)
            ->withHeader('WWW-Authenticate', 'Basic');
    }

    /**
     * Create a 403 Forbidden response.
     */
    public static function forbidden(string $message = 'Forbidden'): self
    {
        return new self(HttpStatus::FORBIDDEN->value, $message);
    }

    /**
     * Create a 404 Not Found response.
     */
    public static function notFound(string $message = 'Not Found'): self
    {
        return new self(HttpStatus::NOT_FOUND->value, $message);
    }

    /**
     * Create a 405 Method Not Allowed response.
     */
    public static function methodNotAllowed(string $message = 'Method Not Allowed'): self
    {
        return new self(HttpStatus::METHOD_NOT_ALLOWED->value, $message);
    }

    /**
     * Create a 422 Unprocessable Entity response.
     */
    public static function unprocessableEntity(string $message = 'Unprocessable Entity'): self
    {
        return new self(HttpStatus::UNPROCESSABLE_ENTITY->value, $message);
    }

    /**
     * Create a 500 Internal Server Error response.
     */
    public static function serverError(string $message = 'Internal Server Error'): self
    {
        return new self(HttpStatus::INTERNAL_SERVER_ERROR->value, $message);
    }

    /**
     * Get the status code.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Set the status code.
     */
    public function withStatus(int $statusCode): self
    {
        $clone = clone $this;
        $clone->statusCode = $statusCode;
        $clone->reasonPhrase = '';
        return $clone;
    }

    /**
     * Get the reason phrase.
     */
    public function getReasonPhrase(): string
    {
        if ($this->reasonPhrase === '') {
            try {
                $status = HttpStatus::from($this->statusCode);
                return $status->getReasonPhrase();
            } catch (\InvalidArgumentException) {
                return '';
            }
        }
        return $this->reasonPhrase;
    }

    /**
     * Set a custom reason phrase.
     */
    public function withReasonPhrase(string $reasonPhrase): self
    {
        $clone = clone $this;
        $clone->reasonPhrase = $reasonPhrase;
        return $clone;
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
     */
    public function getHeader(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    /**
     * Check if a header exists.
     */
    public function hasHeader(string $name): bool
    {
        return isset($this->headers[strtolower($name)]);
    }

    /**
     * Set a header value.
     *
     * @param string $name Header name
     * @param string $value Header value
     */
    public function withHeader(string $name, string $value): self
    {
        $clone = clone $this;
        $clone->headers[strtolower($name)] = $value;
        return $clone;
    }

    /**
     * Set multiple headers.
     *
     * @param array<string, string> $headers
     */
    public function withHeaders(array $headers): self
    {
        $clone = clone $this;
        foreach ($headers as $name => $value) {
            $clone->headers[strtolower($name)] = $value;
        }
        return $clone;
    }

    /**
     * Remove a header.
     */
    public function withoutHeader(string $name): self
    {
        $clone = clone $this;
        unset($clone->headers[strtolower($name)]);
        return $clone;
    }

    /**
     * Get the response body.
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * Set the response body.
     */
    public function withBody(string $body): self
    {
        $clone = clone $this;
        $clone->body = $body;
        return $clone;
    }

    /**
     * Append to the response body.
     */
    public function withAddedBody(string $body): self
    {
        $clone = clone $this;
        $clone->body .= $body;
        return $clone;
    }

    /**
     * Check if the response has been sent.
     */
    public function isSent(): bool
    {
        return $this->sent;
    }

    /**
     * Send the response to the client.
     *
     * This method sends the status line, headers, and body to the client.
     * It should be called only once per response.
     */
    public function send(): void
    {
        if ($this->sent) {
            return;
        }

        $this->sent = true;

        // Send status line
        $reason = $this->getReasonPhrase();
        header("HTTP/1.1 {$this->statusCode} {$reason}", true, $this->statusCode);

        // Send headers
        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }

        // Send body
        echo $this->body;
    }

    /**
     * Clear the response (for reuse).
     */
    public function clear(): self
    {
        $clone = clone $this;
        $clone->statusCode = HttpStatus::OK->value;
        $clone->reasonPhrase = '';
        $clone->headers = [];
        $clone->body = '';
        $clone->sent = false;
        return $clone;
    }
}
