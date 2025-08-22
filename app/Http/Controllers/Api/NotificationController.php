<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateNotificationSettingsRequest;
use App\Http\Resources\UserResource;
use App\Http\Traits\HasApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Notification Management
 * 
 * APIs for managing user notification settings and testing notification channels.
 */
class NotificationController extends Controller
{
    use HasApiResponses;

    /**
     * Get notification settings
     * 
     * Retrieve the current user's notification settings including configured webhook URLs 
     * and notification preferences.
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Notification settings retrieved successfully",
     *   "data": {
     *     "email_notifications": true,
     *     "slack_webhook_url": "https://hooks.slack.com/services/...",
     *     "teams_webhook_url": "https://your-tenant.webhook.office.com/...",
     *     "discord_webhook_url": "https://discord.com/api/webhooks/...",
     *     "default_notification_channels": ["email"]
     *   }
     * }
     */
    public function settings(Request $request): JsonResponse
    {
        $user = $request->user();

        $settings = [
            'email_notifications' => !empty($user->notification_email),
            'slack_webhook_url' => $user->slack_webhook_url,
            'teams_webhook_url' => $user->teams_webhook_url,
            'discord_webhook_url' => $user->discord_webhook_url,
            'default_notification_channels' => ['email'], // Default value for now
        ];

        return $this->successResponse($settings, 'Notification settings retrieved successfully');
    }

    /**
     * Update notification settings
     * 
     * Update the current user's notification settings including webhook URLs 
     * and notification preferences.
     * 
     * @bodyParam email_notifications boolean optional Whether to enable email notifications. Example: true
     * @bodyParam notification_email string optional Email address for notifications. Example: user@example.com
     * @bodyParam slack_webhook_url string optional Slack webhook URL for notifications. Example: https://hooks.slack.com/services/...
     * @bodyParam teams_webhook_url string optional Microsoft Teams webhook URL. Example: https://your-tenant.webhook.office.com/...
     * @bodyParam discord_webhook_url string optional Discord webhook URL. Example: https://discord.com/api/webhooks/...
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Notification settings updated successfully",
     *   "data": {
     *     "id": 1,
     *     "name": "John Doe",
     *     "email": "user@example.com",
     *     "notification_email": "user@example.com",
     *     "slack_webhook_url": "https://hooks.slack.com/services/...",
     *     "teams_webhook_url": null,
     *     "discord_webhook_url": null
     *   }
     * }
     */
    public function updateSettings(UpdateNotificationSettingsRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();
        
        // Handle email_notifications boolean -> notification_email mapping
        if (isset($validated['email_notifications'])) {
            $validated['notification_email'] = $validated['email_notifications'] ? $user->email : null;
            unset($validated['email_notifications']);
        }
        
        // Store default_notification_channels if provided (for future use)
        if (isset($validated['default_notification_channels'])) {
            // For now, just remove it from the validated data since User model doesn't have this field yet
            unset($validated['default_notification_channels']);
        }
        
        $user->update($validated);
        $user->refresh();

        // Return the same structure as the settings method
        $settings = [
            'email_notifications' => !empty($user->notification_email),
            'slack_webhook_url' => $user->slack_webhook_url,
            'teams_webhook_url' => $user->teams_webhook_url,
            'discord_webhook_url' => $user->discord_webhook_url,
            'default_notification_channels' => ['email'], // Default value for now
        ];

        return $this->successResponse($settings, 'Notification settings updated successfully');
    }

    /**
     * Test notification channel
     * 
     * Send a test notification through the specified channel to verify configuration.
     * 
     * @urlParam type string required The notification channel to test. Must be one of: email, slack, teams, discord. Example: slack
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Test slack notification sent successfully",
     *   "data": {
     *     "success": true
     *   }
     * }
     * 
     * @response 400 {
     *   "success": false,
     *   "message": "Slack webhook URL not configured"
     * }
     */
    public function test(Request $request, string $type): JsonResponse
    {
        $request->merge(['type' => $type]);
        
        $request->validate([
            'type' => 'required|in:email,slack,teams,discord',
        ]);

        $user = $request->user();

        try {
            match ($type) {
                'email' => $this->testEmail($user),
                'slack' => $this->testSlack($user),
                'teams' => $this->testTeams($user),
                'discord' => $this->testDiscord($user),
            };

            return $this->successResponse(
                ['success' => true],
                "Test {$type} notification sent successfully"
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                400
            );
        }
    }

    /**
     * Send test email notification.
     */
    private function testEmail($user): void
    {
        if (!$user->notification_email) {
            throw new \Exception('Email notifications not configured');
        }

        // In a real application, you would send an actual email
        \Illuminate\Support\Facades\Log::info(
            "Test email notification sent to {$user->notification_email}"
        );
    }

    /**
     * Send test Slack notification.
     */
    private function testSlack($user): void
    {
        if (!$user->slack_webhook_url) {
            throw new \Exception('Slack webhook URL not configured');
        }

        $payload = [
            'text' => '🧪 Test notification from your Laravel Monitoring App',
        ];

        \Illuminate\Support\Facades\Http::post($user->slack_webhook_url, $payload);
    }

    /**
     * Send test Teams notification.
     */
    private function testTeams($user): void
    {
        if (!$user->teams_webhook_url) {
            throw new \Exception('Teams webhook URL not configured');
        }

        $payload = [
            '@type' => 'MessageCard',
            '@context' => 'http://schema.org/extensions',
            'summary' => 'Test notification from Laravel Monitoring App',
            'text' => '🧪 Test notification from your Laravel Monitoring App',
        ];

        \Illuminate\Support\Facades\Http::post($user->teams_webhook_url, $payload);
    }

    /**
     * Send test Discord notification.
     */
    private function testDiscord($user): void
    {
        if (!$user->discord_webhook_url) {
            throw new \Exception('Discord webhook URL not configured');
        }

        $payload = [
            'content' => '🧪 Test notification from your Laravel Monitoring App',
        ];

        \Illuminate\Support\Facades\Http::post($user->discord_webhook_url, $payload);
    }

    /**
     * Get notification history
     * 
     * Retrieve statistics and history of sent notifications for the current user.
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Notification history retrieved successfully",
     *   "data": {
     *     "total_sent": 0,
     *     "last_7_days": 0,
     *     "by_type": {
     *       "email": 0,
     *       "slack": 0,
     *       "teams": 0,
     *       "discord": 0
     *     },
     *     "recent_notifications": []
     *   }
     * }
     */
    public function history(Request $request): JsonResponse
    {
        // This would typically show a log of sent notifications
        // For now, we'll return a placeholder response
        
        $history = [
            'total_sent' => 0,
            'last_7_days' => 0,
            'by_type' => [
                'email' => 0,
                'slack' => 0,
                'teams' => 0,
                'discord' => 0,
            ],
            'recent_notifications' => [],
        ];

        return $this->successResponse($history, 'Notification history retrieved successfully');
    }
}
