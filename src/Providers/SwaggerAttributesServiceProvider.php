<?php

namespace BellissimoPizza\SwaggerAttributes\Providers;

use BellissimoPizza\SwaggerAttributes\Commands\GenerateOpenApiDocCommand;
use Illuminate\Support\ServiceProvider;

class SwaggerAttributesServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/openapi-attributes.php', 'openapi-attributes'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register views
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'openapi-attributes');
        
        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateOpenApiDocCommand::class,
            ]);

            // Publish config
            $this->publishes([
                __DIR__ . '/../../config/openapi-attributes.php' => config_path('openapi-attributes.php'),
            ], 'openapi-attributes-config');

            // Publish views for customization
            $this->publishes([
                __DIR__ . '/../../resources/views' => resource_path('views/vendor/openapi-attributes'),
            ], 'openapi-attributes-views');
            
            // Publish public assets
            $this->publishes([
                __DIR__ . '/../../resources/swagger-ui' => public_path('vendor/openapi-attributes'),
            ], 'openapi-attributes-assets');
            
            // Publish all assets in one group
            $this->publishes([
                __DIR__ . '/../../config/openapi-attributes.php' => config_path('openapi-attributes.php'),
                __DIR__ . '/../../resources/views' => resource_path('views/vendor/openapi-attributes'),
                __DIR__ . '/../../resources/swagger-ui' => public_path('vendor/openapi-attributes'),
            ], 'openapi-attributes');
        }

        // Register routes for Swagger UI and Redoc
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
    }
}
