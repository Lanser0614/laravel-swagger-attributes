<?php

namespace BellissimoPizza\SwaggerAttributes\Tests\Unit\Enums;

use BellissimoPizza\SwaggerAttributes\Enums\HttpStatusCode;
use PHPUnit\Framework\TestCase;

class HttpStatusCodeTest extends TestCase
{
    public function testHttpStatusCodeValues()
    {
        $this->assertEquals(200, HttpStatusCode::OK->value);
        $this->assertEquals(201, HttpStatusCode::CREATED->value);
        $this->assertEquals(404, HttpStatusCode::NOT_FOUND->value);
        $this->assertEquals(500, HttpStatusCode::INTERNAL_SERVER_ERROR->value);
    }

    public function testHttpStatusCodeDescription()
    {
        $this->assertEquals('OK', HttpStatusCode::OK->description());
        $this->assertEquals('Created', HttpStatusCode::CREATED->description());
        $this->assertEquals('Not Found', HttpStatusCode::NOT_FOUND->description());
    }

    public function testCanBeUsedAsInteger()
    {
        $statusCode = HttpStatusCode::OK;
        $this->assertEquals(200, $statusCode->value);
        
        $intValue = (int) $statusCode->value;
        $this->assertIsInt($intValue);
        $this->assertEquals(200, $intValue);
    }

    public function testCanBeConvertedToString()
    {
        $statusCode = HttpStatusCode::OK;
        $stringValue = (string) $statusCode->value;
        
        $this->assertIsString($stringValue);
        $this->assertEquals('200', $stringValue);
    }
}
