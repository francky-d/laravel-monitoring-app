<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule application monitoring every 5 minutes
Schedule::command('monitor:applications')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/monitoring.log'));

// Schedule a daily summary (optional)
Schedule::command('monitor:applications --force')
    ->dailyAt('08:00')
    ->timezone('UTC')
    ->description('Daily application monitoring report');
