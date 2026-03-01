<?php

use App\Models\User;



beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

test('admin can see user details', function () {
    $user = User::factory()->create();
    $user->assignRole('staff');
   
    $response = $this->actingAs($this->admin)->getJson('/api/users/' . $user->id);
    $response->assertOk()->assertJsonStructure([
        'message',
        'data' => [
            'id',
            'first_name',
            'last_name',
            'email',
            'office',
            'designation',
            'department',
            'role',
            'created_at',
            'updated_at',
        ],
    ]);
});

test('admin cannot see other admin user details', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');
    
    $response = $this->actingAs($this->admin)->getJson('/api/users/' . $user->id);
    $response->assertForbidden();
});

test('superadmin can see other admin user details', function () {
    $superAdmin = User::where('email', 'docusphere@admin.com')->first();

    $user = User::factory()->create();
    $user->assignRole('admin');
    
    $response = $this->actingAs($superAdmin)->getJson('/api/users/' . $user->id);
    $response->assertOk()->assertJsonStructure([
        'message',
        'data' => [
            'id',
            'first_name',
            'last_name',
            'email',
            'office',
            'designation',
            'department',
            'role',
            'created_at',
            'updated_at',
        ]
    ]);
});

test('admin can update user details', function () {
    $user = User::factory()->create();
    $user->assignRole('staff');

    $response = $this->actingAs($this->admin)->putJson('/api/users/' . $user->id, [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'O9KoM@example.com',
        'office' => 'New York',
        'designation' => 'Manager',
        'department' => 'HR',
        'role' => 'chief',
    ]);

    $response->assertOk();

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'O9KoM@example.com',
        'office' => 'New York',
        'designation' => 'Manager',
        'department' => 'HR',
    ]);

    expect($user->fresh()->hasRole('chief'))->toBe(true);
});

test('superadmin can update other admin user details', function () {
    $superAdmin = User::where('email', 'docusphere@admin.com')->first();

    $user = User::factory()->create();
    $user->assignRole('admin');
    
    $response = $this->actingAs($superAdmin)->putJson('/api/users/' . $user->id, [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'O9Ko111M@example.com',
        'office' => 'New York',
        'designation' => 'Manager',
        'department' => 'HR',
        'role' => 'chief',
    ]);

    $response->assertOk();

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'O9Ko111M@example.com',
        'office' => 'New York',
        'designation' => 'Manager',
        'department' => 'HR',
    ]);

    expect($user->fresh()->hasRole('chief'))->toBe(true);
});

test('non-superadmin cannot update other admin user details', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $user = User::factory()->create();
    $user->assignRole('admin');
    
    $response = $this->actingAs($admin)->putJson('/api/users/' . $user->id, [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'O9Ko111M@example.com',
        'office' => 'New York',
        'designation' => 'Manager',
        'department' => 'HR',
        'role' => 'chief',
    ]);

    $response->assertForbidden();
    
    $this->assertDatabaseMissing('users', [
        'id' => $user->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'O9Ko111M@example.com',
        'office' => 'New York',
        'designation' => 'Manager',
        'department' => 'HR',
    ]);

    expect($user->fresh()->hasRole('chief'))->toBe(false);


});


?>