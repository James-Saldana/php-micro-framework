<?php

declare(strict_types=1);

/**
 * Application configuration.
 */

return [
    // Application environment
    // Supported: development, staging, production
    'env' => getenv('APP_ENV') ?: 'development',

    // Debug mode
    // When enabled, detailed error messages are shown
    'debug' => (bool) (getenv('APP_DEBUG') ?: true),

    // Application URL
    // Used for generating absolute URLs
    'url' => getenv('APP_URL') ?: 'http://localhost',

    // Application timezone
    'timezone' => getenv('APP_TIMEZONE') ?: 'UTC',

    // Application locale
    'locale' => getenv('APP_LOCALE') ?: 'en',

    // Application fallback locale
    'fallback_locale' => getenv('APP_FALLBACK_LOCALE') ?: 'en',

    // Encryption key
    // Used for various encryption purposes
    'key' => getenv('APP_KEY') ?: null,

    // Cipher for encryption
    'cipher' => 'AES-256-CBC',
];
