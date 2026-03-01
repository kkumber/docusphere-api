<?php

use App\Models\User;

beforeEach(function () {
   $this->records = User::factory()->create();
   $this->records->assignRole('records'); 
});

test('returns a list of documents', function () {
    $response = $this->actingAs($this->records)->getJson('/api/record/documents');
    $response->assertOk();
});

test('non-records cannot see documents', function () {
    $user = User::factory()->create();
    $user->assignRole('staff');
    
    $response = $this->actingAs($user)->getJson('/api/record/documents');
    $response->assertForbidden();
});

?>