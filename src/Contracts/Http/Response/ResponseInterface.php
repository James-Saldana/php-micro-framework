<?php

declare(strict_types=1);

namespace App\Contracts\Http\Response;

/**
 * HTTP response interface.
 */
interface ResponseInterface
{
    /**
     * Get the status code.
     */
    public function getStatusCode(): int;

    /**
     * Set the status code and return a new instance.
     */
    public function withStatus(int $statusCode): static;

    /**
     * Get the reason phrase.
     */
    public function getReasonPhrase(): string;

    /**
     * Set the reason phrase and return a new instance.
     */
    public function withReasonPhrase(string $reasonPhrase): static;

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
     * Set a header and return a new instance.
     */
    public function withHeader(string $name, string $value): static;

    /**
     * Set multiple headers and return a new instance.
     *
     * @param array<string, string> $headers
     */
    public function withHeaders(array $headers): static;

    /**
     * Remove a header and return a new instance.
     */
    public function withoutHeader(string $name): static;

    /**
     * Get the response body.
     */
    public function getBody(): string;

    /**
     * Set the body and return a new instance.
     */
    public function withBody(string $body): static;

    /**
     * Append to the body and return a new instance.
     */
    public function withAddedBody(string $body): static;

    /**
     * Check if the response has been sent.
     */
    public function isSent(): bool;

    /**
     * Send the response to the client.
     */
    public function send(): void;

    /**
     * Clear the response and return a new instance.
     */
    public function clear(): static;
}
