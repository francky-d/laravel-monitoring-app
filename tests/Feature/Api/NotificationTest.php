<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user);
});

test('user can get notification settings', function () {
    $response = $this->getJson('/api/user/notification-settings');

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'email_notifications',
                'slack_webhook_url',
                'discord_webhook_url',
                'teams_webhook_url',
                'default_notification_channels',
            ],
        ])
        ->assertJson([
            'success' => true,
            'message' => 'Notification settings retrieved successfully',
        ]);
});

test('user can update notification settings', function () {
    $settings = [
        'email_notifications' => true,
        'slack_webhook_url' => 'https://hooks.slack.com/services/test',
        'discord_webhook_url' => 'https://discord.com/api/webhooks/test',
        'teams_webhook_url' => 'https://teams.microsoft.com/webhook/test',
        'default_notification_channels' => ['email', 'slack'],
    ];

    $response = $this->putJson('/api/user/notification-settings', $settings);

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'email_notifications',
                'slack_webhook_url',
                'discord_webhook_url',
                'teams_webhook_url',
                'default_notification_channels',
            ],
        ])
        ->assertJson([
            'success' => true,
            'message' => 'Notification settings updated successfully',
        ]);

    // Check that settings are persisted (assuming they're stored in user profile)
    $this->user->refresh();
    expect($this->user->email_notifications)->toBe(true);
});

test('user can test email notification', function () {
    $response = $this->postJson('/api/user/test-notification/email');

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Test email notification sent successfully',
        ]);
});

test('user can test slack notification', function () {
    // Set up slack webhook URL first
    $this->user->update(['slack_webhook_url' => 'https://hooks.slack.com/services/test']);

    $response = $this->postJson('/api/user/test-notification/slack');

    $response->assertOk()
        ->assertJson([
            'status' => 'success',
            'message' => 'Test slack notification sent successfully',
            'data' => [
                'success' => true,
            ],
        ]);
});

test('user can test discord notification', function () {
    // Set up discord webhook URL first
    $this->user->update(['discord_webhook_url' => 'https://discord.com/api/webhooks/test']);

    $response = $this->postJson('/api/user/test-notification/discord');

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Test discord notification sent successfully',
        ]);
});

test('user can test teams notification', function () {
    // Set up teams webhook URL first
    $this->user->update(['teams_webhook_url' => 'https://teams.microsoft.com/webhook/test']);

    $response = $this->postJson('/api/user/test-notification/teams');

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Test teams notification sent successfully',
        ]);
});

test('test notification fails for invalid channel', function () {
    $response = $this->postJson('/api/user/test-notification/invalid_channel');

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['channel']);
});

test('test notification fails when webhook url is missing', function () {
    $response = $this->postJson('/api/user/test-notification/slack');

    $response->assertBadRequest()
        ->assertJson([
            'success' => false,
            'message' => 'Slack webhook URL not configured',
        ]);
});

test('notification settings validation', function () {
    $response = $this->putJson('/api/user/notification-settings', [
        'email_notifications' => 'invalid',
        'slack_webhook_url' => 'invalid-url',
        'default_notification_channels' => ['invalid_channel'],
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors([
            'email_notifications',
            'slack_webhook_url',
            'default_notification_channels.0',
        ]);
});

test('webhook urls must be valid when provided', function () {
    $response = $this->putJson('/api/user/notification-settings', [
        'slack_webhook_url' => 'not-a-url',
        'discord_webhook_url' => 'also-not-a-url',
        'teams_webhook_url' => 'definitely-not-a-url',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors([
            'slack_webhook_url',
            'discord_webhook_url',
            'teams_webhook_url',
        ]);
});

test('default notification channels must be valid', function () {
    $response = $this->putJson('/api/user/notification-settings', [
        'default_notification_channels' => ['email', 'invalid_channel', 'slack'],
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['default_notification_channels.1']);
});

test('user can disable email notifications', function () {
    $response = $this->putJson('/api/user/notification-settings', [
        'email_notifications' => false,
    ]);

    $response->assertOk();
    
    $this->user->refresh();
    expect($this->user->email_notifications)->toBe(false);
});

test('user can clear webhook urls', function () {
    // First set some webhook URLs
    $this->user->update([
        'slack_webhook_url' => 'https://hooks.slack.com/services/test',
        'discord_webhook_url' => 'https://discord.com/api/webhooks/test',
    ]);

    // Now clear them
    $response = $this->putJson('/api/user/notification-settings', [
        'slack_webhook_url' => null,
        'discord_webhook_url' => null,
    ]);

    $response->assertOk();
    
    $this->user->refresh();
    expect($this->user->slack_webhook_url)->toBeNull();
    expect($this->user->discord_webhook_url)->toBeNull();
});
