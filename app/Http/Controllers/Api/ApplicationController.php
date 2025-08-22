<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreApplicationRequest;
use App\Http\Requests\UpdateApplicationRequest;
use App\Http\Resources\ApplicationResource;
use App\Http\Resources\SubscriptionResource;
use App\Http\Traits\HasApiResponses;
use App\Models\Application;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @group Applications
 * 
 * APIs for managing applications including CRUD operations, health checks, and monitoring status.
 */
class ApplicationController extends Controller
{
    use AuthorizesRequests, HasApiResponses;
    /**
     * List applications
     * 
     * Retrieve a paginated list of applications for the authenticated user.
     * 
     * @queryParam group_id integer optional Filter by application group ID. Example: 1
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Applications retrieved successfully",
     *   "data": {
     *     "data": [
     *       {
     *         "id": 1,
     *         "name": "My Application",
     *         "description": "Application description",
     *         "url": "https://example.com",
     *         "monitoring_enabled": true,
     *         "application_group": {
     *           "id": 1,
     *           "name": "Production Apps"
     *         },
     *         "incidents_count": 2,
     *         "subscriptions_count": 5
     *       }
     *     ],
     *     "current_page": 1,
     *     "per_page": 15,
     *     "total": 1
     *   }
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $query = Application::where('user_id', Auth::id())
            ->with(['incidents', 'applicationGroup', 'subscriptions']);

        // Optional filtering
        if ($request->has('group_id')) {
            $query->where('application_group_id', $request->group_id);
        }

        $applications = $query->latest()->paginate(15);

        return $this->paginatedResponse(
            ApplicationResource::collection($applications),
            'Applications retrieved successfully'
        );
    }

    /**
     * Create application
     * 
     * Create a new application for monitoring.
     * 
     * @bodyParam name string required The application name. Example: My App
     * @bodyParam description string optional The application description. Example: Production API
     * @bodyParam url string required The application URL to monitor. Example: https://example.com
     * @bodyParam monitoring_enabled boolean optional Enable monitoring for this application. Example: true
     * @bodyParam application_group_id integer optional Associate with an application group. Example: 1
     * 
     * @response 201 {
     *   "success": true,
     *   "message": "Application created successfully",
     *   "data": {
     *     "id": 1,
     *     "name": "My App",
     *     "description": "Production API",
     *     "url": "https://example.com",
     *     "monitoring_enabled": true,
     *     "application_group": null,
     *     "incidents_count": 0,
     *     "subscriptions_count": 0
     *   }
     * }
     * 
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "name": ["The name field is required."]
     *   }
     * }
     */
    public function store(StoreApplicationRequest $request): JsonResponse
    {
        $application = Application::create($request->validated());
        $application->load(['incidents', 'applicationGroup', 'subscriptions']);

        return $this->createdResponse(
            new ApplicationResource($application),
            'Application created successfully'
        );
    }

    /**
     * Show application
     * 
     * Retrieve detailed information about a specific application.
     * 
     * @urlParam application integer required The application ID. Example: 1
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Application retrieved successfully",
     *   "data": {
     *     "id": 1,
     *     "name": "My Application",
     *     "description": "Production API",
     *     "url": "https://example.com",
     *     "monitoring_enabled": true,
     *     "application_group": {
     *       "id": 1,
     *       "name": "Production Apps"
     *     },
     *     "incidents": [],
     *     "subscriptions": []
     *   }
     * }
     * 
     * @response 403 {
     *   "message": "This action is unauthorized."
     * }
     * 
     * @response 404 {
     *   "message": "Application not found."
     * }
     */
    public function show(Application $application): JsonResponse
    {
        $this->authorize('view', $application);

        $application->load(['incidents', 'applicationGroup', 'subscriptions.user']);

        return $this->successResponse(
            new ApplicationResource($application),
            'Application retrieved successfully'
        );
    }

    /**
     * Update application
     * 
     * Update the specified application's information.
     * 
     * @urlParam application integer required The application ID. Example: 1
     * @bodyParam name string optional The application name. Example: Updated App
     * @bodyParam description string optional The application description. Example: Updated description
     * @bodyParam url string optional The application URL. Example: https://updated-example.com
     * @bodyParam monitoring_enabled boolean optional Enable/disable monitoring. Example: false
     * @bodyParam application_group_id integer optional Change application group. Example: 2
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Application updated successfully",
     *   "data": {
     *     "id": 1,
     *     "name": "Updated App",
     *     "description": "Updated description",
     *     "url": "https://updated-example.com",
     *     "monitoring_enabled": false
     *   }
     * }
     * 
     * @response 403 {
     *   "message": "This action is unauthorized."
     * }
     * 
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "url": ["The url field must be a valid URL."]
     *   }
     * }
     */
    public function update(UpdateApplicationRequest $request, Application $application): JsonResponse
    {
        $this->authorize('update', $application);

        $application->update($request->validated());
        $application->load(['incidents', 'applicationGroup', 'subscriptions']);

        return $this->successResponse(
            new ApplicationResource($application),
            'Application updated successfully'
        );
    }

    /**
     * Delete application
     * 
     * Delete the specified application. This action cannot be undone.
     * 
     * @urlParam application integer required The application ID. Example: 1
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Application deleted successfully",
     *   "data": null
     * }
     * 
     * @response 403 {
     *   "message": "This action is unauthorized."
     * }
     */
    public function destroy(Application $application): JsonResponse
    {
        $this->authorize('delete', $application);

        $application->delete();

        return $this->successResponse(null, 'Application deleted successfully');
    }

    /**
     * Get application subscribers
     * 
     * Retrieve all users subscribed to notifications for this application.
     * 
     * @urlParam application integer required The application ID. Example: 1
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Application subscribers retrieved successfully",
     *   "data": [
     *     {
     *       "id": 1,
     *       "application_id": 1,
     *       "user": {
     *         "id": 1,
     *         "name": "John Doe",
     *         "email": "john@example.com"
     *       },
     *       "notification_types": ["email", "slack"]
     *     }
     *   ]
     * }
     */
    public function subscribers(Application $application): JsonResponse
    {
        $this->authorize('view', $application);

        $subscriptions = $application->subscriptions()->with('user')->get();

        return $this->successResponse(
            SubscriptionResource::collection($subscriptions),
            'Application subscribers retrieved successfully'
        );
    }

    /**
     * Trigger health check
     * 
     * Manually trigger a health check for the specified application.
     * 
     * @urlParam application integer required The application ID. Example: 1
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Health check initiated",
     *   "data": {
     *     "id": 1,
     *     "name": "My Application",
     *     "status": "checking"
     *   }
     * }
     */
    public function healthCheck(Application $application): JsonResponse
    {
        $this->authorize('update', $application);

        // TODO: Implement health check logic
        // This would dispatch a job to check the application health
        
        return $this->successResponse(
            new ApplicationResource($application),
            'Health check initiated'
        );
    }

    /**
     * Get application status
     * 
     * Get the current monitoring status and recent incident information for an application.
     * 
     * @urlParam application integer required The application ID. Example: 1
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Application status retrieved successfully",
     *   "data": {
     *     "application": {
     *       "id": 1,
     *       "name": "My Application",
     *       "monitoring_enabled": true
     *     },
     *     "status": "operational",
     *     "recent_incidents": 2,
     *     "last_check": "2024-01-15T10:30:00Z"
     *   }
     * }
     */
    public function status(Application $application): JsonResponse
    {
        $this->authorize('view', $application);

        // TODO: Implement status checking logic
        $recentIncidents = $application->incidents()
            ->where('created_at', '>=', now()->subDays(7))
            ->latest()
            ->limit(5)
            ->get();

        return $this->successResponse([
            'application' => new ApplicationResource($application),
            'status' => 'operational', // TODO: Calculate based on recent incidents
            'recent_incidents' => $recentIncidents->count(),
            'last_check' => now(), // TODO: Get from monitoring system
        ], 'Application status retrieved successfully');
    }
}
