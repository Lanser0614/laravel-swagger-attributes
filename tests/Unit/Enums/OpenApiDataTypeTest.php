<?php

namespace BellissimoPizza\SwaggerAttributes\Tests\Unit\Enums;

use BellissimoPizza\SwaggerAttributes\Enums\OpenApiDataType;
use PHPUnit\Framework\TestCase;

class OpenApiDataTypeTest extends TestCase
{
    public function testOpenApiDataTypeValues()
    {
        $this->assertEquals('string', OpenApiDataType::STRING->value);
        $this->assertEquals('integer', OpenApiDataType::INTEGER->value);
        $this->assertEquals('boolean', OpenApiDataType::BOOLEAN->value);
        $this->assertEquals('array', OpenApiDataType::ARRAY->value);
        $this->assertEquals('object', OpenApiDataType::OBJECT->value);
    }

    public function testAvailableFormats()
    {
        $stringFormats = OpenApiDataType::STRING->availableFormats();
        $this->assertIsArray($stringFormats);
        $this->assertArrayHasKey('date', $stringFormats);
        $this->assertArrayHasKey('email', $stringFormats);
        $this->assertArrayHasKey('uuid', $stringFormats);
        
        $integerFormats = OpenApiDataType::INTEGER->availableFormats();
        $this->assertIsArray($integerFormats);
        $this->assertArrayHasKey('int32', $integerFormats);
        $this->assertArrayHasKey('int64', $integerFormats);
    }

    public function testDefaultFormat()
    {
        $this->assertNull(OpenApiDataType::STRING->defaultFormat());
        $this->assertEquals('float', OpenApiDataType::NUMBER->defaultFormat());
        $this->assertEquals('int32', OpenApiDataType::INTEGER->defaultFormat());
    }
}
