<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    /** @use HasFactory<\Database\Factories\DocumentFactory> */
    use HasFactory;

    protected $fillable = [
        'tracking_no',
        'title',
        'instructions',
        'category',
        'originating_office',
        'request_type',
        'user_id',
        'status_id',
        'due_date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function documentFiles()
    {
        return $this->hasMany(DocumentFile::class);
    }

    public function DocResponseFiles() 
    {
        return $this->hasMany(DocResponseFile::class);
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    public function documentTracking()
    {
        return $this->hasMany(DocumentTracking::class);
    }
}
