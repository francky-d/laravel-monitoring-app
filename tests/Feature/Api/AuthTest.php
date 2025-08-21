<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('user can register', function () {
    $response = $this->postJson('/api/auth/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'user' => ['id', 'name', 'email'],
                'token',
            ],
        ])
        ->assertJson([
            'success' => true,
            'message' => 'User registered successfully',
        ]);

    $this->assertDatabaseHas('users', [
        'email' => 'test@example.com',
    ]);
});

test('user can login', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'user' => ['id', 'name', 'email'],
                'token',
            ],
        ])
        ->assertJson([
            'success' => true,
            'message' => 'Login successful',
        ]);
});

test('user can logout', function () {
    Sanctum::actingAs($this->user);

    $response = $this->postJson('/api/auth/logout');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
        ])
        ->assertJson([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
});

test('authenticated user can get profile', function () {
    Sanctum::actingAs($this->user);

    $response = $this->getJson('/api/auth/user');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => ['id', 'name', 'email'],
        ])
        ->assertJson([
            'success' => true,
            'message' => 'User profile retrieved successfully',
        ]);
});
