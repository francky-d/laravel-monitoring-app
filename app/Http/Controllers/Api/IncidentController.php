<?php

namespace App\Http\Controllers\Api;

use App\Enums\IncidentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreIncidentRequest;
use App\Http\Requests\UpdateIncidentRequest;
use App\Http\Resources\IncidentResource;
use App\Http\Traits\HasApiResponses;
use App\Jobs\NotifySubscribersJob;
use App\Models\Incident;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IncidentController extends Controller
{
    use HasApiResponses, AuthorizesRequests;

    /**
     * Display a listing of incidents.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Incident::with(['application', 'user'])
            ->whereHas('application', function ($q) use ($request) {
                $q->where('user_id', $request->user()->id);
            });

        // Filter by application
        if ($request->has('application_id')) {
            $query->where('application_id', $request->application_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', strtoupper($request->status));
        }

        // Filter by severity
        if ($request->has('severity')) {
            $query->where('severity', strtoupper($request->severity));
        }

        // Filter by date range
        if ($request->has('start_date')) {
            $query->where('started_at', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->where('started_at', '<=', $request->end_date);
        }

        // Sort by most recent first
        $query->orderBy('started_at', 'desc');

        $incidents = $query->paginate(15);

        return $this->paginatedResponse(
            IncidentResource::collection($incidents),
            'Incidents retrieved successfully'
        );
    }

    /**
     * Store a newly created incident.
     */
    public function store(StoreIncidentRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $incident = Incident::create($validated);

        $incident->load(['application', 'user']);

        // Dispatch notification job
        NotifySubscribersJob::dispatch($incident, 'created');

        return $this->createdResponse(
            new IncidentResource($incident),
            'Incident created successfully'
        );
    }

    /**
     * Display the specified incident.
     */
    public function show(Incident $incident): JsonResponse
    {
        $this->authorize('view', $incident);

        $incident->load(['application', 'user']);

        return $this->successResponse(
            new IncidentResource($incident),
            'Incident retrieved successfully'
        );
    }

    /**
     * Update the specified incident.
     */
    public function update(UpdateIncidentRequest $request, Incident $incident): JsonResponse
    {
        $this->authorize('update', $incident);

        $oldStatus = $incident->status;
        
        $incident->update($request->validated());

        // If status changed to resolved, set ended_at
        if ($oldStatus !== IncidentStatus::RESOLVED && $incident->status === IncidentStatus::RESOLVED) {
            $incident->update(['ended_at' => now()]);
            
            // Dispatch notification for resolution
            NotifySubscribersJob::dispatch($incident, 'resolved');
        }

        $incident->load(['application', 'user']);

        return $this->successResponse(
            new IncidentResource($incident),
            'Incident updated successfully'
        );
    }

    /**
     * Remove the specified incident.
     */
    public function destroy(Incident $incident): JsonResponse
    {
        $this->authorize('delete', $incident);

        $incident->delete();

        return $this->successResponse(null, 'Incident deleted successfully');
    }

    /**
     * Mark incident as resolved.
     */
    public function resolve(Incident $incident): JsonResponse
    {
        $this->authorize('update', $incident);

        if ($incident->status === IncidentStatus::RESOLVED) {
            return $this->errorResponse('Incident is already resolved', 400);
        }

        $incident->update([
            'status' => IncidentStatus::RESOLVED,
            'ended_at' => now(),
            'resolved_at' => now(),
        ]);

        $incident->load(['application', 'user']);

        // Dispatch notification for resolution
        NotifySubscribersJob::dispatch($incident, 'resolved');

        return $this->successResponse(
            new IncidentResource($incident),
            'Incident resolved successfully'
        );
    }

    /**
     * Reopen a resolved incident.
     */
    public function reopen(Incident $incident): JsonResponse
    {
        $this->authorize('update', $incident);

        if ($incident->status !== IncidentStatus::RESOLVED) {
            return $this->errorResponse('Only resolved incidents can be reopened', 400);
        }

        $incident->update([
            'status' => IncidentStatus::OPEN,
            'ended_at' => null,
            'resolved_at' => null,
        ]);

        $incident->load(['application', 'user']);

        // Dispatch notification for reopening
        NotifySubscribersJob::dispatch($incident, 'reopened');

        return $this->successResponse(
            new IncidentResource($incident),
            'Incident reopened successfully'
        );
    }

    /**
     * Get incident statistics.
     */
    public function stats(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $stats = [
            'total' => Incident::whereHas('application', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })->count(),
            
            'open' => Incident::whereHas('application', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })->where('status', IncidentStatus::OPEN)->count(),
            
            'resolved' => Incident::whereHas('application', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })->where('status', IncidentStatus::RESOLVED)->count(),
            
            'by_severity' => [
                'critical' => Incident::whereHas('application', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                })->where('severity', 'CRITICAL')->count(),
                
                'high' => Incident::whereHas('application', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                })->where('severity', 'HIGH')->count(),
                
                'low' => Incident::whereHas('application', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                })->where('severity', 'LOW')->count(),
            ],
            
            'by_application' => Incident::whereHas('application', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })->with('application:id,name')
                ->get()
                ->groupBy('application.name')
                ->map(function ($incidents) {
                    return $incidents->count();
                })
                ->toArray(),
        ];

        return $this->successResponse($stats, 'Incident statistics retrieved successfully');
    }
}
