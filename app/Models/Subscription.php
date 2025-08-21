<?php

namespace App\Models;

use App\Enums\NotificationChannel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Subscription extends Model
{
    /** @use HasFactory<\Database\Factories\SubscriptionFactory> */
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'subscribable_type',
        'subscribable_id',
        'notification_channels',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'notification_channels' => 'array',
        ];
    }

    /**
     * Get the user that owns the subscription.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the subscribable model (Application or ApplicationGroup).
     */
    public function subscribable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Check if a notification channel is enabled.
     */
    public function hasChannel(string $channel): bool
    {
        return in_array($channel, $this->notification_channels ?? []);
    }

    /**
     * Add a notification channel.
     */
    public function addChannel(string $channel): void
    {
        $channels = $this->notification_channels ?? [];
        
        if (!in_array($channel, $channels)) {
            $channels[] = $channel;
            $this->notification_channels = $channels;
            $this->save();
        }
    }

    /**
     * Remove a notification channel.
     */
    public function removeChannel(string $channel): void
    {
        $channels = $this->notification_channels ?? [];
        $channels = array_filter($channels, fn($c) => $c !== $channel);
        
        $this->notification_channels = array_values($channels);
        $this->save();
    }

    /**
     * Set the notification channels, ensuring email is always included.
     */
    public function setNotificationChannelsAttribute(array $channels): void
    {
        // Ensure email is always included
        if (!in_array(NotificationChannel::EMAIL->value, $channels)) {
            $channels[] = NotificationChannel::EMAIL->value;
        }

        // Validate channels
        $validChannels = NotificationChannel::values();
        $channels = array_filter($channels, fn($channel) => in_array($channel, $validChannels));

        $this->attributes['notification_channels'] = json_encode(array_unique($channels));
    }
}
