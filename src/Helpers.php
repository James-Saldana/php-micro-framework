<?php

declare(strict_types=1);

/**
 * Global helper functions for the framework.
 */

if (!function_exists('app')) {
    /**
     * Get the application instance.
     *
     * @return \App\Core\Application
     */
    function app(): \App\Core\Application
    {
        static $app = null;
        
        if ($app === null) {
            $app = require __DIR__ . '/../bootstrap/app.php';
        }
        
        return $app;
    }
}

if (!function_exists('config')) {
    /**
     * Get a configuration value.
     *
     * @param string $key Dot-notation key
     * @param mixed $default Default value
     * @return mixed
     */
    function config(string $key, mixed $default = null): mixed
    {
        $config = require __DIR__ . '/../config/app.php';
        
        $keys = explode('.', $key);
        $value = $config;
        
        foreach ($keys as $k) {
            if (!is_array($value) || !array_key_exists($k, $value)) {
                return $default;
            }
            $value = $value[$k];
        }
        
        return $value;
    }
}

if (!function_exists('env')) {
    /**
     * Get an environment variable.
     *
     * @param string $key Variable name
     * @param mixed $default Default value
     * @return mixed
     */
    function env(string $key, mixed $default = null): mixed
    {
        $value = getenv($key);
        
        if ($value === false) {
            return $default;
        }
        
        // Convert to boolean
        if (strtolower($value) === 'true') {
            return true;
        }
        if (strtolower($value) === 'false') {
            return false;
        }
        
        // Convert to integer
        if (is_numeric($value)) {
            return $value + 0;
        }
        
        return $value;
    }
}

if (!function_exists('response')) {
    /**
     * Create a response.
     *
     * @param mixed $data Response data
     * @param int $statusCode HTTP status code
     * @param array<string, string> $headers Headers
     * @return \App\Http\Response
     */
    function response(mixed $data = '', int $statusCode = 200, array $headers = []): \App\Http\Response
    {
        $response = new \App\Http\Response($statusCode, '');
        
        if (is_array($data) || is_object($data)) {
            $response = \App\Http\Response::json($data, $statusCode);
        } elseif (is_string($data)) {
            $response = $response->withBody($data);
        }
        
        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }
        
        return $response;
    }
}

if (!function_exists('redirect')) {
    /**
     * Create a redirect response.
     *
     * @param string $url URL to redirect to
     * @param int $statusCode HTTP status code (302 by default)
     * @return \App\Http\Response
     */
    function redirect(string $url, int $statusCode = 302): \App\Http\Response
    {
        return \App\Http\Response::redirect($url, $statusCode);
    }
}

if (!function_exists('view')) {
    /**
     * Render a view.
     *
     * @param string $name View name
     * @param array<string, mixed> $data Data to pass to the view
     * @return \App\Http\Response
     */
    function view(string $name, array $data = []): \App\Http\Response
    {
        $viewPath = __DIR__ . '/../storage/views/' . $name . '.php';
        
        if (!file_exists($viewPath)) {
            return \App\Http\Response::notFound('View not found: ' . $name);
        }
        
        extract($data);
        ob_start();
        require $viewPath;
        $content = ob_get_clean();
        
        return \App\Http\Response::html($content);
    }
}

if (!function_exists('logger')) {
    /**
     * Log a message.
     *
     * @param string $message Log message
     * @param string $level Log level (info, warning, error, debug)
     * @param array<string, mixed> $context Additional context
     */
    function logger(string $message, string $level = 'info', array $context = []): void
    {
        $logPath = __DIR__ . '/../storage/logs/app.log';
        $timestamp = date('Y-m-d H:i:s');
        
        $logEntry = sprintf(
            "[%s] %s: %s%s\n",
            $timestamp,
            strtoupper($level),
            $message,
            !empty($context) ? ' ' . json_encode($context) : ''
        );
        
        file_put_contents($logPath, $logEntry, FILE_APPEND);
    }
}
