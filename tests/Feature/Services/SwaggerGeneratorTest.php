<?php

namespace BellissimoPizza\SwaggerAttributes\Tests\Feature\Services;

use BellissimoPizza\SwaggerAttributes\Attributes\OpenApi;
use BellissimoPizza\SwaggerAttributes\Attributes\OpenApiQueryParam;
use BellissimoPizza\SwaggerAttributes\Attributes\OpenApiResponse;
use BellissimoPizza\SwaggerAttributes\Enums\HttpMethod;
use BellissimoPizza\SwaggerAttributes\Enums\HttpStatusCode;
use BellissimoPizza\SwaggerAttributes\Enums\OpenApiDataType;
use BellissimoPizza\SwaggerAttributes\Enums\ResponseType;
use BellissimoPizza\SwaggerAttributes\Services\SwaggerGenerator;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Orchestra\Testbench\TestCase;
use ReflectionMethod;

class SwaggerGeneratorTest extends TestCase
{
    protected SwaggerGenerator $generator;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->generator = new SwaggerGenerator();
        
        // Setup basic configuration
        config([
            'swagger-attributes' => [
                'title' => 'Test API',
                'description' => 'API Documentation for tests',
                'version' => '1.0.0',
                'base_path' => '/api',
                'swagger_ui_path' => '/api/docs',
                'output_path' => storage_path('swagger/swagger.json'),
                'format' => 'json',
            ]
        ]);
    }
    
    protected function getPackageProviders($app)
    {
        return [
            'BellissimoPizza\SwaggerAttributes\Providers\SwaggerAttributesServiceProvider',
        ];
    }
    
    /**
     * Test the query parameters are properly added to the OpenAPI operation
     */
    public function testAddQueryParamsToOperation()
    {
        // Create a mock controller method with query param attributes
        $controller = new class {
            #[OpenApi(tag: 'Products', summary: 'List products')]
            #[OpenApiQueryParam(
                name: 'category',
                type: OpenApiDataType::STRING,
                description: 'Filter by category',
                example: 'electronics'
            )]
            #[OpenApiQueryParam(
                name: 'price',
                type: OpenApiDataType::NUMBER,
                description: 'Filter by price',
                required: true
            )]
            #[OpenApiResponse(
                statusCode: HttpStatusCode::OK,
                description: 'List of products'
            )]
            public function index(Request $request)
            {
                // Controller logic
            }
        };
        
        // Get reflection of the controller method
        $reflectionMethod = new ReflectionMethod($controller, 'index');
        
        // Define a route for the controller method
        $route = new Route(['GET'], '/api/products', [get_class($controller), 'index']);
        
        // Create a empty OpenAPI operation array
        $operation = [];
        
        // Use reflection to access the protected method
        $reflectionClass = new \ReflectionClass(SwaggerGenerator::class);
        $method = $reflectionClass->getMethod('addQueryParamsToOperation');
        $method->setAccessible(true);
        
        // Call the method with our test data
        $method->invoke($this->generator, $reflectionMethod, $operation);
        
        // Assert query parameters were added correctly
        $this->assertArrayHasKey('parameters', $operation);
        $this->assertCount(2, $operation['parameters']);
        
        // Verify first parameter (category)
        $categoryParam = $operation['parameters'][0];
        $this->assertEquals('category', $categoryParam['name']);
        $this->assertEquals('query', $categoryParam['in']);
        $this->assertEquals('Filter by category', $categoryParam['description']);
        $this->assertFalse($categoryParam['required']);
        $this->assertEquals('string', $categoryParam['schema']['type']);
        $this->assertEquals('electronics', $categoryParam['schema']['example']);
        
        // Verify second parameter (price)
        $priceParam = $operation['parameters'][1];
        $this->assertEquals('price', $priceParam['name']);
        $this->assertEquals('query', $priceParam['in']);
        $this->assertEquals('Filter by price', $priceParam['description']);
        $this->assertTrue($priceParam['required']);
        $this->assertEquals('number', $priceParam['schema']['type']);
    }
    
    /**
     * Test that HTTP enums are properly used in the generator
     */
    public function testHttpStatusCodeAndMethodEnumsIntegration()
    {
        // Create a mock controller method with response using HttpStatusCode enum
        $controller = new class {
            #[OpenApi(
                tag: 'Users',
                summary: 'Get user details', 
                method: HttpMethod::GET
            )]
            #[OpenApiResponse(
                statusCode: HttpStatusCode::OK,
                description: 'User details'
            )]
            #[OpenApiResponse(
                statusCode: HttpStatusCode::NOT_FOUND,
                description: 'User not found'
            )]
            public function show(int $id)
            {
                // Controller logic
            }
        };
        
        // Get reflection of the controller method
        $reflectionMethod = new ReflectionMethod($controller, 'show');
        
        // Define a route for the controller method
        $route = new Route(['GET'], '/api/users/{id}', [get_class($controller), 'show']);
        
        // Create a empty OpenAPI operation array
        $operation = [];
        
        // Use reflection to access the protected method
        $reflectionClass = new \ReflectionClass(SwaggerGenerator::class);
        $method = $reflectionClass->getMethod('addResponsesToOperation');
        $method->setAccessible(true);
        
        // Call the method with our test data
        $method->invoke($this->generator, $reflectionMethod, $operation);
        
        // Assert responses were added correctly
        $this->assertArrayHasKey('responses', $operation);
        
        // Verify 200 response
        $this->assertArrayHasKey('200', $operation['responses']);
        $this->assertEquals('User details', $operation['responses']['200']['description']);
        
        // Verify 404 response
        $this->assertArrayHasKey('404', $operation['responses']);
        $this->assertEquals('User not found', $operation['responses']['404']['description']);
    }
    
    /**
     * Test that the response type enum is properly used for schema generation
     */
    public function testResponseTypeEnumIntegration()
    {
        // Create a mock response with ResponseType enum
        $response = new OpenApiResponse(
            statusCode: HttpStatusCode::OK,
            description: 'List of items',
            responseType: ResponseType::COLLECTION
        );
        
        // Use reflection to access the protected method
        $reflectionClass = new \ReflectionClass(SwaggerGenerator::class);
        $method = $reflectionClass->getMethod('getResponseSchema');
        $method->setAccessible(true);
        
        // Call the method with our test data
        $schema = $method->invoke($this->generator, $response);
        
        // Assert schema structure matches collection type
        $this->assertEquals('array', $schema['type']);
        $this->assertArrayHasKey('items', $schema);
    }
}
