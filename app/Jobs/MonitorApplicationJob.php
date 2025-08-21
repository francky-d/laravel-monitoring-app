<?php

namespace App\Jobs;

use App\Enums\IncidentSeverity;
use App\Enums\IncidentStatus;
use App\Models\Application;
use App\Models\Incident;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MonitorApplicationJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 30;
    public int $tries = 3;
    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Application $application
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Monitoring application: {$this->application->name} ({$this->application->monitor_url})");

        try {
            $response = Http::timeout(15)->get($this->application->monitor_url);
            
            $isHealthy = $response->successful() && $response->status() === $this->application->expected_http_code;
            
            if ($isHealthy) {
                $this->handleHealthyApplication();
            } else {
                $this->handleUnhealthyApplication($response->status(), $response->body());
            }
            
        } catch (\Exception $e) {
            $this->handleUnhealthyApplication(0, $e->getMessage());
        }
    }

    /**
     * Handle when application is healthy.
     */
    private function handleHealthyApplication(): void
    {
        // Close any open incidents for this application
        $activeIncidents = $this->application->incidents()
            ->where('status', IncidentStatus::OPEN)
            ->get();

        foreach ($activeIncidents as $incident) {
            $incident->update([
                'status' => IncidentStatus::RESOLVED,
                'ended_at' => now(),
            ]);

            Log::info("Resolved incident {$incident->id} for application {$this->application->name}");
            
            // Dispatch notification for incident resolution
            NotifySubscribersJob::dispatch($incident, 'resolved');
        }
    }

    /**
     * Handle when application is unhealthy.
     */
    private function handleUnhealthyApplication(int $statusCode, string $errorMessage): void
    {
        // Check if there's already an open incident
        $existingIncident = $this->application->incidents()
            ->where('status', IncidentStatus::OPEN)
            ->first();

        if (!$existingIncident) {
            // Create new incident
            $severity = $this->determineSeverity($statusCode);
            
            $incident = Incident::create([
                'application_id' => $this->application->id,
                'user_id' => $this->application->user_id,
                'title' => $this->generateIncidentTitle($statusCode),
                'description' => $this->generateIncidentDescription($statusCode, $errorMessage),
                'severity' => $severity,
                'status' => IncidentStatus::OPEN,
                'response_code' => $statusCode > 0 ? $statusCode : null,
                'started_at' => now(),
            ]);

            Log::warning("Created new incident {$incident->id} for application {$this->application->name}");
            
            // Dispatch notification for new incident
            NotifySubscribersJob::dispatch($incident, 'created');
        } else {
            Log::info("Incident {$existingIncident->id} already exists for application {$this->application->name}");
        }
    }

    /**
     * Determine incident severity based on status code.
     */
    private function determineSeverity(int $statusCode): IncidentSeverity
    {
        return match (true) {
            $statusCode === 0 => IncidentSeverity::CRITICAL, // Connection error
            $statusCode >= 500 => IncidentSeverity::HIGH,     // Server errors
            $statusCode >= 400 => IncidentSeverity::LOW,      // Client errors
            default => IncidentSeverity::LOW,
        };
    }

    /**
     * Generate incident title based on status code.
     */
    private function generateIncidentTitle(int $statusCode): string
    {
        return match (true) {
            $statusCode === 0 => 'Connection Failed',
            $statusCode >= 500 => 'Server Error',
            $statusCode >= 400 => 'Client Error',
            default => 'Application Issue',
        };
    }

    /**
     * Generate incident description.
     */
    private function generateIncidentDescription(int $statusCode, string $errorMessage): string
    {
        if ($statusCode === 0) {
            return "Failed to connect to application: {$errorMessage}";
        }

        return "Application returned HTTP {$statusCode}. Error: {$errorMessage}";
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("MonitorApplicationJob failed for application {$this->application->name}: {$exception->getMessage()}");
        
        // Create a critical incident for monitoring failure
        Incident::create([
            'application_id' => $this->application->id,
            'user_id' => $this->application->user_id,
            'title' => 'Monitoring System Failure',
            'description' => "Failed to monitor application: {$exception->getMessage()}",
            'severity' => IncidentSeverity::CRITICAL,
            'status' => IncidentStatus::OPEN,
            'started_at' => now(),
        ]);
    }
}
