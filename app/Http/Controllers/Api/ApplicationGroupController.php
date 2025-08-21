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

        // Check if the group has applications
        if ($applicationGroup->applications()->count() > 0) {
            return $this->errorResponse(
                'Cannot delete group that contains applications. Please move or delete the applications first.',
                400
            );
        }

        $applicationGroup->delete();

        return $this->noContentResponse('Application group deleted successfully');
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
}
