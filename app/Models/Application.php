<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Application extends Model
{
    /** @use HasFactory<\Database\Factories\ApplicationFactory> */
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'url',
        'url_to_watch',
        'expected_http_code',
        'monitoring_interval',
        'user_id',
        'application_group_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expected_http_code' => 'integer',
        ];
    }

    /**
     * Get the user that owns the application.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the application group that this application belongs to.
     */
    public function applicationGroup(): BelongsTo
    {
        return $this->belongsTo(ApplicationGroup::class);
    }

    /**
     * Get the incidents for the application.
     */
    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }

    /**
     * Get all subscriptions for this application.
     */
    public function subscriptions(): MorphMany
    {
        return $this->morphMany(Subscription::class, 'subscribable');
    }

    /**
     * Get the URL to monitor (uses url_to_watch if available, otherwise url).
     */
    public function getMonitorUrlAttribute(): string
    {
        return $this->url_to_watch ?? $this->url;
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::created(function (Application $application) {
            // Auto-subscribe the owner to their application
            $application->subscriptions()->create([
                'user_id' => $application->user_id,
                'notification_channels' => ['email'],
            ]);
        });
    }
}
