<?php

namespace App\Http\Traits;

use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

trait HasApiResponses
{
    /**
     * Return a success response.
     */
    protected function successResponse(
        mixed $data = null,
        string $message = 'Success',
        int $statusCode = 200,
        array $meta = []
    ): JsonResponse {
        return ApiResponse::success($data, $message, $statusCode, $meta);
    }

    /**
     * Return a paginated success response.
     */
    protected function paginatedResponse(
        ResourceCollection $collection,
        string $message = 'Success'
    ): JsonResponse {
        return ApiResponse::paginated($collection, $message);
    }

    /**
     * Return an error response.
     */
    protected function errorResponse(
        string $message = 'An error occurred',
        int $statusCode = 400,
        array $errors = [],
        mixed $data = null
    ): JsonResponse {
        return ApiResponse::error($message, $statusCode, $errors, $data);
    }

    /**
     * Return a validation error response.
     */
    protected function validationErrorResponse(
        array $errors,
        string $message = 'Validation failed'
    ): JsonResponse {
        return ApiResponse::validationError($errors, $message);
    }

    /**
     * Return an unauthorized response.
     */
    protected function unauthorizedResponse(string $message = 'Unauthorized'): JsonResponse
    {
        return ApiResponse::unauthorized($message);
    }

    /**
     * Return a forbidden response.
     */
    protected function forbiddenResponse(string $message = 'Forbidden'): JsonResponse
    {
        return ApiResponse::forbidden($message);
    }

    /**
     * Return a not found response.
     */
    protected function notFoundResponse(string $message = 'Resource not found'): JsonResponse
    {
        return ApiResponse::notFound($message);
    }

    /**
     * Return a server error response.
     */
    protected function serverErrorResponse(string $message = 'Internal server error'): JsonResponse
    {
        return ApiResponse::serverError($message);
    }

    /**
     * Return a created response.
     */
    protected function createdResponse(
        mixed $data = null,
        string $message = 'Resource created successfully'
    ): JsonResponse {
        return ApiResponse::created($data, $message);
    }

    /**
     * Return a no content response.
     */
    protected function noContentResponse(string $message = 'Operation completed successfully'): JsonResponse
    {
        return ApiResponse::noContent($message);
    }
}
