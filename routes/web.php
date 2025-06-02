<?php

use Illuminate\Support\Facades\Route;

// Only register routes if UI is enabled
if (config('swagger-attributes.enable_ui', true)) {
    Route::get(config('swagger-attributes.ui_route', 'api/documentation'), function () {
        $documentationPath = config('swagger-attributes.output_file');
        $relativePath = 'api-docs/swagger.json';
        
        if (file_exists($documentationPath)) {
            return view('swagger-attributes::swagger-ui', [
                'title' => config('swagger-attributes.title', 'API Documentation'),
                'documentationUrl' => url($relativePath),
            ]);
        }
        
        return response()->json(['error' => 'Swagger documentation has not been generated yet. Run "php artisan swagger:generate" command.'], 404);
    })->middleware(config('swagger-attributes.ui_middleware', 'web'));
}
