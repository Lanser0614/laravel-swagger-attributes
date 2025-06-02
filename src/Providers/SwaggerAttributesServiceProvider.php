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
        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateSwaggerDocCommand::class,
            ]);

            $this->publishes([
                __DIR__ . '/../../config/swagger-attributes.php' => config_path('swagger-attributes.php'),
            ], 'config');

            $this->publishes([
                __DIR__ . '/../../resources/swagger-ui' => public_path('vendor/swagger-attributes'),
            ], 'public');
        }

        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
    }
}
