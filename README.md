# PHP Micro Framework

An extremely lightweight, high-performance PHP 8.2+ micro-framework built for simplicity and speed.

## Features

- **Ultra-lightweight**: Under 15 core files, no external dependencies
- **Modern PHP 8.2+**: Strict types, readonly properties, enums
- **Fast routing**: Expressive routing with parameters, constraints, and groups
- **Middleware pipeline**: Onion-style middleware support
- **DI Container**: Lightweight dependency injection
- **Error handling**: Centralized exception handling with JSON/HTML responses
- **PSR-12 compliant**: Clean, readable code following modern standards

## Requirements

- PHP 8.2 or higher
- Composer (optional, for autoloading)

## Installation

### Option 1: Clone the repository

```bash
cd /path/to/your/project
git clone https://github.com/your-username/php-micro-framework.git
cd php-micro-framework
composer install
```

### Option 2: Manual setup

1. Download the framework files
2. Place them in your project directory
3. Ensure PHP 8.2+ is installed

## Directory Structure

```
php-micro-framework/
├── src/
│   ├── Core/
│   │   ├── App.php              # Main application class
│   │   ├── Container.php        # Dependency injection container
│   │   ├── ExceptionHandler.php # Centralized error handling
│   │   └── Router.php           # HTTP router with route groups
│   ├── Enums/
│   │   ├── HttpMethod.php       # HTTP method enumeration
│   │   └── HttpStatus.php       # HTTP status code enumeration
│   └── Http/
│       ├── Middleware/
│       │   ├── MiddlewareInterface.php  # Middleware interface
│       │   └── MiddlewarePipeline.php   # Middleware pipeline
│       ├── Request.php          # HTTP request representation
│       └── Response.php         # HTTP response builder
├── bootstrap.php               # Application bootstrap
├── public/
│   └── index.php               # Public entry point
├── composer.json
└── README.md
```

## Quick Start

### 1. Create your entry point

Create a `public/index.php` file:

```php
<?php
require_once __DIR__ . '/../bootstrap.php';

$app = require __DIR__ . '/../bootstrap.php';

// Define your routes
$app->get('/', function (App\Http\Request $request) {
    return App\Http\Response::html('<h1>Hello World!</h1>');
});

$app->get('/api/users/{id}', function (App\Http\Request $request) {
    $id = $request->getAttribute('id');
    return App\Http\Response::json(['id' => $id, 'name' => 'John Doe']);
});

$app->run();
```

### 2. Configure your web server

**Apache (using .htaccess):**

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php [QSA,L]
</IfModule>
```

**Nginx:**

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/php-micro-framework/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

## Routing

### Basic Routes

```php
// GET route
$app->get('/users', function (App\Http\Request $request) {
    return App\Http\Response::json(['users' => []]);
});

// POST route
$app->post('/users', function (App\Http\Request $request) {
    $data = $request->getParsedBody();
    return App\Http\Response::json(['id' => 1, ...$data], 201);
});

// PUT route
$app->put('/users/{id}', function (App\Http\Request $request) {
    $id = $request->getAttribute('id');
    return App\Http\Response::json(['updated' => true]);
});

// PATCH route
$app->patch('/users/{id}', function (App\Http\Request $request) {
    $id = $request->getAttribute('id');
    return App\Http\Response::json(['patched' => true]);
});

// DELETE route
$app->delete('/users/{id}', function (App\Http\Request $request) {
    $id = $request->getAttribute('id');
    return App\Http\Response::json(['deleted' => true]);
});

// OPTIONS route
$app->options('/users', function (App\Http\Request $request) {
    return App\Http\Response::empty(204);
});

// HEAD route
$app->head('/users', function (App\Http\Request $request) {
    return App\Http\Response::empty();
});
```

### Route Parameters

```php
// Required parameter
$app->get('/users/{id}', function (App\Http\Request $request) {
    $id = $request->getAttribute('id');
    return App\Http\Response::json(['id' => $id]);
});

// Optional parameter
$app->get('/users/{id?}', function (App\Http\Request $request) {
    $id = $request->getAttribute('id', 'default');
    return App\Http\Response::json(['id' => $id]);
});

// Multiple parameters
$app->get('/users/{id}/posts/{postId}', function (App\Http\Request $request) {
    $id = $request->getAttribute('id');
    $postId = $request->getAttribute('postId');
    return App\Http\Response::json(['user' => $id, 'post' => $postId]);
});
```

### Route Groups

```php
$app->group('/api/v1', [
    // Group-level middleware would go here
], function ($router) {
    $router->get('/users', function (App\Http\Request $request) {
        return App\Http\Response::json(['version' => 'v1', 'users' => []]);
    });

    $router->get('/posts', function (App\Http\Request $request) {
        return App\Http\Response::json(['version' => 'v1', 'posts' => []]);
    });
});

// Nested groups
$app->group('/api', function ($router) {
    $router->group('/v1', function ($router) {
        $router->get('/users', fn() => App\Http\Response::json(['api' => 'v1']));
    });
    
    $router->group('/v2', function ($router) {
        $router->get('/users', fn() => App\Http\Response::json(['api' => 'v2']));
    });
});
```

### Match Multiple Methods

```php
// Match specific methods
$app->match([HttpMethod::GET, HttpMethod::POST], '/users', function () {
    return App\Http\Response::json(['message' => 'GET or POST']);
});

// Match all methods
$app->any('/wildcard', function () {
    return App\Http\Response::json(['message' => 'Any method']);
});
```

### Controller Routes

```php
// Using controller class
$app->get('/users', [UserController::class, 'index']);
$app->post('/users', [UserController::class, 'store']);
$app->get('/users/{id}', [UserController::class, 'show']);

// Example controller
class UserController
{
    public function index(App\Http\Request $request): App\Http\Response
    {
        return App\Http\Response::json(['users' => []]);
    }

    public function store(App\Http\Request $request): App\Http\Response
    {
        $data = $request->getParsedBody();
        return App\Http\Response::json(['id' => 1, ...$data], 201);
    }

    public function show(App\Http\Request $request): App\Http\Response
    {
        $id = $request->getAttribute('id');
        return App\Http\Response::json(['id' => $id]);
    }
}
```

## Middleware

### Creating Middleware

```php
use App\Http\Middleware\MiddlewareInterface;
use App\Http\Request;
use App\Http\Response;

class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        // Before request handling
        if (!$request->getHeader('Authorization')) {
            return App\Http\Response::unauthorized('Unauthorized');
        }

        // Call the next middleware/handler
        $response = $next($request);

        // After request handling
        return $response->withHeader('X-Auth', 'true');
    }
}

// Simple closure middleware
$authMiddleware = function (Request $request, callable $next): Response {
    if (!$request->getHeader('Authorization')) {
        return App\Http\Response::unauthorized('Unauthorized');
    }
    return $next($request);
};
```

### Using Middleware

```php
// Global middleware (runs on every request)
$app->middleware(new AuthMiddleware());
$app->middleware($authMiddleware);

// Route-specific middleware
$app->get('/admin', function () {
    return App\Http\Response::json(['admin' => true]);
}, [new AuthMiddleware()]);

// Multiple middleware
$app->get('/secure', function () {
    return App\Http\Response::json(['secure' => true]);
}, [new AuthMiddleware(), new RateLimitMiddleware()]);

// Group middleware
$app->group('/admin', [new AuthMiddleware()], function ($router) {
    $router->get('/dashboard', fn() => App\Http\Response::json(['page' => 'dashboard']));
    $router->get('/settings', fn() => App\Http\Response::json(['page' => 'settings']));
});
```

### Built-in Response Helpers

```php
// JSON responses
return App\Http\Response::json(['data' => $data]);
return App\Http\Response::json(['error' => 'Not found'], 404);

// HTML responses
return App\Http\Response::html('<h1>Hello</h1>');
return App\Http\Response::html('<p>Error</p>', 500);

// Text responses
return App\Http\Response::text('Hello World');

// Redirect responses
return App\Http\Response::redirect('/login');
return App\Http\Response::redirect('/dashboard', 301);

// Empty responses
return App\Http\Response::empty();
return App\Http\Response::noContent();

// Status responses
return App\Http\Response::ok();
return App\Http\Response::created();
return App\Http\Response::badRequest();
return App\Http\Response::unauthorized();
return App\Http\Response::forbidden();
return App\Http\Response::notFound();
return App\Http\Response::serverError();
```

## Dependency Injection

### Registering Services

```php
// Bind a service
$app->bind(
    UserRepository::class,
    function ($container) {
        return new UserRepository($container->get(Database::class));
    }
);

// Singleton service
$app->singleton(
    Database::class,
    function () {
        return new Database($dsn, $username, $password);
    }
);

// Transient service (new instance each time)
$app->transient(
    UserService::class,
    UserService::class
);

// Bind an interface to an implementation
$app->bind(
    UserRepositoryInterface::class,
    UserRepository::class
);

// Register an existing instance
$app->instance(
    Logger::class,
    new Logger('/path/to/logs')
);
```

### Using the Container

```php
// Resolve a service
$database = $app->container()->get(Database::class);

// Check if a service exists
if ($app->container()->has(Database::class)) {
    $database = $app->container()->get(Database::class);
}

// Call a method with dependency injection
$app->container()->call([$userService, 'createUser'], [
    'name' => 'John',
    'email' => 'john@example.com'
]);
```

### Auto-wiring in Routes

```php
$app->get('/users/{id}', function (
    App\Http\Request $request,
    UserRepository $repository
) use ($app) {
    $id = $request->getAttribute('id');
    $user = $repository->find($id);
    return App\Http\Response::json($user);
});

// With controller methods
$app->get('/users/{id}', [UserController::class, 'show']);

class UserController
{
    public function __construct(private UserRepository $repository) {}

    public function show(App\Http\Request $request): App\Http\Response
    {
        $id = $request->getAttribute('id');
        $user = $this->repository->find($id);
        return App\Http\Response::json($user);
    }
}
```

## Error Handling

### Custom Exception Handlers

```php
// Handle specific exception types
$app->handle(
    \InvalidArgumentException::class,
    function (\InvalidArgumentException $e, App\Http\Request $request) {
        return App\Http\Response::badRequest($e->getMessage());
    }
);

$app->handle(
    \RuntimeException::class,
    function (\RuntimeException $e, App\Http\Request $request) {
        return App\Http\Response::serverError('Internal Server Error');
    }
);

// Default handler for all exceptions
$app->exceptionHandler()->default(function (\Throwable $e, App\Http\Request $request) {
    return App\Http\Response::json([
        'error' => 'Internal Server Error',
        'code' => 500
    ], 500);
});
```

### Environment Configuration

```php
// Set environment (affects error detail level)
$app->environment('production'); // or 'development', 'testing'

// Enable/disable debug mode
$app->debug(true); // Shows detailed errors in development
$app->debug(false); // Hides sensitive info in production
```

## Request Handling

### Accessing Request Data

```php
$app->get('/demo', function (App\Http\Request $request) {
    // Get method
    $method = $request->getMethod();

    // Get URI and path
    $uri = $request->getUri();
    $path = $request->getPath();
    $queryString = $request->getQueryString();

    // Get query parameters
    $page = $request->getQueryParam('page', 1);
    $allQuery = $request->getQueryParams();

    // Get headers
    $contentType = $request->getHeader('Content-Type');
    $hasAuth = $request->hasHeader('Authorization');
    $allHeaders = $request->getHeaders();

    // Get body
    $rawBody = $request->getBody();
    $parsedBody = $request->getParsedBody();
    $name = $request->getParsedBodyParam('name');

    // Get route attributes (parameters)
    $id = $request->getAttribute('id');
    $hasId = $request->hasAttribute('id');
    $allAttributes = $request->getAttributes();

    // Get server info
    $ip = $request->getClientIp();
    $host = $request->getHost();
    $scheme = $request->getScheme();
    $url = $request->getUrl();

    // Check request type
    $isAjax = $request->isAjax();
    $expectsJson = $request->expectsJson();
    $isSecure = $request->isSecure();

    return App\Http\Response::json([
        'method' => $method,
        'path' => $path,
        'query' => $allQuery,
        'headers' => $allHeaders,
    ]);
});
```

## Configuration

### Bootstrap Configuration

Create a `config.php` file:

```php
<?php

return [
    'app' => [
        'name' => 'My Application',
        'env' => getenv('APP_ENV') ?: 'development',
        'debug' => (bool) getenv('APP_DEBUG'),
        'url' => getenv('APP_URL') ?: 'http://localhost',
    ],
    'database' => [
        'driver' => getenv('DB_DRIVER') ?: 'mysql',
        'host' => getenv('DB_HOST') ?: 'localhost',
        'database' => getenv('DB_DATABASE') ?: 'myapp',
        'username' => getenv('DB_USERNAME') ?: 'root',
        'password' => getenv('DB_PASSWORD') ?: '',
    ],
];
```

Load configuration in bootstrap:

```php
$config = require __DIR__ . '/config.php';

$app = new App\Core\App();
$app->environment($config['app']['env']);
$app->debug($config['app']['debug']);

// Register database service
$app->singleton(
    Database::class,
    function () use ($config) {
        return new Database(
            $config['database']['driver'],
            $config['database']['host'],
            $config['database']['database'],
            $config['database']['username'],
            $config['database']['password']
        );
    }
);
```

## Best Practices

### 1. Keep it Simple

The framework is designed to be minimal. Don't add complexity unless necessary.

### 2. Use Route Groups

Group related routes together for better organization:

```php
$app->group('/api/v1', function ($router) {
    $router->group('/users', function ($router) {
        $router->get('/', [UserController::class, 'index']);
        $router->post('/', [UserController::class, 'store']);
        $router->get('/{id}', [UserController::class, 'show']);
        $router->put('/{id}', [UserController::class, 'update']);
        $router->delete('/{id}', [UserController::class, 'destroy']);
    });
});
```

### 3. Use Middleware Wisely

Apply middleware at the appropriate level:
- Global middleware for cross-cutting concerns (logging, CORS)
- Group middleware for area-specific concerns (authentication)
- Route middleware for specific requirements

### 4. Keep Controllers Thin

Move business logic to services:

```php
// Good: Controller delegates to service
class UserController
{
    public function __construct(private UserService $service) {}

    public function store(App\Http\Request $request): App\Http\Response
    {
        $data = $request->getParsedBody();
        $user = $this->service->createUser($data);
        return App\Http\Response::json($user, 201);
    }
}

// UserService contains the business logic
class UserService
{
    public function __construct(private UserRepository $repository) {}

    public function createUser(array $data): User
    {
        // Validation, business rules, etc.
        return $this->repository->create($data);
    }
}
```

### 5. Handle Errors Gracefully

Always provide meaningful error responses:

```php
$app->handle(
    \App\Exceptions\ValidationException::class,
    function ($e, $request) {
        return App\Http\Response::json([
            'error' => 'Validation Failed',
            'messages' => $e->errors()
        ], 422);
    }
);
```

## Performance Tips

1. **Cache route patterns**: The router compiles regex patterns on the fly. For high-traffic applications, consider caching.

2. **Use singletons**: Register frequently used services as singletons.

3. **Minimize middleware**: Each middleware adds overhead. Only use what you need.

4. **Avoid heavy operations in routes**: Move complex logic to services or background jobs.

5. **Use OPcache**: Enable PHP OPcache for better performance.

## Testing

### Unit Testing

```php
use PHPUnit\Framework\TestCase;
use App\Http\Request;
use App\Http\Response;

class RouterTest extends TestCase
{
    public function testGetRoute()
    {
        $router = new \App\Core\Router();
        $router->get('/test', fn() => Response::ok());

        $request = new Request('GET', '/test');
        $container = new \App\Core\Container();

        $response = $router->dispatch($request, $container);

        $this->assertEquals(200, $response->getStatusCode());
    }
}
```

### Integration Testing

```php
use PHPUnit\Framework\TestCase;

class AppTest extends TestCase
{
    public function testHomeRoute()
    {
        $app = new \App\Core\App();
        
        $app->get('/', fn() => Response::html('<h1>Home</h1>'));

        $request = new Request('GET', '/');
        $response = $app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('Home', $response->getBody());
    }
}
```

## License

This framework is open-sourced software licensed under the [MIT license](LICENSE).

## Contributing

Contributions are welcome! Please follow PSR-12 coding standards and include tests for new features.

## Support

For questions, issues, or feature requests, please open an issue on GitHub.
