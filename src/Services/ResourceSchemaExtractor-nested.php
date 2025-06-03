<?php

namespace BellissimoPizza\SwaggerAttributes\Services;

use ReflectionClass;
use ReflectionMethod;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;
use Illuminate\Pagination\AbstractPaginator;
use Exception;

class ResourceSchemaExtractor
{
    /**
     * Generate an OpenAPI schema from a Laravel API Resource
     *
     * @param string $resourceClass Fully qualified class name of a Laravel Resource
     * @param string|null $modelClass Optional related model class name
     * @param OpenApiGenerator|null $generator Reference to the main generator for model schema generation
     * @return array|null The generated schema or null if unable to generate
     */
    public function generateResourceSchema(string $resourceClass, ?string $modelClass = null, OpenApiGenerator $generator = null): ?array
    {
        try {
            // Validate the resource class exists
            if (!class_exists($resourceClass)) {
                return null;
            }

            $reflectionClass = new ReflectionClass($resourceClass);

            // Check if this is a collection
            $isCollection = $this->isResourceCollection($reflectionClass);

            // Check if this is a Laravel resource
            $isResource = $isCollection || $reflectionClass->isSubclassOf(JsonResource::class);

            if (!$isResource) {
                // Not a Laravel resource, return null
                return null;
            }

            // Handle collection differently
            if ($isCollection) {
                return $this->generateCollectionSchema($resourceClass, $modelClass, $generator);
            }

            // Create a base schema structure for a single resource
            $schema = [
                'type' => 'object',
                'properties' => []
            ];

            // If we have a related model, get its schema as a starting point
            if ($modelClass && class_exists($modelClass) && $generator) {
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

                // Extract properties from method body with nested structure support
                $this->extractNestedStructureFromMethod($toArrayMethod, $schema);
            }

            // Look for additional methods that might add fields
            $this->extractFromAdditionalMethods($reflectionClass, $schema);

            return $schema;
        } catch (Exception $e) {
            // Return a basic object schema if resource schema generation fails
            return ['type' => 'object', 'properties' => []];
        }
    }

    /**
     * Check if a class is a Laravel resource collection
     *
     * @param ReflectionClass $reflectionClass The class to check
     * @return bool
     */
    protected function isResourceCollection(ReflectionClass $reflectionClass): bool
    {
        // Check if it directly extends ResourceCollection
        if ($reflectionClass->isSubclassOf(ResourceCollection::class)) {
            return true;
        }

        // Check if it's an AnonymousResourceCollection
        if ($reflectionClass->getName() === AnonymousResourceCollection::class) {
            return true;
        }

        // Check for collection method - but make sure it's a static method on the class itself, not the JsonResource parent
        if ($reflectionClass->isSubclassOf(JsonResource::class) && $reflectionClass->hasMethod('collection')) {
            $method = $reflectionClass->getMethod('collection');
            if ($method->isStatic() && $method->getDeclaringClass()->getName() === $reflectionClass->getName()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate schema for a resource collection
     *
     * @param string $resourceClass Collection class name
     * @param string|null $modelClass Optional related model class
     * @param OpenApiGenerator|null $generator Reference to main generator
     * @return array The generated schema
     * @throws \ReflectionException
     */
    protected function generateCollectionSchema(string $resourceClass, ?string $modelClass = null, ?OpenApiGenerator $generator = null): array
    {
        // Detect the resource type for the collection
        $itemResourceClass = $this->detectCollectionResourceType($resourceClass);

        // Generate the item schema
        $itemSchema = null;
        if ($itemResourceClass) {
            // If we found the item resource class, generate its schema
            $itemSchema = $this->generateResourceSchema($itemResourceClass, $modelClass, $generator);
        } elseif ($modelClass) {
            // Fall back to model schema for items if available
            $itemSchema = $generator ? $generator->getModelSchema($modelClass) : null;
        }

        // Default item schema if we couldn't generate one
        if (!$itemSchema) {
            $itemSchema = ['type' => 'object', 'properties' => []];
        }

        // Check if we should create a paginated response
        $reflectionClass = new ReflectionClass($resourceClass);
        $isPaginated = $this->isPaginatedCollection($reflectionClass);

        if ($isPaginated) {
            // Create Laravel pagination structure
            return [
                'type' => 'object',
                'properties' => [
                    'data' => [
                        'type' => 'array',
                        'items' => $itemSchema
                    ],
                    'links' => [
                        'type' => 'object',
                        'properties' => [
                            'first' => ['type' => 'string', 'format' => 'uri'],
                            'last' => ['type' => 'string', 'format' => 'uri'],
                            'prev' => ['type' => 'string', 'format' => 'uri', 'nullable' => true],
                            'next' => ['type' => 'string', 'format' => 'uri', 'nullable' => true]
                        ]
                    ],
                    'meta' => [
                        'type' => 'object',
                        'properties' => [
                            'current_page' => ['type' => 'integer'],
                            'from' => ['type' => 'integer', 'nullable' => true],
                            'last_page' => ['type' => 'integer'],
                            'path' => ['type' => 'string'],
                            'per_page' => ['type' => 'integer'],
                            'to' => ['type' => 'integer', 'nullable' => true],
                            'total' => ['type' => 'integer']
                        ]
                    ]
                ]
            ];
        } else {
            // Create a simple array response
            return [
                'type' => 'array',
                'items' => $itemSchema
            ];
        }
    }

    /**
     * Detect if a collection is paginated
     *
     * @param ReflectionClass $reflectionClass The collection class
     * @return bool
     */
    protected function isPaginatedCollection(ReflectionClass $reflectionClass): bool
    {
        // Check if it has a paginate method or extends AbstractPaginator
        if ($reflectionClass->hasMethod('paginate') || $reflectionClass->isSubclassOf(AbstractPaginator::class)) {
            return true;
        }

        // Check if there's a reference to pagination in the class code
        $fileName = $reflectionClass->getFileName();
        if ($fileName && file_exists($fileName)) {
            $fileContent = file_get_contents($fileName);
            if (preg_match('/paginat(e|or|ion)/i', $fileContent)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detect the resource class type for a collection
     *
     * @param string $collectionClass The collection class name
     * @return string|null The resource class name or null if not detected
     */
    protected function detectCollectionResourceType(string $collectionClass): ?string
    {
        try {
            $reflectionClass = new ReflectionClass($collectionClass);

            // Check if it's an AnonymousResourceCollection created by the ::collection method
            if ($reflectionClass->getName() === AnonymousResourceCollection::class) {
                // Try to infer from the constructor or static properties
                $fileName = $reflectionClass->getFileName();
                if ($fileName && file_exists($fileName)) {
                    $fileContent = file_get_contents($fileName);
                    // Look for pattern like SomeResource::collection
                    if (preg_match('/([a-zA-Z0-9_\\\\]+)::collection/', $fileContent, $matches)) {
                        return $matches[1];
                    }
                }
            }

            // Check for collects property that defines the resource class
            if ($reflectionClass->hasProperty('collects')) {
                $collectsProperty = $reflectionClass->getProperty('collects');
                $collectsProperty->setAccessible(true);

                // Get the value - might be null if it's not statically defined
                $collectsValue = null;
                if (!$collectsProperty->isInitialized()) {
                    // Try to create an instance to get the value
                    if ($reflectionClass->hasMethod('__construct')) {
                        $constructor = $reflectionClass->getMethod('__construct');
                        if ($constructor->getNumberOfRequiredParameters() === 0) {
                            $instance = $reflectionClass->newInstance();
                            $collectsValue = $collectsProperty->getValue($instance);
                        }
                    }
                } else {
                    $collectsValue = $collectsProperty->getValue();
                }

                if ($collectsValue) {
                    return $collectsValue;
                }
            }

            // Try to infer from the namespace (e.g., App\Http\Resources\UserCollection -> App\Http\Resources\UserResource)
            $className = $reflectionClass->getShortName();
            if (Str::endsWith($className, 'Collection')) {
                $resourceName = Str::replaceLast('Collection', 'Resource', $className);
                $namespace = $reflectionClass->getNamespaceName();
                $potentialResourceClass = $namespace . '\\' . $resourceName;

                if (class_exists($potentialResourceClass)) {
                    return $potentialResourceClass;
                }
            }

            return null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Extract additional property information from common Laravel Resource methods
     *
     * @param ReflectionClass $reflectionClass The resource class reflection
     * @param array $schema The schema to populate
     */
    protected function extractFromAdditionalMethods(ReflectionClass $reflectionClass, array &$schema): void
    {
        // Check for common resource methods that might add fields
        $methodsToCheck = ['with', 'additional', 'withResponse', 'withMeta'];

        foreach ($methodsToCheck as $methodName) {
            if ($reflectionClass->hasMethod($methodName)) {
                $method = $reflectionClass->getMethod($methodName);
                if (!$method->isAbstract()) {
                    $this->extractNestedStructureFromMethod($method, $schema);
                }
            }
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
        $propertyPattern = '/@(api)?property\s+([\w\\\[\]]+)\s+\$(\w+)(?:\s+(.*))?/m';
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
     * Extract nested structure from a method
     *
     * @param ReflectionMethod $method The method to analyze
     * @param array $schema The schema to populate
     */
    protected function extractNestedStructureFromMethod(ReflectionMethod $method, array &$schema): void
    {
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

        // Find the return array structure
        if (preg_match('/return\s*\[\s*(.*?)\s*\]\s*;/s', $methodBody, $matches)) {
            $returnContent = $matches[1];
            $this->extractNestedStructure($returnContent, $schema['properties']);
        }

        // Look for whenLoaded patterns (relationships)
        $whenLoadedPattern = '/whenLoaded\([\'"](\w+)[\'"]/';
        if (preg_match_all($whenLoadedPattern, $methodBody, $matches)) {
            foreach ($matches[1] as $relation) {
                if (!isset($schema['properties'][$relation])) {
                    $schema['properties'][$relation] = [
                        'type' => 'object',
                        'nullable' => true,
                        'description' => "Loaded relationship: {$relation}"
                    ];
                }
            }
        }
    }

    /**
     * Extract nested structure from array content
     * 
     * @param string $content Array content
     * @param array $properties Schema properties to populate
     */
    protected function extractNestedStructure(string $content, array &$properties): void
    {
        // Extract all key-value pairs, including nested arrays
        // This regex matches patterns like 'key' => value, where value can be anything
        $pattern = '/[\'"](\w+)[\'"]\s*=>\s*(.+?)(?=,\s*[\'"]|,\s*$|\s*$)/s';
        
        if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $key = $match[1];
                $value = trim($match[2]);
                
                if (substr($value, 0, 1) === '[' && substr($value, -1) === ']') {
                    // This is a nested array
                    $nestedContent = substr($value, 1, -1);
                    
                    // Check if this is an associative array (with string keys)
                    if (preg_match('/[\'"](\w+)[\'"]\s*=>/', $nestedContent)) {
                        // This is an object (associative array)
                        $properties[$key] = [
                            'type' => 'object',
                            'properties' => []
                        ];
                        $this->extractNestedStructure($nestedContent, $properties[$key]['properties']);
                    } else {
                        // This is a simple array (non-associative)
                        $properties[$key] = [
                            'type' => 'array',
                            'items' => ['type' => 'string']
                        ];
                    }
                } else {
                    // This is a simple value
                    $properties[$key] = $this->inferTypeFromValue($value);
                }
            }
        }
    }
    
    /**
     * Infer the OpenAPI type from a PHP value
     * 
     * @param string $value The PHP value as a string
     * @return array OpenAPI type definition
     */
    protected function inferTypeFromValue(string $value): array
    {
        // Remove any trailing comma
        $value = rtrim($value, ',');
        
        // Check for common PHP values and types
        if ($value === 'true' || $value === 'false') {
            return ['type' => 'boolean'];
        } elseif ($value === 'null') {
            return ['type' => 'string', 'nullable' => true];
        } elseif (is_numeric($value)) {
            if (strpos($value, '.') !== false) {
                return ['type' => 'number'];
            }
            return ['type' => 'integer'];
        } elseif (preg_match('/^[\'"].+[\'"]$/', $value)) {
            // String literal
            return ['type' => 'string'];
        } elseif (strpos($value, '::') !== false) {
            // Class constant
            return ['type' => 'string'];
        } elseif (preg_match('/^\$[a-zA-Z0-9_]+(?:\[[\'"][a-zA-Z0-9_]+[\'"]\])+$/', $value)) {
            // Array access like $points['spent']
            return ['type' => 'string'];
        } else {
            // Default to string for anything else
            return ['type' => 'string'];
        }
    }

    /**
     * Convert PHP type to OpenAPI type with improved handling of Laravel resources
     *
     * @param string $phpType PHP type hint
     * @return array OpenAPI type definition
     */
    protected function convertPhpTypeToOpenApiType(string $phpType): array
    {
        // Clean the type name
        $phpType = trim($phpType);

        // Handle nullable types (e.g., ?string)
        if (str_starts_with($phpType, '?')) {
            $type = $this->convertPhpTypeToOpenApiType(substr($phpType, 1));
            $type['nullable'] = true;
            return $type;
        }

        // Handle fully qualified class names
        if (str_starts_with($phpType, '\\')) {
            $phpType = substr($phpType, 1);
        }

        // Handle array types
        if (str_ends_with($phpType, '[]') || preg_match('/^array<(.+)>$/', $phpType) || preg_match('/^Collection<(.+)>$/', $phpType)) {
            $itemType = null;

            if (str_ends_with($phpType, '[]')) {
                $itemType = substr($phpType, 0, -2);
            } elseif (preg_match('/^array<(.+)>$/', $phpType, $matches)) {
                $itemType = $matches[1];
            } elseif (preg_match('/^Collection<(.+)>$/', $phpType, $matches)) {
                $itemType = $matches[1];
            }

            return [
                'type' => 'array',
                'items' => $itemType ? $this->convertPhpTypeToOpenApiType($itemType) : ['type' => 'string']
            ];
        }

        // Handle union types (e.g., string|null)
        if (str_contains($phpType, '|')) {
            $types = explode('|', $phpType);
            $hasNull = in_array('null', $types, true);

            // Filter out null, mixed, or other ambiguous types
            $types = array_filter($types, fn($t) => !in_array(strtolower($t), ['null', 'mixed']));

            if (!empty($types)) {
                // Use the first non-null type
                $result = $this->convertPhpTypeToOpenApiType(reset($types));

                // Mark as nullable if null was in the union
                if ($hasNull) {
                    $result['nullable'] = true;
                }

                return $result;
            }
        }

        // Check for Laravel resource types
        if (class_exists($phpType)) {
            try {
                $reflectionClass = new ReflectionClass($phpType);

                // If it's a resource, we'll represent it as an object
                if ($reflectionClass->isSubclassOf(JsonResource::class)) {
                    return ['type' => 'object', 'description' => 'Laravel API Resource: ' . $phpType];
                }

                // If it's a model, also represent as an object
                if (str_contains($phpType, '\\Models\\') || str_contains($phpType, '\\Model')) {
                    return ['type' => 'object', 'description' => 'Eloquent Model: ' . $phpType];
                }
            } catch (Exception $e) {
                // If we can't analyze the class, fall back to default handling
            }
        }

        // Map PHP types to OpenAPI types
        return match(strtolower($phpType)) {
            'string' => ['type' => 'string'],
            'int', 'integer' => ['type' => 'integer'],
            'float', 'double', 'decimal' => ['type' => 'number'],
            'bool', 'boolean' => ['type' => 'boolean'],
            'array' => ['type' => 'array', 'items' => ['type' => 'string']],
            'object', 'stdclass', 'stdclass' => ['type' => 'object'],
            'datetime', 'datetime', 'dateinterface' => ['type' => 'string', 'format' => 'date-time'],
            'date' => ['type' => 'string', 'format' => 'date'],
            'carbon', 'carbon\\carbon', 'illuminate\\support\\carbon' => ['type' => 'string', 'format' => 'date-time'],
            'collection', 'illuminate\\support\\collection', 'illuminate\\database\\eloquent\\collection' => [
                'type' => 'array',
                'items' => ['type' => 'object']
            ],
            'model', 'illuminate\\database\\eloquent\\model' => ['type' => 'object'],
            'jsonresource', 'illuminate\\http\\resources\\json\\jsonresource' => ['type' => 'object'],
            'resourcecollection', 'illuminate\\http\\resources\\json\\resourcecollection' => [
                'type' => 'array',
                'items' => ['type' => 'object']
            ],
            default => ['type' => 'string'],
        };
    }
}
