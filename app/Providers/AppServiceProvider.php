<?php

namespace App\Providers;

use Carbon\CarbonInterval;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;

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
        // Define a per-user swipe rate limit: max 100 swipes per minute
        RateLimiter::for('swipes', function (Request $request) {
            $key = optional($request->user())->id ?? $request->ip();
            return Limit::perMinute(100)->by($key);
        });
    }
}
