<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Swagger Documentation Settings
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration settings for generating Swagger
    | documentation using PHP attributes.
    |
    */

    // General API Information
    'title' => env('SWAGGER_TITLE', 'API Documentation'),
    'description' => env('SWAGGER_DESCRIPTION', 'API Documentation using Laravel Swagger Attributes'),
    'version' => env('SWAGGER_VERSION', '1.0.0'),
    'contact' => [
        'name' => env('SWAGGER_CONTACT_NAME', ''),
        'email' => env('SWAGGER_CONTACT_EMAIL', ''),
        'url' => env('SWAGGER_CONTACT_URL', ''),
    ],

    // Output file path (relative to storage_path or absolute)
    'output_file' => storage_path('api-docs/swagger.json'),
    
    // Output format (json or yaml)
    'format' => env('SWAGGER_FORMAT', 'json'),

    // Server URLs
    'servers' => [
        [
            'url' => env('APP_URL', 'http://localhost'),
            'description' => 'API Server',
        ],
    ],

    // Security Schemes
    'security_schemes' => [
        'bearerAuth' => [
            'type' => 'http',
            'scheme' => 'bearer',
            'bearerFormat' => 'JWT',
        ],
    ],

    // Default security to apply to all endpoints
    'security' => [
        ['bearerAuth' => []],
    ],

    // Enable Swagger UI
    'enable_ui' => env('SWAGGER_ENABLE_UI', true),
    
    // Swagger UI route
    'ui_route' => env('SWAGGER_UI_ROUTE', 'api/documentation'),

    // Route middleware for Swagger UI
    'ui_middleware' => env('SWAGGER_UI_MIDDLEWARE', 'web'),
    
    // Include these controller namespaces in the scan
    'include_namespaces' => [
        'App\\Http\\Controllers',
    ],
    
    // Exclude these controller namespaces from the scan
    'exclude_namespaces' => [],
];
