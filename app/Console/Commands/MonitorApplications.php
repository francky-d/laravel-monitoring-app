<?php

namespace App\Console\Commands;

use App\Jobs\MonitorApplicationJob;
use App\Models\Application;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MonitorApplications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitor:applications 
                           {--application= : Monitor a specific application ID}
                           {--group= : Monitor applications in a specific group}
                           {--force : Force monitoring even if interval has not passed}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor all active applications for availability and performance';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting application monitoring...');

        $query = Application::query();

        // Filter by specific application if provided
        if ($applicationId = $this->option('application')) {
            $query->where('id', $applicationId);
        }

        // Filter by application group if provided
        if ($groupId = $this->option('group')) {
            $query->where('application_group_id', $groupId);
        }

        // Get all applications to monitor
        $applications = $query->with(['applicationGroup', 'user'])->get();

        if ($applications->isEmpty()) {
            $this->warn('No applications found to monitor.');
            return self::SUCCESS;
        }

        $this->info("Found {$applications->count()} application(s) to monitor.");

        $processed = 0;
        $skipped = 0;

        foreach ($applications as $application) {
            // Check if we should monitor this application based on its last check time
            $shouldMonitor = $this->shouldMonitorApplication($application);

            if (!$shouldMonitor && !$this->option('force')) {
                $skipped++;
                $this->line("  - Skipping {$application->name} (interval not reached)");
                continue;
            }

            // Dispatch monitoring job
            try {
                MonitorApplicationJob::dispatch($application);
                $processed++;
                $this->line("  ✓ Queued monitoring for: {$application->name}");
            } catch (\Exception $e) {
                $this->error("  ✗ Failed to queue monitoring for {$application->name}: {$e->getMessage()}");
                Log::error('Failed to dispatch monitoring job', [
                    'application_id' => $application->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();
        $this->info("Monitoring summary:");
        $this->info("  - Processed: {$processed}");
        $this->info("  - Skipped: {$skipped}");
        $this->info("  - Total: {$applications->count()}");

        return self::SUCCESS;
    }

    /**
     * Determine if an application should be monitored based on its monitoring interval.
     */
    private function shouldMonitorApplication(Application $application): bool
    {
        // If no monitoring interval is set, use a default of 5 minutes
        $interval = $application->monitoring_interval ?? 5;

        // Get the last monitoring time from the most recent incident or creation time
        $lastCheck = $application->incidents()
            ->latest('created_at')
            ->value('created_at') ?? $application->created_at;

        // Check if enough time has passed since the last monitoring
        $nextCheckTime = $lastCheck->addMinutes($interval);

        return now()->gte($nextCheckTime);
    }
}
