<?php

namespace App\Helpers;

use Carbon\Carbon;

class SystemHelper {

	public static function minReleaseDate(string $format = 'Y-m-d') {
		return (new Carbon(now()->subDays((int) env('RELEASE_DATA_RETENTION_DAYS'))))->format($format);
	}
}
