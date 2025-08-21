<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IncidentResource extends JsonResource
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
            'title' => $this->title,
            'description' => $this->description,
            'application_id' => $this->application_id,
            'user_id' => $this->user_id,
            'status' => $this->status->value,
            'severity' => $this->severity->value,
            'started_at' => $this->started_at,
            'ended_at' => $this->ended_at,
            'duration' => $this->duration,
            'is_active' => $this->isActive(),
            'is_closed' => $this->isClosed(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Relationships
            'application' => $this->whenLoaded('application', fn() => new ApplicationResource($this->application)),
            'user' => $this->whenLoaded('user', fn() => new UserResource($this->user)),
            
            // Additional metadata
            'severity_color' => $this->severity->getColor(),
            'status_transitions' => $this->status->getAllowedTransitions(),
        ];
    }
}
