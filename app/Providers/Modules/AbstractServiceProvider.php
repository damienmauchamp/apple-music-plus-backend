<?php

namespace App\Providers\Modules;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

abstract class AbstractServiceProvider extends ServiceProvider
{
    use ModuleResolver;

    public string $module = '';

    public ServiceProvider|string $routeServiceProvider;

    public function register(): void
    {
        $module = ucfirst($this->module ?: $this->resolveModuleName());
        $lowerModule = strtolower($module);

        // Load the Migrations
        $this->loadMigrationsFrom(
            $this->resolveModulePath('Database/Migrations', $module)
        );

        // Load the configurations
        if (file_exists($moduleConfiguration = $this->resolveModulePath('config/config.php', $module))) {
            $this->mergeConfigFrom($moduleConfiguration, "module::{$lowerModule}");
        }

        // Load the translations
        $this->loadTranslationsFrom(
            $this->resolveModulePath('lang', $module),
            config("module::{$lowerModule}.namespace", $lowerModule)
        );
        $this->loadTranslationsFrom(
            $this->resolveModulePath('lang', $module),
        );

        // Load resources
        $this->loadViewsFrom(
            $this->resolveModulePath('resources/views', $module),
            config("module::{$lowerModule}.namespace", $lowerModule)
        );

        if ($this->routeServiceProvider) {
            // Load the service provider for routes
            $this->app->register($this->routeServiceProvider);
        }
    }

    public function boot(): void { }

}
