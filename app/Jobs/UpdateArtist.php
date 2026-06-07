<?php

namespace App\Jobs;

use App\Services\Core\ReleasesUpdater;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;
use Laravel\Nightwatch\Facades\Nightwatch;
use Modules\Artist\Models\Artist;
use Throwable;

class UpdateArtist implements ShouldQueue // , ShouldBeUniqueUntilProcessing
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Artist $artist,
        public bool $echo = false
    ) {}

    //    public function uniqueId(): string
    //    {
    //        return $this->artist->storeId;
    //    }

    public function handle(): void
    {
        Context::add([
            'artist_id' => $this->artist->id,
            'artist_store_id' => $this->artist->storeId,
            'artist_name' => $this->artist->name,
        ]);

        Nightwatch::sample(rate: (float) config('app.nightwatch.update_artist_job_sample_rate', 0.01));

        $updater = new ReleasesUpdater($this->artist->storeId);

        $updater->updateArtist();
        $updater->update();

        $this->passed();
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Artist update job failed', ['exception' => $exception]);
        report($exception);

        if ($this->echo) {
            echo "❌ {$this->artist->name} ({$this->artist->storeId}) - ".$exception->getMessage()."\n";
        }
    }

    public function passed(): void
    {
        Log::info('Artist updated');

        if (! $this->echo) {
            echo "✅ {$this->artist->name} ({$this->artist->storeId})\n";
        }
    }
}
