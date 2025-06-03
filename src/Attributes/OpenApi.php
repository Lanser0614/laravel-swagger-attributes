<?php

namespace BellissimoPizza\SwaggerAttributes\Attributes;

use Attribute;
use BellissimoPizza\SwaggerAttributes\Enums\HttpMethod;

#[Attribute(Attribute::TARGET_METHOD)]
class OpenApi
{
    /**
     * @param string $tag Tag for grouping API endpoints
     * @param string $summary Short summary of the endpoint
     * @param string $description Long description of the endpoint
     * @param HttpMethod $method HTTP method (GET, POST, PUT, DELETE, etc.)
     * @param string|null $path Custom path (if different from the route path)
     * @param bool $deprecated Whether the endpoint is deprecated
     */
    public function __construct(
        public string $tag,
        public string $summary,
        public string $description = '',
        public HttpMethod $method = HttpMethod::GET,
        public ?string $path = null,
        public bool $deprecated = false
    ) {
    }
}
