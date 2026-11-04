# PHP Micro Framework Pro

A professional, lightweight, high-performance PHP 8.2+ micro-framework for building modern web applications and APIs.

## Features

- **Ultra-lightweight**: Under 20 core files, no external dependencies
- **Modern PHP 8.2+**: Strict types, readonly properties, enums, interfaces
- **Professional structure**: Clean architecture with contracts and implementations
- **Fast routing**: Expressive routing with parameters, constraints, and groups
- **Middleware pipeline**: Onion-style middleware support
- **DI Container**: Lightweight dependency injection with PSR-11 style
- **Error handling**: Centralized exception handling with custom handlers
- **PSR-12 compliant**: Clean, readable code following modern standards
- **Production-ready**: Built for real-world applications

## Requirements

- PHP 8.2 or higher
- Composer (optional, for autoloading and development tools)

## Installation

### Option 1: Clone the repository

```bash
git clone https://github.com/your-repo/php-micro-framework-pro.git
cd php-micro-framework-pro
composer install
```

### Option 2: Manual setup

1. Download the framework files
2. Place them in your project directory
3. Ensure PHP 8.2+ is installed

## Directory Structure

```
php-micro-framework-pro/
├── src/
│   ├── Contracts/                    # Interfaces and contracts
│   │   ├── Container/
│   │   │   └── ContainerInterface.php
│   │   └── Http/
│   │       ├── Middleware/
│   │       │   └── MiddlewareInterface.php
│   │       ├── Request/
│   │       │   └── RequestInterface.php
│   │       └── Response/
│   │           └── ResponseInterface.php
│   ├── Core/                         # Core framework classes
│   │   ├── Application.php           # Main application class
│   │   ├── Container/
│   │   │   └── Container.php         # DI Container
│   │   └── Router/
│   │       ├── Router.php            # HTTP Router
│   │       └── Route.php             # Route data class
│   ├── Enums/                        # Enumerations
│   │   ├── HttpMethod.php
│   │   └── HttpStatus.php
│   ├── Exceptions/                   # Exception handling
│   │   └── Handler.php
│   └── Http/                          # HTTP classes
│       ├── Middleware/
│       │   ├── MiddlewarePipeline.php
│       │   ├── TrimStrings.php
│       │   └── ConvertEmptyStringsToNull.php
│       ├── Request.php
│       └── Response.php
├── bootstrap/
│   └── app.php                       # Application bootstrap
├── config/
│   └── app.php                       # Configuration
├── public/
│   ├── index.php                     # Public entry point
│   └── .htaccess                     # Apache configuration
├── tests/                           # Unit tests
├── storage/                         # Storage (logs, cache, views)
│   ├── cache/
│   ├── logs/
│   └── views/
├── composer.json
├── phpunit.xml
└── README.md
```

## Quick Start

### 1. Create your entry point

The `public/index.php` file is already configured. Just add your routes:

```php
<?php
require_once __DIR__ . '/../bootstrap/app.php';

$app = require __DIR__ . '/../bootstrap/app.php';

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
    root /path/to/php-micro-framework-pro/public;
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

### 3. Run the development server

```bash
php -S localhost:8000 -t public
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
    new App\Http\Middleware\TrimStrings(),
    new App\Http\Middleware\ConvertEmptyStringsToNull(),
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
$app->match([App\Enums\HttpMethod::GET, App\Enums\HttpMethod::POST], '/users', function () {
    return App\Http\Response::json(['message' => 'GET or POST']);
});

// Match all methods
$app->any('/wildcard', function () {
    return App\Http\Response::json(['message' => 'Any method']);
});
```

### Controller Routes

```php
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

### Built-in Middleware

```php
// Trim strings from request input
$app->middleware(new App\Http\Middleware\TrimStrings());

// Convert empty strings to null
$app->middleware(new App\Http\Middleware\ConvertEmptyStringsToNull());
```

### Creating Custom Middleware

```php
use App\Contracts\Http\Middleware\MiddlewareInterface;
use App\Http\Request;
use App\Http\Response;

class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        // Before request handling
        if (!$this->isAuthenticated($request)) {
            return App\Http\Response::unauthorized('Unauthorized');
        }

        // Call the next middleware/handler
        $response = $next($request);

        // After request handling
        return $response->withHeader('X-Auth', 'true');
    }

    private function isAuthenticated(Request $request): bool
    {
        return $request->getHeader('Authorization') !== null;
    }
}

// Register middleware
$app->middleware(new AuthMiddleware());

// Or use closure middleware
$app->middleware(function (Request $request, callable $next): Response {
    // Before
    $response = $next($request);
    // After
    return $response;
});
```

### Route-Specific Middleware

```php
$app->get('/admin', function () {
    return App\Http\Response::json(['admin' => true]);
}, [new AuthMiddleware()]);

// Multiple middleware
$app->get('/secure', function () {
    return App\Http\Response::json(['secure' => true]);
}, [new AuthMiddleware(), new RateLimitMiddleware()]);
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

// Bind interface to implementation
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
) {
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
// Set environment
$app->environment('production'); // or 'development', 'staging', 'testing'

// Enable/disable debug mode
$app->debug(true); // Shows detailed errors in development
$app->debug(false); // Hides sensitive info in production
```

### Logging Exceptions

```php
$app->exceptionHandler()->logHandler(function (\Throwable $e, App\Http\Request $request) {
    // Log to file
    file_put_contents(
        __DIR__ . '/../storage/logs/exceptions.log',
        sprintf(
            "[%s] %s: %s in %s:%d\n",
            date('Y-m-d H:i:s'),
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        ),
        FILE_APPEND
    );
});
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

## Response Helpers

### JSON Responses

```php
return App\Http\Response::json(['data' => $data]);
return App\Http\Response::json(['error' => 'Not found'], 404);
```

### HTML Responses

```php
return App\Http\Response::html('<h1>Hello</h1>');
return App\Http\Response::html('<p>Error</p>', 500);
```

### Text Responses

```php
return App\Http\Response::text('Hello World');
```

### Redirect Responses

```php
return App\Http\Response::redirect('/login');
return App\Http\Response::redirect('/dashboard', 301);
```

### Empty Responses

```php
return App\Http\Response::empty();
return App\Http\Response::noContent();
```

### Status Responses

```php
return App\Http\Response::ok();
return App\Http\Response::created();
return App\Http\Response::badRequest();
return App\Http\Response::unauthorized();
return App\Http\Response::forbidden();
return App\Http\Response::notFound();
return App\Http\Response::serverError();
return App\Http\Response::serviceUnavailable();
```

## Configuration

### Application Configuration

Create a `config/app.php` file:

```php
<?php

return [
    'env' => getenv('APP_ENV') ?: 'development',
    'debug' => (bool) (getenv('APP_DEBUG') ?: true),
    'url' => getenv('APP_URL') ?: 'http://localhost',
    'timezone' => getenv('APP_TIMEZONE') ?: 'UTC',
    'locale' => getenv('APP_LOCALE') ?: 'en',
    'key' => getenv('APP_KEY') ?: null,
];
```

### Loading Configuration

```php
$config = require __DIR__ . '/config/app.php';

$app->environment($config['env']);
$app->debug($config['debug']);
```

## Best Practices

### 1. Keep Controllers Thin

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

### 2. Use Middleware Wisely

- Global middleware for cross-cutting concerns (logging, CORS, request sanitization)
- Group middleware for area-specific concerns (authentication, authorization)
- Route middleware for specific requirements (rate limiting, feature flags)

### 3. Handle Errors Gracefully

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

### 4. Use Route Groups

Group related routes together:

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

### 5. Keep Routes RESTful

Follow REST conventions:

```php
// GET    /users          -> List all users
// POST   /users          -> Create a user
// GET    /users/{id}     -> Get a user
// PUT    /users/{id}     -> Replace a user
// PATCH  /users/{id}     -> Update a user
// DELETE /users/{id}     -> Delete a user
```

## Performance Tips

1. **Cache route patterns**: The router compiles regex patterns on the fly. For high-traffic applications, consider caching.

2. **Use singletons**: Register frequently used services as singletons.

3. **Minimize middleware**: Each middleware adds overhead. Only use what you need.

4. **Avoid heavy operations in routes**: Move complex logic to services or background jobs.

5. **Use OPcache**: Enable PHP OPcache for better performance.

## Testing

### Running Tests

```bash
# Install PHPUnit
composer install

# Run all tests
./vendor/bin/phpunit

# Run with coverage
./vendor/bin/phpunit --coverage-html coverage

# Run specific test suite
./vendor/bin/phpunit tests/Core
```

### Example Test

```php
use App\Core\Application;
use App\Http\Request;
use App\Http\Response;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
    public function testGetRoute()
    {
        $app = new Application();
        
        $app->get('/test', function () {
            return Response::ok('Success');
        });

        $request = new Request('GET', '/test');
        $response = $app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Success', $response->getBody());
    }
}
```

## License

This framework is open-sourced software licensed under the [MIT license](LICENSE).

## Contributing

Contributions are welcome! Please follow:
- PSR-12 coding standards
- Include tests for new features
- Keep the framework lightweight
- Document new features

## Support

For questions, issues, or feature requests, please open an issue on GitHub.
