<?php

namespace BellissimoPizza\SwaggerAttributes\Enums;

enum ResponseType
{
    case SINGLE;
    case COLLECTION;
    case PAGINATED;
    
    /**
     * Get response structure for this response type
     *
     * @return array
     */
    public function getStructure(): array
    {
        return match($this) {
            self::SINGLE => [
                'type' => 'object',
            ],
            self::COLLECTION => [
                'type' => 'array',
                'items' => [],
            ],
            self::PAGINATED => [
                'type' => 'object',
                'properties' => [
                    'data' => [
                        'type' => 'array',
                        'items' => [],
                    ],
                    'meta' => [
                        'type' => 'object',
                        'properties' => [
                            'current_page' => ['type' => 'integer'],
                            'from' => ['type' => 'integer'],
                            'last_page' => ['type' => 'integer'],
                            'path' => ['type' => 'string'],
                            'per_page' => ['type' => 'integer'],
                            'to' => ['type' => 'integer'],
                            'total' => ['type' => 'integer'],
                        ],
                    ],
                    'links' => [
                        'type' => 'object',
                        'properties' => [
                            'first' => ['type' => 'string'],
                            'last' => ['type' => 'string'],
                            'prev' => ['type' => 'string', 'nullable' => true],
                            'next' => ['type' => 'string', 'nullable' => true],
                        ],
                    ],
                ],
            ],
        };
    }
}
