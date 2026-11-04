<?php

declare(strict_types=1);

namespace Tests\Enums;

use App\Enums\HttpStatus;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for HttpStatus enum.
 */
class HttpStatusTest extends TestCase
{
    public function testInformationalStatusCodes(): void
    {
        $this->assertTrue(HttpStatus::CONTINUE->isInformational());
        $this->assertTrue(HttpStatus::SWITCHING_PROTOCOLS->isInformational());
        $this->assertFalse(HttpStatus::OK->isInformational());
    }

    public function testSuccessStatusCodes(): void
    {
        $this->assertTrue(HttpStatus::OK->isSuccess());
        $this->assertTrue(HttpStatus::CREATED->isSuccess());
        $this->assertTrue(HttpStatus::NO_CONTENT->isSuccess());
        $this->assertFalse(HttpStatus::BAD_REQUEST->isSuccess());
    }

    public function testRedirectionStatusCodes(): void
    {
        $this->assertTrue(HttpStatus::MOVED_PERMANENTLY->isRedirection());
        $this->assertTrue(HttpStatus::FOUND->isRedirection());
        $this->assertTrue(HttpStatus::TEMPORARY_REDIRECT->isRedirection());
        $this->assertFalse(HttpStatus::OK->isRedirection());
    }

    public function testClientErrorStatusCodes(): void
    {
        $this->assertTrue(HttpStatus::BAD_REQUEST->isClientError());
        $this->assertTrue(HttpStatus::UNAUTHORIZED->isClientError());
        $this->assertTrue(HttpStatus::FORBIDDEN->isClientError());
        $this->assertTrue(HttpStatus::NOT_FOUND->isClientError());
        $this->assertFalse(HttpStatus::OK->isClientError());
    }

    public function testServerErrorStatusCodes(): void
    {
        $this->assertTrue(HttpStatus::INTERNAL_SERVER_ERROR->isServerError());
        $this->assertTrue(HttpStatus::BAD_GATEWAY->isServerError());
        $this->assertTrue(HttpStatus::SERVICE_UNAVAILABLE->isServerError());
        $this->assertFalse(HttpStatus::OK->isServerError());
    }

    public function testReasonPhrases(): void
    {
        $this->assertEquals('OK', HttpStatus::OK->getReasonPhrase());
        $this->assertEquals('Created', HttpStatus::CREATED->getReasonPhrase());
        $this->assertEquals('Bad Request', HttpStatus::BAD_REQUEST->getReasonPhrase());
        $this->assertEquals('Not Found', HttpStatus::NOT_FOUND->getReasonPhrase());
        $this->assertEquals('Internal Server Error', HttpStatus::INTERNAL_SERVER_ERROR->getReasonPhrase());
    }

    public function testFromStatusCode(): void
    {
        $this->assertEquals(HttpStatus::OK, HttpStatus::from(200));
        $this->assertEquals(HttpStatus::CREATED, HttpStatus::from(201));
        $this->assertEquals(HttpStatus::BAD_REQUEST, HttpStatus::from(400));
        $this->assertEquals(HttpStatus::NOT_FOUND, HttpStatus::from(404));
        $this->assertEquals(HttpStatus::INTERNAL_SERVER_ERROR, HttpStatus::from(500));
    }

    public function testFromInvalidStatusCodeThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        HttpStatus::from(999);
    }

    public function testStatusCodeValues(): void
    {
        $this->assertEquals(200, HttpStatus::OK->value);
        $this->assertEquals(201, HttpStatus::CREATED->value);
        $this->assertEquals(400, HttpStatus::BAD_REQUEST->value);
        $this->assertEquals(404, HttpStatus::NOT_FOUND->value);
        $this->assertEquals(500, HttpStatus::INTERNAL_SERVER_ERROR->value);
    }

    public function testCommonStatusCodesExist(): void
    {
        $commonCodes = [
            100, 101, 102, 103, // 1xx
            200, 201, 202, 204, 205, 206, // 2xx
            301, 302, 303, 304, 307, 308, // 3xx
            400, 401, 403, 404, 405, 406, 408, 409, 410, 422, 429, // 4xx
            500, 501, 502, 503, 504, // 5xx
        ];

        foreach ($commonCodes as $code) {
            try {
                $status = HttpStatus::from($code);
                $this->assertInstanceOf(HttpStatus::class, $status);
            } catch (\InvalidArgumentException) {
                $this->fail("Status code {$code} should be valid");
            }
        }
    }
}
