<?php

namespace Ouodev\Prerender;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\ServiceProvider;

class PrerenderServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/prerender.php', 'prerender');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if (app()->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/prerender.php' => config_path('prerender.php'),
            ], 'prerender');
        }

        if (!app()->runningInConsole() && config('prerender.enabled')) {
            app()->make(Kernel::class)->pushMiddleware(Prerender::class);
        }
    }
}
