<?php

namespace App\Enums;

enum NotificationChannel: string
{
    case EMAIL = 'email';
    case SLACK = 'slack';
    case TEAMS = 'teams';
    case DISCORD = 'discord';

    /**
     * Get all possible notification channel values.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get the default notification channels.
     */
    public static function defaults(): array
    {
        return [self::EMAIL->value];
    }
}
