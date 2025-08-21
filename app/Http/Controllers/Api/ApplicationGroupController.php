<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreApplicationGroupRequest;
use App\Http\Requests\UpdateApplicationGroupRequest;
use App\Http\Resources\ApplicationGroupResource;
use App\Http\Traits\HasApiResponses;
use App\Models\ApplicationGroup;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApplicationGroupController extends Controller
{
    use HasApiResponses, AuthorizesRequests;

    /**
     * Display a listing of application groups.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ApplicationGroup::with(['applications'])
            ->where('user_id', $request->user()->id)
            ->orderBy('name');

        // Filter by name
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $groups = $query->paginate(15);

        return $this->paginatedResponse(
            ApplicationGroupResource::collection($groups),
            'Application groups retrieved successfully'
        );
    }

    /**
     * Store a newly created application group.
     */
    public function store(StoreApplicationGroupRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['user_id'] = $request->user()->id;

        $group = ApplicationGroup::create($validated);

        $group->load(['applications']);

        return $this->createdResponse(
            new ApplicationGroupResource($group),
            'Application group created successfully'
        );
    }

    /**
     * Display the specified application group.
     */
    public function show(ApplicationGroup $applicationGroup): JsonResponse
    {
        $this->authorize('view', $applicationGroup);

        $applicationGroup->load(['applications']);

        return $this->successResponse(
            new ApplicationGroupResource($applicationGroup),
            'Application group retrieved successfully'
        );
    }

    /**
     * Update the specified application group.
     */
    public function update(UpdateApplicationGroupRequest $request, ApplicationGroup $applicationGroup): JsonResponse
    {
        $this->authorize('update', $applicationGroup);

        $applicationGroup->update($request->validated());

        $applicationGroup->load(['applications']);

        return $this->successResponse(
            new ApplicationGroupResource($applicationGroup),
            'Application group updated successfully'
        );
    }

    /**
     * Remove the specified application group.
     */
    public function destroy(ApplicationGroup $applicationGroup): JsonResponse
    {
        $this->authorize('delete', $applicationGroup);

        // Remove applications from this group (set application_group_id to null)
        \App\Models\Application::where('application_group_id', $applicationGroup->id)
            ->update(['application_group_id' => null]);

        $applicationGroup->delete();

        return $this->successResponse(null, 'Application group deleted successfully');
    }

    /**
     * Add applications to a group.
     */
    public function addApplications(Request $request, ApplicationGroup $applicationGroup): JsonResponse
    {
        $this->authorize('update', $applicationGroup);

        $request->validate([
            'application_ids' => 'required|array',
            'application_ids.*' => 'string|exists:applications,id',
        ]);

        // Verify all applications belong to the user
        $applications = \App\Models\Application::whereIn('id', $request->application_ids)
            ->where('user_id', $request->user()->id)
            ->get();

        if ($applications->count() !== count($request->application_ids)) {
            return $this->errorResponse('Some applications do not exist or do not belong to you.', 400);
        }

        // Update applications to belong to this group
        \App\Models\Application::whereIn('id', $request->application_ids)
            ->update(['application_group_id' => $applicationGroup->id]);

        $applicationGroup->load(['applications']);

        return $this->successResponse(
            new ApplicationGroupResource($applicationGroup),
            'Applications added to group successfully'
        );
    }

    /**
     * Add a single application to a group.
     */
    public function addApplication(Request $request, ApplicationGroup $applicationGroup): JsonResponse
    {
        $this->authorize('update', $applicationGroup);

        $validated = $request->validate([
            'application_id' => [
                'required',
                'string',
                'exists:applications,id',
                function ($attribute, $value, $fail) use ($request) {
                    $application = \App\Models\Application::where('id', $value)
                        ->where('user_id', $request->user()->id)
                        ->first();
                    
                    if (!$application) {
                        $fail('The selected application does not belong to you.');
                    }
                },
            ],
        ]);

        $application = \App\Models\Application::find($validated['application_id']);

        // Update application to belong to this group
        $application->update(['application_group_id' => $applicationGroup->id]);

        $applicationGroup->load(['applications']);

        return $this->successResponse(
            new ApplicationGroupResource($applicationGroup),
            'Application added to group successfully'
        );
    }

    /**
     * Remove applications from a group.
     */
    public function removeApplications(Request $request, ApplicationGroup $applicationGroup): JsonResponse
    {
        $this->authorize('update', $applicationGroup);

        $request->validate([
            'application_ids' => 'required|array',
            'application_ids.*' => 'string|exists:applications,id',
        ]);

        // Update applications to remove from this group
        \App\Models\Application::whereIn('id', $request->application_ids)
            ->where('application_group_id', $applicationGroup->id)
            ->where('user_id', $request->user()->id)
            ->update(['application_group_id' => null]);

        $applicationGroup->load(['applications']);

        return $this->successResponse(
            new ApplicationGroupResource($applicationGroup),
            'Applications removed from group successfully'
        );
    }

    /**
     * Remove a single application from a group.
     */
    public function removeApplication(Request $request, ApplicationGroup $applicationGroup, \App\Models\Application $application): JsonResponse
    {
        $this->authorize('update', $applicationGroup);

        // Verify application belongs to the user and is in this group
        if ($application->user_id !== $request->user()->id) {
            return $this->errorResponse('Application does not belong to you.', 403);
        }

        if ($application->application_group_id !== $applicationGroup->id) {
            return $this->errorResponse('Application is not in this group.', 400);
        }

        // Remove application from group
        $application->update(['application_group_id' => null]);

        $applicationGroup->load(['applications']);

        return $this->successResponse(
            new ApplicationGroupResource($applicationGroup),
            'Application removed from group successfully'
        );
    }

    /**
     * Get statistics for application groups.
     */
    public function stats(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $stats = [
            'total_groups' => ApplicationGroup::where('user_id', $userId)->count(),
            'groups_with_applications' => ApplicationGroup::where('user_id', $userId)
                ->whereHas('applications')
                ->count(),
            'ungrouped_applications' => \App\Models\Application::where('user_id', $userId)
                ->whereNull('application_group_id')
                ->count(),
        ];

        return $this->successResponse($stats, 'Application group statistics retrieved successfully');
    }

    /**
     * Get subscribers for an application group.
     */
    public function subscribers(ApplicationGroup $applicationGroup): JsonResponse
    {
        $this->authorize('view', $applicationGroup);

        $subscribers = $applicationGroup->subscriptions()
            ->with(['user'])
            ->where('is_active', true)
            ->get();

        return $this->successResponse(
            \App\Http\Resources\SubscriptionResource::collection($subscribers),
            'Application group subscribers retrieved successfully'
        );
    }
}
