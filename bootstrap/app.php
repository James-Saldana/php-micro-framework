<?php

declare(strict_types=1);

/**
 * Application bootstrap file.
 *
 * This file creates and configures the application instance.
 */

// Ensure we're running PHP 8.2+
if (version_compare(PHP_VERSION, '8.2.0', '<')) {
    die('PHP 8.2 or higher is required.');
}

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Define application root
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

// Define application environment
if (!defined('APP_ENV')) {
    define('APP_ENV', getenv('APP_ENV') ?: 'development');
}

// Define debug mode
if (!defined('APP_DEBUG')) {
    define('APP_DEBUG', (bool) (getenv('APP_DEBUG') ?: APP_ENV !== 'production'));
}

// Define storage path
if (!defined('STORAGE_PATH')) {
    define('STORAGE_PATH', APP_ROOT . '/storage');
}

// Define config path
if (!defined('CONFIG_PATH')) {
    define('CONFIG_PATH', APP_ROOT . '/config');
}

// Register autoloader
spl_autoload_register(function (string $class): void {
    if (str_starts_with($class, 'App\\')) {
        $prefix = 'App\\';
        $baseDir = APP_ROOT . '/src/';
        $relativeClass = substr($class, strlen($prefix));
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) {
            require $file;
        }
    }
});

// Load configuration
if (file_exists(CONFIG_PATH . '/app.php')) {
    $config = require CONFIG_PATH . '/app.php';
} else {
    $config = [];
}

// Create and configure the application
$app = new App\Core\Application();

// Set environment and debug mode
$app->environment($config['env'] ?? APP_ENV);
$app->debug($config['debug'] ?? APP_DEBUG);

// Register PHP error and exception handlers
$app->registerHandlers();

// Register built-in middleware
$app->bind(
    App\Contracts\Http\Middleware\MiddlewareInterface::class,
    App\Http\Middleware\MiddlewarePipeline::class
);

// Return the application instance
return $app;
