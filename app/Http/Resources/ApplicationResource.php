<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationResource extends JsonResource
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
            'name' => $this->name,
            'url' => $this->url,
            'url_to_watch' => $this->url_to_watch,
            'expected_http_code' => $this->expected_http_code,
            'monitor_url' => $this->monitor_url,
            'user_id' => $this->user_id,
            'application_group_id' => $this->application_group_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Conditional relationships
            'user' => $this->whenLoaded('user', fn() => new UserResource($this->user)),
            'application_group' => $this->whenLoaded('applicationGroup', fn() => new ApplicationGroupResource($this->applicationGroup)),
            'incidents' => $this->whenLoaded('incidents', fn() => IncidentResource::collection($this->incidents)),
            'subscriptions' => $this->whenLoaded('subscriptions', fn() => SubscriptionResource::collection($this->subscriptions)),
            
            // Incident counts
            'incidents_count' => $this->when(
                $this->relationLoaded('incidents'),
                fn() => $this->incidents->count()
            ),
            'active_incidents_count' => $this->when(
                $this->relationLoaded('incidents'),
                fn() => $this->incidents->filter(fn($incident) => $incident->isActive())->count()
            ),
        ];
    }
}
