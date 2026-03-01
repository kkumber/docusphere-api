<?php

use App\Models\Document;
use App\Models\Status;
use App\Models\User;

beforeEach(function () {
    $this->records = User::factory()->create();
    $this->records->assignRole('records');
});

test('records can archive completed documents', function () {
    $document = Document::factory()->create([
        'status_id' => Status::DOC_COMPLETED,
        'uploaded_by' => $this->records->id
    ]);

    $response = $this->actingAs($this->records)->patchJson('/api/record/documents/' . $document->id . '/archive');
    $response->assertOk();

    $this->assertDatabaseHas('documents', [
        'id' => $document->id,
        'status_id' => Status::DOC_ARCHIVED
    ]);
});

test('records can archive rejected documents', function () {
    $document = Document::factory()->create([
        'status_id' => Status::DOC_REJECTED,
        'uploaded_by' => $this->records->id
    ]);

    $response = $this->actingAs($this->records)->patchJson('/api/record/documents/' . $document->id . '/archive');
    $response->assertOk();

    $this->assertDatabaseHas('documents', [
        'id' => $document->id,
        'status_id' => Status::DOC_ARCHIVED
    ]);
});

test('records cannot archive an archived document', function () {
    $document = Document::factory()->create([
        'status_id' => Status::DOC_ARCHIVED,
        'uploaded_by' => $this->records->id
    ]);

    $response = $this->actingAs($this->records)->patchJson('/api/record/documents/' . $document->id . '/archive');
    $response->assertBadRequest();

    $this->assertDatabaseHas('documents', [
        'id' => $document->id,
        'status_id' => Status::DOC_ARCHIVED
    ]);
});

test('records cannot archive a document that is not completed or rejected', function () {
    $document = Document::factory()->create([
        'status_id' => Status::DOC_RELEASED,
        'uploaded_by' => $this->records->id
    ]);

    $response = $this->actingAs($this->records)->patchJson('/api/record/documents/' . $document->id . '/archive');
    $response->assertBadRequest();

    $this->assertDatabaseHas('documents', [
        'id' => $document->id,
        'status_id' => Status::DOC_RELEASED
    ]);
});

test('non-records cannot archive documents', function () {
    $document = Document::factory()->create([
        'status_id' => Status::DOC_COMPLETED,
        'uploaded_by' => $this->records->id
    ]);

    $user = User::factory()->create();
    $user->assignRole('staff');
    
    $response = $this->actingAs($user)->patchJson('/api/record/documents/' . $document->id . '/archive');
    $response->assertForbidden();

    $this->assertDatabaseHas('documents', [
        'id' => $document->id,
        'status_id' => Status::DOC_COMPLETED
    ]);
});

?>