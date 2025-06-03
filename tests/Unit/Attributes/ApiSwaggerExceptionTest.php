<?php

namespace BellissimoPizza\SwaggerAttributes\Tests\Unit\Attributes;

use BellissimoPizza\SwaggerAttributes\Attributes\OpenApiException;
use BellissimoPizza\SwaggerAttributes\Enums\HttpStatusCode;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ApiSwaggerExceptionTest extends TestCase
{
    public function testApiSwaggerExceptionWithEnum()
    {
        $exception = new OpenApiException(
            statusCode: HttpStatusCode::NOT_FOUND,
            message: 'Resource not found'
        );
        
        $this->assertEquals(HttpStatusCode::NOT_FOUND, $exception->statusCode);
        $this->assertEquals(404, $exception->statusCode->value);
        $this->assertEquals('Resource not found', $exception->message);
        $this->assertNull($exception->exceptionClass);
        $this->assertEmpty($exception->responseSchema);
    }
    
    public function testApiSwaggerExceptionWithCustomSchema()
    {
        $customSchema = [
            'type' => 'object',
            'properties' => [
                'code' => ['type' => 'integer', 'example' => 404],
                'error' => ['type' => 'string', 'example' => 'Not Found'],
                'details' => ['type' => 'string', 'example' => 'The requested resource was not found']
            ]
        ];
        
        $exception = new OpenApiException(
            statusCode: HttpStatusCode::NOT_FOUND,
            message: 'Resource not found',
            exceptionClass: \Exception::class,
            responseSchema: $customSchema
        );
        
        $this->assertEquals(HttpStatusCode::NOT_FOUND, $exception->statusCode);
        $this->assertEquals('Resource not found', $exception->message);
        $this->assertEquals(\Exception::class, $exception->exceptionClass);
        $this->assertEquals($customSchema, $exception->responseSchema);
    }
    
    public function testAttributeIsRepeatable()
    {
        $reflectionClass = new ReflectionClass(OpenApiException::class);
        $attributes = $reflectionClass->getAttributes();
        
        $this->assertNotEmpty($attributes);
        
        $found = false;
        foreach ($attributes as $attribute) {
            if ($attribute->getName() === 'Attribute') {
                $instance = $attribute->newInstance();
                if ($instance->flags & \Attribute::IS_REPEATABLE) {
                    $found = true;
                    break;
                }
            }
        }
        
        $this->assertTrue($found, 'OpenApiException should be repeatable');
    }
    
    public function testHttpStatusCodeEnumCanBeUsedAsKey()
    {
        $exception = new OpenApiException(
            statusCode: HttpStatusCode::INTERNAL_SERVER_ERROR,
            message: 'Server error occurred'
        );
        
        // Test that we can convert the enum value to a string for array keys
        $responses = [];
        $statusCode = (string)$exception->statusCode->value;
        
        $responses[$statusCode] = [
            'description' => $exception->message
        ];
        
        $this->assertArrayHasKey('500', $responses);
        $this->assertEquals('Server error occurred', $responses['500']['description']);
    }
}
