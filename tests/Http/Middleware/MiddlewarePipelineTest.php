<?php

declare(strict_types=1);

namespace Tests\Http\Middleware;

use App\Contracts\Http\Middleware\MiddlewareInterface;
use App\Http\Middleware\MiddlewarePipeline;
use App\Http\Request;
use App\Http\Response;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for MiddlewarePipeline class.
 */
class MiddlewarePipelineTest extends TestCase
{
    public function testEmptyPipeline(): void
    {
        $pipeline = new MiddlewarePipeline();
        $request = new Request('GET', '/test');

        $handler = function (Request $request): Response {
            return Response::ok('Success');
        };

        $response = $pipeline->handle($request, $handler);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Success', $response->getBody());
    }

    public function testSingleMiddleware(): void
    {
        $pipeline = new MiddlewarePipeline();
        $request = new Request('GET', '/test');

        $middleware = new class implements MiddlewareInterface {
            public function handle(Request $request, callable $next): Response
            {
                $response = $next($request);
                return $response->withHeader('X-Middleware', 'test');
            }
        };

        $pipeline->add($middleware);

        $handler = function (Request $request): Response {
            return Response::ok('Success');
        };

        $response = $pipeline->handle($request, $handler);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('test', $response->getHeader('X-Middleware'));
    }

    public function testMultipleMiddleware(): void
    {
        $pipeline = new MiddlewarePipeline();
        $request = new Request('GET', '/test');

        $middleware1 = new class implements MiddlewareInterface {
            public function handle(Request $request, callable $next): Response
            {
                $response = $next($request);
                return $response->withHeader('X-1', 'first');
            }
        };

        $middleware2 = new class implements MiddlewareInterface {
            public function handle(Request $request, callable $next): Response
            {
                $response = $next($request);
                return $response->withHeader('X-2', 'second');
            }
        };

        $middleware3 = new class implements MiddlewareInterface {
            public function handle(Request $request, callable $next): Response
            {
                $response = $next($request);
                return $response->withHeader('X-3', 'third');
            }
        };

        $pipeline->add($middleware1);
        $pipeline->add($middleware2);
        $pipeline->add($middleware3);

        $handler = function (Request $request): Response {
            return Response::ok('Success');
        };

        $response = $pipeline->handle($request, $handler);
        $this->assertEquals('first', $response->getHeader('X-1'));
        $this->assertEquals('second', $response->getHeader('X-2'));
        $this->assertEquals('third', $response->getHeader('X-3'));
    }

    public function testMiddlewareCanShortCircuit(): void
    {
        $pipeline = new MiddlewarePipeline();
        $request = new Request('GET', '/test');

        $middleware = new class implements MiddlewareInterface {
            public function handle(Request $request, callable $next): Response
            {
                // Short-circuit: don't call $next
                return Response::forbidden('Access denied');
            }
        };

        $pipeline->add($middleware);

        $handler = function (Request $request): Response {
            return Response::ok('Success');
        };

        $response = $pipeline->handle($request, $handler);
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals('Access denied', $response->getBody());
    }

    public function testMiddlewareCanModifyRequest(): void
    {
        $pipeline = new MiddlewarePipeline();
        $request = new Request('GET', '/test', '', [], [], [], '', [], ['original' => 'value']);

        $middleware = new class implements MiddlewareInterface {
            public function handle(Request $request, callable $next): Response
            {
                // Modify request by adding attribute
                $request = new Request(
                    $request->getMethod(),
                    $request->getUri(),
                    $request->getQueryString(),
                    $request->getQueryParams(),
                    $request->getHeaders(),
                    $request->getServer(),
                    $request->getBody(),
                    $request->getParsedBody(),
                    array_merge($request->getAttributes(), ['modified' => 'new'])
                );
                return $next($request);
            }
        };

        $pipeline->add($middleware);

        $handler = function (Request $request): Response {
            return Response::json($request->getAttributes());
        };

        $response = $pipeline->handle($request, $handler);
        $data = json_decode($response->getBody(), true);
        $this->assertEquals('value', $data['original']);
        $this->assertEquals('new', $data['modified']);
    }

    public function testPrependMiddleware(): void
    {
        $pipeline = new MiddlewarePipeline();
        $request = new Request('GET', '/test');

        $middleware1 = new class implements MiddlewareInterface {
            public function handle(Request $request, callable $next): Response
            {
                $response = $next($request);
                return $response->withHeader('X-Order', '1');
            }
        };

        $middleware2 = new class implements MiddlewareInterface {
            public function handle(Request $request, callable $next): Response
            {
                $response = $next($request);
                return $response->withHeader('X-Order', '2');
            }
        };

        $pipeline->add($middleware1);
        $pipeline->prepend($middleware2);

        $handler = function (Request $request): Response {
            return Response::ok('Success');
        };

        $response = $pipeline->handle($request, $handler);
        // Middleware2 was prepended, so it should be executed first
        // In onion style, the first added middleware wraps last
        // So middleware2 (prepended) should be on the outside
        $this->assertEquals('2', $response->getHeader('X-Order'));
    }

    public function testCallableMiddleware(): void
    {
        $pipeline = new MiddlewarePipeline();
        $request = new Request('GET', '/test');

        $middleware = function (Request $request, callable $next): Response {
            $response = $next($request);
            return $response->withHeader('X-Callable', 'works');
        };

        $pipeline->add($middleware);

        $handler = function (Request $request): Response {
            return Response::ok('Success');
        };

        $response = $pipeline->handle($request, $handler);
        $this->assertEquals('works', $response->getHeader('X-Callable'));
    }

    public function testClearPipeline(): void
    {
        $pipeline = new MiddlewarePipeline();
        $middleware = new class implements MiddlewareInterface {
            public function handle(Request $request, callable $next): Response
            {
                return $next($request);
            }
        };

        $pipeline->add($middleware);
        $this->assertCount(1, $pipeline->all());

        $pipeline->clear();
        $this->assertCount(0, $pipeline->all());
    }

    public function testGetAllMiddleware(): void
    {
        $pipeline = new MiddlewarePipeline();
        $middleware1 = new class implements MiddlewareInterface {
            public function handle(Request $request, callable $next): Response
            {
                return $next($request);
            }
        };
        $middleware2 = new class implements MiddlewareInterface {
            public function handle(Request $request, callable $next): Response
            {
                return $next($request);
            }
        };

        $pipeline->add($middleware1);
        $pipeline->add($middleware2);

        $all = $pipeline->all();
        $this->assertCount(2, $all);
        $this->assertSame($middleware1, $all[0]);
        $this->assertSame($middleware2, $all[1]);
    }

    public function testStaticRunMethod(): void
    {
        $middleware = new class implements MiddlewareInterface {
            public function handle(Request $request, callable $next): Response
            {
                $response = $next($request);
                return $response->withHeader('X-Static', 'run');
            }
        };

        $request = new Request('GET', '/test');
        $handler = function (Request $request): Response {
            return Response::ok('Success');
        };

        $response = MiddlewarePipeline::run([$middleware], $request, $handler);
        $this->assertEquals('run', $response->getHeader('X-Static'));
    }

    public function testMiddlewareExecutionOrder(): void
    {
        $pipeline = new MiddlewarePipeline();
        $request = new Request('GET', '/test');
        $executionOrder = [];

        $middleware1 = new class($executionOrder) implements MiddlewareInterface {
            private array $order;
            public function __construct(array &$order) {
                $this->order = &$order;
            }
            public function handle(Request $request, callable $next): Response
            {
                $this->order[] = 'middleware1-before';
                $response = $next($request);
                $this->order[] = 'middleware1-after';
                return $response;
            }
        };

        $middleware2 = new class($executionOrder) implements MiddlewareInterface {
            private array $order;
            public function __construct(array &$order) {
                $this->order = &$order;
            }
            public function handle(Request $request, callable $next): Response
            {
                $this->order[] = 'middleware2-before';
                $response = $next($request);
                $this->order[] = 'middleware2-after';
                return $response;
            }
        };

        // Note: We need to use a different approach since we can't capture by reference in anonymous classes
        $order = [];
        
        $m1 = new class($order) implements MiddlewareInterface {
            private array $order;
            public function __construct(array &$order) {
                $this->order = &$order;
            }
            public function handle(Request $request, callable $next): Response
            {
                $this->order[] = 'm1-before';
                $response = $next($request);
                $this->order[] = 'm1-after';
                return $response;
            }
        };
        
        $m2 = new class($order) implements MiddlewareInterface {
            private array $order;
            public function __construct(array &$order) {
                $this->order = &$order;
            }
            public function handle(Request $request, callable $next): Response
            {
                $this->order[] = 'm2-before';
                $response = $next($request);
                $this->order[] = 'm2-after';
                return $response;
            }
        };

        $pipeline->add($m1);
        $pipeline->add($m2);

        $handler = function (Request $request) use (&$order): Response {
            $order[] = 'handler';
            return Response::ok('Success');
        };

        $pipeline->handle($request, $handler);

        // In onion style, the order should be:
        // m1-before, m2-before, handler, m2-after, m1-after
        $this->assertEquals('m1-before', $order[0]);
        $this->assertEquals('m2-before', $order[1]);
        $this->assertEquals('handler', $order[2]);
        $this->assertEquals('m2-after', $order[3]);
        $this->assertEquals('m1-after', $order[4]);
    }
}
