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
        'uploaded_by',
        'status_id',
        'due_date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function documentFiles()
    {
        return $this->hasMany(DocumentFile::class);
    }

    public function documentAssignments()
    {
        return $this->hasMany(DocumentAssignment::class);
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    public function documentTrackings()
    {
        return $this->hasMany(DocumentTracking::class);
    }


    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}
