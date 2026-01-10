<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentFile extends Model
{
    /** @use HasFactory<\Database\Factories\DocumentFileFactory> */
    use HasFactory;

    protected $fillable = [
        'document_id',
        'file_name',
        'public_id',
        'mime_type',
        'file_size',
        'uploaded_by',
        'is_primary',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function document()
    {
        return $this->belongsTo(Document::class);
    }
}
