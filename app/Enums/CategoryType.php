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

        $workingDays = match($category) {
            self::ADVISORY->value => 3,
            self::ENDORSEMENT->value => 7,
            self::MEMORANDUM->value => 3,
            self::UNNUMBERED_MEMORANDUM->value => 3,
            default => null
        };

        if ($workingDays === null) {
            return null;
        }

        return self::addWorkingDays($dateToday, $workingDays);
    }

    private static function addWorkingDays(\Carbon\Carbon $date, int $days): \Carbon\Carbon
    {
        $current = $date->copy();
        $added = 0;

        while ($added < $days) {
            $current->addDay();

            if (!$current->isWeekend()) {
                $added++;
            }
        }

        return $current;
    }
}



?>