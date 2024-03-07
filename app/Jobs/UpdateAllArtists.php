<?php

namespace App\Jobs;

use App\Models\Artist;
use App\Services\Core\ReleasesUpdater;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateAllArtists implements ShouldQueue {
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	public function __construct() {}

	public function handle(): void {
		Log::info("Lauching UpdateAllArtists job at " . now(), [
			'date' => now(),
		]);

		$artists = Artist::orderBy('name')->get();

		Log::info("Scheduling UpdateAllArtists job at " . now(), [
			'date' => now(),
			'artists' => count($artists),
		]);

		ReleasesUpdater::fromArtistArray($artists);
	}
}
