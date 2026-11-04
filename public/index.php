<?php

declare(strict_types=1);

/**
 * Public entry point for the application.
 *
 * This is the only file that should be accessible from the web server.
 * All requests are routed through this file.
 */

// Load the bootstrap file
require_once __DIR__ . '/../bootstrap.php';

// Get the application instance
$app = require __DIR__ . '/../bootstrap.php';

// Define routes
// ============================================================================

// Basic GET route
$app->get('/', function (App\Http\Request $request) {
    return App\Http\Response::html('<h1>Welcome to PHP Micro Framework</h1>');
});

// Route with parameter
$app->get('/hello/{name}', function (App\Http\Request $request) {
    $name = $request->getAttribute('name', 'World');
    return App\Http\Response::html("<h1>Hello, {$name}!</h1>");
});

// Route with optional parameter
$app->get('/greet/{name?}', function (App\Http\Request $request) {
    $name = $request->getAttribute('name', 'Guest');
    return App\Http\Response::json(['message' => "Hello, {$name}!"]);
});

// JSON API route
$app->get('/api/users/{id}', function (App\Http\Request $request) {
    $id = $request->getAttribute('id');
    return App\Http\Response::json([
        'id' => $id,
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);
});

// POST route
$app->post('/api/users', function (App\Http\Request $request) {
    $data = $request->getParsedBody();
    return App\Http\Response::json([
        'message' => 'User created',
        'data' => $data,
    ], 201);
});

// PUT route
$app->put('/api/users/{id}', function (App\Http\Request $request) {
    $id = $request->getAttribute('id');
    $data = $request->getParsedBody();
    return App\Http\Response::json([
        'message' => "User {$id} updated",
        'data' => $data,
    ]);
});

// DELETE route
$app->delete('/api/users/{id}', function (App\Http\Request $request) {
    $id = $request->getAttribute('id');
    return App\Http\Response::json(['message' => "User {$id} deleted"]);
});

// Route group with prefix and middleware
$app->group('/api/admin', [
    // Middleware would go here
], function ($router) {
    $router->get('/dashboard', function (App\Http\Request $request) {
        return App\Http\Response::json(['page' => 'admin-dashboard']);
    });

    $router->get('/settings', function (App\Http\Request $request) {
        return App\Http\Response::json(['page' => 'admin-settings']);
    });
});

// 404 handler (catch-all)
$app->any('/{path?}', function (App\Http\Request $request) {
    return App\Http\Response::notFound('Page not found');
});

// ============================================================================

// Run the application
$app->run();
