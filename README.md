# Laravel OpenApi Attributes

[![Latest Version](https://img.shields.io/github/v/release/bellissimopizza/laravel-swagger-attributes)](https://github.com/bellissimopizza/laravel-swagger-attributes/releases)
[![License](https://img.shields.io/github/license/bellissimopizza/laravel-swagger-attributes)](https://github.com/bellissimopizza/laravel-swagger-attributes/blob/main/LICENSE)

> Version 4.0.0 now available with full Laravel API Resource support! Generate accurate OpenAPI schemas directly from your Resource classes.

This package provides a clean way to generate Swagger/OpenAPI documentation for your Laravel API using PHP 8 Attributes instead of annotations.

## Features

- Use modern PHP 8 Attributes to document your API endpoints
- Leverage PHP 8 Enums for type safety and standardization
- **[NEW in v4.0.0]** Full support for Laravel API Resources in response schema generation
- **[NEW in v4.0.0]** Extract property types from Resource PHPDoc comments and method analysis
- Automatically extract validation rules from Laravel Form Request classes
- Document API exceptions with status codes and error messages
- Command-line tool to scan routes and generate Swagger documentation
- Built-in Swagger UI and Redoc UI for viewing documentation
- Extract property types from Laravel IDE Helper PHPDoc comments
- Support for query parameters with repeatable attributes
- Fully customizable through configuration

## Requirements

- PHP 8.0 or higher
- Laravel 8.0 or higher

## Installation

You can install the package via composer:

```bash
composer require bellissimopizza/laravel-swagger-attributes
```

After installing, publish the configuration and assets:

```bash
# Publish everything
php artisan vendor:publish --provider="BellissimoPizza\SwaggerAttributes\Providers\SwaggerAttributesServiceProvider" --tag="swagger-attributes"

# Or publish individually
php artisan vendor:publish --provider="BellissimoPizza\SwaggerAttributes\Providers\SwaggerAttributesServiceProvider" --tag="swagger-attributes-config"
php artisan vendor:publish --provider="BellissimoPizza\SwaggerAttributes\Providers\SwaggerAttributesServiceProvider" --tag="swagger-attributes-views"
php artisan vendor:publish --provider="BellissimoPizza\SwaggerAttributes\Providers\SwaggerAttributesServiceProvider" --tag="swagger-attributes-assets"
```

## Usage

### Basic Configuration

First, configure the package in `config/swagger-attributes.php`:

```php
return [
    'title' => 'My API Documentation',
    'description' => 'Documentation for my awesome API',
    'version' => '1.0.0',
    
    // UI configuration - choose between Swagger UI and Redoc
    'ui' => [
        // Which UI to use: 'swagger', 'redoc', or 'both'
        'type' => 'both',
        
        // UI routes
        'swagger_route' => 'api/documentation',
        'redoc_route' => 'api/redoc',
        
        // Redoc options
        'redoc' => [
            'theme' => 'light',  // light, dark
            'hide_download_button' => false,
            'expand_responses' => 'all', // all, success, none
        ],
        
        // Swagger UI options
        'swagger' => [
            'deep_linking' => true,
            'doc_expansion' => 'list', // list, full, none
        ],
    ],
    
    // ...other configuration options
];
```

### Documenting API Endpoints

Use PHP attributes to document your API endpoints. Here's a simple example:

```php
use BellissimoPizza\SwaggerAttributes\Attributes\OpenApi;
use BellissimoPizza\SwaggerAttributes\Attributes\OpenApiRequestBody;
use BellissimoPizza\SwaggerAttributes\Attributes\OpenApiException;

class UserController extends Controller
{
    #[OpenApi(
        tag: 'Users',
        summary: 'Create new user',
        method: 'POST'
    )]
    #[OpenApiRequestBody(
        requestClass: StoreUserRequest::class
    )]
    #[OpenApiException(
        statusCode: 422,
        message: 'Validation failed'
    )]
    #[OpenApiException(
        statusCode: 500,
        message: 'Server error'
    )]
    public function store(StoreUserRequest $request)
    {
        // Your controller logic here
    }
}
```

### Available Attributes

#### ApiSwagger

The main attribute for documenting API endpoints:

```php
#[ApiSwagger(
    tag: 'Users',                  // Used for grouping endpoints
    summary: 'Create new user',    // Short summary
    description: 'Detailed...',    // Optional longer description
    method: 'POST',                // HTTP method (GET, POST, PUT, DELETE, etc.) - automatically detected from routes if not specified
    path: '/api/users',            // Optional custom path (if different from route)
    deprecated: false              // Mark as deprecated
)]
```

#### ApiSwaggerRequestBody

Document request body and validation rules:

```php
#[ApiSwaggerRequestBody(
    requestClass: StoreUserRequest::class,  // Laravel FormRequest class
    rules: [],                              // Manual rules (if no request class)
    contentType: 'application/json',        // Content type
    required: true,                         // Whether body is required
    description: 'User data'                // Description
)]
```

#### ApiSwaggerException

Document possible exceptions (can be repeated for multiple exceptions):

```php
#[ApiSwaggerException(
    statusCode: 404,                      // HTTP status code
    message: 'User not found',            // Error message
    exceptionClass: UserNotFoundException::class, // Optional exception class
    responseSchema: []                    // Optional custom response schema
)]
```

#### ApiSwaggerResponse

Document response data with automatic schema generation from Eloquent models:

```php
#[ApiSwaggerResponse(
    statusCode: 200,                     // HTTP status code
    description: 'Successful operation',  // Response description
    model: User::class,                   // Eloquent model (auto-generates schema from DB)
    resource: UserResource::class,        // Laravel API Resource (overrides model properties)
    responseType: ResponseType::SINGLE,   // SINGLE, COLLECTION, or PAGINATED
    contentType: 'application/json'       // Content type
)]

// Or use a custom schema
#[ApiSwaggerResponse(
    statusCode: 200,
    schema: [
        'type' => 'object',
        'properties' => [
            'id' => ['type' => 'integer'],
            'name' => ['type' => 'string']
        ]
    ]
)]
```

#### Using Laravel API Resources

The package now supports Laravel API Resources for response schema generation. This provides more flexibility when your API responses don't directly match your database models.

```php
// Basic API Resource usage
#[ApiSwaggerResponse(
    statusCode: HttpStatusCode::OK,
    resource: UserResource::class,
    responseType: ResponseType::SINGLE
)]

// Combine with a model for additional type information
#[ApiSwaggerResponse(
    statusCode: HttpStatusCode::OK,
    model: User::class,            // Base model schema 
    resource: UserResource::class,  // Resource overrides model properties
    responseType: ResponseType::COLLECTION
)]

// API Resource with nested model placeholder
#[ApiSwaggerResponse(
    statusCode: HttpStatusCode::OK,
    model: User::class,
    resource: UserResource::class,
    schema: [
        'success' => OpenApiDataType::BOOLEAN,
        'data' => 'model'  // This will be replaced with the resource schema
    ]
)]
```

The package automatically analyzes your API Resource classes to extract property information by:

1. Using PHPDoc annotations in your resource's `toArray()` method
2. Analyzing the resource structure when a model is provided
3. Supporting nested models and resource structures

For best results, document your API Resource properties using PHPDoc:

```php
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @property int $id User ID
     * @property string $name User's full name
     * @property string $email User's email address
     * @property array $permissions User's permissions
     * @property string $created_at Creation timestamp
     * 
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'permissions' => $this->permissions,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
```

#### ApiSwaggerQueryParam

Document query parameters for filtering, sorting, and pagination (can be repeated for multiple parameters):

```php
use BellissimoPizza\SwaggerAttributes\Enums\OpenApiDataType;

#[ApiSwaggerQueryParam(
    name: 'filter',                     // Parameter name
    type: OpenApiDataType::STRING,      // Data type (STRING, INTEGER, BOOLEAN, etc.)
    description: 'Filter results',       // Parameter description
    required: false,                     // Whether parameter is required
    example: 'active',                   // Example value
    default: null,                       // Default value
    enum: ['active', 'inactive'],        // Possible values for enum types
    format: null,                        // Format (date, date-time, etc.)
    schema: []                           // Additional schema properties
)]
#[ApiSwaggerQueryParam(
    name: 'sort',
    type: OpenApiDataType::STRING,
    description: 'Sort field',
    example: 'created_at'
)]
#[ApiSwaggerQueryParam(
    name: 'page',
    type: OpenApiDataType::INTEGER,
    description: 'Page number for pagination',
    default: 1
)]
public function index(Request $request)
{
    // Your controller logic here
}
```

### Generating Documentation

Run the command to scan your routes and generate Swagger documentation:

```bash
php artisan swagger:generate
```

By default, this will save the documentation to `storage/api-docs/swagger.json`. You can specify a custom output path and format (JSON or YAML):

```bash
# Generate JSON (default)
php artisan swagger:generate --output=public/api-docs/swagger.json --format=json

# Generate YAML
php artisan swagger:generate --output=public/api-docs/swagger.yaml --format=yaml
```

### Viewing Documentation

Once generated, you can view your documentation at the following URLs:

- Swagger UI: `/api/documentation` (or your custom route)
- Redoc UI: `/api/redoc` (or your custom route)

You can configure which UI is enabled in the configuration file:

```php
'ui' => [
    'type' => 'both', // Options: 'swagger', 'redoc', 'both'
],
```

### IDE Helper Integration

This package can read Laravel IDE Helper generated PHPDoc comments to improve the accuracy of property types in your model schemas. If you have [Laravel IDE Helper](https://github.com/barryvdh/laravel-ide-helper) installed and have generated model docs, the package will automatically read the `@property`, `@property-read`, and `@property-write` annotations to determine property types.

For example, if your model has:

```php
/**
 * @property int $id
 * @property string $name
 * @property-read \Carbon\Carbon $created_at
 * @property-write array $settings
 */
class User extends Model
{
    // ...model code
}
```

These types will be used to generate more accurate OpenAPI schemas compared to just using database column types.

### Enhanced Response Schemas

The package now properly formats response schemas according to OpenAPI 3.0 specifications. When using custom response schemas, you can use PHP 8 enums to define data types:

```php
use BellissimoPizza\SwaggerAttributes\Enums\OpenApiDataType;

#[ApiSwaggerResponse(
    statusCode: 200,
    schema: [
        'id' => OpenApiDataType::INTEGER,
        'name' => OpenApiDataType::STRING,
        'created_at' => OpenApiDataType::DATE_TIME,
        'is_active' => OpenApiDataType::BOOLEAN,
    ]
)]
```

The enum values are automatically converted to proper OpenAPI type and format objects in the schema.

The file extension will be automatically added if not specified in the output path. Old documentation files are automatically deleted before generating new ones to ensure clean output.

### Additional Improvements

#### HTTP Method Normalization

The package now automatically normalizes HTTP methods to lowercase in the OpenAPI paths to ensure compatibility with all OpenAPI consumers and prevent duplicate endpoints with different method casing.

#### Auto-cleaning of Documentation Files

When generating new documentation, any existing Swagger JSON/YAML files are automatically deleted first. This ensures you always have a clean, up-to-date documentation file without artifacts from previous generations.

#### Direct File Access

The package now reads documentation files directly from storage instead of requiring them to be publicly accessible via URLs. This improves security by eliminating the need for symbolic links between storage and public directories, and simplifies deployment across different environments.

You can view your API documentation at the route you specified in the configuration file.

#### Validation Error Responses

The package now automatically adds a standard validation error response (HTTP 422) to endpoints that use validation rules through either:

1. Form Request classes via `ApiSwaggerRequestBody`
2. Inline validation rules in `ApiSwaggerRequestBody`

This validation error schema matches Laravel's default validation error format:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "field_name": [
      "The field_name field is required."
    ],
    "email": [
      "The email must be a valid email address."
    ]
  }
}
```

You can also manually add a validation error response to any endpoint by using the `ApiSwaggerValidationErrorResponse` attribute:

```php
#[ApiSwagger(
    method: HttpMethod::POST,
    path: '/api/users',
    tag: 'Users',
    summary: 'Create a new user'
)]
#[ApiSwaggerRequestBody(
    requestClass: StoreUserRequest::class
)]
#[ApiSwaggerResponse(
    statusCode: HttpStatusCode::CREATED,
    model: User::class
)]
#[ApiSwaggerValidationErrorResponse(
    description: 'Validation failed for the submitted form data'
)]
public function store(StoreUserRequest $request)
{
    // ...
}
```

The ValidationError schema is automatically registered in the OpenAPI components section and can be reused across your API documentation.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
# laravel-swagger-attributes
