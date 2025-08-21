<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationGroupResource extends JsonResource
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
            'description' => $this->description,
            'user_id' => $this->user_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Relationships
            'user' => $this->whenLoaded('user', fn() => new UserResource($this->user)),
            'applications' => $this->whenLoaded('applications', fn() => ApplicationResource::collection($this->applications)),
            'subscriptions' => $this->whenLoaded('subscriptions', fn() => SubscriptionResource::collection($this->subscriptions)),
            
            // Counts
            'applications_count' => $this->when(
                $this->relationLoaded('applications'),
                fn() => $this->applications->count()
            ),
        ];
    }
}
