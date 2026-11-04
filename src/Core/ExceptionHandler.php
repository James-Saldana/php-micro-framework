<?php

declare(strict_types=1);

namespace App\Core;

use App\Enums\HttpStatus;
use App\Http\Request;
use App\Http\Response;
use Error;
use Throwable;

/**
 * Centralized exception handler.
 *
 * This class handles all exceptions and errors that occur during
 * request processing, converting them to appropriate HTTP responses.
 *
 * It supports:
 * - Custom exception handlers for specific exception types
 * - JSON or HTML error responses based on request preferences
 * - Environment-specific error detail levels
 */
final class ExceptionHandler
{
    public const ENV_PRODUCTION = 'production';
    public const ENV_DEVELOPMENT = 'development';
    public const ENV_TESTING = 'testing';

    private string $environment = self::ENV_DEVELOPMENT;
    private bool $debug = true;
    /** @var array<string, callable> */
    private array $exceptionHandlers = [];
    private ?callable $defaultHandler = null;

    /**
     * Set the environment.
     */
    public function setEnvironment(string $environment): self
    {
        $this->environment = $environment;
        $this->debug = $environment !== self::ENV_PRODUCTION;
        return $this;
    }

    /**
     * Enable or disable debug mode.
     */
    public function setDebug(bool $debug): self
    {
        $this->debug = $debug;
        return $this;
    }

    /**
     * Register a custom handler for a specific exception type.
     *
     * @template T of Throwable
     * @param class-string<T> $exceptionClass The exception class to handle
     * @param callable(T, Request): Response $handler The handler function
     */
    public function handle(string $exceptionClass, callable $handler): self
    {
        $this->exceptionHandlers[$exceptionClass] = $handler;
        return $this;
    }

    /**
     * Set the default exception handler.
     */
    public function default(callable $handler): self
    {
        $this->defaultHandler = $handler;
        return $this;
    }

    /**
     * Handle an exception and return a response.
     *
     * @param Throwable $exception The exception to handle
     * @param Request $request The current request
     * @return Response The error response
     */
    public function handleException(Throwable $exception, Request $request): Response
    {
        // Find the most specific handler
        $handler = $this->findHandler($exception);

        if ($handler !== null) {
            return $handler($exception, $request);
        }

        // Use default handler if set
        if ($this->defaultHandler !== null) {
            return ($this->defaultHandler)($exception, $request);
        }

        // Use built-in handler
        return $this->handleDefault($exception, $request);
    }

    /**
     * Handle a PHP error (converted to ErrorException).
     *
     * @param int $errno Error number
     * @param string $errstr Error string
     * @param string $errfile Error file
     * @param int $errline Error line
     * @param Request $request The current request
     * @return Response|null The error response or null if not handled
     */
    public function handleError(
        int $errno,
        string $errstr,
        string $errfile,
        int $errline,
        Request $request
    ): ?Response {
        // Skip suppressed errors
        if ($errno === 0 || ($errno & error_reporting()) === 0) {
            return null;
        }

        // Convert to ErrorException
        $exception = new \ErrorException($errstr, 0, $errno, $errfile, $errline);
        return $this->handleException($exception, $request);
    }

    /**
     * Register as PHP's error and exception handler.
     */
    public function register(): void
    {
        set_error_handler(function (
            int $errno,
            string $errstr,
            string $errfile = '',
            int $errline = 0
        ) {
            if ($this->shouldHandleError($errno)) {
                $request = Request::fromGlobals();
                $response = $this->handleError($errno, $errstr, $errfile, $errline, $request);
                if ($response !== null) {
                    $response->send();
                    exit(1);
                }
            }
            return false; // Let PHP handle it
        });

        set_exception_handler(function (Throwable $exception) {
            $request = Request::fromGlobals();
            $response = $this->handleException($exception, $request);
            $response->send();
            exit(1);
        });
    }

    /**
     * Find the most specific handler for an exception.
     *
     * @param Throwable $exception The exception to find a handler for
     * @return callable|null The handler or null if not found
     */
    private function findHandler(Throwable $exception): ?callable
    {
        $class = get_class($exception);

        // Check for exact match
        if (isset($this->exceptionHandlers[$class])) {
            return $this->exceptionHandlers[$class];
        }

        // Check parent classes
        foreach (class_parents($class) as $parent) {
            if (isset($this->exceptionHandlers[$parent])) {
                return $this->exceptionHandlers[$parent];
            }
        }

        // Check interfaces
        foreach (class_implements($class) as $interface) {
            if (isset($this->exceptionHandlers[$interface])) {
                return $this->exceptionHandlers[$interface];
            }
        }

        return null;
    }

    /**
     * Default exception handler.
     *
     * @param Throwable $exception The exception to handle
     * @param Request $request The current request
     * @return Response The error response
     */
    private function handleDefault(Throwable $exception, Request $request): Response
    {
        $statusCode = $this->determineStatusCode($exception);
        $wantsJson = $request->expectsJson() || $request->isAjax();

        if ($wantsJson) {
            return $this->createJsonResponse($exception, $statusCode);
        }

        return $this->createHtmlResponse($exception, $statusCode);
    }

    /**
     * Determine the appropriate HTTP status code for an exception.
     */
    private function determineStatusCode(Throwable $exception): int
    {
        // Check for specific exception types
        if ($exception instanceof \InvalidArgumentException) {
            return HttpStatus::BAD_REQUEST->value;
        }

        if ($exception instanceof \RuntimeException) {
            return HttpStatus::INTERNAL_SERVER_ERROR->value;
        }

        if ($exception instanceof \LogicException) {
            return HttpStatus::BAD_REQUEST->value;
        }

        if ($exception instanceof \DomainException) {
            return HttpStatus::BAD_REQUEST->value;
        }

        if ($exception instanceof \RangeException) {
            return HttpStatus::BAD_REQUEST->value;
        }

        if ($exception instanceof \Error) {
            return HttpStatus::INTERNAL_SERVER_ERROR->value;
        }

        // Default to 500
        return HttpStatus::INTERNAL_SERVER_ERROR->value;
    }

    /**
     * Create a JSON error response.
     *
     * @param Throwable $exception The exception
     * @param int $statusCode The HTTP status code
     * @return Response The JSON response
     */
    private function createJsonResponse(Throwable $exception, int $statusCode): Response
    {
        $error = [
            'error' => [
                'code' => $statusCode,
                'message' => $this->getErrorMessage($exception),
            ],
        ];

        if ($this->debug) {
            $error['error']['exception'] = get_class($exception);
            $error['error']['file'] = $exception->getFile();
            $error['error']['line'] = $exception->getLine();
            $error['error']['trace'] = $exception->getTraceAsString();
        }

        return Response::json($error, $statusCode);
    }

    /**
     * Create an HTML error response.
     *
     * @param Throwable $exception The exception
     * @param int $statusCode The HTTP status code
     * @return Response The HTML response
     */
    private function createHtmlResponse(Throwable $exception, int $statusCode): Response
    {
        $message = $this->getErrorMessage($exception);
        $statusText = HttpStatus::from($statusCode)->getReasonPhrase();

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$statusCode} - {$statusText}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
               margin: 0; padding: 40px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; }
        .card { background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; margin: 0 0 20px 0; font-size: 24px; }
        .status { color: #666; font-size: 48px; font-weight: bold; margin: 20px 0; }
        .message { color: #555; font-size: 16px; line-height: 1.5; margin: 20px 0; }
        .details { background: #f8f9fa; padding: 20px; border-radius: 4px; 
                    font-family: monospace; font-size: 12px; overflow-x: auto; }
        .trace { white-space: pre-wrap; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="status">{$statusCode}</div>
            <h1>{$statusText}</h1>
            <div class="message">{$message}</div>
HTML;

        if ($this->debug) {
            $html .= <<<HTML
            <div class="details">
                <strong>Exception:</strong> {get_class($exception)}<br>
                <strong>File:</strong> {$exception->getFile()}:{$exception->getLine()}<br><br>
                <strong>Trace:</strong><br>
                <div class="trace">{$exception->getTraceAsString()}</div>
            </div>
HTML;
        }

        $html .= "</div></div></body></html>";

        return Response::html($html, $statusCode);
    }

    /**
     * Get the error message based on environment.
     */
    private function getErrorMessage(Throwable $exception): string
    {
        if ($this->debug) {
            return $exception->getMessage();
        }

        // Production-friendly messages
        $statusCode = $this->determineStatusCode($exception);
        $status = HttpStatus::from($statusCode);
        return $status->getReasonPhrase();
    }

    /**
     * Determine if an error should be handled.
     */
    private function shouldHandleError(int $errno): bool
    {
        return $errno & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR);
    }
}
