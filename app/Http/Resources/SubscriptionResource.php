<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'application_id' => $this->application_id,
            'notification_email' => $this->notification_email,
            'slack_webhook_url' => $this->slack_webhook_url,
            'teams_webhook_url' => $this->teams_webhook_url,
            'discord_webhook_url' => $this->discord_webhook_url,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Relationships
            'user' => new UserResource($this->whenLoaded('user')),
            'application' => new ApplicationResource($this->whenLoaded('application')),
            
            // Computed properties
            'notification_channels' => $this->getNotificationChannels(),
            'has_webhooks' => $this->hasWebhooks(),
        ];
    }

    /**
     * Get active notification channels.
     */
    private function getNotificationChannels(): array
    {
        $channels = [];
        
        if ($this->notification_email) {
            $channels[] = 'email';
        }
        if ($this->slack_webhook_url) {
            $channels[] = 'slack';
        }
        if ($this->teams_webhook_url) {
            $channels[] = 'teams';
        }
        if ($this->discord_webhook_url) {
            $channels[] = 'discord';
        }
        
        return $channels;
    }

    /**
     * Check if subscription has any webhooks configured.
     */
    private function hasWebhooks(): bool
    {
        return !empty($this->slack_webhook_url) || 
               !empty($this->teams_webhook_url) || 
               !empty($this->discord_webhook_url);
    }
}
