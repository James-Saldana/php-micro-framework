<?php

declare(strict_types=1);

/**
 * Public entry point for the application.
 *
 * All requests are routed through this file.
 */

// Load the application
require_once __DIR__ . '/../bootstrap/app.php';

// Get the application instance
$app = require __DIR__ . '/../bootstrap/app.php';

// Define your routes here
// ============================================================================

// Example: Basic route
$app->get('/', function (App\Http\Request $request) {
    return App\Http\Response::html('<h1>Welcome to PHP Micro Framework Pro</h1>');
});

// Example: Route with parameter
$app->get('/hello/{name}', function (App\Http\Request $request) {
    $name = $request->getAttribute('name', 'World');
    return App\Http\Response::html("<h1>Hello, {$name}!</h1>");
});

// Example: JSON API route
$app->get('/api/users/{id}', function (App\Http\Request $request) {
    $id = $request->getAttribute('id');
    return App\Http\Response::json([
        'id' => $id,
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);
});

// Example: POST route
$app->post('/api/users', function (App\Http\Request $request) {
    $data = $request->getParsedBody();
    return App\Http\Response::json([
        'message' => 'User created',
        'data' => $data,
    ], 201);
});

// Example: Route group with middleware
$app->group('/api/admin', [
    // Add your middleware here
    // new App\Http\Middleware\AuthMiddleware(),
], function ($router) {
    $router->get('/dashboard', function (App\Http\Request $request) {
        return App\Http\Response::json(['page' => 'admin-dashboard']);
    });
    
    $router->get('/settings', function (App\Http\Request $request) {
        return App\Http\Response::json(['page' => 'admin-settings']);
    });
});

// Example: 404 handler
$app->any('/{path?}', function (App\Http\Request $request) {
    return App\Http\Response::notFound('Page not found');
});

// ============================================================================

// Run the application
$app->run();
