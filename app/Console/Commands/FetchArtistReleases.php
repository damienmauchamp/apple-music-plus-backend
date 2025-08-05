<?php

namespace App\Console\Commands;

use App\Services\Core\ReleasesUpdater;
use Exception;
use Illuminate\Console\Command;
use Modules\Artist\Models\Artist;

class FetchArtistReleases extends Command {

	protected $signature = 'app:fetch-artist {storeId : artist\'s storeId} {job=0}';
	protected $description = 'Fetch an artist releases';

	public function handle() {

		$storeId = $this->argument('storeId');
		$job = (bool) $this->argument('job');
		$artist = Artist::where('storeId', $storeId)->first();

		if (!$artist) {
			$this->error("Artist not found (storeId: $storeId)");

			return;
		}

		$this->info($job ? "Creating job for $artist->name" : "Fetching releases for $artist->name");

		try {
			$updater = new ReleasesUpdater($artist->storeId, $job, true, true);
			$updater->update();

		} catch (Exception $exception) {
			$this->error("Something went wrong : " . $exception->getMessage());

			return;
		}

		$this->info($job ? "Job created for $artist->name" : "$artist->name updated");
	}
}
