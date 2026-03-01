<?php

use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

test('admin can bulk register users', function () {
    $users = ['users' => [
        [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'O9Ko111M@example.com',
            'office' => 'New York',
            'designation' => 'Manager',
            'department' => 'HR',
            'password' => 'password',
            'role' => 'chief',
        ],
        [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'O9Ko111F@example.com',
            'office' => 'New York',
            'designation' => 'Staff',
            'department' => 'Accounting',
            'password' => 'password',
            'role' => 'staff',
        ],
        [
            'first_name' => 'Bob',
            'last_name' => 'Smith',
            'email' => 'BobSmithF@example.com',
            'office' => 'Metro Manila',
            'designation' => 'Oke',
            'department' => 'Administrative',
            'password' => 'password',
            'role' => 'sds',
        ],
        [
            'first_name' => 'Hallow',
            'last_name' => 'Men',
            'email' => 'admin@example.com',
            'office' => 'New York',
            'designation' => 'Admin',
            'department' => 'Office',
            'password' => 'password',
            'role' => 'admin',
        ]
    ]];

    $response = $this->actingAs($this->admin)
        ->postJson('/api/users/bulk-register', $users);
    
    $response->assertOk();

    $usersArray = $users['users'];
    
    foreach ($usersArray as $user) {
        $this->assertDatabaseHas('users', [
            'email' => $user['email'],
        ]);

        $user = User::where('email', $user['email'])->first();
        expect($user->hasRole($user['role']))->toBeTrue();
    };
});

test('system registers null for empty department and designation', function () {
    $users = ['users' => [
        [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'O9Ko111M@example.com',
            'office' => 'New York',
            'password' => 'password',
            'role' => 'chief',
        ],
        [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'O9Ko111F@example.com',
            'office' => 'New York',
            'password' => 'password',
            'role' => 'staff',
        ],
        [
            'first_name' => 'Bob',
            'last_name' => 'Smith',
            'email' => 'BobSmithF@example.com',
            'office' => 'Metro Manila',
            'password' => 'password',
            'role' => 'sds',
        ],
        [
            'first_name' => 'Hallow',
            'last_name' => 'Men',
            'email' => 'admin@example.com',
            'office' => 'New York',
            'password' => 'password',
            'role' => 'admin',
        ]
    ]];

    $response = $this->actingAs($this->admin)
        ->postJson('/api/users/bulk-register', $users);
    
    $response->assertOk();

    $usersArray = $users['users'];
    
    foreach ($usersArray as $user) {
        $this->assertDatabaseHas('users', [
            'email' => $user['email'],
            'department' => null,
            'designation' => null
        ]);

        $user = User::where('email', $user['email'])->first();
        expect($user->hasRole($user['role']))->toBeTrue();
    };
});

test('system returns error if email is invalid', function () {
    $users = ['users' => [
        [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'johndoe',
            'office' => 'New York',
            'password' => 'password',
            'role' => 'chief',
        ],
    ]];

    $response = $this->actingAs($this->admin)
        ->postJson('/api/users/bulk-register', $users);
    
    $response->assertStatus(422);

    $this->assertDatabaseMissing('users', [
        'email' => 'johndoe',
    ]);
});

test('system rollbacks if duplicate emails', function () {
    $users = ['users' => [
        [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'O9Ko111M@example.com',
            'office' => 'New York',
            'password' => 'password',
            'role' => 'chief',
        ],
        [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'O9Ko111M@example.com',
            'office' => 'New York',
            'password' => 'password',
            'role' => 'staff',
        ],
    ]];

    $response = $this->actingAs($this->admin)
        ->postJson('/api/users/bulk-register', $users);
    
    $response->assertStatus(500);

    $this->assertDatabaseMissing('users', [
        'email' => 'O9Ko111M@example.com',
    ]);
});

test('system returns error if role is invalid', function () {
    $users = ['users' => [
        [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'O9Ko111M@example.com',
            'office' => 'New York',
            'password' => 'password',
            'role' => 'invalid',
        ],
    ]];

    $response = $this->actingAs($this->admin)
        ->postJson('/api/users/bulk-register', $users);
    
    $response->assertStatus(422);

    $this->assertDatabaseMissing('users', [
        'email' => 'O9Ko111M@example.com',
    ]);
});

test('system returns error if fields are empty', function () {
    $users = ['users' => [
        [
            'first_name' => '',
            'last_name' => '',
            'email' => '',
            'office' => '',
            'password' => '',
            'role' => '',
        ],
    ]];

    $response = $this->actingAs($this->admin)
        ->postJson('/api/users/bulk-register', $users);
    
    $response->assertStatus(422);
});

test('non-admin cannot bulk register users', function () {
    $user = User::factory()->create();
    $user->assignRole('staff');
    
    $response = $this->actingAs($user)->postJson('/api/users/bulk-register', []);
    
    $response->assertForbidden();
});


?>