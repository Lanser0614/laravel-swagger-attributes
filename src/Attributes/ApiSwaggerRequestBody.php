<?php

namespace BellissimoPizza\SwaggerAttributes\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class ApiSwaggerRequestBody
{
    /**
     * @param string|null $requestClass Full class name of Laravel FormRequest (if any)
     * @param array $rules Manual validation rules (if no request class provided)
     * @param string $contentType Content type of the request body (default: application/json)
     * @param bool $required Whether the request body is required
     * @param string $description Description of the request body
     */
    public function __construct(
        public ?string $requestClass = null,
        public array $rules = [],
        public string $contentType = 'application/json',
        public bool $required = true,
        public string $description = ''
    ) {
    }
}
