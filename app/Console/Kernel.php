<?php

namespace App\Console;

use App\Jobs\UpdateAllArtists;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel {
	/**
	 * Define the application's command schedule.
	 */
	protected function schedule(Schedule $schedule): void {
		// Update all artists
		// $schedule->command(FetchAllArtistsReleases::class, [true])->everyThirtyMinutes();
		$schedule->job(new UpdateAllArtists)->everyThirtyMinutes();
		$schedule->job(new UpdateAllArtists)->cron('33 * * * *');

		$schedule->call(function () {
			Log::info("Scheduling test 30m job at " . now(), [
				'date' => now(),
				'random' => rand(1, 100),
			]);
		})->everyThirtyMinutes();

		$schedule->call(function () {
			Log::info("Scheduling test job at " . now(), [
				'date' => now(),
				'random' => rand(1, 100),
			]);
		})->everyMinute();
	}

	/**
	 * Register the commands for the application.
	 */
	protected function commands(): void {
		$this->load(__DIR__ . '/Commands');

		require base_path('routes/console.php');
	}
}
