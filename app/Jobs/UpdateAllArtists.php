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

	public function __construct(
		public bool $useJob = true
	) {}

	public function handle(): void {

		$artists = Artist::orderBy('name')->get();

        Log::channel('logs.artists-update')
           ->info('Scheduling job for '.count($artists).' artists');

		ReleasesUpdater::fromArtistArray($artists, $this->useJob);
	}

    public function fail($exception = null): void
    {
        Log::channel('logs.artists-update')
            ->error("Job failed: {$exception->getMessage()}", [
                'exception' => $exception,
            ]);

        parent::fail($exception);
    }
}
