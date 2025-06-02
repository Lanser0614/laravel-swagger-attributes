<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;

// Only register routes if UI is enabled
if (config('swagger-attributes.ui.enabled', true)) {
    $middleware = config('swagger-attributes.ui.middleware', 'web');
    $uiType = config('swagger-attributes.ui.type', 'swagger');
    $documentationPath = config('swagger-attributes.output_file');
    
    // Register route to serve the documentation file
    Route::get('api-docs/swagger.json', function () use ($documentationPath) {
        if (file_exists($documentationPath)) {
            $content = File::get($documentationPath);
            return response($content)->header('Content-Type', 'application/json');
        }
        
        return response()->json(['error' => 'Swagger documentation has not been generated yet.'], 404);
    })->middleware($middleware);
    
    // Check if Swagger UI is enabled
    if ($uiType === 'swagger' || $uiType === 'both') {
        Route::get(config('swagger-attributes.ui.swagger_route', 'api/documentation'), function () use ($documentationPath) {
            if (file_exists($documentationPath)) {
                // Read the documentation file content
                $specContent = File::get($documentationPath);
                
                return view('swagger-attributes::swagger-ui', [
                    'title' => config('swagger-attributes.title', 'API Documentation'),
                    'specContent' => $specContent,
                    'options' => json_encode(config('swagger-attributes.ui.swagger', [])),
                ]);
            }
            
            return response()->json(['error' => 'Swagger documentation has not been generated yet. Run "php artisan swagger:generate" command.'], 404);
        })->middleware($middleware);
    }
    
    // Check if Redoc UI is enabled
    if ($uiType === 'redoc' || $uiType === 'both') {
        Route::get(config('swagger-attributes.ui.redoc_route', 'api/redoc'), function () use ($documentationPath) {
            if (file_exists($documentationPath)) {
                // Read the documentation file content
                $specContent = File::get($documentationPath);
                
                return view('swagger-attributes::redoc', [
                    'title' => config('swagger-attributes.title', 'API Documentation'),
                    'specContent' => $specContent,
                    'options' => json_encode(config('swagger-attributes.ui.redoc', [])),
                ]);
            }
            
            return response()->json(['error' => 'Swagger documentation has not been generated yet. Run "php artisan swagger:generate" command.'], 404);
        })->middleware($middleware);
    }
}
