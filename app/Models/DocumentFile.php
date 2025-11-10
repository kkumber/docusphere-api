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
        'file_path',
        'mime_type',
        'file_size',
        'created_by',
        'is_primary',
    ];

    public function document()
    {
        return $this->belongsTo(Document::class);
    }
}
