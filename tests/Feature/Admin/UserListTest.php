<?php

use App\Models\User;

use function Pest\Laravel\getJson;

describe('Admin User List', function () {
    it('should return a list of users', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');


        $response = $this->actingAs($user)->getJson('/api/admin/users');

        $response->assertStatus(200)->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'first_name',
                    'last_name',
                    'email',
                    'office',
                    'role',
                ]
            ]
        ]);
    });
});

?>