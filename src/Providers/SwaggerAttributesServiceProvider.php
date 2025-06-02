<?php

namespace BellissimoPizza\SwaggerAttributes\Providers;

use BellissimoPizza\SwaggerAttributes\Commands\GenerateSwaggerDocCommand;
use Illuminate\Support\ServiceProvider;

class SwaggerAttributesServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/swagger-attributes.php', 'swagger-attributes'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register views
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'swagger-attributes');
        
        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateSwaggerDocCommand::class,
            ]);

            // Publish config
            $this->publishes([
                __DIR__ . '/../../config/swagger-attributes.php' => config_path('swagger-attributes.php'),
            ], 'swagger-attributes-config');

            // Publish views for customization
            $this->publishes([
                __DIR__ . '/../../resources/views' => resource_path('views/vendor/swagger-attributes'),
            ], 'swagger-attributes-views');
            
            // Publish public assets
            $this->publishes([
                __DIR__ . '/../../resources/swagger-ui' => public_path('vendor/swagger-attributes'),
            ], 'swagger-attributes-assets');
            
            // Publish all assets in one group
            $this->publishes([
                __DIR__ . '/../../config/swagger-attributes.php' => config_path('swagger-attributes.php'),
                __DIR__ . '/../../resources/views' => resource_path('views/vendor/swagger-attributes'),
                __DIR__ . '/../../resources/swagger-ui' => public_path('vendor/swagger-attributes'),
            ], 'swagger-attributes');
        }

        // Register routes for Swagger UI and Redoc
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
        
        // Create a symbolic link from public/api-docs to storage/api-docs if needed
        $this->createPublicSymlink();
    }
    
    /**
     * Create a symbolic link from public/api-docs to storage/api-docs
     * This is needed to access the generated documentation file
     */
    protected function createPublicSymlink(): void
    {
        $publicPath = public_path('api-docs');
        $storagePath = storage_path('api-docs');
        
        // Skip if not running in HTTP context or the link already exists
        if (!$this->app->runningInConsole() && !file_exists($publicPath) && file_exists($storagePath)) {
            if (function_exists('symlink')) {
                // Create the directory if it doesn't exist
                if (!is_dir(dirname($publicPath))) {
                    mkdir(dirname($publicPath), 0755, true);
                }
                
                // Create the symbolic link
                symlink($storagePath, $publicPath);
            }
        }
    }
}
