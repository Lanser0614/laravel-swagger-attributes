<?php

namespace BellissimoPizza\SwaggerAttributes\Attributes;

use Attribute;
use BellissimoPizza\SwaggerAttributes\Enums\HttpStatusCode;
use Exception;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class OpenApiException
{
    /**
     * @param HttpStatusCode $statusCode HTTP status code for the exception
     * @param string $message Exception message
     * @param class-string<Exception>|null $exception Exception class name
     * @param array $responseSchema Optional custom schema for the error response
     */
    public function __construct(
        public HttpStatusCode $statusCode,
        public string $message,
        public ?string $exception = null,
        public array $responseSchema = []
    ) {
    }
}
