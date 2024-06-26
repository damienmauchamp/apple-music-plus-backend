<?php

namespace App\Console;

use App\Jobs\UpdateAllArtists;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel {
	/**
	 * Define the application's command schedule.
	 */
	protected function schedule(Schedule $schedule): void {
		// Update all artists
		// $schedule->command(FetchAllArtistsReleases::class, [true])->everyThirtyMinutes();
		$schedule->job(new UpdateAllArtists)->everyThirtyMinutes();

		// clearing expired cache
		$schedule->command('cache:clear-expired')->dailyAt('01:00');
	}

	/**
	 * Register the commands for the application.
	 */
	protected function commands(): void {
		$this->load(__DIR__ . '/Commands');

		require base_path('routes/console.php');
	}
}
