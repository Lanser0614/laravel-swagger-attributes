<?php

require_once __DIR__ . '/vendor/autoload.php';

use BellissimoPizza\SwaggerAttributes\Services\ResourceSchemaExtractor;
use BellissimoPizza\SwaggerAttributes\Services\OpenApiGenerator;
use Illuminate\Routing\Router;

// Mock resource classes for testing
class TestResource extends \Illuminate\Http\Resources\Json\JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'settings' => [
                'notifications' => $this->settings['notifications'] ?? true,
                'theme' => $this->settings['theme'] ?? 'default'
            ],
            'children' => $this->whenLoaded('children'),
            'parent' => $this->whenLoaded('parent'),
            'counts' => [
                'likes' => 150,
                'views' => 1000.5
            ]
        ];
    }
}

class TestCollection extends \Illuminate\Http\Resources\Json\ResourceCollection
{
    public $collects = TestResource::class;
    
    public function toArray($request)
    {
        return [
            'data' => $this->collection,
            'meta' => [
                'total' => $this->collection->count(),
                'per_page' => 15
            ]
        ];
    }
}

// Initialize the extractor
$router = new Router(new \Illuminate\Events\Dispatcher());
$generator = new OpenApiGenerator($router);
$extractor = new ResourceSchemaExtractor();

// Test single resource schema generation
echo "Testing single resource schema extraction:\n";
$resourceSchema = $extractor->generateResourceSchema(TestResource::class, null, $generator);
echo json_encode($resourceSchema, JSON_PRETTY_PRINT) . "\n\n";

// Test collection resource schema generation
echo "Testing collection resource schema extraction:\n";
$collectionSchema = $extractor->generateResourceSchema(TestCollection::class, null, $generator);
echo json_encode($collectionSchema, JSON_PRETTY_PRINT) . "\n";
