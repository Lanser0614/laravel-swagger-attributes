<?php

namespace BellissimoPizza\SwaggerAttributes\Enums;

enum HttpMethod: string
{
    case GET = 'GET';
    case POST = 'POST';
    case PUT = 'PUT';
    case PATCH = 'PATCH';
    case DELETE = 'DELETE';
    case HEAD = 'HEAD';
    case OPTIONS = 'OPTIONS';
    case TRACE = 'TRACE';
    
    /**
     * Check if this method typically has a request body
     *
     * @return bool
     */
    public function hasRequestBody(): bool
    {
        return match($this) {
            self::POST, self::PUT, self::PATCH => true,
            default => false,
        };
    }
    
    /**
     * Get common description for this method
     *
     * @return string
     */
    public function description(): string
    {
        return match($this) {
            self::GET => 'Retrieve a resource',
            self::POST => 'Create a new resource',
            self::PUT => 'Replace a resource',
            self::PATCH => 'Partially update a resource',
            self::DELETE => 'Delete a resource',
            self::HEAD => 'Same as GET but without response body',
            self::OPTIONS => 'Get supported methods and options',
            self::TRACE => 'Perform a message loop-back test',
        };
    }
    
    /**
     * Check if the method is idempotent
     * 
     * @return bool
     */
    public function isIdempotent(): bool
    {
        return match($this) {
            self::GET, self::PUT, self::DELETE, self::HEAD, self::OPTIONS, self::TRACE => true,
            self::POST, self::PATCH => false,
        };
    }
}
