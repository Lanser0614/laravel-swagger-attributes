<?php

namespace BellissimoPizza\SwaggerAttributes\Tests\Unit\Attributes;

use BellissimoPizza\SwaggerAttributes\Attributes\ApiSwaggerQueryParam;
use BellissimoPizza\SwaggerAttributes\Enums\OpenApiDataType;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ApiSwaggerQueryParamTest extends TestCase
{
    public function testApiSwaggerQueryParamDefaults()
    {
        $queryParam = new ApiSwaggerQueryParam(name: 'filter');
        
        $this->assertEquals('filter', $queryParam->name);
        $this->assertEquals(OpenApiDataType::STRING, $queryParam->type);
        $this->assertEquals('', $queryParam->description);
        $this->assertFalse($queryParam->required);
        $this->assertNull($queryParam->example);
        $this->assertNull($queryParam->default);
        $this->assertEmpty($queryParam->enum);
        $this->assertNull($queryParam->format);
        $this->assertEmpty($queryParam->schema);
    }
    
    public function testApiSwaggerQueryParamCustomValues()
    {
        $queryParam = new ApiSwaggerQueryParam(
            name: 'status',
            type: OpenApiDataType::STRING,
            description: 'Filter by status',
            required: true,
            example: 'active',
            default: 'all',
            enum: ['active', 'inactive', 'all'],
            format: null,
            schema: ['nullable' => true]
        );
        
        $this->assertEquals('status', $queryParam->name);
        $this->assertEquals(OpenApiDataType::STRING, $queryParam->type);
        $this->assertEquals('Filter by status', $queryParam->description);
        $this->assertTrue($queryParam->required);
        $this->assertEquals('active', $queryParam->example);
        $this->assertEquals('all', $queryParam->default);
        $this->assertEquals(['active', 'inactive', 'all'], $queryParam->enum);
        $this->assertNull($queryParam->format);
        $this->assertEquals(['nullable' => true], $queryParam->schema);
    }
    
    public function testAttributeIsRepeatable()
    {
        $reflectionClass = new ReflectionClass(ApiSwaggerQueryParam::class);
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
        
        $this->assertTrue($found, 'ApiSwaggerQueryParam should be repeatable');
    }
}
