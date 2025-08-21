<?php

use App\Models\Application;
use App\Models\ApplicationGroup;
use App\Models\Incident;
use App\Models\User;
use App\Enums\IncidentSeverity;
use App\Enums\IncidentStatus;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->applicationGroup = ApplicationGroup::factory()->create(['user_id' => $this->user->id]);
    $this->application = Application::factory()->create([
        'user_id' => $this->user->id,
        'application_group_id' => $this->applicationGroup->id,
    ]);
    Sanctum::actingAs($this->user);
});

test('user can create incident', function () {
    $incidentData = [
        'title' => 'Application Down',
        'description' => 'The application is not responding',
        'severity' => IncidentSeverity::CRITICAL->value,
        'application_id' => $this->application->id,
    ];

    $response = $this->postJson('/api/incidents', $incidentData);

    $response->assertCreated()
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'title',
                'description',
                'severity',
                'status',
                'application_id',
            ],
        ])
        ->assertJson([
            'success' => true,
            'message' => 'Incident created successfully',
        ]);

    $this->assertDatabaseHas('incidents', [
        'title' => 'Application Down',
        'application_id' => $this->application->id,
        'severity' => IncidentSeverity::CRITICAL->value,
    ]);
});

test('user can list their incidents', function () {
    Incident::factory(3)->create(['application_id' => $this->application->id]);

    $response = $this->getJson('/api/incidents');

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                '*' => [
                    'id',
                    'title',
                    'severity',
                    'status',
                    'application',
                ],
            ],
            'pagination',
        ])
        ->assertJson([
            'success' => true,
            'message' => 'Incidents retrieved successfully',
        ])
        ->assertJsonCount(3, 'data');
});

test('user can view their incident', function () {
    $incident = Incident::factory()->create(['application_id' => $this->application->id]);

    $response = $this->getJson("/api/incidents/{$incident->id}");

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'title',
                'description',
                'severity',
                'status',
                'application',
            ],
        ]);
});

test('user cannot view other users incident', function () {
    $otherUser = User::factory()->create();
    $otherApplicationGroup = ApplicationGroup::factory()->create(['user_id' => $otherUser->id]);
    $otherApplication = Application::factory()->create([
        'user_id' => $otherUser->id,
        'application_group_id' => $otherApplicationGroup->id,
    ]);
    $incident = Incident::factory()->create(['application_id' => $otherApplication->id]);

    $response = $this->getJson("/api/incidents/{$incident->id}");

    $response->assertForbidden();
});

test('user can update their incident', function () {
    $incident = Incident::factory()->create(['application_id' => $this->application->id]);

    $updateData = [
        'title' => 'Updated Incident Title',
        'description' => 'Updated description',
        'severity' => IncidentSeverity::HIGH->value,
    ];

    $response = $this->putJson("/api/incidents/{$incident->id}", $updateData);

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'title',
                'description',
                'severity',
            ],
        ]);

    $this->assertDatabaseHas('incidents', [
        'id' => $incident->id,
        'title' => 'Updated Incident Title',
        'severity' => IncidentSeverity::HIGH->value,
    ]);
});

test('user can resolve incident', function () {
    $incident = Incident::factory()->open()->create(['application_id' => $this->application->id]);

    $response = $this->putJson("/api/incidents/{$incident->id}/resolve");

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Incident resolved successfully',
        ]);

    $this->assertDatabaseHas('incidents', [
        'id' => $incident->id,
        'status' => IncidentStatus::RESOLVED->value,
    ]);

    $incident->refresh();
    expect($incident->resolved_at)->not()->toBeNull();
});

test('user can reopen incident', function () {
    $incident = Incident::factory()->resolved()->create(['application_id' => $this->application->id]);

    $response = $this->putJson("/api/incidents/{$incident->id}/reopen");

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Incident reopened successfully',
        ]);

    $this->assertDatabaseHas('incidents', [
        'id' => $incident->id,
        'status' => IncidentStatus::OPEN->value,
        'resolved_at' => null,
    ]);
});

test('user can delete their incident', function () {
    $incident = Incident::factory()->create(['application_id' => $this->application->id]);

    $response = $this->deleteJson("/api/incidents/{$incident->id}");

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Incident deleted successfully',
        ]);

    $this->assertSoftDeleted('incidents', [
        'id' => $incident->id,
    ]);
});

test('user can filter incidents by severity', function () {
    Incident::factory()->critical()->create(['application_id' => $this->application->id]);
    Incident::factory()->high()->create(['application_id' => $this->application->id]);
    Incident::factory()->low()->create(['application_id' => $this->application->id]);

    $response = $this->getJson('/api/incidents?severity=critical');

    $response->assertOk()
        ->assertJsonCount(1, 'data');
});

test('user can filter incidents by status', function () {
    Incident::factory()->open()->create(['application_id' => $this->application->id]);
    Incident::factory()->resolved()->create(['application_id' => $this->application->id]);

    $response = $this->getJson('/api/incidents?status=open');

    $response->assertOk()
        ->assertJsonCount(1, 'data');
});

test('user can get incident statistics', function () {
    Incident::factory()->critical()->open()->create(['application_id' => $this->application->id]);
    Incident::factory()->high()->resolved()->create(['application_id' => $this->application->id]);
    Incident::factory()->low()->open()->create(['application_id' => $this->application->id]);

    $response = $this->getJson('/api/incidents/stats');

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'total',
                'open',
                'resolved',
                'by_severity',
                'by_application',
            ],
        ])
        ->assertJson([
            'success' => true,
            'data' => [
                'total' => 3,
                'open' => 2,
                'resolved' => 1,
            ],
        ]);
});

test('incident creation requires valid data', function () {
    $response = $this->postJson('/api/incidents', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['title', 'application_id']);
});

test('incident severity must be valid', function () {
    $response = $this->postJson('/api/incidents', [
        'title' => 'Test Incident',
        'application_id' => $this->application->id,
        'severity' => 'invalid_severity',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['severity']);
});
