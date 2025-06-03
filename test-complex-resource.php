<?php

require_once __DIR__ . '/vendor/autoload.php';

use BellissimoPizza\SwaggerAttributes\Services\ResourceSchemaExtractor;
use BellissimoPizza\SwaggerAttributes\Services\OpenApiGenerator;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Routing\Router;

// Create a mock Customer class with constants
class Customer {
    const MIN_POINT_FOR_USAGE_FOR_ORDER = 100;
    const MAX_PERCENTAGE_POINT_USAGE_FOR_ORDER = 50;
    const CUSTOMER_MIN_AGE_TO_ACCEPT_LOYALTY_POLICY = 18;
}

// Mock complex resource with nested structure
class ComplexResource extends JsonResource
{
    // Create a basic constructor to avoid null resource issues
    public function __construct($resource = null)
    {
        parent::__construct($resource ?: new \stdClass());
    }
    
    public function toArray($request)
    {
        $points = [
            'spent' => 50,
            'confirmed' => 200,
            'total' => 250,
            'unconfirmed' => 0,
            'all' => ['item1', 'item2'],
            'expiringOrExpired' => ['expired1']
        ];
        
        return [
            'success' => true,
            'message' => 'Success',
            'data' => [
                'loyaltyInfo' => [
                    'isAccepted' => false,
                    'isRejected' => false,
                    'settings' => [
                        'minPointUsageForOrder' => Customer::MIN_POINT_FOR_USAGE_FOR_ORDER,
                        'maxPercentagePointUsageForOrder' => Customer::MAX_PERCENTAGE_POINT_USAGE_FOR_ORDER,
                        'minAgeToAcceptLoyalty' => Customer::CUSTOMER_MIN_AGE_TO_ACCEPT_LOYALTY_POLICY
                    ],
                    'points' => [
                        'spent' => $points['spent'] ?? 0,
                        'confirmed' => $points['confirmed'] ?? 0,
                        'total' => $points['total'] ?? 0,
                        'unconfirmed' => $points['unconfirmed'] ?? 0,
                        'history' => [
                            'all' => $points['all'] ?? [],
                            'expiringOrExpired' => $points['expiringOrExpired'] ?? [],
                        ],
                    ],
                ],
            ],
        ];
    }
}

// Initialize the extractor
$router = new Router(new \Illuminate\Events\Dispatcher());
$generator = new OpenApiGenerator($router);
$extractor = new ResourceSchemaExtractor();

// Test complex resource schema extraction
echo "Testing complex nested resource schema extraction:\n";
$resourceSchema = $extractor->generateResourceSchema(ComplexResource::class, null, $generator);

// Pretty print the schema
echo json_encode($resourceSchema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
