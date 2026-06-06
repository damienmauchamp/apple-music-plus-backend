<?php

namespace App\Providers;

use App\Jobs\UpdateArtist;
use Illuminate\Support\ServiceProvider;
use Laravel\Nightwatch\Facades\Nightwatch;
use Laravel\Nightwatch\Records\QueuedJob;

class NightwatchServiceProvider extends ServiceProvider
{
    public function register(): void
    {

    }

    public function boot(): void
    {
        if (config('app.nightwatch.update_artist_job_filter_queued_jobs')) {
            Nightwatch::rejectQueuedJobs(
                fn(QueuedJob $job) => $job->name === UpdateArtist::class
            );
        }
    }
}
