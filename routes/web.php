<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;

// Only register routes if UI is enabled
if (config('openapi-attributes.ui.enabled', true)) {
    $middleware = config('openapi-attributes.ui.middleware', 'web');
    $uiType = config('openapi-attributes.ui.type', 'openapi');
    $documentationPath = config('openapi-attributes.output_file');

    // Register route to serve the documentation file
    Route::get('api-docs/openapi.json', function () use ($documentationPath) {
        if (file_exists($documentationPath)) {
            $content = File::get($documentationPath);
            return response($content)->header('Content-Type', 'application/json');
        }

        return response()->json(['error' => 'openapi documentation has not been generated yet.'], 404);
    })->middleware($middleware);

    // Check if openapi UI is enabled
    if ($uiType === 'openapi' || $uiType === 'both') {
        Route::get(config('openapi-attributes.ui.openapi_route', 'api/documentation'), function () use ($documentationPath) {
            if (file_exists($documentationPath)) {
                // Read the documentation file content
                $specContent = File::get($documentationPath);

                return view('openapi-attributes::swagger-ui', [
                    'title' => config('openapi-attributes.title', 'API Documentation'),
                    'specContent' => $specContent,
                    'options' => json_encode(config('openapi-attributes.ui.openapi', [])),
                ]);
            }

            return response()->json(['error' => 'openapi documentation has not been generated yet. Run "php artisan openapi:generate" command.'], 404);
        })->middleware($middleware);
    }

    // Check if Redoc UI is enabled
    if ($uiType === 'redoc' || $uiType === 'both') {
        Route::get(config('openapi-attributes.ui.redoc_route', 'api/redoc'), function () use ($documentationPath) {
            if (file_exists($documentationPath)) {
                // Read the documentation file content
                $specContent = File::get($documentationPath);

                return view('openapi-attributes::redoc', [
                    'title' => config('openapi-attributes.title', 'API Documentation'),
                    'specContent' => $specContent,
                    'options' => json_encode(config('openapi-attributes.ui.redoc', [])),
                ]);
            }

            return response()->json(['error' => 'openapi documentation has not been generated yet. Run "php artisan openapi:generate" command.'], 404);
        })->middleware($middleware);
    }
}
