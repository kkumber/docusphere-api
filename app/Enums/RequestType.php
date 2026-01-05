<?php 
namespace App\Enums;

enum RequestType: string
{
    case FOR_SIGNATURE   = 'for_signature';
    case FOR_APPROVAL    = 'for_approval';
    case FOR_REVIEW      = 'for_review';
    case FOR_RESPONSE    = 'for_response';
    case FOR_ACKNOWLEDGE = 'for_acknowledgement';

    public function label(): string
    {
        return match ($this) {
            self::FOR_SIGNATURE   => 'For Signature',
            self::FOR_APPROVAL    => 'For Approval',
            self::FOR_REVIEW      => 'For Review',
            self::FOR_RESPONSE    => 'For Response',
            self::FOR_ACKNOWLEDGE => 'For Acknowledgement',
        };
    }
}




?>