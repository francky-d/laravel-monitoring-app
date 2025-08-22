<?php

use App\Models\Application;
use App\Models\ApplicationGroup;
use App\Models\Subscription;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

// Auto-subscriptions disabled in tests - see Application and ApplicationGroup models

test('user can create subscription for application', function () {
    $testUser = User::factory()->create();
    $testGroup = ApplicationGroup::factory()->create(['user_id' => $testUser->id]);
    $testApplication = Application::factory()->create([
        'user_id' => $testUser->id,
        'application_group_id' => $testGroup->id,
    ]);
    
    Sanctum::actingAs($testUser);

    $subscriptionData = [
        'subscribable_type' => Application::class,
        'subscribable_id' => $testApplication->id,
        'notification_channels' => ['email', 'slack'],
        'webhook_url' => 'https://hooks.slack.com/services/test',
    ];

    $response = $this->postJson('/api/subscriptions', $subscriptionData);

    $response->assertCreated()
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'user_id',
                'notification_channels',
                'webhook_url',
                'subscribable_type',
                'subscribable_id',
            ],
        ])
        ->assertJson([
            'success' => true,
            'message' => 'Subscription created successfully',
        ]);

    $this->assertDatabaseHas('subscriptions', [
        'user_id' => $testUser->id,
        'subscribable_id' => $testApplication->id,
        'subscribable_type' => Application::class,
    ]);
});

test('user can create subscription for application group', function () {
    $testUser = User::factory()->create();
    $testGroup = ApplicationGroup::factory()->create(['user_id' => $testUser->id]);
    
    Sanctum::actingAs($testUser);
    
    $subscriptionData = [
        'subscribable_type' => ApplicationGroup::class,
        'subscribable_id' => $testGroup->id,
        'notification_channels' => ['email'],
    ];

    $response = $this->postJson('/api/subscriptions', $subscriptionData);

    $response->assertCreated()
        ->assertJson([
            'success' => true,
            'message' => 'Subscription created successfully',
        ]);

    $this->assertDatabaseHas('subscriptions', [
        'user_id' => $testUser->id,
        'subscribable_id' => $testGroup->id,
        'subscribable_type' => ApplicationGroup::class,
    ]);
});

test('user can list their subscriptions', function () {
    $testUser = User::factory()->create();
    $testGroup = ApplicationGroup::factory()->create(['user_id' => $testUser->id]);
    $testApp = Application::factory()->create(['user_id' => $testUser->id, 'application_group_id' => $testGroup->id]);
    Subscription::factory()->forApplication($testApp)->create(['user_id' => $testUser->id]);
    Subscription::factory()->forApplicationGroup($testGroup)->create(['user_id' => $testUser->id]);
    
    Sanctum::actingAs($testUser);

    $response = $this->getJson('/api/subscriptions');

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
                    'subscribable',
                ],
            ],
            'pagination',
        ])
        ->assertJson([
            'success' => true,
            'message' => 'Subscriptions retrieved successfully',
        ])
        ->assertJsonCount(2, 'data');
});

test('user can view their subscription', function () {
    $testUser = User::factory()->create();
    $testGroup = ApplicationGroup::factory()->create(['user_id' => $testUser->id]);
    $testApp = Application::factory()->create(['user_id' => $testUser->id, 'application_group_id' => $testGroup->id]);
    $subscription = Subscription::factory()->forApplication($testApp)->create(['user_id' => $testUser->id]);
    
    Sanctum::actingAs($testUser);

    $response = $this->getJson("/api/subscriptions/{$subscription->id}");

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'user_id',
                'notification_channels',
                'subscribable',
            ],
        ]);
});

test('user cannot view other users subscription', function () {
    $currentUser = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherApplicationGroup = ApplicationGroup::factory()->create(['user_id' => $otherUser->id]);
    $otherApplication = Application::factory()->create([
        'user_id' => $otherUser->id,
        'application_group_id' => $otherApplicationGroup->id,
    ]);
    $subscription = Subscription::factory()->forApplication($otherApplication)->create(['user_id' => $otherUser->id]);

    Sanctum::actingAs($currentUser);

    $response = $this->getJson("/api/subscriptions/{$subscription->id}");

    $response->assertForbidden();
});

test('user can update their subscription', function () {
    $testUser = User::factory()->create();
    $testGroup = ApplicationGroup::factory()->create(['user_id' => $testUser->id]);
    $testApp = Application::factory()->create(['user_id' => $testUser->id, 'application_group_id' => $testGroup->id]);
    $subscription = Subscription::factory()->forApplication($testApp)->create(['user_id' => $testUser->id]);
    
    Sanctum::actingAs($testUser);

    $updateData = [
        'notification_channels' => ['email', 'teams'],
        'webhook_url' => 'https://teams.microsoft.com/webhook/test',
    ];

    $response = $this->putJson("/api/subscriptions/{$subscription->id}", $updateData);

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'notification_channels',
                'webhook_url',
            ],
        ]);

    $this->assertDatabaseHas('subscriptions', [
        'id' => $subscription->id,
        'webhook_url' => 'https://teams.microsoft.com/webhook/test',
    ]);
});

test('user can delete their subscription', function () {
    $testUser = User::factory()->create();
    $testGroup = ApplicationGroup::factory()->create(['user_id' => $testUser->id]);
    $testApp = Application::factory()->create(['user_id' => $testUser->id, 'application_group_id' => $testGroup->id]);
    $subscription = Subscription::factory()->forApplication($testApp)->create(['user_id' => $testUser->id]);
    
    Sanctum::actingAs($testUser);

    $response = $this->deleteJson("/api/subscriptions/{$subscription->id}");

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Subscription deleted successfully',
        ]);

    $this->assertDatabaseMissing('subscriptions', [
        'id' => $subscription->id,
    ]);
});

test('user can test notification for subscription', function () {
    $testUser = User::factory()->create();
    $testGroup = ApplicationGroup::factory()->create(['user_id' => $testUser->id]);
    $testApp = Application::factory()->create(['user_id' => $testUser->id, 'application_group_id' => $testGroup->id]);
    $subscription = Subscription::factory()->emailOnly()->forApplication($testApp)->create(['user_id' => $testUser->id]);
    
    Sanctum::actingAs($testUser);

    $response = $this->postJson("/api/subscriptions/{$subscription->id}/test");

    // Debug code removed

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Test notification sent successfully',
        ]);
});

test('user can test slack notification', function () {
    $testUser = User::factory()->create();
    $testGroup = ApplicationGroup::factory()->create(['user_id' => $testUser->id]);
    $testApp = Application::factory()->create(['user_id' => $testUser->id, 'application_group_id' => $testGroup->id]);
    $subscription = Subscription::factory()->slack()->forApplication($testApp)->create(['user_id' => $testUser->id]);
    
    Sanctum::actingAs($testUser);

    $response = $this->postJson("/api/subscriptions/{$subscription->id}/test");

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Test notification sent successfully',
        ]);
});

test('subscription creation requires valid data', function () {
    $testUser = User::factory()->create();
    Sanctum::actingAs($testUser);
    
    $response = $this->postJson('/api/subscriptions', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['subscribable_type', 'subscribable_id', 'notification_channels']);
});

test('notification channels must be valid', function () {
    $testUser = User::factory()->create();
    $testGroup = ApplicationGroup::factory()->create(['user_id' => $testUser->id]);
    $testApplication = Application::factory()->create([
        'user_id' => $testUser->id,
        'application_group_id' => $testGroup->id,
    ]);
    
    Sanctum::actingAs($testUser);

    $response = $this->postJson('/api/subscriptions', [
        'subscribable_type' => Application::class,
        'subscribable_id' => $testApplication->id,
        'notification_channels' => ['invalid_channel'],
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['notification_channels.0']);
});

test('webhook url must be valid when provided', function () {
    $testUser = User::factory()->create();
    $testGroup = ApplicationGroup::factory()->create(['user_id' => $testUser->id]);
    $testApplication = Application::factory()->create([
        'user_id' => $testUser->id,
        'application_group_id' => $testGroup->id,
    ]);
    
    Sanctum::actingAs($testUser);

    $response = $this->postJson('/api/subscriptions', [
        'subscribable_type' => Application::class,
        'subscribable_id' => $testApplication->id,
        'notification_channels' => ['slack'],
        'webhook_url' => 'invalid-url',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['webhook_url']);
});

test('user cannot subscribe to other users resources', function () {
    $currentUser = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherApplicationGroup = ApplicationGroup::factory()->create(['user_id' => $otherUser->id]);
    $otherApplication = Application::factory()->create([
        'user_id' => $otherUser->id,
        'application_group_id' => $otherApplicationGroup->id,
    ]);
    
    Sanctum::actingAs($currentUser);

    $response = $this->postJson('/api/subscriptions', [
        'subscribable_type' => Application::class,
        'subscribable_id' => $otherApplication->id,
        'notification_channels' => ['email'],
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['subscribable_id']);
});
