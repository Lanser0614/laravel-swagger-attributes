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

    // UI Options
    'ui' => [
        // Which UI to use: 'swagger', 'redoc', or 'both'
        'type' => env('SWAGGER_UI_TYPE', 'swagger'),
        
        // Enable UI
        'enabled' => env('SWAGGER_ENABLE_UI', true),
        
        // UI routes
        'swagger_route' => env('SWAGGER_UI_ROUTE', 'api/documentation'),
        'redoc_route' => env('REDOC_UI_ROUTE', 'api/redoc'),
        
        // Route middleware for UI
        'middleware' => env('SWAGGER_UI_MIDDLEWARE', 'web'),
        
        // Redoc options
        'redoc' => [
            'theme' => env('REDOC_THEME', 'light'),  // light, dark
            'hide_download_button' => env('REDOC_HIDE_DOWNLOAD', false),
            'expand_responses' => env('REDOC_EXPAND_RESPONSES', 'all'), // all, success, none
            'scroll_y_offset' => env('REDOC_SCROLL_Y_OFFSET', 0),
        ],
        
        // Swagger UI options
        'swagger' => [
            'deep_linking' => true,
            'display_operation_id' => false,
            'default_models_expand_depth' => 1,
            'default_model_expand_depth' => 1,
            'default_model_rendering' => 'example',
            'doc_expansion' => 'list', // list, full, none
        ],
    ],
    
    // Include these controller namespaces in the scan
    'include_namespaces' => [
        'App\\Http\\Controllers',
    ],
    
    // Exclude these controller namespaces from the scan
    'exclude_namespaces' => [],
];
