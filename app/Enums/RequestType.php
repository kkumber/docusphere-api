<?php 
namespace App\Enums;

enum RequestType: string
{
    case FOR_SIGNATURE   = 'for_signature';
    case FOR_APPROVAL    = 'for_approval';
    case FOR_REVIEW      = 'for_review';
    case FOR_RESPONSE    = 'for_response';
    case FOR_ACKNOWLEDGE = 'for_acknowledgement';
    case FOR_ISSUANCE    = 'for_issuance';

    public function label(): string
    {
        return match ($this) {
            self::FOR_SIGNATURE   => 'For Signature',
            self::FOR_APPROVAL    => 'For Approval',
            self::FOR_REVIEW      => 'For Review',
            self::FOR_RESPONSE    => 'For Response',
            self::FOR_ACKNOWLEDGE => 'For Acknowledgement',
            self::FOR_ISSUANCE    => 'For Issuance',
        };
    }

    public static function requiredActions($requestType)
    {
        switch ($requestType) {
            case self::FOR_SIGNATURE:
                return [Actions::SIGNED->value];
            case self::FOR_APPROVAL:
                return [Actions::APPROVED->value, Actions::REJECTED->value, Actions::REVIEWED->value];
            case self::FOR_REVIEW:
                return [Actions::REVIEWED->value];
            case self::FOR_RESPONSE:
                return [Actions::RESPONDED->value];
            case self::FOR_ACKNOWLEDGE:
                return [Actions::ACKNOWLEDGED->value];
            case self::FOR_ISSUANCE:
                return [Actions::APPROVED->value, Actions::REJECTED->value];
        }
    }
}




?>