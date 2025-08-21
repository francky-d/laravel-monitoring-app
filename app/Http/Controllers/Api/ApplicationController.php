<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreApplicationRequest;
use App\Http\Requests\UpdateApplicationRequest;
use App\Http\Resources\ApplicationResource;
use App\Http\Resources\SubscriptionResource;
use App\Models\Application;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApplicationController extends Controller
{
    use AuthorizesRequests;
    /**
     * Display a listing of the resource.
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

        return response()->json([
            'data' => ApplicationResource::collection($applications->items()),
            'meta' => [
                'current_page' => $applications->currentPage(),
                'per_page' => $applications->perPage(),
                'total' => $applications->total(),
                'last_page' => $applications->lastPage(),
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreApplicationRequest $request): JsonResponse
    {
        $application = Application::create($request->validated());
        $application->load(['incidents', 'applicationGroup', 'subscriptions']);

        return response()->json([
            'message' => 'Application created successfully',
            'data' => new ApplicationResource($application),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Application $application): JsonResponse
    {
        $this->authorize('view', $application);

        $application->load(['incidents', 'applicationGroup', 'subscriptions.user']);

        return response()->json([
            'data' => new ApplicationResource($application),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateApplicationRequest $request, Application $application): JsonResponse
    {
        $this->authorize('update', $application);

        $application->update($request->validated());
        $application->load(['incidents', 'applicationGroup', 'subscriptions']);

        return response()->json([
            'message' => 'Application updated successfully',
            'data' => new ApplicationResource($application),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Application $application): JsonResponse
    {
        $this->authorize('delete', $application);

        $application->delete();

        return response()->json([
            'message' => 'Application deleted successfully',
        ]);
    }

    /**
     * Get application subscribers.
     */
    public function subscribers(Application $application): JsonResponse
    {
        $this->authorize('view', $application);

        $subscriptions = $application->subscriptions()->with('user')->get();

        return response()->json([
            'data' => SubscriptionResource::collection($subscriptions),
        ]);
    }

    /**
     * Manual health check for an application.
     */
    public function healthCheck(Application $application): JsonResponse
    {
        $this->authorize('update', $application);

        // TODO: Implement health check logic
        // This would dispatch a job to check the application health
        
        return response()->json([
            'message' => 'Health check initiated',
            'application' => new ApplicationResource($application),
        ]);
    }

    /**
     * Get current monitoring status.
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

        return response()->json([
            'application' => new ApplicationResource($application),
            'status' => 'operational', // TODO: Calculate based on recent incidents
            'recent_incidents' => $recentIncidents->count(),
            'last_check' => now(), // TODO: Get from monitoring system
        ]);
    }
}
