<?php

namespace BellissimoPizza\SwaggerAttributes\Services;

use ReflectionClass;
use ReflectionMethod;
use Illuminate\Http\Resources\Json\JsonResource;
use BellissimoPizza\SwaggerAttributes\Services\SwaggerGenerator;

class ResourceSchemaExtractor
{
    /**
     * Generate an OpenAPI schema from a Laravel API Resource
     *
     * @param string $resourceClass Fully qualified class name of a Laravel Resource
     * @param string|null $modelClass Optional related model class name
     * @param SwaggerGenerator|null $generator Reference to the main generator for model schema generation
     * @return array|null The generated schema or null if unable to generate
     */
    public function generateResourceSchema(string $resourceClass, ?string $modelClass = null, SwaggerGenerator $generator = null): ?array
    {
        try {
            // Validate the resource class exists
            if (!class_exists($resourceClass)) {
                return null;
            }
            
            // Check if this is a Laravel resource by looking at the class hierarchy
            $reflectionClass = new ReflectionClass($resourceClass);
            $isResource = $reflectionClass->isSubclassOf(JsonResource::class);
            
            if (!$isResource) {
                // Not a Laravel resource, return null
                return null;
            }
            
            // Create a base schema structure
            $schema = [
                'type' => 'object',
                'properties' => []
            ];
            
            // If we have a related model, get its schema as a starting point
            $modelSchema = null;
            if ($modelClass && class_exists($modelClass) && $generator) {
                // Use the proper method from the generator to get model schema
                $modelSchema = $generator->getModelSchema($modelClass);
                if ($modelSchema && isset($modelSchema['properties'])) {
                    $schema['properties'] = $modelSchema['properties'];
                }
            }
            
            // Look for toArray method
            if ($reflectionClass->hasMethod('toArray')) {
                $toArrayMethod = $reflectionClass->getMethod('toArray');
                $docComment = $toArrayMethod->getDocComment();
                
                // Extract properties from doc comments if available
                if ($docComment) {
                    $this->extractPropertiesFromDocComment($docComment, $schema);
                }
                
                // Try to extract properties by analyzing the method body (advanced)
                $this->extractPropertiesFromMethodBody($toArrayMethod, $schema);
            }
            
            return $schema;
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Extract properties from PHPDoc comment
     *
     * @param string $docComment The docs comment to parse
     * @param array $schema The schema to populate
     */
    protected function extractPropertiesFromDocComment(string $docComment, array &$schema): void
    {
        // Look for @property tags or custom @apiProperty tags
        $propertyPattern = '/@(api)?property\s+([\w\\\[\]]+)\s+\$([\w_]+)(?:\s+(.*))?/m';
        if (preg_match_all($propertyPattern, $docComment, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $type = $match[2];
                $name = $match[3];
                $description = $match[4] ?? null;
                
                // Convert PHP type to OpenAPI type
                $schema['properties'][$name] = $this->convertPhpTypeToOpenApiType($type);
                
                if ($description) {
                    $schema['properties'][$name]['description'] = trim($description);
                }
            }
        }
        
        // Look for @return array structure documentation
        $returnPattern = '/@return\s+array\s*{([^}]+)}/m';
        if (preg_match($returnPattern, $docComment, $returnMatches)) {
            $returnStructure = $returnMatches[1];
            $returnPropertyPattern = '/\s*([\w_]+)\s*:\s*([\w\\\[\]|]+)(?:\s+(.*))?/m';
            if (preg_match_all($returnPropertyPattern, $returnStructure, $propMatches, PREG_SET_ORDER)) {
                foreach ($propMatches as $match) {
                    $name = $match[1];
                    $type = $match[2];
                    $description = $match[3] ?? null;
                    
                    $schema['properties'][$name] = $this->convertPhpTypeToOpenApiType($type);
                    
                    if ($description) {
                        $schema['properties'][$name]['description'] = trim($description);
                    }
                }
            }
        }
    }
    
    /**
     * Extract properties by analyzing the method body
     * This is a basic implementation - a more advanced version would use code analysis
     *
     * @param ReflectionMethod $method The method to analyze
     * @param array $schema The schema to populate
     */
    protected function extractPropertiesFromMethodBody(ReflectionMethod $method, array &$schema): void
    {
        // This is a simplified approach - a more robust implementation would use
        // actual code parsing to analyze the return structure of the toArray method
        
        $fileName = $method->getFileName();
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();
        
        if (!$fileName || !file_exists($fileName)) {
            return;
        }
        
        $fileContent = file_get_contents($fileName);
        if (!$fileContent) {
            return;
        }
        
        $lines = array_slice(
            explode("\n", $fileContent),
            $startLine - 1,
            $endLine - $startLine + 1
        );
        
        $methodBody = implode("\n", $lines);
        
        // Look for array keys in return statements
        $pattern = '/[\'"](\w+)[\'"]\s*=>\s*/';
        if (preg_match_all($pattern, $methodBody, $matches)) {
            foreach ($matches[1] as $property) {
                if (!isset($schema['properties'][$property])) {
                    $schema['properties'][$property] = ['type' => 'string'];
                }
            }
        }
    }
    
    /**
     * Convert PHP type to OpenAPI type
     *
     * @param string $phpType PHP type hint
     * @return array OpenAPI type definition
     */
    protected function convertPhpTypeToOpenApiType(string $phpType): array
    {
        // Handle array types
        if (str_ends_with($phpType, '[]') || preg_match('/^array<(.+)>$/', $phpType)) {
            $itemType = str_ends_with($phpType, '[]') 
                ? substr($phpType, 0, -2)
                : preg_replace('/^array<(.+)>$/', '$1', $phpType);
                
            return [
                'type' => 'array',
                'items' => $this->convertPhpTypeToOpenApiType($itemType)
            ];
        }
        
        // Handle union types (e.g., string|null)
        if (str_contains($phpType, '|')) {
            $types = explode('|', $phpType);
            // Filter out null, mixed, or other ambiguous types
            $types = array_filter($types, fn($t) => !in_array(strtolower($t), ['null', 'mixed']));
            if (!empty($types)) {
                // Use the first non-null type
                return $this->convertPhpTypeToOpenApiType(reset($types));
            }
        }
        
        // Map PHP types to OpenAPI types
        return match(strtolower($phpType)) {
            'string' => ['type' => 'string'],
            'int', 'integer' => ['type' => 'integer'],
            'float', 'double', 'decimal' => ['type' => 'number'],
            'bool', 'boolean' => ['type' => 'boolean'],
            'array' => ['type' => 'array', 'items' => ['type' => 'string']],
            'object', 'stdclass', '\\stdclass' => ['type' => 'object'],
            'datetime', '\\datetime' => ['type' => 'string', 'format' => 'date-time'],
            'date' => ['type' => 'string', 'format' => 'date'],
            'carbon', '\\carbon\\carbon', '\\illuminate\\support\\carbon' => ['type' => 'string', 'format' => 'date-time'],
            default => ['type' => 'string'],
        };
    }
}
