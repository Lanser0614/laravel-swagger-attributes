<?php

namespace BellissimoPizza\SwaggerAttributes\Enums;

enum OpenApiDataType: string
{
    case STRING = 'string';
    case NUMBER = 'number';
    case INTEGER = 'integer';
    case BOOLEAN = 'boolean';
    case ARRAY = 'array';
    case OBJECT = 'object';
    
    /**
     * Get common formats for this data type
     *
     * @return array
     */
    public function availableFormats(): array
    {
        return match($this) {
            self::STRING => [
                'date' => 'Date (RFC3339 full-date)',
                'date-time' => 'DateTime (RFC3339 date-time)',
                'password' => 'Password (obscured in Swagger UI)',
                'byte' => 'Base64-encoded characters',
                'binary' => 'Binary data',
                'email' => 'Email address',
                'uuid' => 'UUID string',
                'uri' => 'URI string',
                'hostname' => 'Hostname',
                'ipv4' => 'IPv4 address',
                'ipv6' => 'IPv6 address',
            ],
            self::NUMBER => [
                'float' => 'Floating point number',
                'double' => 'Double precision floating point number',
            ],
            self::INTEGER => [
                'int32' => '32-bit integer',
                'int64' => '64-bit integer',
            ],
            default => [],
        };
    }
    
    /**
     * Get default format for this type if applicable
     *
     * @return string|null
     */
    public function defaultFormat(): ?string
    {
        return match($this) {
            self::NUMBER => 'float',
            self::INTEGER => 'int32',
            default => null,
        };
    }
}
