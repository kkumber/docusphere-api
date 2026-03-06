<?php

use App\Enums\RequestType;
use App\Models\Document;
use App\Models\DocumentFile;
use App\Models\Status;
use App\Services\DocumentService;
use Illuminate\Http\UploadedFile;
use App\Models\User;
use App\Services\CloudinaryService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * @var User $user
 * @var Document $document
 * @var UploadedFile $file
 * @var string $folder
 */
beforeEach(function () {
    // User uploader
    $this->user = User::factory()->create();
    $this->user->assignRole('records');

    // Document
    $this->document = [
        'tracking_no' => 'Random',
        'title' => 'Title',
        'instructions' => 'Instructions here',
        'category' => 'Memorandum',
        'originating_office' => 'Administrative',
        'request_type' => RequestType::FOR_SIGNATURE->value,
        'uploaded_by' => $this->user->id,
        'status_id' => Status::DOC_PENDING,
        'due_date' => '2023-01-01',
    ];
    
    // File
    $this->file = UploadedFile::fake()->create('document.pdf');
    
    // Folder
    $this->folder = 'documents/' . $this->document['category'];
});


test('save document with file upload', function () {
    $currentYear = date('Y');
    $cloudinary = $this->mock(CloudinaryService::class);
    $cloudinary->shouldReceive('uploadToCloudinary')->with($this->file, $this->folder)->once()->andReturn('public_id');

    $cloudinary->shouldReceive('destroyFromCloudinary')->with(['public_id'])->never();

    $service = new DocumentService($cloudinary);
    $result = $service->saveDocumentWithFileUpload($this->document, $this->user->id, $this->folder, $this->file);

    expect($result['document'])->toBeInstanceOf(Document::class);
    expect($result['file'])->toBeInstanceOf(DocumentFile::class);
    
    $formatTrackingNo = strtoupper("DM NO. {$result['document']->id}, S. {$currentYear} {$this->document['title']}");
    $this->assertDatabaseHas('documents', [
        'id' => $result['document']->id,
        'uploaded_by' => $this->user->id,
        'tracking_no' => $formatTrackingNo,
        'status_id' => Status::DOC_PENDING
    ]);

    $this->assertDatabaseHas('document_files', [
        'document_id' => $result['document']->id,
    ]);
});

test('throw exception when upload to cloudinary fails', function () {
    $cloudinary = $this->mock(CloudinaryService::class);
    $cloudinary->shouldReceive('uploadToCloudinary')->with($this->file, $this->folder)->once()->andThrow(\Exception::class);
    
    $cloudinary->shouldReceive('destroyFromCloudinary')->with(['public_id'])->never();

    $service = new DocumentService($cloudinary);
    $this->expectException(RuntimeException::class);
    $service->saveDocumentWithFileUpload($this->document, $this->user->id, $this->folder, $this->file);

    $this->assertDatabaseMissing('documents', [
        'id' => $this->document->id,
    ]);

    $this->assertDatabaseMissing('document_files', [
        'document_id' => $this->document->id,
    ]);
    
});

test('throw exception when saving document fails', function () {
    $wrongDocument = [
        'tracking_no' => null,
        'title' => 'Title',
        'instructions' => 'Instructions here',
        'category' => 'Memorandumssss',
        'originating_office' => 'Office',
        'request_type' => RequestType::FOR_SIGNATURE->value,
        'uploaded_by' => $this->user->id,
        'status_id' => Status::DOC_PENDING,
        'due_date' => '2023-01-01',
    ];

    $cloudinary = $this->spy(CloudinaryService::class);
    $cloudinary->shouldReceive('uploadToCloudinary')
    ->with($this->file, $this->folder)
    ->once()
    ->andReturn('public_id');
    
    $service = new DocumentService($cloudinary);
    
    expect(fn () => $service->saveDocumentWithFileUpload($wrongDocument, $this->user->id, $this->folder, $this->file))->toThrow(\Exception::class);
    
    $this->assertDatabaseMissing('documents', [
        'title' => $wrongDocument['title'],
    ]);

    $this->assertDatabaseMissing('document_files', [
        'file_name' => Hash::make($this->file->getClientOriginalName()),
    ]);
});

?>