<?php

namespace Ben182\Letterxpress;

use Illuminate\Support\ServiceProvider;

class LetterxpressServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('letterxpress.php'),
            ], 'config');
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'letterxpress');

        // Register the main class to use with the facade
        $this->app->singleton('letterxpress', function () {
            return new Letterxpress;
        });
    }
}
