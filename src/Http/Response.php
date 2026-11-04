<?php

declare(strict_types=1);

namespace App\Http;

use App\Contracts\Http\Response\ResponseInterface;
use App\Enums\HttpStatus;

/**
 * HTTP response implementation.
 */
final class Response implements ResponseInterface
{
    private int $statusCode = HttpStatus::OK->value;
    private string $reasonPhrase = '';
    /** @var array<string, string> */
    private array $headers = [];
    private string $body = '';
    private bool $sent = false;

    public function __construct(int $statusCode = HttpStatus::OK->value, string $body = '')
    {
        $this->statusCode = $statusCode;
        $this->body = $body;
    }

    public static function withStatus(HttpStatus $status, string $body = ''): self
    {
        return new self($status->value, $body);
    }

    public static function json(
        mixed $data,
        int $statusCode = HttpStatus::OK->value,
        int $flags = JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
    ): self {
        $body = json_encode($data, $flags);
        return (new self($statusCode, $body))
            ->withHeader('Content-Type', 'application/json; charset=utf-8');
    }

    public static function text(string $text, int $statusCode = HttpStatus::OK->value): self
    {
        return (new self($statusCode, $text))
            ->withHeader('Content-Type', 'text/plain; charset=utf-8');
    }

    public static function html(string $html, int $statusCode = HttpStatus::OK->value): self
    {
        return (new self($statusCode, $html))
            ->withHeader('Content-Type', 'text/html; charset=utf-8');
    }

    public static function redirect(string $url, int $statusCode = HttpStatus::FOUND->value): self
    {
        return (new self($statusCode))
            ->withHeader('Location', $url);
    }

    public static function empty(int $statusCode = HttpStatus::NO_CONTENT->value): self
    {
        return new self($statusCode);
    }

    public static function noContent(): self
    {
        return new self(HttpStatus::NO_CONTENT->value);
    }

    public static function ok(string $body = ''): self
    {
        return new self(HttpStatus::OK->value, $body);
    }

    public static function created(string $body = '', string $location = ''): self
    {
        $response = new self(HttpStatus::CREATED->value, $body);
        if ($location !== '') {
            $response = $response->withHeader('Location', $location);
        }
        return $response;
    }

    public static function badRequest(string $message = 'Bad Request'): self
    {
        return new self(HttpStatus::BAD_REQUEST->value, $message);
    }

    public static function unauthorized(string $message = 'Unauthorized'): self
    {
        return new self(HttpStatus::UNAUTHORIZED->value, $message)
            ->withHeader('WWW-Authenticate', 'Basic');
    }

    public static function forbidden(string $message = 'Forbidden'): self
    {
        return new self(HttpStatus::FORBIDDEN->value, $message);
    }

    public static function notFound(string $message = 'Not Found'): self
    {
        return new self(HttpStatus::NOT_FOUND->value, $message);
    }

    public static function methodNotAllowed(string $message = 'Method Not Allowed'): self
    {
        return new self(HttpStatus::METHOD_NOT_ALLOWED->value, $message);
    }

    public static function unprocessableEntity(string $message = 'Unprocessable Entity'): self
    {
        return new self(HttpStatus::UNPROCESSABLE_ENTITY->value, $message);
    }

    public static function serverError(string $message = 'Internal Server Error'): self
    {
        return new self(HttpStatus::INTERNAL_SERVER_ERROR->value, $message);
    }

    public static function serviceUnavailable(string $message = 'Service Unavailable'): self
    {
        return new self(HttpStatus::SERVICE_UNAVAILABLE->value, $message);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function withStatus(int $statusCode): self
    {
        $clone = clone $this;
        $clone->statusCode = $statusCode;
        $clone->reasonPhrase = '';
        return $clone;
    }

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

    public function withReasonPhrase(string $reasonPhrase): self
    {
        $clone = clone $this;
        $clone->reasonPhrase = $reasonPhrase;
        return $clone;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getHeader(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    public function hasHeader(string $name): bool
    {
        return isset($this->headers[strtolower($name)]);
    }

    public function withHeader(string $name, string $value): self
    {
        $clone = clone $this;
        $clone->headers[strtolower($name)] = $value;
        return $clone;
    }

    public function withHeaders(array $headers): self
    {
        $clone = clone $this;
        foreach ($headers as $name => $value) {
            $clone->headers[strtolower($name)] = $value;
        }
        return $clone;
    }

    public function withoutHeader(string $name): self
    {
        $clone = clone $this;
        unset($clone->headers[strtolower($name)]);
        return $clone;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function withBody(string $body): self
    {
        $clone = clone $this;
        $clone->body = $body;
        return $clone;
    }

    public function withAddedBody(string $body): self
    {
        $clone = clone $this;
        $clone->body .= $body;
        return $clone;
    }

    public function isSent(): bool
    {
        return $this->sent;
    }

    public function send(): void
    {
        if ($this->sent) {
            return;
        }

        $this->sent = true;

        $reason = $this->getReasonPhrase();
        header("HTTP/1.1 {$this->statusCode} {$reason}", true, $this->statusCode);

        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }

        echo $this->body;
    }

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
