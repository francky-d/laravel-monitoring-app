<?php

use App\Models\Application;
use App\Models\ApplicationGroup;
use App\Models\Incident;
use App\Models\User;
use App\Policies\IncidentPolicy;

beforeEach(function () {
    $this->policy = new IncidentPolicy();
    $this->user = User::factory()->create();
    $this->otherUser = User::factory()->create();
    
    $this->applicationGroup = ApplicationGroup::factory()->create(['user_id' => $this->user->id]);
    $this->application = Application::factory()->create([
        'user_id' => $this->user->id,
        'application_group_id' => $this->applicationGroup->id,
    ]);
    
    $this->incident = Incident::factory()->create(['application_id' => $this->application->id]);
});

test('user can view any incidents', function () {
    expect($this->policy->viewAny($this->user))->toBeTrue();
    expect($this->policy->viewAny($this->otherUser))->toBeTrue();
});

test('user can view their own incident', function () {
    expect($this->policy->view($this->user, $this->incident))->toBeTrue();
});

test('user cannot view other users incident', function () {
    expect($this->policy->view($this->otherUser, $this->incident))->toBeFalse();
});

test('user can create incidents', function () {
    expect($this->policy->create($this->user))->toBeTrue();
    expect($this->policy->create($this->otherUser))->toBeTrue();
});

test('user can update their own incident', function () {
    expect($this->policy->update($this->user, $this->incident))->toBeTrue();
});

test('user cannot update other users incident', function () {
    expect($this->policy->update($this->otherUser, $this->incident))->toBeFalse();
});

test('user can delete their own incident', function () {
    expect($this->policy->delete($this->user, $this->incident))->toBeTrue();
});

test('user cannot delete other users incident', function () {
    expect($this->policy->delete($this->otherUser, $this->incident))->toBeFalse();
});

test('user can restore their own incident', function () {
    expect($this->policy->restore($this->user, $this->incident))->toBeTrue();
});

test('user cannot restore other users incident', function () {
    expect($this->policy->restore($this->otherUser, $this->incident))->toBeFalse();
});

test('user can force delete their own incident', function () {
    expect($this->policy->forceDelete($this->user, $this->incident))->toBeTrue();
});

test('user cannot force delete other users incident', function () {
    expect($this->policy->forceDelete($this->otherUser, $this->incident))->toBeFalse();
});

test('policy checks application ownership correctly', function () {
    // Create another user's application and incident
    $otherApplicationGroup = ApplicationGroup::factory()->create(['user_id' => $this->otherUser->id]);
    $otherApplication = Application::factory()->create([
        'user_id' => $this->otherUser->id,
        'application_group_id' => $otherApplicationGroup->id,
    ]);
    $otherIncident = Incident::factory()->create(['application_id' => $otherApplication->id]);

    // Original user should not be able to access other user's incident
    expect($this->policy->view($this->user, $otherIncident))->toBeFalse();
    expect($this->policy->update($this->user, $otherIncident))->toBeFalse();
    expect($this->policy->delete($this->user, $otherIncident))->toBeFalse();

    // Other user should be able to access their own incident
    expect($this->policy->view($this->otherUser, $otherIncident))->toBeTrue();
    expect($this->policy->update($this->otherUser, $otherIncident))->toBeTrue();
    expect($this->policy->delete($this->otherUser, $otherIncident))->toBeTrue();
});
