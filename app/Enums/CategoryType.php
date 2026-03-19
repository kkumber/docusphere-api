<?php 
namespace App\Enums;


enum CategoryType: string
{
    case ADVISORY = 'advisory';
    case ENDORSEMENT = 'endorsement';
    case MEMORANDUM = 'memorandum';
    case UNNUMBERED_MEMORANDUM = 'unnumbered_memorandum';

    public static function officialTimeline(string $category)
    {
        $dateToday = now();

        return match($category) {
            self::ADVISORY->value => $dateToday->addDays(3),
            self::ENDORSEMENT->value => $dateToday->addDays(7),
            self::MEMORANDUM->value => $dateToday->addDays(3),
            self::UNNUMBERED_MEMORANDUM->value => $dateToday->addDays(3),
            default => null
        };
    }
}



?>