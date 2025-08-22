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
            'subscribable_type' => $this->subscribable_type,
            'subscribable_id' => $this->subscribable_id,
            'notification_channels' => $this->notification_channels,
            'webhook_url' => $this->webhook_url,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Relationships
            'user' => $this->whenLoaded('user', fn() => new UserResource($this->user)),
            'subscribable' => $this->when(
                $this->relationLoaded('subscribable'),
                function () {
                    if ($this->subscribable_type === 'App\\Models\\Application') {
                        return new ApplicationResource($this->subscribable);
                    }
                    if ($this->subscribable_type === 'App\\Models\\ApplicationGroup') {
                        return new ApplicationGroupResource($this->subscribable);
                    }
                    return null;
                }
            ),
        ];
    }
}
