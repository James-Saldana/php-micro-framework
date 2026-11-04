<?php

declare(strict_types=1);

namespace Tests\Http;

use App\Enums\HttpStatus;
use App\Http\Response;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for Response class.
 */
class ResponseTest extends TestCase
{
    public function testDefaultStatusCode(): void
    {
        $response = new Response();
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testCustomStatusCode(): void
    {
        $response = new Response(404);
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testWithStatus(): void
    {
        $response = new Response();
        $newResponse = $response->withStatus(404);
        $this->assertEquals(404, $newResponse->getStatusCode());
        $this->assertEquals(200, $response->getStatusCode()); // Original unchanged
    }

    public function testDefaultBody(): void
    {
        $response = new Response();
        $this->assertEquals('', $response->getBody());
    }

    public function testCustomBody(): void
    {
        $body = 'Hello World';
        $response = new Response(200, $body);
        $this->assertEquals($body, $response->getBody());
    }

    public function testWithBody(): void
    {
        $response = new Response();
        $newResponse = $response->withBody('New Body');
        $this->assertEquals('New Body', $newResponse->getBody());
        $this->assertEquals('', $response->getBody()); // Original unchanged
    }

    public function testWithAddedBody(): void
    {
        $response = new Response(200, 'Hello');
        $newResponse = $response->withAddedBody(' World');
        $this->assertEquals('Hello World', $newResponse->getBody());
        $this->assertEquals('Hello', $response->getBody()); // Original unchanged
    }

    public function testDefaultHeaders(): void
    {
        $response = new Response();
        $this->assertEmpty($response->getHeaders());
    }

    public function testWithHeader(): void
    {
        $response = new Response();
        $newResponse = $response->withHeader('Content-Type', 'application/json');
        $this->assertEquals('application/json', $newResponse->getHeader('Content-Type'));
        $this->assertEmpty($response->getHeaders()); // Original unchanged
    }

    public function testHeaderLookupIsCaseInsensitive(): void
    {
        $response = new Response()->withHeader('Content-Type', 'application/json');
        $this->assertEquals('application/json', $response->getHeader('content-type'));
        $this->assertEquals('application/json', $response->getHeader('CONTENT-TYPE'));
    }

    public function testHasHeader(): void
    {
        $response = new Response()->withHeader('Content-Type', 'application/json');
        $this->assertTrue($response->hasHeader('Content-Type'));
        $this->assertTrue($response->hasHeader('content-type'));
        $this->assertFalse($response->hasHeader('Authorization'));
    }

    public function testWithHeaders(): void
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer token',
        ];
        $response = new Response()->withHeaders($headers);
        $this->assertEquals('application/json', $response->getHeader('Content-Type'));
        $this->assertEquals('Bearer token', $response->getHeader('Authorization'));
    }

    public function testWithoutHeader(): void
    {
        $response = new Response()->withHeader('Content-Type', 'application/json');
        $newResponse = $response->withoutHeader('Content-Type');
        $this->assertFalse($newResponse->hasHeader('Content-Type'));
        $this->assertTrue($response->hasHeader('Content-Type')); // Original unchanged
    }

    public function testReasonPhrase(): void
    {
        $response = new Response(200);
        $this->assertEquals('OK', $response->getReasonPhrase());

        $response = new Response(404);
        $this->assertEquals('Not Found', $response->getReasonPhrase());

        $response = new Response(500);
        $this->assertEquals('Internal Server Error', $response->getReasonPhrase());
    }

    public function testWithReasonPhrase(): void
    {
        $response = new Response(200);
        $newResponse = $response->withReasonPhrase('Custom Reason');
        $this->assertEquals('Custom Reason', $newResponse->getReasonPhrase());
    }

    public function testIsSent(): void
    {
        $response = new Response();
        $this->assertFalse($response->isSent());
    }

    public function testClear(): void
    {
        $response = new Response(404, 'Error')->withHeader('X-Custom', 'value');
        $newResponse = $response->clear();
        $this->assertEquals(200, $newResponse->getStatusCode());
        $this->assertEquals('', $newResponse->getBody());
        $this->assertEmpty($newResponse->getHeaders());
        $this->assertFalse($newResponse->isSent());
    }

    public function testWithStatusReturnsNewInstance(): void
    {
        $response = new Response();
        $newResponse = $response->withStatus(404);
        $this->assertNotSame($response, $newResponse);
    }

    public function testImmutableOperations(): void
    {
        $response = new Response(200, 'Original');
        $response->withStatus(404);
        $response->withBody('Modified');
        $response->withHeader('X-Test', 'value');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Original', $response->getBody());
        $this->assertEmpty($response->getHeaders());
    }

    public function testJsonResponse(): void
    {
        $data = ['name' => 'test', 'value' => 123];
        $response = Response::json($data);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json; charset=utf-8', $response->getHeader('Content-Type'));
        $this->assertJson($response->getBody());
        $this->assertEquals($data, json_decode($response->getBody(), true));
    }

    public function testJsonResponseWithStatus(): void
    {
        $data = ['error' => 'Not found'];
        $response = Response::json($data, 404);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('application/json; charset=utf-8', $response->getHeader('Content-Type'));
    }

    public function testTextResponse(): void
    {
        $response = Response::text('Hello World');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('text/plain; charset=utf-8', $response->getHeader('Content-Type'));
        $this->assertEquals('Hello World', $response->getBody());
    }

    public function testHtmlResponse(): void
    {
        $html = '<h1>Hello</h1>';
        $response = Response::html($html);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('text/html; charset=utf-8', $response->getHeader('Content-Type'));
        $this->assertEquals($html, $response->getBody());
    }

    public function testRedirectResponse(): void
    {
        $response = Response::redirect('/login');
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/login', $response->getHeader('Location'));
    }

    public function testRedirectResponseWithStatus(): void
    {
        $response = Response::redirect('/login', 301);
        $this->assertEquals(301, $response->getStatusCode());
        $this->assertEquals('/login', $response->getHeader('Location'));
    }

    public function testEmptyResponse(): void
    {
        $response = Response::empty();
        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEquals('', $response->getBody());
    }

    public function testNoContentResponse(): void
    {
        $response = Response::noContent();
        $this->assertEquals(204, $response->getStatusCode());
    }

    public function testOkResponse(): void
    {
        $response = Response::ok();
        $this->assertEquals(200, $response->getStatusCode());

        $response = Response::ok('Success');
        $this->assertEquals('Success', $response->getBody());
    }

    public function testCreatedResponse(): void
    {
        $response = Response::created();
        $this->assertEquals(201, $response->getStatusCode());

        $response = Response::created('Created', '/users/1');
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals('/users/1', $response->getHeader('Location'));
    }

    public function testBadRequestResponse(): void
    {
        $response = Response::badRequest();
        $this->assertEquals(400, $response->getStatusCode());

        $response = Response::badRequest('Invalid input');
        $this->assertEquals('Invalid input', $response->getBody());
    }

    public function testUnauthorizedResponse(): void
    {
        $response = Response::unauthorized();
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('Basic', $response->getHeader('WWW-Authenticate'));
    }

    public function testForbiddenResponse(): void
    {
        $response = Response::forbidden();
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testNotFoundResponse(): void
    {
        $response = Response::notFound();
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testMethodNotAllowedResponse(): void
    {
        $response = Response::methodNotAllowed();
        $this->assertEquals(405, $response->getStatusCode());
    }

    public function testUnprocessableEntityResponse(): void
    {
        $response = Response::unprocessableEntity();
        $this->assertEquals(422, $response->getStatusCode());
    }

    public function testServerErrorResponse(): void
    {
        $response = Response::serverError();
        $this->assertEquals(500, $response->getStatusCode());
    }

    public function testWithStatusEnum(): void
    {
        $response = Response::withStatus(HttpStatus::OK);
        $this->assertEquals(200, $response->getStatusCode());

        $response = Response::withStatus(HttpStatus::NOT_FOUND);
        $this->assertEquals(404, $response->getStatusCode());
    }
}
