<?php

namespace App\Helpers;

use Carbon\Carbon;

class SystemHelper {

	public static function minReleaseDate(string $format = 'Y-m-d') {
		return (new Carbon(now()->subDays((int) env('RELEASE_DATA_RETENTION_DAYS'))))->format($format);
	}

	public static function getLastWeekDayNumber(): int {
		return (int) env('RELEASE_WEEKDAY', Carbon::FRIDAY);
	}

	public static function getLastWeekDayFromDate(?string $from = null, bool $weekBefore = false) {
		$date = $from ? Carbon::parse($from) : Carbon::now();
		if (intval($date->format('N')) == self::getLastWeekDayNumber() && !$weekBefore) {
			return $date;
		}

		return $date->previous(self::getLastWeekDayNumber());
	}

	public static function getLastFriday(?string $from = null) {
		return self::getLastWeekDayFromDate($from)->format('Y-m-d');
	}

	public static function defineWeeklyDate(?string $from = null, bool $weekly = false) {
		$from = $from ?? now()->format('Y-m-d');
		if ($weekly ?? false) {
			return SystemHelper::getLastWeekDayFromDate($from)->format('Y-m-d');
		}

		return $from ?: now()->subWeek()->format('Y-m-d');
	}
}
