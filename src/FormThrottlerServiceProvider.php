<?php

namespace Ghobaty\FormThrottler;

use Illuminate\Cache\RateLimiter;
use Illuminate\Support\ServiceProvider;

class FormThrottlerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/form-throttler.php' => config_path('form-throttler.php'),
        ], 'config');

        $this->app->bind(FormThrottler::class, function () {
            return new FormThrottler(
                $this->app->get('config')->get('form-throttler'),
                $this->app->get('request'),
                $this->app->make(RateLimiter::class),
                $this->app->get('events'),
                $this->app->get('translator')
            );
        });
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/form-throttler.php', 'form-throttler');
    }
}
