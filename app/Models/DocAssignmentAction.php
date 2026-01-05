<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocAssignmentAction extends Model
{
    //
    protected $fillable = [
        'document_assignments_id',
        'action',
        'performed_by'
    ];

}
