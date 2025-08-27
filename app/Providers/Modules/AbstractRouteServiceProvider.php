<?php

namespace App\Providers\Modules;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Route;

abstract class AbstractRouteServiceProvider extends RouteServiceProvider
{
    use ModuleResolver;

    public string $module = '';

    public ?string $modulePrefix = null;

    public ?string $moduleNamespace = null;

    public const string MODULE_SUFFIX = '';

    public function boot(): void
    {
        $module = $this->module ?: $this->resolveModuleName();
        $modulePrefix = sprintf(
            "api/%s",
            $this->modulePrefix ?: config(
                "module::{$module}.route.prefix",
                lcfirst($module) . static::MODULE_SUFFIX
            )
        );
        // $moduleNamespace = $this->moduleNamespace ?: config("module::{$module}.module.namespace", "");
        $moduleNamespace = $this->moduleNamespace ?: config("module::{$module}.module.namespace", ucfirst($module));

        $this->routes(function () use ($module, $modulePrefix, $moduleNamespace) {

            // Load API routes
            $this->loadApiRoutes(
                $module,
                $modulePrefix,
                $moduleNamespace
            );

            // Load Console routes
            $this->loadConsoleRoutes(
                $module,
                $modulePrefix,
                $moduleNamespace
            );

            // Load Web routes
            $webModulePrefix = config(
                "module::{$module}.route.prefix",
                // "module::{$module}.route.web_prefix",
                $modulePrefix
            );
            $this->loadWebRoutes(
                $module,
                $webModulePrefix,
                $moduleNamespace
            );
        });
    }

    protected function loadApiRoutes(string $module, string $prefix, string $namespace): void
    {
        $apiRoutesPath = $this->resolveModulePath(
            'routes/api.php',
            $module
        );

        if (file_exists($apiRoutesPath)) {
//            Route::name("{$namespace}::")
            Route::prefix($prefix)
                 ->namespace($namespace)
                 ->middleware('api')
                 ->group($apiRoutesPath);
        }
    }

    protected function loadConsoleRoutes(string $module, string $prefix, string $namespace): void
    {
        $consoleRoutesPath = $this->resolveModulePath(
            'routes/console.php',
            $module
        );

        if (file_exists($consoleRoutesPath)) {
            Route::prefix($prefix)
                 ->namespace($namespace)
                 ->middleware('console')
                 ->group($consoleRoutesPath);
        }
    }

    protected function loadWebRoutes(string $module, string $prefix, string $namespace): void
    {
        $webRoutesPath = $this->resolveModulePath(
            'routes/web.php',
            $module
        );

        if (file_exists($webRoutesPath)) {
            Route::prefix($prefix)
                 ->namespace($namespace)
                 ->middleware('web')
                 ->group($webRoutesPath);
        }
    }


}
