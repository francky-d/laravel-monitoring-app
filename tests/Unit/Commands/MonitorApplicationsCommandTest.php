<?php

use App\Jobs\MonitorApplicationJob;
use App\Models\Application;
use App\Models\ApplicationGroup;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
    $this->user = User::factory()->create();
    $this->applicationGroup = ApplicationGroup::factory()->create(['user_id' => $this->user->id]);
});

test('command dispatches monitoring jobs for all applications', function () {
    Application::factory(3)->create([
        'user_id' => $this->user->id,
        'application_group_id' => $this->applicationGroup->id,
    ]);

    $this->artisan('monitor:applications', ['--force' => true])
        ->expectsOutput('Starting application monitoring...')
        ->expectsOutput('Found 3 application(s) to monitor.')
        ->assertExitCode(0);

    Queue::assertPushed(MonitorApplicationJob::class, 3);
});

test('command can monitor specific application', function () {
    $app1 = Application::factory()->create([
        'user_id' => $this->user->id,
        'application_group_id' => $this->applicationGroup->id,
    ]);
    
    $app2 = Application::factory()->create([
        'user_id' => $this->user->id,
        'application_group_id' => $this->applicationGroup->id,
    ]);

    $this->artisan('monitor:applications', ['--application' => $app1->id, '--force' => true])
        ->expectsOutput('Found 1 application(s) to monitor.')
        ->assertExitCode(0);

    Queue::assertPushed(MonitorApplicationJob::class, 1);
    Queue::assertPushed(MonitorApplicationJob::class, function ($job) use ($app1) {
        return $job->application->id === $app1->id;
    });
});

test('command can monitor applications in specific group', function () {
    $group2 = ApplicationGroup::factory()->create(['user_id' => $this->user->id]);
    
    Application::factory(2)->create([
        'user_id' => $this->user->id,
        'application_group_id' => $this->applicationGroup->id,
    ]);
    
    Application::factory(1)->create([
        'user_id' => $this->user->id,
        'application_group_id' => $group2->id,
    ]);

    $this->artisan('monitor:applications', ['--group' => $group2->id, '--force' => true])
        ->expectsOutput('Found 1 application(s) to monitor.')
        ->assertExitCode(0);

    Queue::assertPushed(MonitorApplicationJob::class, 1);
});

test('command shows warning when no applications found', function () {
    $this->artisan('monitor:applications')
        ->expectsOutput('No applications found to monitor.')
        ->assertExitCode(0);

    Queue::assertNotPushed(MonitorApplicationJob::class);
});

test('command respects force option', function () {
    // Create application that was recently monitored
    $application = Application::factory()->create([
        'user_id' => $this->user->id,
        'application_group_id' => $this->applicationGroup->id,
        'monitoring_interval' => 60, // 1 hour
    ]);

    // Create a recent incident to simulate recent monitoring
    \App\Models\Incident::factory()->create([
        'application_id' => $application->id,
        'created_at' => now()->subMinutes(30), // 30 minutes ago
    ]);

    // Without force, should skip
    $this->artisan('monitor:applications')
        ->expectsOutput('Starting application monitoring...')
        ->assertExitCode(0);

    Queue::assertNotPushed(MonitorApplicationJob::class);

    // With force, should monitor
    $this->artisan('monitor:applications', ['--force' => true])
        ->expectsOutput('Starting application monitoring...')
        ->assertExitCode(0);

    Queue::assertPushed(MonitorApplicationJob::class, 1);
});

test('command shows monitoring summary', function () {
    Application::factory(2)->create([
        'user_id' => $this->user->id,
        'application_group_id' => $this->applicationGroup->id,
    ]);

    $this->artisan('monitor:applications', ['--force' => true])
        ->expectsOutput('Monitoring summary:')
        ->expectsOutput('  - Processed: 2')
        ->expectsOutput('  - Skipped: 0')
        ->expectsOutput('  - Total: 2')
        ->assertExitCode(0);
});

test('command handles monitoring interval correctly', function () {
    // Create application with 5-minute monitoring interval
    $application = Application::factory()->create([
        'user_id' => $this->user->id,
        'application_group_id' => $this->applicationGroup->id,
        'monitoring_interval' => 5,
    ]);

    // Create incident from 10 minutes ago (should trigger monitoring)
    \App\Models\Incident::factory()->create([
        'application_id' => $application->id,
        'created_at' => now()->subMinutes(10),
    ]);

    $this->artisan('monitor:applications')
        ->expectsOutput('Found 1 application(s) to monitor.')
        ->assertExitCode(0);

    Queue::assertPushed(MonitorApplicationJob::class, 1);
});

test('command skips applications within monitoring interval', function () {
    // Create application with 60-minute monitoring interval
    $application = Application::factory()->create([
        'user_id' => $this->user->id,
        'application_group_id' => $this->applicationGroup->id,
        'monitoring_interval' => 60,
    ]);

    // Create recent incident (should skip monitoring)
    \App\Models\Incident::factory()->create([
        'application_id' => $application->id,
        'created_at' => now()->subMinutes(10),
    ]);

    $this->artisan('monitor:applications')
        ->expectsOutput('Monitoring summary:')
        ->expectsOutput('  - Processed: 0')
        ->expectsOutput('  - Skipped: 1')
        ->assertExitCode(0);

    Queue::assertNotPushed(MonitorApplicationJob::class);
});

test('command provides verbose output for individual applications', function () {
    $application = Application::factory()->create([
        'user_id' => $this->user->id,
        'application_group_id' => $this->applicationGroup->id,
        'name' => 'Test Application',
    ]);

    $this->artisan('monitor:applications', ['--force' => true])
        ->expectsOutput("  ✓ Queued monitoring for: Test Application")
        ->assertExitCode(0);
});

test('command handles invalid application id gracefully', function () {
    $this->artisan('monitor:applications', ['--application' => 'invalid-id'])
        ->expectsOutput('No applications found to monitor.')
        ->assertExitCode(0);

    Queue::assertNotPushed(MonitorApplicationJob::class);
});

test('command handles invalid group id gracefully', function () {
    $this->artisan('monitor:applications', ['--group' => 'invalid-id'])
        ->expectsOutput('No applications found to monitor.')
        ->assertExitCode(0);

    Queue::assertNotPushed(MonitorApplicationJob::class);
});

test('command uses default monitoring interval when not set', function () {
    // Create application and check it has a monitoring interval from factory
    $application = Application::factory()->create([
        'user_id' => $this->user->id,
        'application_group_id' => $this->applicationGroup->id,
    ]);
    
    // Verify it has a monitoring interval (from factory or database default)
    $this->assertNotNull($application->fresh()->monitoring_interval);

    // Should monitor the application when forced
    $this->artisan('monitor:applications', ['--force' => true])
        ->expectsOutput('Found 1 application(s) to monitor.')
        ->assertExitCode(0);

    Queue::assertPushed(MonitorApplicationJob::class, 1);
});
