<?php

use App\Models\User;

use Pest\Laravel\getJson;


test('should return a list of users', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $response = $this->actingAs($user)->getJson('/api/users');

    $response->assertStatus(200)->assertJsonStructure([
        'data' => [
            '*' => [
                'id',
                'first_name',
                'last_name',
                'email',
                'office',
                'designation',
                'department',
                'role',
            ]
        ]
    ]);
});


test('non-admin user should not be able to access user list', function () {
    $user = User::factory()->create();
    $user->assignRole('staff');
    
    $response = $this->actingAs($user)->getJson('/api/users');
    $response->assertStatus(403);
});

?>