<?php

namespace App\Jobs;

use App\Models\Artist;
use App\Services\Core\ReleasesUpdater;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class UpdateArtist implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private readonly bool $enableLogging;

    public function __construct(
        public Artist $artist,
        public bool $echo = false
    )
    {
        $this->enableLogging = config('app.releases_updater.enable_logs', false);
    }

    public function uniqueId(): string
    {
        return $this->artist->storeId;
    }

    public function handle(): void
    {
        // Log::channel('jobs.artist-update')
        //    ->info("({$this->artist->storeId}) {$this->artist->name}: Updating...");

        $updater = new ReleasesUpdater($this->artist->storeId);

        // fetching artist info
        $updater->updateArtist();

        // fetching albums & songs
        $updater->update();

        $this->passed();
    }

    public function failed(?Throwable $exception): void
    {
        if ($this->enableLogging) {
            Log::channel('jobs.artist-update')
                ->error("({$this->artist->storeId}) {$this->artist->name}: ❌ Job failed - {$exception->getMessage()}", [
                    'exception' => $exception,
                ]);
        }

        if ($this->echo) {
            echo "❌ {$this->artist->name} ({$this->artist->storeId}) - " . $exception->getMessage() . "\n";
        }
    }

    public function passed(): void
    {
        if ($this->enableLogging) {
            Log::channel('jobs.artist-update')
                ->info("({$this->artist->storeId}) {$this->artist->name}: Updated");
        }

        if (!$this->echo) {
            echo "✅ {$this->artist->name} ({$this->artist->storeId})\n";
        }
    }
}
