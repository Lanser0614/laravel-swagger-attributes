<?php

namespace BellissimoPizza\SwaggerAttributes\Services;

use BellissimoPizza\SwaggerAttributes\Attributes\ApiSwagger;
use BellissimoPizza\SwaggerAttributes\Attributes\ApiSwaggerException;
use BellissimoPizza\SwaggerAttributes\Attributes\ApiSwaggerRequestBody;
use BellissimoPizza\SwaggerAttributes\Attributes\ApiSwaggerResponse;
use BellissimoPizza\SwaggerAttributes\Attributes\ApiSwaggerValidationErrorResponse;
use BellissimoPizza\SwaggerAttributes\Attributes\ApiSwaggerQueryParam;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\File;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\Yaml\Yaml;
use BellissimoPizza\SwaggerAttributes\Services\ResourceSchemaExtractor;
use BellissimoPizza\SwaggerAttributes\Enums\HttpMethod;
use BellissimoPizza\SwaggerAttributes\Enums\HttpStatusCode;
use BellissimoPizza\SwaggerAttributes\Enums\OpenApiDataType;
use BellissimoPizza\SwaggerAttributes\Enums\ResponseType;

class SwaggerGenerator
{
    /**
     * @var array The generated OpenAPI specification
     */
    protected array $openApi = [];

    /**
     * @var array Cached reflection classes
     */
    protected array $reflectionCache = [];

    /**
     * @param Router $router Laravel router instance
     */
    public function __construct(protected Router $router)
    {
    }

    /**
     * Generate the Swagger documentation
     *
     * @param string $outputPath The file path to save the documentation
     * @param string $format The output format (json or yaml)
     * @return bool
     */
    public function generate(string $outputPath, string $format = 'json'): bool
    {
        $this->initializeOpenApi();
        $this->scanRoutes();
        
        return $this->saveDocumentation($outputPath, $format);
    }

    /**
     * Initialize the OpenAPI structure with base information
     */
    protected function initializeOpenApi(): void
    {
        $this->openApi = [
            'openapi' => '3.0.0',
            'info' => [
                'title' => config('swagger-attributes.title', 'API Documentation'),
                'description' => config('swagger-attributes.description', 'API Documentation generated using Laravel Swagger Attributes'),
                'version' => config('swagger-attributes.version', '1.0.0'),
                'contact' => config('swagger-attributes.contact', []),
            ],
            'servers' => config('swagger-attributes.servers', [
                ['url' => config('app.url'), 'description' => 'API Server'],
            ]),
            'paths' => [],
            'components' => config('swagger-attributes.components', [
                'schemas' => [],
                'securitySchemes' => [],
            ]),
            'tags' => [],
        ];
    }

    /**
     * Scan all routes for Swagger attributes
     */
    protected function scanRoutes(): void
    {
        $routes = $this->router->getRoutes();
        $processedTags = [];

        foreach ($routes as $route) {
            $this->processRoute($route, $processedTags);
        }
    }

    /**
     * Process a single route for Swagger attributes
     *
     * @param Route $route The Laravel route
     * @param array $processedTags Array of already processed tags
     */
    protected function processRoute(Route $route, array &$processedTags): void
    {
        $actionName = $route->getActionName();
        
        // Skip routes without controller actions
        if ($actionName === 'Closure' || !str_contains($actionName, '@')) {
            return;
        }
        
        [$controllerClass, $methodName] = explode('@', $actionName);
        
        // Skip if controller class doesn't exist
        if (!class_exists($controllerClass)) {
            return;
        }
        
        $reflectionMethod = $this->getReflectionMethod($controllerClass, $methodName);
        
        // Skip if method doesn't exist
        if (!$reflectionMethod) {
            return;
        }
        
        // Get API Swagger attribute
        $apiSwaggerAttributes = $reflectionMethod->getAttributes(ApiSwagger::class);
        
        // Skip if no API Swagger attribute found
        if (empty($apiSwaggerAttributes)) {
            return;
        }
        
        $apiSwagger = $apiSwaggerAttributes[0]->newInstance();
        
        // Add tag to tags list if not already added
        if (!in_array($apiSwagger->tag, $processedTags)) {
            $this->openApi['tags'][] = [
                'name' => $apiSwagger->tag,
                'description' => $apiSwagger->tag . ' endpoints',
            ];
            $processedTags[] = $apiSwagger->tag;
        }
        
        // Process the route and add it to the OpenAPI spec
        $this->addRouteToOpenApi($route, $reflectionMethod, $apiSwagger);
    }

    /**
     * Add a route to the OpenAPI specification
     *
     * @param Route $route The Laravel route
     * @param ReflectionMethod $reflectionMethod The controller method reflection
     * @param ApiSwagger $apiSwagger The API Swagger attribute
     */
    protected function addRouteToOpenApi(Route $route, ReflectionMethod $reflectionMethod, ApiSwagger $apiSwagger): void
    {
        $path = $apiSwagger->path ?? $this->normalizePath($route->uri());
        
        // Get the HTTP method from the route if not specified in the attribute
        if ($apiSwagger->method === HttpMethod::GET && !empty($route->methods())) {
            // Use the first method from the route (usually the primary one)
            $routeMethods = array_map('strtoupper', $route->methods());
            // Filter out HEAD and OPTIONS methods which are often automatically added
            $filteredMethods = array_filter($routeMethods, function($method) {
                return !in_array($method, [HttpMethod::HEAD->value, HttpMethod::OPTIONS->value]);
            });
            
            if (!empty($filteredMethods)) {
                $httpMethod = reset($filteredMethods);
                if ($httpMethod !== false) {
                    // Convert string method to HttpMethod enum
                    try {
                        $method = HttpMethod::from($httpMethod);
                    } catch (\ValueError $e) {
                        // If invalid method, fallback to the one from attribute
                        $method = $apiSwagger->method;
                    }
                } else {
                    $method = $apiSwagger->method;
                }
            } else {
                $method = $apiSwagger->method;
            }
        } else {
            $method = $apiSwagger->method;
        }
        
        // Initialize path if it doesn't exist
        if (!isset($this->openApi['paths'][$path])) {
            $this->openApi['paths'][$path] = [];
        }
        
        // Convert enum to lowercase string for OpenAPI spec
        $methodKey = $method instanceof HttpMethod ? strtolower($method->value) : strtolower((string) $method);
        
        // Create basic operation object
        $operation = [
            'tags' => [$apiSwagger->tag],
            'summary' => $apiSwagger->summary,
            'description' => $apiSwagger->description,
            'operationId' => $this->generateOperationId($route),
            'responses' => [
                '200' => [
                    'description' => 'Successful operation',
                ],
            ],
        ];
        
        // Set as deprecated if specified
        if ($apiSwagger->deprecated) {
            $operation['deprecated'] = true;
        }
        
        // Add request body based on attributes
        $this->addRequestBodyToOperation($reflectionMethod, $operation);
        
        // Add responses based on attributes
        $this->addResponsesToOperation($reflectionMethod, $operation);
        
        // Add exceptions as responses
        $this->addExceptionsToOperation($reflectionMethod, $operation);
        
        // Add validation error response if explicitly specified
        $this->addValidationErrorResponse($reflectionMethod, $operation);
        
        // Add parameters from route
        $this->addParametersToOperation($route, $operation);
        
        // Add query parameters if defined
        $this->addQueryParamsToOperation($reflectionMethod, $operation);
        
        // Add security if configured
        $securityConfig = config('swagger-attributes.security');
        if (!empty($securityConfig) && is_array($securityConfig)) {
            $operation['security'] = $securityConfig;
        }
        
        // Add the operation to the path - convert enum to lowercase string for array key
        // OpenAPI spec expects lowercase HTTP methods
        $this->openApi['paths'][$path][strtolower($method->value)] = $operation;
    }

    /**
     * Generate an operation ID from a route
     *
     * @param Route $route The Laravel route
     * @return string
     */
    protected function generateOperationId(Route $route): string
    {
        $actionName = $route->getActionName();
        
        if (str_contains($actionName, '@')) {
            [$controller, $method] = explode('@', $actionName);
            $controller = class_basename($controller);
            return lcfirst($controller) . ucfirst($method);
        }
        
        return md5($route->uri() . implode('|', $route->methods()));
    }

    /**
     * Add request body information to an operation
     *
     * @param ReflectionMethod $reflectionMethod The controller method reflection
     * @param array $operation The OpenAPI operation object
     */
    protected function addRequestBodyToOperation(ReflectionMethod $reflectionMethod, array &$operation): void
    {
        $requestBodyAttributes = $reflectionMethod->getAttributes(ApiSwaggerRequestBody::class);
        
        if (empty($requestBodyAttributes)) {
            return;
        }
        
        $requestBody = $requestBodyAttributes[0]->newInstance();
        $schema = ['type' => 'object', 'properties' => []];
        
        // Get validation rules
        $rules = $requestBody->rules;
        
        // If a request class is provided, extract rules from it
        if ($requestBody->requestClass && class_exists($requestBody->requestClass)) {
            $requestReflection = new ReflectionClass($requestBody->requestClass);
            
            if ($requestReflection->isSubclassOf(FormRequest::class)) {
                $requestInstance = $requestReflection->newInstance();
                $rules = method_exists($requestInstance, 'rules') ? $requestInstance->rules() : [];
            }
        }
        
        // Convert Laravel validation rules to OpenAPI schema
        $schema['properties'] = $this->convertLaravelRulesToOpenApi($rules);
        
        // Add required properties
        $required = [];
        foreach ($rules as $field => $fieldRules) {
            $fieldRules = is_string($fieldRules) ? explode('|', $fieldRules) : $fieldRules;
            if (in_array('required', $fieldRules) || in_array('required_with', $fieldRules) || in_array('required_without', $fieldRules)) {
                $required[] = $field;
            }
        }
        
        if (!empty($required)) {
            $schema['required'] = $required;
        }
        
        // Create request body object
        $operation['requestBody'] = [
            'description' => $requestBody->description ?: 'Request Body',
            'required' => $requestBody->required,
            'content' => [
                $requestBody->contentType => [
                    'schema' => $schema
                ]
            ]
        ];
        
        // Automatically add a validation error response (422) when validation rules are present
        // Only add if it doesn't already exist
        if (!empty($rules) && (!isset($operation['responses']['422']) || empty($operation['responses']['422']))) {
            $operation['responses']['422'] = [
                'description' => 'Validation Error',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            '$ref' => '#/components/schemas/ValidationError'
                        ]
                    ]
                ]
            ];
        }
    }

    /**
     * Convert Laravel validation rules to OpenAPI schema properties
     *
     * @param array $rules Laravel validation rules
     * @return array
     */
    protected function convertLaravelRulesToOpenApi(array $rules): array
    {
        $properties = [];
        
        foreach ($rules as $field => $fieldRules) {
            $fieldRules = is_string($fieldRules) ? explode('|', $fieldRules) : $fieldRules;
            $property = ['type' => 'string']; // Default type
            
            foreach ($fieldRules as $rule) {
                $this->applyRuleToProperty($rule, $property);
            }
            
            $properties[$field] = $property;
        }
        
        return $properties;
    }

    /**
     * Apply a single Laravel validation rule to an OpenAPI property
     *
     * @param string|object $rule The Laravel validation rule
     * @param array $property The OpenAPI property to modify
     */
    protected function applyRuleToProperty($rule, array &$property): void
    {
        if (is_object($rule)) {
            $rule = get_class($rule);
        }
        
        if (is_string($rule)) {
            // Handle string rules
            if (str_starts_with($rule, 'integer') || $rule === 'numeric') {
                $property['type'] = 'integer';
            } elseif ($rule === 'boolean') {
                $property['type'] = 'boolean';
            } elseif ($rule === 'array') {
                $property['type'] = 'array';
                $property['items'] = ['type' => 'string'];
            } elseif (str_starts_with($rule, 'min:')) {
                $min = (int) substr($rule, 4);
                if ($property['type'] === 'string') {
                    $property['minLength'] = $min;
                } elseif ($property['type'] === 'integer') {
                    $property['minimum'] = $min;
                } elseif ($property['type'] === 'array') {
                    $property['minItems'] = $min;
                }
            } elseif (str_starts_with($rule, 'max:')) {
                $max = (int) substr($rule, 4);
                if ($property['type'] === 'string') {
                    $property['maxLength'] = $max;
                } elseif ($property['type'] === 'integer') {
                    $property['maximum'] = $max;
                } elseif ($property['type'] === 'array') {
                    $property['maxItems'] = $max;
                }
            } elseif ($rule === 'email') {
                $property['format'] = 'email';
            } elseif ($rule === 'date' || $rule === 'date_format') {
                $property['format'] = 'date-time';
            } elseif (str_starts_with($rule, 'in:')) {
                $values = explode(',', substr($rule, 3));
                $property['enum'] = $values;
            } elseif ($rule === 'uuid') {
                $property['format'] = 'uuid';
            }
        }
    }

    /**
     * Add exceptions information to an operation
     *
     * @param ReflectionMethod $reflectionMethod The controller method reflection
     * @param array $operation The OpenAPI operation object
     */
    protected function addExceptionsToOperation(ReflectionMethod $reflectionMethod, array &$operation): void
    {
        $exceptionAttributes = $reflectionMethod->getAttributes(ApiSwaggerException::class);
        
        foreach ($exceptionAttributes as $attribute) {
            $exception = $attribute->newInstance();
            $statusCode = (string) $exception->statusCode->value;
            
            $response = [
                'description' => $exception->message,
            ];
            
            if (!empty($exception->responseSchema)) {
                $response['content'] = [
                    'application/json' => [
                        'schema' => $exception->responseSchema
                    ]
                ];
            } else {
                $response['content'] = [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'message' => [
                                    'type' => 'string',
                                    'example' => $exception->message
                                ],
                                'status_code' => [
                                    'type' => 'integer',
                                    'example' => $exception->statusCode->value
                                ]
                            ]
                        ]
                    ]
                ];
            }
            
            $operation['responses'][$statusCode] = $response;
        }
    }
    
    /**
     * Add validation error response to an operation
     *
     * @param ReflectionMethod $reflectionMethod The controller method reflection
     * @param array $operation The OpenAPI operation object
     */
    protected function addValidationErrorResponse(ReflectionMethod $reflectionMethod, array &$operation): void
    {
        $validationErrorAttributes = $reflectionMethod->getAttributes(ApiSwaggerValidationErrorResponse::class);
        
        // If we already have a 422 response from the request body handling, don't add another one
        if (isset($operation['responses']['422']) && !empty($operation['responses']['422'])) {
            return;
        }
        
        // Check if we have an explicit validation error response attribute
        if (!empty($validationErrorAttributes)) {
            $validationError = $validationErrorAttributes[0]->newInstance();
            
            $operation['responses']['422'] = [
                'description' => $validationError->description,
                'content' => [
                    'application/json' => [
                        'schema' => [
                            '$ref' => '#/components/schemas/ValidationError'
                        ]
                    ]
                ]
            ];
        }
    }

    /**
     * Add parameters to an operation from a route
     *
     * @param Route $route The Laravel route
     * @param array $operation The OpenAPI operation object
     */
    protected function addParametersToOperation(Route $route, array &$operation): void
    {
        $parameters = [];
        
        // Process URI parameters
        preg_match_all('/\{([^}]+)\}/', $route->uri(), $matches);
        
        if (isset($matches[1]) && !empty($matches[1])) {
            foreach ($matches[1] as $paramName) {
                $isOptional = str_ends_with($paramName, '?');
                $cleanParamName = $isOptional ? rtrim($paramName, '?') : $paramName;
                
                $parameters[] = [
                    'name' => $cleanParamName,
                    'in' => 'path',
                    'description' => ucfirst(str_replace('_', ' ', $cleanParamName)),
                    'required' => !$isOptional,
                    'schema' => [
                        'type' => 'string'
                    ]
                ];
            }
        }
        
        if (!empty($parameters)) {
            if (isset($operation['parameters'])) {
                $operation['parameters'] = array_merge($operation['parameters'], $parameters);
            } else {
                $operation['parameters'] = $parameters;
            }
        }
    }
    
    /**
     * Add query parameters to an operation from ApiSwaggerQueryParam attributes
     *
     * @param ReflectionMethod $reflectionMethod The controller method reflection
     * @param array $operation The OpenAPI operation object
     */
    protected function addQueryParamsToOperation(ReflectionMethod $reflectionMethod, array &$operation): void
    {
        $queryParamAttributes = $reflectionMethod->getAttributes(ApiSwaggerQueryParam::class);
        
        if (empty($queryParamAttributes)) {
            return;
        }
        
        $parameters = [];
        
        foreach ($queryParamAttributes as $attribute) {
            $queryParam = $attribute->newInstance();
            
            $parameter = [
                'name' => $queryParam->name,
                'in' => 'query',
                'description' => $queryParam->description,
                'required' => $queryParam->required,
                'schema' => [
                    'type' => $queryParam->type->value,
                ]
            ];
            
            // Add format if specified
            if ($queryParam->format) {
                $parameter['schema']['format'] = $queryParam->format;
            }
            
            // Add example if specified
            if ($queryParam->example !== null) {
                $parameter['schema']['example'] = $queryParam->example;
            }
            
            // Add default value if specified
            if ($queryParam->default !== null) {
                $parameter['schema']['default'] = $queryParam->default;
            }
            
            // Add enum values if specified
            if (!empty($queryParam->enum)) {
                $parameter['schema']['enum'] = $queryParam->enum;
            }
            
            // Add additional schema properties
            if (!empty($queryParam->schema)) {
                $parameter['schema'] = array_merge($parameter['schema'], $queryParam->schema);
            }
            
            $parameters[] = $parameter;
        }
        
        if (!empty($parameters)) {
            if (isset($operation['parameters'])) {
                $operation['parameters'] = array_merge($operation['parameters'], $parameters);
            } else {
                $operation['parameters'] = $parameters;
            }
        }
    }

    /**
     * Normalize a path for OpenAPI spec
     *
     * @param string $path The route URI
     * @return string
     */
    protected function normalizePath(string $path): string
    {
        // Replace Laravel's optional parameters {param?} with OpenAPI format {param}
        $path = preg_replace('/\{([^}]+)\?\}/', '{$1}', $path);
        
        // Make sure path starts with '/'
        if (!str_starts_with($path, '/')) {
            $path = '/' . $path;
        }
        
        return $path;
    }

    /**
     * Get a reflection method with caching
     *
     * @param string $className The class name
     * @param string $methodName The method name
     * @return ReflectionMethod|null
     */
    protected function getReflectionMethod(string $className, string $methodName): ?ReflectionMethod
    {
        $cacheKey = $className . '::' . $methodName;
        
        if (!isset($this->reflectionCache[$cacheKey])) {
            try {
                $reflectionClass = new ReflectionClass($className);
                
                if ($reflectionClass->hasMethod($methodName)) {
                    $this->reflectionCache[$cacheKey] = $reflectionClass->getMethod($methodName);
                } else {
                    return null;
                }
            } catch (\ReflectionException $e) {
                return null;
            }
        }
        
        return $this->reflectionCache[$cacheKey];
    }

    /**
     * Add response information to an operation
     *
     * @param ReflectionMethod $reflectionMethod The controller method reflection
     * @param array $operation The OpenAPI operation object
     */
    protected function addResponsesToOperation(ReflectionMethod $reflectionMethod, array &$operation): void
    {
        $responseAttributes = $reflectionMethod->getAttributes(ApiSwaggerResponse::class);
        
        if (empty($responseAttributes)) {
            return; // Default 200 response is already added
        }
        
        foreach ($responseAttributes as $attribute) {
            $response = $attribute->newInstance();
            $statusCode = (string) $response->statusCode->value;
            
            $responseObject = [
                'description' => $response->description,
            ];
            
            // If we have a model or schema, add content
            if ($response->model || !empty($response->schema)) {
                $schema = $this->getResponseSchema($response);
                
                $responseObject['content'] = [
                    $response->contentType => [
                        'schema' => $schema
                    ]
                ];
            }
            
            $operation['responses'][$statusCode] = $responseObject;
        }
    }
    
    /**
     * Get schema for response based on model or custom schema
     *
     * @param ApiSwaggerResponse $response The response attribute
     * @return array The schema definition
     */
    protected function getResponseSchema(ApiSwaggerResponse $response): array
    {
        $modelSchema = null;
        $resourceSchema = null;
        $customProperties = [];
        
        // Generate model schema if a model is provided
        if ($response->model && class_exists($response->model)) {
            $modelSchema = $this->generateModelSchema($response->model);
        }
        
        // Generate resource schema if a resource is provided
        if ($response->resource && class_exists($response->resource)) {
            $resourceSchema = $this->generateResourceSchema($response->resource, $response->model);
            // If we have both resource and model schemas, resource takes precedence
            if ($resourceSchema) {
                $modelSchema = $resourceSchema;
            }
        }
        
        // Special case: check for 'model' keyword in schema
        if (!empty($response->schema)) {
            // Special case: handle schema with 'data' property set to 'model'
            if (isset($response->schema['data']) && $response->schema['data'] === 'model' && $modelSchema) {
                // Return a nested structure with 'data' containing the model schema
                return [
                    'type' => 'object',
                    'properties' => [
                        'data' => $modelSchema
                    ]
                ];
            }
            
            // Check if schema already has a type and possibly properties structure
            if (isset($response->schema['type'])) {
                if (!$modelSchema) {
                    // If only schema is provided with type, return it directly
                    return $response->schema;
                }
                // Both model and fully structured schema are present
                // Combine them according to the desired logic below
            } else {
                // Process properties to handle enums and convert to proper schema objects
                foreach ($response->schema as $property => $type) {
                    if ($type === 'model' && $modelSchema) {
                        // If a property is set to 'model', use the complete model schema
                        $customProperties[$property] = $modelSchema;
                    } elseif (is_object($type) && $type instanceof OpenApiDataType) {
                        // Convert OpenApiDataType enum to proper schema
                        $customProperties[$property] = ['type' => $type->value];
                        
                        // Add format if available
                        $defaultFormat = $type->defaultFormat();
                        if ($defaultFormat) {
                            $customProperties[$property]['format'] = $defaultFormat;
                        }
                    } elseif (is_array($type)) {
                        $customProperties[$property] = $type;
                    } else {
                        // String or other primitive value
                        $customProperties[$property] = ['type' => (string)$type];
                    }
                }
            }
        }
        
        // Now decide what schema to return based on what we've processed
        
        // If no model is provided, return the custom schema or an empty schema
        if (!$modelSchema) {
            if (!empty($customProperties)) {
                return [
                    'type' => 'object',
                    'properties' => $customProperties
                ];
            }
            return ['type' => 'object', 'properties' => []];
        }
        
        // If we have both model and custom properties, merge them
        if (!empty($customProperties)) {
            // Here's where we combine the model schema with custom properties
            // Custom properties take precedence over model properties
            $modelSchema['properties'] = array_merge(
                $modelSchema['properties'] ?? [], 
                $customProperties
            );
        }
        
        // Use response type to determine the structure with the potentially modified model schema
        if ($response->responseType === ResponseType::PAGINATED) {
            // Create a paginated response structure
            $paginatedStructure = [
                'type' => 'object',
                'properties' => [
                    'data' => [
                        'type' => 'array',
                        'items' => $modelSchema
                    ],
                    'meta' => [
                        'type' => 'object',
                        'properties' => [
                            'current_page' => ['type' => 'integer'],
                            'from' => ['type' => 'integer'],
                            'last_page' => ['type' => 'integer'],
                            'per_page' => ['type' => 'integer'],
                            'to' => ['type' => 'integer'],
                            'total' => ['type' => 'integer']
                        ]
                    ],
                    'links' => [
                        'type' => 'object',
                        'properties' => [
                            'first' => ['type' => 'string'],
                            'last' => ['type' => 'string'],
                            'prev' => ['type' => 'string', 'nullable' => true],
                            'next' => ['type' => 'string', 'nullable' => true]
                        ]
                    ]
                ]
            ];
            
            return $paginatedStructure;
        } elseif ($response->responseType === ResponseType::COLLECTION) {
            // Create a collection structure
            $collectionStructure = [
                'type' => 'array',
                'items' => $modelSchema
            ];
            
            return $collectionStructure;
        }
        
        // For single item response, return the model schema directly
        return $modelSchema;
    }
    
    /**
     * Generate schema from Eloquent model
 *
 * @param string $modelClass Fully qualified model class name
 * @return array The generated schema
 */
protected function generateModelSchema(string $modelClass): array
{
    try {
        $model = new $modelClass();
        
        if (!$model instanceof Model) {
            return ['type' => 'object', 'properties' => []];
        }
        
        $table = $model->getTable();
        $connection = $model->getConnection();
        $schema = ['type' => 'object', 'properties' => []];
        
        // Extract PHPDoc property types from IDE Helper annotations if they exist
        $propertyTypes = $this->extractPhpDocPropertyTypes($modelClass);
        
        if (Schema::connection($connection->getName())->hasTable($table)) {
            $columns = Schema::connection($connection->getName())->getColumnListing($table);
            
            foreach ($columns as $column) {
                // Check if we have IDE Helper type information for this property
                if (isset($propertyTypes[$column])) {
                    $schema['properties'][$column] = $this->convertPhpTypeToOpenApi($propertyTypes[$column]);
                } else {
                    $type = Schema::connection($connection->getName())
                        ->getColumnType($table, $column);
                    
                    $schema['properties'][$column] = $this->mapDatabaseTypeToOpenApi($type);
                }
            }
            
            if (method_exists($model, 'getAppends') && is_array($model->getAppends())) {
                foreach ($model->getAppends() as $append) {
                    // Check if we have IDE Helper type information for appended attributes
                    if (isset($propertyTypes[$append])) {
                        $schema['properties'][$append] = $this->convertPhpTypeToOpenApi($propertyTypes[$append]);
                    } else {
                        $schema['properties'][$append] = ['type' => 'string'];
                    }
                }
            }
        }
        
        return $schema;
        
    } catch (\Exception $e) {
        // If anything goes wrong, return a basic object schema
        return ['type' => 'object', 'properties' => []];
    }
}

    /**
     * Generate an OpenAPI schema from a Laravel API Resource
     *
     * @param string $resourceClass Fully qualified class name of a Laravel Resource
     * @param string|null $modelClass Optional related model class name
     * @return array|null The generated schema or null if unable to generate
     */
    protected function generateResourceSchema(string $resourceClass, ?string $modelClass = null): ?array
    {
        static $resourceExtractor = null;
        
        try {
            if ($resourceExtractor === null) {
                $resourceExtractor = new ResourceSchemaExtractor();
            }
            
            return $resourceExtractor->generateResourceSchema($resourceClass, $modelClass, $this);
        } catch (\Exception $e) {
            // Return a basic object schema if resource schema generation fails
            return ['type' => 'object', 'properties' => []];
        }
    }
    
    /**
     * Public method to get a model schema for use by other services
     * 
     * @param string $modelClass Fully qualified model class name
     * @return array The generated schema
     */
    public function getModelSchema(string $modelClass): array
    {
        return $this->generateModelSchema($modelClass);
    }
    
    /**
     * Extract property types from PHPDoc annotations generated by Laravel IDE Helper
     *
     * @param string $modelClass Fully qualified model class name
     * @return array Associative array of property names and their types
    protected function extractPhpDocPropertyTypes(string $modelClass): array
    {
        $propertyTypes = [];
        
        if (!class_exists($modelClass)) {
            return $propertyTypes;
        }
        
        try {
            $reflection = new \ReflectionClass($modelClass);
            $docComment = $reflection->getDocComment();
            
            if (!$docComment) {
                return $propertyTypes;
            }
            
            // Extract @property PHPDoc tags
            preg_match_all('/@property(?:-read|-write)?\s+([\w\\\[\]<>|]+)\s+\$(\w+)/', $docComment, $matches, PREG_SET_ORDER);
            
            foreach ($matches as $match) {
                if (isset($match[1]) && isset($match[2])) {
                    $type = $match[1];
                    $property = $match[2];
                    $propertyTypes[$property] = $type;
                }
            }
            
            return $propertyTypes;
        } catch (\Exception $e) {
            return [];
        }
    }

/**
 * Map database column type to OpenAPI type
 *
 * @param string $databaseType The database column type
 * @return array The OpenAPI type definition
 */
protected function mapDatabaseTypeToOpenApi(string $databaseType): array
{
    switch ($databaseType) {
        // Integer types (MySQL and PostgreSQL)
        case 'bigint':
        case 'int':
        case 'integer':
        case 'smallint':
        case 'tinyint':
        case 'int2':          // PostgreSQL
        case 'int4':          // PostgreSQL
        case 'int8':          // PostgreSQL
        case 'serial':        // PostgreSQL
        case 'bigserial':     // PostgreSQL
        case 'smallserial':   // PostgreSQL
            return ['type' => 'integer'];
            
        // Numeric types (MySQL and PostgreSQL)
        case 'decimal':
        case 'double':
        case 'float':
        case 'numeric':       // PostgreSQL
        case 'real':          // PostgreSQL
        case 'money':         // PostgreSQL
            return ['type' => 'number', 'format' => 'float'];
            
        // Boolean types
        case 'boolean':
        case 'bool':          // PostgreSQL
            return ['type' => 'boolean'];
            
        // Date types
        case 'date':
            return ['type' => 'string', 'format' => 'date'];
            
        // Datetime types
        case 'datetime':
        case 'timestamp':
        case 'timestamptz':   // PostgreSQL with timezone
            return ['type' => 'string', 'format' => 'date-time'];
            
        // JSON types
        case 'json':
        case 'jsonb':         // PostgreSQL binary JSON
        case 'array':         // For both MySQL and PostgreSQL arrays
            return ['type' => 'object'];
            
        // Time types
        case 'time':
        case 'timetz':        // PostgreSQL time with timezone
            return ['type' => 'string', 'format' => 'time'];
            
        // UUID types
        case 'uuid':          // Both MySQL and PostgreSQL
            return ['type' => 'string', 'format' => 'uuid'];
        
        // Network address types (PostgreSQL specific)
        case 'inet':
        case 'cidr':
        case 'macaddr':
            return ['type' => 'string'];
            
        // Geometric types (PostgreSQL specific)
        case 'point':
        case 'line':
        case 'lseg':
        case 'box':
        case 'path':
        case 'polygon':
        case 'circle':
            return ['type' => 'string', 'description' => 'PostgreSQL geometric type'];
            
        // Text and character types
        case 'char':
        case 'varchar':
        case 'text':
        case 'mediumtext':
        case 'longtext':
        case 'character':     // PostgreSQL
        case 'character varying': // PostgreSQL
        default:
            return ['type' => 'string'];
    }
}

/**
 * Get the table name of a model
 *
 * @param string $modelClass Fully qualified model class name
 * @return string|null The table name or null if unable to determine
 */
protected function getModelTableName(string $modelClass): ?string
{
    try {
        $model = new $modelClass;
        return $model->getTable();
    } catch (\Exception $e) {
        return null;
    }
}

/**
 * Generate an OpenAPI schema from a Laravel API Resource
 *
 * @param string $resourceClass Fully qualified class name of a Laravel Resource
    /**
     * Convert PHP type to OpenAPI schema type
     * 
     * @param string $type PHP type from PHPDoc
     * @return array OpenAPI schema for the type
     */
    protected function convertPhpTypeToOpenApi(string $type): array
    {
        // Normalize the type by removing nullable indicator and trimming
        $type = trim(str_replace('?', '', $type));
        
        // Handle collection/array types
        if (preg_match('/^array<(.+)>$|^(.+)\[\]$/', $type, $matches)) {
            $itemType = !empty($matches[1]) ? $matches[1] : $matches[2];
            return [
                'type' => 'array',
                'items' => $this->convertPhpTypeToOpenApi($itemType)
            ];
        }
        
        // Handle union types (e.g., string|null)
        if (str_contains($type, '|')) {
            $types = explode('|', $type);
            // Filter out null, mixed, or other ambiguous types
            $types = array_filter($types, fn($t) => !in_array(strtolower($t), ['null', 'mixed']));
            if (!empty($types)) {
                // Use the first non-null type
                return $this->convertPhpTypeToOpenApi(reset($types));
            }
        }
        
        // Handle common PHP types
        switch (strtolower($type)) {
            case 'int': 
            case 'integer':
                return ['type' => 'integer'];
                
            case 'float':
            case 'double':
            case 'decimal':
                return ['type' => 'number', 'format' => 'float'];
                
            case 'bool':
            case 'boolean':
                return ['type' => 'boolean'];
                
            case 'array':
                return ['type' => 'array', 'items' => ['type' => 'string']];
                
            case 'object':
            case 'stdclass':
                return ['type' => 'object'];
                
            case '\datetime':
            case 'datetime':
            case '\carbon\carbon':
            case 'carbon':
            case '\illuminate\support\carbon':
                return ['type' => 'string', 'format' => 'date-time'];
                
            case 'date':
                return ['type' => 'string', 'format' => 'date'];
                
            default:
                // For complex or custom types, default to string or check for known model types
                return ['type' => 'string'];
        }
    }
    
    /**
     * Map database column type to OpenAPI type
     *
     * @param string $databaseType The database column type
     * @return array The OpenAPI type definition
     */

    /**
     * Save the documentation to a file
     *
     * @param string $outputPath The file path to save the documentation
     * @param string $format The output format (json or yaml)
     * @return bool
     */
    protected function saveDocumentation(string $outputPath, string $format = 'json'): bool
    {
        $directory = dirname($outputPath);
        
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }
        
        // Remove existing file if it exists to ensure clean generation
        if (File::exists($outputPath)) {
            File::delete($outputPath);
        }
        
        // Convert the OpenAPI spec to the requested format
        if (strtolower($format) === 'yaml' || strtolower($format) === 'yml') {
            // Use Symfony YAML component to convert to YAML
            $content = Yaml::dump($this->openApi, 10, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
            
            // If the file doesn't have a .yaml or .yml extension, add .yaml
            if (!preg_match('/\.(ya?ml)$/i', $outputPath)) {
                $outputPath = preg_replace('/\.json$/i', '.yaml', $outputPath);
                if (!preg_match('/\.yaml$/i', $outputPath)) {
                    $outputPath .= '.yaml';
                }
            }
        } else {
            // Default to JSON format
            $content = json_encode($this->openApi, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            
            // If the file doesn't have a .json extension, add it
            if (!preg_match('/\.json$/i', $outputPath)) {
                $outputPath = preg_replace('/\.(ya?ml)$/i', '.json', $outputPath);
                if (!preg_match('/\.json$/i', $outputPath)) {
                    $outputPath .= '.json';
                }
            }
        }
        
        return File::put($outputPath, $content) !== false;
    }
}
