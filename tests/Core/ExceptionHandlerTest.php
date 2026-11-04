<?php

declare(strict_types=1);

namespace Tests\Core;

use App\Core\ExceptionHandler;
use App\Enums\HttpStatus;
use App\Http\Request;
use App\Http\Response;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for ExceptionHandler class.
 */
class ExceptionHandlerTest extends TestCase
{
    private ExceptionHandler $handler;

    protected function setUp(): void
    {
        $this->handler = new ExceptionHandler();
    }

    public function testDevelopmentEnvironment(): void
    {
        $this->handler->setEnvironment(ExceptionHandler::ENV_DEVELOPMENT);
        $this->assertTrue($this->handler->isDebug());
    }

    public function testProductionEnvironment(): void
    {
        $this->handler->setEnvironment(ExceptionHandler::ENV_PRODUCTION);
        $this->assertFalse($this->handler->isDebug());
    }

    public function testSetDebug(): void
    {
        $this->handler->setDebug(true);
        $this->assertTrue($this->handler->isDebug());

        $this->handler->setDebug(false);
        $this->assertFalse($this->handler->isDebug());
    }

    public function testHandleExceptionWithJsonResponse(): void
    {
        $request = new Request('GET', '/test', '', [], ['Accept' => 'application/json']);
        $exception = new \InvalidArgumentException('Test error');

        $response = $this->handler->handleException($exception, $request);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('application/json; charset=utf-8', $response->getHeader('Content-Type'));

        $data = json_decode($response->getBody(), true);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals(400, $data['error']['code']);
    }

    public function testHandleExceptionWithHtmlResponse(): void
    {
        $request = new Request('GET', '/test');
        $exception = new \RuntimeException('Test error');

        $response = $this->handler->handleException($exception, $request);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertStringContainsString('Internal Server Error', $response->getBody());
    }

    public function testHandleExceptionWithAjaxRequest(): void
    {
        $request = new Request('GET', '/test', '', [], ['X-Requested-With' => 'XMLHttpRequest']);
        $exception = new \InvalidArgumentException('Test error');

        $response = $this->handler->handleException($exception, $request);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('application/json; charset=utf-8', $response->getHeader('Content-Type'));
    }

    public function testCustomExceptionHandler(): void
    {
        $customHandler = function (\InvalidArgumentException $e, Request $request): Response {
            return Response::json(['custom' => true, 'message' => $e->getMessage()], 422);
        };

        $this->handler->handle(\InvalidArgumentException::class, $customHandler);

        $request = new Request('GET', '/test', '', [], ['Accept' => 'application/json']);
        $exception = new \InvalidArgumentException('Custom error');

        $response = $this->handler->handleException($exception, $request);

        $this->assertEquals(422, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertTrue($data['custom']);
        $this->assertEquals('Custom error', $data['message']);
    }

    public function testParentClassExceptionHandler(): void
    {
        $customHandler = function (\RuntimeException $e, Request $request): Response {
            return Response::json(['parent' => true], 503);
        };

        $this->handler->handle(\RuntimeException::class, $customHandler);

        $request = new Request('GET', '/test');
        $exception = new \DomainException('Domain error'); // Extends RuntimeException

        $response = $this->handler->handleException($exception, $request);

        $this->assertEquals(503, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertTrue($data['parent']);
    }

    public function testInterfaceExceptionHandler(): void
    {
        $customHandler = function (\Throwable $e, Request $request): Response {
            return Response::json(['interface' => true], 500);
        };

        $this->handler->handle(\Throwable::class, $customHandler);

        $request = new Request('GET', '/test');
        $exception = new \Exception('Test');

        $response = $this->handler->handleException($exception, $request);

        $this->assertEquals(500, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertTrue($data['interface']);
    }

    public function testDefaultHandler(): void
    {
        $defaultHandler = function (\Throwable $e, Request $request): Response {
            return Response::json(['default' => true], 500);
        };

        $this->handler->default($defaultHandler);

        $request = new Request('GET', '/test');
        $exception = new \Exception('Test');

        $response = $this->handler->handleException($exception, $request);

        $this->assertEquals(500, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertTrue($data['default']);
    }

    public function testHandleError(): void
    {
        $request = new Request('GET', '/test', '', [], ['Accept' => 'application/json']);

        $response = $this->handler->handleError(E_USER_ERROR, 'Test error', __FILE__, __LINE__, $request);

        $this->assertNotNull($response);
        $this->assertEquals(500, $response->getStatusCode());
    }

    public function testHandleWarningErrorReturnsNull(): void
    {
        $request = new Request('GET', '/test');

        // Warnings should not be handled by default
        $response = $this->handler->handleError(E_USER_WARNING, 'Test warning', __FILE__, __LINE__, $request);

        // Should return null for warnings
        $this->assertNull($response);
    }

    public function testHandleSuppressedErrorReturnsNull(): void
    {
        $request = new Request('GET', '/test');

        // Suppressed errors (with @) should return null
        $response = $this->handler->handleError(0, 'Test error', __FILE__, __LINE__, $request);

        $this->assertNull($response);
    }

    public function testStatusCodeForInvalidArgumentException(): void
    {
        $request = new Request('GET', '/test');
        $exception = new \InvalidArgumentException('Test');

        $response = $this->handler->handleException($exception, $request);
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testStatusCodeForRuntimeException(): void
    {
        $request = new Request('GET', '/test');
        $exception = new \RuntimeException('Test');

        $response = $this->handler->handleException($exception, $request);
        $this->assertEquals(500, $response->getStatusCode());
    }

    public function testStatusCodeForLogicException(): void
    {
        $request = new Request('GET', '/test');
        $exception = new \LogicException('Test');

        $response = $this->handler->handleException($exception, $request);
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testStatusCodeForDomainException(): void
    {
        $request = new Request('GET', '/test');
        $exception = new \DomainException('Test');

        $response = $this->handler->handleException($exception, $request);
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testStatusCodeForRangeException(): void
    {
        $request = new Request('GET', '/test');
        $exception = new \RangeException('Test');

        $response = $this->handler->handleException($exception, $request);
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testStatusCodeForError(): void
    {
        $request = new Request('GET', '/test');
        $exception = new \Error('Test');

        $response = $this->handler->handleException($exception, $request);
        $this->assertEquals(500, $response->getStatusCode());
    }

    public function testProductionErrorMessage(): void
    {
        $this->handler->setEnvironment(ExceptionHandler::ENV_PRODUCTION);

        $request = new Request('GET', '/test');
        $exception = new \InvalidArgumentException('Sensitive error message');

        $response = $this->handler->handleException($exception, $request);

        // In production, sensitive error messages should not be exposed
        $this->assertStringNotContainsString('Sensitive error message', $response->getBody());
    }

    public function testDevelopmentErrorMessage(): void
    {
        $this->handler->setEnvironment(ExceptionHandler::ENV_DEVELOPMENT);

        $request = new Request('GET', '/test');
        $exception = new \InvalidArgumentException('Development error message');

        $response = $this->handler->handleException($exception, $request);

        // In development, error messages should be shown
        $this->assertStringContainsString('Development error message', $response->getBody());
    }

    public function testJsonResponseIncludesDebugInfo(): void
    {
        $this->handler->setEnvironment(ExceptionHandler::ENV_DEVELOPMENT);

        $request = new Request('GET', '/test', '', [], ['Accept' => 'application/json']);
        $exception = new \RuntimeException('Test error');

        $response = $this->handler->handleException($exception, $request);

        $data = json_decode($response->getBody(), true);
        $this->assertArrayHasKey('error', $data);
        $this->assertArrayHasKey('exception', $data['error']);
        $this->assertArrayHasKey('file', $data['error']);
        $this->assertArrayHasKey('line', $data['error']);
        $this->assertArrayHasKey('trace', $data['error']);
    }

    public function testHtmlResponseIncludesDebugInfo(): void
    {
        $this->handler->setEnvironment(ExceptionHandler::ENV_DEVELOPMENT);

        $request = new Request('GET', '/test');
        $exception = new \RuntimeException('Test error');

        $response = $this->handler->handleException($exception, $request);

        $body = $response->getBody();
        $this->assertStringContainsString('Exception:', $body);
        $this->assertStringContainsString('File:', $body);
        $this->assertStringContainsString('Trace:', $body);
    }

    public function testRegisterHandlers(): void
    {
        // This test verifies that register() method doesn't throw errors
        // We can't easily test the actual error handlers without triggering errors
        $this->handler->register();

        // Restore previous handlers
        restore_error_handler();
        restore_exception_handler();
    }

    public function testCustomErrorHandler(): void
    {
        $customHandler = function (\Throwable $e, Request $request): Response {
            return Response::json(['custom_error' => true], 500);
        };

        $this->handler->handle(\Error::class, $customHandler);

        $request = new Request('GET', '/test');
        $exception = new \Error('Test error');

        $response = $this->handler->handleException($exception, $request);

        $this->assertEquals(500, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertTrue($data['custom_error']);
    }
}
