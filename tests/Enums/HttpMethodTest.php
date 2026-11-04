<?php

declare(strict_types=1);

namespace Tests\Enums;

use App\Enums\HttpMethod;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for HttpMethod enum.
 */
class HttpMethodTest extends TestCase
{
    public function testAllMethodsAreValid(): void
    {
        $methods = [
            'GET',
            'POST',
            'PUT',
            'PATCH',
            'DELETE',
            'OPTIONS',
            'HEAD',
        ];

        foreach ($methods as $method) {
            $this->assertTrue(HttpMethod::isValid($method));
        }
    }

    public function testInvalidMethod(): void
    {
        $this->assertFalse(HttpMethod::isValid('INVALID'));
        $this->assertFalse(HttpMethod::isValid(''));
        $this->assertFalse(HttpMethod::isValid('get'));
    }

    public function testAllReturnsAllMethods(): void
    {
        $all = HttpMethod::all();
        $this->assertContains('GET', $all);
        $this->assertContains('POST', $all);
        $this->assertContains('PUT', $all);
        $this->assertContains('PATCH', $all);
        $this->assertContains('DELETE', $all);
        $this->assertContains('OPTIONS', $all);
        $this->assertContains('HEAD', $all);
        $this->assertCount(7, $all);
    }

    public function testEnumValues(): void
    {
        $this->assertEquals('GET', HttpMethod::GET->value);
        $this->assertEquals('POST', HttpMethod::POST->value);
        $this->assertEquals('PUT', HttpMethod::PUT->value);
        $this->assertEquals('PATCH', HttpMethod::PATCH->value);
        $this->assertEquals('DELETE', HttpMethod::DELETE->value);
        $this->assertEquals('OPTIONS', HttpMethod::OPTIONS->value);
        $this->assertEquals('HEAD', HttpMethod::HEAD->value);
    }

    public function testFromString(): void
    {
        $this->assertEquals(HttpMethod::GET, HttpMethod::from('GET'));
        $this->assertEquals(HttpMethod::POST, HttpMethod::from('POST'));
    }

    public function testCases(): void
    {
        $cases = HttpMethod::cases();
        $this->assertCount(7, $cases);
        $this->assertInstanceOf(HttpMethod::class, $cases[0]);
    }
}
