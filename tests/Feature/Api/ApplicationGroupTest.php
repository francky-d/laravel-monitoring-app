<?php

use App\Models\Application;
use App\Models\ApplicationGroup;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user);
});

test('user can create application group', function () {
    $groupData = [
        'name' => 'Production Apps',
        'description' => 'All production applications',
    ];

    $response = $this->postJson('/api/application-groups', $groupData);

    $response->assertCreated()
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'name',
                'description',
                'user_id',
            ],
        ])
        ->assertJson([
            'success' => true,
            'message' => 'Application group created successfully',
        ]);

    $this->assertDatabaseHas('application_groups', [
        'name' => 'Production Apps',
        'user_id' => $this->user->id,
    ]);
});

test('user can list their application groups', function () {
    ApplicationGroup::factory(3)->create(['user_id' => $this->user->id]);

    $response = $this->getJson('/api/application-groups');

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'description',
                    'user_id',
                    'applications_count',
                ],
            ],
            'pagination',
        ])
        ->assertJson([
            'success' => true,
            'message' => 'Application groups retrieved successfully',
        ])
        ->assertJsonCount(3, 'data');
});

test('user can view their application group', function () {
    $group = ApplicationGroup::factory()->create(['user_id' => $this->user->id]);
    Application::factory(2)->create([
        'user_id' => $this->user->id,
        'application_group_id' => $group->id,
    ]);

    $response = $this->getJson("/api/application-groups/{$group->id}");

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'name',
                'description',
                'applications',
            ],
        ]);
});

test('user cannot view other users application group', function () {
    $otherUser = User::factory()->create();
    $group = ApplicationGroup::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->getJson("/api/application-groups/{$group->id}");

    $response->assertForbidden();
});

test('user can update their application group', function () {
    $group = ApplicationGroup::factory()->create(['user_id' => $this->user->id]);

    $updateData = [
        'name' => 'Updated Group Name',
        'description' => 'Updated description',
    ];

    $response = $this->putJson("/api/application-groups/{$group->id}", $updateData);

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'name',
                'description',
            ],
        ]);

    $this->assertDatabaseHas('application_groups', [
        'id' => $group->id,
        'name' => 'Updated Group Name',
        'description' => 'Updated description',
    ]);
});

test('user can delete their application group', function () {
    $group = ApplicationGroup::factory()->create(['user_id' => $this->user->id]);

    $response = $this->deleteJson("/api/application-groups/{$group->id}");

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Application group deleted successfully',
        ]);

    $this->assertDatabaseMissing('application_groups', [
        'id' => $group->id,
    ]);
});

test('user can add application to group', function () {
    $group = ApplicationGroup::factory()->create(['user_id' => $this->user->id]);
    $application = Application::factory()->create([
        'user_id' => $this->user->id,
        'application_group_id' => null,
    ]);

    $response = $this->postJson("/api/application-groups/{$group->id}/applications", [
        'application_id' => $application->id,
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Application added to group successfully',
        ]);

    $this->assertDatabaseHas('applications', [
        'id' => $application->id,
        'application_group_id' => $group->id,
    ]);
});

test('user can remove application from group', function () {
    $group = ApplicationGroup::factory()->create(['user_id' => $this->user->id]);
    $application = Application::factory()->create([
        'user_id' => $this->user->id,
        'application_group_id' => $group->id,
    ]);

    $response = $this->deleteJson("/api/application-groups/{$group->id}/applications/{$application->id}");

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Application removed from group successfully',
        ]);

    $this->assertDatabaseHas('applications', [
        'id' => $application->id,
        'application_group_id' => null,
    ]);
});

test('user can get group subscribers', function () {
    $group = ApplicationGroup::factory()->create(['user_id' => $this->user->id]);
    
    // Create some applications in the group
    Application::factory(2)->create([
        'user_id' => $this->user->id,
        'application_group_id' => $group->id,
    ]);

    $response = $this->getJson("/api/application-groups/{$group->id}/subscribers");

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                '*' => [
                    'id',
                    'user_id',
                    'notification_channels',
                    'subscribable_type',
                    'subscribable_id',
                ],
            ],
        ]);
});

test('application group creation requires valid data', function () {
    $response = $this->postJson('/api/application-groups', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

test('application group name must be unique for user', function () {
    ApplicationGroup::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Production',
    ]);

    $response = $this->postJson('/api/application-groups', [
        'name' => 'Production',
        'description' => 'Another production group',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

test('user cannot add other users application to their group', function () {
    $otherUser = User::factory()->create();
    $group = ApplicationGroup::factory()->create(['user_id' => $this->user->id]);
    $application = Application::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->postJson("/api/application-groups/{$group->id}/applications", [
        'application_id' => $application->id,
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['application_id']);
});

test('user cannot manage other users groups', function () {
    $otherUser = User::factory()->create();
    $group = ApplicationGroup::factory()->create(['user_id' => $otherUser->id]);
    $application = Application::factory()->create(['user_id' => $this->user->id]);

    $response = $this->postJson("/api/application-groups/{$group->id}/applications", [
        'application_id' => $application->id,
    ]);

    $response->assertForbidden();
});

test('deleting group removes it from applications', function () {
    $group = ApplicationGroup::factory()->create(['user_id' => $this->user->id]);
    $application = Application::factory()->create([
        'user_id' => $this->user->id,
        'application_group_id' => $group->id,
    ]);

    $this->deleteJson("/api/application-groups/{$group->id}");

    $this->assertDatabaseMissing('application_groups', [
        'id' => $group->id,
    ]);

    // Application should still exist but with null group
    $this->assertDatabaseHas('applications', [
        'id' => $application->id,
        'application_group_id' => null,
    ]);
});
