<?php

test('new users can register', function () {
    $response = $this->post('/register', [
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'test@example.com',
        'office' => 'Test Office',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->dump();

    $response->assertStatus(302);
    // $this->assertAuthenticated();
    // $response->assertNoContent();
});
