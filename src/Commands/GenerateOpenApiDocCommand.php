<?php

namespace BellissimoPizza\SwaggerAttributes\Commands;

use BellissimoPizza\SwaggerAttributes\Services\OpenApiGenerator;
use Illuminate\Console\Command;

class GenerateOpenApiDocCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'swagger:generate 
                            {--output= : Path to save the generated swagger documentation}
                            {--format=json : Output format (json or yaml)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Swagger documentation by scanning routes and PHP attributes';

    /**
     * Execute the console command.
     */
    public function handle(OpenApiGenerator $generator): int
    {
        $outputPath = $this->option('output') ?: config('swagger-attributes.output_file');
        $format = strtolower($this->option('format'));
        
        // Validate format option
        if (!in_array($format, ['json', 'yaml', 'yml'])) {
            $this->error('Invalid format specified. Allowed formats: json, yaml, yml');
            return Command::FAILURE;
        }
        
        $this->info('Scanning routes for Swagger attributes...');
        
        try {
            $documentationGenerated = $generator->generate($outputPath, $format);
            
            if ($documentationGenerated) {
                $this->info('Swagger documentation has been generated successfully.');
                
                // Get the final path with extension that might have been added by the generator
                $extension = $format === 'json' ? '.json' : '.yaml';
                if (!preg_match('/\.' . preg_quote($extension, '/') . '$/i', $outputPath)) {
                    $outputPath = preg_replace('/\.(?:json|ya?ml)$/i', $extension, $outputPath);
                    if (!preg_match('/\.' . preg_quote($extension, '/') . '$/i', $outputPath)) {
                        $outputPath .= $extension;
                    }
                }
                
                $this->info("Output file: $outputPath");
                $this->info("Format: " . strtoupper($format));
                return Command::SUCCESS;
            } else {
                $this->error('Failed to generate Swagger documentation.');
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error('Error generating Swagger documentation: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
