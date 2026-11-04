<?php

declare(strict_types=1);

/**
 * Test bootstrap file.
 *
 * This file is loaded before each test suite runs.
 */

// Ensure we're running PHP 8.2+
if (version_compare(PHP_VERSION, '8.2.0', '<')) {
    die('PHP 8.2 or higher is required.');
}

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Define application root
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

// Register autoloader for App namespace
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

// Load Composer autoloader if available
if (file_exists(APP_ROOT . '/vendor/autoload.php')) {
    require APP_ROOT . '/vendor/autoload.php';
}
