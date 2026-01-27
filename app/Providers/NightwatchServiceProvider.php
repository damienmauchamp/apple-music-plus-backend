<?php

namespace App\Providers;

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
            Nightwatch::rejectQueuedJobs(function (QueuedJob $job) {
                return $job->name === 'App\\Jobs\\UpdateArtist';
            });
        }
    }
}
