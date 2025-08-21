<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSubscriptionRequest;
use App\Http\Requests\UpdateSubscriptionRequest;
use App\Http\Resources\SubscriptionResource;
use App\Http\Traits\HasApiResponses;
use App\Models\Subscription;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    use HasApiResponses, AuthorizesRequests;

    /**
     * Display a listing of subscriptions.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Subscription::with(['application', 'user'])
            ->whereHas('application', function ($q) use ($request) {
                $q->where('user_id', $request->user()->id);
            });

        // Filter by application
        if ($request->has('application_id')) {
            $query->where('application_id', $request->application_id);
        }

        // Filter by notification type
        if ($request->has('notification_type')) {
            $query->where('notification_type', $request->notification_type);
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        $subscriptions = $query->paginate(15);

        return $this->paginatedResponse(
            SubscriptionResource::collection($subscriptions),
            'Subscriptions retrieved successfully'
        );
    }

    /**
     * Store a newly created subscription.
     */
    public function store(StoreSubscriptionRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['user_id'] = $request->user()->id;

        $subscription = Subscription::create($validated);

        $subscription->load(['application', 'user']);

        return $this->createdResponse(
            new SubscriptionResource($subscription),
            'Subscription created successfully'
        );
    }

    /**
     * Display the specified subscription.
     */
    public function show(Subscription $subscription): JsonResponse
    {
        $this->authorize('view', $subscription);

        $subscription->load(['application', 'user']);

        return $this->successResponse(
            new SubscriptionResource($subscription),
            'Subscription retrieved successfully'
        );
    }

    /**
     * Update the specified subscription.
     */
    public function update(UpdateSubscriptionRequest $request, Subscription $subscription): JsonResponse
    {
        $this->authorize('update', $subscription);

        $subscription->update($request->validated());

        $subscription->load(['application', 'user']);

        return $this->successResponse(
            new SubscriptionResource($subscription),
            'Subscription updated successfully'
        );
    }

    /**
     * Remove the specified subscription.
     */
    public function destroy(Subscription $subscription): JsonResponse
    {
        $this->authorize('delete', $subscription);

        $subscription->delete();

        return $this->noContentResponse('Subscription deleted successfully');
    }

    /**
     * Toggle subscription active status.
     */
    public function toggle(Subscription $subscription): JsonResponse
    {
        $this->authorize('update', $subscription);

        $subscription->update(['is_active' => !$subscription->is_active]);

        $subscription->load(['application', 'user']);

        $status = $subscription->is_active ? 'activated' : 'deactivated';

        return $this->successResponse(
            new SubscriptionResource($subscription),
            "Subscription {$status} successfully"
        );
    }

    /**
     * Test a subscription by sending a test notification.
     */
    public function test(Subscription $subscription): JsonResponse
    {
        $this->authorize('update', $subscription);

        if (!$subscription->is_active) {
            return $this->errorResponse('Cannot test inactive subscription', 400);
        }

        try {
            // Create a test incident for notification testing
            $testIncident = new \App\Models\Incident([
                'application_id' => $subscription->application_id,
                'title' => 'Test Notification',
                'description' => 'This is a test notification to verify your subscription settings.',
                'severity' => \App\Enums\IncidentSeverity::LOW,
                'status' => \App\Enums\IncidentStatus::OPEN,
                'started_at' => now(),
            ]);

            // Load the application relationship for the test incident
            $testIncident->setRelation('application', $subscription->application);

            // Send test notification
            $this->sendTestNotification($subscription, $testIncident);

            return $this->successResponse(
                null,
                'Test notification sent successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to send test notification: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Get subscription statistics for the authenticated user.
     */
    public function stats(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $stats = [
            'total' => Subscription::whereHas('application', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })->count(),
            
            'active' => Subscription::whereHas('application', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })->where('is_active', true)->count(),
            
            'inactive' => Subscription::whereHas('application', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })->where('is_active', false)->count(),
            
            'by_type' => [
                'email' => Subscription::whereHas('application', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                })->where('notification_type', 'email')->count(),
                
                'slack' => Subscription::whereHas('application', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                })->where('notification_type', 'slack')->count(),
                
                'teams' => Subscription::whereHas('application', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                })->where('notification_type', 'teams')->count(),
                
                'discord' => Subscription::whereHas('application', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                })->where('notification_type', 'discord')->count(),
            ],
        ];

        return $this->successResponse($stats, 'Subscription statistics retrieved successfully');
    }

    /**
     * Send a test notification for the subscription.
     */
    private function sendTestNotification(Subscription $subscription, \App\Models\Incident $testIncident): void
    {
        match ($subscription->notification_type) {
            'email' => $this->sendTestEmail($subscription, $testIncident),
            'slack' => $this->sendTestSlack($subscription, $testIncident),
            'teams' => $this->sendTestTeams($subscription, $testIncident),
            'discord' => $this->sendTestDiscord($subscription, $testIncident),
            default => throw new \Exception("Unknown notification type: {$subscription->notification_type}"),
        };
    }

    /**
     * Send test email notification.
     */
    private function sendTestEmail(Subscription $subscription, \App\Models\Incident $testIncident): void
    {
        if (!$subscription->email) {
            throw new \Exception('No email address configured for this subscription');
        }

        // In a real application, you would send an actual email
        \Illuminate\Support\Facades\Log::info(
            "Test email notification sent to {$subscription->email} for application {$testIncident->application->name}"
        );
    }

    /**
     * Send test Slack notification.
     */
    private function sendTestSlack(Subscription $subscription, \App\Models\Incident $testIncident): void
    {
        if (!$subscription->webhook_url) {
            throw new \Exception('No webhook URL configured for this subscription');
        }

        $payload = [
            'text' => "🧪 *Test Notification*: {$testIncident->application->name} - {$testIncident->title}",
            'attachments' => [
                [
                    'color' => '#0099FF',
                    'fields' => [
                        [
                            'title' => 'Application',
                            'value' => $testIncident->application->name,
                            'short' => true,
                        ],
                        [
                            'title' => 'Type',
                            'value' => 'Test Notification',
                            'short' => true,
                        ],
                    ],
                ],
            ],
        ];

        \Illuminate\Support\Facades\Http::post($subscription->webhook_url, $payload);
    }

    /**
     * Send test Teams notification.
     */
    private function sendTestTeams(Subscription $subscription, \App\Models\Incident $testIncident): void
    {
        if (!$subscription->webhook_url) {
            throw new \Exception('No webhook URL configured for this subscription');
        }

        $payload = [
            '@type' => 'MessageCard',
            '@context' => 'http://schema.org/extensions',
            'themeColor' => '0099FF',
            'summary' => "Test Notification: {$testIncident->application->name}",
            'sections' => [
                [
                    'activityTitle' => "🧪 Test Notification: {$testIncident->application->name}",
                    'facts' => [
                        [
                            'name' => 'Application',
                            'value' => $testIncident->application->name,
                        ],
                        [
                            'name' => 'Type',
                            'value' => 'Test Notification',
                        ],
                    ],
                ],
            ],
        ];

        \Illuminate\Support\Facades\Http::post($subscription->webhook_url, $payload);
    }

    /**
     * Send test Discord notification.
     */
    private function sendTestDiscord(Subscription $subscription, \App\Models\Incident $testIncident): void
    {
        if (!$subscription->webhook_url) {
            throw new \Exception('No webhook URL configured for this subscription');
        }

        $payload = [
            'content' => "🧪 **Test Notification**: {$testIncident->application->name} - {$testIncident->title}",
            'embeds' => [
                [
                    'title' => $testIncident->title,
                    'description' => $testIncident->description,
                    'color' => 0x0099FF,
                    'fields' => [
                        [
                            'name' => 'Application',
                            'value' => $testIncident->application->name,
                            'inline' => true,
                        ],
                        [
                            'name' => 'Type',
                            'value' => 'Test Notification',
                            'inline' => true,
                        ],
                    ],
                ],
            ],
        ];

        \Illuminate\Support\Facades\Http::post($subscription->webhook_url, $payload);
    }
}
