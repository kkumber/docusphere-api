<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('users can authenticate using the login screen', function () {
    $password = 'password';
    $user = User::factory()->create([
        'password' => Hash::make($password),
        'email_verified_at' => now(),
        'status' => 1
    ]);

    $response = $this->postJson('/login', [
        'email' => $user->email,
        'password' => $password,
    ]);


    $this->assertAuthenticated();
    $response->assertOk();
});

test('users can not authenticate with deactivated account', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'status' => 0
    ]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertGuest();
    $response->assertJsonStructure([
        'message',
    ]);
});

test('users can not authenticate with unverified email', function () {
    $user = User::factory()->create([
        'email_verified_at' => null,
        'status' => 1
    ]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertGuest();
    $response->assertJsonStructure([
        'message',
    ]);
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertNoContent();
});
