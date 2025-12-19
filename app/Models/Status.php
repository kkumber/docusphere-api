<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    /** @use HasFactory<\Database\Factories\StatusFactory> */
    use HasFactory;

    public const DOC_PENDING = 1;
    public const DOC_ARCHIVED = 2;
    public const DOC_COMPLETED = 3;
    public const DOC_DELAYED = 4;
    public const DOC_RELEASED = 5;

    public const DOC_ASSIGN_PENDING = 6;
    public const DOC_ASSIGN_COMPLETED = 7;
    public const DOC_ASSIGN_DELAYED = 8;

    public const DOC_TRACK_ROUTED = 9;
    public const DOC_TRACK_COMPLETED = 10;
    public const DOC_TRACK_RETURNED = 11;
    public const DOC_TRACK_APPROVED = 12;

    protected $fillable = [
        'module',
        'code',
        'label',
    ];
}
