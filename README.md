# Laravel Swagger Attributes

This package provides a clean way to generate Swagger/OpenAPI documentation for your Laravel API using PHP 8 Attributes instead of annotations.

## Features

- Use modern PHP 8 Attributes to document your API endpoints
- Automatically extract validation rules from Laravel Form Request classes
- Document API exceptions with status codes and error messages
- Command-line tool to scan routes and generate Swagger documentation
- Built-in Swagger UI for viewing documentation
- Fully customizable through configuration

## Requirements

- PHP 8.0 or higher
- Laravel 8.0 or higher

## Installation

You can install the package via composer:

```bash
composer require bellissimopizza/laravel-swagger-attributes
```

After installing, publish the configuration file:

```bash
php artisan vendor:publish --provider="BellissimoPizza\SwaggerAttributes\Providers\SwaggerAttributesServiceProvider" --tag="config"
```

## Usage

### Basic Configuration

First, configure the package in `config/swagger-attributes.php`:

```php
return [
    'title' => 'My API Documentation',
    'description' => 'Documentation for my awesome API',
    'version' => '1.0.0',
    // ...other configuration options
];
```

### Documenting API Endpoints

Use PHP attributes to document your API endpoints. Here's a simple example:

```php
use BellissimoPizza\SwaggerAttributes\Attributes\ApiSwagger;
use BellissimoPizza\SwaggerAttributes\Attributes\ApiSwaggerRequestBody;
use BellissimoPizza\SwaggerAttributes\Attributes\ApiSwaggerException;

class UserController extends Controller
{
    #[ApiSwagger(
        tag: 'Users',
        summary: 'Create new user',
        method: 'POST'
    )]
    #[ApiSwaggerRequestBody(
        requestClass: StoreUserRequest::class
    )]
    #[ApiSwaggerException(
        statusCode: 422,
        message: 'Validation failed'
    )]
    #[ApiSwaggerException(
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
    isCollection: false,                  // Whether it's a collection of models
    isPaginated: false,                   // Whether it's a paginated response
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

The file extension will be automatically added if not specified in the output path.

### Viewing Documentation

If you have enabled the Swagger UI in the configuration, you can view your API documentation at:

```
http://your-app.test/api/documentation
```

You can customize this URL in the configuration file.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
# laravel-swagger-attributes
