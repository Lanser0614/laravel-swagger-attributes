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
    }
}
