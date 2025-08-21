<?php

namespace App\Models;

use App\Enums\IncidentSeverity;
use App\Enums\IncidentStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Incident extends Model
{
    /** @use HasFactory<\Database\Factories\IncidentFactory> */
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'application_id',
        'user_id',
        'status',
        'severity',
        'response_code',
        'response_time',
        'error_message',
        'started_at',
        'ended_at',
        'resolved_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => IncidentStatus::class,
            'severity' => IncidentSeverity::class,
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    /**
     * Get the application that this incident belongs to.
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * Get the user who reported this incident.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the incident is active (open or in progress).
     */
    public function isActive(): bool
    {
        return $this->status->isActive();
    }

    /**
     * Check if the incident is closed (resolved or closed).
     */
    public function isClosed(): bool
    {
        return $this->status->isClosed();
    }

    /**
     * Get the duration of the incident in seconds.
     */
    public function getDurationAttribute(): ?int
    {
        if (!$this->ended_at) {
            return null;
        }

        return $this->ended_at->diffInSeconds($this->started_at);
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::updating(function (Incident $incident) {
            // Automatically set ended_at when status changes to RESOLVED or CLOSED
            if ($incident->isDirty('status')) {
                $newStatus = $incident->status instanceof IncidentStatus 
                    ? $incident->status 
                    : IncidentStatus::from($incident->status);
                
                if ($newStatus->isClosed() && !$incident->ended_at) {
                    $incident->ended_at = now();
                } elseif (!$newStatus->isClosed() && $incident->ended_at) {
                    $incident->ended_at = null;
                }
            }
        });
    }
}
