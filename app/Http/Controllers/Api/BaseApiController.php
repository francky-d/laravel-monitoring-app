<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

/**
 * @group System Status
 * 
 * APIs for checking system status and health.
 */
class BaseApiController extends Controller
{
    /**
     * Get API status
     * 
     * Check if the API is running and get basic system information.
     * 
     * @unauthenticated
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "API is running",
     *   "data": {
     *     "version": "1.0.0",
     *     "status": "healthy",
     *     "timestamp": "2025-08-22T01:58:09.000000Z"
     *   }
     * }
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
