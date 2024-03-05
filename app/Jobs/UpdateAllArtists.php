<?php

namespace App\Jobs;

use App\Models\Artist;
use App\Services\Core\ReleasesUpdater;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateAllArtists implements ShouldQueue {
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	public function __construct() {}

	public function handle(): void {
		$artists = Artist::orderBy('name')->get();

		ReleasesUpdater::fromArtistArray($artists);
	}
}
