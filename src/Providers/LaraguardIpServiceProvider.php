<?php

namespace Haxneeraj\LaraguardIp\Providers;

use Haxneeraj\LaraguardIp\Console\Commands\LaraguardIpCommand;
use Haxneeraj\LaraguardIp\Http\Middleware\LaraguardIpMiddleware;
use Illuminate\Support\ServiceProvider;

class LaraguardIpServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        \Log::info('LaraguardIpServiceProvider boot');
        // Load config
        $this->publishes([
            __DIR__.'/../../config/laraguard-ip.php' => config_path('laraguard-ip.php'),
        ], 'config');

        $this->publishes([
            __DIR__.'/../../laraguard-ip.json' => config('laraguard-ip.storage.path'),
        ], 'laravel-abuse-ip');

        if ($this->app->runningInConsole()) {
            $this->commands([
                LaraguardIpCommand::class,
            ]);
        }

        $this->app['router']->aliasMiddleware('laraguard-ip', LaraguardIpMiddleware::class);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        // Register any application services here if needed
    }
}
