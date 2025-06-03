<?php

namespace BellissimoPizza\SwaggerAttributes\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class OpenApiValidationErrorResponse
{
    /**
     * @param string $description Description of the validation error response
     */
    public function __construct(
        public string $description = 'Validation Error'
    ) {
    }
}
