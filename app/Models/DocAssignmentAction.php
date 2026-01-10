<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocAssignmentAction extends Model
{
    //
    protected $fillable = [
        'document_assignment_id',
        'action',
        'performed_by'
    ];



    public function documentAssignment()
    {
        return $this->belongsTo(DocumentAssignment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
