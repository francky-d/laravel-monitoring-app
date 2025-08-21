<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateNotificationSettingsRequest;
use App\Http\Resources\UserResource;
use App\Http\Traits\HasApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use HasApiResponses;

    /**
     * Get user's notification settings.
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
     * Update user's notification settings.
     */
    public function updateSettings(UpdateNotificationSettingsRequest $request): JsonResponse
    {
        $user = $request->user();
        
        $user->update($request->validated());

        return $this->successResponse(
            new UserResource($user),
            'Notification settings updated successfully'
        );
    }

    /**
     * Test notification settings by sending a test message.
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
     * Get notification history (placeholder for future implementation).
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
