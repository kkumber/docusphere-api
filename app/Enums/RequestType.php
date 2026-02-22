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

    public static function requiredActions(string $requestType): array
    {
        return match ($requestType) {
            self::FOR_SIGNATURE->value    => [Actions::SIGNED->value],
            self::FOR_APPROVAL->value     => [Actions::APPROVED->value, Actions::REJECTED->value, Actions::REVIEWED->value],
            self::FOR_REVIEW->value       => [Actions::REVIEWED->value],
            self::FOR_RESPONSE->value     => [Actions::RESPONDED->value],
            self::FOR_ACKNOWLEDGE->value  => [Actions::ACKNOWLEDGED->value],
            self::FOR_ISSUANCE->value     => [Actions::APPROVED->value, Actions::REJECTED->value],
            default => [], 
        };
    }
}




?>