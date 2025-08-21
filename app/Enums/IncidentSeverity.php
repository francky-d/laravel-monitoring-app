<?php

namespace App\Enums;

enum IncidentSeverity: string
{
    case LOW = 'LOW';
    case HIGH = 'HIGH';
    case CRITICAL = 'CRITICAL';

    /**
     * Get all possible severity values.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get the severity level as an integer (for comparison).
     */
    public function getLevel(): int
    {
        return match ($this) {
            self::LOW => 1,
            self::HIGH => 2,
            self::CRITICAL => 3,
        };
    }

    /**
     * Get color representation for the severity.
     */
    public function getColor(): string
    {
        return match ($this) {
            self::LOW => '#28a745',      // Green
            self::HIGH => '#ffc107',     // Yellow/Orange
            self::CRITICAL => '#dc3545', // Red
        };
    }
}
