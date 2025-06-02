<?php

namespace BellissimoPizza\SwaggerAttributes\Tests\Unit\Enums;

use BellissimoPizza\SwaggerAttributes\Enums\ResponseType;
use PHPUnit\Framework\TestCase;

class ResponseTypeTest extends TestCase
{
    public function testResponseTypeValues()
    {
        $this->assertEquals('single', ResponseType::SINGLE->value);
        $this->assertEquals('collection', ResponseType::COLLECTION->value);
        $this->assertEquals('paginated', ResponseType::PAGINATED->value);
    }

    public function testGetStructureForSingleType()
    {
        $structure = ResponseType::SINGLE->getStructure();
        
        // Single response type doesn't modify the schema structure
        $this->assertIsArray($structure);
        $this->assertEmpty($structure);
    }

    public function testGetStructureForCollectionType()
    {
        $structure = ResponseType::COLLECTION->getStructure();
        
        $this->assertIsArray($structure);
        $this->assertEquals('array', $structure['type']);
        $this->assertArrayHasKey('items', $structure);
    }

    public function testGetStructureForPaginatedType()
    {
        $structure = ResponseType::PAGINATED->getStructure();
        
        $this->assertIsArray($structure);
        $this->assertEquals('object', $structure['type']);
        $this->assertArrayHasKey('properties', $structure);
        
        // Check pagination properties
        $properties = $structure['properties'];
        $this->assertArrayHasKey('data', $properties);
        $this->assertArrayHasKey('meta', $properties);
        $this->assertArrayHasKey('links', $properties);
        
        // Verify data is an array
        $this->assertEquals('array', $properties['data']['type']);
        $this->assertArrayHasKey('items', $properties['data']);
        
        // Verify pagination metadata
        $meta = $properties['meta']['properties'];
        $this->assertArrayHasKey('current_page', $meta);
        $this->assertArrayHasKey('last_page', $meta);
        $this->assertArrayHasKey('per_page', $meta);
        $this->assertArrayHasKey('total', $meta);
    }
}
