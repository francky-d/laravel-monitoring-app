<?php

use App\Models\Application;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user);
});

test('user can create application', function () {
    $applicationData = [
        'name' => 'Test App',
        'url' => 'https://example.com',
        'expected_http_code' => 200,
    ];

    $response = $this->postJson('/api/applications', $applicationData);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'name',
                'url',
                'expected_http_code',
                'user_id',
            ],
        ])
        ->assertJson([
            'success' => true,
            'message' => 'Application created successfully',
        ]);

    $this->assertDatabaseHas('applications', [
        'name' => 'Test App',
        'url' => 'https://example.com',
        'user_id' => $this->user->id,
    ]);
});

test('user can list their applications', function () {
    Application::factory(3)->create(['user_id' => $this->user->id]);

    $response = $this->getJson('/api/applications');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'url',
                    'user_id',
                ],
            ],
            'pagination',
        ])
        ->assertJson([
            'success' => true,
            'message' => 'Applications retrieved successfully',
        ])
        ->assertJsonCount(3, 'data');
});

test('user can view their application', function () {
    $application = Application::factory()->create(['user_id' => $this->user->id]);

    $response = $this->getJson("/api/applications/{$application->id}");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'url',
                'user_id',
            ],
        ]);
});

test('user cannot view other users application', function () {
    $otherUser = User::factory()->create();
    $application = Application::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->getJson("/api/applications/{$application->id}");

    $response->assertStatus(403);
});

test('user can update their application', function () {
    $application = Application::factory()->create(['user_id' => $this->user->id]);

    $updateData = [
        'name' => 'Updated App Name',
        'url' => 'https://updated-example.com',
    ];

    $response = $this->putJson("/api/applications/{$application->id}", $updateData);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'message',
            'data' => [
                'id',
                'name',
                'url',
            ],
        ]);

    $this->assertDatabaseHas('applications', [
        'id' => $application->id,
        'name' => 'Updated App Name',
        'url' => 'https://updated-example.com',
    ]);
});

test('user can delete their application', function () {
    $application = Application::factory()->create(['user_id' => $this->user->id]);

    $response = $this->deleteJson("/api/applications/{$application->id}");

    $response->assertStatus(200)
        ->assertJson(['message' => 'Application deleted successfully']);

    $this->assertDatabaseMissing('applications', [
        'id' => $application->id,
    ]);
});
