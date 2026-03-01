<?php

use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});


test('admin can deactivate user', function () {
    $user = User::factory()->create([
        'status' => 1
    ]);
    $user->assignRole('staff');
    
    $response = $this->actingAs($this->admin)->patchJson('/api/users/' . $user->id . '/deactivate');
    
    $response->assertOk();
    
    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'status' => 0
    ]);
});

test('admin cannot deactivate other admins', function () {
    $user = User::factory()->create([
        'status' => 1
    ]);
    $user->assignRole('admin');
    
    $response = $this->actingAs($this->admin)->patchJson('/api/users/' . $user->id . '/deactivate');
    
    $response->assertForbidden();
    
    $this->assertDatabaseMissing('users', [
        'id' => $user->id,
        'status' => 0
    ]);
});

test('superadmin can deactivate other admins', function () {
    $superAdmin = User::where('email', 'docusphere@admin.com')->first();
    
    $user = User::factory()->create([
        'status' => 1
    ]);
    $user->assignRole('admin');
    
    $response = $this->actingAs($superAdmin)->patchJson('/api/users/' . $user->id . '/deactivate');
    
    $response->assertOk();
    
    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'status' => 0
    ]);
});

test('admin cannot deactivate already deactivated user', function () {
    $user = User::factory()->create([
        'status' => 0
    ]);
    $user->assignRole('staff');
    
    $response = $this->actingAs($this->admin)->patchJson('/api/users/' . $user->id . '/deactivate');
    
    $response->assertBadRequest()->assertJsonStructure([
        'message'
    ]);
});

?>