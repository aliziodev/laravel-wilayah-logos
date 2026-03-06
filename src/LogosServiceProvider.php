<?php

namespace Aliziodev\WilayahLogos;

use Aliziodev\WilayahLogos\Commands\LogosPublishCommand;
use Illuminate\Support\ServiceProvider;

class LogosServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Publish asset logo ke public/vendor/wilayah/logos/
        $this->publishes([
            __DIR__.'/../assets/' => public_path('vendor/wilayah/logos'),
        ], 'wilayah-logos');

        if ($this->app->runningInConsole()) {
            $this->commands([LogosPublishCommand::class]);
        }
    }
}
