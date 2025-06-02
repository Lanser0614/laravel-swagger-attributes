<?php

namespace BellissimoPizza\SwaggerAttributes\Attributes;

use Attribute;
use BellissimoPizza\SwaggerAttributes\Enums\HttpStatusCode;
use BellissimoPizza\SwaggerAttributes\Enums\ResponseType;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class ApiSwaggerResponse
{
    /**
     * @param HttpStatusCode $statusCode HTTP status code for the response
     * @param string $description Description of the response
     * @param string|null $model Fully qualified class name of an Eloquent model (if applicable)
     * @param array $schema Custom schema for the response (if not using a model)
     * @param ResponseType|null $responseType Type of response (SINGLE, COLLECTION, PAGINATED)
     * @param string $contentType Content type of the response
     */
    public function __construct(
        public HttpStatusCode $statusCode = HttpStatusCode::OK,
        public string $description = 'Successful operation',
        public ?string $model = null,
        public array $schema = [],
        public ?ResponseType $responseType = ResponseType::SINGLE,
        public string $contentType = 'application/json'
    ) {
    }
}
