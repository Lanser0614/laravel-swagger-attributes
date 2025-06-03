<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Models\User;
use BellissimoPizza\SwaggerAttributes\Attributes\OpenApi;
use BellissimoPizza\SwaggerAttributes\Attributes\OpenApiRequestBody;
use BellissimoPizza\SwaggerAttributes\Attributes\OpenApiException;
use BellissimoPizza\SwaggerAttributes\Attributes\OpenApiResponse;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    /**
     * Create a new user
     */
    #[OpenApi(
        tag: 'Users',
        summary: 'Create new user',
        description: 'Creates a new user with the provided data',
        method: 'POST'
    )]
    #[OpenApiRequestBody(
        requestClass: StoreUserRequest::class,
        description: 'User data for creating a new account'
    )]
    #[OpenApiResponse(
        statusCode: 201,
        description: 'User created successfully',
        model: User::class
    )]
    #[OpenApiException(
        statusCode: 422,
        message: 'Validation failed'
    )]
    #[OpenApiException(
        statusCode: 500,
        message: 'Server error'
    )]
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = User::create($request->validated());
        
        return response()->json([
            'message' => 'User created successfully',
            'data' => $user
        ], 201);
    }

    /**
     * Get user details
     */
    #[OpenApi(
        tag: 'Users',
        summary: 'Get user details',
        description: 'Retrieves detailed information about a specific user',
        method: 'GET'
    )]
    #[OpenApiResponse(
        statusCode: 200,
        description: 'User details retrieved successfully',
        model: User::class
    )]
    #[OpenApiException(
        statusCode: 404,
        message: 'User not found'
    )]
    public function show(int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        
        return response()->json([
            'data' => $user
        ]);
    }
    
    /**
     * Get a list of users
     */
    #[OpenApi(
        tag: 'Users',
        summary: 'List all users',
        description: 'Retrieves a paginated list of all users',
        method: 'GET'
    )]
    #[OpenApiResponse(
        statusCode: 200,
        description: 'Users retrieved successfully',
        model: User::class,
        isCollection: true,
        isPaginated: true
    )]
    public function index(): JsonResponse
    {
        $users = User::paginate(15);
        
        return response()->json($users);
    }
    
    /**
     * Get user statistics
     */
    #[OpenApi(
        tag: 'Users',
        summary: 'Get user statistics',
        description: 'Retrieves aggregated statistics about users',
        method: 'GET'
    )]
    #[OpenApiResponse(
        statusCode: 200,
        description: 'Statistics retrieved successfully',
        schema: [
            'type' => 'object',
            'properties' => [
                'total_users' => ['type' => 'integer'],
                'active_users' => ['type' => 'integer'],
                'average_age' => ['type' => 'number', 'format' => 'float'],
                'registration_by_month' => [
                    'type' => 'object',
                    'additionalProperties' => ['type' => 'integer']
                ]
            ]
        ]
    )]
    public function statistics(): JsonResponse
    {
        // Calculate statistics...
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('active', true)->count(),
            'average_age' => User::avg('age'),
            'registration_by_month' => []
        ];
        
        return response()->json($stats);
    }
}
