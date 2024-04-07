<?php

namespace App\Console\Commands;

use App\Models\Artist;
use App\Services\Core\ReleasesUpdater;
use Exception;
use Illuminate\Console\Command;

class FetchAllArtistsReleases extends Command {

	protected $signature = 'app:fetch-all-artists {job=0}';
	protected $description = 'Fetch all artists releases';

	public function handle() {

		$job = (bool) $this->argument('job');

		// getting all artists linked to at least one user
		// $artists = Artist::whereHas('users')->orderBy('name')->get();
		$artists = Artist::orderBy('name')->get();

		$this->info($job ? "Creating jobs for " . count($artists) . " artists" : "Fetching releases for " . count($artists) . " artists");

		try {
			$data = ReleasesUpdater::fromArtistArray($artists, (bool) $job);
		} catch (Exception $exception) {
			$this->error("Something went wrong : " . $exception->getMessage());

			return;
		}

		$this->info($job ? "Jobs created for " . count($artists) . " artists" : "{$data['results_count']} artists updated");
		if ($data['errors_count']) {
			$this->error("Errors ({$data['errors_count']}) : ");
			foreach ($data['errors'] as $error) {
				$this->error(sprintf("- %s (%s) : %s",
					$error['artist']->name,
					$error['artist']->storeId,
					$error['error']));
			}
		}
	}
}
