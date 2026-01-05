<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    /** @use HasFactory<\Database\Factories\StatusFactory> */
    use HasFactory;

   // document
    public const DOC_PENDING   = 1;
    public const DOC_ARCHIVED  = 2;
    public const DOC_COMPLETED = 3;
    public const DOC_DELAYED   = 4;
    public const DOC_RELEASED  = 5;


    // document_assignment
    public const DOC_ASSIGN_PENDING       = 6;
    public const DOC_ASSIGN_ACKNOWLEDGED  = 7;
    public const DOC_ASSIGN_APPROVED      = 8;
    public const DOC_ASSIGN_SIGNED        = 9;
    public const DOC_ASSIGN_COMPLETED     = 10;
    public const DOC_ASSIGN_DELAYED       = 11;

        
    // document_tracking
    public const DOC_TRACK_ROUTED     = 12;
    public const DOC_TRACK_COMPLETED  = 13;
    public const DOC_TRACK_RETURNED   = 14;
    public const DOC_TRACK_APPROVED   = 15;


    protected $fillable = [
        'module',
        'code',
        'label',
    ];
}
