<?php

namespace BellissimoPizza\SwaggerAttributes\Attributes;

use Attribute;
use BellissimoPizza\SwaggerAttributes\Enums\OpenApiDataType;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class OpenApiQueryParam
{
    /**
     * @param string $name Name of the query parameter
     * @param OpenApiDataType $type Data type of the parameter
     * @param string $description Description of the parameter
     * @param bool $required Whether the parameter is required
     * @param mixed $example Example value for the parameter
     * @param mixed $default Default value for the parameter
     * @param array $enum Possible values for enum type parameters
     * @param string|null $format Format of the parameter (date, date-time, email, etc.)
     * @param array $schema Additional schema properties for the parameter
     */
    public function __construct(
        public string $name,
        public OpenApiDataType $type = OpenApiDataType::STRING,
        public string $description = '',
        public bool $required = false,
        public mixed $example = null,
        public mixed $default = null,
        public array $enum = [],
        public ?string $format = null,
        public array $schema = []
    ) {
    }
}
