<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use OpenApi\Attributes as OA;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Laravel Application Monitoring API",
 *     description="API for monitoring applications and managing incidents, notifications and subscriptions",
 *     @OA\Contact(
 *         email="support@example.com"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 * 
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="API Server"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 * 
 * @OA\Tag(
 *     name="System",
 *     description="System status and health checks"
 * )
 * 
 * @OA\Tag(
 *     name="Applications",
 *     description="Operations related to application management"
 * )
 * 
 * @OA\Tag(
 *     name="Incidents",
 *     description="Operations related to incident management"
 * )
 * 
 * @OA\Tag(
 *     name="Subscriptions",
 *     description="Operations related to notification subscriptions"
 * )
 * 
 * @OA\Tag(
 *     name="Application Groups",
 *     description="Operations related to application group management"
 * )
 * 
 * @OA\Tag(
 *     name="Notifications",
 *     description="Operations related to notification settings"
 * )
 */
class BaseApiController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/status",
     *     summary="API Status",
     *     description="Get the API status and version information",
     *     tags={"System"},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="API is running"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="version", type="string", example="1.0.0"),
     *                 @OA\Property(property="status", type="string", example="healthy"),
     *                 @OA\Property(property="timestamp", type="string", format="date-time")
     *             )
     *         )
     *     )
     * )
     */
    public function status()
    {
        return $this->successResponse([
            'version' => '1.0.0',
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
        ], 'API is running');
    }

    /**
     * Standard success response format
     */
    protected function successResponse($data = null, string $message = 'Success', int $status = 200): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    /**
     * Standard error response format
     */
    protected function errorResponse(string $message = 'Error', int $status = 400, $errors = null): \Illuminate\Http\JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $status);
    }

    /**
     * Paginated response format
     */
    protected function paginatedResponse($paginator, string $message = 'Success'): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }
}
