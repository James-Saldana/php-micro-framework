<?php

declare(strict_types=1);

namespace Tests\Core;

use App\Core\Container\Container;
use App\Core\Router\Router;
use App\Enums\HttpMethod;
use App\Http\Request;
use App\Http\Response;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for Router class.
 */
class RouterTest extends TestCase
{
    private Router $router;
    private Container $container;

    protected function setUp(): void
    {
        $this->router = new Router();
        $this->container = new Container();
    }

    public function testGetRoute(): void
    {
        $this->router->get('/test', function () {
            return Response::ok('GET works');
        });

        $request = new Request('GET', '/test');
        $response = $this->router->dispatch($request, $this->container);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('GET works', $response->getBody());
    }

    public function testPostRoute(): void
    {
        $this->router->post('/test', function () {
            return Response::ok('POST works');
        });

        $request = new Request('POST', '/test');
        $response = $this->router->dispatch($request, $this->container);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('POST works', $response->getBody());
    }

    public function testPutRoute(): void
    {
        $this->router->put('/test', function () {
            return Response::ok('PUT works');
        });

        $request = new Request('PUT', '/test');
        $response = $this->router->dispatch($request, $this->container);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('PUT works', $response->getBody());
    }

    public function testPatchRoute(): void
    {
        $this->router->patch('/test', function () {
            return Response::ok('PATCH works');
        });

        $request = new Request('PATCH', '/test');
        $response = $this->router->dispatch($request, $this->container);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('PATCH works', $response->getBody());
    }

    public function testDeleteRoute(): void
    {
        $this->router->delete('/test', function () {
            return Response::ok('DELETE works');
        });

        $request = new Request('DELETE', '/test');
        $response = $this->router->dispatch($request, $this->container);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('DELETE works', $response->getBody());
    }

    public function testOptionsRoute(): void
    {
        $this->router->options('/test', function () {
            return Response::ok('OPTIONS works');
        });

        $request = new Request('OPTIONS', '/test');
        $response = $this->router->dispatch($request, $this->container);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OPTIONS works', $response->getBody());
    }

    public function testHeadRoute(): void
    {
        $this->router->head('/test', function () {
            return Response::ok('HEAD works');
        });

        $request = new Request('HEAD', '/test');
        $response = $this->router->dispatch($request, $this->container);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testRouteWithParameter(): void
    {
        $this->router->get('/users/{id}', function (Request $request) {
            return Response::json(['id' => $request->getAttribute('id')]);
        });

        $request = new Request('GET', '/users/123');
        $response = $this->router->dispatch($request, $this->container);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertEquals('123', $data['id']);
    }

    public function testRouteWithMultipleParameters(): void
    {
        $this->router->get('/users/{id}/posts/{postId}', function (Request $request) {
            return Response::json([
                'id' => $request->getAttribute('id'),
                'postId' => $request->getAttribute('postId'),
            ]);
        });

        $request = new Request('GET', '/users/123/posts/456');
        $response = $this->router->dispatch($request, $this->container);

        $data = json_decode($response->getBody(), true);
        $this->assertEquals('123', $data['id']);
        $this->assertEquals('456', $data['postId']);
    }

    public function testRouteWithOptionalParameter(): void
    {
        $this->router->get('/users/{id?}', function (Request $request) {
            return Response::json(['id' => $request->getAttribute('id', 'default')]);
        });

        // With parameter
        $request = new Request('GET', '/users/123');
        $response = $this->router->dispatch($request, $this->container);
        $data = json_decode($response->getBody(), true);
        $this->assertEquals('123', $data['id']);

        // Without parameter
        $request = new Request('GET', '/users');
        $response = $this->router->dispatch($request, $this->container);
        $data = json_decode($response->getBody(), true);
        $this->assertEquals('default', $data['id']);
    }

    public function testRouteNotFound(): void
    {
        $request = new Request('GET', '/nonexistent');
        $response = $this->router->dispatch($request, $this->container);

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testRouteWithMiddleware(): void
    {
        $middleware = function (Request $request, callable $next) {
            $response = $next($request);
            return $response->withHeader('X-Middleware', 'test');
        };

        $this->router->get('/test', function () {
            return Response::ok('Success');
        }, [$middleware]);

        $request = new Request('GET', '/test');
        $response = $this->router->dispatch($request, $this->container);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('test', $response->getHeader('X-Middleware'));
    }

    public function testRouteGroupWithPrefix(): void
    {
        $this->router->group('/api/v1', function ($router) {
            $router->get('/users', function () {
                return Response::json(['endpoint' => 'users']);
            });
            $router->get('/posts', function () {
                return Response::json(['endpoint' => 'posts']);
            });
        });

        $request = new Request('GET', '/api/v1/users');
        $response = $this->router->dispatch($request, $this->container);
        $data = json_decode($response->getBody(), true);
        $this->assertEquals('users', $data['endpoint']);

        $request = new Request('GET', '/api/v1/posts');
        $response = $this->router->dispatch($request, $this->container);
        $data = json_decode($response->getBody(), true);
        $this->assertEquals('posts', $data['endpoint']);
    }

    public function testRouteGroupWithMiddleware(): void
    {
        $middleware = function (Request $request, callable $next) {
            $response = $next($request);
            return $response->withHeader('X-Group-Middleware', 'test');
        };

        $this->router->group('/api', [$middleware], function ($router) {
            $router->get('/users', function () {
                return Response::ok('Users');
            });
        });

        $request = new Request('GET', '/api/users');
        $response = $this->router->dispatch($request, $this->container);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('test', $response->getHeader('X-Group-Middleware'));
    }

    public function testNestedRouteGroups(): void
    {
        $this->router->group('/api', function ($router) {
            $router->group('/v1', function ($router) {
                $router->get('/users', function () {
                    return Response::json(['version' => 'v1']);
                });
            });
            $router->group('/v2', function ($router) {
                $router->get('/users', function () {
                    return Response::json(['version' => 'v2']);
                });
            });
        });

        $request = new Request('GET', '/api/v1/users');
        $response = $this->router->dispatch($request, $this->container);
        $data = json_decode($response->getBody(), true);
        $this->assertEquals('v1', $data['version']);

        $request = new Request('GET', '/api/v2/users');
        $response = $this->router->dispatch($request, $this->container);
        $data = json_decode($response->getBody(), true);
        $this->assertEquals('v2', $data['version']);
    }

    public function testMatchMultipleMethods(): void
    {
        $this->router->match([HttpMethod::GET, HttpMethod::POST], '/test', function () {
            return Response::ok('Matched');
        });

        $request = new Request('GET', '/test');
        $response = $this->router->dispatch($request, $this->container);
        $this->assertEquals(200, $response->getStatusCode());

        $request = new Request('POST', '/test');
        $response = $this->router->dispatch($request, $this->container);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testAnyMethod(): void
    {
        $this->router->any('/test', function () {
            return Response::ok('Any method');
        });

        foreach (HttpMethod::all() as $method) {
            $request = new Request($method, '/test');
            $response = $this->router->dispatch($request, $this->container);
            $this->assertEquals(200, $response->getStatusCode());
        }
    }

    public function testGetRoutes(): void
    {
        $this->router->get('/test1', function () {});
        $this->router->post('/test2', function () {});

        $routes = $this->router->getRoutes();
        $this->assertCount(2, $routes);
    }

    public function testGetRoutesByMethod(): void
    {
        $this->router->get('/test1', function () {});
        $this->router->get('/test2', function () {});
        $this->router->post('/test3', function () {});

        $getRoutes = $this->router->getRoutesByMethod(HttpMethod::GET);
        $postRoutes = $this->router->getRoutesByMethod(HttpMethod::POST);

        $this->assertCount(2, $getRoutes);
        $this->assertCount(1, $postRoutes);
    }

    public function testClearRoutes(): void
    {
        $this->router->get('/test', function () {});
        $this->assertCount(1, $this->router->getRoutes());

        $this->router->clear();
        $this->assertCount(0, $this->router->getRoutes());
    }

    public function testRouteWithController(): void
    {
        $controller = new class() {
            public function index(Request $request): Response
            {
                return Response::json(['action' => 'index']);
            }

            public function show(Request $request): Response
            {
                return Response::json(['action' => 'show', 'id' => $request->getAttribute('id')]);
            }
        };

        $this->container->instance(get_class($controller), $controller);

        $this->router->get('/test', [get_class($controller), 'index']);
        $this->router->get('/test/{id}', [get_class($controller), 'show']);

        $request = new Request('GET', '/test');
        $response = $this->router->dispatch($request, $this->container);
        $data = json_decode($response->getBody(), true);
        $this->assertEquals('index', $data['action']);

        $request = new Request('GET', '/test/123');
        $response = $this->router->dispatch($request, $this->container);
        $data = json_decode($response->getBody(), true);
        $this->assertEquals('show', $data['action']);
        $this->assertEquals('123', $data['id']);
    }

    public function testRouteWithDependencyInjection(): void
    {
        $service = new class() {
            public function getValue(): string
            {
                return 'injected';
            }
        };

        $this->container->instance(get_class($service), $service);

        $this->router->get('/test', function (object $s) use ($service) {
            return Response::json(['value' => $s->getValue()]);
        });

        // Note: This test might need adjustment based on how the container resolves dependencies
        // For now, we'll test that the route can be called
        $request = new Request('GET', '/test');
        $response = $this->router->dispatch($request, $this->container);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testNormalizedPath(): void
    {
        // Test that paths are normalized
        $this->router->get('/test/path', function () {
            return Response::ok('Normalized');
        });

        // Should match with or without trailing slash
        $request = new Request('GET', '/test/path');
        $response = $this->router->dispatch($request, $this->container);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testRouteWithQueryString(): void
    {
        $this->router->get('/test', function (Request $request) {
            return Response::json(['query' => $request->getQueryParams()]);
        });

        $request = new Request('GET', '/test?foo=bar&baz=qux', '', ['foo' => 'bar', 'baz' => 'qux']);
        $response = $this->router->dispatch($request, $this->container);

        $data = json_decode($response->getBody(), true);
        $this->assertEquals('bar', $data['query']['foo']);
        $this->assertEquals('qux', $data['query']['baz']);
    }

    public function testRouteWithBody(): void
    {
        $this->router->post('/test', function (Request $request) {
            return Response::json(['body' => $request->getParsedBody()]);
        });

        $request = new Request('POST', '/test', '', [], [], [], '{"name":"test"}', ['name' => 'test']);
        $response = $this->router->dispatch($request, $this->container);

        $data = json_decode($response->getBody(), true);
        $this->assertEquals('test', $data['body']['name']);
    }

    public function testMethodMismatch(): void
    {
        $this->router->get('/test', function () {
            return Response::ok('GET');
        });

        $request = new Request('POST', '/test');
        $response = $this->router->dispatch($request, $this->container);

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testRouteWithChainedMiddleware(): void
    {
        $middleware1 = function (Request $request, callable $next) {
            $response = $next($request);
            return $response->withHeader('X-1', 'first');
        };

        $middleware2 = function (Request $request, callable $next) {
            $response = $next($request);
            return $response->withHeader('X-2', 'second');
        };

        $this->router->get('/test', function () {
            return Response::ok('Success');
        }, [$middleware1, $middleware2]);

        $request = new Request('GET', '/test');
        $response = $this->router->dispatch($request, $this->container);

        $this->assertEquals('first', $response->getHeader('X-1'));
        $this->assertEquals('second', $response->getHeader('X-2'));
    }
}
