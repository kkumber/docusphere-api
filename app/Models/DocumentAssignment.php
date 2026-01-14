<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentAssignment extends Model
{
    /** @use HasFactory<\Database\Factories\DocumentAssignmentFactory> */
    use HasFactory;

    protected $fillable = [
        'document_id',
        'assigned_by',
        'assigned_to',
        'request_type',
        'instructions',
        'due_date',
        'status_id',
        'completion_date'
    ];

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assigner()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    public function actions()
    {
        return $this->hasMany(DocAssignmentAction::class);
    }
}
