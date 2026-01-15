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
    public const DOC_ASSIGN_REVIEWED      = 10;
    public const DOC_ASSIGN_RESPONDED     = 11;
    public const DOC_ASSIGN_COMPLETED     = 12;
    public const DOC_ASSIGN_DELAYED       = 13;


    // document_tracking NOTE: THE OTHER 3 ARE NOT IN USE MIGHT NEED TO BE REMOVED BUT KEEPING IT FOR NOW
    public const DOC_TRACK_ROUTED     = 14;
    public const DOC_TRACK_COMPLETED  = 15;
    public const DOC_TRACK_RETURNED   = 16;
    public const DOC_TRACK_APPROVED   = 17;

    // document drafts
    public const DOC_DRAFT_PENDING = 18;
    public const DOC_DRAFT_IN_REVIEW = 19;
    public const DOC_DRAFT_APPROVED = 20;
    


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

        // assignment
        self::DOC_ASSIGN_PENDING      => 'Pending',
        self::DOC_ASSIGN_ACKNOWLEDGED => 'Acknowledged',
        self::DOC_ASSIGN_APPROVED     => 'Approved',
        self::DOC_ASSIGN_SIGNED       => 'Signed',
        self::DOC_ASSIGN_REVIEWED     => 'Reviewed',
        self::DOC_ASSIGN_RESPONDED    => 'Responded',
        self::DOC_ASSIGN_COMPLETED    => 'Completed',
        self::DOC_ASSIGN_DELAYED      => 'Delayed',

        // tracking
        self::DOC_TRACK_ROUTED    => 'Routed',
        self::DOC_TRACK_COMPLETED => 'Completed',
        self::DOC_TRACK_RETURNED  => 'Returned',
        self::DOC_TRACK_APPROVED  => 'Approved',

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
