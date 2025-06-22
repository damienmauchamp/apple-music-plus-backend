<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\Request;

class DateRangeService
{
    public static function resolveWeeklyRange(string $from, string $startDay = 'friday'): array
    {
        $fromDate = Carbon::parse($from);

        $weekStartIndex = Carbon::parse("next $startDay")->dayOfWeek;

        $currentDayIndex = $fromDate->dayOfWeek;

        $daysToSubtract = ($currentDayIndex - $weekStartIndex + 7) % 7;
        $startOfWeek = (clone $fromDate)->subDays($daysToSubtract);
        $endOfWeek = (clone $startOfWeek)->addDays(6);

        return [
            'from' => $startOfWeek->startOfDay()->toDateString(),
            'to'   => $endOfWeek->endOfDay()->toDateString(),
        ];
    }
}
