<?php

use Illuminate\Support\Facades\Route;

// Only register routes if UI is enabled
if (config('swagger-attributes.ui.enabled', true)) {
    $middleware = config('swagger-attributes.ui.middleware', 'web');
    $uiType = config('swagger-attributes.ui.type', 'swagger');
    $documentationPath = config('swagger-attributes.output_file');
    $relativePath = str_replace(storage_path('api-docs/'), 'api-docs/', $documentationPath);
    
    // Check if Swagger UI is enabled
    if ($uiType === 'swagger' || $uiType === 'both') {
        Route::get(config('swagger-attributes.ui.swagger_route', 'api/documentation'), function () use ($documentationPath, $relativePath) {
            if (file_exists($documentationPath)) {
                return view('swagger-attributes::swagger-ui', [
                    'title' => config('swagger-attributes.title', 'API Documentation'),
                    'documentationUrl' => url($relativePath),
                    'options' => json_encode(config('swagger-attributes.ui.swagger', [])),
                ]);
            }
            
            return response()->json(['error' => 'Swagger documentation has not been generated yet. Run "php artisan swagger:generate" command.'], 404);
        })->middleware($middleware);
    }
    
    // Check if Redoc UI is enabled
    if ($uiType === 'redoc' || $uiType === 'both') {
        Route::get(config('swagger-attributes.ui.redoc_route', 'api/redoc'), function () use ($documentationPath, $relativePath) {
            if (file_exists($documentationPath)) {
                return view('swagger-attributes::redoc', [
                    'title' => config('swagger-attributes.title', 'API Documentation'),
                    'documentationUrl' => url($relativePath),
                    'options' => json_encode(config('swagger-attributes.ui.redoc', [])),
                ]);
            }
            
            return response()->json(['error' => 'Swagger documentation has not been generated yet. Run "php artisan swagger:generate" command.'], 404);
        })->middleware($middleware);
    }
}
