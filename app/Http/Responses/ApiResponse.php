<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ApiResponse
{
    /**
     * Return a successful response (200 OK).
     */
    public static function ok(
        mixed $data = null,
        string $message = 'Success',
        array $meta = []
    ): JsonResponse {
        return self::buildSuccessResponse($data, $message, 200, $meta);
    }

    /**
     * Return a success response (alias for ok).
     */
    public static function success(
        mixed $data = null,
        string $message = 'Success',
        array $meta = []
    ): JsonResponse {
        return self::ok($data, $message, $meta);
    }

    /**
     * Build a success response with the given parameters.
     */
    private static function buildSuccessResponse(
        mixed $data = null,
        string $message = 'Success',
        int $statusCode = 200,
        array $meta = []
    ): JsonResponse {
        $response = [
            'status' => 'success',
            'message' => $message,
        ];

        if ($data !== null) {
            if ($data instanceof ResourceCollection) {
                $response['data'] = $data->response()->getData(true)['data'];
                if (isset($data->response()->getData(true)['meta'])) {
                    $response['meta'] = array_merge($meta, $data->response()->getData(true)['meta']);
                }
                if (isset($data->response()->getData(true)['links'])) {
                    $response['links'] = $data->response()->getData(true)['links'];
                }
            } elseif ($data instanceof JsonResource) {
                $response['data'] = $data->toArray(request());
            } else {
                $response['data'] = $data;
            }
        }

        if (! empty($meta)) {
            $response['meta'] = array_merge($response['meta'] ?? [], $meta);
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return a paginated success response.
     */
    public static function paginated(
        ResourceCollection $collection,
        string $message = 'Success'
    ): JsonResponse {
        $data = $collection->response()->getData(true);

        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data['data'],
            'meta' => $data['meta'] ?? [],
            'links' => $data['links'] ?? [],
        ]);
    }

    /**
     * Return an error response.
     */
    public static function error(
        string $message = 'An error occurred',
        int $statusCode = 400,
        array $errors = [],
        mixed $data = null
    ): JsonResponse {
        $response = [
            'status' => 'error',
            'message' => $message,
        ];

        if (! empty($errors)) {
            $response['errors'] = $errors;
        }

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return a validation error response.
     */
    public static function validationError(
        array $errors,
        string $message = 'Validation failed'
    ): JsonResponse {
        return self::error($message, 422, $errors);
    }

    /**
     * Return an unauthorized response.
     */
    public static function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return self::error($message, 401);
    }

    /**
     * Return a forbidden response.
     */
    public static function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return self::error($message, 403);
    }

    /**
     * Return a not found response.
     */
    public static function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return self::error($message, 404);
    }

    /**
     * Return a server error response.
     */
    public static function serverError(string $message = 'Internal server error'): JsonResponse
    {
        return self::error($message, 500);
    }

    /**
     * Return a created response (201 Created).
     */
    public static function created(
        mixed $data = null,
        string $message = 'Resource created successfully'
    ): JsonResponse {
        return self::buildSuccessResponse($data, $message, 201);
    }

    /**
     * Return a no content response (204 No Content).
     */
    public static function noContent(string $message = 'Operation completed successfully'): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
        ], 204);
    }

    /**
     * Handle exceptions and return consistent API error responses.
     */
    public static function handleException(\Throwable $exception, $request): JsonResponse
    {
        // Handle specific Laravel exceptions
        if ($exception instanceof \Illuminate\Validation\ValidationException) {
            return self::validationError($exception->errors(), 'Validation failed');
        }

        if ($exception instanceof \Illuminate\Auth\AuthenticationException) {
            return self::unauthorized('Authentication required');
        }

        if ($exception instanceof \Illuminate\Auth\Access\AuthorizationException) {
            return self::forbidden('You do not have permission to perform this action');
        }

        if ($exception instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
            return self::notFound('The requested resource was not found');
        }

        if ($exception instanceof \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException) {
            return self::error('Method not allowed', 405);
        }

        if ($exception instanceof \Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException) {
            return self::error('Too many requests', 429);
        }

        // Handle database exceptions
        if ($exception instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
            return self::notFound('Resource not found');
        }

        if ($exception instanceof \Illuminate\Database\QueryException) {
            if (app()->environment('production')) {
                return self::serverError('Database error occurred');
            }

            return self::serverError('Database error: '.$exception->getMessage());
        }

        // Default server error for unhandled exceptions
        if (app()->environment('production')) {
            return self::serverError('An unexpected error occurred');
        }

        // In development, show the actual error
        return self::serverError($exception->getMessage());
    }
}
