<?php

namespace Modules\Auth\Providers;

use App\Providers\Modules\AbstractServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AuthModuleProvider extends AbstractServiceProvider
{
    public string $module = 'auth';

    public ServiceProvider|string $routeServiceProvider = AuthRouteServiceProvider::class;

    public function boot(): void {
        parent::boot();
        Relation::enforceMorphMap([
            'user' => \App\Models\User::class,
        ]);
    }
}
