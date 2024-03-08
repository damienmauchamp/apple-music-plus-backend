<?php

namespace App\Helpers;

use Carbon\Carbon;
use DateTimeZone;

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

	public static function storeFrontdateTime(?string $date = null) {
		$date = new Carbon($date ?? now(), new DateTimeZone(env('TIMEZONE')));
		$date->setTimezone(new DateTimeZone(env('AM_STOREFRONT_TIMEZONE')));

		return $date;
	}
}
