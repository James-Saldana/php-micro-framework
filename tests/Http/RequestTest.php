<?php

declare(strict_types=1);

namespace Tests\Http;

use App\Http\Request;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for Request class.
 */
class RequestTest extends TestCase
{
    public function testGetMethod(): void
    {
        $request = new Request('GET', '/test');
        $this->assertEquals('GET', $request->getMethod());

        $request = new Request('POST', '/test');
        $this->assertEquals('POST', $request->getMethod());
    }

    public function testMethodIsUppercase(): void
    {
        $request = new Request('get', '/test');
        $this->assertEquals('GET', $request->getMethod());

        $request = new Request('post', '/test');
        $this->assertEquals('POST', $request->getMethod());
    }

    public function testGetUri(): void
    {
        $request = new Request('GET', '/test/path?query=value');
        $this->assertEquals('/test/path?query=value', $request->getUri());
    }

    public function testGetPath(): void
    {
        $request = new Request('GET', '/test/path?query=value');
        $this->assertEquals('/test/path', $request->getPath());

        $request = new Request('GET', '/test/path');
        $this->assertEquals('/test/path', $request->getPath());
    }

    public function testGetQueryString(): void
    {
        $request = new Request('GET', '/test?foo=bar&baz=qux', 'foo=bar&baz=qux');
        $this->assertEquals('foo=bar&baz=qux', $request->getQueryString());
    }

    public function testGetQueryParams(): void
    {
        $params = ['foo' => 'bar', 'baz' => 'qux'];
        $request = new Request('GET', '/test', '', $params);
        $this->assertEquals($params, $request->getQueryParams());
    }

    public function testGetQueryParam(): void
    {
        $params = ['foo' => 'bar', 'baz' => 'qux'];
        $request = new Request('GET', '/test', '', $params);

        $this->assertEquals('bar', $request->getQueryParam('foo'));
        $this->assertEquals('qux', $request->getQueryParam('baz'));
        $this->assertEquals('default', $request->getQueryParam('nonexistent', 'default'));
        $this->assertNull($request->getQueryParam('nonexistent'));
    }

    public function testGetHeaders(): void
    {
        $headers = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer token'];
        $request = new Request('GET', '/test', '', [], $headers);
        $this->assertEquals($headers, $request->getHeaders());
    }

    public function testGetHeader(): void
    {
        $headers = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer token'];
        $request = new Request('GET', '/test', '', [], $headers);

        $this->assertEquals('application/json', $request->getHeader('Content-Type'));
        $this->assertEquals('Bearer token', $request->getHeader('Authorization'));
        $this->assertNull($request->getHeader('X-Custom'));
    }

    public function testHeaderLookupIsCaseInsensitive(): void
    {
        $headers = ['Content-Type' => 'application/json'];
        $request = new Request('GET', '/test', '', [], $headers);

        $this->assertEquals('application/json', $request->getHeader('content-type'));
        $this->assertEquals('application/json', $request->getHeader('CONTENT-TYPE'));
        $this->assertEquals('application/json', $request->getHeader('Content-Type'));
    }

    public function testHasHeader(): void
    {
        $headers = ['Content-Type' => 'application/json'];
        $request = new Request('GET', '/test', '', [], $headers);

        $this->assertTrue($request->hasHeader('Content-Type'));
        $this->assertTrue($request->hasHeader('content-type'));
        $this->assertFalse($request->hasHeader('Authorization'));
    }

    public function testGetBody(): void
    {
        $body = '{"name": "test"}';
        $request = new Request('POST', '/test', '', [], [], [], $body);
        $this->assertEquals($body, $request->getBody());
    }

    public function testGetParsedBody(): void
    {
        $parsedBody = ['name' => 'test', 'value' => 123];
        $request = new Request('POST', '/test', '', [], [], [], '', $parsedBody);
        $this->assertEquals($parsedBody, $request->getParsedBody());
    }

    public function testGetParsedBodyParam(): void
    {
        $parsedBody = ['name' => 'test', 'value' => 123];
        $request = new Request('POST', '/test', '', [], [], [], '', $parsedBody);

        $this->assertEquals('test', $request->getParsedBodyParam('name'));
        $this->assertEquals(123, $request->getParsedBodyParam('value'));
        $this->assertEquals('default', $request->getParsedBodyParam('nonexistent', 'default'));
        $this->assertNull($request->getParsedBodyParam('nonexistent'));
    }

    public function testGetAttributes(): void
    {
        $attributes = ['id' => '123', 'name' => 'test'];
        $request = new Request('GET', '/test', '', [], [], [], '', [], $attributes);
        $this->assertEquals($attributes, $request->getAttributes());
    }

    public function testGetAttribute(): void
    {
        $attributes = ['id' => '123', 'name' => 'test'];
        $request = new Request('GET', '/test', '', [], [], [], '', [], $attributes);

        $this->assertEquals('123', $request->getAttribute('id'));
        $this->assertEquals('test', $request->getAttribute('name'));
        $this->assertEquals('default', $request->getAttribute('nonexistent', 'default'));
        $this->assertNull($request->getAttribute('nonexistent'));
    }

    public function testHasAttribute(): void
    {
        $attributes = ['id' => '123'];
        $request = new Request('GET', '/test', '', [], [], [], '', [], $attributes);

        $this->assertTrue($request->hasAttribute('id'));
        $this->assertFalse($request->hasAttribute('nonexistent'));
    }

    public function testGetServer(): void
    {
        $server = ['REMOTE_ADDR' => '127.0.0.1', 'HTTP_HOST' => 'localhost'];
        $request = new Request('GET', '/test', '', [], [], $server);
        $this->assertEquals($server, $request->getServer());
    }

    public function testGetServerParam(): void
    {
        $server = ['REMOTE_ADDR' => '127.0.0.1', 'HTTP_HOST' => 'localhost'];
        $request = new Request('GET', '/test', '', [], [], $server);

        $this->assertEquals('127.0.0.1', $request->getServerParam('REMOTE_ADDR'));
        $this->assertEquals('localhost', $request->getServerParam('HTTP_HOST'));
        $this->assertEquals('default', $request->getServerParam('nonexistent', 'default'));
        $this->assertNull($request->getServerParam('nonexistent'));
    }

    public function testIsAjax(): void
    {
        $request = new Request('GET', '/test', '', [], ['X-Requested-With' => 'XMLHttpRequest']);
        $this->assertTrue($request->isAjax());

        $request = new Request('GET', '/test', '', [], []);
        $this->assertFalse($request->isAjax());

        $request = new Request('GET', '/test', '', [], ['X-Requested-With' => 'HTML']);
        $this->assertFalse($request->isAjax());
    }

    public function testExpectsJson(): void
    {
        $request = new Request('GET', '/test', '', [], ['Accept' => 'application/json']);
        $this->assertTrue($request->expectsJson());

        $request = new Request('GET', '/test', '', [], []);
        $this->assertFalse($request->expectsJson());

        $request = new Request('GET', '/test', '', [], ['Accept' => 'text/html']);
        $this->assertFalse($request->expectsJson());
    }

    public function testGetClientIp(): void
    {
        // Test with REMOTE_ADDR
        $request = new Request('GET', '/test', '', [], [], ['REMOTE_ADDR' => '192.168.1.1']);
        $this->assertEquals('192.168.1.1', $request->getClientIp());

        // Test with HTTP_X_FORWARDED_FOR
        $request = new Request('GET', '/test', '', [], [], [
            'HTTP_X_FORWARDED_FOR' => '10.0.0.1, 192.168.1.1',
            'REMOTE_ADDR' => '127.0.0.1',
        ]);
        $this->assertEquals('10.0.0.1', $request->getClientIp());

        // Test with HTTP_CF_CONNECTING_IP (Cloudflare)
        $request = new Request('GET', '/test', '', [], [], [
            'HTTP_CF_CONNECTING_IP' => '203.0.113.1',
            'REMOTE_ADDR' => '127.0.0.1',
        ]);
        $this->assertEquals('203.0.113.1', $request->getClientIp());
    }

    public function testIsSecure(): void
    {
        // HTTPS is on
        $request = new Request('GET', '/test', '', [], [], ['HTTPS' => 'on']);
        $this->assertTrue($request->isSecure());

        // SERVER_PORT is 443
        $request = new Request('GET', '/test', '', [], [], ['SERVER_PORT' => '443']);
        $this->assertTrue($request->isSecure());

        // HTTP_X_FORWARDED_PROTO is https
        $request = new Request('GET', '/test', '', [], [], ['HTTP_X_FORWARDED_PROTO' => 'https']);
        $this->assertTrue($request->isSecure());

        // Not secure
        $request = new Request('GET', '/test', '', [], [], []);
        $this->assertFalse($request->isSecure());
    }

    public function testGetScheme(): void
    {
        $request = new Request('GET', '/test', '', [], [], ['HTTPS' => 'on']);
        $this->assertEquals('https', $request->getScheme());

        $request = new Request('GET', '/test', '', [], [], []);
        $this->assertEquals('http', $request->getScheme());
    }

    public function testGetHost(): void
    {
        $request = new Request('GET', '/test', '', [], [], ['HTTP_HOST' => 'example.com']);
        $this->assertEquals('example.com', $request->getHost());

        $request = new Request('GET', '/test', '', [], [], ['SERVER_NAME' => 'localhost']);
        $this->assertEquals('localhost', $request->getHost());

        $request = new Request('GET', '/test', '', [], [], []);
        $this->assertEquals('localhost', $request->getHost());
    }

    public function testGetUrl(): void
    {
        $request = new Request('GET', '/test/path?query=value', '', [], [], [
            'HTTPS' => 'on',
            'HTTP_HOST' => 'example.com',
        ]);
        $this->assertEquals('https://example.com/test/path?query=value', $request->getUrl());

        $request = new Request('GET', '/test', '', [], [], [
            'HTTP_HOST' => 'localhost',
        ]);
        $this->assertEquals('http://localhost/test', $request->getUrl());
    }
}
