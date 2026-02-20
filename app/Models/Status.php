<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | DOCUMENT STATUSES
    |--------------------------------------------------------------------------
    */
    public const DOC_PENDING   = 1;
    public const DOC_ARCHIVED  = 2;
    public const DOC_COMPLETED = 3;
    public const DOC_DELAYED   = 4;
    public const DOC_RELEASED  = 5;

    /*
    |--------------------------------------------------------------------------
    | DOCUMENT ASSIGNMENT STATUSES
    |--------------------------------------------------------------------------
    */
    public const DOC_ASSIGN_PENDING   = 6;
    public const DOC_ASSIGN_COMPLETED = 7;
    public const DOC_ASSIGN_DELAYED   = 8;

    /*
    |--------------------------------------------------------------------------
    | DOCUMENT TRACKING STATUSES
    |--------------------------------------------------------------------------
    */
    public const DOC_TRACK_ROUTED    = 9;
    public const DOC_TRACK_COMPLETED = 10;
    public const DOC_TRACK_RETURNED  = 11;

    /*
    |--------------------------------------------------------------------------
    | DOCUMENT DRAFT STATUSES
    |--------------------------------------------------------------------------
    */
    public const DOC_DRAFT_PENDING   = 12;
    public const DOC_DRAFT_IN_REVIEW = 13;
    public const DOC_DRAFT_APPROVED  = 14;

    /*
    |--------------------------------------------------------------------------
    | STATUSES FOR SDS AND RECORDS
    |--------------------------------------------------------------------------
    */
    public const DOC_REJECTED = 15;
    public const DOC_RETURNED = 16;


    protected $fillable = [
        'module',
        'code',
        'label',
    ];


    protected static array $labels = [

        // document
        self::DOC_PENDING   => 'Pending',
        self::DOC_ARCHIVED  => 'Archived',
        self::DOC_COMPLETED => 'Completed',
        self::DOC_DELAYED   => 'Delayed',
        self::DOC_RELEASED  => 'Released',
        self::DOC_REJECTED  => 'Rejected',
        self::DOC_RETURNED  => 'Returned',

        // assignment
        self::DOC_ASSIGN_PENDING   => 'Pending',
        self::DOC_ASSIGN_COMPLETED => 'Completed',
        self::DOC_ASSIGN_DELAYED   => 'Delayed',

        // tracking
        self::DOC_TRACK_ROUTED    => 'Routed',
        self::DOC_TRACK_COMPLETED => 'Completed',
        self::DOC_TRACK_RETURNED  => 'Returned',

        // drafts
        self::DOC_DRAFT_PENDING   => 'Pending',
        self::DOC_DRAFT_IN_REVIEW => 'In Review',
        self::DOC_DRAFT_APPROVED  => 'Approved',
    ];


    public static function label(int $statusId): string
    {
        return self::$labels[$statusId] ?? 'Unknown';
    }
}
