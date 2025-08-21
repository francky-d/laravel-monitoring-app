<?php

use App\Models\Application;
use App\Models\ApplicationGroup;
use App\Models\Incident;
use App\Models\Subscription;
use App\Models\User;
use App\Enums\IncidentSeverity;
use App\Enums\IncidentStatus;

test('user factory creates valid user', function () {
    $user = User::factory()->create();

    expect($user->name)->toBeString();
    expect($user->email)->toBeString()->toContain('@');
    expect($user->password)->toBeString();
    expect($user->email_verified_at)->not()->toBeNull();
});

test('application group factory creates valid group', function () {
    $group = ApplicationGroup::factory()->create();

    expect($group->name)->toBeString();
    expect($group->user_id)->toBeInt();
    expect($group->user)->toBeInstanceOf(User::class);
});

test('application group factory states work correctly', function () {
    $production = ApplicationGroup::factory()->production()->create();
    $staging = ApplicationGroup::factory()->staging()->create();
    $development = ApplicationGroup::factory()->development()->create();

    expect($production->name)->toBe('Production');
    expect($staging->name)->toBe('Staging');
    expect($development->name)->toBe('Development');
});

test('application factory creates valid application', function () {
    $application = Application::factory()->create();

    expect($application->name)->toBeString();
    expect($application->url)->toBeString();
    expect($application->expected_http_code)->toBeInt();
    expect($application->monitoring_interval)->toBeInt();
    expect($application->user_id)->toBeInt();
    expect($application->application_group_id)->toBeString();
    expect($application->user)->toBeInstanceOf(User::class);
    expect($application->applicationGroup)->toBeInstanceOf(ApplicationGroup::class);
});

test('application factory states work correctly', function () {
    $app = Application::factory()->withInterval(30)->create();
    expect($app->monitoring_interval)->toBe(30);

    $app = Application::factory()->expectsCode(201)->create();
    expect($app->expected_http_code)->toBe(201);
});

test('incident factory creates valid incident', function () {
    $incident = Incident::factory()->create();

    expect($incident->title)->toBeString();
    expect($incident->description)->toBeString();
    expect($incident->severity)->toBeInstanceOf(IncidentSeverity::class);
    expect($incident->status)->toBeInstanceOf(IncidentStatus::class);
    expect($incident->application_id)->toBeString();
    expect($incident->application)->toBeInstanceOf(Application::class);
});

test('incident factory states work correctly', function () {
    $openIncident = Incident::factory()->open()->create();
    expect($openIncident->status)->toBe(IncidentStatus::OPEN);
    expect($openIncident->resolved_at)->toBeNull();

    $resolvedIncident = Incident::factory()->resolved()->create();
    expect($resolvedIncident->status)->toBe(IncidentStatus::RESOLVED);
    expect($resolvedIncident->resolved_at)->not()->toBeNull();

    $criticalIncident = Incident::factory()->critical()->create();
    expect($criticalIncident->severity)->toBe(IncidentSeverity::CRITICAL);

    $highIncident = Incident::factory()->high()->create();
    expect($highIncident->severity)->toBe(IncidentSeverity::HIGH);

    $lowIncident = Incident::factory()->low()->create();
    expect($lowIncident->severity)->toBe(IncidentSeverity::LOW);
});

test('subscription factory creates valid subscription', function () {
    $subscription = Subscription::factory()->create();

    expect($subscription->user_id)->toBeInt();
    expect($subscription->notification_channels)->toBeArray();
    expect($subscription->subscribable_type)->toBeString();
    expect($subscription->subscribable_id)->toBeString();
    expect($subscription->user)->toBeInstanceOf(User::class);
});

test('subscription factory states work correctly', function () {
    $application = Application::factory()->create();
    $group = ApplicationGroup::factory()->create();

    $appSubscription = Subscription::factory()->forApplication($application)->create();
    expect($appSubscription->subscribable_type)->toBe(Application::class);
    expect($appSubscription->subscribable_id)->toBe($application->id);

    $groupSubscription = Subscription::factory()->forApplicationGroup($group)->create();
    expect($groupSubscription->subscribable_type)->toBe(ApplicationGroup::class);
    expect($groupSubscription->subscribable_id)->toBe($group->id);

    $emailOnly = Subscription::factory()->emailOnly()->create();
    expect($emailOnly->notification_channels)->toBe(['email']);

    $slack = Subscription::factory()->slack()->create();
    expect($slack->notification_channels)->toContain('slack');
    expect($slack->notification_channels)->toContain('email'); // Email is always included
    expect($slack->webhook_url)->toContain('hooks.slack.com');
});

test('factories create related models correctly', function () {
    $incident = Incident::factory()->create();

    // Should have created application and related models
    expect($incident->application)->toBeInstanceOf(Application::class);
    expect($incident->application->user)->toBeInstanceOf(User::class);
    expect($incident->application->applicationGroup)->toBeInstanceOf(ApplicationGroup::class);
});

test('subscription factory with custom channels', function () {
    $channels = ['email', 'slack', 'discord'];
    $subscription = Subscription::factory()->withChannels($channels)->create();

    expect($subscription->notification_channels)->toBe($channels);
});

test('subscription factory with webhook', function () {
    $url = 'https://custom.webhook.url';
    $subscription = Subscription::factory()->withWebhook($url)->create();

    expect($subscription->webhook_url)->toBe($url);
});

test('application group factory with custom name and description', function () {
    $name = 'Custom Group';
    $description = 'Custom description';
    
    $group = ApplicationGroup::factory()
        ->named($name)
        ->withDescription($description)
        ->create();

    expect($group->name)->toBe($name);
    expect($group->description)->toBe($description);
});

test('application factory with watch url', function () {
    $app = Application::factory()->withWatchUrl()->create();

    expect($app->url_to_watch)->not()->toBeNull();
    expect($app->url_to_watch)->toBeString();
});

test('factories respect relationships', function () {
    $user = User::factory()->create();
    $group = ApplicationGroup::factory()->create(['user_id' => $user->id]);
    $app = Application::factory()->create([
        'user_id' => $user->id,
        'application_group_id' => $group->id,
    ]);

    expect($app->user->id)->toBe($user->id);
    expect($app->applicationGroup->id)->toBe($group->id);
    expect($app->applicationGroup->user->id)->toBe($user->id);
});
