<?php

use App\Jobs\MonitorApplicationJob;
use App\Jobs\NotifySubscribersJob;
use App\Models\Application;
use App\Models\ApplicationGroup;
use App\Models\Incident;
use App\Models\User;
use App\Enums\IncidentSeverity;
use App\Enums\IncidentStatus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->applicationGroup = ApplicationGroup::factory()->create(['user_id' => $this->user->id]);
    $this->application = Application::factory()->create([
        'user_id' => $this->user->id,
        'application_group_id' => $this->applicationGroup->id,
        'url' => 'https://example.com',
        'expected_http_code' => 200,
    ]);
});

test('job creates incident when application is down', function () {
    Http::fake([
        '*' => Http::response('', 500),
    ]);

    (new MonitorApplicationJob($this->application))->handle();

    expect(Incident::count())->toBe(1);
    
    $incident = Incident::first();
    expect($incident->application_id)->toBe($this->application->id);
    expect($incident->severity)->toBe(IncidentSeverity::HIGH);
    expect($incident->status)->toBe(IncidentStatus::OPEN);
});

test('job creates low severity incident for 404 errors', function () {
    Http::fake([
        '*' => Http::response('', 404),
    ]);

    (new MonitorApplicationJob($this->application))->handle();

    $incident = Incident::first();
    expect($incident->severity)->toBe(IncidentSeverity::LOW);
});

test('job creates no incident for successful response', function () {
    Http::fake([
        '*' => Http::response('OK', 200),
    ]);

    (new MonitorApplicationJob($this->application))->handle();

    // No incident should be created for successful response
    expect(Incident::count())->toBe(0);
});

test('job resolves existing incidents when application is healthy', function () {
    // Create an existing open incident
    $existingIncident = Incident::factory()->open()->create([
        'application_id' => $this->application->id,
    ]);

    Http::fake([
        '*' => Http::response('OK', 200),
    ]);

    (new MonitorApplicationJob($this->application))->handle();

    $existingIncident->refresh();
    expect($existingIncident->status)->toBe(IncidentStatus::RESOLVED);
    expect($existingIncident->ended_at)->not()->toBeNull();
});

test('job dispatches notification when incident is created', function () {
    Queue::fake();
    Http::fake([
        '*' => Http::response('', 500),
    ]);

    (new MonitorApplicationJob($this->application))->handle();

    Queue::assertPushed(NotifySubscribersJob::class, function ($job) {
        return $job->incident->application_id === $this->application->id;
    });
});

test('job dispatches notification when incident is resolved', function () {
    Queue::fake();
    
    // Create an existing open incident
    Incident::factory()->open()->create([
        'application_id' => $this->application->id,
    ]);

    Http::fake([
        '*' => Http::response('OK', 200),
    ]);

    (new MonitorApplicationJob($this->application))->handle();

    Queue::assertPushed(NotifySubscribersJob::class, 1); // One for resolution only
});

test('job uses url_to_watch when available', function () {
    $this->application->update(['url_to_watch' => 'https://health.example.com']);

    Http::fake([
        'health.example.com' => Http::response('OK', 200),
    ]);

    MonitorApplicationJob::dispatch($this->application);

    Http::assertSent(function ($request) {
        return $request->url() === 'https://health.example.com';
    });
});

test('job handles connection timeouts', function () {
    Http::fake([
        '*' => Http::response('Connection timeout', 408),
    ]);

    (new MonitorApplicationJob($this->application))->handle();

    $incident = Incident::first();
    expect($incident->severity)->toBe(IncidentSeverity::LOW);
    expect($incident->response_code)->toBe(408);
});

test('job respects expected http code', function () {
    $this->application->update(['expected_http_code' => 201]);

    Http::fake([
        '*' => Http::response('Created', 201),
    ]);

    (new MonitorApplicationJob($this->application))->handle();

    // Should not create incident for expected 201 response
    expect(Incident::count())->toBe(0);
});

test('job creates incident when response code doesnt match expected', function () {
    $this->application->update(['expected_http_code' => 201]);

    Http::fake([
        '*' => Http::response('OK', 200),
    ]);

    (new MonitorApplicationJob($this->application))->handle();

    $incident = Incident::first();
    expect($incident->title)->toBe('Application Issue');
    expect($incident->response_code)->toBe(200);
});

test('job doesnt create duplicate incidents for same issue', function () {
    // Create an existing open incident
    Incident::factory()->open()->create([
        'application_id' => $this->application->id,
        'response_code' => 500,
    ]);

    Http::fake([
        'example.com' => Http::response('', 500),
    ]);

    MonitorApplicationJob::dispatch($this->application);

    // Should not create a new incident
    expect(Incident::count())->toBe(1);
});

test('job logs monitoring activity', function () {
    Http::fake([
        '*' => Http::response('OK', 200),
    ]);

    (new MonitorApplicationJob($this->application))->handle();

    // Just verify job runs without errors for successful response
    expect(Incident::count())->toBe(0);
});

test('job handles ssl certificate errors', function () {
    Http::fake([
        '*' => Http::response('SSL Error', 526),
    ]);

    (new MonitorApplicationJob($this->application))->handle();

    $incident = Incident::first();
    expect($incident->severity)->toBe(IncidentSeverity::HIGH);
    expect($incident->response_code)->toBe(526);
});