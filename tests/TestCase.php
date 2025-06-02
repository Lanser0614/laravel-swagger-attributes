<?php

namespace BellissimoPizza\SwaggerAttributes\Tests;

use BellissimoPizza\SwaggerAttributes\Providers\SwaggerAttributesServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            SwaggerAttributesServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function defineEnvironment($app): void
    {
        // Set up basic configuration for tests
        $app['config']->set('swagger-attributes', [
            'title' => 'Test API',
            'description' => 'API Documentation for tests',
            'version' => '1.0.0',
            'base_path' => '/api',
            'swagger_ui_path' => '/api/docs',
            'output_path' => storage_path('swagger/swagger.json'),
            'format' => 'json',
        ]);
    }
    
    /**
     * Create a temporary test file for storing generated output
     *
     * @param string $content The content to write
     * @return string The path to the temporary file
     */
    protected function createTempFile(string $content = ''): string
    {
        $path = sys_get_temp_dir() . '/laravel-swagger-test-' . uniqid() . '.json';
        file_put_contents($path, $content);
        
        $this->tearDown(function() use ($path) {
            if (file_exists($path)) {
                unlink($path);
            }
        });
        
        return $path;
    }
}
