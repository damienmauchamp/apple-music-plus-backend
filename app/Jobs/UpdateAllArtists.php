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

class UpdateAllArtists implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private readonly bool $enableLogging;

    public function __construct(
        public bool $useJob = true
    )
    {
    }

    public function handle(): void
    {

        $artists = Artist::orderBy('name')->get();

        if (config('app.releases_updater.enable_logs', false)) {
            Log::channel('jobs.artists-update')
                ->info('Scheduling job for ' . count($artists) . ' artists');
        }

        ReleasesUpdater::fromArtistArray($artists, $this->useJob);
    }

    public function failed($exception = null): void
    {
        if (config('app.releases_updater.enable_logs', false)) {
            Log::channel('jobs.artists-update')
                ->error("Job failed: {$exception->getMessage()}", [
                    'exception' => $exception,
                ]);
        }
    }
}
