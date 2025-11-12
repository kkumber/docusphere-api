<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentVersion extends Model
{
    /** @use HasFactory<\Database\Factories\DocumentVersionFactory> */
    use HasFactory;

    protected $fillable = [
        'document_id',
        'uploaded_by',
        'version_number',
        'previous_version_id',
        'modification_type',
        'file_name',
        'file_path',
        'mime_type',
        'file_size',
    ];

public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function previousVersion()
    {
        return $this->belongsTo(DocumentVersion::class, 'previous_version_id');
    }
}
