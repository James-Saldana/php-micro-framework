<?php

declare(strict_types=1);

/**
 * Application bootstrap file.
 *
 * This file is responsible for:
 * - Setting up the autoloader
 * - Creating the application instance
 * - Registering services and configurations
 * - Returning the configured application
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

// Define application environment (default: development)
if (!defined('APP_ENV')) {
    define('APP_ENV', getenv('APP_ENV') ?: 'development');
}

// Define debug mode
if (!defined('APP_DEBUG')) {
    define('APP_DEBUG', (bool) (getenv('APP_DEBUG') ?: APP_ENV !== 'production'));
}

// Register autoloader
spl_autoload_register(function (string $class): void {
    // Only load classes from the App namespace
    if (str_starts_with($class, 'App\\')) {
        $prefix = 'App\\';
        $baseDir = APP_ROOT . '/src/';

        // Remove the prefix from the class name
        $relativeClass = substr($class, strlen($prefix));

        // Replace namespace separators with directory separators
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

        // If the file exists, require it
        if (file_exists($file)) {
            require $file;
        }
    }
});

// Create and configure the application
$app = new App\Core\App();

// Set environment and debug mode
$app->environment(APP_ENV);
$app->debug(APP_DEBUG);

// Register PHP error and exception handlers
$app->registerHandlers();

// Return the application instance
return $app;
