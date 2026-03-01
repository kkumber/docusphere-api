<?php

use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});


test('admin can activate user', function () {
    $user = User::factory()->create([
        'status' => 0
    ]);
    $user->assignRole('staff');
    
    $response = $this->actingAs($this->admin)->patchJson('/api/users/' . $user->id . '/activate');
    
    $response->assertOk();
    
    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'status' => 1
    ]);
});

test('admin cannot activate other admins', function () {
    $user = User::factory()->create([
        'status' => 0
    ]);
    $user->assignRole('admin');
    
    $response = $this->actingAs($this->admin)->patchJson('/api/users/' . $user->id . '/activate');
    
    $response->assertForbidden();
    
    $this->assertDatabaseMissing('users', [
        'id' => $user->id,
        'status' => 1
    ]);
});

test('superadmin can activate other admins', function () {
    $superAdmin = User::where('email', 'docusphere@admin.com')->first();
    
    $user = User::factory()->create([
        'status' => 0
    ]);
    $user->assignRole('admin');
    
    $response = $this->actingAs($superAdmin)->patchJson('/api/users/' . $user->id . '/activate');
    
    $response->assertOk();
    
    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'status' => 1
    ]);
});

test('admin cannot activate already activated user', function () {
    $user = User::factory()->create([
        'status' => 1
    ]);
    $user->assignRole('staff');
    
    $response = $this->actingAs($this->admin)->patchJson('/api/users/' . $user->id . '/activate');
    
    $response->assertBadRequest()->assertJsonStructure([
        'message'
    ]);
});

?>