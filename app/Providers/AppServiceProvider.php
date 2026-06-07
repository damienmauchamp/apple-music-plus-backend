<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Modules\Album\Models\Album;
use Modules\Artist\Models\Artist;
use Modules\Song\Models\Song;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
        Schema::defaultStringLength(191);

        // Model::shouldBeStrict(! app()->isProduction());

        // Register morph map for polymorphic relations
        // Enforce morph map to use specific class names for polymorphic relations
        // This is useful for avoiding issues with class name changes or module reorganization
        Relation::enforceMorphMap([
            'artist' => Artist::class,
            'album' => Album::class,
            'song' => Song::class,
        ]);

        Request::macro('artist', fn () => $this->get('artist')
            ?? Artist::where('id', $this->header('artist'))->first()
        );
    }
}
