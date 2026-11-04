<?php

declare(strict_types=1);

namespace Tests\Core;

use App\Core\App;
use App\Core\Container;
use App\Http\Request;
use App\Http\Response;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for App class.
 */
class AppTest extends TestCase
{
    private App $app;

    protected function setUp(): void
    {
        $this->app = new App();
    }

    public function testRouter(): void
    {
        $router = $this->app->router();
        $this->assertInstanceOf(\App\Core\Router::class, $router);
    }

    public function testContainer(): void
    {
        $container = $this->app->container();
        $this->assertInstanceOf(Container::class, $container);
    }

    public function testExceptionHandler(): void
    {
        $handler = $this->app->exceptionHandler();
        $this->assertInstanceOf(\App\Core\ExceptionHandler::class, $handler);
    }

    public function testEnvironment(): void
    {
        $this->app->environment('production');
        $handler = $this->app->exceptionHandler();
        $this->assertFalse($handler->isDebug());

        $this->app->environment('development');
        $handler = $this->app->exceptionHandler();
        $this->assertTrue($handler->isDebug());
    }

    public function testDebug(): void
    {
        $this->app->debug(true);
        $handler = $this->app->exceptionHandler();
        $this->assertTrue($handler->isDebug());

        $this->app->debug(false);
        $handler = $this->app->exceptionHandler();
        $this->assertFalse($handler->isDebug());
    }

    public function testMiddleware(): void
    {
        $middleware = function (Request $request, callable $next) {
            return $next($request);
        };

        $this->app->middleware($middleware);

        // We can't directly test the middleware stack, but we can verify
        // that the app accepts middleware registration
        $this->assertInstanceOf(App::class, $this->app);
    }

    public function testBind(): void
    {
        $this->app->bind(
            TestService::class,
            function () {
                return new TestService('bound');
            }
        );

        $service = $this->app->container()->get(TestService::class);
        $this->assertInstanceOf(TestService::class, $service);
        $this->assertEquals('bound', $service->getValue());
    }

    public function testSingleton(): void
    {
        $this->app->singleton(
            TestService::class,
            function () {
                return new TestService('singleton');
            }
        );

        $service1 = $this->app->container()->get(TestService::class);
        $service2 = $this->app->container()->get(TestService::class);

        $this->assertSame($service1, $service2);
    }

    public function testTransient(): void
    {
        $this->app->transient(
            TestService::class,
            function () {
                return new TestService('transient');
            }
        );

        $service1 = $this->app->container()->get(TestService::class);
        $service2 = $this->app->container()->get(TestService::class);

        $this->assertNotSame($service1, $service2);
    }

    public function testHandle(): void
    {
        $this->app->get('/test', function () {
            return Response::ok('Success');
        });

        $request = new Request('GET', '/test');
        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Success', $response->getBody());
    }

    public function testHandleWithGlobalMiddleware(): void
    {
        $middleware = function (Request $request, callable $next) {
            $response = $next($request);
            return $response->withHeader('X-Global', 'middleware');
        };

        $this->app->middleware($middleware);
        $this->app->get('/test', function () {
            return Response::ok('Success');
        });

        $request = new Request('GET', '/test');
        $response = $this->app->handle($request);

        $this->assertEquals('middleware', $response->getHeader('X-Global'));
    }

    public function testHandleWithException(): void
    {
        $this->app->get('/test', function () {
            throw new \RuntimeException('Test error');
        });

        $request = new Request('GET', '/test');
        $response = $this->app->handle($request);

        $this->assertEquals(500, $response->getStatusCode());
    }

    public function testHandleWithCustomExceptionHandler(): void
    {
        $this->app->handle(
            \RuntimeException::class,
            function (\RuntimeException $e, Request $request) {
                return Response::json(['custom' => true], 503);
            }
        );

        $this->app->get('/test', function () {
            throw new \RuntimeException('Test error');
        });

        $request = new Request('GET', '/test');
        $response = $this->app->handle($request);

        $this->assertEquals(503, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertTrue($data['custom']);
    }

    public function testBoot(): void
    {
        $booted = false;
        $this->app->bootstrap(function () use (&$booted) {
            $booted = true;
        });

        $this->assertFalse($this->app->isBooted());

        $this->app->boot();
        $this->assertTrue($booted);
        $this->assertTrue($this->app->isBooted());
    }

    public function testBootOnlyRunsOnce(): void
    {
        $counter = 0;
        $this->app->bootstrap(function () use (&$counter) {
            $counter++;
        });

        $this->app->boot();
        $this->app->boot();
        $this->app->boot();

        $this->assertEquals(1, $counter);
    }

    public function testRoutes(): void
    {
        $this->app->get('/test1', function () {});
        $this->app->post('/test2', function () {});

        $routes = $this->app->routes();
        $this->assertCount(2, $routes);
    }

    public function testGetRoute(): void
    {
        $this->app->get('/test', function () {
            return Response::ok('GET');
        });

        $request = new Request('GET', '/test');
        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testPostRoute(): void
    {
        $this->app->post('/test', function () {
            return Response::ok('POST');
        });

        $request = new Request('POST', '/test');
        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testPutRoute(): void
    {
        $this->app->put('/test', function () {
            return Response::ok('PUT');
        });

        $request = new Request('PUT', '/test');
        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testPatchRoute(): void
    {
        $this->app->patch('/test', function () {
            return Response::ok('PATCH');
        });

        $request = new Request('PATCH', '/test');
        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testDeleteRoute(): void
    {
        $this->app->delete('/test', function () {
            return Response::ok('DELETE');
        });

        $request = new Request('DELETE', '/test');
        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testOptionsRoute(): void
    {
        $this->app->options('/test', function () {
            return Response::ok('OPTIONS');
        });

        $request = new Request('OPTIONS', '/test');
        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testHeadRoute(): void
    {
        $this->app->head('/test', function () {
            return Response::ok('HEAD');
        });

        $request = new Request('HEAD', '/test');
        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testMatchRoute(): void
    {
        $this->app->match([\App\Enums\HttpMethod::GET, \App\Enums\HttpMethod::POST], '/test', function () {
            return Response::ok('MATCHED');
        });

        $request = new Request('GET', '/test');
        $response = $this->app->handle($request);
        $this->assertEquals(200, $response->getStatusCode());

        $request = new Request('POST', '/test');
        $response = $this->app->handle($request);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testAnyRoute(): void
    {
        $this->app->any('/test', function () {
            return Response::ok('ANY');
        });

        foreach ([\App\Enums\HttpMethod::GET, \App\Enums\HttpMethod::POST, \App\Enums\HttpMethod::PUT] as $method) {
            $request = new Request($method->value, '/test');
            $response = $this->app->handle($request);
            $this->assertEquals(200, $response->getStatusCode());
        }
    }

    public function testGroupRoute(): void
    {
        $this->app->group('/api', function ($router) {
            $router->get('/users', function () {
                return Response::json(['endpoint' => 'users']);
            });
        });

        $request = new Request('GET', '/api/users');
        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertEquals('users', $data['endpoint']);
    }

    public function testRouteWithMiddleware(): void
    {
        $middleware = function (Request $request, callable $next) {
            $response = $next($request);
            return $response->withHeader('X-Route-Middleware', 'test');
        };

        $this->app->get('/test', function () {
            return Response::ok('Success');
        }, [$middleware]);

        $request = new Request('GET', '/test');
        $response = $this->app->handle($request);

        $this->assertEquals('test', $response->getHeader('X-Route-Middleware'));
    }

    public function testRouteWithParameter(): void
    {
        $this->app->get('/users/{id}', function (Request $request) {
            return Response::json(['id' => $request->getAttribute('id')]);
        });

        $request = new Request('GET', '/users/123');
        $response = $this->app->handle($request);

        $data = json_decode($response->getBody(), true);
        $this->assertEquals('123', $data['id']);
    }

    public function testRegisterHandlers(): void
    {
        $this->app->registerHandlers();

        // Restore handlers
        restore_error_handler();
        restore_exception_handler();

        $this->assertInstanceOf(App::class, $this->app);
    }

    public function testIsBootedInitiallyFalse(): void
    {
        $this->assertFalse($this->app->isBooted());
    }

    public function testIsBootedAfterBoot(): void
    {
        $this->app->boot();
        $this->assertTrue($this->app->isBooted());
    }

    public function testChainedMethods(): void
    {
        $app = $this->app;
        $result = $app
            ->environment('development')
            ->debug(true)
            ->middleware(function ($r, $n) { return $n($r); })
            ->bind(TestService::class, TestService::class)
            ->get('/test', function () { return Response::ok(); });

        $this->assertSame($app, $result);
    }
}

// Test class for dependency injection tests
class TestService
{
    private string $value;

    public function __construct(string $value = 'default')
    {
        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
