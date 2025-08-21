<?php

namespace App\Enums;

enum IncidentStatus: string
{
    case OPEN = 'OPEN';
    case IN_PROGRESS = 'IN_PROGRESS';
    case RESOLVED = 'RESOLVED';
    case CLOSED = 'CLOSED';

    /**
     * Get all possible status values.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Check if the current status can transition to the given status.
     */
    public function canTransitionTo(self $status): bool
    {
        return match ($this) {
            self::OPEN => in_array($status, [self::IN_PROGRESS, self::CLOSED]),
            self::IN_PROGRESS => in_array($status, [self::RESOLVED, self::CLOSED]),
            self::RESOLVED => $status === self::CLOSED,
            self::CLOSED => false,
        };
    }

    /**
     * Get the next allowed statuses for this status.
     */
    public function getAllowedTransitions(): array
    {
        return match ($this) {
            self::OPEN => [self::IN_PROGRESS, self::CLOSED],
            self::IN_PROGRESS => [self::RESOLVED, self::CLOSED],
            self::RESOLVED => [self::CLOSED],
            self::CLOSED => [],
        };
    }

    /**
     * Check if this status indicates the incident is active.
     */
    public function isActive(): bool
    {
        return in_array($this, [self::OPEN, self::IN_PROGRESS]);
    }

    /**
     * Check if this status indicates the incident is closed.
     */
    public function isClosed(): bool
    {
        return in_array($this, [self::RESOLVED, self::CLOSED]);
    }
}
