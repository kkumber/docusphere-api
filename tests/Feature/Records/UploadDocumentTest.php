<?php

use App\Models\User;
use App\Enums\CategoryType;
use Illuminate\Http\UploadedFile;
use App\Models\Document;
use App\Enums\RequestType;
use App\Services\CloudinaryService;
use Mockery\MockInterface;


test('non-authorize users cannot upload documents', function () {

    $response = $this->postJson('/api/record/documents', [
        'title' => 'Test Document',
        'instructions' => 'This is a test document',
        'due_date' => now()->addDays(7)->toDateString(),
        'file' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
    ]);

    $response->assertUnauthorized();
});

test('non-records cannot upload official documents', function () {
    $user = User::factory()->create();
    $user->assignRole('staff');

    $response = $this->actingAs($user)->postJson('/api/record/documents', [
        'title' => 'Test',
        'due_date' => now()->addDays(7)->toDateString(),
        'file' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
    ]);

    $response->assertForbidden();
});

test('records can upload official documents', function () {
    $user = User::factory()->create();
    $user->assignRole('records');

    $this->mock(CloudinaryService::class, function (MockInterface $mock) {
        $mock->shouldReceive('uploadToCloudinary')
            ->once()
            ->andReturn('mocked_public_id');
    });

    $response = $this->actingAs($user)->postJson('/api/record/documents', [
        'tracking_no' => 'Official Document',
        'title' => 'Test Document',
        'instructions' => 'This is a test document',
        'due_date' => now()->addDays(7)->toDateString(),
        'category' => CategoryType::MEMORANDUM->value,
        'request_type' => 'for_signature',
        'originating_office' => 'Test Office',
        'file' => UploadedFile::fake()->create('document.pdf'),
    ]);

    $response->assertSuccessful();

    $this->assertDatabaseHas('documents', [
        'title' => 'TEST DOCUMENT',
        'category' => CategoryType::MEMORANDUM->value,
        'uploaded_by' => $user->id,
    ]);

    $trackingNo = $response['data']['document']['tracking_no'];
    expect($trackingNo)->toContain('DM NO');
});


?>