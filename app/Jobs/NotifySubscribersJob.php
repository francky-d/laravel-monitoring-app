<?php

namespace App\Jobs;

use App\Models\Incident;
use App\Models\Subscription;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotifySubscribersJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 60;
    public int $tries = 3;
    public int $backoff = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Incident $incident,
        public string $eventType = 'created'
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Notifying subscribers for incident {$this->incident->id} - event: {$this->eventType}");

        // Get all active subscriptions for this application
        $subscriptions = Subscription::where('subscribable_type', \App\Models\Application::class)
            ->where('subscribable_id', $this->incident->application_id)
            ->where('is_active', true)
            ->get();

        // Also get subscriptions for the application's group
        if ($this->incident->application->application_group_id) {
            $groupSubscriptions = Subscription::where('subscribable_type', \App\Models\ApplicationGroup::class)
                ->where('subscribable_id', $this->incident->application->application_group_id)
                ->where('is_active', true)
                ->get();
            
            $subscriptions = $subscriptions->merge($groupSubscriptions);
        }

        foreach ($subscriptions as $subscription) {
            $this->sendNotification($subscription);
        }

        // Also notify the application owner
        $this->notifyApplicationOwner();
    }

    /**
     * Send notification based on subscription channels.
     */
    private function sendNotification(Subscription $subscription): void
    {
        foreach ($subscription->notification_channels as $channel) {
            try {
                match ($channel) {
                    'email' => $this->sendEmailNotification($subscription),
                    'slack' => $this->sendSlackNotification($subscription),
                    'teams' => $this->sendTeamsNotification($subscription),
                    'discord' => $this->sendDiscordNotification($subscription),
                    default => Log::warning("Unknown notification channel: {$channel}"),
                };
            } catch (\Exception $e) {
                Log::error("Failed to send {$channel} notification: {$e->getMessage()}");
            }
        }
    }

    /**
     * Send email notification.
     */
    private function sendEmailNotification(Subscription $subscription): void
    {
        if (!$subscription->email) {
            Log::warning("No email address for subscription {$subscription->id}");
            return;
        }

        $subject = $this->getEmailSubject();
        $message = $this->getEmailMessage();

        // In a real application, you would use a proper Mail class
        // For now, we'll just log the email
        Log::info("Email notification sent to {$subscription->email}: {$subject}");
        
        // TODO: Implement actual email sending with Mail::to($subscription->email)->send()
    }

    /**
     * Send Slack notification.
     */
    private function sendSlackNotification(Subscription $subscription): void
    {
        if (!$subscription->webhook_url) {
            Log::warning("No webhook URL for Slack subscription {$subscription->id}");
            return;
        }

        $payload = [
            'text' => $this->getSlackMessage(),
            'attachments' => [
                [
                    'color' => $this->getSlackColor(),
                    'fields' => [
                        [
                            'title' => 'Application',
                            'value' => $this->incident->application->name,
                            'short' => true,
                        ],
                        [
                            'title' => 'Severity',
                            'value' => $this->incident->severity->value,
                            'short' => true,
                        ],
                        [
                            'title' => 'Status',
                            'value' => $this->incident->status->value,
                            'short' => true,
                        ],
                        [
                            'title' => 'Time',
                            'value' => $this->incident->started_at->format('Y-m-d H:i:s T'),
                            'short' => true,
                        ],
                    ],
                ],
            ],
        ];

        Http::post($subscription->webhook_url, $payload);
        Log::info("Slack notification sent for incident {$this->incident->id}");
    }

    /**
     * Send Teams notification.
     */
    private function sendTeamsNotification(Subscription $subscription): void
    {
        if (!$subscription->webhook_url) {
            Log::warning("No webhook URL for Teams subscription {$subscription->id}");
            return;
        }

        $payload = [
            '@type' => 'MessageCard',
            '@context' => 'http://schema.org/extensions',
            'themeColor' => $this->getTeamsColor(),
            'summary' => $this->getTeamsMessage(),
            'sections' => [
                [
                    'activityTitle' => $this->getTeamsMessage(),
                    'facts' => [
                        [
                            'name' => 'Application',
                            'value' => $this->incident->application->name,
                        ],
                        [
                            'name' => 'Severity',
                            'value' => $this->incident->severity->value,
                        ],
                        [
                            'name' => 'Status',
                            'value' => $this->incident->status->value,
                        ],
                        [
                            'name' => 'Time',
                            'value' => $this->incident->started_at->format('Y-m-d H:i:s T'),
                        ],
                    ],
                ],
            ],
        ];

        Http::post($subscription->webhook_url, $payload);
        Log::info("Teams notification sent for incident {$this->incident->id}");
    }

    /**
     * Send Discord notification.
     */
    private function sendDiscordNotification(Subscription $subscription): void
    {
        if (!$subscription->webhook_url) {
            Log::warning("No webhook URL for Discord subscription {$subscription->id}");
            return;
        }

        $payload = [
            'content' => $this->getDiscordMessage(),
            'embeds' => [
                [
                    'title' => $this->incident->title,
                    'description' => $this->incident->description,
                    'color' => $this->getDiscordColor(),
                    'fields' => [
                        [
                            'name' => 'Application',
                            'value' => $this->incident->application->name,
                            'inline' => true,
                        ],
                        [
                            'name' => 'Severity',
                            'value' => $this->incident->severity->value,
                            'inline' => true,
                        ],
                        [
                            'name' => 'Status',
                            'value' => $this->incident->status->value,
                            'inline' => true,
                        ],
                    ],
                    'timestamp' => $this->incident->started_at->toISOString(),
                ],
            ],
        ];

        Http::post($subscription->webhook_url, $payload);
        Log::info("Discord notification sent for incident {$this->incident->id}");
    }

    /**
     * Notify the application owner directly.
     */
    private function notifyApplicationOwner(): void
    {
        $owner = $this->incident->application->user;
        
        // Send email if available
        if ($owner->notification_email) {
            $this->sendOwnerEmailNotification($owner->notification_email);
        }

        // Send to webhook URLs if configured
        if ($owner->slack_webhook_url) {
            $this->sendSlackToUrl($owner->slack_webhook_url);
        }

        if ($owner->teams_webhook_url) {
            $this->sendTeamsToUrl($owner->teams_webhook_url);
        }

        if ($owner->discord_webhook_url) {
            $this->sendDiscordToUrl($owner->discord_webhook_url);
        }
    }

    /**
     * Helper methods for message formatting.
     */
    private function getEmailSubject(): string
    {
        $action = $this->eventType === 'resolved' ? 'Resolved' : 'Alert';
        return "[{$action}] {$this->incident->application->name} - {$this->incident->title}";
    }

    private function getEmailMessage(): string
    {
        $status = $this->eventType === 'resolved' ? 'has been resolved' : 'is experiencing an issue';
        return "Your application '{$this->incident->application->name}' {$status}.\n\n" .
               "Incident: {$this->incident->title}\n" .
               "Description: {$this->incident->description}\n" .
               "Severity: {$this->incident->severity->value}\n" .
               "Time: {$this->incident->started_at->format('Y-m-d H:i:s T')}";
    }

    private function getSlackMessage(): string
    {
        $emoji = $this->eventType === 'resolved' ? '✅' : '🚨';
        $action = $this->eventType === 'resolved' ? 'Resolved' : 'Alert';
        return "{$emoji} *{$action}*: {$this->incident->application->name} - {$this->incident->title}";
    }

    private function getTeamsMessage(): string
    {
        $action = $this->eventType === 'resolved' ? 'Resolved' : 'Alert';
        return "{$action}: {$this->incident->application->name} - {$this->incident->title}";
    }

    private function getDiscordMessage(): string
    {
        $emoji = $this->eventType === 'resolved' ? '✅' : '🚨';
        $action = $this->eventType === 'resolved' ? 'Resolved' : 'Alert';
        return "{$emoji} **{$action}**: {$this->incident->application->name} - {$this->incident->title}";
    }

    /**
     * Color helpers for different platforms.
     */
    private function getSlackColor(): string
    {
        if ($this->eventType === 'resolved') {
            return 'good';
        }

        return match ($this->incident->severity) {
            \App\Enums\IncidentSeverity::CRITICAL => 'danger',
            \App\Enums\IncidentSeverity::HIGH => 'warning',
            \App\Enums\IncidentSeverity::LOW => '#439FE0',
        };
    }

    private function getTeamsColor(): string
    {
        if ($this->eventType === 'resolved') {
            return '00FF00';
        }

        return match ($this->incident->severity) {
            \App\Enums\IncidentSeverity::CRITICAL => 'FF0000',
            \App\Enums\IncidentSeverity::HIGH => 'FFA500',
            \App\Enums\IncidentSeverity::LOW => '0078D4',
        };
    }

    private function getDiscordColor(): int
    {
        if ($this->eventType === 'resolved') {
            return 0x00FF00; // Green
        }

        return match ($this->incident->severity) {
            \App\Enums\IncidentSeverity::CRITICAL => 0xFF0000, // Red
            \App\Enums\IncidentSeverity::HIGH => 0xFFA500,     // Orange
            \App\Enums\IncidentSeverity::LOW => 0x0099FF,      // Blue
        };
    }

    /**
     * Send notifications to specific URLs.
     */
    private function sendSlackToUrl(string $url): void
    {
        $payload = ['text' => $this->getSlackMessage()];
        Http::post($url, $payload);
    }

    private function sendTeamsToUrl(string $url): void
    {
        $payload = [
            '@type' => 'MessageCard',
            '@context' => 'http://schema.org/extensions',
            'summary' => $this->getTeamsMessage(),
            'text' => $this->getTeamsMessage(),
        ];
        Http::post($url, $payload);
    }

    private function sendDiscordToUrl(string $url): void
    {
        $payload = ['content' => $this->getDiscordMessage()];
        Http::post($url, $payload);
    }

    private function sendOwnerEmailNotification(string $email): void
    {
        // TODO: Implement actual email sending
        Log::info("Owner email notification sent to {$email}: {$this->getEmailSubject()}");
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("NotifySubscribersJob failed for incident {$this->incident->id}: {$exception->getMessage()}");
    }
}
