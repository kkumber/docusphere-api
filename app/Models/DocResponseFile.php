<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocResponseFile extends Model
{
    /** @use HasFactory<\Database\Factories\DocResponseFileFactory> */
    use HasFactory;

    protected $fillable = [
        'document_id',
        'uploaded_by',
        'file_name',
        'public_id',
        'folder',
        'mime_type',
        'file_size',
        'remarks'
    ];


    public function user() 
    {
        return $this->belongsTo(User::class);
    }

    public function document() 
    {
        return $this->belongsTo(Document::class);
    }
}
